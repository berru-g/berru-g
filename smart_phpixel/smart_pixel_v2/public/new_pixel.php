<?php
// public/pixel.php - VERSION COMPLÈTE AVEC TOUTES LES FEATURES
error_reporting(0); // Désactive l'affichage des erreurs pour le pixel
ini_set('log_errors', 1);

// Démarrer le buffer pour éviter toute sortie avant les headers
if (ob_get_level() == 0) ob_start();

require_once __DIR__ . '/../includes/config.php';

// Nettoyer tout ce qui a été éventuellement affiché avant
ob_clean();

// Headers pour l'image GIF
header('Content-Type: image/gif');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 1. Récupérer le code de tracking
$tracking_code = $_GET['t'] ?? '';
if (empty($tracking_code)) {
    // Pixel vide si pas de code
    echo base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
    ob_end_flush();
    exit();
}

// 2. Trouver le site correspondant
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Vérifier si le site existe et est actif
    $stmt = $pdo->prepare("SELECT id, user_id FROM user_sites WHERE tracking_code = ? AND is_active = 1");
    $stmt->execute([$tracking_code]);
    $site = $stmt->fetch();
    
    if (!$site) {
        // Site non trouvé ou inactif
        error_log("Pixel: Site non trouvé ou inactif pour le code: $tracking_code");
        echo base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
        ob_end_flush();
        exit();
    }
    
    // 3. PRÉPARER TOUTES LES DONNÉES COMPLÈTES
    $page_url = $_GET['ref'] ?? ($_SERVER['HTTP_REFERER'] ?? 'direct');
    $source = $_GET['s'] ?? (parse_url($page_url, PHP_URL_HOST) ?? 'direct');
    
    // Données principales
    $data = [
        'site_id' => $site['id'],
        'user_id' => $site['user_id'],
        'timestamp' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'page_url' => $page_url,
        'source' => $source,
        'session_id' => $_GET['sid'] ?? '',
        'click_data' => $_GET['click'] ?? '',
        'viewport' => $_GET['vp'] ?? '',
        'timezone' => $_GET['tz'] ?? '0',
        'language' => $_GET['l'] ?? '',
        'event_name' => $_GET['e'] ?? 'pageview',
        'event_data' => $_GET['ed'] ?? '',
        'country' => '', // À calculer si tu veux
        'city' => '', // À calculer si tu veux
        'device_type' => $this->getDeviceType($_SERVER['HTTP_USER_AGENT'] ?? ''), // Fonction à définir
        'browser' => $this->getBrowser($_SERVER['HTTP_USER_AGENT'] ?? ''), // Fonction à définir
        'os' => $this->getOS($_SERVER['HTTP_USER_AGENT'] ?? ''), // Fonction à définir
        'screen_resolution' => $_GET['sr'] ?? '',
        'color_depth' => $_GET['cd'] ?? '',
        'time_on_page' => 0, // À calculer avec JavaScript
        'scroll_depth' => $_GET['sd'] ?? '0',
        'utm_source' => $_GET['utm_source'] ?? '',
        'utm_medium' => $_GET['utm_medium'] ?? '',
        'utm_campaign' => $_GET['utm_campaign'] ?? '',
        'utm_term' => $_GET['utm_term'] ?? '',
        'utm_content' => $_GET['utm_content'] ?? '',
        'is_bounce' => 1, // Par défaut, à mettre à jour avec une seconde visite
        'exit_page' => '',
        'entry_page' => $page_url,
        'previous_page' => $_SERVER['HTTP_REFERER'] ?? '',
        'page_load_time' => $_GET['plt'] ?? '0',
        'dom_load_time' => $_GET['dlt'] ?? '0',
        'network_type' => $_GET['nt'] ?? '',
        'is_mobile' => preg_match('/mobile|android|iphone/i', $_SERVER['HTTP_USER_AGENT'] ?? '') ? 1 : 0,
        'is_tablet' => preg_match('/tablet|ipad/i', $_SERVER['HTTP_USER_AGENT'] ?? '') ? 1 : 0,
        'is_bot' => preg_match('/bot|crawl|spider/i', $_SERVER['HTTP_USER_AGENT'] ?? '') ? 1 : 0,
        'has_js' => 1, // Si le pixel est appelé, JS est actif
        'has_cookie' => isset($_COOKIE['sp_session_id']) ? 1 : 0,
        'connection_type' => $_SERVER['HTTP_CONNECTION'] ?? '',
        'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
        'accept_encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
        'dnt_header' => $_SERVER['HTTP_DNT'] ?? '0',
        'referrer_policy' => $_SERVER['HTTP_REFERRER_POLICY'] ?? '',
        'sec_ch_ua' => $_SERVER['HTTP_SEC_CH_UA'] ?? '',
        'sec_ch_ua_mobile' => $_SERVER['HTTP_SEC_CH_UA_MOBILE'] ?? '',
        'sec_ch_ua_platform' => $_SERVER['HTTP_SEC_CH_UA_PLATFORM'] ?? ''
    ];
    
    // 4. INSERT COMPLET avec toutes les colonnes
    // Note: Tu dois adapter cette requête à ta table exacte
    $stmt = $pdo->prepare("
        INSERT INTO smart_pixel_tracking 
        (site_id, user_id, timestamp, ip_address, user_agent, page_url, source, 
         session_id, click_data, viewport, timezone, language, event_name, event_data,
         country, city, device_type, browser, os, screen_resolution, color_depth,
         time_on_page, scroll_depth, utm_source, utm_medium, utm_campaign, utm_term,
         utm_content, is_bounce, exit_page, entry_page, previous_page, page_load_time,
         dom_load_time, network_type, is_mobile, is_tablet, is_bot, has_js, has_cookie,
         connection_type, accept_language, accept_encoding, dnt_header, referrer_policy,
         sec_ch_ua, sec_ch_ua_mobile, sec_ch_ua_platform)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute(array_values($data));
    
    $lastId = $pdo->lastInsertId();
    error_log("Pixel: Succès - ID: $lastId, Site: {$site['id']}, Code: $tracking_code");
    
} catch (PDOException $e) {
    // Erreur DB silencieuse
    error_log("Pixel DB Error: " . $e->getMessage());
} catch (Exception $e) {
    // Erreur générale
    error_log("Pixel Error: " . $e->getMessage());
}

// 5. Toujours retourner un pixel GIF 1x1 valide
echo base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');

// Nettoyer et envoyer
ob_end_flush();

// Fonctions utilitaires (à mettre dans une classe séparée normalement)
function getDeviceType($user_agent) {
    if (preg_match('/mobile|android|iphone/i', $user_agent)) return 'mobile';
    if (preg_match('/tablet|ipad/i', $user_agent)) return 'tablet';
    return 'desktop';
}

function getBrowser($user_agent) {
    if (preg_match('/chrome/i', $user_agent)) return 'Chrome';
    if (preg_match('/firefox/i', $user_agent)) return 'Firefox';
    if (preg_match('/safari/i', $user_agent)) return 'Safari';
    if (preg_match('/edge/i', $user_agent)) return 'Edge';
    if (preg_match('/opera/i', $user_agent)) return 'Opera';
    return 'Other';
}

function getOS($user_agent) {
    if (preg_match('/windows/i', $user_agent)) return 'Windows';
    if (preg_match('/mac os x/i', $user_agent)) return 'macOS';
    if (preg_match('/linux/i', $user_agent)) return 'Linux';
    if (preg_match('/android/i', $user_agent)) return 'Android';
    if (preg_match('/iphone|ipad|ipod/i', $user_agent)) return 'iOS';
    return 'Unknown';
}
?>