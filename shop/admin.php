<?php
/**
 * /shop/admin.php — Gestion des produits : prix, stock, visibilité.
 * Un seul endroit pour tout changer : la page met à jour la BDD ET Stripe
 * (création du nouveau prix + rebranchement du lien de paiement) automatiquement.
 * Tu n'ouvres plus jamais le Dashboard Stripe pour changer un prix.
 *
 * Accès : définir SHOP_ADMIN_TOKEN dans config.php, puis ouvrir
 * /shop/admin.php?t=TON_TOKEN  (à mettre en favori).
 */
require __DIR__ . '/config.php';

// ---- Protection d'accès (minimal, zéro dépendance) ----
$token = $_GET['t'] ?? $_POST['t'] ?? '';
if (!defined('SHOP_ADMIN_TOKEN') || !hash_equals(SHOP_ADMIN_TOKEN, $token)) {
    http_response_code(403);
    exit('Accès refusé.');
}

/**
 * Client Stripe minimal en cURL natif (pas de lib stripe-php).
 * $params accepte des clés imbriquées type "line_items[0][price]".
 */
function stripe_request(string $method, string $path, array $params = []): array
{
    $ch = curl_init('https://api.stripe.com/' . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_USERPWD         => STRIPE_SECRET_KEY . ':',
        CURLOPT_TIMEOUT         => 20,
        CURLOPT_POSTFIELDS      => $params ? http_build_query($params) : null,
    ]);
    $resp = json_decode(curl_exec($ch) ?: '', true);
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($code >= 400 || isset($resp['error'])) {
        throw new RuntimeException($resp['error']['message'] ?? 'Erreur Stripe (HTTP ' . $code . ')');
    }
    return $resp;
}

$msg = '';
$msgType = 'ok';

// ---- Traitement du formulaire ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $id         = (int) ($_POST['id'] ?? 0);
    $priceEuros = (float) str_replace(['€', ' '], '', str_replace(',', '.', $_POST['price'] ?? '0'));
    $priceCents = max(0, (int) round($priceEuros * 100));
    $stock      = max(0, (int) ($_POST['stock'] ?? 0));
    $active     = isset($_POST['active']) ? 1 : 0;

    $q = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $q->execute([$id]);
    $p = $q->fetch();

    if (!$p) {
        $msg = 'Produit introuvable.'; $msgType = 'err';
    } else {
        try {
            $newPriceId = $p['stripe_price_id'];

            // 1. Si le prix change : nouveau prix Stripe (les prix sont immuables)
            //    + rebranchement dans le Payment Link existant.
            if ($priceCents !== (int) $p['price_cents']) {
                $newPrice = stripe_request('POST', 'v1/prices', [
                    'product'      => $p['stripe_product_id'],
                    'unit_amount'  => $priceCents,
                    'currency'     => 'eur',
                    'tax_behavior' => 'inclusive',
                ]);
                $newPriceId = $newPrice['id'];

                // Remplace l'ancien prix dans le lien de paiement (ligne inchangée côté client)
                stripe_request('POST', 'v1/payment_links/' . $p['stripe_payment_link'], [
                    'line_items[0][price]'                      => $newPriceId,
                    'line_items[0][quantity]'                   => 1,
                    'line_items[0][adjustable_quantity][enabled]' => 'true',
                    'line_items[0][adjustable_quantity][minimum]' => 1,
                    'line_items[0][adjustable_quantity][maximum]' => 10,
                ]);

                // Archive l'ancien prix pour garder le Dashboard propre (facultatif)
                try {
                    stripe_request('POST', 'v1/prices/' . $p['stripe_price_id'], ['active' => 'false']);
                } catch (RuntimeException $e) { /* non bloquant */ }
            }

            // 2. Mise à jour de la BDD (source d'affichage du catalogue)
            $pdo->prepare(
                'UPDATE products SET price_cents = ?, stock = ?, active = ?, stripe_price_id = ? WHERE id = ?'
            )->execute([$priceCents, $stock, $active, $newPriceId, $id]);

            $msg = '✔ ' . htmlspecialchars($p['name']) . ' mis à jour'
                 . ($priceCents !== (int) $p['price_cents'] ? ' — prix synchronisé avec Stripe' : '');
        } catch (RuntimeException $e) {
            $msg = 'Erreur Stripe : ' . $e->getMessage() . ' — rien n\'a été changé.';
            $msgType = 'err';
        }
    }
}

// ---- Lecture des produits ----
$products = $pdo->query('SELECT * FROM products ORDER BY sort_order')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Berru Shop — Admin</title>
<style>
:root { color-scheme: dark; }
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: #0c0b14; color: #eae7f7; font-family: system-ui, sans-serif; padding: 2rem 1rem; line-height: 1.5; }
.wrap { max-width: 920px; margin: 0 auto; }
h1 { font-size: 1.4rem; margin-bottom: .3rem; }
.sub { color: #9d94b8; font-size: .85rem; margin-bottom: 1.5rem; }
.msg { padding: .8rem 1rem; border-radius: 10px; margin-bottom: 1.2rem; font-size: .9rem; }
.msg.ok  { background: rgba(78,205,196,.1);  border: 1px solid rgba(78,205,196,.4);  color: #4ecdc4; }
.msg.err { background: rgba(255,107,139,.1); border: 1px solid rgba(255,107,139,.4); color: #ff6b8b; }
form.p { background: #14121f; border: 1px solid #2a2545; border-radius: 12px; padding: 1.1rem; margin-bottom: 1rem; }
.pname b { font-size: .95rem; }
.pname span { display: block; color: #9d94b8; font-size: .72rem; margin-top: .15rem; }
.fields { display: grid; gap: .8rem; margin-top: .8rem; grid-template-columns: repeat(3, minmax(90px, 1fr)) auto; align-items: end; }
label { display: block; font-size: .72rem; color: #9d94b8; margin-bottom: .25rem; }
input[type=text], input[type=number] { width: 100%; background: #0c0b14; border: 1px solid #2a2545; color: #eae7f7; border-radius: 8px; padding: .5rem; font-size: .95rem; }
.switch { display: flex; align-items: center; gap: .4rem; font-size: .85rem; padding-bottom: .35rem; }
button { background: #9d86ff; border: none; color: #fff; font-weight: 700; padding: .55rem 1.1rem; border-radius: 8px; cursor: pointer; font-size: .85rem; }
button:hover { background: #ff86e9; }
</style>
</head>
<body>
<div class="wrap">
    <h1>🛠️ Berru Shop — Admin</h1>
    <p class="sub">Prix, stock, visibilité — Stripe est synchronisé automatiquement. Le lien de paiement reste identique (même URL, l'ancien prix est archivé).</p>

    <?php if ($msg): ?><div class="msg <?= $msgType ?>"><?= $msg ?></div><?php endif; ?>

    <?php foreach ($products as $p): ?>
        <form class="p" method="post">
            <input type="hidden" name="t" value="<?= htmlspecialchars($token) ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
            <div class="pname">
                <b><?= htmlspecialchars($p['name']) ?></b>
                <span>Stock actuel : <?= (int) $p['stock'] ?> · Prix Stripe : <?= htmlspecialchars($p['stripe_price_id']) ?></span>
            </div>
            <div class="fields">
                <div>
                    <label>Prix TTC (€)</label>
                    <input type="text" name="price" value="<?= number_format($p['price_cents'] / 100, 2, ',', '') ?>">
                </div>
                <div>
                    <label>Stock</label>
                    <input type="number" name="stock" min="0" value="<?= (int) $p['stock'] ?>">
                </div>
                <div>
                    <label>Visible boutique</label>
                    <span class="switch"><input type="checkbox" name="active" <?= $p['active'] ? 'checked' : '' ?>> actif</span>
                </div>
                <button type="submit">Enregistrer</button>
            </div>
        </form>
    <?php endforeach; ?>
</div>
</body>
</html>