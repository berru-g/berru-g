<?php
// dashboard.php - DASHBOARD AMÉLIORÉ AVEC ONGLET DÉTAIL
require_once 'config.php';
//require_once '../board/includes/header.php'; // a integrer dans mon /board/
//include __DIR__ . '/includes/header.php';
$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);

/* Vérification de sécurité basique
if (!isset($_SESSION['admin_logged_in'])) {
    // Redirection vers une page de login si nécessaire
    // header('Location: login.php');
    // exit();
}*/

// Filtre de période (par défaut: 1 an)
$period = isset($_GET['period']) ? $_GET['period'] : 365;
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
    LIMIT 250
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
        /* ===== RESET & VARIABLES ===== */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

:root {
  /* Couleurs Light Mode */
  --primary-color: #9d86ff;
  --primary-light: rgba(138, 111, 248, 0.1);
  --bg-color: #ffffff;
  --sidebar-bg: #ffffff;
  --text-color: #333333;
  --text-secondary: #666666;
  --border-color: rgba(0, 0, 0, 0.1);
  --hover-bg: rgba(0, 0, 0, 0.05);
  --shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  --search-bg: #f8f8f8;
  --positive: #10b981;
  --negative: #ef4444;
}

/* Dark Mode Variables */
@media (prefers-color-scheme: dark) {
  :root {
    --primary-color: #9d86ff;
    --primary-light: rgba(157, 134, 255, 0.15);
    --bg-color: #151515;
    --sidebar-bg: #1d1d1e;
    --text-color: #f0f0f0;
    --text-secondary: #b0b0b0;
    --border-color: rgba(255, 255, 255, 0.15);
    --hover-bg: rgba(255, 255, 255, 0.08);
    --shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
    --search-bg: #1e1e1e;
    --positive: #34d399;
    --negative: #f87171;
  }
}

/* ===== STRUCTURE DE BASE ===== */
body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  background: var(--bg-color);
  color: var(--text-color);
  transition: background-color 0.3s ease, color 0.3s ease;
  min-height: 100vh;
}

/* ===== HEADER ===== */
header {
  background: var(--sidebar-bg);
  border-bottom: 1px solid var(--border-color);
  padding: 1.5rem 0;
  margin-bottom: 2rem;
  box-shadow: var(--shadow);
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1.5rem;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-content h1 {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-color);
}

.period-filter {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.period-filter select {
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--border-color);
  border-radius: 8px;
  background: var(--search-bg);
  color: var(--text-color);
  font-family: inherit;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.period-filter select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px var(--primary-light);
}

/* ===== TABS ===== */
.dashboard-tabs {
  background: var(--sidebar-bg);
  border-radius: 12px;
  border: 1px solid var(--border-color);
  overflow: hidden;
  box-shadow: var(--shadow);
}

.tabs {
  display: flex;
  border-bottom: 1px solid var(--border-color);
  overflow-x: auto;
  background: var(--sidebar-bg);
}

.tab {
  padding: 1rem 1.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
  border-bottom: 2px solid transparent;
  white-space: nowrap;
}

.tab:hover {
  color: var(--text-color);
  background: var(--hover-bg);
}

.tab.active {
  color: var(--primary-color);
  border-bottom-color: var(--primary-color);
  background: var(--primary-light);
}

/* ===== CONTENU DES ONGLETS ===== */
.tab-content {
  display: none;
  padding: 1.5rem;
}

.tab-content.active {
  display: block;
}

/* ===== STATS GRID ===== */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: var(--bg-color);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 1.5rem;
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow);
}

.stat-card h3 {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-secondary);
  margin-bottom: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.stat-value {
  font-size: 2rem;
  font-weight: 600;
  color: var(--text-color);
  margin-bottom: 0.5rem;
}

.stat-change {
  font-size: 0.875rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.stat-change.positive {
  color: var(--positive);
}

.stat-change.negative {
  color: var(--negative);
}

/* ===== GRID DE DONNÉES ===== */
.data-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.data-grid.compact {
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
}

/* ===== CONTAINERS DE GRAPHIQUES ===== */
.chart-container {
  background: var(--bg-color);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  transition: all 0.3s ease;
}

.chart-container:hover {
  box-shadow: var(--shadow);
}

.chart-container.small {
  padding: 1rem;
}

.chart-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-color);
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--border-color);
}

/* ===== TABLES DE DONNÉES ===== */
.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.data-table thead {
  border-bottom: 2px solid var(--border-color);
}

.data-table th {
  padding: 0.75rem 1rem;
  text-align: left;
  font-weight: 600;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-size: 0.75rem;
}

.data-table tbody tr {
  border-bottom: 1px solid var(--border-color);
  transition: background-color 0.2s ease;
}

.data-table tbody tr:hover {
  background-color: var(--hover-bg);
}

.data-table td {
  padding: 1rem;
  color: var(--text-color);
}

/* ===== UTILITAIRES DE TABLE ===== */
.url-truncate {
  max-width: 300px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  cursor: help;
}

.ip-address {
  font-family: 'Monaco', 'Consolas', monospace;
  font-size: 0.8125rem;
  color: var(--text-secondary);
}

.table-responsive {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

/* ===== BADGES ===== */
.badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 500;
  border-radius: 6px;
  line-height: 1;
}

.badge-primary {
  background: var(--primary-light);
  color: var(--primary-color);
}

/* ===== FOOTER ===== */
footer {
  margin-top: 3rem;
  padding: 1.5rem 0;
  border-top: 1px solid var(--border-color);
  text-align: center;
  color: var(--text-secondary);
  font-size: 0.875rem;
}

footer a {
  color: var(--primary-color);
  text-decoration: none;
  transition: opacity 0.2s ease;
}

footer a:hover {
  opacity: 0.8;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
  .container {
    padding: 0 1rem;
  }
  
  .header-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .period-filter {
    align-self: flex-end;
  }
  
  .tabs {
    flex-wrap: nowrap;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  
  .tab {
    padding: 0.875rem 1rem;
    font-size: 0.8125rem;
  }
  
  .tab-content {
    padding: 1rem;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .data-grid {
    grid-template-columns: 1fr;
  }
  
  .chart-container {
    padding: 1rem;
  }
  
  .data-table {
    font-size: 0.8125rem;
  }
  
  .data-table th,
  .data-table td {
    padding: 0.75rem 0.5rem;
  }
  
  .url-truncate {
    max-width: 200px;
  }
}

@media (max-width: 480px) {
  .header-content h1 {
    font-size: 1.25rem;
  }
  
  .stat-value {
    font-size: 1.75rem;
  }
  
  .url-truncate {
    max-width: 150px;
  }
}

/* ===== ANIMATIONS ===== */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.tab-content.active {
  animation: fadeIn 0.3s ease-out;
}

/* ===== OPTIMISATION POUR LES GRAPHIQUES ===== */
canvas {
  max-height: 400px;
}

/* ===== AMÉLIORATION DU SCROLL ===== */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  background: var(--border-color);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: var(--text-secondary);
}

/* ===== COULEURS POUR LES GRAPHIQUES ===== */
:root {
  --chart-color-1: #9d86ff;
  --chart-color-2: #ff6b8b;
  --chart-color-3: #4ecdc4;
  --chart-color-4: #ffe66d;
  --chart-color-5: #7ae582;
}
    </style>
</head>

<body>
    <header>
        <div class="container">
            <div class="header-content">
                <h1>Le Smart Pixel de LibreAnalytics <a href="./auto-heberge/" style="text-decoration:none;">doc</a></h1>
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
                    <h3 class="chart-title">Détails des visites récentes (250 dernières)</h3>
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
            <p><a href="https://gael-berru.com/">Dev by berru-g 2024</a>/ Smart Pixel Analytics &copy; <?= date('Y') ?> - Données mises à
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
                        borderColor: '#9d86ff',
                        backgroundColor: 'rgba(67, 97, 238, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Visiteurs uniques',
                        data: dailyStats.map(stat => stat.unique_visitors),
                        borderColor: '#4ecdc4',
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
                        '#9d86ff', '#4ecdc4', '#ff6b8b', '#ffe66d', '#7ae582'
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
                    backgroundColor: ['#9d86ff', '#4ecdc4', '#ff6b8b']
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
                    backgroundColor: '#7ae582'
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
                        '#9d86ff', '#4ecdc4', '#ff6b8b', '#ffe66d', '#7ae582'
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
                    backgroundColor: '#7ae582'
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
                    backgroundColor: '#9d86ff'
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
                    backgroundColor: ['#9d86ff', '#4ecdc4', '#ff6b8b']
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
                        '#9d86ff', '#4ecdc4', '#ff6b8b', '#ffe66d', '#7ae582'
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