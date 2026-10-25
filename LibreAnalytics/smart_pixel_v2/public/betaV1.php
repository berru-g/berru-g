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

// --- Récupération des données ---
try {
    // Données pour le graphique 4 courbes (7 derniers jours)
    $stmt = $pdo->query("
        SELECT
            DATE(CURDATE() - INTERVAL n DAY) AS date,
            (SELECT COUNT(*) FROM users WHERE DATE(created_at) <= DATE(CURDATE() - INTERVAL n DAY)) AS cumulative_users,
            (SELECT COUNT(*) FROM user_sites WHERE DATE(created_at) <= DATE(CURDATE() - INTERVAL n DAY)) AS cumulative_sites,
            (SELECT COUNT(*) FROM smart_pixel_tracking WHERE DATE(timestamp) <= DATE(CURDATE() - INTERVAL n DAY)) AS cumulative_visits,
            (SELECT COUNT(DISTINCT ip_address) FROM smart_pixel_tracking WHERE DATE(timestamp) <= DATE(CURDATE() - INTERVAL n DAY)) AS cumulative_unique_visitors
        FROM (
            SELECT 0 AS n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION
            SELECT 4 UNION SELECT 5 UNION SELECT 6
        ) AS numbers
        ORDER BY date ASC
    ");
    $historicalData = $stmt->fetchAll();

    // Top 5 des sites
    $stmt = $pdo->query("
        SELECT s.site_name, s.id, COUNT(t.id) AS total_visits
        FROM user_sites s
        LEFT JOIN smart_pixel_tracking t ON s.id = t.site_id
        GROUP BY s.id
        ORDER BY total_visits DESC
        LIMIT 5
    ");
    $topSites = $stmt->fetchAll();

    // Nombre total de sites (fix : la carte "Sites" comptait des visites)
    $totalSites = (int) $pdo->query("SELECT COUNT(*) FROM user_sites")->fetchColumn();

    // Pays visités (top 20)
    $stmt = $pdo->query("
        SELECT country, COUNT(*) AS visits
        FROM smart_pixel_tracking
        WHERE country IS NOT NULL
        GROUP BY country
        ORDER BY visits DESC
        LIMIT 20
    ");
    $visitedCountries = $stmt->fetchAll();

    // Liste des utilisateurs
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

    // Statistiques par plan
    $stmt = $pdo->query("SELECT plan, COUNT(*) AS count FROM users GROUP BY plan");
    $plansStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Activité récente (inscriptions/jour, 90 derniers jours pour le bar chart)
    $stmt = $pdo->query("
        SELECT DATE(created_at) AS date, COUNT(*) AS count
        FROM users
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $recentActivity = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Data fetch failed: ' . $e->getMessage());
    die("Erreur lors de la récupération des données. Réessayez plus tard.");
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
// MÉTÉO VERTOU (44) — Open-Meteo, API gratuite, AUCUNE clé requise.
// Appel 100 % côté serveur + cache 30 min. Coordonnées Vertou :
// 47.1667 N, -1.4667 W. Jour en cours inclus (forecast_days=3).
// ================================================================
$weatherDays = null;

/**
 * Mappe un code WMO vers [libellé, icône FontAwesome].
 */
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

    // Échec API → dernier cache connu plutôt qu'un panneau vide
    if ($weatherRaw === null && is_file($weatherCache)) {
        $weatherRaw = json_decode(file_get_contents($weatherCache), true);
    }
}

// Mise en forme des 3 jours (aujourd'hui compris)
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
    <link rel="stylesheet" href="https://gael-berru.com/LibreAnalytics/smart_pixel_v2/assets/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script
        src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/map.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/geodata/worldLow.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <style>
        /* ============================================================
   LibreAnalytics — dashboard.css
   Refonte UI/UX, à utiliser en complément du markup existant
   (mêmes classes : .topbar, .report, .weather, .stats-strip,
   .panel, .duo, .data-table, .badge, etc.)

   Principes :
   1. Une seule source de vérité : design tokens en :root
   2. Hiérarchie par typographie, pas par décor
   3. Densité maîtrisée : les données respirent
   4. Feedback : hover / focus visibles et cohérents
   5. Accessibilité : contraste AA, reduced-motion respecté
   ============================================================ */

        :root {
            /* --- Couleurs : fond encre chaude, accent unique --- */
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
            --acid: #c8f65d;
            --violet: #ab9ff2;
            --rose: #ff7a8a;
            --blue: #86baff;

            /* --- Typo --- */
            --mono: 'JetBrains Mono', ui-monospace, monospace;
            --grot: 'Space Grotesk', system-ui, sans-serif;

            /* --- Rythme : échelle 4px --- */
            --s-1: 0.25rem;
            --s-2: 0.5rem;
            --s-3: 0.75rem;
            --s-4: 1rem;
            --s-5: 1.5rem;
            --s-6: 2.25rem;
            --s-7: 3.25rem;

            /* --- Rayons : très légers, signature "précision" --- */
            --r-1: 3px;
            --r-2: 6px;

            /* --- Ombres discrètes, jamais de glow gratuit --- */
            --shadow: 0 1px 2px rgba(0, 0, 0, 0.4), 0 8px 24px rgba(0, 0, 0, 0.18);

            /* --- Mouvements --- */
            --t-fast: 120ms cubic-bezier(0.4, 0, 0.2, 1);
            --t-med: 220ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ============================================================
   BASE
   ============================================================ */

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
            /* Fond : dégradé radial très léger pour donner de la profondeur
       sans "dashboard SaaS générique" */
            background-image:
                radial-gradient(1100px 500px at 80% -10%, rgba(171, 159, 242, 0.05), transparent 60%),
                radial-gradient(900px 400px at 10% -5%, rgba(200, 246, 93, 0.035), transparent 55%);
            background-repeat: no-repeat;
        }

        ::selection {
            background: var(--acid);
            color: var(--ink);
        }

        /* Focus clavier toujours visible — UX clavier */
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

        /* Scrollbars sobres (WebKit) */
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

        /* ============================================================
   TOPBAR
   ============================================================ */

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
            text-decoration: none;
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
            font-size: 0.62rem;
            color: var(--txt-faint);
            text-transform: uppercase;
            letter-spacing: 0.18em;
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
            font-size: 0.68rem;
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

        /* ============================================================
   MENU "COMMANDES"
   ============================================================ */

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

        .cmd-toggle:hover {
            border-color: var(--lime);
            color: var(--lime);
            background: var(--ink-4);
        }

        .cmd-toggle .caret {
            transition: transform var(--t-med);
            font-size: 0.6rem;
        }

        .cmd.open .cmd-toggle {
            border-color: var(--lime);
            color: var(--lime);
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
            color: var(--txt);
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
            transition: color var(--t-fast);
        }

        .cmd-menu a:hover {
            background: var(--ink-3);
            color: var(--lime);
            padding-left: 1.4rem;
        }

        .cmd-menu a:hover i {
            color: var(--lime);
        }

        /* ============================================================
   HERO / RAPPORT
   ============================================================ */

        .report {
            padding: var(--s-7) 0 var(--s-5);
            display: grid;
            grid-template-columns: 1fr minmax(300px, 420px);
            align-items: end;
            gap: var(--s-5);
        }

        .report h1 {
            font-size: clamp(2.1rem, 6vw, 4rem);
            font-weight: 700;
            line-height: 0.98;
            letter-spacing: -0.03em;
            text-transform: uppercase;
        }

        .report h1 .stroke {
            color: transparent;
            -webkit-text-stroke: 1.5px var(--violet);
        }

        .report .meta {
            font-family: var(--mono);
            font-size: 0.68rem;
            color: var(--txt-dim);
            text-transform: uppercase;
            letter-spacing: 0.15em;
            margin-top: var(--s-3);
        }

        .report .meta strong {
            color: var(--lime);
            font-weight: 700;
        }

        @media (max-width: 1100px) {
            .report {
                grid-template-columns: 1fr;
            }
        }

        /* ============================================================
   MÉTÉO
   ============================================================ */

        .weather {
            border: 1px solid var(--line);
            border-radius: var(--r-2);
            background: var(--ink-2);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .weather-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.65rem 1rem;
            border-bottom: 1px solid var(--line);
        }

        .weather-head .loc {
            font-family: var(--mono);
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: var(--txt);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .weather-head .loc i {
            color: var(--lime);
        }

        .weather-head .src {
            font-family: var(--mono);
            font-size: 0.56rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--txt-faint);
        }

        .weather-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
        }

        .weather-day {
            padding: var(--s-4) var(--s-3) var(--s-3);
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

        /* Jour courant : marqueur discret, pas de gadget */
        .weather-day.today::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 28px;
            height: 2px;
            background: var(--acid);
            border-radius: 2px;
        }

        .weather-day .d-label {
            font-family: var(--mono);
            font-size: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            color: var(--txt-dim);
            margin-bottom: var(--s-2);
        }

        .weather-day .d-icon {
            font-size: 1.45rem;
            color: var(--acid);
            margin-bottom: var(--s-2);
        }

        .weather-day .d-desc {
            font-family: var(--mono);
            font-size: 0.56rem;
            color: var(--txt-dim);
            letter-spacing: 0.04em;
            margin-bottom: var(--s-3);
            min-height: 1.6em;
        }

        .weather-day .d-temps {
            font-family: var(--mono);
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .weather-day .d-temps .min {
            color: var(--txt-faint);
            font-weight: 400;
        }

        .weather-day .d-rain {
            font-family: var(--mono);
            font-size: 0.6rem;
            color: var(--blue);
            margin-top: 0.3rem;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.3rem;
        }

        .weather-day .d-rain i {
            font-size: 0.55rem;
        }

        .weather-day .d-wind {
            font-family: var(--mono);
            font-size: 0.56rem;
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

            .weather-day:last-child {
                border-bottom: none;
            }
        }

        /* ============================================================
   SECTIONS
   ============================================================ */

        .section {
            margin-bottom: var(--s-6);
        }

        /* Le titre de section devient une vraie légende éditoriale :
   index mono + règle horizontale qui respire */
        .section-head {
            display: flex;
            align-items: center;
            gap: var(--s-3);
            margin-bottom: var(--s-4);
        }

        .section-head .index {
            font-family: var(--mono);
            font-size: 0.68rem;
            font-weight: 700;
            color: var(--lime);
            letter-spacing: 0.1em;
            background: rgba(52, 211, 153, 0.08);
            border: 1px solid rgba(52, 211, 153, 0.25);
            border-radius: var(--r-1);
            padding: 0.15rem 0.5rem;
        }

        .section-head h2 {
            font-size: 0.82rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.22em;
            color: var(--txt);
            white-space: nowrap;
        }

        .section-head::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, var(--line), transparent);
        }

        /* ============================================================
   STATS STRIP
   ============================================================ */

        .stats-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border: 1px solid var(--line);
            border-radius: var(--r-2);
            background: var(--ink-2);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .stat {
            padding: var(--s-5) var(--s-4);
            border-right: 1px solid var(--line-soft);
            position: relative;
            transition: background var(--t-fast);
        }

        .stat:last-child {
            border-right: none;
        }

        .stat:hover {
            background: var(--ink-3);
        }

        .stat .k {
            font-family: var(--mono);
            font-size: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            color: var(--txt-dim);
            margin-bottom: var(--s-2);
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .stat .k i {
            color: var(--violet);
            font-size: 0.68rem;
        }

        .stat .v {
            font-family: var(--mono);
            font-size: clamp(1.6rem, 3vw, 2.3rem);
            font-weight: 700;
            color: var(--txt);
            letter-spacing: -0.04em;
            font-variant-numeric: tabular-nums;
        }

        .stat::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 0;
            height: 2px;
            background: var(--acid);
            transition: width var(--t-med);
        }

        .stat:hover::after {
            width: 100%;
        }

        @media (max-width: 900px) {
            .stats-strip {
                grid-template-columns: repeat(2, 1fr);
            }

            .stat:nth-child(2) {
                border-right: none;
            }

            .stat:nth-child(1),
            .stat:nth-child(2) {
                border-bottom: 1px solid var(--line-soft);
            }
        }

        @media (max-width: 480px) {
            .stats-strip {
                grid-template-columns: 1fr;
            }

            .stat {
                border-right: none;
                border-bottom: 1px solid var(--line-soft);
            }

            .stat:last-child {
                border-bottom: none;
            }
        }

        /* ============================================================
   PANNEAUX
   ============================================================ */

        .panel {
            background: var(--ink-2);
            border: 1px solid var(--line);
            border-radius: var(--r-2);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        /* Signature : coin supérieur gauche en accent lime.
   Discret, structurel, cohérent sur tout le site. */
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
            letter-spacing: 0.16em;
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
            grid-template-columns: 1.1fr 1fr;
            gap: var(--s-4);
            margin-bottom: var(--s-4);
        }

        @media (max-width: 1000px) {
            .duo {
                grid-template-columns: 1fr;
            }
        }

        .duo-2 {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: var(--s-4);
        }

        @media (max-width: 1000px) {
            .duo-2 {
                grid-template-columns: 1fr;
            }
        }

        /* ============================================================
   WALLET
   ============================================================ */

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
            font-size: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 0.16em;
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
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.06em;
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
            font-size: 0.92rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: var(--txt);
        }

        .crypto-item .price {
            font-family: var(--mono);
            font-size: 0.72rem;
            color: var(--txt-dim);
            font-variant-numeric: tabular-nums;
        }

        .crypto-item .holdings {
            display: flex;
            flex-direction: column;
            text-align: right;
        }

        .crypto-item .holdings .amount {
            font-family: var(--mono);
            font-size: 0.72rem;
            color: var(--txt-dim);
        }

        .crypto-item .holdings .value {
            font-family: var(--mono);
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--violet);
            font-variant-numeric: tabular-nums;
        }

        .crypto-item .change {
            font-family: var(--mono);
            font-size: 0.82rem;
            font-weight: 700;
            min-width: 72px;
            text-align: right;
            font-variant-numeric: tabular-nums;
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

        /* Adresses wallet */
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
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
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
            font-size: 0.7rem;
            color: var(--txt-dim);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .copy-button {
            font-family: var(--mono);
            font-size: 0.62rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            border: 1px solid var(--violet);
            border-radius: var(--r-1);
            background: transparent;
            color: var(--violet);
            padding: 0.4rem 0.8rem;
            cursor: pointer;
            transition: background var(--t-fast), color var(--t-fast), border-color var(--t-fast);
        }

        .copy-button:hover {
            background: var(--violet);
            color: var(--ink);
            border-color: var(--violet);
        }

        .copy-button:active {
            transform: translateY(1px);
        }

        .wallet-links-btn {
            font-family: var(--mono);
            font-size: 0.66rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
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

        /* ============================================================
   TABLEAUX
   ============================================================ */

        .table-responsive {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-family: var(--mono);
            font-size: 0.76rem;
        }

        .data-table th {
            text-align: left;
            padding: 0.75rem var(--s-4);
            color: var(--txt-faint);
            font-weight: 700;
            font-size: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            border-bottom: 1px solid var(--line);
            white-space: nowrap;
            position: sticky;
            top: 0;
            background: var(--ink-2);
        }

        .data-table td {
            padding: 0.75rem var(--s-4);
            border-bottom: 1px solid var(--line-soft);
            color: var(--txt);
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
            font-size: 0.7rem;
        }

        /* ============================================================
   BADGES PLANS
   ============================================================ */

        .badge {
            display: inline-block;
            padding: 0.22rem 0.6rem;
            font-family: var(--mono);
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            border: 1px solid;
            border-radius: 999px;
        }

        .badge-free {
            background: transparent;
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

        /* ============================================================
   EXPORT
   ============================================================ */

        .export-btn {
            font-family: var(--mono);
            font-size: 0.66rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            background: var(--lime);
            color: var(--ink);
            padding: 0.55rem 1rem;
            border: none;
            border-radius: var(--r-1);
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: filter var(--t-fast), transform var(--t-fast);
        }

        .export-btn:hover {
            filter: brightness(1.12);
        }

        .export-btn:active {
            transform: translateY(1px);
        }

        /* ============================================================
   GRAPHIQUES & CARTE
   ============================================================ */

        #worldMap {
            width: 100%;
            height: 400px;
            background: var(--ink-2);
            border-radius: var(--r-1);
        }

        .chart-wrap {
            position: relative;
            height: 320px;
        }

        .chart-wrap-sm {
            position: relative;
            height: 220px;
        }

        /* Doughnut plans */
        .plans-flex {
            display: flex;
            align-items: center;
            gap: var(--s-5);
            flex-wrap: wrap;
        }

        .plans-flex .chart-wrap-sm {
            width: 200px;
            height: 200px;
            flex-shrink: 0;
        }

        .plans-legend {
            flex: 1;
            min-width: 180px;
        }

        .plan-legend-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: var(--mono);
            font-size: 0.74rem;
            padding: 0.55rem 0;
            border-bottom: 1px dashed var(--line);
        }

        .plan-legend-row:last-child {
            border-bottom: none;
        }

        .plan-legend-row .dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: var(--r-1);
            margin-right: 0.5rem;
        }

        /* ============================================================
   ÉTATS VIDES
   ============================================================ */

        .empty-state {
            text-align: center;
            padding: var(--s-6) var(--s-4);
            color: var(--txt-dim);
            font-family: var(--mono);
            font-size: 0.78rem;
            font-style: italic;
        }

        /* ============================================================
   SWEETALERT & TOASTIFY
   ============================================================ */

        .custom-swal-popup {
            background: var(--ink-2) !important;
            color: var(--txt) !important;
            border: 1px solid var(--line) !important;
            border-radius: var(--r-2) !important;
            font-family: var(--grot) !important;
            box-shadow: var(--shadow) !important;
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
            text-decoration: none;
            font-weight: 700;
            border-bottom: 1px dotted rgba(52, 211, 153, 0.4);
        }

        .custom-swal-content a:hover {
            border-bottom-style: solid;
        }

        .toastify {
            font-family: var(--mono) !important;
            border-radius: var(--r-1) !important;
            border-left: 3px solid var(--ink) !important;
            box-shadow: var(--shadow) !important;
        }

        /* ============================================================
   ACCESSIBILITÉ & PRÉFÉRENCES
   ============================================================ */

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
        <!-- ===== HERO + MÉTÉO ===== -->
        <section class="report">
            <div>
                <h1>Rapport<br><span class="stroke">SmartPixel</span></h1>
                <p class="meta">console admin — <strong><?= date('d.m.Y') ?></strong> — accès :
                    <?= htmlspecialchars($_SESSION['user_email']) ?>
                </p>
            </div>

            <div class="weather" aria-label="Météo Vertou, 3 jours">
                <div class="weather-head">
                    <span class="loc"><i class="fas fa-location-dot"></i> Vertou (44)</span>
                    <span class="src">open-meteo · j+2</span>
                </div>
                <?php if (!empty($weatherDays)): ?>
                    <div class="weather-grid">
                        <?php foreach ($weatherDays as $i => $day): ?>
                            <div class="weather-day <?= $i === 0 ? 'today' : '' ?>">
                                <div class="d-label"><?= htmlspecialchars($day['day_label']) ?></div>
                                <div class="d-icon"><i class="fas <?= htmlspecialchars($day['icon']) ?>"></i></div>
                                <div class="d-desc"><?= htmlspecialchars($day['label']) ?></div>
                                <div class="d-temps"><?= $day['t_max'] ?>° <span class="min">/ <?= $day['t_min'] ?>°</span>
                                </div>
                                <div class="d-rain"><i class="fas fa-umbrella"></i> <?= $day['rain_prob'] ?>%</div>
                                <div class="d-wind"><i class="fas fa-wind"></i> <?= $day['wind_max'] ?> km/h</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="weather-chart">
                        <canvas id="weatherChart"></canvas>
                    </div>
                <?php else: ?>
                    <div class="empty-state">Météo indisponible</div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ===== 01 — STATS ===== -->
        <section class="section">
            <div class="section-head"><span class="index">01</span>
                <h2>Signaux globaux</h2>
            </div>
            <div class="stats-strip">
                <div class="stat">
                    <div class="k"><i class="fas fa-users"></i> Utilisateurs</div>
                    <div class="v"><?= number_format(count($usersList)) ?></div>
                </div>
                <div class="stat">
                    <div class="k"><i class="fas fa-globe"></i> Sites</div>
                    <div class="v"><?= number_format($totalSites) ?></div>
                </div>
                <div class="stat">
                    <div class="k"><i class="fas fa-eye"></i> Visites</div>
                    <div class="v"><?= number_format(array_sum(array_column($visitedCountries, 'visits'))) ?></div>
                </div>
                <div class="stat">
                    <div class="k"><i class="fas fa-user-check"></i> Visiteurs uniques</div>
                    <div class="v">
                        <?= number_format($historicalData ? (int) end($historicalData)['cumulative_unique_visitors'] : 0) ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== 02 — WALLET CRYPTO ===== -->
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
                <div class="panel">
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
                </div>
            </div>
        </section>

        <!-- ===== 03 — CROISSANCE + PLANS ===== -->
        <section class="section">
            <div class="section-head"><span class="index">03</span>
                <h2>Croissance générale</h2>
            </div>
            <div class="duo">
                <div class="panel">
                    <div class="panel-head">
                        <h3 class="panel-title"><i class="fas fa-chart-line"></i> Évolution cumulative — 7 jours</h3>
                    </div>
                    <div class="panel-body chart-wrap">
                        <canvas id="globalStatsChart"></canvas>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel-head">
                        <h3 class="panel-title"><i class="fas fa-chart-pie"></i> Répartition des plans</h3>
                    </div>
                    <div class="panel-body">
                        <div class="plans-flex">
                            <div class="chart-wrap-sm"><canvas id="plansChart"></canvas></div>
                            <div class="plans-legend">
                                <?php
                                $planColors = [
                                    'free' => '#8b90a5',
                                    'pro' => '#34d399',
                                    'business' => '#ff7a8a',
                                    'premium' => '#ab9ff2',
                                ];
                                $totalPlans = max(1, array_sum($plansStats));
                                foreach ($plansStats as $plan => $count):
                                    $color = $planColors[$plan] ?? '#86baff';
                                    ?>
                                    <div class="plan-legend-row">
                                        <span><span class="dot"
                                                style="background: <?= $color ?>"></span><?= strtoupper(htmlspecialchars((string) $plan)) ?></span>
                                        <span><strong><?= (int) $count ?></strong> <span
                                                style="color:var(--txt-dim)"><?= round($count * 100 / $totalPlans) ?>%</span></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== 04 — TOP SITES + CARTE ===== -->
        <section class="section">
            <div class="section-head"><span class="index">04</span>
                <h2>Top sites &amp; géographie</h2>
            </div>
            <div class="duo-2">
                <div class="panel">
                    <div class="panel-head">
                        <h3 class="panel-title"><i class="fas fa-trophy"></i> Top 5 sites</h3>
                    </div>
                    <div class="panel-body chart-wrap-sm">
                        <canvas id="topSitesChart"></canvas>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel-head">
                        <h3 class="panel-title"><i class="fas fa-map-marked-alt"></i> Pays visités (Top 20)</h3>
                    </div>
                    <div class="panel-body">
                        <div id="worldMap"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== 05 — ACTIVITÉ ===== -->
        <section class="section">
            <div class="section-head"><span class="index">05</span>
                <h2>Inscriptions — 90 jours</h2>
            </div>
            <div class="panel">
                <div class="panel-head">
                    <h3 class="panel-title"><i class="fas fa-wave-square"></i> Nouveaux comptes par jour</h3>
                </div>
                <div class="panel-body chart-wrap-sm">
                    <canvas id="activityChart"></canvas>
                </div>
            </div>
        </section>

        <!-- ===== 06 — UTILISATEURS ===== -->
        <section class="section">
            <div class="section-head"><span class="index">06</span>
                <h2>Registre des utilisateurs (<?= count($usersList) ?>)</h2>
            </div>
            <div class="panel">
                <div class="panel-head">
                    <h3 class="panel-title"><i class="fas fa-address-card"></i> Comptes</h3>
                    <a href="?export_emails=1" class="export-btn">
                        <i class="fas fa-download"></i> Exporter les emails
                    </a>
                </div>
                <div class="panel-body" style="padding: 0;">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Email</th>
                                    <th>Plan</th>
                                    <th>Sites</th>
                                    <th>Visites</th>
                                    <th>Inscription</th>
                                    <th>Dernière connexion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($usersList)): ?>
                                    <?php foreach ($usersList as $user):
                                        $planClass = strtolower($user['plan'] ?? 'free');
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td><span
                                                    class="badge badge-<?= htmlspecialchars($planClass) ?>"><?= strtoupper($user['plan'] ?? 'free') ?></span>
                                            </td>
                                            <td><?= (int) ($user['site_count'] ?? 0) ?></td>
                                            <td><?= number_format($user['total_visits'] ?? 0) ?></td>
                                            <td><?= !empty($user['created_at']) ? (new DateTime($user['created_at']))->format('d/m/Y') : '-' ?>
                                            </td>
                                            <td><?= !empty($user['last_login']) ? (new DateTime($user['last_login']))->format('d/m/Y H:i') : '<span style="color:var(--txt-dim);">Jamais</span>' ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
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
        // ===== MENU DÉROULANT "COMMANDES" =====
        (function () {
            const cmd = document.getElementById('cmd-menu');
            const toggle = document.getElementById('cmd-toggle');

            toggle.addEventListener('click', (e) => {
                e.stopPropagation();
                const open = cmd.classList.toggle('open');
                toggle.setAttribute('aria-expanded', open);
            });

            document.addEventListener('click', (e) => {
                if (!cmd.contains(e.target)) {
                    cmd.classList.remove('open');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    cmd.classList.remove('open');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        })();

        // ===== WALLET CRYPTO =====
        const cryptoData = <?= json_encode($coingeckoData ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const tokenHoldings = {
            bitcoin: 0,
            solana: 4.67,
            sui: 1675,
            'usd-coin': 0,
            ami: 35535,
        };

        // Capital investi à l'origine (€) sol=312 € à 68 l'unité, sui=1650€ à 0.96 l'unité, ami=40€ à 0.001 l'unité, btc=0 € à 0 l'unité, usdc=0 € à 0 l'unité
        const INITIAL_INVESTMENT = 1962;

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
            const pnlTxt = (isGain ? '+' : '') + pnl.toFixed(2) + ' €';

            totalElement.innerHTML =
                `<div class="label">Total</div><h3>${totalPortfolioValue.toFixed(2)} €</h3>
     <div class="pnl ${isGain ? 'positive' : 'negative'}">${pnlTxt}</div>`;
        })();

        // Liens 0x via SweetAlert2
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

        // Copie des adresses (API Clipboard moderne + fallback)
        document.querySelectorAll('.copy-button').forEach(button => {
            button.addEventListener('click', function () {
                const address = document.getElementById(this.getAttribute('data-target')).textContent;

                const done = () => Toastify({
                    text: "✅ Adresse copiée !",
                    duration: 2000,
                    gravity: "center",
                    position: "center",
                    backgroundColor: "#c8f65d",
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

        // ===== GRAPHIQUES =====
        Chart.defaults.font.family = "'JetBrains Mono', monospace";
        Chart.defaults.color = '#8b90a5';

        const sharedScales = {
            x: { grid: { display: false } },
            y: { beginAtZero: true, grid: { color: '#1b2030' } }
        };

        // --- Météo : températures min/max sur 3 jours ---
        const weatherDays = <?= json_encode($weatherDays ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const weatherCtx = document.getElementById('weatherChart');
        if (weatherCtx && weatherDays.length) {
            new Chart(weatherCtx, {
                type: 'bar',
                data: {
                    labels: weatherDays.map(d => d.day_label),
                    datasets: [
                        {
                            label: 'Max',
                            data: weatherDays.map(d => d.t_max),
                            backgroundColor: 'rgba(200, 246, 93, 0.75)',
                            borderColor: '#c8f65d',
                            borderWidth: 1,
                            barPercentage: 0.6,
                        },
                        {
                            label: 'Min',
                            data: weatherDays.map(d => d.t_min),
                            backgroundColor: 'rgba(134, 186, 255, 0.55)',
                            borderColor: '#86baff',
                            borderWidth: 1,
                            barPercentage: 0.6,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => ctx.dataset.label + ' : ' + ctx.parsed.y + ' °C'
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 9 } } },
                        y: { grid: { color: '#1b2030' }, ticks: { font: { size: 9 }, callback: (v) => v + '°' } }
                    }
                }
            });
        }

        // --- Graphique 4 courbes cumulatives ---
        const globalCtx = document.getElementById('globalStatsChart').getContext('2d');
        new Chart(globalCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($historicalData, 'date')) ?>,
                datasets: [
                    {
                        label: 'Utilisateurs',
                        data: <?= json_encode(array_column($historicalData, 'cumulative_users')) ?>,
                        borderColor: '#ab9ff2',
                        backgroundColor: 'rgba(171, 159, 242, 0.06)',
                        tension: 0.2,
                        fill: true,
                        pointRadius: 2
                    },
                    {
                        label: 'Sites',
                        data: <?= json_encode(array_column($historicalData, 'cumulative_sites')) ?>,
                        borderColor: '#86baff',
                        backgroundColor: 'rgba(134, 186, 255, 0.06)',
                        tension: 0.2,
                        fill: true,
                        pointRadius: 2
                    },
                    {
                        label: 'Visites',
                        data: <?= json_encode(array_column($historicalData, 'cumulative_visits')) ?>,
                        borderColor: '#c8f65d',
                        backgroundColor: 'rgba(200, 246, 93, 0.06)',
                        tension: 0.2,
                        fill: true,
                        pointRadius: 2
                    },
                    {
                        label: 'Visiteurs uniques',
                        data: <?= json_encode(array_column($historicalData, 'cumulative_unique_visitors')) ?>,
                        borderColor: '#ff7a8a',
                        backgroundColor: 'rgba(255, 122, 138, 0.06)',
                        tension: 0.2,
                        fill: true,
                        pointRadius: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { usePointStyle: true, boxWidth: 8 }
                    },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        title: { display: true, text: 'Date' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#1b2030' },
                        title: { display: true, text: 'Nombre' }
                    }
                }
            }
        });

        // --- Doughnut répartition des plans ---
        const plansData = <?= json_encode($plansStats ?: [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const planColorsMap = {
            free: '#8b90a5',
            pro: '#34d399',
            business: '#ff7a8a',
            premium: '#ab9ff2'
        };
        new Chart(document.getElementById('plansChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(plansData).map(p => p.toUpperCase()),
                datasets: [{
                    data: Object.values(plansData),
                    backgroundColor: Object.keys(plansData).map(p => planColorsMap[p] || '#86baff'),
                    borderColor: '#10131b',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ' ' + ctx.label + ' : ' + ctx.parsed + ' compte(s)'
                        }
                    }
                }
            }
        });

        // --- Top 5 sites : barres horizontales ---
        const topSites = <?= json_encode($topSites ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        new Chart(document.getElementById('topSitesChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: topSites.map(s => s.site_name),
                datasets: [{
                    label: 'Visites',
                    data: topSites.map(s => Number(s.total_visits)),
                    backgroundColor: 'rgba(52, 211, 153, 0.7)',
                    borderColor: '#34d399',
                    borderWidth: 1,
                    borderRadius: 0
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ' ' + ctx.parsed.x.toLocaleString('fr-FR') + ' visites'
                        }
                    }
                },
                scales: {
                    x: { beginAtZero: true, grid: { color: '#1b2030' } },
                    y: { grid: { display: false } }
                }
            }
        });

        // --- Activité : inscriptions/jour sur 90 jours ---
        const activityData = <?= json_encode($recentActivity ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        new Chart(document.getElementById('activityChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: activityData.map(d => d.date),
                datasets: [{
                    label: 'Inscriptions',
                    data: activityData.map(d => Number(d.count)),
                    backgroundColor: 'rgba(171, 159, 242, 0.65)',
                    borderColor: '#ab9ff2',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        type: 'time',
                        time: { unit: 'week', displayFormats: { week: 'dd/MM' } },
                        grid: { display: false },
                        ticks: { maxRotation: 0, autoSkipPadding: 20 }
                    },
                    y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#1b2030' } }
                }
            }
        });

        // --- Carte du monde ---
        document.addEventListener('DOMContentLoaded', function () {
            const countries = <?= json_encode($visitedCountries ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
            if (!countries.length) {
                document.getElementById('worldMap').innerHTML = '<div class="empty-state">Données géographiques indisponibles</div>';
                return;
            }

            const getCountryCode = (countryName) => {
                const map = {
                    'france': 'FR',
                    'united states': 'US',
                    'usa': 'US',
                    'germany': 'DE',
                    'united kingdom': 'GB',
                    'uk': 'GB',
                    'canada': 'CA',
                    'australia': 'AU',
                    'japan': 'JP',
                    'china': 'CN',
                    'brazil': 'BR',
                    'india': 'IN',
                    'italy': 'IT',
                    'spain': 'ES',
                    'netherlands': 'NL',
                    'belgium': 'BE',
                    'switzerland': 'CH',
                    'portugal': 'PT',
                    'russia': 'RU',
                    'mexico': 'MX',
                    'south korea': 'KR',
                    'singapore': 'SG'
                };
                return map[String(countryName).toLowerCase().trim()] || null;
            };

            const countryData = countries.map(country => ({
                id: getCountryCode(country.country),
                value: country.visits
            })).filter(item => item.id !== null);

            if (countryData.length === 0) {
                document.getElementById('worldMap').innerHTML = '<div class="empty-state">Pays non mappés</div>';
                return;
            }

            const root = am5.Root.new("worldMap");
            root.setThemes([am5themes_Animated.new(root)]);
            root._logo && root._logo.dispose && root._logo.dispose();

            const chart = root.container.children.push(
                am5map.MapChart.new(root, {
                    panX: "rotateX",
                    panY: "translateY",
                    projection: am5map.geoMercator(),
                    layout: root.horizontalLayout
                })
            );

            const polygonSeries = chart.series.push(
                am5map.MapPolygonSeries.new(root, {
                    geoJSON: am5geodata_worldLow,
                    exclude: ["AQ"]
                })
            );

            polygonSeries.mapPolygons.template.setAll({
                tooltipText: "{name}: {value} visites",
                interactive: true,
                fill: am5.color(0x232839),
                stroke: am5.color(0x0b0d12),
                strokeWidth: 0.5
            });

            polygonSeries.data.setAll(countryData);
            polygonSeries.set("heatRules", [{
                target: polygonSeries.mapPolygons.template,
                min: am5.color(0x3d3568),
                max: am5.color(0xc8f65d),
                dataField: "value"
            }]);

            polygonSeries.mapPolygons.template.states.create("hover", {
                fill: am5.color(0xab9ff2)
            });
        });
    </script>
</body>

</html>