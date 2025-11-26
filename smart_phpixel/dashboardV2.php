<?php
// dashboard.php - DASHBOARD 2.0 DEBUGGÉ
require_once 'config.php';

$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);

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

// GÉOLOCALISATION POUR LA MAP
$countriesMap = $pdo->query("
    SELECT country, COUNT(*) as visits 
    FROM " . DB_TABLE . " 
    WHERE timestamp >= '$dateFilter' AND country != 'Unknown'
    GROUP BY country 
    ORDER BY visits DESC
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

// DONNÉES DE CLICS POUR HEATMAP (version sécurisée)
$clickData = $pdo->query("
    SELECT click_data, page_url
    FROM " . DB_TABLE . "
    WHERE click_data IS NOT NULL AND click_data != '' AND timestamp >= '$dateFilter'
    LIMIT 500
")->fetchAll();

// TOP ÉLÉMENTS CLIQUÉS (version sécurisée)
$topClicks = [];
try {
    $topClicks = $pdo->query("
        SELECT 
            TRIM(BOTH '\"' FROM JSON_EXTRACT(click_data, '$.element')) as element,
            COUNT(*) as click_count
        FROM " . DB_TABLE . "
        WHERE click_data IS NOT NULL AND click_data != '' AND timestamp >= '$dateFilter'
        GROUP BY TRIM(BOTH '\"' FROM JSON_EXTRACT(click_data, '$.element'))
        HAVING element IS NOT NULL AND element != ''
        ORDER BY click_count DESC
        LIMIT 15
    ")->fetchAll();
} catch (Exception $e) {
    // Fallback si JSON_EXTRACT ne fonctionne pas
    $topClicks = $pdo->query("
        SELECT 'button' as element, COUNT(*) as click_count
        FROM " . DB_TABLE . "
        WHERE click_data IS NOT NULL AND click_data != '' AND timestamp >= '$dateFilter'
        LIMIT 15
    ")->fetchAll();
}

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

/* NOUVEAU: TEMPS MOYEN PAR PAGE (version sécurisée)
$avgTimePerPage = [];
try {
    $avgTimePerPage = $pdo->query("
        SELECT 
            page_url,
            AVG(time_on_page) as avg_time,
            COUNT(*) as visits
        FROM " . DB_TABLE . "
        WHERE time_on_page > 0 AND timestamp >= '$dateFilter'
        GROUP BY page_url
        ORDER BY avg_time DESC
        LIMIT 10
    ")->fetchAll();
} catch(Exception $e) {
    // Tableau vide si la colonne n'existe pas
    $avgTimePerPage = [];
}*/

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

// Préparation des données pour la map (version sécurisée)
$mapData = [];
$maxVisits = 0;
if (count($countriesMap) > 0) {
    $visits = array_column($countriesMap, 'visits');
    $maxVisits = max($visits);

    foreach ($countriesMap as $country) {
        $mapData[] = [
            'id' => $country['country'],
            'name' => $country['country'],
            'value' => $country['visits']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Pixel Analytics - Dashboard 2.0</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <!-- Chargement conditionnel d'amCharts -->
    <script>
        // On charge amCharts seulement si nécessaire
        function loadAmCharts() {
            return new Promise((resolve) => {
                if (typeof am5 !== 'undefined') {
                    resolve();
                    return;
                }

                const script1 = document.createElement('script');
                script1.src = 'https://cdn.amcharts.com/lib/5/index.js';
                script1.onload = () => {
                    const script2 = document.createElement('script');
                    script2.src = 'https://cdn.amcharts.com/lib/5/map.js';
                    script2.onload = () => {
                        const script3 = document.createElement('script');
                        script3.src = 'https://cdn.amcharts.com/lib/5/geodata/worldLow.js';
                        script3.onload = () => {
                            const script4 = document.createElement('script');
                            script4.src = 'https://cdn.amcharts.com/lib/5/themes/Animated.js';
                            script4.onload = resolve;
                            document.head.appendChild(script4);
                        };
                        document.head.appendChild(script3);
                    };
                    document.head.appendChild(script2);
                };
                document.head.appendChild(script1);
            });
        }
        
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

            // Trouver et activer l'onglet correspondant dans le menu
            const allTabs = document.querySelectorAll('.tab');
            allTabs.forEach(tab => {
                if (tab.getAttribute('onclick').includes(tabName)) {
                    tab.classList.add('active');
                }
            });

            // Réinitialiser la map si on ouvre l'onglet géographie
            if (tabName === 'geography') {
                setTimeout(() => {
                    if (window.worldMap) {
                        window.worldMap.root.resize();
                    }
                }, 100);
            }
        }

        // Fonction pour changer la période
        function changePeriod(period) {
            window.location.href = `?period=${period}`;
        }
    </script>
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
            cursor: pointer;
        }

        .data-table tr.expanded {
            background-color: #e3f2fd;
        }

        .click-details {
            background: #f8f9fa;
            padding: 15px;
            border-left: 4px solid var(--primary);
            margin: 10px 0;
            display: none;
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

        #worldMap {
            width: 100%;
            height: 500px;
            background: white;
            border-radius: 10px;
        }

        .map-fallback {
            background: #f8f9fa;
            padding: 40px;
            text-align: center;
            border-radius: 10px;
            color: #6c757d;
        }

        .heatmap-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .heatmap-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .heatmap-item {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            text-align: center;
        }

        .heatmap-intensity {
            height: 20px;
            background: linear-gradient(90deg, #4cc9f0, #4361ee, #7209b7, #f72585);
            border-radius: 10px;
            margin: 5px 0;
        }

        footer {
            text-align: center;
            padding: 2rem 0;
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 3rem;
        }

        /* Responsive Design */
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

            #worldMap {
                height: 400px;
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

            .chart-container.small {
                height: 250px;
            }

            #worldMap {
                height: 300px;
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

            .tab {
                padding: 8px 12px;
                font-size: 0.85rem;
            }

            .url-truncate {
                max-width: 150px;
            }

            #worldMap {
                height: 250px;
            }
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .engagement-score {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .score-high {
            background: #d4edda;
            color: #155724;
        }

        .score-medium {
            background: #fff3cd;
            color: #856404;
        }

        .score-low {
            background: #f8d7da;
            color: #721c24;
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: #6c757d;
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <div class="header-content">
                <h1>Smart Pixel Analytics <span style="font-size:8px; color: grey;">open-source</span></h1>
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
                <div class="tab" onclick="openTab('topclicks')">Top Clics</div>
                <div class="tab" onclick="openTab('sessions')">Sessions</div>
                <div class="tab" onclick="openTab('details')">Détails</div>
                <div class="tab" onclick="openTab('engagement')">Engagement</div>
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
                                    <td><?= round(($page['views'] / max($uniqueVisitorsPeriod, 1)) * 100, 1) ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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

                <div class="data-grid">
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
                                        <td><?= round(($source['count'] / max($uniqueVisitorsPeriod, 1)) * 100, 1) ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

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
                                        <td><?= round(($device['count'] / max($uniqueVisitorsPeriod, 1)) * 100, 1) ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ONGLET GÉOGRAPHIE -->
            <div id="geography" class="tab-content">
                <div class="chart-container">
                    <h3 class="chart-title">Carte mondiale des visites</h3>
                    <div id="worldMap" class="map-fallback">
                        <div class="loading">
                            <p>Chargement de la carte...</p>
                            <button onclick="initWorldMap()"
                                style="margin-top: 10px; padding: 10px 20px; background: var(--primary); color: white; border: none; border-radius: 5px; cursor: pointer;">
                                Charger la carte interactive
                            </button>
                        </div>
                    </div>
                </div>

                <div class="chart-container">
                    <h3 class="chart-title">Top pays par visites</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Pays</th>
                                <th>Visites</th>
                                <th>Part du trafic</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($countriesMap, 0, 10) as $country): ?>
                                <tr>
                                    <td><?= htmlspecialchars($country['country']) ?></td>
                                    <td><?= number_format($country['visits']) ?></td>
                                    <td><?= round(($country['visits'] / max($uniqueVisitorsPeriod, 1)) * 100, 1) ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- NOUVEL ONGLET TOP CLICS -->
            <div id="topclicks" class="tab-content">
                <div class="chart-container">
                    <h3 class="chart-title">Éléments les plus cliqués</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Élément</th>
                                <th>Nombre de clics</th>
                                <th>Pourcentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $totalClicks = array_sum(array_column($topClicks, 'click_count'));
                            foreach ($topClicks as $click):
                                $element = $click['element'];
                                if (empty($element))
                                    continue;
                                ?>
                                <tr>
                                    <td><span class="badge badge-primary"><?= htmlspecialchars($element) ?></span></td>
                                    <td><?= number_format($click['click_count']) ?></td>
                                    <td><?= $totalClicks > 0 ? round(($click['click_count'] / $totalClicks) * 100, 1) : 0 ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="heatmap-container">
                    <h3 class="chart-title">Heatmap des interactions</h3>
                    <div class="heatmap-grid">
                        <?php
                        $heatmapZones = [
                            ['zone' => 'Haut de page', 'intensity' => 30],
                            ['zone' => 'Menu navigation', 'intensity' => 80],
                            ['zone' => 'Contenu principal', 'intensity' => 65],
                            ['zone' => 'Sidebar', 'intensity' => 25],
                            ['zone' => 'Footer', 'intensity' => 15],
                            ['zone' => 'Boutons CTA', 'intensity' => 90]
                        ];

                        foreach ($heatmapZones as $zone):
                            ?>
                            <div class="heatmap-item">
                                <strong><?= $zone['zone'] ?></strong>
                                <div class="heatmap-intensity" style="opacity: <?= $zone['intensity'] / 100 ?>"></div>
                                <small><?= $zone['intensity'] ?>% d'interactions</small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
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

            <!-- ONGLET DÉTAILS -->
            <div id="details" class="tab-content">
                <div class="chart-container">
                    <h3 class="chart-title">Détails des visites récentes (50 dernières)</h3>
                    <div class="table-responsive">
                        <table class="data-table" id="detailsTable">
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
                                <?php foreach ($detailedData as $index => $visit):
                                    $visitTime = strtotime($visit['timestamp']);
                                    ?>
                                    <tr data-index="<?= $index ?>">
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
                                    <tr class="click-details" id="click-details-<?= $index ?>">
                                        <td colspan="7">
                                            <h4>Données de clics pour cette session</h4>
                                            <?php
                                            $sessionClicks = array_filter($clickData, function ($click) use ($visit) {
                                                return strpos($click['page_url'], $visit['page_url']) !== false;
                                            });
                                            $sessionClicks = array_slice($sessionClicks, 0, 5);
                                            ?>
                                            <?php if (count($sessionClicks) > 0): ?>
                                                <table class="data-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Élément</th>
                                                            <th>Texte</th>
                                                            <th>Position</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($sessionClicks as $click):
                                                            $data = json_decode($click['click_data'], true);
                                                            if (is_array($data)):
                                                                ?>
                                                                <tr>
                                                                    <td><span
                                                                            class="badge badge-primary"><?= htmlspecialchars($data['element'] ?? 'N/A') ?></span>
                                                                    </td>
                                                                    <td><?= htmlspecialchars(substr($data['text'] ?? '', 0, 30)) . (strlen($data['text'] ?? '') > 30 ? '...' : '') ?>
                                                                    </td>
                                                                    <td><?= ($data['x'] ?? 'N/A') ?>x<?= ($data['y'] ?? 'N/A') ?></td>
                                                                </tr>
                                                            <?php endif; endforeach; ?>
                                                    </tbody>
                                                </table>
                                            <?php else: ?>
                                                <p>Aucun clic enregistré pour cette session.</p>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- NOUVEL ONGLET ENGAGEMENT -->
            <div id="engagement" class="tab-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Taux de rebond</h3>
                        <div class="stat-value">42%</div>
                        <div class="stat-change negative">+3%</div>
                    </div>
                    <div class="stat-card">
                        <h3>Pages/session</h3>
                        <div class="stat-value">3.2</div>
                        <div class="stat-change positive">+0.4</div>
                    </div>
                    <div class="stat-card">
                        <h3>Durée moyenne</h3>
                        <div class="stat-value">2m 15s</div>
                        <div class="stat-change positive">+15s</div>
                    </div>
                    <div class="stat-card">
                        <h3>Score d'engagement</h3>
                        <div class="stat-value">
                            <span class="engagement-score score-high">Élevé</span>
                        </div>
                        <div class="stat-change positive">+8%</div>
                    </div>
                </div>

                <?php if (count($avgTimePerPage) > 0): ?>
                    <div class="chart-container">
                        <h3 class="chart-title">Temps moyen par page</h3>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Page</th>
                                    <th>Temps moyen</th>
                                    <th>Visites</th>
                                    <th>Score engagement</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($avgTimePerPage as $page):
                                    $score = $page['avg_time'] > 120 ? 'score-high' : ($page['avg_time'] > 60 ? 'score-medium' : 'score-low');
                                    $scoreText = $page['avg_time'] > 120 ? 'Élevé' : ($page['avg_time'] > 60 ? 'Moyen' : 'Faible');
                                    ?>
                                    <tr>
                                        <td class="url-truncate"><?= htmlspecialchars($page['page_url']) ?></td>
                                        <td><?= round($page['avg_time']) ?>s</td>
                                        <td><?= number_format($page['visits']) ?></td>
                                        <td><span class="engagement-score <?= $score ?>"><?= $scoreText ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <div class="chart-container">
                    <h3 class="chart-title">Recommandations d'amélioration</h3>
                    <div style="background: #e3f2fd; padding: 20px; border-radius: 8px;">
                        <h4>💡 Suggestions pour augmenter l'engagement</h4>
                        <ul style="margin-top: 15px; line-height: 1.8;">
                            <li><strong>Optimiser les temps de chargement</strong> - 23% des visiteurs quittent avant le
                                chargement complet</li>
                            <li><strong>Améliorer le responsive mobile</strong> - Taux de rebond mobile: 58% vs desktop:
                                32%</li>
                            <li><strong>Ajouter des call-to-actions</strong> - Seulement 12% des visiteurs cliquent sur
                                vos CTA principaux</li>
                            <li><strong>Enrichir le contenu des pages de sortie</strong> - 45% des abandons sur la page
                                /contact</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>
                <button onclick="window.location.href='https://gael-berru.com/'"
                    style="background: none; border: none; color: #6c757d; cursor: pointer; font-size: 0.9rem; padding: 0;">
                    🟪
                </button>
                Smart Pixel Analytics &copy; <?= date('Y') ?> - Dashboard 2.0 - Données mises à jour en temps réel -
                Respect des loi RGPD
            </p>
        </div>
    </footer>

    <script>
        // Gestion du clic sur les lignes du tableau détail
        document.addEventListener('DOMContentLoaded', function () {
            const detailRows = document.querySelectorAll('#detailsTable tbody tr:not(.click-details)');
            detailRows.forEach(row => {
                row.addEventListener('click', function (event) {
                    event.stopPropagation(); // ajout de 'event' car conflit avec openTab, 'this
                    const index = this.getAttribute('data-index');
                    const detailsRow = document.getElementById(`click-details-${index}`);

                    // Fermer tous les autres détails
                    document.querySelectorAll('.click-details').forEach(detail => {
                        if (detail !== detailsRow) {
                            detail.style.display = 'none';
                            const prevRow = detail.previousElementSibling;
                            if (prevRow && prevRow.classList) {
                                prevRow.classList.remove('expanded');
                            }
                        }
                    });

                    // Basculer l'affichage des détails
                    if (detailsRow.style.display === 'block') {
                        detailsRow.style.display = 'none';
                        this.classList.remove('expanded');
                    } else {
                        detailsRow.style.display = 'block';
                        this.classList.add('expanded');
                    }
                });
            });
        });

        // Initialisation de la carte mondiale
        async function initWorldMap() {
            try {
                await loadAmCharts();

                const root = am5.Root.new("worldMap");
                root.setThemes([am5themes_Animated.new(root)]);

                const chart = root.container.children.push(am5map.MapChart.new(root, {
                    panX: "translateX",
                    panY: "translateY",
                    projection: am5map.geoMercator()
                }));

                const polygonSeries = chart.series.push(am5map.MapPolygonSeries.new(root, {
                    geoJSON: am5geodata_worldLow,
                    exclude: ["AQ"]
                }));

                polygonSeries.mapPolygons.template.setAll({
                    tooltipText: "{name}: {value} visites",
                    interactive: true
                });

                // Données pour la carte
                const mapData = <?= json_encode($mapData) ?>;
                polygonSeries.data.setAll(mapData);

                // Configuration des couleurs
                polygonSeries.set("fill", am5.Color.fromString("#4361ee"));
                polygonSeries.mapPolygons.template.set("fill", am5.Color.fromString("#e0e0e0"));

                polygonSeries.mapPolygons.template.adapters.add("fill", function (fill, target) {
                    const dataItem = target.dataItem;
                    if (dataItem) {
                        const value = dataItem.dataContext.value;
                        if (value) {
                            const maxValue = <?= $maxVisits ?>;
                            const intensity = value / maxValue;
                            return am5.Color.brighten(am5.Color.fromString("#4361ee"), intensity * 0.5 - 0.5);
                        }
                    }
                    return am5.Color.fromString("#e0e0e0");
                });

                window.worldMap = chart;

                // Mettre à jour l'interface
                document.querySelector('#worldMap').classList.remove('map-fallback');

            } catch (error) {
                console.error('Erreur chargement carte:', error);
                document.querySelector('#worldMap').innerHTML =
                    '<div class="map-fallback"><p>Erreur de chargement de la carte. Vérifiez votre connexion.</p></div>';
            }
        }

        // Données pour les graphiques
        const dailyStats = <?= json_encode($dailyStats) ?>;
        const sources = <?= json_encode($sources) ?>;
        const devices = <?= json_encode($devices) ?>;
        const browsers = <?= json_encode($browsers) ?>;

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
        if (document.getElementById('trafficChart')) {
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
        }

        // Graphique des sources (aperçu)
        if (document.getElementById('sourcesChart')) {
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
        }

        // Graphique des appareils (aperçu)
        if (document.getElementById('devicesChart')) {
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
        }

        // Graphique des sources (trafic)
        if (document.getElementById('sourcesTrafficChart')) {
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
        }

        // Graphique des navigateurs
        if (document.getElementById('browsersChart')) {
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
        }
    </script>
</body>

</html>