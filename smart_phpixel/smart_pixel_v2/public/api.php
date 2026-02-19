<?php
// public/api.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

// 1. Récupérer les paramètres
$siteId = $_GET['site_id'] ?? null;
$apiKey = $_GET['api_key'] ?? null;
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// 2. Vérifier les paramètres obligatoires
if (!$siteId || !$apiKey) {
    http_response_code(400);
    echo json_encode(['error' => 'Les paramètres site_id et api_key sont requis.']);
    exit;
}

// 3. Connexion à la base de données
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
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur de connexion à la base de données',
        'details' => 'Hôte: ' . DB_HOST . ', Base: ' . DB_NAME . ', Erreur: ' . $e->getMessage()
    ]);
    exit;
}


// 4. Vérifier le site et la clé API
try {
    $stmt = $pdo->prepare("
        SELECT us.id as site_id, us.user_id
        FROM user_sites us
        JOIN users u ON us.user_id = u.id
        WHERE us.tracking_code = ? AND u.api_key = ?
    ");
    $stmt->execute([$siteId, $apiKey]);
    $site = $stmt->fetch();

    if (!$site) {
        http_response_code(403);
        echo json_encode(['error' => 'Site non trouvé ou clé API invalide.']);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur lors de la vérification du site',
        'details' => $e->getMessage()
    ]);
    exit;
}

// 5. Récupérer les données de tracking
try {
    $stmt = $pdo->prepare("
        SELECT
            DATE(timestamp) as date,
            COUNT(*) as visits,
            COUNT(DISTINCT ip_address) as unique_visitors
        FROM smart_pixel_tracking
        WHERE site_id = :site_id
        AND DATE(timestamp) BETWEEN :start_date AND :end_date
        GROUP BY DATE(timestamp)
        ORDER BY date ASC
    ");
    $stmt->execute([
        'site_id' => $site['site_id'],  // Utilise l'ID numérique du site
        'start_date' => $startDate,
        'end_date' => $endDate
    ]);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        'success' => true,
        'data' => $results,
        'meta' => [
            'site_id' => $siteId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_visits' => array_sum(array_column($results, 'visits')),
            'total_unique_visitors' => array_sum(array_column($results, 'unique_visitors'))
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur lors de la récupération des données',
        'details' => $e->getMessage()
    ]);
}
?>
