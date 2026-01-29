<?php
// public/webhook.php
require_once '../includes/config.php';

// Vérifier la signature du webhook (optionnel mais recommandé)
$lemonSecret = 'YOUR_WEBHOOK_SECRET'; // À mettre dans config.php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';

// Valider la signature
if (!hash_equals(hash_hmac('sha256', $payload, $lemonSecret), $signature)) {
    http_response_code(401);
    exit('Signature invalide');
} 

$data = json_decode($payload, true);
$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);

// Journal des webhooks (pour debug)
file_put_contents('../storage/logs/webhook.log', date('Y-m-d H:i:s') . ' - ' . $payload . PHP_EOL, FILE_APPEND);

// Traiter l'événement
$eventName = $data['meta']['event_name'] ?? '';
$customData = $data['data']['attributes']['user_email'] ?? $data['data']['attributes']['custom'] ?? [];

// Extraire les données custom (celles qu'on a passées dans le checkout)
if (isset($customData['custom'])) {
    $userId = $customData['custom']['user_id'] ?? null;
    $plan = $customData['custom']['plan'] ?? null;
} else {
    // Fallback : essayer de récupérer depuis l'email
    $userEmail = $data['data']['attributes']['user_email'] ?? '';
    if ($userEmail) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$userEmail]);
        $user = $stmt->fetch();
        $userId = $user['id'] ?? null;
    }
}

if (!$userId) {
    http_response_code(400);
    exit('User ID non trouvé');
}

switch ($eventName) {
    case 'subscription_created':
    case 'subscription_updated':
    case 'subscription_resumed':
        $status = $data['data']['attributes']['status'] ?? '';
        $variantId = $data['data']['attributes']['variant_id'] ?? '';
        
        // Déterminer le plan depuis le variant_id
        $plan = ($variantId == 'YOUR_PRO_VARIANT_ID') ? 'pro' : 
                ($variantId == 'YOUR_BUSINESS_VARIANT_ID' ? 'business' : 'free');
        
        // Définir la limite de sites selon le plan
        $sitesLimit = $plan == 'pro' ? 5 : 20;
        
        // Mettre à jour l'utilisateur
        $stmt = $pdo->prepare("UPDATE users SET plan = ?, sites_limit = ?, last_payment = NOW() WHERE id = ?");
        $stmt->execute([$plan, $sitesLimit, $userId]);
        
        // Ajouter à l'historique des paiements
        $stmt = $pdo->prepare("
            INSERT INTO payments (user_id, plan, amount, status, lemon_id, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $userId,
            $plan,
            $data['data']['attributes']['renewal_price'] / 100, // Convertir centimes en euros
            $status,
            $data['data']['id'] ?? ''
        ]);
        
        http_response_code(200);
        echo 'OK';
        break;
        
    case 'subscription_cancelled':
    case 'subscription_expired':
        // Rétrograder en free après la fin de la période payée
        $stmt = $pdo->prepare("UPDATE users SET plan = 'free', sites_limit = 1 WHERE id = ?");
        $stmt->execute([$userId]);
        http_response_code(200);
        echo 'OK';
        break;
        
    case 'order_created':
        // Traiter une commande unique (pas un abonnement)
        $status = $data['data']['attributes']['status'] ?? '';
        if ($status === 'paid') {
            // Mettre à jour le statut de paiement
            $stmt = $pdo->prepare("
                INSERT INTO payments (user_id, plan, amount, status, lemon_id, created_at)
                VALUES (?, 'one_time', ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $userId,
                $data['data']['attributes']['total'] / 100,
                $status,
                $data['data']['id'] ?? ''
            ]);
        }
        http_response_code(200);
        echo 'OK';
        break;
        
    default:
        http_response_code(200);
        echo 'Événement non traité';
        break;
}
?>