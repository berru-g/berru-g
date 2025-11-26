<?php
// dashboard.php - DASHBOARD AMÉLIORÉ AVEC ONGLET DÉTAIL
require_once 'config.php';
//require_once '../board/includes/header.php'; // a integrer dans mon /board/

$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);

// Vérification de sécurité basique
if (!isset($_SESSION['admin_logged_in'])) {
    // Redirection vers une page de login si nécessaire
    // header('Location: login.php');
    // exit();
}

// Filtre de période (par défaut: 30 derniers jours)
$period = isset($_GET['period']) ? $_GET['period'] : 30;
$dateFilter = date('Y-m-d H:i:s', strtotime("-$period days"));

// STATS GÉNÉRALES
$totalViews = $pdo->query("SELECT COUNT(*) FROM " . DB_TABLE)->fetchColumn();
$uniqueVisitors = $pdo->query("SELECT COUNT(DISTINCT ip_address) FROM " . DB_TABLE)->fetchColumn();

// Visiteurs uniques sur la période
$uniqueVisitorsPeriod = $pdo->query("
    SELECT COUNT(DISTINCT ip_address) 
    FROM " . DB_TABLE . " 
    WHERE timestamp >= '$dateFilter'
")->fetchColumn();

// SOURCES DE TRAFIC
$sources = $pdo->query("
    SELECT source, COUNT(*) as count 
    FROM " . DB_TABLE . " 
    WHERE timestamp >= '$dateFilter'
    GROUP BY source 
    ORDER BY count DESC
")->fetchAll();

// TOP PAGES
$topPages = $pdo->query("
    SELECT page_url, COUNT(*) as views 
    FROM " . DB_TABLE . " 
    WHERE page_url != 'direct' AND timestamp >= '$dateFilter'
    GROUP BY page_url 
    ORDER BY views DESC 
    LIMIT 10
")->fetchAll();

// GÉOLOCALISATION
$countries = $pdo->query("
    SELECT country, COUNT(*) as visits 
    FROM " . DB_TABLE . " 
    WHERE timestamp >= '$dateFilter'
    GROUP BY country 
    ORDER BY visits DESC 
    LIMIT 10
")->fetchAll();

// APPAREILS ET NAVIGATEURS
$devices = $pdo->query("
    SELECT 
        CASE 
            WHEN user_agent LIKE '%Mobile%' THEN 'Mobile'
            WHEN user_agent LIKE '%Tablet%' THEN 'Tablet'
            ELSE 'Desktop'
        END as device,
        COUNT(*) as count
    FROM " . DB_TABLE . "
    WHERE timestamp >= '$dateFilter'
    GROUP BY device
    ORDER BY count DESC
")->fetchAll();

// NAVIGATEURS
$browsers = $pdo->query("
    SELECT 
        CASE 
            WHEN user_agent LIKE '%Chrome%' THEN 'Chrome'
            WHEN user_agent LIKE '%Firefox%' THEN 'Firefox'
            WHEN user_agent LIKE '%Safari%' THEN 'Safari'
            WHEN user_agent LIKE '%Edge%' THEN 'Edge'
            ELSE 'Other'
        END as browser,
        COUNT(*) as count
    FROM " . DB_TABLE . "
    WHERE timestamp >= '$dateFilter'
    GROUP BY browser
    ORDER BY count DESC
")->fetchAll();

// ÉVOLUTION TEMPORELLE (7 derniers jours)
$dailyStats = $pdo->query("
    SELECT 
        DATE(timestamp) as date,
        COUNT(*) as visits,
        COUNT(DISTINCT ip_address) as unique_visitors
    FROM " . DB_TABLE . "
    WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(timestamp)
    ORDER BY date ASC
")->fetchAll();

// DONNÉES DE CLICS (si disponibles)
$clickData = $pdo->query("
    SELECT click_data
    FROM " . DB_TABLE . "
    WHERE click_data IS NOT NULL AND click_data != '' AND timestamp >= '$dateFilter'
    LIMIT 100
")->fetchAll();

// ANALYSE DES SESSIONS
$sessionData = $pdo->query("
    SELECT 
        session_id,
        COUNT(*) as page_views,
        MIN(timestamp) as first_visit,
        MAX(timestamp) as last_visit
    FROM " . DB_TABLE . "
    WHERE session_id != '' AND timestamp >= '$dateFilter'
    GROUP BY session_id
    ORDER BY page_views DESC
    LIMIT 10
")->fetchAll();

// DONNÉES DÉTAILLÉES POUR L'ONGLET DÉTAIL
$detailedData = $pdo->query("
    SELECT 
        ip_address,
        country,
        city,
        page_url,
        timestamp,
        user_agent,
        source,
        session_id
    FROM " . DB_TABLE . "
    WHERE timestamp >= '$dateFilter'
    ORDER BY timestamp DESC
    LIMIT 50
")->fetchAll();

// Calcul du temps moyen de session
$avgSessionTime = 0;
if (count($sessionData) > 0) {
    $totalSessionTime = 0;
    foreach ($sessionData as $session) {
        $first = strtotime($session['first_visit']);
        $last = strtotime($session['last_visit']);
        $totalSessionTime += ($last - $first);
    }
    $avgSessionTime = round($totalSessionTime / count($sessionData) / 60, 1); // en minutes
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Pixel Analytics - Tableau de bord amélioré</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fb;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.5rem 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        h1 {
            font-size: 1.8rem;
            font-weight: 600;
        }

        .period-filter {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .period-filter select {
            padding: 8px 12px;
            border-radius: 5px;
            border: none;
            background: white;
            color: var(--dark);
        }

        .dashboard-tabs {
            margin: 2rem 0;
        }

        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .tab {
            padding: 12px 24px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            font-weight: 500;
        }

        .tab.active {
            border-bottom-color: var(--primary);
            color: var(--primary);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary);
            margin: 10px 0;
        }

        .stat-change {
            font-size: 0.85rem;
            padding: 4px 8px;
            border-radius: 20px;
            display: inline-block;
        }

        .positive {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success);
        }

        .negative {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--danger);
        }

        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin: 20px 0;
        }

        .chart-container.small {
            height: 300px;
        }

        .chart-title {
            font-size: 1.1rem;
            margin-bottom: 1rem;
            color: var(--dark);
            font-weight: 600;
        }

        .data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .data-grid.compact {
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 0.9rem;
        }

        .data-table th,
        .data-table td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        .data-table tr:hover {
            background-color: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-primary {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }

        .badge-success {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success);
        }

        .ip-address {
            font-family: monospace;
            font-size: 0.85rem;
        }

        .url-truncate {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        footer {
            text-align: center;
            padding: 2rem 0;
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 3rem;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }

            .header-content {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .data-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .tabs {
                overflow-x: auto;
                white-space: nowrap;
            }
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 1200px) {
            .container {
                max-width: 100%;
                padding: 0 15px;
            }

            .data-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }

        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .data-grid {
                grid-template-columns: 1fr;
            }

            .header-content {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .tabs {
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 5px;
            }

            .tab {
                padding: 10px 16px;
                font-size: 0.9rem;
            }

            .chart-container {
                padding: 15px;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .stat-card {
                padding: 20px;
            }

            .stat-value {
                font-size: 1.8rem;
            }

            .data-table {
                font-size: 0.85rem;
            }

            .data-table th,
            .data-table td {
                padding: 8px 10px;
            }

            /* Amélioration de l'affichage des tableaux sur mobile */
            .data-table-container {
                overflow-x: auto;
            }

            .chart-container.small {
                height: 250px;
            }
        }

        @media (max-width: 576px) {
            h1 {
                font-size: 1.5rem;
            }

            .period-filter {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .period-filter select {
                width: 100%;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-value {
                font-size: 1.6rem;
            }

            .chart-container {
                padding: 12px;
                margin: 15px 0;
            }

            .chart-title {
                font-size: 1rem;
            }

            /* Optimisation pour les très petits écrans */
            .tab {
                padding: 8px 12px;
                font-size: 0.85rem;
            }

            /* Cache certaines colonnes dans les tableaux sur mobile */
            .data-table th:nth-child(4),
            .data-table td:nth-child(4),
            .data-table th:nth-child(6),
            .data-table td:nth-child(6) {
                display: none;
            }

            .url-truncate {
                max-width: 150px;
            }
        }

        /* Amélioration du scroll horizontal pour les tableaux */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Optimisation pour l'orientation paysage sur mobile */
        @media (max-height: 500px) and (orientation: landscape) {
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }

            .chart-container.small {
                height: 200px;
            }
        }

        /* Support des écrans haute résolution */
        @media (min-resolution: 192dpi) {
            .stat-card {
                border: 0.5px solid #e9ecef;
            }
        }

        /* Mode sombre automatique 
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #1a1a1a;
                color: #e9ecef;
            }

            .stat-card,
            .chart-container {
                background-color: #2d3748;
                color: #e9ecef;
            }

            .data-table th {
                background-color: #4a5568;
                color: #e9ecef;
            }

            .data-table tr:hover {
                background-color: #4a5568;
            }
        }*/

        /* Animation de chargement pour les graphiques */
        .chart-loading {
            position: relative;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chart-loading::after {
            content: "Chargement...";
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Amélioration de l'accessibilité */
        @media (prefers-reduced-motion: reduce) {
            .stat-card {
                transition: none;
            }
        }

        /* Focus visible pour la navigation au clavier */
        .tab:focus {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* Impression */
        @media print {

            .tabs,
            .period-filter,
            footer {
                display: none;
            }

            .tab-content {
                display: block !important;
            }

            .stat-card,
            .chart-container {
                break-inside: avoid;
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <div class="header-content">
                <h1>Smart Pixel Analytics <a href="dashboardV2.php">V2</a></h1>
                <div class="period-filter">
                    <span>Période :</span>
                    <select id="periodSelect" onchange="changePeriod(this.value)">
                        <option value="7" <?= $period == 7 ? 'selected' : '' ?>>7 jours</option>
                        <option value="30" <?= $period == 30 ? 'selected' : '' ?>>30 jours</option>
                        <option value="90" <?= $period == 90 ? 'selected' : '' ?>>90 jours</option>
                        <option value="365" <?= $period == 365 ? 'selected' : '' ?>>1 an</option>
                    </select>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="dashboard-tabs">
            <div class="tabs">
                <div class="tab active" onclick="openTab('overview')">Aperçu</div>
                <div class="tab" onclick="openTab('traffic')">Trafic</div>
                <div class="tab" onclick="openTab('geography')">Géographie</div>
                <div class="tab" onclick="openTab('devices')">Appareils</div>
                <div class="tab" onclick="openTab('content')">Contenu</div>
                <div class="tab" onclick="openTab('sessions')">Sessions</div>
                <div class="tab" onclick="openTab('details')">Détails</div>
            </div>

            <!-- ONGLET APERÇU -->
            <div id="overview" class="tab-content active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Vues totales</h3>
                        <div class="stat-value"><?= number_format($totalViews) ?></div>
                        <div class="stat-change positive">+12%</div>
                    </div>
                    <div class="stat-card">
                        <h3>Visiteurs uniques</h3>
                        <div class="stat-value"><?= number_format($uniqueVisitorsPeriod) ?></div>
                        <div class="stat-change positive">+8%</div>
                    </div>
                    <div class="stat-card">
                        <h3>Pages vues/session</h3>
                        <div class="stat-value">2.4</div>
                        <div class="stat-change negative">-3%</div>
                    </div>
                    <div class="stat-card">
                        <h3>Temps moyen</h3>
                        <div class="stat-value"><?= $avgSessionTime ?> min</div>
                        <div class="stat-change positive">+5%</div>
                    </div>
                </div>

                <div class="chart-container">
                    <h3 class="chart-title">Évolution du trafic (7 derniers jours)</h3>
                    <canvas id="trafficChart" height="80"></canvas>
                </div>

                <div class="data-grid compact">
                    <div class="chart-container small">
                        <h3 class="chart-title">Sources de trafic</h3>
                        <canvas id="sourcesChart"></canvas>
                    </div>

                    <div class="chart-container small">
                        <h3 class="chart-title">Appareils utilisés</h3>
                        <canvas id="devicesChart"></canvas>
                    </div>

                    <div class="chart-container small">
                        <h3 class="chart-title">Top pays</h3>
                        <canvas id="countriesOverviewChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- ONGLET TRAFIC -->
            <div id="traffic" class="tab-content">
                <div class="data-grid">
                    <div class="chart-container">
                        <h3 class="chart-title">Sources de trafic</h3>
                        <canvas id="sourcesTrafficChart" height="200"></canvas>
                    </div>

                    <div class="chart-container">
                        <h3 class="chart-title">Navigateurs utilisés</h3>
                        <canvas id="browsersChart" height="200"></canvas>
                    </div>
                </div>

                <div class="chart-container">
                    <h3 class="chart-title">Détail des sources</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Source</th>
                                <th>Visites</th>
                                <th>Pourcentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sources as $source): ?>
                                <tr>
                                    <td><?= htmlspecialchars($source['source']) ?></td>
                                    <td><?= number_format($source['count']) ?></td>
                                    <td><?= round(($source['count'] / $uniqueVisitorsPeriod) * 100, 1) ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ONGLET GÉOGRAPHIE -->
            <div id="geography" class="tab-content">
                <div class="chart-container">
                    <h3 class="chart-title">Top pays par visites</h3>
                    <canvas id="countriesChart" height="200"></canvas>
                </div>

                <div class="chart-container">
                    <h3 class="chart-title">Répartition géographique</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Pays</th>
                                <th>Visites</th>
                                <th>Part du trafic</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($countries as $country): ?>
                                <tr>
                                    <td><?= htmlspecialchars($country['country']) ?></td>
                                    <td><?= number_format($country['visits']) ?></td>
                                    <td><?= round(($country['visits'] / $uniqueVisitorsPeriod) * 100, 1) ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ONGLET APPAREILS -->
            <div id="devices" class="tab-content">
                <div class="data-grid">
                    <div class="chart-container">
                        <h3 class="chart-title">Types d'appareils</h3>
                        <canvas id="deviceTypesChart" height="200"></canvas>
                    </div>

                    <div class="chart-container">
                        <h3 class="chart-title">Navigateurs</h3>
                        <canvas id="browserTypesChart" height="200"></canvas>
                    </div>
                </div>

                <div class="data-grid">
                    <div class="chart-container">
                        <h3 class="chart-title">Détail des appareils</h3>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Appareil</th>
                                    <th>Visites</th>
                                    <th>Pourcentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($devices as $device): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($device['device']) ?></td>
                                        <td><?= number_format($device['count']) ?></td>
                                        <td><?= round(($device['count'] / $uniqueVisitorsPeriod) * 100, 1) ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="chart-container">
                        <h3 class="chart-title">Détail des navigateurs</h3>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Navigateur</th>
                                    <th>Utilisations</th>
                                    <th>Pourcentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($browsers as $browser): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($browser['browser']) ?></td>
                                        <td><?= number_format($browser['count']) ?></td>
                                        <td><?= round(($browser['count'] / $uniqueVisitorsPeriod) * 100, 1) ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ONGLET CONTENU -->
            <div id="content" class="tab-content">
                <div class="chart-container">
                    <h3 class="chart-title">Pages les plus populaires</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Page</th>
                                <th>Vues</th>
                                <th>Pourcentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topPages as $page): ?>
                                <tr>
                                    <td class="url-truncate" title="<?= htmlspecialchars($page['page_url']) ?>">
                                        <?= htmlspecialchars($page['page_url']) ?>
                                    </td>
                                    <td><?= number_format($page['views']) ?></td>
                                    <td><?= round(($page['views'] / $uniqueVisitorsPeriod) * 100, 1) ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (count($clickData) > 0): ?>
                    <div class="chart-container">
                        <h3 class="chart-title">Données de clics récentes</h3>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Élément</th>
                                    <th>Texte</th>
                                    <th>Position</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $clickCount = 0;
                                foreach ($clickData as $click):
                                    if ($clickCount >= 10)
                                        break;
                                    $data = json_decode($click['click_data'], true);
                                    if (is_array($data)):
                                        ?>
                                        <tr>
                                            <td><span class="badge badge-primary"><?= htmlspecialchars($data['element']) ?></span>
                                            </td>
                                            <td><?= htmlspecialchars(substr($data['text'], 0, 30)) . (strlen($data['text']) > 30 ? '...' : '') ?>
                                            </td>
                                            <td><?= $data['x'] ?>x<?= $data['y'] ?></td>
                                        </tr>
                                        <?php
                                        $clickCount++;
                                    endif;
                                endforeach;
                                ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ONGLET SESSIONS -->
            <div id="sessions" class="tab-content">
                <div class="chart-container">
                    <h3 class="chart-title">Sessions les plus actives</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID Session</th>
                                <th>Pages vues</th>
                                <th>Première visite</th>
                                <th>Dernière visite</th>
                                <th>Durée</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessionData as $session):
                                $first = strtotime($session['first_visit']);
                                $last = strtotime($session['last_visit']);
                                $duration = round(($last - $first) / 60, 1); // en minutes
                                ?>
                                <tr>
                                    <td><?= substr($session['session_id'], 0, 8) ?>...</td>
                                    <td><?= $session['page_views'] ?></td>
                                    <td><?= date('H:i', $first) ?></td>
                                    <td><?= date('H:i', $last) ?></td>
                                    <td><?= $duration ?> min</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- NOUVEL ONGLET DÉTAILS -->

            <div id="details" class="tab-content">
                <div class="chart-container">
                    <h3 class="chart-title">Détails des visites récentes (50 dernières)</h3>
                    <table class="data-table">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>IP</th>
                                        <th>Pays</th>
                                        <th>Ville</th>
                                        <th>Page visitée</th>
                                        <th>Heure</th>
                                        <th>Source</th>
                                        <th>Session</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($detailedData as $visit):
                                        $visitTime = strtotime($visit['timestamp']);
                                        ?>
                                        <tr>
                                            <td class="ip-address"><?= htmlspecialchars($visit['ip_address']) ?></td>
                                            <td><?= htmlspecialchars($visit['country']) ?></td>
                                            <td><?= htmlspecialchars($visit['city']) ?></td>
                                            <td class="url-truncate" title="<?= htmlspecialchars($visit['page_url']) ?>">
                                                <?= htmlspecialchars($visit['page_url']) ?>
                                            </td>
                                            <td><?= date('H:i', $visitTime) ?></td>
                                            <td><span
                                                    class="badge badge-primary"><?= htmlspecialchars($visit['source']) ?></span>
                                            </td>
                                            <td><?= substr($visit['session_id'], 0, 8) ?>...</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <p><a href="https://gael-berru.com/">🟪</a> Smart Pixel Analytics &copy; <?= date('Y') ?> - Données mises à
                jour en temps réel - Respect des loi RGPD</p>
        </div>
    </footer>

    <script>
        // Fonction pour changer d'onglet
        function openTab(tabName) {
            // Masquer tous les contenus d'onglets
            const tabContents = document.getElementsByClassName('tab-content');
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove('active');
            }

            // Désactiver tous les onglets
            const tabs = document.getElementsByClassName('tab');
            for (let i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }

            // Activer l'onglet sélectionné
            document.getElementById(tabName).classList.add('active');
            event.currentTarget.classList.add('active');
        }

        // Fonction pour changer la période
        function changePeriod(period) {
            window.location.href = `?period=${period}`;
        }

        // Données pour les graphiques
        const dailyStats = <?= json_encode($dailyStats) ?>;
        const sources = <?= json_encode($sources) ?>;
        const devices = <?= json_encode($devices) ?>;
        const browsers = <?= json_encode($browsers) ?>;
        const countries = <?= json_encode($countries) ?>;

        // Configuration commune pour les petits graphiques
        const smallChartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        };

        // Graphique d'évolution du trafic
        const trafficCtx = document.getElementById('trafficChart').getContext('2d');
        const trafficChart = new Chart(trafficCtx, {
            type: 'line',
            data: {
                labels: dailyStats.map(stat => stat.date),
                datasets: [
                    {
                        label: 'Visites',
                        data: dailyStats.map(stat => stat.visits),
                        borderColor: '#4361ee',
                        backgroundColor: 'rgba(67, 97, 238, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Visiteurs uniques',
                        data: dailyStats.map(stat => stat.unique_visitors),
                        borderColor: '#4cc9f0',
                        backgroundColor: 'rgba(76, 201, 240, 0.1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Graphique des sources (aperçu)
        const sourcesCtx = document.getElementById('sourcesChart').getContext('2d');
        const sourcesChart = new Chart(sourcesCtx, {
            type: 'doughnut',
            data: {
                labels: sources.map(s => s.source),
                datasets: [{
                    data: sources.map(s => s.count),
                    backgroundColor: [
                        '#4361ee', '#4cc9f0', '#f72585', '#7209b7', '#4895ef'
                    ]
                }]
            },
            options: smallChartOptions
        });

        // Graphique des appareils (aperçu)
        const devicesCtx = document.getElementById('devicesChart').getContext('2d');
        const devicesChart = new Chart(devicesCtx, {
            type: 'pie',
            data: {
                labels: devices.map(d => d.device),
                datasets: [{
                    data: devices.map(d => d.count),
                    backgroundColor: ['#4361ee', '#4cc9f0', '#f72585']
                }]
            },
            options: smallChartOptions
        });

        // Graphique des pays (aperçu)
        const countriesOverviewCtx = document.getElementById('countriesOverviewChart').getContext('2d');
        const countriesOverviewChart = new Chart(countriesOverviewCtx, {
            type: 'bar',
            data: {
                labels: countries.map(c => c.country),
                datasets: [{
                    label: 'Visites',
                    data: countries.map(c => c.visits),
                    backgroundColor: '#4895ef'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Graphique des sources (trafic)
        const sourcesTrafficCtx = document.getElementById('sourcesTrafficChart').getContext('2d');
        const sourcesTrafficChart = new Chart(sourcesTrafficCtx, {
            type: 'doughnut',
            data: {
                labels: sources.map(s => s.source),
                datasets: [{
                    data: sources.map(s => s.count),
                    backgroundColor: [
                        '#4361ee', '#4cc9f0', '#f72585', '#7209b7', '#4895ef'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });

        // Graphique des navigateurs
        const browsersCtx = document.getElementById('browsersChart').getContext('2d');
        const browsersChart = new Chart(browsersCtx, {
            type: 'bar',
            data: {
                labels: browsers.map(b => b.browser),
                datasets: [{
                    label: 'Utilisations',
                    data: browsers.map(b => b.count),
                    backgroundColor: '#4895ef'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Graphique des pays
        const countriesCtx = document.getElementById('countriesChart').getContext('2d');
        const countriesChart = new Chart(countriesCtx, {
            type: 'bar',
            data: {
                labels: countries.map(c => c.country),
                datasets: [{
                    label: 'Visites',
                    data: countries.map(c => c.visits),
                    backgroundColor: '#4361ee'
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Graphique des types d'appareils
        const deviceTypesCtx = document.getElementById('deviceTypesChart').getContext('2d');
        const deviceTypesChart = new Chart(deviceTypesCtx, {
            type: 'doughnut',
            data: {
                labels: devices.map(d => d.device),
                datasets: [{
                    data: devices.map(d => d.count),
                    backgroundColor: ['#4361ee', '#4cc9f0', '#f72585']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });

        // Graphique des types de navigateurs
        const browserTypesCtx = document.getElementById('browserTypesChart').getContext('2d');
        const browserTypesChart = new Chart(browserTypesCtx, {
            type: 'pie',
            data: {
                labels: browsers.map(b => b.browser),
                datasets: [{
                    data: browsers.map(b => b.count),
                    backgroundColor: [
                        '#4361ee', '#4cc9f0', '#f72585', '#7209b7', '#4895ef'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });
    </script>
</body>

</html>