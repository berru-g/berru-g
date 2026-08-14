<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/coingecko.php';
require_once __DIR__ . '/solana.php';

function getProducts() {
    global $db;
    return $db->query("SELECT * FROM products ORDER BY created_at DESC");
}

function getProduct($id) {
    global $db;
    $result = $db->query("SELECT * FROM products WHERE id = ?", [$id]);
    return $result->fetch_assoc();
}

function createOrder($productId, $buyerWallet, $amountSol, $amountUsdc, $txHash) {
    global $db;
    return $db->insert('orders', [
        'product_id' => $productId,
        'buyer_wallet' => $buyerWallet,
        'amount_sol' => $amountSol,
        'amount_usdc' => $amountUsdc,
        'tx_hash' => $txHash,
        'status' => 'pending'
    ]);
}

function updateOrderStatus($orderId, $status) {
    global $db;
    $db->query("UPDATE orders SET status = ? WHERE id = ?", [$status, $orderId]);
}
?>