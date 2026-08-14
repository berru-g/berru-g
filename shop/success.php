<?php
require_once __DIR__ . '/inc/functions.php';

$orderId = $_GET['order_id'] ?? 0;
if (!$orderId) {
    header('Location: index.php');
    exit;
}

global $db;
$result = $db->query("SELECT * FROM orders WHERE id = ?", [$orderId]);
$order = $result->fetch_assoc();
if (!$order) {
    header('Location: index.php');
    exit;
}

$product = getProduct($order['product_id']);
$rates = getExchangeRates();
$solToUsd = $rates['sol'];
$usdcToUsd = $rates['usdc'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Commande #<?php echo $orderId; ?> - Confirmation</title>
    <link rel="stylesheet" href="https://unpkg.com/pico@latest/css/pico.min.css">
</head>
<body>
    <header>
        <h1>Commande #<?php echo $orderId; ?> - Confirmée ✅</h1>
    </header>

    <main>
        <div class="order-summary">
            <h2>Merci pour votre achat!</h2>

            <h3>Détails de la commande</h3>
            <table>
                <tr>
                    <th>Produit</th>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                </tr>
                <tr>
                    <th>Montant</th>
                    <td>
                        <?php if ($order['amount_usdc']): ?>
                            <?php echo number_format($order['amount_usdc'], 2); ?> USDC
                        <?php else: ?>
                            <?php echo number_format($order['amount_sol'], 4); ?> SOL
                        <?php endif; ?>
                        (~$<?php echo number_format(($order['amount_usdc'] ?? ($order['amount_sol'] * $solToUsd)), 2); ?>)
                    </td>
                </tr>
                <tr>
                    <th>Transaction Solana</th>
                    <td>
                        <a href="https://solscan.io/tx/<?php echo $order['tx_hash']; ?>" target="_blank">
                            <?php echo substr($order['tx_hash'], 0, 16) . '...'; ?>
                        </a>
                    </td>
                </tr>
                <tr>
                    <th>Statut</th>
                    <td><?php echo ucfirst($order['status']); ?></td>
                </tr>
                <tr>
                    <th>Date</th>
                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                </tr>
            </table>

            <h3>Prochaines étapes</h3>
            <p>Votre commande a été enregistrée. Vous recevrez un email de confirmation sous 24h.</p>
            <p>Pour toute question, contactez-moi à <a href="mailto:ton@email.com">ton@email.com</a>.</p>

            <a href="index.php" role="button">Retour à la boutique</a>
        </div>
    </main>

    <footer>
        <p>Merci de soutenir les projets open-source et souverains.</p>
    </footer>
</body>
</html>