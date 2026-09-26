<?php
// 
session_start();

// --- Vérification de l'authentification ---
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (!Auth::isLoggedIn() || $_SESSION['user_email'] !== 'contact@gael-berru.com') {
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
    die("Erreur de connexion à la base de données : " . $e->getMessage());
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

    // Activité récente (7 jours)
    $stmt = $pdo->query("
        SELECT DATE(created_at) AS date, COUNT(*) AS count
        FROM users
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $recentActivity = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur lors de la récupération des données : " . $e->getMessage());
}

// --- Export des emails ---
if (isset($_GET['export_emails'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="smartpixel_users_emails.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Email', 'Plan', 'Nombre de sites', 'Date d\'inscription', 'Dernière connexion']);
    foreach ($usersList as $user) {
        fputcsv($out, [
            $user['email'],
            strtoupper($user['plan']),
            $user['site_count'],
            $user['created_at'],
            $user['last_login'] ?? 'Jamais'
        ]);
    }
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LibreAnalytics — Contrôle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- CSS existant (versionné) -->
    <link rel="stylesheet" href="https://gael-berru.com/LibreAnalytics/smart_pixel_v2/assets/dashboard.css">
    <!-- CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/map.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/geodata/worldLow.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
    <!-- Wallet crypto : SweetAlert2 + Toastify -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <style>
        /* =====================================================
           LIBREANALYTICS — "TERMINAL ÉDITORIAL"
           Thème singulier : fond encre profonde, typographie
           mono/grotesk, coins vifs, filets fins, accent lime
           + violet. Structure en "rapport" plutôt qu'en admin.
           ===================================================== */

        :root {
            --ink: #0b0d12;
            --ink-2: #10131b;
            --ink-3: #161a25;
            --line: #232839;
            --line-soft: #1b2030;
            --txt: #e8eaf2;
            --txt-dim: #8b90a5;
            --lime: #c8f65d;
            --violet: #ab9ff2;
            --rose: #ff7a8a;
            --mono: 'JetBrains Mono', monospace;
            --grot: 'Space Grotesk', sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: var(--grot);
            background: var(--ink);
            color: var(--txt);
            line-height: 1.5;
            background-image:
                linear-gradient(var(--line-soft) 1px, transparent 1px),
                linear-gradient(90deg, var(--line-soft) 1px, transparent 1px);
            background-size: 44px 44px;
        }

        ::selection { background: var(--lime); color: var(--ink); }

        a { color: inherit; }

        .shell {
            max-width: 1520px;
            margin: 0 auto;
            padding: 0 clamp(0.8rem, 3vw, 2.5rem) 4rem;
        }

        /* ===== TOPBAR ===== */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(11, 13, 18, 0.88);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--line);
        }

        .topbar-inner {
            max-width: 1520px;
            margin: 0 auto;
            padding: 0.75rem clamp(0.8rem, 3vw, 2.5rem);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .brand {
            display: flex;
            align-items: baseline;
            gap: 0.6rem;
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
            font-size: 0.65rem;
            color: var(--txt-dim);
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .live-dot {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-family: var(--mono);
            font-size: 0.7rem;
            color: var(--txt-dim);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .live-dot::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--lime);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.25; }
        }

        /* ===== MENU DÉROULANT "COMMANDES" ===== */
        .cmd {
            position: relative;
        }

        .cmd-toggle {
            font-family: var(--mono);
            font-size: 0.78rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--txt);
            background: var(--ink-3);
            border: 1px solid var(--line);
            padding: 0.55rem 1rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            transition: border-color 0.2s, color 0.2s;
        }

        .cmd-toggle:hover { border-color: var(--lime); color: var(--lime); }

        .cmd-toggle .caret { transition: transform 0.25s; font-size: 0.6rem; }

        .cmd.open .cmd-toggle .caret { transform: rotate(180deg); }

        .cmd-menu {
            position: absolute;
            right: 0;
            top: calc(100% + 0.4rem);
            min-width: 260px;
            background: var(--ink-2);
            border: 1px solid var(--line);
            border-top: 2px solid var(--lime);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px);
            transition: opacity 0.2s, transform 0.2s, visibility 0.2s;
        }

        .cmd.open .cmd-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .cmd-menu a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.7rem 1rem;
            font-size: 0.85rem;
            text-decoration: none;
            color: var(--txt);
            border-bottom: 1px solid var(--line-soft);
            transition: background 0.15s, color 0.15s, padding-left 0.15s;
        }

        .cmd-menu a:last-child { border-bottom: none; }

        .cmd-menu a i {
            width: 1.2rem;
            text-align: center;
            color: var(--violet);
            font-size: 0.8rem;
        }

        .cmd-menu a:hover {
            background: var(--ink-3);
            color: var(--lime);
            padding-left: 1.35rem;
        }

        /* ===== HERO / RAPPORT ===== */
        .report {
            padding: 3.5rem 0 2rem;
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: end;
            gap: 1.5rem;
        }

        .report h1 {
            font-size: clamp(2rem, 6vw, 4.2rem);
            font-weight: 700;
            line-height: 0.95;
            letter-spacing: -0.03em;
            text-transform: uppercase;
        }

        .report h1 .stroke {
            color: transparent;
            -webkit-text-stroke: 1.5px var(--violet);
        }

        .report .meta {
            font-family: var(--mono);
            font-size: 0.7rem;
            color: var(--txt-dim);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 0.8rem;
        }

        .report .meta strong { color: var(--lime); font-weight: 700; }

        @media (max-width: 700px) {
            .report { grid-template-columns: 1fr; }
        }

        /* ===== SECTIONS ===== */
        .section { margin-bottom: 3rem; }

        .section-head {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1rem;
        }

        .section-head .index {
            font-family: var(--mono);
            font-size: 0.7rem;
            color: var(--lime);
            letter-spacing: 1px;
        }

        .section-head h2 {
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: var(--txt);
        }

        .section-head::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--line);
        }

        /* ===== STATS — bandeau horizontal défilable ===== */
        .stats-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border: 1px solid var(--line);
            background: var(--ink-2);
        }

        .stat {
            padding: 1.4rem 1.2rem;
            border-right: 1px solid var(--line);
            position: relative;
            transition: background 0.2s;
        }

        .stat:last-child { border-right: none; }

        .stat:hover { background: var(--ink-3); }

        .stat .k {
            font-family: var(--mono);
            font-size: 0.62rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--txt-dim);
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .stat .k i { color: var(--violet); font-size: 0.7rem; }

        .stat .v {
            font-family: var(--mono);
            font-size: clamp(1.6rem, 3vw, 2.4rem);
            font-weight: 700;
            color: var(--txt);
            letter-spacing: -1px;
        }

        .stat::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -1px;
            width: 0;
            height: 2px;
            background: var(--lime);
            transition: width 0.35s;
        }

        .stat:hover::after { width: 100%; }

        @media (max-width: 900px) {
            .stats-strip { grid-template-columns: repeat(2, 1fr); }
            .stat:nth-child(2) { border-right: none; }
            .stat:nth-child(1), .stat:nth-child(2) { border-bottom: 1px solid var(--line); }
        }

        @media (max-width: 480px) {
            .stats-strip { grid-template-columns: 1fr; }
            .stat { border-right: none; border-bottom: 1px solid var(--line); }
            .stat:last-child { border-bottom: none; }
        }

        /* ===== PANNEAUX ===== */
        .panel {
            background: var(--ink-2);
            border: 1px solid var(--line);
            position: relative;
        }

        .panel::before {
            content: '';
            position: absolute;
            top: -1px;
            left: -1px;
            width: 14px;
            height: 14px;
            border-top: 2px solid var(--lime);
            border-left: 2px solid var(--lime);
        }

        .panel-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            padding: 1rem 1.4rem;
            border-bottom: 1px solid var(--line);
        }

        .panel-title {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }

        .panel-title i { color: var(--lime); font-size: 0.85rem; }

        .panel-body { padding: 1.4rem; }

        .duo {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 1.2rem;
            margin-bottom: 1.2rem;
        }

        @media (max-width: 1000px) {
            .duo { grid-template-columns: 1fr; }
        }

        .duo-2 {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 1.2rem;
        }

        @media (max-width: 1000px) {
            .duo-2 { grid-template-columns: 1fr; }
        }

        /* ===== WALLET ===== */
        #portfolio-total {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
            padding: 1.1rem 1.4rem;
            background: var(--ink-3);
            border: 1px solid var(--line);
            border-left: 3px solid var(--lime);
            margin-bottom: 0.9rem;
        }

        #portfolio-total .label {
            font-family: var(--mono);
            font-size: 0.62rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--txt-dim);
        }

        #portfolio-total h3 {
            font-family: var(--mono);
            font-size: clamp(1.5rem, 3.5vw, 2.1rem);
            font-weight: 700;
            color: var(--lime);
            letter-spacing: -1px;
        }

        #crypto-prices { display: flex; flex-direction: column; }

        .crypto-item {
            display: grid;
            grid-template-columns: 36px 1fr auto auto;
            align-items: center;
            gap: 0.9rem;
            padding: 0.9rem 1.1rem;
            border-bottom: 1px solid var(--line-soft);
            transition: background 0.15s;
        }

        .crypto-item:last-child { border-bottom: none; }
        .crypto-item:hover { background: var(--ink-3); }

        .crypto-item img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            filter: grayscale(30%);
            transition: filter 0.2s;
        }

        .crypto-item:hover img { filter: none; }

        .crypto-item .name { display: flex; flex-direction: column; }

        .crypto-item .symbol {
            font-family: var(--mono);
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--txt);
        }

        .crypto-item .price {
            font-family: var(--mono);
            font-size: 0.75rem;
            color: var(--txt-dim);
        }

        .crypto-item .holdings { display: flex; flex-direction: column; text-align: right; }

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

        .crypto-item .change.positive::before { content: '▲ '; font-size: 0.6rem; }
        .crypto-item .change.negative::before { content: '▼ '; font-size: 0.6rem; }
        .crypto-item .change.positive { color: var(--lime); }
        .crypto-item .change.negative { color: var(--rose); }

        @media (max-width: 420px) {
            .crypto-item { grid-template-columns: 32px 1fr auto; }
            .crypto-item .holdings { display: none; }
        }

        /* Adresses wallet */
        .wallet-row {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.85rem 0;
            border-bottom: 1px dashed var(--line);
        }

        .wallet-row:last-child { border-bottom: none; }

        .wallet-row .network {
            font-family: var(--mono);
            font-size: 0.62rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--ink);
            background: var(--lime);
            padding: 0.3rem 0.5rem;
            min-width: 52px;
            text-align: center;
        }

        .wallet-row .address {
            flex: 1;
            font-family: var(--mono);
            font-size: 0.72rem;
            color: var(--txt-dim);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .copy-button {
            font-family: var(--mono);
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: 1px solid var(--violet);
            background: transparent;
            color: var(--violet);
            padding: 0.4rem 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .copy-button:hover {
            background: var(--violet);
            color: var(--ink);
        }

        .wallet-links-btn {
            font-family: var(--mono);
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: 1px solid var(--line);
            background: transparent;
            color: var(--txt);
            padding: 0.5rem 0.9rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .wallet-links-btn i { color: var(--lime); }

        .wallet-links-btn:hover {
            border-color: var(--lime);
            color: var(--lime);
        }

        /* ===== TABLEAUX ===== */
        .table-responsive { overflow-x: auto; }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-family: var(--mono);
            font-size: 0.78rem;
        }

        .data-table th {
            text-align: left;
            padding: 0.8rem 1rem;
            color: var(--txt-dim);
            font-weight: 700;
            font-size: 0.62rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            border-bottom: 1px solid var(--line);
            white-space: nowrap;
        }

        .data-table td {
            padding: 0.8rem 1rem;
            border-bottom: 1px solid var(--line-soft);
            color: var(--txt);
            white-space: nowrap;
        }

        .data-table tbody tr { transition: background 0.15s; }
        .data-table tbody tr:hover { background: var(--ink-3); }

        .data-table code {
            background: var(--ink-3);
            padding: 0.15rem 0.4rem;
            color: var(--lime);
            font-size: 0.72rem;
        }

        /* ===== BADGES PLANS ===== */
        .badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            font-family: var(--mono);
            font-size: 0.62rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: 1px solid;
        }

        .badge-free { background: transparent; color: var(--txt-dim); border-color: var(--line); }
        .badge-pro { background: transparent; color: var(--lime); border-color: var(--lime); }
        .badge-business { background: transparent; color: var(--rose); border-color: var(--rose); }
        .badge-premium { background: transparent; color: var(--violet); border-color: var(--violet); }

        /* ===== EXPORT ===== */
        .export-btn {
            font-family: var(--mono);
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: var(--lime);
            color: var(--ink);
            padding: 0.55rem 1rem;
            border: none;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: filter 0.2s;
        }

        .export-btn:hover { filter: brightness(1.1); }

        /* ===== GRAPHIQUE & CARTE ===== */
        #worldMap {
            width: 100%;
            height: 400px;
            background: var(--ink-2);
        }

        .chart-wrap { position: relative; max-height: 320px; }

        /* ===== EMPTY ===== */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--txt-dim);
            font-family: var(--mono);
            font-size: 0.8rem;
            font-style: italic;
        }

        /* ===== SWEETALERT ===== */
        .custom-swal-popup {
            background: var(--ink-2) !important;
            color: var(--txt) !important;
            border: 1px solid var(--line) !important;
            border-radius: 0 !important;
            font-family: var(--grot) !important;
        }

        .custom-swal-popup h2 {
            font-family: var(--mono) !important;
            color: var(--lime) !important;
            letter-spacing: 2px;
        }

        .custom-swal-close-button { color: var(--txt-dim) !important; }

        .custom-swal-content ul { list-style: none; padding: 0; text-align: left; }

        .custom-swal-content li {
            padding: 0.7rem 0;
            border-bottom: 1px solid var(--line);
            font-family: var(--mono);
            font-size: 0.85rem;
        }

        .custom-swal-content li:last-child { border-bottom: none; }

        .custom-swal-content a {
            color: var(--lime);
            text-decoration: none;
            font-weight: 700;
        }

        .custom-swal-content a:hover { text-decoration: underline; }

        /* ===== TOASTIFY ===== */
        .toastify {
            font-family: var(--mono) !important;
            border-radius: 0 !important;
            border-left: 3px solid var(--ink) !important;
        }
    </style>
</head>

<body>
    <!-- ===== TOPBAR avec menu déroulant "Commandes" ===== -->
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="dashboard.php">
                <span class="sigil">LB—PX</span>
                <small>LibreAnalytics / contrôle</small>
            </a>
            <div class="topbar-right">
                <span class="live-dot">Live</span>
                <nav class="cmd" id="cmd-menu">
                    <button class="cmd-toggle" id="cmd-toggle" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-terminal"></i> Commandes <i class="fas fa-chevron-down caret"></i>
                    </button>
                    <div class="cmd-menu" role="menu">
                        <a href="dashboard.php" role="menuitem"><i class="fas fa-arrow-left"></i> Dashboard</a>
                        <a href="../campain/rapport.php" role="menuitem"><i class="fas fa-file-alt"></i> Rapport id5</a>
                        <a href="../campain/rapport_golden.php" role="menuitem"><i class="fas fa-file-alt"></i> Rapport id4</a>
                        <a href="../campain/prospect_template.php" role="menuitem"><i class="fa-regular fa-file-code"></i> Script prospection</a>
                        <a href="?export_emails=1" role="menuitem"><i class="fas fa-download"></i> Export emails CSV</a>
                    </div>
                </nav>
            </div>
        </div>
    </header>

    <div class="shell">
        <!-- ===== HERO ===== -->
        <section class="report">
            <div>
                <h1>Rapport<br><span class="stroke">SmartPixel</span></h1>
                <p class="meta">console admin — <strong><?= date('d.m.Y') ?></strong> — accès : <?= htmlspecialchars($_SESSION['user_email']) ?></p>
            </div>
        </section>

        <!-- ===== 01 — STATS ===== -->
        <section class="section">
            <div class="section-head"><span class="index">01</span><h2>Signaux globaux</h2></div>
            <div class="stats-strip">
                <div class="stat">
                    <div class="k"><i class="fas fa-users"></i> Utilisateurs</div>
                    <div class="v"><?= number_format(count($usersList)) ?></div>
                </div>
                <div class="stat">
                    <div class="k"><i class="fas fa-globe"></i> Sites</div>
                    <div class="v"><?= number_format(array_sum(array_column($topSites, 'total_site'))) ?></div>
                </div>
                <div class="stat">
                    <div class="k"><i class="fas fa-eye"></i> Visites</div>
                    <div class="v"><?= number_format(array_sum(array_column($visitedCountries, 'visits'))) ?></div>
                </div>
                <div class="stat">
                    <div class="k"><i class="fas fa-user-check"></i> Visiteurs uniques</div>
                    <div class="v"><?= number_format(end($historicalData)['cumulative_unique_visitors']) ?></div>
                </div>
            </div>
        </section>

        <!-- ===== 02 — WALLET CRYPTO ===== -->
        <section class="section">
            <div class="section-head"><span class="index">02</span><h2>Wallet — temps réel</h2></div>
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

        <!-- ===== 03 — CROISSANCE ===== -->
        <section class="section">
            <div class="section-head"><span class="index">03</span><h2>Croissance générale</h2></div>
            <div class="panel">
                <div class="panel-head">
                    <h3 class="panel-title"><i class="fas fa-chart-line"></i> Évolution cumulative — 7 jours</h3>
                </div>
                <div class="panel-body chart-wrap">
                    <canvas id="globalStatsChart" height="110"></canvas>
                </div>
            </div>
        </section>

        <!-- ===== 04 — TOP SITES + CARTE ===== -->
        <section class="section">
            <div class="section-head"><span class="index">04</span><h2>Top sites &amp; géographie</h2></div>
            <div class="duo-2">
                <div class="panel">
                    <div class="panel-head">
                        <h3 class="panel-title"><i class="fas fa-trophy"></i> Top 5 sites</h3>
                    </div>
                    <div class="panel-body" style="padding: 0;">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Visites</th>
                                        <th>ID</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($topSites)): ?>
                                        <?php foreach ($topSites as $site): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($site['site_name']) ?></td>
                                                <td><strong style="color: var(--lime);"><?= number_format($site['total_visits']) ?></strong></td>
                                                <td><code><?= htmlspecialchars($site['id']) ?></code></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="empty-state">Aucune donnée</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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

        <!-- ===== 05 — UTILISATEURS ===== -->
        <section class="section">
            <div class="section-head"><span class="index">05</span><h2>Registre des utilisateurs (<?= count($usersList) ?>)</h2></div>
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
                                        // Gestion de la classe du badge selon le plan
                                        $planClass = 'free'; // default
                                        if (isset($user['plan'])) {
                                            $planClass = strtolower($user['plan']);
                                        }
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td><span class="badge badge-<?= $planClass ?>"><?= strtoupper($user['plan'] ?? 'free') ?></span></td>
                                            <td><?= (int)($user['site_count'] ?? 0) ?></td>
                                            <td><?= number_format($user['total_visits'] ?? 0) ?></td>
                                            <td><?= isset($user['created_at']) ? (new DateTime($user['created_at']))->format('d/m/Y') : '-' ?></td>
                                            <td><?= isset($user['last_login']) && $user['last_login'] ? (new DateTime($user['last_login']))->format('d/m/Y H:i') : '<span style="color:var(--txt-dim);">Jamais</span>' ?></td>
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
    </div> <!-- .shell -->

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

            // Fermer au clic extérieur ou à l'échappement
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
        const tokenHoldings = {
            bitcoin: 0,
            solana: 4.65,
            sui: 613,
        };

        function refreshCryptoPrices() {
            fetch('https://api.coingecko.com/api/v3/coins/markets?vs_currency=eur&ids=bitcoin,solana,sui')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('crypto-prices');
                    let totalPortfolioValue = 0;

                    // Total du portefeuille en tête
                    let totalElement = document.querySelector('#portfolio-total');
                    if (!totalElement) {
                        totalElement = document.createElement('div');
                        totalElement.id = 'portfolio-total';
                        container.prepend(totalElement);
                    }

                    data.forEach(crypto => {
                        const price = crypto.current_price;
                        const change24h = crypto.price_change_percentage_24h.toFixed(2);
                        const holdings = tokenHoldings[crypto.id] || 0;
                        const totalValue = (price * holdings).toFixed(2);
                        const imageUrl = crypto.image;

                        totalPortfolioValue += parseFloat(totalValue);

                        let cryptoElement = document.querySelector(`#${crypto.id}`);

                        if (!cryptoElement) {
                            cryptoElement = document.createElement('div');
                            cryptoElement.id = crypto.id;
                            cryptoElement.classList.add('crypto-item');
                            cryptoElement.innerHTML = `
                                <img src="${imageUrl}" alt="${crypto.id} logo">
                                <div class="name">
                                    <span class="symbol">${crypto.symbol.toUpperCase()}</span>
                                    <span class="price">${price.toLocaleString('fr-FR', {minimumFractionDigits: 2})} €</span>
                                </div>
                                <div class="holdings">
                                    <span class="amount">${holdings} ${crypto.symbol.toUpperCase()}</span>
                                    <span class="value">${totalValue} €</span>
                                </div>
                                <p class="change">${change24h}%</p>
                            `;
                            container.appendChild(cryptoElement);
                        } else {
                            cryptoElement.querySelector('.price').textContent = `${price.toLocaleString('fr-FR', {minimumFractionDigits: 2})} €`;
                            cryptoElement.querySelector('.amount').textContent = `${holdings} ${crypto.symbol.toUpperCase()}`;
                            cryptoElement.querySelector('.value').textContent = `${totalValue} €`;
                            cryptoElement.querySelector('.change').textContent = `${change24h}%`;
                        }

                        const changeElement = cryptoElement.querySelector('.change');
                        changeElement.classList.toggle('positive', change24h >= 0);
                        changeElement.classList.toggle('negative', change24h < 0);
                    });

                    totalElement.innerHTML = `<div class="label">Valeur totale — live</div><h3>${totalPortfolioValue.toFixed(2)} €</h3>`;
                })
                .catch(error => console.error('Erreur lors de la récupération des données:', error));
        }

        refreshCryptoPrices();
        // Rafraîchissement toutes les 60s (limite gratuite CoinGecko)
        setInterval(refreshCryptoPrices, 60000);

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

        // ===== DASHBOARD PIXEL =====
        // --- Graphique 4 courbes ---
        const globalCtx = document.getElementById('globalStatsChart').getContext('2d');
        Chart.defaults.font.family = "'JetBrains Mono', monospace";
        Chart.defaults.color = '#8b90a5';

        new Chart(globalCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($historicalData, 'date')) ?>,
                datasets: [{
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
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#1b2030'
                        },
                        title: {
                            display: true,
                            text: 'Nombre'
                        }
                    }
                }
            }
        });

        // --- Carte du monde ---
        document.addEventListener('DOMContentLoaded', function() {
            const countries = <?= json_encode($visitedCountries ?? []) ?>;
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
                return map[countryName.toLowerCase().trim()] || null;
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

            // Animation de survol
            polygonSeries.mapPolygons.template.states.create("hover", {
                fill: am5.color(0xab9ff2)
            });
        });
    </script>
</body>

</html>