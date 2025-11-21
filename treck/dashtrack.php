<?php
// dashtrack.php - Tableau de bord stylisé
header('Content-Type: text/html; charset=utf-8');

// Lire les données
$logs = [];
if (file_exists(__DIR__ . '/tracking_data.log')) {
    $lines = file(__DIR__ . '/tracking_data.log', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $logs[] = json_decode($line, true);
    }
}

// Statistiques
$pageviews = array_filter($logs, fn($log) => ($log['type'] ?? '') === 'pageview');
$clicks = array_filter($logs, fn($log) => ($log['type'] ?? '') === 'click');
$pixelTracks = array_filter($logs, fn($log) => ($log['type'] ?? '') === 'pixel_track');
$uniqueVisitors = array_unique(array_column($logs, 'visitor_id'));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard</title>
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --dark: #2b2d42;
            --light: #f8f9fa;
            --gray: #6c757d;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .dashboard {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            text-align: center;
        }
        
        .header h1 {
            color: var(--dark);
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            color: var(--gray);
            font-size: 1.1em;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.pageviews { border-left: 4px solid var(--primary); }
        .stat-card.clicks { border-left: 4px solid var(--success); }
        .stat-card.visitors { border-left: 4px solid var(--warning); }
        .stat-card.pixels { border-left: 4px solid var(--danger); }
        
        .stat-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 2.2em;
            font-weight: bold;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        
        .section-title {
            color: var(--dark);
            font-size: 1.4em;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        th {
            background: var(--light);
            color: var(--dark);
            font-weight: 600;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            color: var(--gray);
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
        }
        
        .badge-primary { background: #e3f2fd; color: var(--primary); }
        .badge-success { background: #e8f5e8; color: #2e7d32; }
        .badge-warning { background: #fff3e0; color: #ef6c00; }
        .badge-danger { background: #ffebee; color: var(--danger); }
        
        .country-flag {
            font-size: 1.2em;
            margin-right: 8px;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            th, td {
                padding: 10px 8px;
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="header">
            <h1>📊 Analytics Dashboard</h1>
            <p>Surveillance en temps réel de votre trafic</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card pageviews">
                <div class="stat-icon">👁️</div>
                <div class="stat-number"><?php echo count($pageviews); ?></div>
                <div class="stat-label">Pages Vues</div>
            </div>
            
            <div class="stat-card clicks">
                <div class="stat-icon">🖱️</div>
                <div class="stat-number"><?php echo count($clicks); ?></div>
                <div class="stat-label">Clics</div>
            </div>
            
            <div class="stat-card visitors">
                <div class="stat-icon">👥</div>
                <div class="stat-number"><?php echo count($uniqueVisitors); ?></div>
                <div class="stat-label">Visiteurs Uniques</div>
            </div>
            
            <div class="stat-card pixels">
                <div class="stat-icon">📧</div>
                <div class="stat-number"><?php echo count($pixelTracks); ?></div>
                <div class="stat-label">Pixels Trackés</div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">
                🌍 Géolocalisation des Visiteurs
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Pays</th>
                        <th>Ville</th>
                        <th>Pages Visitées</th>
                        <th>Visites</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $geoStats = [];
                    foreach ($pageviews as $log) {
                        $country = $log['geo_data']['country'] ?? 'Inconnu';
                        $city = $log['geo_data']['city'] ?? 'Inconnu';
                        $key = $country . '|' . $city;
                        
                        if (!isset($geoStats[$key])) {
                            $geoStats[$key] = ['count' => 0, 'pages' => []];
                        }
                        $geoStats[$key]['count']++;
                        $geoStats[$key]['pages'][] = $log['page_url'];
                    }

                    foreach ($geoStats as $key => $data) {
                        list($country, $city) = explode('|', $key);
                        $uniquePages = count(array_unique($data['pages']));
                        echo "<tr>
                                <td><span class='country-flag'>🇫🇷</span>$country</td>
                                <td>$city</td>
                                <td><span class='badge badge-primary'>$uniquePages pages</span></td>
                                <td><strong>{$data['count']}</strong></td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">
                🖱️ Clics les Plus Populaires
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Élément</th>
                        <th>Texte</th>
                        <th>Nombre de Clics</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $clickStats = [];
                    foreach ($clicks as $log) {
                        $target = $log['click_data']['target_tag'] ?? 'unknown';
                        $text = substr($log['click_data']['target_text'] ?? 'No text', 0, 50);
                        $key = $target . '|' . $text;
                        
                        $clickStats[$key] = ($clickStats[$key] ?? 0) + 1;
                    }

                    arsort($clickStats);
                    foreach (array_slice($clickStats, 0, 10) as $key => $count) {
                        list($target, $text) = explode('|', $key);
                        $badgeClass = $target === 'A' ? 'badge-success' : 'badge-primary';
                        echo "<tr>
                                <td><span class='badge $badgeClass'>$target</span></td>
                                <td>$text</td>
                                <td><strong>$count</strong></td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">
                📧 Performance des Pixels
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Source</th>
                        <th>Vues</th>
                        <th>Dernière Activité</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sourceStats = [];
                    foreach ($pixelTracks as $log) {
                        $source = $log['pixel_source'] ?? 'unknown';
                        if (!isset($sourceStats[$source])) {
                            $sourceStats[$source] = ['count' => 0, 'last_seen' => ''];
                        }
                        $sourceStats[$source]['count']++;
                        if ($log['timestamp'] > $sourceStats[$source]['last_seen']) {
                            $sourceStats[$source]['last_seen'] = $log['timestamp'];
                        }
                    }
                    
                    arsort($sourceStats);
                    foreach ($sourceStats as $source => $data) {
                        echo "<tr>
                                <td><strong>$source</strong></td>
                                <td><span class='badge badge-warning'>{$data['count']}</span></td>
                                <td>{$data['last_seen']}</td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">
                ⚡ Activités Récentes
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Heure</th>
                        <th>Type</th>
                        <th>Page/Source</th>
                        <th>Pays</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recentLogs = array_slice(array_reverse($logs), 0, 15);
                    foreach ($recentLogs as $log) {
                        $time = date('H:i:s', strtotime($log['timestamp']));
                        $type = $log['type'];
                        $page = substr($log['page_url'] ?? $log['pixel_source'] ?? 'N/A', 0, 30);
                        $country = $log['geo_data']['country'] ?? 'Inconnu';
                        
                        $badgeClass = match($type) {
                            'pageview' => 'badge-primary',
                            'click' => 'badge-success', 
                            'pixel_track' => 'badge-danger',
                            default => 'badge-warning'
                        };
                        
                        echo "<tr>
                                <td><small>$time</small></td>
                                <td><span class='badge $badgeClass'>$type</span></td>
                                <td>$page</td>
                                <td>$country</td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>