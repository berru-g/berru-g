<?php
/**
 * /shop/webhook.php — Webhook Stripe (vanilla, sans lib stripe-php)
 * Événement écouté : checkout.session.completed (Payment Link payé)
 * → enregistre la commande dans la table `orders`.
 *
 * Configuration Stripe :
 * Dashboard > Développeurs > Webhooks > Ajouter un endpoint :
 *   URL    : https://gael-berru.com/shop/webhook.php
 *   Event  : checkout.session.completed
 *   Secret : copier whsec_... dans config.php (STRIPE_WEBHOOK_SECRET)
 *
 * Test local : stripe listen --forward-to localhost/shop/webhook.php
 */
require __DIR__ . '/config.php';

// Stripe envoie du JSON brut
$payload = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$secret = STRIPE_WEBHOOK_SECRET;

// ---- 1. Vérification de signature (HMAC-SHA256, spec Stripe, sans dépendance) ----
function verify_stripe_signature(string $payload, string $sigHeader, string $secret, int $tolerance = 300): bool
{
    $timestamp = null;
    $signatures = [];
    foreach (explode(',', $sigHeader) as $part) {
        $kv = explode('=', trim($part), 2);
        if (count($kv) !== 2) continue;
        if ($kv[0] === 't') $timestamp = (int) $kv[1];
        if ($kv[0] === 'v1') $signatures[] = $kv[1];
    }
    if (!$timestamp || !$signatures) return false;
    if (abs(time() - $timestamp) > $tolerance) return false; // anti-replay

    $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    foreach ($signatures as $sig) {
        if (hash_equals($expected, $sig)) return true;
    }
    return false;
}

if (!verify_stripe_signature($payload, $sigHeader, $secret)) {
    http_response_code(400);
    exit('Signature invalide');
}

$event = json_decode($payload, true);
if (!$event) {
    http_response_code(400);
    exit('JSON invalide');
}

// ---- 2. Traitement : checkout.session.completed ----
if (($event['type'] ?? '') === 'checkout.session.completed') {
    $session = $event['data']['object'];
    $sessionId = $session['id'];
    $status    = $session['payment_status'] ?? 'unpaid'; // paid | unpaid
    $email     = $session['customer_details']['email'] ?? null;
    $intent    = $session['payment_intent'] ?? null;
    $total     = (int) ($session['amount_total'] ?? 0);
    $currency  = $session['currency'] ?? 'eur';
    $shipping  = null;
    if (!empty($session['collected_information']['shipping_details']['address'])) {
        $shipping = json_encode($session['collected_information']['shipping_details']['address']);
    }

    // Idempotence : ne pas retraiter une session déjà enregistrée
    $exists = $pdo->prepare('SELECT id FROM orders WHERE stripe_session_id = ?');
    $exists->execute([$sessionId]);
    if (!$exists->fetch()) {
        // Rattacher chaque line item payé à sa fiche produit (via stripe_price_id)
        // On récupère les line items via l'API Stripe (clé secrète, cURL natif)
        $ch = curl_init('https://api.stripe.com/v1/checkout/sessions/' . urlencode($sessionId) . '/line_items?limit=20');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => STRIPE_SECRET_KEY . ':',
        ]);
        $resp = json_decode(curl_exec($ch) ?: '', true);
        curl_close($ch);

        if (!empty($resp['data'])) {
            $insert = $pdo->prepare(
                'INSERT INTO orders
                    (stripe_session_id, stripe_payment_intent, stripe_customer_email,
                     product_id, quantity, amount_total, currency, payment_status, shipping_address)
                 VALUES (?,?,?,?,?,?,?,?,?)'
            );
            $findProduct = $pdo->prepare(
                'SELECT id FROM products WHERE stripe_price_id = ? LIMIT 1'
            );
            foreach ($resp['data'] as $item) {
                $findProduct->execute([$item['price']['id'] ?? '']);
                $product = $findProduct->fetchColumn();
                if (!$product) continue; // prix inconnu : ignoré
                $insert->execute([
                    $sessionId, $intent, $email,
                    (int) $product,
                    max(1, (int) ($item['quantity'] ?? 1)),
                    $total, $currency,
                    $status === 'paid' ? 'paid' : 'pending',
                    $shipping,
                ]);
                // Décrément du stock
                $pdo->prepare('UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ?')
                    ->execute([max(1, (int) ($item['quantity'] ?? 1)), (int) $product]);
            }
        }
    }
}

http_response_code(200);
echo 'OK';