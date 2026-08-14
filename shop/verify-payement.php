<?php
require_once __DIR__ . '/inc/security.php';
require_once __DIR__ . '/inc/functions.php';

sendSecurityHeaders();

// Vérifier la méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Méthode non autorisée']));
}

// Vérifier le rate limiting (10 requêtes/minute/IP)
if (!checkRateLimit($_SERVER['REMOTE_ADDR'])) {
    http_response_code(429);
    die(json_encode(['success' => false, 'error' => 'Trop de requêtes. Veuillez patienter.']));
}

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Token CSRF invalide']));
}

// Valider et sanitizer les entrées
$txHash = sanitizeInput($_POST['tx_hash'] ?? '', 'string');
$productId = sanitizeInput($_POST['product_id'] ?? 0, 'int');
$buyerWallet = sanitizeInput($_POST['buyer_wallet'] ?? '', 'string');
$amount = sanitizeInput($_POST['amount'] ?? 0, 'float');
$currency = sanitizeInput($_POST['currency'] ?? '', 'string');

// Validation stricte
if (!isValidTxHash($txHash)) {
    die(json_encode(['success' => false, 'error' => 'Hash de transaction invalide']));
}
if (!isValidSolanaAddress($buyerWallet)) {
    die(json_encode(['success' => false, 'error' => 'Adresse Solana invalide']));
}
if ($productId <= 0) {
    die(json_encode(['success' => false, 'error' => 'ID de produit invalide']));
}
if ($amount <= 0) {
    die(json_encode(['success' => false, 'error' => 'Montant invalide']));
}
if (!in_array($currency, ['sol', 'usdc'])) {
    die(json_encode(['success' => false, 'error' => 'Devise invalide']));
}

// Récupérer le produit
$product = getProduct($productId);
if (!$product) {
    die(json_encode(['success' => false, 'error' => 'Produit introuvable']));
}

// Vérifier le montant (tolérance de 0.001 SOL pour les frais de réseau)
$expectedAmount = $product['price_sol'];
if (abs($amount - $expectedAmount) > 0.001) {
    die(json_encode(['success' => false, 'error' => 'Montant incorrect']));
}

// Vérifier le paiement sur la blockchain
$isValid = verifyPayment($txHash, $amount, $buyerWallet);
if (!$isValid) {
    die(json_encode(['success' => false, 'error' => 'Paiement non valide ou non confirmé']));
}

// Créer la commande (avec requête préparée)
$orderId = $db->insert('orders', [
    'product_id' => $productId,
    'buyer_wallet' => $buyerWallet,
    'amount_sol' => $amount,
    'amount_usdc' => ($currency === 'usdc' ? $amount : null),
    'tx_hash' => $txHash,
    'status' => 'paid'
]);

if (!$orderId) {
    error_log("Erreur lors de la création de la commande");
    die(json_encode(['success' => false, 'error' => 'Erreur interne']));
}

echo json_encode([
    'success' => true,
    'order_id' => $orderId,
    'tx_hash' => $txHash
]);
?>