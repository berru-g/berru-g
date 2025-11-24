<?php
// image-tracker.php - IMAGE PNG AVEC TRACKER INTÉGRÉ
// https://gael-berru.com/smart_phpixel/image-tracker.php?source=social&campaign=partage
// 
require_once 'config.php';

header('Content-Type: image/png');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupération des données
    $requestData = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
    
    // GÉOLOCALISATION
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    $geo = json_decode(file_get_contents("http://ip-api.com/json/{$ip}"), true);
    
    // DONNÉES DE TRACKING
    $data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip_address' => $ip,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'page_url' => $_SERVER['HTTP_REFERER'] ?? ($requestData['page_url'] ?? 'direct'),
        'source' => $requestData['source'] ?? 'direct',
        'campaign' => $requestData['campaign'] ?? '',
        'country' => $geo['country'] ?? 'Unknown',
        'city' => $geo['city'] ?? 'Unknown',
        'image_type' => $requestData['image_type'] ?? 'tracking',
        'shared_by' => $requestData['shared_by'] ?? '',
        'click_data' => $requestData['click_data'] ?? '',
        'viewport' => $requestData['viewport'] ?? '',
        'session_id' => $requestData['session_id'] ?? ''
    ];
    
    // INSERTION EN BASE
    $stmt = $pdo->prepare("
        INSERT INTO ".DB_TABLE." 
        (timestamp, ip_address, user_agent, page_url, source, campaign, country, city, click_data, viewport, session_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute(array_values($data));
    
} catch(Exception $e) {
    error_log("Image Tracker Error: " . $e->getMessage());
}

// CRÉATION D'UNE IMAGE PNG DYNAMIQUE
$width = 800;
$height = 600;
$image = imagecreate($width, $height);

// Couleurs
$white = imagecolorallocate($image, 255, 255, 255);
$black = imagecolorallocate($image, 0, 0, 0);
$blue = imagecolorallocate($image, 67, 97, 238);

// Fond blanc
imagefill($image, 0, 0, $white);

// Texte de l'image
$texts = [
    "Smart Pixel Analytics",
    "Image de Tracking",
    "Partagée par: " . ($_GET['shared_by'] ?? 'Utilisateur'),
    "Campagne: " . ($_GET['campaign'] ?? 'Générale'),
    date('d/m/Y H:i:s')
];

$y = 100;
foreach ($texts as $text) {
    imagestring($image, 5, 50, $y, $text, $black);
    $y += 40;
}

// QR Code simple (simulé)
imagestring($image, 3, 50, 300, "🔍 QR Code de Tracking", $blue);
imagestring($image, 2, 50, 330, "Scannez-moi pour voir les stats!", $black);

// Génération de l'image
imagepng($image);
imagedestroy($image);
?>