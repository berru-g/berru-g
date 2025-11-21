<?php
// dashtrack.php - Tableau de bord simple CORRIGÉ
header('Content-Type: text/html; charset=utf-8');

// Lire les données - CHEMIN CORRECT
$logs = [];
if (file_exists(__DIR__ . '/tracking_data.log')) {
    $lines = file(__DIR__ . '/tracking_data.log', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $logs[] = json_decode($line, true);
    }
}

// Statistiques simples
$pageviews = array_filter($logs, fn($log) => ($log['type'] ?? '') === 'pageview');
$clicks = array_filter($logs, fn($log) => ($log['type'] ?? '') === 'click');
$uniqueVisitors = array_unique(array_column($logs, 'visitor_id'));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Tracking</title>
    <link rel="stylesheet" href="../board/assets/css/styles.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .stat { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Tableau de bord Tracking</h1>
    
    <div class="stat">
        <h3>Statistiques globales</h3>
        <p>Pageviews: <?php echo count($pageviews); ?></p>
        <p>Clics: <?php echo count($clicks); ?></p>
        <p>Visiteurs uniques: <?php echo count($uniqueVisitors); ?></p>
    </div>

    <h3>Géolocalisation des visiteurs</h3>
    <table>
        <tr><th>Pays</th><th>Ville</th><th>Pages visitées</th></tr>
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
            echo "<tr><td>$country</td><td>$city</td><td>$uniquePages pages</td></tr>";
        }
        ?>
    </table>

    <h3>Clics les plus populaires</h3>
    <table>
        <tr><th>Élément</th><th>Texte</th><th>Nombre de clics</th></tr>
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
            echo "<tr><td>$target</td><td>$text</td><td>$count</td></tr>";
        }
        ?>
    </table>

        <!-- Section Pixel Tracker - À AJOUTER -->
    <div class="stat">
        <h3>📊 Pixel Tracker - Statistiques</h3>
        <?php
        // Compter les pixels
        $pixelTracks = array_filter($logs, fn($log) => ($log['type'] ?? '') === 'pixel_track');
        $pixelSources = array_count_values(array_column($pixelTracks, 'pixel_source'));
        $pixelCampaigns = array_count_values(array_column($pixelTracks, 'campaign'));
        ?>
        <p>Pixels chargés: <?php echo count($pixelTracks); ?></p>
        <p>Sources différentes: <?php echo count($pixelSources); ?></p>
        <p>Campagnes: <?php echo count($pixelCampaigns); ?></p>
    </div>

    <h3>🌍 Sources des Pixels</h3>
    <table>
        <tr><th>Source</th><th>Nombre de vues</th><th>Dernière vue</th></tr>
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
            echo "<tr><td>$source</td><td>{$data['count']}</td><td>{$data['last_seen']}</td></tr>";
        }
        ?>
    </table>

    <!--<h3>Dernières activités SmartPixel</h3>
    <table>
        <tr><th>Heure</th><th>Source</th><th>Campagne</th><th>Pays</th><th>Referrer</th></tr>
        <?php
        $recentPixels = array_slice(array_reverse($pixelTracks), 0, 15);
        foreach ($recentPixels as $log) {
            $time = $log['timestamp'];
            $source = $log['pixel_source'] ?? 'unknown';
            $campaign = $log['campaign'] ?? 'none';
            $country = $log['geo_data']['country'] ?? 'Inconnu';
            $referrer = substr($log['referrer'] ?? 'direct', 0, 30);
            echo "<tr><td>$time</td><td>$source</td><td>$campaign</td><td>$country</td><td>$referrer</td></tr>";
        }
        ?>
    </table>-->

    <h3>Dernières activités</h3>
    <table>
        <tr><th>Heure</th><th>Type</th><th>Page</th><th>Pays</th></tr>
        <?php
        $recentLogs = array_slice(array_reverse($logs), 0, 20);
        foreach ($recentLogs as $log) {
            $time = $log['timestamp'];
            $type = $log['type'];
            $page = substr($log['page_url'] ?? 'N/A', 0, 30);
            $country = $log['geo_data']['country'] ?? 'Inconnu';
            echo "<tr><td>$time</td><td>$type</td><td>$page</td><td>$country</td></tr>";
        }
        ?>
    </table>
</body>
</html>