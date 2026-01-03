<?php
// ====================================================
// FICHIER : block-ip.php
// BLOQUE MANUELLEMENT UNE IP
// ====================================================

session_start();

// Vérifier les permissions (ajuste selon ton système d'authentification)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    die('Accès interdit');
}

// Vérifier la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Méthode non autorisée');
}

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);
$ip = $input['ip'] ?? '';

if (empty($ip)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'IP manquante']);
    exit();
}

// Valider l'IP
if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'IP invalide']);
    exit();
}

// Chemin des fichiers
$blockedFile = __DIR__ . '/blocked_ips.json';
$ipFile = __DIR__ . '/ip_data/' . md5($ip) . '.json';

// 1. Ajouter à la liste des IPs bloquées manuellement
$blockedIPs = [];
if (file_exists($blockedFile)) {
    $blockedIPs = json_decode(file_get_contents($blockedFile), true) ?: [];
}

// Vérifier si déjà bloquée
if (in_array($ip, $blockedIPs)) {
    echo json_encode(['success' => true, 'message' => 'IP déjà bloquée']);
    exit();
}

// Ajouter l'IP
$blockedIPs[] = $ip;
file_put_contents($blockedFile, json_encode($blockedIPs, JSON_PRETTY_PRINT));

// 2. Marquer comme bloquée dans le fichier IP
if (file_exists($ipFile)) {
    $ipData = json_decode(file_get_contents($ipFile), true) ?: [];
    $ipData['blocked'] = true;
    $ipData['blocked_at'] = date('Y-m-d H:i:s');
    $ipData['blocked_by'] = $_SESSION['admin_username'] ?? 'admin';
    $ipData['block_reason'] = 'Bloqué manuellement depuis le dashboard';
    file_put_contents($ipFile, json_encode($ipData, JSON_PRETTY_PRINT));
} else {
    // Créer un nouveau fichier IP
    $ipData = [
        'ip' => $ip,
        'attempts' => 999,
        'last_attempt' => time(),
        'last_success' => null,
        'blocked' => true,
        'blocked_at' => date('Y-m-d H:i:s'),
        'blocked_by' => $_SESSION['admin_username'] ?? 'admin',
        'block_reason' => 'Bloqué manuellement depuis le dashboard',
        'created_at' => date('Y-m-d H:i:s')
    ];
    file_put_contents($ipFile, json_encode($ipData, JSON_PRETTY_PRINT));
}

// 3. Ajouter au fichier .htaccess (optionnel mais puissant)
$htaccessPath = __DIR__ . '/../.htaccess';
if (file_exists($htaccessPath)) {
    $htaccess = file_get_contents($htaccessPath);
    
    // Vérifier si l'IP est déjà dans le .htaccess
    if (strpos($htaccess, "Deny from $ip") === false) {
        // Ajouter la règle de blocage
        $rule = "\n# IP bloquée manuellement: $ip\nDeny from $ip\n";
        
        // Insérer après RewriteEngine On ou à la fin
        if (strpos($htaccess, 'RewriteEngine On') !== false) {
            $htaccess = str_replace(
                'RewriteEngine On',
                "RewriteEngine On\n# IP bloquée manuellement: $ip\nDeny from $ip",
                $htaccess
            );
        } else {
            $htaccess .= $rule;
        }
        
        file_put_contents($htaccessPath, $htaccess);
    }
}

// 4. Logger l'action
$logEntry = date('[Y-m-d H:i:s]') . " | IP_BLOCKED_MANUALLY | IP: $ip | By: " . ($_SESSION['admin_username'] ?? 'admin') . "\n";
file_put_contents(__DIR__ . '/admin_actions.log', $logEntry, FILE_APPEND);

// Réponse succès
echo json_encode([
    'success' => true,
    'message' => "IP $ip bloquée avec succès",
    'actions' => [
        'added_to_blocklist' => true,
        'ip_file_updated' => true,
        'htaccess_updated' => file_exists($htaccessPath)
    ]
]);
?>