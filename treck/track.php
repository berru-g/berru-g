<?php
// track.php - Récepteur des données de tracking CORRIGÉ
header('Content-Type: image/gif');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Ignorer les requêtes OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Fichier de log - CHEMIN CORRECT
$logFile = __DIR__ . '/tracking_data.log';

// Collecte toutes les données
$trackingData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'type' => $_GET['type'] ?? 'unknown',
    'visitor_id' => $_GET['visitor_id'] ?? 'unknown',
    'session_id' => $_GET['session_id'] ?? 'unknown',
    
    // Informations de page
    'page_url' => $_GET['page_url'] ?? 'unknown',
    'page_title' => $_GET['page_title'] ?? 'unknown',
    'referrer' => $_GET['referrer'] ?? 'unknown',
    
    // Informations utilisateur
    'user_agent' => $_GET['user_agent'] ?? 'unknown',
    'ip_address' => getClientIP(), // CORRIGÉ : sans $this->
    'language' => $_GET['language'] ?? 'unknown',
    
    // Informations techniques
    'screen_resolution' => $_GET['screen_resolution'] ?? 'unknown',
    'viewport_size' => $_GET['viewport_size'] ?? 'unknown',
    'timezone' => $_GET['timezone'] ?? 'unknown',
    'color_depth' => $_GET['color_depth'] ?? 'unknown',
    'pixel_ratio' => $_GET['pixel_ratio'] ?? 'unknown',
    
    // Données de géolocalisation
    'geo_data' => getGeoLocation(getClientIP()), // CORRIGÉ : sans $this->
    
    // Données de clic
    'click_data' => getClickData(), // CORRIGÉ : sans $this->
    
    // Données de performance
    'performance_data' => getPerformanceData() // CORRIGÉ : sans $this->
];

// Enregistrement dans le fichier log
file_put_contents($logFile, json_encode($trackingData, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);

// Enregistrement séparé pour les analyses
saveToDatabase($trackingData); // CORRIGÉ : sans $this->

// Renvoie une image GIF 1x1 pixel transparente
echo base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');

// Méthodes utilitaires - CORRIGÉ : fonctions simples, pas de $this
function getClientIP() {
    $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function getGeoLocation($ip) {
    if ($ip === 'unknown' || $ip === '127.0.0.1') {
        return ['country' => 'Local', 'city' => 'Local'];
    }
    
    try {
        // Utilisation de ipapi.co (gratuit, 1000 requêtes/mois)
        $response = @file_get_contents("http://ipapi.co/{$ip}/json/");
        if ($response) {
            $geoData = json_decode($response, true);
            return [
                'country' => $geoData['country_name'] ?? 'Unknown',
                'city' => $geoData['city'] ?? 'Unknown',
                'region' => $geoData['region'] ?? 'Unknown',
                'country_code' => $geoData['country_code'] ?? 'Unknown',
                'postal_code' => $geoData['postal'] ?? 'Unknown',
                'latitude' => $geoData['latitude'] ?? 'Unknown',
                'longitude' => $geoData['longitude'] ?? 'Unknown',
                'timezone' => $geoData['timezone'] ?? 'Unknown',
                'currency' => $geoData['currency'] ?? 'Unknown',
                'languages' => $geoData['languages'] ?? 'Unknown'
            ];
        }
    } catch (Exception $e) {
        // Silence is golden
    }
    
    return ['country' => 'Unknown', 'city' => 'Unknown'];
}

function getClickData() {
    return [
        'target_tag' => $_GET['target_tag'] ?? 'none',
        'target_id' => $_GET['target_id'] ?? 'none',
        'target_class' => $_GET['target_class'] ?? 'none',
        'target_text' => $_GET['target_text'] ?? 'none',
        'target_href' => $_GET['target_href'] ?? 'none',
        'click_x' => $_GET['click_x'] ?? 0,
        'click_y' => $_GET['click_y'] ?? 0,
        'page_x' => $_GET['page_x'] ?? 0,
        'page_y' => $_GET['page_y'] ?? 0
    ];
}

function getPerformanceData() {
    return [
        'connection_type' => $_GET['connection_type'] ?? 'unknown',
        'device_memory' => $_GET['device_memory'] ?? 'unknown',
        'hardware_concurrency' => $_GET['hardware_concurrency'] ?? 'unknown',
        'scroll_percentage' => $_GET['scroll_percentage'] ?? 0,
        'time_spent' => $_GET['time_spent_sec'] ?? 0
    ];
}

function saveToDatabase($data) {
    // Ici tu peux adapter pour enregistrer dans une base de données MySQL
    // Exemple basique avec JSON
    $dbFile = __DIR__ . '/tracking_database.json'; // CORRIGÉ : chemin absolu
    $currentData = [];
    
    if (file_exists($dbFile)) {
        $currentData = json_decode(file_get_contents($dbFile), true) ?? [];
    }
    
    $currentData[] = $data;
    file_put_contents($dbFile, json_encode($currentData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}
?>