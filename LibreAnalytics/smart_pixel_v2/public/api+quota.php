<?php
// public/api.php — API publique LibreAnalytics
// Accessible à TOUS les plans (free inclus) : aucune restriction par plan ici.
// La différenciation free/payant se fait par QUOTA, pas par blocage.
//
// IMPORTANT : plus de require auth.php — un endpoint public ne doit dépendre
// ni de la session, ni d'une redirection, sinon cURL/ESP32/fetch() externe reçoit du HTML.
require_once __DIR__ . '/../includes/config.php';

/* Table de quota (pas encore integré !!!) :
CREATE TABLE api_usage (
    api_key VARCHAR(64) NOT NULL,
    day     DATE NOT NULL,
    calls   INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (api_key, day)
) ENGINE=InnoDB;
*/
const API_DAILY_QUOTA = ['free' => 100, 'pro' => 10000, 'business' => 100000];
const API_DAILY_QUOTA_DEFAULT = 1000;   // plan inconnu : valeur médiane
const API_MAX_RANGE_DAYS = 366;         // évite de vider toute la table d'un coup

// ---------- Réponse JSON + CORS ----------
// CORS est indispensable : dashboards externes (CodePen, sites perso) lisent la
// réponse via fetch(). Sans ce header, le navigateur bloque la donnée.
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // ou restreindre aux domaines de tes users
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: X-Api-Key, Authorization');
header('Vary: Origin');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function apiError(int $code, string $message): void
{
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

// ---------- 1. Paramètres ----------
// Clé acceptée en query string (rétrocompatible) OU en header (plus propre :
// une clé en URL finit dans les logs serveur, les referrers et l'historique).
$siteId = $_GET['site_id'] ?? null;
$apiKey = $_GET['api_key']
    ?? $_SERVER['HTTP_X_API_KEY']
    ?? (isset($_SERVER['HTTP_AUTHORIZATION']) ? preg_replace('/^Bearer\s+/i', '', $_SERVER['HTTP_AUTHORIZATION']) : null);

$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

if (!$siteId || !$apiKey) {
    apiError(400, 'Les paramètres site_id et api_key sont requis.');
}

// Validation des dates : sans ça, une date absurde passe silencieusement en SQL.
$start = DateTime::createFromFormat('Y-m-d', $startDate);
$end = DateTime::createFromFormat('Y-m-d', $endDate);
if (!$start || !$end) {
    apiError(400, 'Dates invalides. Format attendu : YYYY-MM-DD.');
}
if ($start > $end) {
    apiError(400, 'start_date doit être antérieure ou égale à end_date.');
}
if ($start->diff($end)->days > API_MAX_RANGE_DAYS) {
    apiError(400, 'Plage trop large. Maximum ' . API_MAX_RANGE_DAYS . ' jours.');
}
// Fin de période exclusive : permet à MySQL d'utiliser l'index sur timestamp
// (DATE(timestamp) BETWEEN ... ignore l'index et scanne toute la table).
$endExclusive = (clone $end)->modify('+1 day')->format('Y-m-d');

// ---------- 2. Connexion ----------
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('[api.php] Connexion DB : ' . $e->getMessage()); // log serveur, jamais au client
    apiError(500, 'Erreur interne. Réessayez plus tard.');
}

// ---------- 3. Vérifier le site + la clé, et lire le plan ----------
// NB : si un utilisateur free a api_key = NULL en base (générée seulement à
// l'inscription payante ?), le JOIN ne matche jamais => 403 trompeur.
// Fix côté inscription : générer la clé pour TOUS les comptes à la création.
try {
    $stmt = $pdo->prepare("
        SELECT us.id AS site_id, us.user_id, u.plan
        FROM user_sites us
        JOIN users u ON us.user_id = u.id
        WHERE us.tracking_code = ? AND u.api_key = ?
    ");
    $stmt->execute([$siteId, $apiKey]);
    $site = $stmt->fetch();

    if (!$site) {
        apiError(403, 'Site non trouvé ou clé API invalide.');
    }

    // ---------- 4. Quota par plan (remplace le blocage par plan) ----------
    $plan = strtolower($site['plan'] ?? 'free');
    $quota = API_DAILY_QUOTA[$plan] ?? API_DAILY_QUOTA_DEFAULT;

    $stmt = $pdo->prepare("
        INSERT INTO api_usage (api_key, day, calls)
        VALUES (?, CURDATE(), 1)
        ON DUPLICATE KEY UPDATE calls = calls + 1
    ");
    $stmt->execute([$apiKey]);

    $stmt = $pdo->prepare("SELECT calls FROM api_usage WHERE api_key = ? AND day = CURDATE()");
    $stmt->execute([$apiKey]);
    $calls = (int) ($stmt->fetchColumn() ?: 0);

    if ($calls > $quota) {
        apiError(429, 'Quota journalier atteint pour votre plan (' . $quota . ' requêtes/jour).');
    }
} catch (PDOException $e) {
    // Si la table api_usage n'existe pas encore : on loggue et on laisse passer
    // plutôt que de casser l'API (créez la table avec le SQL en tête de fichier).
    error_log('[api.php] Quota : ' . $e->getMessage());
}

// ---------- 5. Données de tracking ----------
try {
    $stmt = $pdo->prepare("
        SELECT
            DATE(timestamp)      AS date,
            COUNT(*)             AS visits,
            COUNT(DISTINCT ip_address) AS unique_visitors
        FROM smart_pixel_tracking
        WHERE site_id = :site_id
          AND timestamp >= :start_date
          AND timestamp <  :end_exclusive
        GROUP BY DATE(timestamp)
        ORDER BY date ASC
    ");
    $stmt->execute([
        'site_id' => $site['site_id'],   // ID numérique, pas le tracking_code
        'start_date' => $start->format('Y-m-d'),
        'end_exclusive' => $endExclusive,
    ]);

    $results = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $results,
        'meta' => [
            'site_id' => $siteId,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'total_visits' => array_sum(array_column($results, 'visits')),
            'total_unique_visitors' => array_sum(array_column($results, 'unique_visitors')),
            'quota' => [
                'plan' => $plan,
                'daily_limit' => $quota,
                'calls_today' => $calls,
                'calls_left' => max(0, $quota - $calls),
            ],
        ],
    ]);
} catch (PDOException $e) {
    error_log('[api.php] Requête tracking : ' . $e->getMessage());
    apiError(500, 'Erreur interne. Réessayez plus tard.');
}