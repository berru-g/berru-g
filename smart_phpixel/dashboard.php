<?php
require_once '../galerie/figueconfite.php';

$pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", DB_USER, DB_PASS);

// STATS
$totalViews = $pdo->query("SELECT COUNT(*) FROM ".DB_TABLE)->fetchColumn();
$uniqueVisitors = $pdo->query("SELECT COUNT(DISTINCT ip_address) FROM ".DB_TABLE)->fetchColumn();
$sources = $pdo->query("SELECT source, COUNT(*) as count FROM ".DB_TABLE." GROUP BY source ORDER BY count DESC")->fetchAll();

// TOP PAGES
$topPages = $pdo->query("
    SELECT page_url, COUNT(*) as views 
    FROM ".DB_TABLE." 
    WHERE page_url != 'direct' 
    GROUP BY page_url 
    ORDER BY views DESC 
    LIMIT 10
")->fetchAll();

// GÉOLOCALISATION
$countries = $pdo->query("
    SELECT country, COUNT(*) as visits 
    FROM ".DB_TABLE." 
    GROUP BY country 
    ORDER BY visits DESC 
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Smart Pixel Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .chart-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin: 20px 0; }
    </style>
</head>
<body>
    <h1>Smart Pixel Analytics</h1>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Views</h3>
            <div style="font-size: 2em; font-weight: bold;"><?= number_format($totalViews) ?></div>
        </div>
        <div class="stat-card">
            <h3>Unique Visitors</h3>
            <div style="font-size: 2em; font-weight: bold;"><?= number_format($uniqueVisitors) ?></div>
        </div>
        <div class="stat-card">
            <h3>Sources</h3>
            <div style="font-size: 2em; font-weight: bold;"><?= count($sources) ?></div>
        </div>
    </div>

    <div class="chart-container">
        <h3>Top Countries</h3>
        <canvas id="countriesChart" width="400" height="200"></canvas>
    </div>

    <div class="chart-container">
        <h3>Traffic Sources</h3>
        <canvas id="sourcesChart" width="400" height="200"></canvas>
    </div>

    <script>
        // Chart Pays
        new Chart(document.getElementById('countriesChart'), {
            type: 'bar',
            data: {
                labels: [<?= implode(',', array_map(fn($c) => "'".$c['country']."'", $countries)) ?>],
                datasets: [{
                    label: 'Visits',
                    data: [<?= implode(',', array_column($countries, 'visits')) ?>],
                    backgroundColor: '#4361ee'
                }]
            }
        });

        // Chart Sources
        new Chart(document.getElementById('sourcesChart'), {
            type: 'pie',
            data: {
                labels: [<?= implode(',', array_map(fn($s) => "'".$s['source']."'", $sources)) ?>],
                datasets: [{
                    data: [<?= implode(',', array_column($sources, 'count')) ?>],
                    backgroundColor: ['#4361ee', '#4cc9f0', '#f72585', '#7209b7']
                }]
            }
        });
    </script>
</body>
</html>