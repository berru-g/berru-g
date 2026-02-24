<?php
// public/pixel.php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: image/gif');
header('Cache-Control: no-store, no-cache');

// 1. Récupérer le code de tracking
$tracking_code = $_GET['t'] ?? '';
if (empty($tracking_code)) exit();

// 2. Trouver le site correspondant
$pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
$stmt = $pdo->prepare("SELECT id, user_id FROM user_sites WHERE tracking_code = ? AND is_active = 1");
$stmt->execute([$tracking_code]);
$site = $stmt->fetch();
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
$geo = json_decode(file_get_contents("http://ip-api.com/json/{$ip}"), true);
if (!$site) exit();

// 3. Préparer les données (AJOUT de country, city, campaign)
$data = [
    'site_id' => $site['id'],
    'user_id' => $site['user_id'],
    'timestamp' => date('Y-m-d H:i:s'),
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'page_url' => $_SERVER['HTTP_REFERER'] ?? 'direct',
    'source' => $_GET['s'] ?? 'direct',
    'session_id' => $_GET['sid'] ?? '',
    'click_data' => $_GET['click'] ?? '',
    'viewport' => $_GET['viewport'] ?? '',
    'country' => $geo['country'] ?? 'Unknown',
    'city' => $geo['city'] ?? 'Unknown',
    'campaign' => $_GET['utm_campaign'] ?? ''  // AJOUTÉ - pour la colonne campaign
];

// 4. Insérer (AJOUT des colonnes country, city, campaign)
$stmt = $pdo->prepare("
    INSERT INTO smart_pixel_tracking 
    (site_id, user_id, timestamp, ip_address, user_agent, page_url, source, 
     session_id, click_data, viewport, country, city, campaign)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute(array_values($data));

// 5. Envoyer le pixel 1x1
echo base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
?>