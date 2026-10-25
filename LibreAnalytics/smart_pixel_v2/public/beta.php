<?php
session_start();

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
// PÉRIODE SÉLECTIONNÉE (7 / 30 / 90 jours) — pilote les requêtes
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
    $totalSites  = (int) $pdo->query("SELECT COUNT(*) FROM user_sites")->fetchColumn();
    $totalUsers  = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

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
    fputcsv($out, ['Email', 'Plan', 'Nombre de sites', 'Date d\'inscription', 'Derniere connexion']);
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
            'sparkline' => 'false',
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
        0  => ['Ciel dégagé', 'fa-sun'],
        1  => ['Plutôt dégagé', 'fa-sun'],
        2  => ['Partiellement nuageux', 'fa-cloud-sun'],
        3  => ['Couvert', 'fa-cloud'],
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
        'latitude'      => 47.1667,
        'longitude'     => -1.4667,
        'daily'         => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max,wind_speed_10m_max',
        'timezone'      => 'Europe/Paris',
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
            'date'      => $day,
            'day_label' => $i === 0 ? "Aujourd'hui" : ($i === 1 ? 'Demain' : (new DateTime($day))->format('D d/m')),
            'wmo'       => $wmo,
            'label'     => $label,
            'icon'      => $icon,
            't_max'     => round((float) ($weatherRaw['daily']['temperature_2m_max'][$i] ?? 0)),
            't_min'     => round((float) ($weatherRaw['daily']['temperature_2m_min'][$i] ?? 0)),
            'rain_prob' => (int) ($weatherRaw['daily']['precipitation_probability_max'][$i] ?? 0),
            'wind_max'  => round((float) ($weatherRaw['daily']['wind_speed_10m_max'][$i] ?? 0)),
        ];
    }
}

// Données passées aux graphiques
$chartPayload = [
    'period' => $period,
    'labels' => array_map(fn ($r) => (new DateTime($r['date']))->format('d/m'), $dailySeries),
    'series' => [
        'users'   => array_map('intval', array_column($dailySeries, 'new_users')),
        'sites'   => array_map('intval', array_column($dailySeries, 'new_sites')),
        'visits'  => array_map('intval', array_column($dailySeries, 'visits')),
        'uniques' => array_map('intval', array_column($dailySeries, 'unique_visitors')),
    ],
];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>LibreAnalytics — Contrôle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=JetBrains+Mono:wght@400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Perf UX : scripts non bloquants -->
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <!-- amCharts : chargé à la demande, uniquement si la carte est demandée -->
    <style>
        :root {
            --ink: #0d0f14;
            --ink-2: #13161d;
            --ink-3: #1a1e27;
            --ink-4: #212634;
            --line: #262b38;
            --line-soft: #1c212d;
            --txt: #eef0f6;
            --txt-dim: #9aa0b4;
            --txt-faint: #62688a;
            --lime: #34d399;
            --acid: #62faa4;
            --violet: #ab9ff2;
            --rose: #ff7a8a;
            --blue: #86baff;
            --mono: 'JetBrains Mono', ui-monospace, monospace;
            --grot: 'Space Grotesk', system-ui, sans-serif;
            --s-1: 0.25rem;
            --s-2: 0.5rem;
            --s-3: 0.75rem;
            --s-4: 1rem;
            --s-5: 1.5rem;
            --s-6: 2.25rem;
            --s-7: 3.25rem;
            --r-1: 3px;
            --r-2: 6px;
            --shadow: 0 1px 2px rgba(0, 0, 0, 0.4), 0 8px 24px rgba(0, 0, 0, 0.18);
            --t-fast: 120ms cubic-bezier(0.4, 0, 0.2, 1);
            --t-med: 220ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
            scrollbar-color: var(--ink-4) transparent;
        }

        body {
            font-family: var(--grot);
            font-size: 15px;
            background: var(--ink);
            color: var(--txt);
            line-height: 1.55;
            background-image:
                radial-gradient(1100px 500px at 80% -10%, rgba(171, 159, 242, 0.05), transparent 60%),
                radial-gradient(900px 400px at 10% -5%, rgba(200, 246, 93, 0.035), transparent 55%);
            background-repeat: no-repeat;
        }

        ::selection {
            background: var(--acid);
            color: var(--ink);
        }

        :focus-visible {
            outline: 2px solid var(--lime);
            outline-offset: 2px;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button {
            font: inherit;
        }

        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--ink-4);
            border-radius: var(--r-1);
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        .shell {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 clamp(1rem, 3.5vw, 2.5rem) var(--s-7);
        }

        /* ===== TOPBAR ===== */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 50;
            background: color-mix(in srgb, var(--ink) 82%, transparent);
            backdrop-filter: blur(14px) saturate(1.2);
            border-bottom: 1px solid var(--line);
        }

        .topbar-inner {
            max-width: 1440px;
            margin: 0 auto;
            padding: var(--s-3) clamp(1rem, 3.5vw, 2.5rem);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--s-4);
        }

        .brand {
            display: flex;
            align-items: baseline;
            gap: var(--s-2);
        }

        .brand .sigil {
            font-family: var(--mono);
            font-weight: 700;
            font-size: 1.05rem;
            color: var(--lime);
            letter-spacing: -1px;
        }

        .brand small {
            font-family: var(--mono);
            font-size: 0.7rem;
            color: var(--txt-faint);
            text-transform: uppercase;
            letter-spacing: 0.15em;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: var(--s-3);
        }

        .live-dot {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-family: var(--mono);
            font-size: 0.7rem;
            color: var(--txt-dim);
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        .live-dot::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--lime);
            box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.15);
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

        /* ===== MÉTÉO : pill topbar + popover ===== */
        .wx-wrap {
            position: relative;
        }

        .wx-pill {
            font-family: var(--mono);
            font-size: 0.75rem;
            color: var(--txt);
            background: var(--ink-3);
            border: 1px solid var(--line);
            border-radius: var(--r-1);
            padding: 0.5rem 0.85rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            transition: border-color var(--t-fast), color var(--t-fast);
        }

        .wx-pill i {
            color: var(--acid);
        }

        .wx-pill:hover,
        .wx-pill[aria-expanded="true"] {
            border-color: var(--lime);
            color: var(--lime);
        }

        .wx-pop {
            position: absolute;
            right: 0;
            top: calc(100% + 0.5rem);
            width: min(340px, calc(100vw - 2rem));
            background: var(--ink-2);
            border: 1px solid var(--line);
            border-radius: var(--r-2);
            box-shadow: var(--shadow);
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px);
            transition: opacity var(--t-fast), transform var(--t-fast), visibility var(--t-fast);
            z-index: 60;
        }

        .wx-pop.open {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .wx-pop .weather-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.65rem 1rem;
            border-bottom: 1px solid var(--line);
        }

        .wx-pop .loc {
            font-family: var(--mono);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .wx-pop .loc i {
            color: var(--lime);
        }

        .wx-pop .src {
            font-family: var(--mono);
            font-size: 0.7rem;
            color: var(--txt-faint);
            letter-spacing: 0.08em;
        }

        .weather-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
        }

        .weather-day {
            padding: var(--s-3) var(--s-2);
            text-align: center;
            border-right: 1px solid var(--line-soft);
            position: relative;
            transition: background var(--t-fast);
        }

        .weather-day:last-child {
            border-right: none;
        }

        .weather-day:hover {
            background: var(--ink-3);
        }

        .weather-day.today::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 28px;
            height: 2px;
            background: var(--acid);
        }

        .weather-day .d-label {
            font-family: var(--mono);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--txt-dim);
            margin-bottom: var(--s-2);
        }

        .weather-day .d-icon {
            font-size: 1.4rem;
            color: var(--acid);
            margin-bottom: var(--s-1);
        }

        .weather-day .d-desc {
            font-family: var(--mono);
            font-size: 0.7rem;
            color: var(--txt-dim);
            margin-bottom: var(--s-2);
            min-height: 1.6em;
        }

        .weather-day .d-temps {
            font-family: var(--mono);
            font-size: 0.95rem;
            font-weight: 700;
        }

        .weather-day .d-temps .min {
            color: var(--txt-faint);
            font-weight: 400;
        }

        .weather-day .d-rain {
            font-family: var(--mono);
            font-size: 0.7rem;
            color: var(--blue);
            margin-top: 0.3rem;
        }

        .weather-day .d-wind {
            font-family: var(--mono);
            font-size: 0.7rem;
            color: var(--txt-faint);
            margin-top: 0.15rem;
        }

        .weather-chart {
            height: 90px;
            padding: 0.4rem 0.6rem 0.5rem;
            border-top: 1px solid var(--line-soft);
        }

        @media (max-width: 520px) {
            .weather-grid {
                grid-template-columns: 1fr;
            }

            .weather-day {
                border-right: none;
                border-bottom: 1px solid var(--line-soft);
            }
        }

        /* ===== MENU "COMMANDES" ===== */
        .cmd {
            position: relative;
        }

        .cmd-toggle {
            font-family: var(--mono);
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--txt);
            background: var(--ink-3);
            border: 1px solid var(--line);
            border-radius: var(--r-1);
            padding: 0.55rem 1rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            transition: border-color var(--t-fast), color var(--t-fast), background var(--t-fast);
        }

        .cmd-toggle:hover,
        .cmd-toggle[aria-expanded="true"] {
            border-color: var(--lime);
            color: var(--lime);
        }

        .cmd-toggle .caret {
            transition: transform var(--t-med);
            font-size: 0.6rem;
        }

        .cmd.open .cmd-toggle .caret {
            transform: rotate(180deg);
        }

        .cmd-menu {
            position: absolute;
            right: 0;
            top: calc(100% + 0.5rem);
            min-width: 270px;
            background: var(--ink-2);
            border: 1px solid var(--line);
            border-radius: var(--r-2);
            box-shadow: var(--shadow);
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px);
            transition: opacity var(--t-fast), transform var(--t-fast), visibility var(--t-fast);
        }

        .cmd.open .cmd-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .cmd-menu a {
            display: flex;
            align-items: center;
            gap: var(--s-3);
            padding: 0.7rem 1.1rem;
            font-size: 0.82rem;
            border-bottom: 1px solid var(--line-soft);
            transition: background var(--t-fast), color var(--t-fast), padding-left var(--t-fast);
        }

        .cmd-menu a:last-child {
            border-bottom: none;
        }

        .cmd-menu a i {
            width: 1.1rem;
            text-align: center;
            color: var(--violet);
            font-size: 0.78rem;
        }

        .cmd-menu a:hover {
            background: var(--ink-3);
            color: var(--lime);
            padding-left: 1.4rem;
        }

        /* ===== BANDEAU CONTEXTE + SÉLECTEUR DE PÉRIODE ===== */
        .ctx-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: var(--s-3);
            padding: var(--s-5) 0 var(--s-4);
        }

        .ctx-bar .meta {
            font-family: var(--mono);
            font-size: 0.75rem;
            color: var(--txt-dim);
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        .ctx-bar .meta strong {
            color: var(--lime);
        }

        .period-switch {
            display: inline-flex;
            border: 1px solid var(--line);
            border-radius: var(--r-1);
            background: var(--ink-2);
            overflow: hidden;
        }

        .period-switch a {
            font-family: var(--mono);
            font-size: 0.75rem;
            padding: 0.45rem 0.95rem;
            color: var(--txt-dim);
            letter-spacing: 0.06em;
            transition: background var(--t-fast), color var(--t-fast);
        }

        .period-switch a+a {
            border-left: 1px solid var(--line);
        }

        .period-switch a:hover {
            color: var(--txt);
        }

        .period-switch a.active {
            background: var(--lime);
            color: var(--ink);
            font-weight: 700;
        }

        /* ===== SECTIONS ===== */
        .section {
            margin-bottom: var(--s-6);
        }

        .section-head {
            display: flex;
            align-items: center;
            gap: var(--s-3);
            margin-bottom: var(--s-4);
        }

        .section-head .index {
            font-family: var(--mono);
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--lime);
            background: rgba(52, 211, 153, 0.08);
            border: 1px solid rgba(52, 211, 153, 0.25);
            border-radius: var(--r-1);
            padding: 0.15rem 0.5rem;
        }

        .section-head h2 {
            font-size: 0.82rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.2em;
        }

        .section-head::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, var(--line), transparent);
        }

        /* ===== KPI : valeur + delta + sparkline ===== */
        .kpi-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: var(--s-4);
        }

        .kpi {
            background: var(--ink-2);
            border: 1px solid var(--line);
            border-radius: var(--r-2);
            box-shadow: var(--shadow);
            padding: var(--s-4);
            position: relative;
            overflow: hidden;
            transition: border-color var(--t-fast);
        }

        .kpi:hover {
            border-color: var(--ink-4);
        }

        .kpi-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: var(--s-2);
            margin-bottom: var(--s-2);
        }

        .kpi-label {
            font-family: var(--mono);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--txt-dim);
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }

        .kpi-label i {
            color: var(--violet);
            font-size: 0.72rem;
        }

        .kpi-delta {
            font-family: var(--mono);
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.1rem 0.45rem;
            border-radius: 999px;
            letter-spacing: 0.04em;
        }

        .kpi-delta.up {
            color: var(--lime);
            background: rgba(52, 211, 153, 0.1);
        }

        .kpi-delta.down {
            color: var(--rose);
            background: rgba(255, 122, 138, 0.1);
        }

        .kpi-delta.flat {
            color: var(--txt-dim);
            background: var(--ink-3);
        }

        .kpi-value {
            font-family: var(--mono);
            font-size: clamp(1.6rem, 3vw, 2.2rem);
            font-weight: 700;
            letter-spacing: -0.04em;
            font-variant-numeric: tabular-nums;
        }

        .kpi-sub {
            font-family: var(--mono);
            font-size: 0.7rem;
            color: var(--txt-faint);
            margin-top: 0.15rem;
        }

        .kpi .spark {
            height: 38px;
            margin-top: var(--s-3);
        }

        @media (max-width: 1000px) {
            .kpi-strip {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 560px) {
            .kpi-strip {
                grid-template-columns: 1fr;
            }
        }

        /* ===== PANNEAUX ===== */
        .panel {
            background: var(--ink-2);
            border: 1px solid var(--line);
            border-radius: var(--r-2);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 18px;
            height: 2px;
            background: var(--lime);
        }

        .panel-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--s-3);
            padding: var(--s-4) var(--s-5);
            border-bottom: 1px solid var(--line-soft);
        }

        .panel-title {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }

        .panel-title i {
            color: var(--lime);
            font-size: 0.82rem;
        }

        .panel-body {
            padding: var(--s-4);
        }

        .duo {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--s-4);
            margin-bottom: var(--s-4);
        }

        @media (max-width: 1000px) {
            .duo {
                grid-template-columns: 1fr;
            }
        }

        /* ===== PETIT-MULTIPLES ===== */
        .multiples {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--s-4);
        }

        @media (max-width: 1000px) {
            .multiples {
                grid-template-columns: 1fr;
            }
        }

        .mini {
            background: var(--ink-2);
            border: 1px solid var(--line);
            border-radius: var(--r-2);
            padding: var(--s-4);
        }

        .mini-head {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: var(--s-2);
        }

        .mini-head .t {
            font-family: var(--mono);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--txt-dim);
        }

        .mini-head .v {
            font-family: var(--mono);
            font-size: 1.15rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }

        .mini .chart-sm {
            height: 130px;
        }

        /* ===== GÉO : toggle barres / carte ===== */
        .geo-toggle {
            display: inline-flex;
            border: 1px solid var(--line);
            border-radius: var(--r-1);
            overflow: hidden;
        }

        .geo-toggle button {
            font-family: var(--mono);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 0.4rem 0.85rem;
            background: transparent;
            color: var(--txt-dim);
            border: none;
            cursor: pointer;
            transition: background var(--t-fast), color var(--t-fast);
        }

        .geo-toggle button+button {
            border-left: 1px solid var(--line);
        }

        .geo-toggle button.active {
            background: var(--violet);
            color: var(--ink);
            font-weight: 700;
        }

        .geo-view {
            height: 360px;
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
            font-family: var(--mono);
            font-size: 0.8rem;
            color: var(--txt-dim);
        }

        /* ===== WALLET ===== */
        #portfolio-total {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: var(--s-2);
            padding: var(--s-4);
            background: linear-gradient(180deg, var(--ink-3), var(--ink-2));
            border: 1px solid var(--line);
            border-left: 3px solid var(--lime);
            border-radius: var(--r-1);
            margin-bottom: var(--s-3);
        }

        #portfolio-total .label {
            font-family: var(--mono);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: var(--txt-dim);
        }

        #portfolio-total h3 {
            font-family: var(--mono);
            font-size: clamp(1.4rem, 3.5vw, 2rem);
            font-weight: 700;
            color: var(--lime);
            letter-spacing: -0.04em;
            font-variant-numeric: tabular-nums;
        }

        #portfolio-total .pnl {
            font-family: var(--mono);
            font-size: 0.85rem;
            font-weight: 700;
        }

        #portfolio-total .pnl.positive {
            color: var(--acid);
        }

        #portfolio-total .pnl.negative {
            color: var(--rose);
        }

        #crypto-prices {
            display: flex;
            flex-direction: column;
        }

        .crypto-item {
            display: grid;
            grid-template-columns: 36px 1fr auto auto;
            align-items: center;
            gap: var(--s-4);
            padding: var(--s-3) var(--s-4);
            border-bottom: 1px solid var(--line-soft);
            transition: background var(--t-fast);
        }

        .crypto-item:last-child {
            border-bottom: none;
        }

        .crypto-item:hover {
            background: var(--ink-3);
        }

        .crypto-item img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            filter: grayscale(30%);
            transition: filter var(--t-med), transform var(--t-med);
        }

        .crypto-item:hover img {
            filter: none;
            transform: scale(1.08);
        }

        .crypto-item .name {
            display: flex;
            flex-direction: column;
        }

        .crypto-item .symbol {
            font-family: var(--mono);
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.08em;
        }

        .crypto-item .price {
            font-family: var(--mono);
            font-size: 0.75rem;
            color: var(--txt-dim);
        }

        .crypto-item .holdings {
            display: flex;
            flex-direction: column;
            text-align: right;
        }

        .crypto-item .holdings .amount {
            font-family: var(--mono);
            font-size: 0.75rem;
            color: var(--txt-dim);
        }

        .crypto-item .holdings .value {
            font-family: var(--mono);
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--violet);
        }

        .crypto-item .change {
            font-family: var(--mono);
            font-size: 0.85rem;
            font-weight: 700;
            min-width: 72px;
            text-align: right;
        }

        .crypto-item .change.positive::before {
            content: '▲ ';
            font-size: 0.6rem;
        }

        .crypto-item .change.negative::before {
            content: '▼ ';
            font-size: 0.6rem;
        }

        .crypto-item .change.positive {
            color: var(--lime);
        }

        .crypto-item .change.negative {
            color: var(--rose);
        }

        @media (max-width: 420px) {
            .crypto-item {
                grid-template-columns: 32px 1fr auto;
            }

            .crypto-item .holdings {
                display: none;
            }
        }

        .wallet-row {
            display: flex;
            align-items: center;
            gap: var(--s-3);
            padding: var(--s-3) 0;
            border-bottom: 1px dashed var(--line);
        }

        .wallet-row:last-child {
            border-bottom: none;
        }

        .wallet-row .network {
            font-family: var(--mono);
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--ink);
            background: var(--lime);
            border-radius: var(--r-1);
            padding: 0.3rem 0.5rem;
            min-width: 52px;
            text-align: center;
        }

        .wallet-row .address {
            flex: 1;
            font-family: var(--mono);
            font-size: 0.75rem;
            color: var(--txt-dim);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .copy-button {
            font-family: var(--mono);
            font-size: 0.7rem;
            text-transform: uppercase;
            border: 1px solid var(--violet);
            border-radius: var(--r-1);
            background: transparent;
            color: var(--violet);
            padding: 0.4rem 0.8rem;
            cursor: pointer;
            transition: background var(--t-fast), color var(--t-fast);
        }

        .copy-button:hover {
            background: var(--violet);
            color: var(--ink);
        }

        .wallet-links-btn {
            font-family: var(--mono);
            font-size: 0.7rem;
            text-transform: uppercase;
            border: 1px solid var(--line);
            border-radius: var(--r-1);
            background: transparent;
            color: var(--txt);
            padding: 0.5rem 0.9rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: border-color var(--t-fast), color var(--t-fast);
        }

        .wallet-links-btn i {
            color: var(--lime);
        }

        .wallet-links-btn:hover {
            border-color: var(--lime);
            color: var(--lime);
        }

        /* ===== TABLE USERS : recherche + tri ===== */
        .search-box {
            position: relative;
        }

        .search-box input {
            font-family: var(--mono);
            font-size: 0.78rem;
            color: var(--txt);
            background: var(--ink-3);
            border: 1px solid var(--line);
            border-radius: var(--r-1);
            padding: 0.5rem 0.8rem 0.5rem 2.1rem;
            width: min(260px, 60vw);
            transition: border-color var(--t-fast);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--lime);
        }

        .search-box i {
            position: absolute;
            left: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--txt-faint);
            font-size: 0.75rem;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-family: var(--mono);
            font-size: 0.78rem;
        }

        .data-table th {
            text-align: left;
            padding: 0.75rem var(--s-4);
            color: var(--txt-faint);
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            border-bottom: 1px solid var(--line);
            white-space: nowrap;
            background: var(--ink-2);
            user-select: none;
        }

        .data-table th.sortable {
            cursor: pointer;
            transition: color var(--t-fast);
        }

        .data-table th.sortable:hover {
            color: var(--lime);
        }

        .data-table th.sortable::after {
            content: '↕';
            margin-left: 0.4rem;
            opacity: 0.4;
            font-size: 0.7rem;
        }

        .data-table th.sortable.sorted-asc::after {
            content: '↑';
            opacity: 1;
            color: var(--lime);
        }

        .data-table th.sortable.sorted-desc::after {
            content: '↓';
            opacity: 1;
            color: var(--lime);
        }

        .data-table td {
            padding: 0.75rem var(--s-4);
            border-bottom: 1px solid var(--line-soft);
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .data-table tbody tr {
            transition: background var(--t-fast);
        }

        .data-table tbody tr:hover {
            background: var(--ink-3);
        }

        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        .data-table code {
            background: var(--ink-3);
            border-radius: var(--r-1);
            padding: 0.15rem 0.4rem;
            color: var(--lime);
            font-size: 0.75rem;
        }

        .search-empty {
            display: none;
        }

        .badge {
            display: inline-block;
            padding: 0.22rem 0.6rem;
            font-family: var(--mono);
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border: 1px solid;
            border-radius: 999px;
        }

        .badge-free {
            color: var(--txt-dim);
            border-color: var(--line);
        }

        .badge-pro {
            background: rgba(52, 211, 153, 0.08);
            color: var(--lime);
            border-color: rgba(52, 211, 153, 0.5);
        }

        .badge-business {
            background: rgba(255, 122, 138, 0.08);
            color: var(--rose);
            border-color: rgba(255, 122, 138, 0.5);
        }

        .badge-premium {
            background: rgba(171, 159, 242, 0.08);
            color: var(--violet);
            border-color: rgba(171, 159, 242, 0.5);
        }

        .export-btn {
            font-family: var(--mono);
            font-size: 0.7rem;
            text-transform: uppercase;
            background: var(--lime);
            color: var(--ink);
            padding: 0.55rem 1rem;
            border: none;
            border-radius: var(--r-1);
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: filter var(--t-fast);
        }

        .export-btn:hover {
            filter: brightness(1.12);
        }

        /* ===== ÉTATS VIDES ===== */
        .empty-state {
            text-align: center;
            padding: var(--s-6) var(--s-4);
            color: var(--txt-dim);
            font-family: var(--mono);
            font-size: 0.8rem;
            font-style: italic;
        }

        /* ===== SWEETALERT & TOASTIFY ===== */
        .custom-swal-popup {
            background: var(--ink-2) !important;
            color: var(--txt) !important;
            border: 1px solid var(--line) !important;
            border-radius: var(--r-2) !important;
            font-family: var(--grot) !important;
        }

        .custom-swal-popup h2 {
            font-family: var(--mono) !important;
            color: var(--lime) !important;
            letter-spacing: 0.15em;
        }

        .custom-swal-close-button {
            color: var(--txt-dim) !important;
        }

        .custom-swal-content ul {
            list-style: none;
            padding: 0;
            text-align: left;
        }

        .custom-swal-content li {
            padding: 0.7rem 0;
            border-bottom: 1px solid var(--line);
            font-family: var(--mono);
            font-size: 0.84rem;
        }

        .custom-swal-content li:last-child {
            border-bottom: none;
        }

        .custom-swal-content a {
            color: var(--lime);
            font-weight: 700;
            border-bottom: 1px dotted rgba(52, 211, 153, 0.4);
        }

        .toastify {
            font-family: var(--mono) !important;
            border-radius: var(--r-1) !important;
            border-left: 3px solid var(--ink) !important;
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
            <a class="brand" href="dashboard.php">
                <span class="sigil">LB—PX</span>
                <small>LibreAnalytics / contrôle</small>
            </a>
            <div class="topbar-right">
                <!-- Météo : pill compacte, popover au clic -->
                <?php if (!empty($weatherDays)): ?>
                    <div class="wx-wrap">
                        <button class="wx-pill" id="wx-pill" aria-expanded="false" aria-controls="wx-pop">
                            <i class="fas <?= htmlspecialchars($weatherDays[0]['icon']) ?>"></i>
                            <?= $weatherDays[0]['t_max'] ?>° / <?= $weatherDays[0]['t_min'] ?>° Vertou
                        </button>
                        <div class="wx-pop" id="wx-pop" role="dialog" aria-label="Météo Vertou 3 jours">
                            <div class="weather-head">
                                <span class="loc"><i class="fas fa-location-dot"></i> Vertou (44)</span>
                                <span class="src">open-meteo · j+2</span>
                            </div>
                            <div class="weather-grid">
                                <?php foreach ($weatherDays as $i => $day): ?>
                                    <div class="weather-day <?= $i === 0 ? 'today' : '' ?>">
                                        <div class="d-label"><?= htmlspecialchars($day['day_label']) ?></div>
                                        <div class="d-icon"><i class="fas <?= htmlspecialchars($day['icon']) ?>"></i></div>
                                        <div class="d-desc"><?= htmlspecialchars($day['label']) ?></div>
                                        <div class="d-temps"><?= $day['t_max'] ?>° <span
                                                class="min">/ <?= $day['t_min'] ?>°</span></div>
                                        <div class="d-rain"><i class="fas fa-umbrella"></i>
                                            <?= $day['rain_prob'] ?>%</div>
                                        <div class="d-wind"><i class="fas fa-wind"></i> <?= $day['wind_max'] ?> km/h</div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="weather-chart">
                                <canvas id="weatherChart"></canvas>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <span class="live-dot">API</span>
                <nav class="cmd" id="cmd-menu">
                    <button class="cmd-toggle" id="cmd-toggle" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-terminal"></i> Commandes <i class="fas fa-chevron-down caret"></i>
                    </button>
                    <div class="cmd-menu" role="menu">
                        <a href="dashboard.php" role="menuitem"><i class="fas fa-arrow-left"></i> Dashboard</a>
                        <a href="../campain/rapport.php" role="menuitem"><i class="fas fa-file-alt"></i> Rapport
                            id_gb</a>
                        <a href="../campain/rapport_lb.php" role="menuitem"><i class="fas fa-file-alt"></i> Rapport
                            id_lb</a>
                        <a href="../campain/prospect_template.php" role="menuitem"><i
                                class="fa-regular fa-file-code"></i> Script prospection</a>
                        <a href="?export_emails=1" role="menuitem"><i class="fas fa-download"></i> Export emails CSV</a>
                    </div>
                </nav>
            </div>
        </div>
    </header>

    <div class="shell">
        <!-- ===== CONTEXTE + PÉRIODE ===== -->
        <div class="ctx-bar">
            <p class="meta">Console admin — <strong><?= date('d.m.Y') ?></strong> —
                <?= htmlspecialchars($_SESSION['user_email']) ?></p>
            <nav class="period-switch" aria-label="Période d'analyse">
                <a href="?period=7" class="<?= $period === 7 ? 'active' : '' ?>">7j</a>
                <a href="?period=30" class="<?= $period === 30 ? 'active' : '' ?>">30j</a>
                <a href="?period=90" class="<?= $period === 90 ? 'active' : '' ?>">90j</a>
                <a href="?period=365" class="<?= $period === 365 ? 'active' : '' ?>">365j</a>
            </nav>
        </div>

        <!-- ===== 01 — KPI AVEC DELTAS + SPARKLINES ===== -->
        <section class="section" aria-label="Indicateurs clés">
            <?php
            $kpis = [
                ['key' => 'users', 'label' => 'Nouveaux utilisateurs', 'icon' => 'fa-user-plus',
                 'cur' => (int) ($periodTotals['cur_users'] ?? 0), 'prev' => (int) ($periodTotals['prev_users'] ?? 0)],
                ['key' => 'sites', 'label' => 'Nouveaux sites', 'icon' => 'fa-globe',
                 'cur' => (int) ($periodTotals['cur_sites'] ?? 0), 'prev' => (int) ($periodTotals['prev_sites'] ?? 0)],
                ['key' => 'visits', 'label' => 'Visites', 'icon' => 'fa-eye',
                 'cur' => (int) ($periodTotals['cur_visits'] ?? 0), 'prev' => (int) ($periodTotals['prev_visits'] ?? 0)],
                ['key' => 'uniques', 'label' => 'Visiteurs uniques', 'icon' => 'fa-fingerprint',
                 'cur' => (int) ($periodTotals['cur_uniques'] ?? 0), 'prev' => (int) ($periodTotals['prev_uniques'] ?? 0)],
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
                            <span class="kpi-delta <?= $deltaClass ?>"><?= $deltaTxt ?></span>
                        </div>
                        <div class="kpi-value"><?= number_format($kpi['cur']) ?></div>
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

        <!-- ===== 02 — WALLET ===== -->
        <section class="section">
            <div class="section-head"><span class="index">02</span>
                <h2>Wallet</h2>
            </div>
            <div class="duo">
                <div class="panel">
                    <div class="panel-head">
                        <h3 class="panel-title"><i class="fas fa-wallet"></i> Token</h3>
                        <button class="wallet-links-btn" id="wallet-links-btn">
                            <i class="fas fa-link"></i> Liens 0x
                        </button>
                    </div>
                    <div class="panel-body">
                        <div id="crypto-prices"></div>
                    </div>
                </div>
                <!--<div class="panel">
                    <div class="panel-head">
                        <h3 class="panel-title"><i class="fas fa-key"></i> Adresses</h3>
                    </div>
                    <div class="panel-body">
                        <div class="wallet-row">
                            <span class="network">Sol</span>
                            <span class="address" id="sol-address">D6khWoqvc2zX46HVtSZcNrPumnPLPM72SnSuDhBrZeTC</span>
                            <button class="copy-button" data-target="sol-address">Copy</button>
                        </div>
                        <div class="wallet-row">
                            <span class="network">BTC</span>
                            <span class="address" id="btc-address">bc1qxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx</span>
                            <button class="copy-button" data-target="btc-address">Copy</button>
                        </div>
                    </div>
                </div>-->
            </div>
        </section>

        <!-- ===== 03 — PETIT-MULTIPLES (chaque métrique à son échelle) ===== -->
        <section class="section">
            <div class="section-head"><span class="index">03</span>
                <h2>Activité détaillée — <?= $period ?> jours</h2>
            </div>
            <div class="multiples">
                <div class="mini">
                    <div class="mini-head">
                        <span class="t">Utilisateurs / jour</span>
                        <span class="v" style="color: var(--violet)"
                            id="sum-users"><?= number_format(array_sum($chartPayload['series']['users'])) ?></span>
                    </div>
                    <div class="chart-sm"><canvas id="mini-users"></canvas></div>
                </div>
                <div class="mini">
                    <div class="mini-head">
                        <span class="t">Sites / jour</span>
                        <span class="v" style="color: var(--blue)"
                            id="sum-sites"><?= number_format(array_sum($chartPayload['series']['sites'])) ?></span>
                    </div>
                    <div class="chart-sm"><canvas id="mini-sites"></canvas></div>
                </div>
                <div class="mini">
                    <div class="mini-head">
                        <span class="t">Visites / jour</span>
                        <span class="v" style="color: var(--acid)"
                            id="sum-visits"><?= number_format(array_sum($chartPayload['series']['visits'])) ?></span>
                    </div>
                    <div class="chart-sm"><canvas id="mini-visits"></canvas></div>
                </div>
                <div class="mini">
                    <div class="mini-head">
                        <span class="t">Visiteurs uniques / jour</span>
                        <span class="v" style="color: var(--rose)"
                            id="sum-uniques"><?= number_format(array_sum($chartPayload['series']['uniques'])) ?></span>
                    </div>
                    <div class="chart-sm"><canvas id="mini-uniques"></canvas></div>
                </div>
            </div>
        </section>

        <!-- ===== 04 — TOP SITES + GÉO ===== -->
        <section class="section">
            <div class="section-head"><span class="index">04</span>
                <h2>Top sites &amp; géographie</h2>
            </div>
            <div class="duo">
                <div class="panel">
                    <div class="panel-head">
                        <h3 class="panel-title"><i class="fas fa-trophy"></i> Top 5 sites</h3>
                    </div>
                    <div class="panel-body">
                        <div style="height: 280px;">
                            <canvas id="topSitesChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel-head">
                        <h3 class="panel-title"><i class="fas fa-map-marked-alt"></i> Pays visités</h3>
                        <div class="geo-toggle" role="tablist">
                            <button id="geo-bars" class="active">Barres</button>
                            <button id="geo-map">Carte</button>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="geo-view">
                            <canvas id="countriesChart"></canvas>
                            <div class="map-holder" id="map-holder">
                                <div id="worldMap"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== 05 — REGISTRE UTILISATEURS ===== -->
        <section class="section">
            <div class="section-head"><span class="index">05</span>
                <h2>Registre des utilisateurs (<?= count($usersList) ?>)</h2>
            </div>
            <div class="panel">
                <div class="panel-head">
                    <h3 class="panel-title"><i class="fas fa-address-card"></i> Comptes</h3>
                    <div style="display:flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="search" id="user-search" placeholder="Rechercher un email…" aria-label="Rechercher un email">
                        </div>
                        <a href="?export_emails=1" class="export-btn">
                            <i class="fas fa-download"></i> Export CSV
                        </a>
                    </div>
                </div>
                <div class="panel-body" style="padding: 0;">
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
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td><span
                                                    class="badge badge-<?= htmlspecialchars($planClass) ?>"><?= strtoupper($user['plan'] ?? 'free') ?></span>
                                            </td>
                                            <td data-value="<?= (int) ($user['site_count'] ?? 0) ?>">
                                                <?= (int) ($user['site_count'] ?? 0) ?></td>
                                            <td data-value="<?= (int) ($user['total_visits'] ?? 0) ?>">
                                                <?= number_format($user['total_visits'] ?? 0) ?></td>
                                            <td data-value="<?= $createdTs ?>">
                                                <?= $createdTs ? date('d/m/Y', $createdTs) : '-' ?></td>
                                            <td data-value="<?= $loginTs ?>">
                                                <?= $loginTs ? date('d/m/Y H:i', $loginTs) : '<span style="color:var(--txt-faint);">Jamais</span>' ?>
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
            </div>
        </section>
    </div><!-- .shell -->

    <script>
        // Toutes les initialisations attendent les scripts defer (Chart, Swal, Toastify)
        document.addEventListener('DOMContentLoaded', () => {

            // ===== DONNÉES SERVEUR =====
            const chartData = <?= json_encode($chartPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
            const weatherDays = <?= json_encode($weatherDays ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
            const topSites = <?= json_encode($topSites ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
            const countries = <?= json_encode($visitedCountries ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
            const cryptoData = <?= json_encode($coingeckoData ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

            Chart.defaults.font.family = "'JetBrains Mono', monospace";
            Chart.defaults.color = '#9aa0b4';

            const METRIC_COLORS = {
                users: '#ab9ff2',
                sites: '#86baff',
                visits: '#62faa4',
                uniques: '#ff7a8a'
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
                            backgroundColor: METRIC_COLORS[key] + '14',
                            borderWidth: 1.5,
                            fill: true,
                            tension: 0.3,
                            pointRadius: 0,
                            pointHitRadius: 8
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
                            borderRadius: 2,
                            maxBarThickness: 14
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
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
                            y: { beginAtZero: true, grid: { color: '#1c212d' }, ticks: { precision: 0, font: { size: 10 } } }
                        }
                    }
                });
            });

            // ===== MÉTÉO : mini chart températures =====
            const wxCanvas = document.getElementById('weatherChart');
            if (wxCanvas && weatherDays.length) {
                new Chart(wxCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: weatherDays.map(d => d.day_label),
                        datasets: [
                            { label: 'Max', data: weatherDays.map(d => d.t_max), backgroundColor: 'rgba(200,246,93,0.75)', borderColor: '#62faa4', borderWidth: 1, barPercentage: 0.6 },
                            { label: 'Min', data: weatherDays.map(d => d.t_min), backgroundColor: 'rgba(134,186,255,0.55)', borderColor: '#86baff', borderWidth: 1, barPercentage: 0.6 }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: (ctx) => ' ' + ctx.dataset.label + ' : ' + ctx.parsed.y + ' °C' } }
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                            y: { grid: { color: '#1c212d' }, ticks: { font: { size: 10 }, callback: v => v + '°' } }
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
                        backgroundColor: 'rgba(52,211,153,0.7)',
                        borderColor: '#34d399',
                        borderWidth: 1,
                        borderRadius: 2
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
                        x: { beginAtZero: true, grid: { color: '#1c212d' } },
                        y: { grid: { display: false } }
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
                        backgroundColor: 'rgba(171,159,242,0.65)',
                        borderColor: '#ab9ff2',
                        borderWidth: 1,
                        borderRadius: 2
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
                        x: { beginAtZero: true, grid: { color: '#1c212d' } },
                        y: { grid: { display: false } }
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

            let mapReady = false;
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
                    fill: am5.color(0x262b38),
                    stroke: am5.color(0x0d0f14),
                    strokeWidth: 0.5
                });

                polygonSeries.data.setAll(mappedCountries.map(c => ({ id: c.code, value: c.visits })));
                polygonSeries.set('heatRules', [{
                    target: polygonSeries.mapPolygons.template,
                    min: am5.color(0x3d3568),
                    max: am5.color(0xc8f65d),
                    dataField: 'value'
                }]);

                polygonSeries.mapPolygons.template.states.create('hover', {
                    fill: am5.color(0xab9ff2)
                });

                mapReady = true;
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

            // ===== MÉTÉO : ouverture/fermeture du popover =====
            const wxPill = document.getElementById('wx-pill');
            const wxPop = document.getElementById('wx-pop');
            if (wxPill && wxPop) {
                wxPill.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const open = wxPop.classList.toggle('open');
                    wxPill.setAttribute('aria-expanded', open);
                });
                document.addEventListener('click', (e) => {
                    if (!wxPop.contains(e.target) && e.target !== wxPill) {
                        wxPop.classList.remove('open');
                        wxPill.setAttribute('aria-expanded', 'false');
                    }
                });
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        wxPop.classList.remove('open');
                        wxPill.setAttribute('aria-expanded', 'false');
                    }
                });
            }

            // ===== MENU "COMMANDES" =====
            const cmd = document.getElementById('cmd-menu');
            const cmdToggle = document.getElementById('cmd-toggle');

            cmdToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                const open = cmd.classList.toggle('open');
                cmdToggle.setAttribute('aria-expanded', open);
            });

            document.addEventListener('click', (e) => {
                if (!cmd.contains(e.target)) {
                    cmd.classList.remove('open');
                    cmdToggle.setAttribute('aria-expanded', 'false');
                }
            });

            // ===== WALLET CRYPTO =====
        //const cryptoData = <?= json_encode($coingeckoData ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const tokenHoldings = {
            bitcoin: 0,
            solana: 4.67,
            sui: 1675,
            'usd-coin': 0,
            ami: 35535,
        };

        // Capital investi à l'origine (€) sol=312 € à 68 l'unité, sui=1600€ à 0.95 l'unité, ami=44€ à 0.001237 l'unité, btc=0 € à 0 l'unité, usdc=0 € à 0 l'unité
        const INITIAL_INVESTMENT = 1956;

            (function renderCrypto() {
                const container = document.getElementById('crypto-prices');

                if (!Array.isArray(cryptoData) || cryptoData.length === 0) {
                    container.innerHTML = '<div class="empty-state">Prix crypto indisponibles</div>';
                    return;
                }

                let totalPortfolioValue = 0;
                const totalElement = document.createElement('div');
                totalElement.id = 'portfolio-total';
                container.appendChild(totalElement);

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
                            <span class="symbol">${crypto.symbol.toUpperCase()}</span>
                            <span class="price">${price.toLocaleString('fr-FR', { minimumFractionDigits: 2 })} €</span>
                        </div>
                        <div class="holdings">
                            <span class="amount">${holdings} ${crypto.symbol.toUpperCase()}</span>
                            <span class="value">${totalValue.toFixed(2)} €</span>
                        </div>
                        <p class="change ${change24h >= 0 ? 'positive' : 'negative'}">${changeTxt}%</p>
                    `;
                    container.appendChild(el);
                });

                const pnl = totalPortfolioValue - INITIAL_INVESTMENT;
                const isGain = pnl >= 0;
                totalElement.innerHTML =
                    `<div class="label">Total</div><h3>${totalPortfolioValue.toFixed(2)} €</h3>
                     <div class="pnl ${isGain ? 'positive' : 'negative'}">${(isGain ? '+' : '') + pnl.toFixed(2)} €</div>`;
            })();

            document.getElementById('wallet-links-btn').addEventListener('click', () => {
                Swal.fire({
                    title: '0x',
                    html: '<ul><li><a href="https://accounts.binance.com/register?ref=">Binance</a>.com</li><li><a href="https://shop.ledger.com/?r=">Ledger</a>/live</li><li><a href="https://app.uniswap.org">Uniswap</a>.org<li><a href="#">Phantom</a>/app</li><li><a href="https://solscan.io/account/D6khWoqvc2zX46HVtSZcNrPumnPLPM72SnSuDhBrZeTC#portfolio">Solscan</a>.io</li><li><a href="https://pump.fun/profile/D6khWo">Pump</a>.fun</li><li><a href="https://jup.ag">jup</a>.ag</li></ul>',
                    showCloseButton: true,
                    showConfirmButton: false,
                    customClass: {
                        popup: 'custom-swal-popup',
                        closeButton: 'custom-swal-close-button',
                        content: 'custom-swal-content',
                    }
                });
            });

            document.querySelectorAll('.copy-button').forEach(button => {
                button.addEventListener('click', function () {
                    const address = document.getElementById(this.getAttribute('data-target')).textContent;
                    const done = () => Toastify({
                        text: "✅ Adresse copiée !",
                        duration: 2000,
                        gravity: "center",
                        position: "center",
                        backgroundColor: "#62faa4",
                    }).showToast();

                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(address).then(done).catch(() => fallbackCopy(address, done));
                    } else {
                        fallbackCopy(address, done);
                    }
                });
            });

            function fallbackCopy(text, callback) {
                const tempInput = document.createElement('input');
                tempInput.value = text;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                callback();
            }

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