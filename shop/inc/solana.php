<?php
require_once __DIR__ . '/config.php';

function getTransaction($txHash) {
    $url = SOLANA_API . '/v2/transactions/' . $txHash . '?encoding=json';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function verifyPayment($txHash, $expectedAmount, $buyerWallet, $myWallet = MY_SOLANA_WALLET) {
    $tx = getTransaction($txHash);
    if (!$tx || !isset($tx['transaction'])) {
        return false;
    }

    $transaction = $tx['transaction'];
    $meta = $tx['meta'] ?? null;

    // Vérifier que la transaction est confirmée
    if (!isset($tx['slot']) || $tx['slot'] <= 0) {
        return false;
    }

    // Vérifier le montant (en lamports, 1 SOL = 1e9 lamports)
    $amount = 0;
    foreach ($transaction['message']['instructions'] as $instruction) {
        if (isset($instruction['parsed']['info']['amount'])) {
            $amount += (int)$instruction['parsed']['info']['amount'];
        }
    }
    $amountSol = $amount / 1e9; // Convertir en SOL

    // Vérifier que le montant correspond
    if (abs($amountSol - $expectedAmount) > 0.0001) { // Tolérance de 0.0001 SOL
        return false;
    }

    // Vérifier que l'acheteur a bien envoyé les fonds
    $senderFound = false;
    foreach ($transaction['message']['accountKeys'] as $account) {
        if ($account === $buyerWallet) {
            $senderFound = true;
            break;
        }
    }
    if (!$senderFound) {
        return false;
    }

    // Vérifier que tu as bien reçu les fonds
    $recipientFound = false;
    foreach ($transaction['message']['accountKeys'] as $account) {
        if ($account === $myWallet) {
            $recipientFound = true;
            break;
        }
    }
    if (!$recipientFound) {
        return false;
    }

    return true;
}

// Exemple d'utilisation :
// $isValid = verifyPayment($_POST['tx_hash'], $_POST['amount'], $_POST['buyer_wallet']);
// if ($isValid) { /* Mettre à jour la commande */ }
?>