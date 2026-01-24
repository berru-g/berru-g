<?php
// pixel.php - PIXEL INTELLIGENT
require_once 'config.php'; // Chemin à adapter

header('Content-Type: image/gif');
header('Access-Control-Allow-Origin: *');

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // GÉOLOCALISATION
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    $geo = json_decode(file_get_contents("http://ip-api.com/json/{$ip}"), true);
    
    // DONNÉES
    $data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip_address' => $ip,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'page_url' => $_SERVER['HTTP_REFERER'] ?? 'direct',
        'source' => $_GET['source'] ?? 'direct',
        'campaign' => $_GET['campaign'] ?? '',
        'country' => $geo['country'] ?? 'Unknown',
        'city' => $geo['city'] ?? 'Unknown',
        'click_data' => $_GET['click_data'] ?? '',
        'viewport' => $_GET['viewport'] ?? '',
        'session_id' => $_GET['session_id'] ?? ''
    ];
    
    // INSERTION
    $stmt = $pdo->prepare("
        INSERT INTO ".DB_TABLE." 
        (timestamp, ip_address, user_agent, page_url, source, campaign, country, city, click_data, viewport, session_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute(array_values($data));
    
} catch(Exception $e) {
    // Log erreur silencieusement
}

// PIXEL TRANSPARENT
echo base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
?>