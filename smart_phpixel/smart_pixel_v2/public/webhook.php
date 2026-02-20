<?php
// public/webhook.php
require_once '../includes/config.php';
header('Content-Type: application/json');

// Vérifier la signature du webhook
$lemonSecret = 'LEMON_WEBHOOK_SECRET';
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
$eventData = $data['data']['attributes'] ?? [];
$customData = $eventData['custom'] ?? [];

// Extraire les données custom (user_id et billing_cycle)
$userId = $customData['user_id'] ?? null;
$billingCycle = $customData['billing_cycle'] ?? 'monthly'; // mensuel par défaut

// Si user_id n'est pas dans les custom data, essayer de le récupérer via l'email
if (!$userId && isset($eventData['user_email'])) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$eventData['user_email']]);
    $user = $stmt->fetch();
    $userId = $user['id'] ?? null;
}

if (!$userId) {
    http_response_code(400);
    exit('User ID non trouvé');
}

// Fonction pour mettre à jour l'utilisateur
function updateUser($pdo, $userId, $plan, $billingCycle, $endsAt = null) {
    $stmt = $pdo->prepare("
        UPDATE users
        SET
            plan = ?,
            billing_cycle = ?,
            sites_limit = 99,  -- Illimité pour Premium
            subscription_end = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $plan,
        $billingCycle,
        $endsAt,
        $userId
    ]);
}

// Fonction pour ajouter un paiement à l'historique
function addPayment($pdo, $userId, $plan, $amount, $status, $lemonId, $billingCycle) {
    $stmt = $pdo->prepare("
        INSERT INTO payments
        (user_id, plan, amount, status, lemon_id, billing_cycle, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $userId,
        $plan,
        $amount,
        $status,
        $lemonId,
        $billingCycle
    ]);
}

// Fonction pour ajouter/modifier un abonnement
function updateSubscription($pdo, $userId, $lemonSubscriptionId, $status, $currentPeriodEnd) {
    $stmt = $pdo->prepare("
        INSERT INTO subscriptions
        (user_id, lemon_subscription_id, status, current_period_end, created_at, updated_at)
        VALUES (?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            current_period_end = VALUES(current_period_end),
            updated_at = NOW()
    ");
    $stmt->execute([
        $userId,
        $lemonSubscriptionId,
        $status,
        $currentPeriodEnd
    ]);
}

switch ($eventName) {
    case 'subscription_created':
    case 'subscription_updated':
    case 'subscription_resumed':
        $status = $eventData['status'] ?? '';
        $lemonSubscriptionId = $eventData['id'] ?? '';
        $variantId = $eventData['variant_id'] ?? '';
        $currentPeriodEnd = $eventData['renews_at'] ?? $eventData['ends_at'] ?? null;

        // Déterminer le plan (toujours "premium" pour le nouveau système)
        $plan = 'premium';

        // Mettre à jour l'utilisateur
        updateUser($pdo, $userId, $plan, $billingCycle, $currentPeriodEnd);

        // Ajouter à l'historique des paiements
        $amount = $eventData['renewal_price'] / 100; // Convertir centimes en euros
        addPayment($pdo, $userId, $plan, $amount, $status, $lemonSubscriptionId, $billingCycle);

        // Mettre à jour l'abonnement
        updateSubscription($pdo, $userId, $lemonSubscriptionId, $status, $currentPeriodEnd);

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Abonnement mis à jour']);
        break;

    case 'subscription_cancelled':
    case 'subscription_expired':
        // Rétrograder en "free" après la fin de la période payée
        updateUser($pdo, $userId, 'free', null, null);

        // Mettre à jour le statut de l'abonnement
        $lemonSubscriptionId = $eventData['id'] ?? '';
        updateSubscription($pdo, $userId, $lemonSubscriptionId, 'cancelled', null);

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Abonnement annulé']);
        break;

    case 'subscription_payment_success':
        // Mettre à jour le statut du paiement
        $lemonSubscriptionId = $eventData['subscription_id'] ?? '';
        $amount = $eventData['total'] / 100; // Convertir centimes en euros

        addPayment($pdo, $userId, 'premium', $amount, 'paid', $lemonSubscriptionId, $billingCycle);

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Paiement enregistré']);
        break;

    case 'order_created':
        // Traiter une commande unique (pas un abonnement)
        $status = $eventData['status'] ?? '';
        if ($status === 'paid') {
            $amount = $eventData['total'] / 100;
            addPayment($pdo, $userId, 'one_time', $amount, $status, $eventData['id'] ?? '', $billingCycle);
        }
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Commande traitée']);
        break;

    default:
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Événement non traité : ' . $eventName]);
        break;
}
?>
