<?php
/**
 * /shop/merci.php — Page de remerciement après paiement Stripe
 * Les liens de paiement redirigent ici (after_completion: redirect).
 * Stripe ajoute ?session_id={CHECKOUT_SESSION_ID} à l'URL.
 * Vanilla, sans dépendance : la page fonctionne même si la BDD est indisponible.
 */
require __DIR__ . '/config.php';

$paid = false;
$sessionId = $_GET['session_id'] ?? '';

// Vérification directe auprès de Stripe que la session est bien payée (anti-triche)
if ($sessionId && preg_match('/^(cs_|cs_test_)[A-Za-z0-9]+$/', $sessionId)) {
    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions/' . urlencode($sessionId));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => STRIPE_SECRET_KEY . ':',
    ]);
    $resp = json_decode(curl_exec($ch) ?: '', true);
    curl_close($ch);
    if (($resp['payment_status'] ?? '') === 'paid') {
        $paid = true;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Merci pour votre commande — Berru Shop</title>
<style>
:root { color-scheme: dark; }
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    background: #0d1117; color: #e6edf3;
    font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
    line-height: 1.7; display: flex; min-height: 100vh;
    align-items: center; justify-content: center; padding: 2rem 1rem;
    text-align: center;
}
.card {
    max-width: 560px; background: #161b22;
    border: 1px solid #30363d; border-radius: 12px; padding: 2.5rem;
}
h1 { font-size: 1.6rem; margin-bottom: 1rem; }
h1 .check { color: #9d86ff; }
p { color: #8b949e; margin-bottom: .8rem; }
a { color: #58a6ff; }
.back {
    display: inline-block; margin-top: 1.5rem; padding: .7rem 1.4rem;
    background: #9d86ff; color: #fff; text-decoration: none;
    border-radius: 8px; font-weight: 600;
}
.back:hover { background: #7357f0; }
</style>
</head>
<body>
<div class="card">
    <?php if ($paid): ?>
        <h1><span class="check">✔</span> Merci pour votre commande !</h1>
        <p>Votre paiement est confirmé. Un reçu vous a été envoyé par email par Stripe.</p>
        <p>Votre création électronique est en préparation — vous recevrez les infos
           d'expédition très vite. Fabrication maison, donc chaque pièce est testée avant l'envoi.</p>
    <?php else: ?>
        <h1>Commande en cours de vérification…</h1>
        <p>Si vous venez de payer, votre paiement est en cours de confirmation.
           Le reçu Stripe arrive par email dans les minutes qui suivent.</p>
        <p>Une question ? <a href="mailto:contact@gael-berru.com?subject=commande en cours de vérification">contact@gael-berru.com</a></p>
    <?php endif; ?>
    <a class="back" href="index.php">← Retour à la boutique</a>
</div>
</body>
</html>