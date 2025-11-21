<?php
// Comment utiliser pixel :
// <img src="https://gael-berru.com/treck/pixel.php" width="1" height="1" style="display:none;"> 
// source personnalisée :
// <img src="https://gael-berru.com/treck/pixel.php?source=newsletter" width="1" height="1" style="display:none;">
// campagnes personnalisées :
//<img src="https://gael-berru.com/treck/pixel.php?source=email&campaign=promo_janvier&medium=email&content=banner_top" width="1" height="1" style="display:none;">
header('Content-Type: image/gif');
header('Access-Control-Allow-Origin: *');

// Fichier de log pour le pixel
$pixelLogFile = __DIR__ . '/pixel_tracking.log';

// Collecte des données avancées
$trackingData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'type' => 'pixel_track',
    
    // Source du pixel
    'pixel_source' => $_GET['source'] ?? 'direct',
    'pixel_id' => $_GET['id'] ?? 'default',
    
    // Informations de référence
    'referrer' => $_SERVER['HTTP_REFERER'] ?? 'direct',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    
    // Informations techniques
    'ip_address' => getClientIP(),
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'http_host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
    
    // Données de géolocalisation
    'geo_data' => getGeoLocation(getClientIP()),
    
    // Paramètres personnalisés
    'campaign' => $_GET['campaign'] ?? 'none',
    'medium' => $_GET['medium'] ?? 'none',
    'content' => $_GET['content'] ?? 'none'
];

// Enregistrement dans le fichier log
file_put_contents($pixelLogFile, json_encode($trackingData, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);

// Enregistrement également dans le fichier principal pour le dashboard
file_put_contents(__DIR__ . '/tracking_data.log', json_encode($trackingData, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);

// Renvoie une image GIF 1x1 pixel transparente
echo base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');

// Fonctions utilitaires (les mêmes que dans track.php)
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
        $response = @file_get_contents("http://ipapi.co/{$ip}/json/");
        if ($response) {
            $geoData = json_decode($response, true);
            return [
                'country' => $geoData['country_name'] ?? 'Unknown',
                'city' => $geoData['city'] ?? 'Unknown',
                'region' => $geoData['region'] ?? 'Unknown',
                'country_code' => $geoData['country_code'] ?? 'Unknown'
            ];
        }
    } catch (Exception $e) {
        // Silence is golden
    }
    
    return ['country' => 'Unknown', 'city' => 'Unknown'];
}
?>