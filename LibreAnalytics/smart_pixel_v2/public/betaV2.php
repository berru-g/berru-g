<?php
session_start();
// Dashboard - beta V2 regroupant :
// - l'analytics de mon soft LibreAnalytics, 
// - l'analytics de mon site perso
// - API coingeck pour les prix crypto (SOL, SUI, AMI, USDC) de mes différents wallet et exchange réunis en un seul
// - API de TradingView pour la chart et les signaux globaux
// - Embed de mon agenda Google Calendar (planning perso)
// - API météo 
// - API Wikipedia pour des info utiles
// - CMD pour envoyer, un rapport à mes clients (emails, stats, etc.) ou un mail de prospection auto (cron)
// par berru-g de la V0.1 en 2020 puis avec Mistral pour les version suivantes (2026)


// --- Vérification de l'authentification ---
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (!Auth::isLoggedIn() || ($_SESSION['user_email'] ?? '') !== 'contact@gael-berru.com') {
    header('Location: account.php');
    exit;
}

// --- Connexion à la base de données ---
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    die("Erreur de connexion à la base de données. Réessayez plus tard.");
}

// ================================================================
// PÉRIODE SÉLECTIONNÉE (7 / 30 / 90 / 365 jours) — pilote les requêtes
// ================================================================
$period = (int) ($_GET['period'] ?? 365);
if (!in_array($period, [7, 30, 90, 365], true)) {
    $period = 365;
}

// --- Récupération des données ---
try {
    // 1) Série journalière sur la période (avec jours à zéro)
    //    Nombres 0..99 générés par produit cartésien -> compatible MySQL 5.x et 8.x
    $stmt = $pdo->prepare("
        SELECT
            DATE(CURDATE() - INTERVAL n DAY) AS date,
            (SELECT COUNT(*) FROM users              WHERE DATE(created_at) = DATE(CURDATE() - INTERVAL n DAY)) AS new_users,
            (SELECT COUNT(*) FROM user_sites         WHERE DATE(created_at) = DATE(CURDATE() - INTERVAL n DAY)) AS new_sites,
            (SELECT COUNT(*) FROM smart_pixel_tracking WHERE DATE(timestamp) = DATE(CURDATE() - INTERVAL n DAY)) AS visits,
            (SELECT COUNT(DISTINCT ip_address) FROM smart_pixel_tracking WHERE DATE(timestamp) = DATE(CURDATE() - INTERVAL n DAY)) AS unique_visitors
        FROM (
            SELECT a.i + b.i * 10 AS n
            FROM (SELECT 0 i UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
                  UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a
            CROSS JOIN
                 (SELECT 0 i UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
                  UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) b
        ) AS nums
        WHERE n < :days
        ORDER BY date ASC
    ");
    $stmt->bindValue(':days', $period, PDO::PARAM_INT);
    $stmt->execute();
    $dailySeries = $stmt->fetchAll();

    // 2) Totaux période courante vs période précédente (pour les deltas des KPI)
    $stmt = $pdo->prepare("
        SELECT
            (SELECT COUNT(*) FROM users WHERE created_at >= CURDATE() - INTERVAL :d1 DAY) AS cur_users,
            (SELECT COUNT(*) FROM users WHERE created_at >= CURDATE() - INTERVAL :d2 DAY AND created_at < CURDATE() - INTERVAL :d1 DAY) AS prev_users,
            (SELECT COUNT(*) FROM user_sites WHERE created_at >= CURDATE() - INTERVAL :d3 DAY) AS cur_sites,
            (SELECT COUNT(*) FROM user_sites WHERE created_at >= CURDATE() - INTERVAL :d4 DAY AND created_at < CURDATE() - INTERVAL :d3 DAY) AS prev_sites,
            (SELECT COUNT(*) FROM smart_pixel_tracking WHERE timestamp >= CURDATE() - INTERVAL :d5 DAY) AS cur_visits,
            (SELECT COUNT(*) FROM smart_pixel_tracking WHERE timestamp >= CURDATE() - INTERVAL :d6 DAY AND timestamp < CURDATE() - INTERVAL :d5 DAY) AS prev_visits,
            (SELECT COUNT(DISTINCT ip_address) FROM smart_pixel_tracking WHERE timestamp >= CURDATE() - INTERVAL :d7 DAY) AS cur_uniques,
            (SELECT COUNT(DISTINCT ip_address) FROM smart_pixel_tracking WHERE timestamp >= CURDATE() - INTERVAL :d8 DAY AND timestamp < CURDATE() - INTERVAL :d7 DAY) AS prev_uniques
    ");
    for ($i = 1; $i <= 8; $i++) {
        $stmt->bindValue(':d' . $i, $i % 2 === 1 ? $period : $period * 2, PDO::PARAM_INT);
    }
    $stmt->execute();
    $periodTotals = $stmt->fetch() ?: [];

    // 3) Nombre total de sites et d'utilisateurs (globaux)
    $totalSites = (int) $pdo->query("SELECT COUNT(*) FROM user_sites")->fetchColumn();
    $totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // 4) Top 5 des sites
    $stmt = $pdo->query("
        SELECT s.site_name, s.id, COUNT(t.id) AS total_visits
        FROM user_sites s
        LEFT JOIN smart_pixel_tracking t ON s.id = t.site_id
        GROUP BY s.id
        ORDER BY total_visits DESC
        LIMIT 5
    ");
    $topSites = $stmt->fetchAll();

    // 5) Pays visités (top 10)
    $stmt = $pdo->query("
        SELECT country, COUNT(*) AS visits
        FROM smart_pixel_tracking
        WHERE country IS NOT NULL
        GROUP BY country
        ORDER BY visits DESC
        LIMIT 10
    ");
    $visitedCountries = $stmt->fetchAll();

    // 6) Liste des utilisateurs
    $stmt = $pdo->query("
        SELECT
            u.id, u.email, u.created_at, u.plan, u.last_login,
            COUNT(s.id) AS site_count,
            (SELECT COUNT(*) FROM smart_pixel_tracking t WHERE t.site_id IN (SELECT id FROM user_sites WHERE user_id = u.id)) AS total_visits
        FROM users u
        LEFT JOIN user_sites s ON u.id = s.user_id
        GROUP BY u.id
        ORDER BY site_count DESC
    ");
    $usersList = $stmt->fetchAll();

    // 7) Statistiques par plan
    $stmt = $pdo->query("SELECT plan, COUNT(*) AS count FROM users GROUP BY plan");
    $plansStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    error_log('Data fetch failed: ' . $e->getMessage());
    die("Erreur lors de la récupération des données. Réessayez plus tard.");
}

// Delta % période courante vs précédente (null si pas de référence)
function deltaPct($cur, $prev): ?float
{
    if ((int) $prev <= 0) {
        return null;
    }
    return round(((int) $cur - (int) $prev) / (int) $prev * 100, 1);
}

// --- Export des emails ---
if (isset($_GET['export_emails'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="smartpixel_users_emails.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8 pour Excel
    fputcsv($out, ['Email', 'Plan', 'Nombre de sites', "Date d'inscription", 'Derniere connexion']);
    foreach ($usersList as $user) {
        fputcsv($out, [
            $user['email'],
            strtoupper($user['plan'] ?? 'free'),
            $user['site_count'],
            $user['created_at'],
            $user['last_login'] ?? 'Jamais'
        ]);
    }
    fclose($out);
    exit;
}

// ================================================================
// PRIX CRYPTO — CoinGecko côté serveur (clé privée + cache 5 min)
// ================================================================
$coingeckoData = null;

if (defined('COINGECKO_API_KEY') && COINGECKO_API_KEY !== '' && COINGECKO_API_KEY !== 'TA_CLE_DEMO_ICI') {
    $coingeckoCache = __DIR__ . '/cache_coingecko.json';

    if (is_file($coingeckoCache) && (time() - filemtime($coingeckoCache)) < 300) {
        $coingeckoData = json_decode(file_get_contents($coingeckoCache), true);
    } else {
        $cgUrl = 'https://api.coingecko.com/api/v3/coins/markets?' . http_build_query([
            'vs_currency' => 'eur',
            'ids' => 'bitcoin,solana,sui,usd-coin,ami',
            'sparkline' => 'true',
        ]);

        $ch = curl_init($cgUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => ['x-cg-demo-api-key: ' . COINGECKO_API_KEY],
        ]);
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && $resp) {
            $decoded = json_decode($resp, true);
            if (is_array($decoded) && $decoded !== []) {
                $coingeckoData = $decoded;
                file_put_contents($coingeckoCache, $resp, LOCK_EX);
            }
        } else {
            error_log("CoinGecko error HTTP {$code}");
        }

        if ($coingeckoData === null && is_file($coingeckoCache)) {
            $coingeckoData = json_decode(file_get_contents($coingeckoCache), true);
        }
    }
}

// ================================================================
// MÉTÉO VERTOU (44) — Open-Meteo : API gratuite, aucune clé requise.
// Appel serveur + cache 30 min. Aujourd'hui + J+1 + J+2 inclus.
// ================================================================
function wmoInfo(int $code): array
{
    $map = [
        0 => ['Ciel dégagé', 'fa-sun'],
        1 => ['Plutôt dégagé', 'fa-sun'],
        2 => ['Partiellement nuageux', 'fa-cloud-sun'],
        3 => ['Couvert', 'fa-cloud'],
        45 => ['Brouillard', 'fa-smog'],
        48 => ['Brouillard givrant', 'fa-smog'],
        51 => ['Bruine légère', 'fa-cloud-rain'],
        53 => ['Bruine', 'fa-cloud-rain'],
        55 => ['Bruine dense', 'fa-cloud-rain'],
        56 => ['Bruine verglaçante', 'fa-cloud-rain'],
        57 => ['Bruine verglaçante dense', 'fa-cloud-rain'],
        61 => ['Pluie légère', 'fa-cloud-rain'],
        63 => ['Pluie', 'fa-cloud-showers-heavy'],
        65 => ['Pluie forte', 'fa-cloud-showers-heavy'],
        66 => ['Pluie verglaçante', 'fa-cloud-rain'],
        67 => ['Pluie verglaçante forte', 'fa-cloud-rain'],
        71 => ['Neige légère', 'fa-snowflake'],
        73 => ['Neige', 'fa-snowflake'],
        75 => ['Neige forte', 'fa-snowflake'],
        77 => ['Grains de neige', 'fa-snowflake'],
        80 => ['Averses légères', 'fa-cloud-sun-rain'],
        81 => ['Averses', 'fa-cloud-showers-heavy'],
        82 => ['Averses violentes', 'fa-cloud-showers-heavy'],
        85 => ['Averses de neige', 'fa-snowflake'],
        86 => ['Averses de neige fortes', 'fa-snowflake'],
        95 => ['Orage', 'fa-bolt'],
        96 => ['Orage et grêle', 'fa-bolt'],
        99 => ['Orage et grêle forte', 'fa-bolt'],
    ];
    return $map[$code] ?? ['Temps indéterminé', 'fa-cloud'];
}

$weatherDays = null;
$weatherCache = __DIR__ . '/cache_meteo.json';
$weatherRaw = null;

if (is_file($weatherCache) && (time() - filemtime($weatherCache)) < 1800) {
    $weatherRaw = json_decode(file_get_contents($weatherCache), true);
} else {
    $omUrl = 'https://api.open-meteo.com/v1/forecast?' . http_build_query([
        'latitude' => 47.1667,
        'longitude' => -1.4667,
        'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max,wind_speed_10m_max',
        'timezone' => 'Europe/Paris',
        'forecast_days' => 3,
    ]);

    $ch = curl_init($omUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_CONNECTTIMEOUT => 4,
    ]);
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200 && $resp) {
        $weatherRaw = json_decode($resp, true);
        if (is_array($weatherRaw) && isset($weatherRaw['daily'])) {
            file_put_contents($weatherCache, $resp, LOCK_EX);
        } else {
            $weatherRaw = null;
        }
    } else {
        error_log("Open-Meteo error HTTP {$code}");
    }

    if ($weatherRaw === null && is_file($weatherCache)) {
        $weatherRaw = json_decode(file_get_contents($weatherCache), true);
    }
}

if (is_array($weatherRaw) && isset($weatherRaw['daily']['time'])) {
    $weatherDays = [];
    foreach ($weatherRaw['daily']['time'] as $i => $day) {
        $wmo = (int) ($weatherRaw['daily']['weather_code'][$i] ?? 0);
        [$label, $icon] = wmoInfo($wmo);
        $weatherDays[] = [
            'date' => $day,
            'day_label' => $i === 0 ? "Aujourd'hui" : ($i === 1 ? 'Demain' : (new DateTime($day))->format('D d/m')),
            'wmo' => $wmo,
            'label' => $label,
            'icon' => $icon,
            't_max' => round((float) ($weatherRaw['daily']['temperature_2m_max'][$i] ?? 0)),
            't_min' => round((float) ($weatherRaw['daily']['temperature_2m_min'][$i] ?? 0)),
            'rain_prob' => (int) ($weatherRaw['daily']['precipitation_probability_max'][$i] ?? 0),
            'wind_max' => round((float) ($weatherRaw['daily']['wind_speed_10m_max'][$i] ?? 0)),
        ];
    }
}

// ================================================================
// INFOS DU JOUR — APIs gratuites sans clé (appel serveur + cache)
// ================================================================
function fetchJsonCached(string $url, string $cacheFile, int $ttl): ?array
{
    if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
        $data = json_decode(file_get_contents($cacheFile), true);
        if (is_array($data)) {
            return $data;
        }
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; LibreAnalytics/1.0)',
    ]);
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200 && $resp) {
        $decoded = json_decode($resp, true);
        if (is_array($decoded) && $decoded !== []) {
            file_put_contents($cacheFile, $resp, LOCK_EX);
            return $decoded;
        }
    }
    error_log("Fetch failed HTTP {$code} : {$url}");

    // Cache périmé plutôt que rien
    if (is_file($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        if (is_array($data)) {
            return $data;
        }
    }
    return null;
}

// 1) « Un jour comme aujourd'hui » — Wikipédia FR via Action API (sans clé).
//    Le feed api.wikimedia.org est instable sans authentification : on parse
//    directement la section « Événements » de la page du jour.
$onThisDay = null;
$wikiMonths = [
    1 => 'janvier',
    2 => 'février',
    3 => 'mars',
    4 => 'avril',
    5 => 'mai',
    6 => 'juin',
    7 => 'juillet',
    8 => 'août',
    9 => 'septembre',
    10 => 'octobre',
    11 => 'novembre',
    12 => 'décembre'
];
$wikiPage = (int) date('j') . '_' . $wikiMonths[(int) date('n')];
$wikiBase = 'https://fr.wikipedia.org/w/api.php';

// 1a) Index de la section « Événements » de la page du jour
$otdSecRaw = fetchJsonCached(
    $wikiBase . '?' . http_build_query([
        'action' => 'parse',
        'page' => $wikiPage,
        'prop' => 'sections',
        'format' => 'json',
        'formatversion' => 2,
    ]),
    __DIR__ . '/cache_otd_sections_' . date('Ymd') . '.json',
    21600
);
$otdSection = null;
if (is_array($otdSecRaw)) {
    foreach (($otdSecRaw['parse']['sections'] ?? []) as $s) {
        $t = trim((string) ($s['line'] ?? ''));
        $isEvents = $t === 'Événements'
            || (function_exists('mb_strtolower') && mb_strtolower($t) === 'événements');
        if ($isEvents) {
            $otdSection = (int) ($s['index'] ?? 0);
            break;
        }
    }
}

// 1b) Wikitext de la section, puis extraction des lignes d'événements
if ($otdSection !== null) {
    $otdEvRaw = fetchJsonCached(
        $wikiBase . '?' . http_build_query([
            'action' => 'parse',
            'page' => $wikiPage,
            'section' => $otdSection,
            'prop' => 'wikitext',
            'format' => 'json',
            'formatversion' => 2,
        ]),
        __DIR__ . '/cache_otd_events_' . date('Ymd') . '.json',
        21600
    );
    $otdWiki = (string) ($otdEvRaw['parse']['wikitext'] ?? '');
    if (trim($otdWiki) !== '' && preg_match_all('/^\*\s*(.+)$/m', $otdWiki, $otdLines)) {
        $otdEvents = [];
        foreach ($otdLines[1] as $line) {
            if (count($otdEvents) >= 4) {
                break;
            }
            // Nettoyage wikitext : refs, modèles imbriqués, liens internes, gras/italique
            $line = preg_replace('/<ref[^>]*\/?>/i', '', $line);
            $line = preg_replace('/<\/ref>/i', '', $line);
            $line = preg_replace('/{{[^{}]*}}/', '', $line);
            $line = preg_replace('/\[\[(?:[^|\]]*\|)?([^|\]]+)\]\]/u', '$1', $line);
            $line = str_replace(["'''", "''"], '', $line);
            $line = trim((string) preg_replace('/\s+/u', ' ', $line), " \t:");
            if ($line === '') {
                continue;
            }
            $year = preg_match('/^(\d{3,4})\b/u', $line, $ym) ? $ym[1] : '';
            $text = trim((string) preg_replace('/^\d{3,4}\s*[:—–-]\s*/u', '', $line));
            if ($text === '') {
                continue;
            }
            if (function_exists('mb_substr')) {
                $text = mb_substr($text, 0, 220);
            } elseif (strlen($text) > 220) {
                $text = substr($text, 0, 220);
            }
            $otdEvents[] = ['year' => $year !== '' ? $year : '—', 'text' => $text];
        }
        if ($otdEvents !== []) {
            $onThisDay = array_slice($otdEvents, 0, 3);
        }
    }
}

// 2) berru.com — stats SmartPixel via l'API publique (clé uniquement côté serveur)
$berruStats = null;
$berruSeries = null;
$berruApiKey = defined('SMARTPIXEL_API_KEY') && SMARTPIXEL_API_KEY !== ''
    ? SMARTPIXEL_API_KEY
    : 'da4ca75b23f50634547bcd158927eb18add7d35fd2ad4ab7005e225443af1550';
$berruRaw = fetchJsonCached(
    'https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/api.php?' . http_build_query([
        'site_id' => 'SP_7f9505cc',
        'start_date' => date('Y') . '-01-01',
        'end_date' => date('Y-m-d'),
        'api_key' => $berruApiKey,
    ]),
    __DIR__ . '/cache_berru.json',
    900
);
if (is_array($berruRaw) && !empty($berruRaw['success']) && is_array($berruRaw['data'] ?? null)) {
    $berruByDate = [];
    foreach ($berruRaw['data'] as $berruRow) {
        if (!empty($berruRow['date'])) {
            $berruByDate[$berruRow['date']] = $berruRow;
        }
    }
    // Série continue depuis le 1er janvier (jours sans données = 0)
    $berruLabels = $berruVisits = $berruUniques = [];
    $berruTotalVisits = $berruTotalUniques = $berruBestDay = 0;
    $berruDay = new DateTime(date('Y') . '-01-01');
    $berruEnd = new DateTime('today');
    while ($berruDay <= $berruEnd) {
        $key = $berruDay->format('Y-m-d');
        $row = $berruByDate[$key] ?? [];
        $v = (int) ($row['visits'] ?? 0);
        $u = (int) ($row['unique_visitors'] ?? 0);
        $berruLabels[] = $berruDay->format('d/m');
        $berruVisits[] = $v;
        $berruUniques[] = $u;
        $berruTotalVisits += $v;
        $berruTotalUniques += $u;
        $berruBestDay = max($berruBestDay, $v);
        $berruDay->modify('+1 day');
    }
    $berruDays = count($berruLabels);
    if ($berruDays > 0) {
        $berruStats = [
            'total_visits' => $berruTotalVisits,
            'total_uniques' => $berruTotalUniques,
            'avg_per_day' => round($berruTotalVisits / $berruDays, 1),
            'best_day' => $berruBestDay,
            'days' => $berruDays,
        ];
        $berruSeries = ['labels' => $berruLabels, 'visits' => $berruVisits, 'uniques' => $berruUniques];
    }
}

// 3) Planning — embed Google Calendar.
//    ⚠️ Google interdit l'affichage de « /calendar/u/0/r » dans une iframe
//    (X-Frame-Options). Pour voir ton planning dans la card, ouvre
//    Google Calendar → Paramètres → « Intégrer le calendrier » et colle
//    l'URL obtenue (https://calendar.google.com/calendar/embed?src=…) ici.
$gcalEmbedUrl = 'https://calendar.google.com/calendar/embed?src=g.leberruyer%40gmail.com&ctz=Europe%2FParis';

// Date longue en français pour le titre « Infos du jour »
$daysFr = ['Monday' => 'lundi', 'Tuesday' => 'mardi', 'Wednesday' => 'mercredi', 'Thursday' => 'jeudi', 'Friday' => 'vendredi', 'Saturday' => 'samedi', 'Sunday' => 'dimanche'];
$monthsFr = ['January' => 'janvier', 'February' => 'février', 'March' => 'mars', 'April' => 'avril', 'May' => 'mai', 'June' => 'juin', 'July' => 'juillet', 'August' => 'août', 'September' => 'septembre', 'October' => 'octobre', 'November' => 'novembre', 'December' => 'décembre'];
$todayLong = ($daysFr[date('l')] ?? date('l')) . ' ' . (int) date('j') . ' ' . ($monthsFr[date('F')] ?? date('F')) . ' ' . date('Y');

// Données passées aux graphiques
$chartPayload = [
    'period' => $period,
    'labels' => array_map(fn($r) => (new DateTime($r['date']))->format('d/m'), $dailySeries),
    'series' => [
        'users' => array_map('intval', array_column($dailySeries, 'new_users')),
        'sites' => array_map('intval', array_column($dailySeries, 'new_sites')),
        'visits' => array_map('intval', array_column($dailySeries, 'visits')),
        'uniques' => array_map('intval', array_column($dailySeries, 'unique_visitors')),
    ],
];

// Ordre d'affichage des plans dans les chips
$planOrder = ['free' => 'Free', 'pro' => 'Pro', 'business' => 'Business', 'premium' => 'Premium'];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0b0c0f">
    <title>LibreAnalytics — Contrôle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Perf UX : scripts non bloquants -->
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- amCharts : chargé à la demande, uniquement si la carte est demandée -->
    <style>
        :root {
            /* Mode clair par défaut — le mode sombre suit automatiquement
               la préférence du système (PC, Android, iOS) via prefers-color-scheme. */
            color-scheme: light;
            --bg: #f4f5f8;
            --surface: #ffffff;
            --surface-2: #eef0f4;
            --surface-3: #e2e5ea;
            --border: #d5d9e0;
            --border-soft: #e5e8ed;
            --text: #16181d;
            --text-2: #474e5c;
            --text-3: #6d7484;
            --green: #8bfa8b;
            --green-soft: rgba(63, 185, 80, 0.12);
            --red: #f0635a;
            --red-soft: rgba(240, 99, 90, 0.12);
            --blue: #58a6ff; 
            --violet: #9d86ff;
            --amber: #e3b341;
            --font: 'Inter', system-ui, -apple-system, sans-serif;
            --mono: 'JetBrains Mono', ui-monospace, monospace;
            --radius: 12px;
            --radius-sm: 8px;
            --shadow: 0 1px 2px rgba(20, 24, 40, 0.05), 0 10px 30px rgba(20, 24, 40, 0.10);
            --topbar-bg: rgba(255, 255, 255, 0.85);
            --t-fast: 140ms cubic-bezier(0.4, 0, 0.2, 1);
            --t-med: 240ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                color-scheme: dark;
                --bg: #0b0c0f;
                --surface: #12141a;
                --surface-2: #171a21;
                --surface-3: #1d212a;
                --border: #23262f;
                --border-soft: #1b1e26;
                --text: #f0f1f4;
                --text-2: #a6abb8;
                --text-3: #6b7180;
                --green: #8bfa8b;
                --green-soft: rgba(63, 185, 80, 0.12);
                --red: #f0635a;
                --red-soft: rgba(240, 99, 90, 0.12);
                --blue: #58a6ff;
                --violet: #9d86ff;
                --amber: #e3b341;
                --shadow: 0 1px 2px rgba(0, 0, 0, 0.35), 0 10px 30px rgba(0, 0, 0, 0.22);
                --topbar-bg: rgba(11, 12, 15, 0.85);
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
            scrollbar-color: var(--surface-3) transparent;
            -webkit-text-size-adjust: 100%;
        }

        body {
            font-family: var(--font);
            font-size: 14px;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
            overflow-x: hidden;
            -webkit-tap-highlight-color: transparent;
        }

        ::selection {
            background: var(--violet);
            color: var(--bg);
        }

        :focus-visible {
            outline: 2px solid var(--violet);
            outline-offset: 2px;
            border-radius: 4px;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button {
            font: inherit;
            color: inherit;
        }

        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--surface-3);
            border-radius: 5px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        .num {
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.01em;
        }

        .shell {
            max-width: 1240px;
            margin: 0 auto;
            padding: 0 20px 64px;
        }

        @media (min-width: 720px) {
            .shell {
                padding: 0 32px 80px;
            }
        }

        /* ===== TOPBAR ===== */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 50;
            background: var(--topbar-bg);
            backdrop-filter: blur(12px) saturate(1.4);
            -webkit-backdrop-filter: blur(12px) saturate(1.4);
            border-bottom: 1px solid var(--border-soft);
        }

        .topbar-inner {
            max-width: 1240px;
            margin: 0 auto;
            padding: 0 20px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        @media (min-width: 720px) {
            .topbar-inner {
                padding: 0 32px;
            }
        }

        @media (max-width: 480px) {
            .back-label {
                display: none;
            }
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Boutons topbar : cible tactile confortable */
        .tb-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 38px;
            padding: 0 12px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-2);
            background: transparent;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: color var(--t-fast), border-color var(--t-fast), background var(--t-fast);
        }

        .tb-btn:hover,
        .tb-btn[aria-expanded="true"] {
            color: var(--text);
            border-color: var(--surface-3);
            background: var(--surface-2);
        }

        .tb-btn i {
            font-size: 13px;
        }

        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-family: var(--mono);
            font-size: 11px;
            color: var(--text-3);
            letter-spacing: 0.04em;
        }

        .status-chip::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--green);
            box-shadow: 0 0 0 3px rgba(139, 127, 250, 0.14);
            animation: pulse 2.4s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.4;
                transform: scale(0.85);
            }
        }

        @media (max-width: 560px) {
            .status-chip {
                display: none;
            }
        }

        /* ===== POPOVERS (météo + menu) ===== */
        .pop {
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            width: min(240px, calc(100vw - 32px));
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-4px);
            transition: opacity var(--t-fast), transform var(--t-fast), visibility var(--t-fast);
            z-index: 60;
        }

        .pop.open {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        /* --- Météo --- */
        .weather-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
        }

        .weather-day {
            padding: 14px 8px 12px;
            text-align: center;
            border-right: 1px solid var(--border-soft);
            position: relative;
        }

        .weather-day:last-child {
            border-right: none;
        }

        .weather-day.today::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 24px;
            height: 2px;
            border-radius: 1px;
            background: var(--violet);
        }

        .weather-day .d-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-2);
            margin-bottom: 8px;
        }

        .weather-day .d-icon {
            font-size: 20px;
            color: var(--text);
            margin-bottom: 4px;
        }

        .weather-day .d-desc {
            font-size: 11px;
            color: var(--text-3);
            margin-bottom: 8px;
            line-height: 1.3;
            min-height: 2.6em;
            display: grid;
            place-items: center;
        }

        .weather-day .d-temps {
            font-size: 14px;
            font-weight: 700;
        }

        .weather-day .d-temps .min {
            color: var(--text-3);
            font-weight: 500;
        }

        .weather-day .d-rain {
            font-size: 11px;
            color: var(--blue);
            margin-top: 4px;
        }

        .weather-day .d-wind {
            font-size: 11px;
            color: var(--text-3);
            margin-top: 2px;
        }

        @media (max-width: 480px) {
            .weather-grid {
                grid-template-columns: 1fr;
            }

            .weather-day {
                border-right: none;
                border-bottom: 1px solid var(--border-soft);
                display: grid;
                grid-template-columns: 64px 1fr auto;
                align-items: center;
                text-align: left;
                gap: 4px 12px;
            }

            .weather-day .d-label {
                grid-column: 1;
                grid-row: 1;
                margin: 0;
                align-self: center;
            }

            .weather-day .d-icon {
                grid-column: 1;
                grid-row: 2 / span 2;
                font-size: 22px;
                margin: 0;
            }

            .weather-day .d-desc {
                grid-column: 2;
                grid-row: 2;
                min-height: 0;
                text-align: left;
                justify-items: start;
                display: block;
            }

            .weather-day .d-temps {
                grid-column: 3;
                grid-row: 2;
            }

            .weather-day .d-rain {
                grid-column: 2;
                grid-row: 3;
                margin: 0;
            }

            .weather-day .d-wind {
                grid-column: 3;
                grid-row: 3;
                margin: 0;
                text-align: right;
            }
        }

        /* --- Menu commandes --- */
        .menu-anchor {
            position: relative;
        }

        .cmd-menu,
        .menu-nav {
            min-width: 264px;
            overflow: hidden;
        }

        .cmd-menu a,
        .menu-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 16px;
            font-size: 13.5px;
            font-weight: 500;
            border-bottom: 1px solid var(--border-soft);
            min-height: 44px;
            transition: background var(--t-fast), color var(--t-fast);
        }

        .cmd-menu a:last-child,
        .menu-nav a:last-child {
            border-bottom: none;
        }

        .cmd-menu a i,
        .menu-nav a i {
            width: 16px;
            text-align: center;
            color: var(--text-3);
            font-size: 13px;
            transition: color var(--t-fast);
        }

        .cmd-menu a:hover,
        .menu-nav a:hover {
            background: var(--surface-2);
            color: var(--violet);
        }

        .cmd-menu a:hover i,
        .menu-nav a:hover i {
            color: var(--violet);
        }

        /* ===== EN-TÊTE DE PAGE + PÉRIODE ===== */
        .page-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            padding: 28px 0 20px;
        }

        .page-head h1 {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .page-head .meta {
            font-size: 13px;
            color: var(--text-3);
            margin-top: 4px;
        }

        .period-switch {
            display: inline-flex;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 3px;
            gap: 2px;
        }

        .period-switch a {
            font-size: 13px;
            font-weight: 500;
            min-width: 44px;
            padding: 6px 12px;
            text-align: center;
            color: var(--text-3);
            border-radius: 6px;
            transition: color var(--t-fast), background var(--t-fast);
        }

        .period-switch a:hover {
            color: var(--text);
        }

        .period-switch a.active {
            background: var(--surface-3);
            color: var(--text);
            font-weight: 600;
        }

        /* ===== SECTIONS ===== */
        .section {
            margin-bottom: 36px;
        }

        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .section-head h2 {
            font-size: 15px;
            font-weight: 600;
            letter-spacing: -0.01em;
        }

        .section-head .hint {
            font-size: 12.5px;
            color: var(--text-3);
        }

        /* ===== KPI ===== */
        .kpi-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }

        @media (max-width: 1020px) {
            .kpi-strip {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 520px) {
            .kpi-strip {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }

        .kpi {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 18px 18px 14px;
            position: relative;
            overflow: hidden;
            transition: border-color var(--t-fast);
        }

        .kpi:hover {
            border-color: var(--surface-3);
        }

        .kpi-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .kpi-label {
            font-size: 13px;
            color: var(--text-2);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .kpi-label i {
            font-size: 12px;
            color: var(--text-3);
        }

        .delta {
            font-size: 12px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 999px;
            white-space: nowrap;
        }

        .delta.up {
            color: var(--green);
            background: var(--green-soft);
        }

        .delta.down {
            color: var(--red);
            background: var(--red-soft);
        }

        .delta.flat {
            color: var(--text-3);
            background: var(--surface-2);
        }

        .kpi-value {
            font-size: clamp(24px, 3.2vw, 30px);
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .kpi-sub {
            font-size: 12px;
            color: var(--text-3);
            margin-top: 2px;
        }

        .kpi .spark {
            height: 42px;
            margin-top: 12px;
        }

        /* ===== CARTES ===== */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }

        .card-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            padding: 16px 18px;
            border-bottom: 1px solid var(--border-soft);
        }

        .card-title {
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .card-title i {
            color: var(--text-3);
            font-size: 13px;
        }

        .card-body {
            padding: 18px;
        }

        .duo {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        @media (max-width: 1020px) {
            .duo {
                grid-template-columns: 1fr;
            }
        }

        /* ===== WALLET ===== */
        .wallet-hero {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            padding: 18px;
            background: linear-gradient(180deg, var(--surface-2), var(--surface));
            border-bottom: 1px solid var(--border-soft);
        }

        .wallet-hero .label {
            font-size: 12px;
            color: var(--text-3);
            margin-bottom: 6px;
        }

        .wallet-hero .total {
            font-size: clamp(28px, 4vw, 36px);
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1;
        }

        .wallet-hero .invested {
            font-size: 12px;
            color: var(--text-3);
            margin-top: 6px;
        }

        .wallet-hero .pnl-box {
            text-align: right;
        }

        .wallet-hero .pnl {
            font-size: 16px;
            font-weight: 700;
        }

        .wallet-hero .pnl.positive {
            color: var(--green);
        }

        .wallet-hero .pnl.negative {
            color: var(--red);
        }

        .wallet-hero .pnl-pct {
            display: inline-block;
            font-size: 12px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 999px;
            margin-bottom: 6px;
        }

        .wallet-hero .pnl-pct.positive {
            color: var(--green);
            background: var(--green-soft);
        }

        .wallet-hero .pnl-pct.negative {
            color: var(--red);
            background: var(--red-soft);
        }

        .wallet-hero .pnl-label {
            display: block;
            font-size: 11px;
            color: var(--text-3);
        }

        #crypto-prices {
            display: flex;
            flex-direction: column;
        }

        .crypto-item {
            display: grid;
            grid-template-columns: 40px 1fr auto auto;
            align-items: center;
            gap: 14px;
            padding: 13px 18px;
            border-bottom: 1px solid var(--border-soft);
            transition: background var(--t-fast);
        }

        .crypto-item:last-child {
            border-bottom: none;
        }

        .crypto-item:hover {
            background: var(--surface-2);
        }

        .crypto-item img {
            width: 34px;
            height: 34px;
            border-radius: 50%;
        }

        .crypto-item .name {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .crypto-item .symbol {
            font-weight: 600;
            font-size: 14px;
        }

        .crypto-item .symbol a {
            color: inherit;
            text-decoration: none;
        }

        .crypto-item .symbol a:hover {
            text-decoration: underline;
        }

        .crypto-item .price {
            font-size: 12.5px;
            color: var(--text-3);
        }

        .crypto-item .holdings {
            display: flex;
            flex-direction: column;
            text-align: right;
        }

        .crypto-item .holdings .amount {
            font-family: var(--mono);
            font-size: 11.5px;
            color: var(--text-3);
        }

        .crypto-item .holdings .value {
            font-size: 14px;
            font-weight: 600;
        }

        .crypto-item .change {
            font-size: 13px;
            font-weight: 600;
            min-width: 68px;
            text-align: right;
        }

        .crypto-item .change.positive {
            color: var(--green);
        }

        .crypto-item .change.negative {
            color: var(--red);
        }

        @media (max-width: 460px) {
            .crypto-item {
                grid-template-columns: 34px 1fr auto;
                gap: 10px;
            }

            .crypto-item .holdings {
                display: none;
            }

            .crypto-item img {
                width: 30px;
                height: 30px;
            }
        }

        .ghost-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 36px;
            padding: 0 12px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-2);
            background: transparent;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: color var(--t-fast), border-color var(--t-fast);
        }

        .ghost-btn:hover {
            color: var(--text);
            border-color: var(--surface-3);
        }

        .ghost-btn i {
            color: var(--text-3);
            font-size: 12px;
        }

        /* ===== ACTIVITÉ (petit-multiples) ===== */
        .multiples {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        @media (max-width: 1020px) {
            .multiples {
                grid-template-columns: 1fr;
            }
        }

        .mini {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px 18px 12px;
        }

        .mini-head {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 10px;
        }

        .mini-head .t {
            font-size: 13px;
            color: var(--text-2);
        }

        .mini-head .v {
            font-size: 16px;
            font-weight: 700;
        }

        .mini .chart-sm {
            height: 150px;
        }

        @media (max-width: 520px) {
            .mini .chart-sm {
                height: 120px;
            }
        }

        /* ===== GÉO ===== */
        .geo-toggle {
            display: inline-flex;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 3px;
            gap: 2px;
        }

        .geo-toggle button {
            font-size: 12px;
            font-weight: 500;
            padding: 5px 12px;
            background: transparent;
            color: var(--text-3);
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background var(--t-fast), color var(--t-fast);
        }

        .geo-toggle button.active {
            background: var(--surface-3);
            color: var(--text);
            font-weight: 600;
        }

        .geo-view {
            height: 340px;
            position: relative;
        }

        .geo-view canvas,
        .geo-view .map-holder {
            height: 100%;
        }

        .map-holder {
            display: none;
        }

        .map-holder.visible {
            display: block;
        }

        .map-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            font-size: 13px;
            color: var(--text-3);
        }

        /* ===== REGISTRE UTILISATEURS ===== */
        .plan-chips {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .plan-chip {
            font-size: 11.5px;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 999px;
            border: 1px solid var(--border);
            color: var(--text-2);
            background: var(--surface-2);
        }

        .plan-chip b {
            font-weight: 700;
            color: var(--text);
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            font-family: var(--font);
            font-size: 13.5px;
            color: var(--text);
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 8px 12px 8px 34px;
            min-height: 38px;
            width: min(260px, 56vw);
            transition: border-color var(--t-fast);
        }

        .search-box input::placeholder {
            color: var(--text-3);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--green);
        }

        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-3);
            font-size: 12px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }

        .data-table th {
            text-align: left;
            padding: 11px 18px;
            color: var(--text-3);
            font-weight: 600;
            font-size: 12px;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
            background: var(--surface);
            user-select: none;
        }

        .data-table th.sortable {
            cursor: pointer;
            transition: color var(--t-fast);
        }

        .data-table th.sortable:hover {
            color: var(--text);
        }

        .data-table th.sortable::after {
            content: '↕';
            margin-left: 5px;
            opacity: 0.4;
            font-size: 11px;
        }

        .data-table th.sortable.sorted-asc::after {
            content: '↑';
            opacity: 1;
            color: var(--green);
        }

        .data-table th.sortable.sorted-desc::after {
            content: '↓';
            opacity: 1;
            color: var(--green);
        }

        .data-table td {
            padding: 12px 18px;
            border-bottom: 1px solid var(--border-soft);
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .data-table tbody tr {
            transition: background var(--t-fast);
        }

        .data-table tbody tr:hover {
            background: var(--surface-2);
        }

        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        .search-empty {
            display: none;
        }

        .badge {
            display: inline-block;
            padding: 3px 9px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.02em;
            border-radius: 999px;
            border: 1px solid;
        }

        .badge-free {
            color: var(--text-2);
            border-color: var(--border);
            background: var(--surface-2);
        }

        .badge-pro {
            color: var(--green);
            border-color: rgba(63, 185, 80, 0.4);
            background: var(--green-soft);
        }

        .badge-business {
            color: var(--red);
            border-color: rgba(240, 99, 90, 0.4);
            background: var(--red-soft);
        }

        .badge-premium {
            color: var(--violet);
            border-color: rgba(167, 139, 250, 0.4);
            background: rgba(167, 139, 250, 0.1);
        }

        .export-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 38px;
            padding: 0 14px;
            font-size: 13px;
            font-weight: 600;
            background: var(--green);
            color: var(--bg);
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: filter var(--t-fast);
        }

        .export-btn:hover {
            filter: brightness(1.1);
        }

        /* --- Mobile : la table devient une liste de cartes --- */
        @media (max-width: 720px) {
            .data-table thead {
                display: none;
            }

            .data-table,
            .data-table tbody,
            .data-table tr,
            .data-table td {
                display: block;
                width: 100%;
            }

            .data-table tbody tr {
                background: var(--surface);
                border: 1px solid var(--border);
                border-radius: var(--radius);
                margin: 10px 12px;
                padding: 4px 0;
                overflow: hidden;
            }

            .data-table tbody tr:hover {
                background: var(--surface);
            }

            .data-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 16px;
                padding: 8px 14px;
                border-bottom: 1px solid var(--border-soft);
                white-space: normal;
                font-size: 13.5px;
            }

            .data-table td:last-child {
                border-bottom: none;
            }

            .data-table td::before {
                content: attr(data-label);
                color: var(--text-3);
                font-size: 12px;
                font-weight: 500;
                flex-shrink: 0;
            }

            .data-table td[data-label="Email"] {
                font-weight: 600;
                word-break: break-all;
            }

            .data-table td[data-label="Email"]::before {
                display: none;
            }

            .data-table td[data-label="Email"] {
                justify-content: flex-start;
            }

            .data-table tbody tr:last-child td {
                border-radius: 0;
            }
        }

        /* ===== ANCRAGES DE SECTION (menu de navigation) ===== */
        .section {
            scroll-margin-top: 76px;
        }

        /* ===== INFOS DU JOUR ===== */
        .info-hero {
            padding: 6px 0 14px;
        }

        .info-hero h2 {
            font-size: clamp(26px, 4vw, 32px);
            font-weight: 700;
            letter-spacing: -0.03em;
        }

        .info-hero .meta {
            font-size: 13px;
            color: var(--text-3);
            margin-top: 4px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            align-items: start;
        }

        @media (max-width: 1020px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        .today-event {
            display: flex;
            gap: 12px;
            padding: 11px 0;
            border-bottom: 1px solid var(--border-soft);
            align-items: flex-start;
        }

        .today-event .year {
            font-family: var(--mono);
            font-size: 12px;
            font-weight: 600;
            color: var(--green);
            background: var(--green-soft);
            border-radius: 6px;
            padding: 2px 8px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .today-event p {
            font-size: 13.5px;
            color: var(--text-2);
            line-height: 1.5;
        }

        .card-src {
            font-size: 11.5px;
            color: var(--text-3);
            margin-top: 14px;
            padding-top: 10px;
            border-top: 1px solid var(--border-soft);
        }

        /* ===== GOOGLE CALENDAR (infos du jour) ===== */
        .gcal-holder {
            padding: 10px 10px 0;
            height: 470px;
        }

        .gcal-holder iframe {
            width: 100%;
            height: 100%;
            border: 0;
            border-radius: var(--radius-sm);
            background: rgba(167, 139, 250, 0.1);
        }

        iframe {
            background-color: rgba(167, 139, 250, 0.1);
        }

        /* ===== WIDGET TRADINGVIEW (wallet) ===== */
        .tv-holder {
            height: 460px;
        }

        .tv-holder .tradingview-widget-container,
        .tv-holder .tradingview-widget-container__widget {
            height: 100%;
            width: 100%;
        }

        .tv-holder iframe {
            border-radius: var(--radius-sm);
        }

        /* ===== MÉTÉO EN SECTION PLEINE ===== */
        .weather-section .weather-day {
            padding: 20px 12px 16px;
        }

        .weather-section .weather-day .d-icon {
            font-size: 26px;
        }

        @media (max-width: 480px) {
            .weather-section .weather-day {
                padding: 14px 16px;
            }
        }

        /* ===== ÉTATS VIDES ===== */
        .empty-state {
            text-align: center;
            padding: 40px 16px;
            color: var(--text-3);
            font-size: 13.5px;
        }

        /* ===== SWEETALERT ===== */
        .custom-swal-popup {
            background: var(--surface) !important;
            color: var(--text) !important;
            border: 1px solid var(--border) !important;
            border-radius: var(--radius) !important;
            font-family: var(--font) !important;
        }

        .custom-swal-popup h2 {
            font-family: var(--mono) !important;
            color: var(--green) !important;
            letter-spacing: 0.1em;
        }

        .custom-swal-close-button {
            color: var(--text-3) !important;
        }

        .custom-swal-content ul {
            list-style: none;
            padding: 0;
            text-align: left;
        }

        .custom-swal-content li {
            padding: 11px 0;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }

        .custom-swal-content li:last-child {
            border-bottom: none;
        }

        .custom-swal-content a {
            color: var(--green);
            font-weight: 600;
        }

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }
    </style>
</head>

<body>
    <!-- ===== TOPBAR ===== -->
    <header class="topbar">
        <div class="topbar-inner">
            <button class="tb-btn" id="back-btn" aria-label="Retour à la page précédente">
                <i class="fas fa-arrow-left"></i>
                <span class="back-label">Retour</span>
            </button>
            <div class="topbar-right">
                <span class="status-chip">API</span>

                <div class="menu-anchor">
                    <button class="tb-btn" id="menu-toggle" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-bars"></i>
                        Menu
                        <i class="fas fa-chevron-down" style="font-size: 10px;"></i>
                    </button>
                    <div class="pop menu-nav" id="menu-nav" role="menu" aria-label="Navigation entre les sections">
                        <a href="dashboard.php?site_id=6" role="menuitem"><i class="fas fa-gauge-high"></i>
                            LibreAnalytics.com</a>
                        <a href="dashboard.php?site_id=5" role="menuitem"><i class="fas fa-house"></i>
                            gael-berru.com</a>
                        <a href="#wallet" role="menuitem"><i class="fas fa-wallet"></i> Wallet all in one</a>
                        <a href="#infos" role="menuitem"><i class="fas fa-bolt"></i> Infos du jour</a>
                        <a href="#meteo" role="menuitem"><i class="fas fa-cloud-sun"></i> Météo</a>
                        <a href="#activite" role="menuitem"><i class="fas fa-chart-bar"></i> Activité détaillée</a>
                        <a href="#geo" role="menuitem"><i class="fas fa-earth-europe"></i> Top sites &amp; géo</a>
                        <a href="#users" role="menuitem"><i class="fas fa-users"></i> Utilisateurs</a>
                    </div>
                </div>

                <div class="menu-anchor">
                    <button class="tb-btn" id="cmd-toggle" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-terminal"></i>
                        CMD
                        <i class="fas fa-chevron-down" style="font-size: 10px;"></i>
                    </button>
                    <div class="pop cmd-menu" id="cmd-menu" role="menu">

                        <a href="../campain/rapport.php" role="menuitem"><i class="fas fa-file-lines"></i> Rapport
                            id_gb</a>
                        <a href="../campain/rapport_lb.php" role="menuitem"><i class="fas fa-file-lines"></i> Rapport
                            id_lb</a>
                        <a href="https://calendar.google.com/calendar/u/0/r" role="menuitem"><i
                                class="fa-regular fa-calendar-days"></i> Calendar</a>
                        <a href="../campain/prospect_template.php" role="menuitem"><i
                                class="fa-regular fa-file-code"></i> Script prospection</a>
                        <a href="?export_emails=1" role="menuitem"><i class="fas fa-download"></i> Export emails
                            CSV</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="shell">
        <!-- ===== EN-TÊTE + PÉRIODE ===== -->
        <div class="page-head">
            <div>
                <h2>LibreAnalytics.com</h2>
                <p class="meta">Console admin — <?= date('d/m/Y') ?> —
                    <?= htmlspecialchars($_SESSION['user_email']) ?>
                </p>
            </div>
            <nav class="period-switch" aria-label="Période d'analyse">
                <a href="?period=7" class="<?= $period === 7 ? 'active' : '' ?>">7j</a>
                <a href="?period=30" class="<?= $period === 30 ? 'active' : '' ?>">30j</a>
                <a href="?period=90" class="<?= $period === 90 ? 'active' : '' ?>">90j</a>
                <a href="?period=365" class="<?= $period === 365 ? 'active' : '' ?>">365j</a>
            </nav>
        </div>

        <!-- ===== KPI : valeur + delta + sparkline ===== -->
        <section class="section" id="kpis" aria-label="Indicateurs clés">
            <?php
            $kpis = [
                [
                    'key' => 'visits',
                    'label' => 'Visites',
                    'icon' => 'fa-eye',
                    'cur' => (int) ($periodTotals['cur_visits'] ?? 0),
                    'prev' => (int) ($periodTotals['prev_visits'] ?? 0)
                ],
                [
                    'key' => 'uniques',
                    'label' => 'Visiteurs uniques',
                    'icon' => 'fa-fingerprint',
                    'cur' => (int) ($periodTotals['cur_uniques'] ?? 0),
                    'prev' => (int) ($periodTotals['prev_uniques'] ?? 0)
                ],
                [
                    'key' => 'users',
                    'label' => 'Nouveaux utilisateurs',
                    'icon' => 'fa-user-plus',
                    'cur' => (int) ($periodTotals['cur_users'] ?? 0),
                    'prev' => (int) ($periodTotals['prev_users'] ?? 0)
                ],
                [
                    'key' => 'sites',
                    'label' => 'Nouveaux sites',
                    'icon' => 'fa-globe',
                    'cur' => (int) ($periodTotals['cur_sites'] ?? 0),
                    'prev' => (int) ($periodTotals['prev_sites'] ?? 0)
                ],
            ];
            $totalLabel = $period . ' derniers jours';
            ?>
            <div class="kpi-strip">
                <?php foreach ($kpis as $kpi):
                    $delta = deltaPct($kpi['cur'], $kpi['prev']);
                    if ($delta === null) {
                        $deltaClass = 'flat';
                        $deltaTxt = 'n/a';
                    } elseif ($delta >= 0) {
                        $deltaClass = 'up';
                        $deltaTxt = '+' . $delta . '%';
                    } else {
                        $deltaClass = 'down';
                        $deltaTxt = $delta . '%';
                    }
                    $global = $kpi['key'] === 'sites' ? $totalSites . ' au total' : ($kpi['key'] === 'users' ? $totalUsers . ' au total' : null);
                    ?>
                    <article class="kpi">
                        <div class="kpi-top">
                            <span class="kpi-label"><i class="fas <?= $kpi['icon'] ?>"></i>
                                <?= htmlspecialchars($kpi['label']) ?></span>
                            <span class="delta <?= $deltaClass ?>"><?= $deltaTxt ?></span>
                        </div>
                        <div class="kpi-value num"><?= number_format($kpi['cur']) ?></div>
                        <?php if ($global): ?>
                            <div class="kpi-sub"><?= $global ?> · <?= $totalLabel ?></div>
                        <?php else: ?>
                            <div class="kpi-sub"><?= $totalLabel ?></div>
                        <?php endif; ?>
                        <div class="spark"><canvas data-spark="<?= $kpi['key'] ?>"></canvas></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- ===== BERRU.COM — stats SmartPixel ===== -->
        <section class="section" id="berru">
            <div class="section-head">
                <h2>gael-berru.com</h2>
                <span class="hint">LibreAnalytics_API · 1ᵉʳ janvier → aujourd'hui</span>
            </div>
            <?php if ($berruStats !== null): ?>
                <div class="kpi-strip">
                    <article class="kpi">
                        <div class="kpi-top">
                            <span class="kpi-label"><i class="fas fa-eye"></i> Visites</span>
                        </div>
                        <div class="kpi-value num"><?= number_format($berruStats['total_visits'], 0, ',', ' ') ?></div>
                        <div class="kpi-sub">depuis le 1ᵉʳ janvier</div>
                    </article>
                    <article class="kpi">
                        <div class="kpi-top">
                            <span class="kpi-label"><i class="fas fa-fingerprint"></i> Visiteurs uniques</span>
                        </div>
                        <div class="kpi-value num"><?= number_format($berruStats['total_uniques'], 0, ',', ' ') ?></div>
                        <div class="kpi-sub">somme des uniques quotidiens</div>
                    </article>
                    <article class="kpi">
                        <div class="kpi-top">
                            <span class="kpi-label"><i class="fas fa-calendar-day"></i> Moyenne / jour</span>
                        </div>
                        <div class="kpi-value num"><?= str_replace('.', ',', (string) $berruStats['avg_per_day']) ?></div>
                        <div class="kpi-sub">sur <?= $berruStats['days'] ?> jours</div>
                    </article>
                    <article class="kpi">
                        <div class="kpi-top">
                            <span class="kpi-label"><i class="fas fa-fire"></i> Meilleure journée</span>
                        </div>
                        <div class="kpi-value num"><?= number_format($berruStats['best_day'], 0, ',', ' ') ?></div>
                        <div class="kpi-sub">visites en 24 h</div>
                    </article>
                </div>
                <div class="card" style="margin-top: 14px;">
                    <div class="card-head">
                        <h3 class="card-title"><i class="fas fa-chart-line"></i> Visites quotidiennes — berru.com</h3>
                    </div>
                    <div class="card-body" style="height: 280px;">
                        <canvas id="berruChart"></canvas>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="empty-state">Statistiques berru.com indisponibles pour le moment</div>
                </div>
            <?php endif; ?>
        </section>

        <!-- ===== WALLET ===== -->
        <section class="section" id="wallet">
            <div class="section-head">
                <h2>Wallet</h2>
                <span class="hint">CoinGecko · cache 5 min</span>
            </div>
            <div class="duo">
                <div class="card">
                    <div class="card-head">
                        <h3 class="card-title"><i class="fas fa-wallet"></i> Tokens</h3>
                        <button class="ghost-btn" id="wallet-links-btn">
                            <i class="fas fa-link"></i> Liens 0x
                        </button>
                    </div>
                    <div class="wallet-hero" id="portfolio-total"></div>
                    <div id="crypto-prices"></div>
                </div>
                <div class="card">

                    <div class="card-body tv-holder">
                        <div class="tradingview-widget-container">
                            <div class="tradingview-widget-container__widget"></div>
                            <script src="https://s3.tradingview.com/external-embedding/embed-widget-advanced-chart.js"
                                async>
                                    {
                                        "autosize": true,
                                            "symbol": "BITSTAMP:SUIEUR",
                                                "interval": "D",
                                                    "timezone": "Europe/Paris",
                                                        "theme": "dark",
                                                            "style": "1",
                                                                "locale": "fr",
                                                                    "backgroundColor": "rgba(18, 20, 26, 0.65)",
                                                                        "gridColor": "rgba(35, 38, 47, 0.6)",
                                                                            "withdateranges": true,
                                                                                "hide_side_toolbar": false,
                                                                                    "allow_symbol_change": true,
                                                                                        "save_image": false,
                                                                                            "details": true,
                                                                                                "calendar": false,
                                                                                                    "support_host": "https://www.tradingview.com"
                                    }
                                </script>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== INFOS DU JOUR ===== -->
        <section class="section" id="infos">
            <div class="info-hero">
                <h2>Infos du jour</h2>
                <p class="meta"><?= htmlspecialchars($todayLong) ?> · CoinGecko · Wikipédia FR</p>
            </div>
            <div class="info-grid">
                <div class="card">
                    <div class="card-head">
                        <h3 class="card-title"><i class="fas fa-cubes"></i> Solana &amp; Sui — sentiment &amp; signaux
                            du jour</h3>
                    </div>
                    <div class="card-body">
                        <script type="module"
                            src="https://widgets.tradingview-widget.com/w/en/tv-news-feed.js"></script>

                        <tv-news-feed symbol="COINBASE:SUIUSD" news-type="all"></tv-news-feed>
                    </div>
                </div>

                <div class="card">
                    <div class="card-head">
                        <h3 class="card-title"><i class="fas fa-calendar-days"></i> Mon planning — Google Calendar</h3>
                        <a class="ghost-btn" href="https://calendar.google.com/calendar/u/0/r" target="_blank"
                            rel="noopener">
                            <i class="fas fa-arrow-up-right-from-square"></i> Ouvrir
                        </a>
                    </div>
                    <div class="card-body gcal-holder">
                        <iframe src="<?= htmlspecialchars($gcalEmbedUrl) ?>" title="Planning Google Calendar"
                            loading="lazy" referrerpolicy="no-referrer"></iframe>
                    </div>
                    <div class="card-src">Google bloque « /calendar/u/0/r » en iframe : colle ton URL « Intégrer le
                        calendrier » dans $gcalEmbedUrl pour afficher ton planning ici</div>
                </div>

                <?php if (!empty($onThisDay)): ?>
                    <div class="card">
                        <div class="card-head">
                            <h3 class="card-title"><i class="fas fa-landmark"></i> Un jour comme aujourd'hui</h3>
                        </div>
                        <div class="card-body">
                            <?php foreach ($onThisDay as $ev): ?>
                                <div class="today-event">
                                    <span class="year num"><?= htmlspecialchars((string) ($ev['year'] ?? '')) ?></span>
                                    <p><?= htmlspecialchars(strip_tags((string) ($ev['text'] ?? ''))) ?></p>
                                </div>
                            <?php endforeach; ?>
                            <div class="card-src">Wikipédia FR · section « Événements »</div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-head">
                            <h3 class="card-title"><i class="fas fa-landmark"></i> Un jour comme aujourd'hui</h3>
                        </div>
                        <div class="empty-state">Événements indisponibles pour le moment</div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ===== MÉTÉO ===== -->
        <section class="section weather-section" id="meteo">
            <div class="section-head">
                <h2>Météo — Vertou (44)</h2>
                <span class="hint">open-meteo · aujourd'hui + J+2</span>
            </div>
            <?php if (!empty($weatherDays)): ?>
                <div class="card">
                    <div class="weather-grid">
                        <?php foreach ($weatherDays as $i => $day): ?>
                            <div class="weather-day <?= $i === 0 ? 'today' : '' ?>">
                                <div class="d-label"><?= htmlspecialchars($day['day_label']) ?></div>
                                <div class="d-icon"><i class="fas <?= htmlspecialchars($day['icon']) ?>"></i></div>
                                <div class="d-desc"><?= htmlspecialchars($day['label']) ?></div>
                                <div class="d-temps num"><?= $day['t_max'] ?>° <span class="min">/ <?= $day['t_min'] ?>°</span>
                                </div>
                                <div class="d-rain"><i class="fas fa-umbrella"></i>
                                    <?= $day['rain_prob'] ?>%
                                </div>
                                <div class="d-wind"><i class="fas fa-wind"></i> <?= $day['wind_max'] ?> km/h</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="empty-state">Météo indisponible pour le moment</div>
                </div>
            <?php endif; ?>
        </section>

        <!-- ===== ACTIVITÉ DÉTAILLÉE (chaque métrique à son échelle) ===== -->
        <section class="section" id="activite">
            <div class="section-head">
                <h2>Activité détaillée LibreAnalytics</h2>
                <span class="hint"><?= $period ?> jours</span>
            </div>
            <div class="multiples">
                <div class="mini">
                    <div class="mini-head">
                        <span class="t">Utilisateurs / jour</span>
                        <span class="v num" style="color: var(--violet)"
                            id="sum-users"><?= number_format(array_sum($chartPayload['series']['users'])) ?></span>
                    </div>
                    <div class="chart-sm"><canvas id="mini-users"></canvas></div>
                </div>
                <div class="mini">
                    <div class="mini-head">
                        <span class="t">Sites / jour</span>
                        <span class="v num" style="color: var(--blue)"
                            id="sum-sites"><?= number_format(array_sum($chartPayload['series']['sites'])) ?></span>
                    </div>
                    <div class="chart-sm"><canvas id="mini-sites"></canvas></div>
                </div>
                <div class="mini">
                    <div class="mini-head">
                        <span class="t">Visites / jour</span>
                        <span class="v num" style="color: var(--green)"
                            id="sum-visits"><?= number_format(array_sum($chartPayload['series']['visits'])) ?></span>
                    </div>
                    <div class="chart-sm"><canvas id="mini-visits"></canvas></div>
                </div>
                <div class="mini">
                    <div class="mini-head">
                        <span class="t">Visiteurs uniques / jour</span>
                        <span class="v num" style="color: var(--amber)"
                            id="sum-uniques"><?= number_format(array_sum($chartPayload['series']['uniques'])) ?></span>
                    </div>
                    <div class="chart-sm"><canvas id="mini-uniques"></canvas></div>
                </div>
            </div>
        </section>

        <!-- ===== TOP SITES + GÉO ===== -->
        <section class="section" id="geo">
            <div class="section-head">
                <h2>Top sites &amp; géographie</h2>
            </div>
            <div class="duo">
                <div class="card">
                    <div class="card-head">
                        <h3 class="card-title"><i class="fas fa-trophy"></i> Top 5 sites</h3>
                    </div>
                    <div class="card-body" style="height: 280px;">
                        <canvas id="topSitesChart"></canvas>
                    </div>
                </div>
                <div class="card">
                    <div class="card-head">
                        <h3 class="card-title"><i class="fas fa-earth-europe"></i> Pays visités</h3>
                        <div class="geo-toggle" role="tablist">
                            <button id="geo-bars" class="active">Barres</button>
                            <button id="geo-map">Carte</button>
                        </div>
                    </div>
                    <div class="card-body geo-view">
                        <canvas id="countriesChart"></canvas>
                        <div class="map-holder" id="map-holder">
                            <div id="worldMap"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== REGISTRE UTILISATEURS ===== -->
        <section class="section" id="users">
            <div class="section-head">
                <h2>Registre des utilisateurs (<?= count($usersList) ?>)</h2>
                <?php if (!empty($plansStats)): ?>
                    <div class="plan-chips" aria-label="Répartition par plan">
                        <?php foreach ($planOrder as $planKey => $planLabel):
                            if (!isset($plansStats[$planKey]))
                                continue; ?>
                            <span class="plan-chip"><?= $planLabel ?> <b
                                    class="num"><?= (int) $plansStats[$planKey] ?></b></span>
                        <?php endforeach; ?>
                        <?php foreach ($plansStats as $planKey => $count):
                            if (isset($planOrder[$planKey]))
                                continue; ?>
                            <span class="plan-chip"><?= htmlspecialchars(ucfirst($planKey)) ?> <b
                                    class="num"><?= (int) $count ?></b></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card">
                <div class="card-head">
                    <h3 class="card-title"><i class="fas fa-users"></i> Comptes</h3>
                    <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="search" id="user-search" placeholder="Rechercher un email…"
                                aria-label="Rechercher un email">
                        </div>
                        <a href="?export_emails=1" class="export-btn">
                            <i class="fas fa-download"></i> Export CSV
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="users-table">
                        <thead>
                            <tr>
                                <th class="sortable" data-sort="text">Email</th>
                                <th class="sortable" data-sort="text">Plan</th>
                                <th class="sortable" data-sort="num">Sites</th>
                                <th class="sortable" data-sort="num">Visites</th>
                                <th class="sortable" data-sort="num">Inscription</th>
                                <th class="sortable" data-sort="num">Dernière connexion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($usersList)): ?>
                                <?php foreach ($usersList as $user):
                                    $planClass = strtolower($user['plan'] ?? 'free');
                                    $createdTs = !empty($user['created_at']) ? strtotime($user['created_at']) : 0;
                                    $loginTs = !empty($user['last_login']) ? strtotime($user['last_login']) : 0;
                                    ?>
                                    <tr data-email="<?= htmlspecialchars(strtolower($user['email'])) ?>">
                                        <td data-label="Email"><?= htmlspecialchars($user['email']) ?></td>
                                        <td data-label="Plan"><span
                                                class="badge badge-<?= htmlspecialchars($planClass) ?>"><?= strtoupper($user['plan'] ?? 'free') ?></span>
                                        </td>
                                        <td data-label="Sites" data-value="<?= (int) ($user['site_count'] ?? 0) ?>">
                                            <?= (int) ($user['site_count'] ?? 0) ?>
                                        </td>
                                        <td data-label="Visites" data-value="<?= (int) ($user['total_visits'] ?? 0) ?>">
                                            <?= number_format($user['total_visits'] ?? 0) ?>
                                        </td>
                                        <td data-label="Inscription" data-value="<?= $createdTs ?>">
                                            <?= $createdTs ? date('d/m/Y', $createdTs) : '-' ?>
                                        </td>
                                        <td data-label="Dernière connexion" data-value="<?= $loginTs ?>">
                                            <?= $loginTs ? date('d/m/Y H:i', $loginTs) : '<span style="color:var(--text-3);">Jamais</span>' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="search-empty">
                                    <td colspan="6" class="empty-state">Aucun utilisateur ne correspond à cette
                                        recherche</td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-state">Aucun utilisateur enregistré</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div><!-- .shell -->

    <script>
        // Toutes les initialisations attendent les scripts defer (Chart, Swal)
        document.addEventListener('DOMContentLoaded', () => {

            // ===== DONNÉES SERVEUR =====
            const chartData = <?= json_encode($chartPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
            const topSites = <?= json_encode($topSites ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
            const countries = <?= json_encode($visitedCountries ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
            const cryptoData = <?= json_encode($coingeckoData ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

            Chart.defaults.font.family = "'Inter', sans-serif";
            Chart.defaults.color = '#6b7180';

            const GRID_COLOR = '#1b1e26';
            const METRIC_COLORS = {
                users: '#a78bfa',
                sites: '#58a6ff',
                visits: '#3fb950',
                uniques: '#e3b341'
            };

            // ===== SPARKLINES KPI =====
            document.querySelectorAll('[data-spark]').forEach(canvas => {
                const key = canvas.dataset.spark;
                new Chart(canvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            data: chartData.series[key],
                            borderColor: METRIC_COLORS[key],
                            backgroundColor: METRIC_COLORS[key] + '1a',
                            borderWidth: 1.5,
                            fill: true,
                            tension: 0.35,
                            pointRadius: 0,
                            pointHitRadius: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { x: { display: false }, y: { display: false, beginAtZero: true } }
                    }
                });
            });

            // ===== PETIT-MULTIPLES =====
            Object.keys(chartData.series).forEach(key => {
                const ctx = document.getElementById('mini-' + key);
                if (!ctx) return;
                new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            data: chartData.series[key],
                            backgroundColor: METRIC_COLORS[key] + 'b3',
                            borderColor: METRIC_COLORS[key],
                            borderWidth: 1,
                            borderRadius: 3,
                            maxBarThickness: 16
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    title: (items) => items[0].label,
                                    label: (ctx) => ' ' + ctx.parsed.y.toLocaleString('fr-FR')
                                }
                            }
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 24, font: { size: 10 } } },
                            y: { beginAtZero: true, grid: { color: GRID_COLOR }, ticks: { precision: 0, font: { size: 10 } } }
                        }
                    }
                });
            });

            // ===== BERRU.COM : visites quotidiennes =====
            const berruData = <?= json_encode($berruSeries, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
            const berruCanvas = document.getElementById('berruChart');
            if (berruCanvas && berruData) {
                new Chart(berruCanvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: berruData.labels,
                        datasets: [
                            { label: 'Visites', data: berruData.visits, borderColor: '#3fb950', backgroundColor: 'rgba(63,185,80,0.12)', borderWidth: 1.5, fill: true, tension: 0.3, pointRadius: 0, pointHitRadius: 8 },
                            { label: 'Visiteurs uniques', data: berruData.uniques, borderColor: '#e3b341', borderWidth: 1.5, borderDash: [4, 3], fill: false, tension: 0.3, pointRadius: 0, pointHitRadius: 8 }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: true, labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true } },
                            tooltip: { callbacks: { label: (ctx) => ' ' + ctx.dataset.label + ' : ' + ctx.parsed.y.toLocaleString('fr-FR') } }
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, autoSkipPadding: 24, font: { size: 10 } } },
                            y: { beginAtZero: true, grid: { color: GRID_COLOR }, ticks: { precision: 0, font: { size: 10 } } }
                        }
                    }
                });
            }

            // ===== TOP SITES : barres horizontales =====
            new Chart(document.getElementById('topSitesChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: topSites.map(s => s.site_name),
                    datasets: [{
                        data: topSites.map(s => Number(s.total_visits)),
                        backgroundColor: 'rgba(63,185,80,0.55)',
                        borderColor: '#3fb950',
                        borderWidth: 1,
                        borderRadius: 3
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: (ctx) => ' ' + ctx.parsed.x.toLocaleString('fr-FR') + ' visites' } }
                    },
                    scales: {
                        x: { beginAtZero: true, grid: { color: GRID_COLOR } },
                        y: { grid: { display: false }, ticks: { font: { size: 11 } } }
                    }
                }
            });

            // ===== GÉO : barres par défaut, carte amCharts en lazy =====
            const geoBarsBtn = document.getElementById('geo-bars');
            const geoMapBtn = document.getElementById('geo-map');
            const barsCanvas = document.getElementById('countriesChart');
            const mapHolder = document.getElementById('map-holder');

            const COUNTRY_CODES = {
                'france': 'FR', 'united states': 'US', 'usa': 'US', 'germany': 'DE',
                'united kingdom': 'GB', 'uk': 'GB', 'canada': 'CA', 'australia': 'AU',
                'japan': 'JP', 'china': 'CN', 'brazil': 'BR', 'india': 'IN',
                'italy': 'IT', 'spain': 'ES', 'netherlands': 'NL', 'belgium': 'BE',
                'switzerland': 'CH', 'portugal': 'PT', 'russia': 'RU', 'mexico': 'MX',
                'south korea': 'KR', 'singapore': 'SG'
            };

            const flagOf = (code) => code && code.length === 2
                ? String.fromCodePoint(...[...code.toUpperCase()].map(c => 0x1f1e6 + c.charCodeAt(0) - 65))
                : '';

            const getCountryCode = (name) => COUNTRY_CODES[String(name).toLowerCase().trim()] || null;

            const mappedCountries = countries
                .map(c => ({ name: c.country, code: getCountryCode(c.country), visits: Number(c.visits) }))
                .filter(c => c.code);

            new Chart(barsCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: mappedCountries.map(c => flagOf(c.code) + ' ' + c.name),
                    datasets: [{
                        data: mappedCountries.map(c => c.visits),
                        backgroundColor: 'rgba(167,139,250,0.55)',
                        borderColor: '#a78bfa',
                        borderWidth: 1,
                        borderRadius: 3
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: (ctx) => ' ' + ctx.parsed.x.toLocaleString('fr-FR') + ' visites' } }
                    },
                    scales: {
                        x: { beginAtZero: true, grid: { color: GRID_COLOR } },
                        y: { grid: { display: false }, ticks: { font: { size: 11 } } }
                    }
                }
            });

            // Chargement à la demande des scripts amCharts
            const AM_SCRIPTS = [
                'https://cdn.amcharts.com/lib/5/index.js',
                'https://cdn.amcharts.com/lib/5/map.js',
                'https://cdn.amcharts.com/lib/5/geodata/worldLow.js',
                'https://cdn.amcharts.com/lib/5/themes/Animated.js'
            ];

            let mapRequested = false;

            const loadScript = (src) => new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = src;
                s.onload = resolve;
                s.onerror = () => reject(new Error('load failed: ' + src));
                document.head.appendChild(s);
            });

            function initMap() {
                const root = am5.Root.new('worldMap');
                root.setThemes([am5themes_Animated.new(root)]);
                root._logo && root._logo.dispose && root._logo.dispose();

                const chart = root.container.children.push(
                    am5map.MapChart.new(root, {
                        panX: 'rotateX',
                        panY: 'translateY',
                        projection: am5map.geoMercator(),
                        layout: root.horizontalLayout
                    })
                );

                const polygonSeries = chart.series.push(
                    am5map.MapPolygonSeries.new(root, {
                        geoJSON: am5geodata_worldLow,
                        exclude: ['AQ']
                    })
                );

                polygonSeries.mapPolygons.template.setAll({
                    tooltipText: '{name}: {value} visites',
                    interactive: true,
                    fill: am5.color(0x1d212a),
                    stroke: am5.color(0x0b0c0f),
                    strokeWidth: 0.5
                });

                polygonSeries.data.setAll(mappedCountries.map(c => ({ id: c.code, value: c.visits })));
                polygonSeries.set('heatRules', [{
                    target: polygonSeries.mapPolygons.template,
                    min: am5.color(0x23262f),
                    max: am5.color(0x3fb950),
                    dataField: 'value'
                }]);

                polygonSeries.mapPolygons.template.states.create('hover', {
                    fill: am5.color(0xa78bfa)
                });
            }

            async function showMap() {
                mapHolder.classList.add('visible');
                barsCanvas.style.display = 'none';
                geoMapBtn.classList.add('active');
                geoBarsBtn.classList.remove('active');

                if (!mapRequested) {
                    mapRequested = true;
                    mapHolder.innerHTML = '<div class="map-loading">Chargement de la carte…</div>';
                    try {
                        for (const src of AM_SCRIPTS) {
                            await loadScript(src);
                        }
                        mapHolder.innerHTML = '<div id="worldMap" style="height:100%;"></div>';
                        initMap();
                    } catch (e) {
                        mapHolder.innerHTML = '<div class="map-loading">Carte indisponible</div>';
                    }
                }
            }

            function showBars() {
                mapHolder.classList.remove('visible');
                barsCanvas.style.display = '';
                geoBarsBtn.classList.add('active');
                geoMapBtn.classList.remove('active');
            }

            geoMapBtn.addEventListener('click', showMap);
            geoBarsBtn.addEventListener('click', showBars);

            // ===== POPOVERS : ouverture/fermeture (météo + menu) =====
            function bindPopover(btnId, popId) {
                const btn = document.getElementById(btnId);
                const pop = document.getElementById(popId);
                if (!btn || !pop) return;
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    // ferme l'autre popover
                    document.querySelectorAll('.pop.open').forEach(p => {
                        if (p !== pop) {
                            p.classList.remove('open');
                            const other = document.querySelector('[aria-controls="' + p.id + '"]');
                            if (other) other.setAttribute('aria-expanded', 'false');
                        }
                    });
                    const open = pop.classList.toggle('open');
                    btn.setAttribute('aria-expanded', open);
                });
            }

            bindPopover('menu-toggle', 'menu-nav');
            bindPopover('cmd-toggle', 'cmd-menu');

            // Le menu de navigation referme le popover après un clic sur une ancre
            const menuNav = document.getElementById('menu-nav');
            if (menuNav) {
                menuNav.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
                    menuNav.classList.remove('open');
                    document.getElementById('menu-toggle').setAttribute('aria-expanded', 'false');
                }));
            }

            // Bouton retour : historique navigateur, sinon page compte
            const backBtn = document.getElementById('back-btn');
            if (backBtn) {
                backBtn.addEventListener('click', () => {
                    if (history.length > 1) {
                        history.back();
                    } else {
                        location.href = 'account.php';
                    }
                });
            }

            document.addEventListener('click', (e) => {
                document.querySelectorAll('.pop.open').forEach(p => {
                    if (!p.contains(e.target)) {
                        p.classList.remove('open');
                        const btn = document.querySelector('[aria-controls="' + p.id + '"]');
                        if (btn) btn.setAttribute('aria-expanded', 'false');
                    }
                });
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.pop.open').forEach(p => {
                        p.classList.remove('open');
                        const btn = document.querySelector('[aria-controls="' + p.id + '"]');
                        if (btn) btn.setAttribute('aria-expanded', 'false');
                    });
                }
            });

            // ===== WALLET CRYPTO =====
            const tokenHoldings = {
                bitcoin: 0,
                solana: 4.67,
                sui: 1675,
                'usd-coin': 0,
                ami: 35535,
            };

            // Capital investi à l'origine (€) 
            // sol=312 € à 68 l'unité, 
            // sui=1600€ à 0.95 l'unité, 
            // ami=44€ à 0.001237 l'unité, 
            // btc=0 € à 0 l'unité, 
            // usdc=0 € à 0 l'unité
            const INITIAL_INVESTMENT = 1956;

            (function renderCrypto() {
                const container = document.getElementById('crypto-prices');
                const hero = document.getElementById('portfolio-total');

                if (!Array.isArray(cryptoData) || cryptoData.length === 0) {
                    container.innerHTML = '<div class="empty-state">Prix crypto indisponibles</div>';
                    return;
                }

                let totalPortfolioValue = 0;

                cryptoData.forEach(crypto => {
                    const price = crypto.current_price ?? 0;
                    const change24h = crypto.price_change_percentage_24h ?? 0;
                    const changeTxt = (change24h >= 0 ? '+' : '') + change24h.toFixed(2);
                    const holdings = tokenHoldings[crypto.id] || 0;
                    const totalValue = price * holdings;
                    totalPortfolioValue += totalValue;

                    const el = document.createElement('div');
                    el.id = crypto.id;
                    el.classList.add('crypto-item');
                    el.innerHTML = `
                        <img src="${crypto.image}" alt="${crypto.id} logo">
                        <div class="name">
                            <span class="symbol"><a href="https://www.coingecko.com/fr/pi%C3%A8ces/${crypto.id}" target="_blank" rel="noopener" title="Voir ${crypto.symbol.toUpperCase()} sur CoinGecko">${crypto.symbol.toUpperCase()}</a></span>
                            <span class="price num">${price.toLocaleString('fr-FR', { minimumFractionDigits: 2 })} €</span>
                        </div>
                        <div class="holdings">
                            <span class="amount num">${holdings} ${crypto.symbol.toUpperCase()}</span>
                            <span class="value num">${totalValue.toFixed(2)} €</span>
                        </div>
                        <p class="change num ${change24h >= 0 ? 'positive' : 'negative'}">${changeTxt}%</p>
                    `;
                    container.appendChild(el);
                });

                const pnl = totalPortfolioValue - INITIAL_INVESTMENT;
                const isGain = pnl >= 0;
                const pnlPct = INITIAL_INVESTMENT > 0 ? (pnl / INITIAL_INVESTMENT * 100) : 0;
                const fmt = new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                hero.innerHTML =
                    `<div>
                        <div class="label">Valeur du portefeuille</div>
                        <div class="total num">${fmt.format(totalPortfolioValue)} €</div>
                        <div class="invested num">Investi : ${fmt.format(INITIAL_INVESTMENT)} €</div>
                    </div>
                    <div class="pnl-box">
                        <span class="pnl-pct num ${isGain ? 'positive' : 'negative'}">${(isGain ? '+' : '') + pnlPct.toFixed(1)}%</span>
                        <div class="pnl num ${isGain ? 'positive' : 'negative'}">${(isGain ? '+' : '') + fmt.format(pnl)} €</div>
                        <span class="pnl-label">vs investi</span>
                    </div>`;
            })();

            document.getElementById('wallet-links-btn').addEventListener('click', () => {
                Swal.fire({
                    title: '0x',
                    html: `<ul>
                        <li><a href="https://accounts.binance.com/register?ref=">Binance</a>.com</li>
                        <li><a href="https://shop.ledger.com/?r=">Ledger</a>/live</li>
                        <li><a href="https://app.uniswap.org">Uniswap</a>.org</li>
                        <li><a href="#">Phantom</a>/app</li>
                        <li><a href="https://solscan.io/account/D6khWoqvc2zX46HVtSZcNrPumnPLPM72SnSuDhBrZeTC#portfolio">Solscan</a>.io</li>
                        <li><a href="https://pump.fun/profile/D6khWo">Pump</a>.fun</li>
                        <li><a href="https://jup.ag">jup</a>.ag</li>
                    </ul>`,
                    showCloseButton: true,
                    showConfirmButton: false,
                    customClass: {
                        popup: 'custom-swal-popup',
                        closeButton: 'custom-swal-close-button',
                        content: 'custom-swal-content',
                    }
                });
            });

            // ===== TABLE USERS : recherche + tri =====
            const table = document.getElementById('users-table');
            const tbody = table.querySelector('tbody');
            const rows = [...tbody.querySelectorAll('tr')].filter(r => r.dataset.email);
            const emptyRow = tbody.querySelector('.search-empty');

            document.getElementById('user-search').addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                let visible = 0;
                rows.forEach(row => {
                    const match = !q || row.dataset.email.includes(q);
                    row.style.display = match ? '' : 'none';
                    if (match) visible++;
                });
                if (emptyRow) emptyRow.style.display = (q && visible === 0) ? '' : 'none';
            });

            table.querySelectorAll('th.sortable').forEach((th, colIndex) => {
                th.addEventListener('click', () => {
                    const type = th.dataset.sort;
                    const isAsc = th.classList.contains('sorted-asc');
                    const dir = isAsc ? -1 : 1;

                    table.querySelectorAll('th.sortable').forEach(h => {
                        h.classList.remove('sorted-asc', 'sorted-desc');
                    });
                    th.classList.add(isAsc ? 'sorted-desc' : 'sorted-asc');

                    rows.sort((a, b) => {
                        const cellA = a.children[colIndex];
                        const cellB = b.children[colIndex];
                        let vA = cellA.dataset.value ?? cellA.textContent.trim();
                        let vB = cellB.dataset.value ?? cellB.textContent.trim();
                        if (type === 'num') {
                            return ((Number(vA) || 0) - (Number(vB) || 0)) * dir;
                        }
                        return String(vA).localeCompare(String(vB), 'fr') * dir;
                    });

                    rows.forEach(row => tbody.appendChild(row));
                    if (emptyRow) tbody.appendChild(emptyRow);
                });
            });
        });
    </script>
</body>

</html>