<?php
/**
 * /shop/index.php — Catalogue Berru Shop
 * Vanilla PHP + MySQL. Affiche les fiches produits et envoie l'achat
 * vers le Payment Link Stripe correspondant (TVA gérée par Stripe Tax).
 */
require __DIR__ . '/config.php';

$stmt = $pdo->query(
    'SELECT * FROM products WHERE active = 1 ORDER BY sort_order ASC'
);
$products = $stmt->fetchAll();

function price_eur(int $cents): string
{
    return number_format($cents / 100, 2, ',', ' ') . ' €';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Berru Shop — créations électroniques</title>
<meta name="description" content="Créations électroniques de Berru : badges NFC, kits radio commandés, contrôleurs MIDI, shields Arduino. Fabrication maison, open source.">
<style>
:root { color-scheme: dark; }
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    background: #0d1117; color: #e6edf3;
    font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
    line-height: 1.6; padding: 2rem 1rem;
}
.wrap { max-width: 1100px; margin: 0 auto; }
header { text-align: center; margin-bottom: 2.5rem; }
header h1 { font-size: 2rem; letter-spacing: .5px; }
header p { color: #8b949e; margin-top: .5rem; }
.grid {
    display: grid; gap: 1.5rem;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
}
.card {
    background: #161b22; border: 1px solid #30363d;
    border-radius: 12px; overflow: hidden;
    display: flex; flex-direction: column;
    transition: border-color .2s;
}
.card:hover { border-color: #58a6ff; }
.card img {
    width: 100%; height: 200px; object-fit: cover;
    background: #0d1117; border-bottom: 1px solid #30363d;
}
.card .body { padding: 1.1rem; flex: 1; display: flex; flex-direction: column; gap: .5rem; }
.card h2 { font-size: 1.05rem; }
.card .desc { color: #8b949e; font-size: .9rem; flex: 1; }
.card .stock { font-size: .8rem; color: #3fb950; }
.card .stock.low { color: #d29922; }
.price { font-size: 1.3rem; font-weight: 700; }
.price small { color: #8b949e; font-weight: 400; font-size: .75rem; }
.buy {
    display: block; margin-top: .5rem; padding: .7rem;
    background: #238636; color: #fff; text-align: center;
    text-decoration: none; border-radius: 8px; font-weight: 600;
    transition: background .2s;
}
.buy:hover { background: #2ea043; }
.oss {
    text-align: center; margin-top: 3rem; padding: 1rem;
    color: #8b949e; font-size: .85rem;
    border-top: 1px solid #30363d;
}
.oss a { color: #58a6ff; }
</style>
</head>
<body>
<div class="wrap">
    <header>
        <h1>Berru Shop 🛠️</h1>
        <p>Créations électroniques fabriquées maison, à Nantes. Open source, paiement sécurisé Stripe. Prix TTC.</p>
    </header>

    <div class="grid">
    <?php foreach ($products as $p): ?>
        <article class="card">
            <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
            <div class="body">
                <h2><?= htmlspecialchars($p['name']) ?></h2>
                <p class="desc"><?= htmlspecialchars($p['short_desc']) ?></p>
                <span class="stock <?= $p['stock'] <= 3 ? 'low' : '' ?>">
                    <?= $p['stock'] > 0 ? 'En stock (' . (int) $p['stock'] . ')' : 'Fabrication sur commande' ?>
                </span>
                <div class="price">
                    <?= price_eur((int) $p['price_cents']) ?>
                    <small>TTC, livraison France/Belgique/Suisse</small>
                </div>
                <a class="buy" href="<?= htmlspecialchars($p['stripe_payment_link']) ?>" rel="noopener">
                    Commander avec Stripe
                </a>
            </div>
        </article>
    <?php endforeach; ?>
    </div>

    <p class="oss">
        Tous mes projets restent <a href="https://github.com/berru-g/">open source sur GitHub</a> —
        ici, vous achetez l'objet assemblé et testé, pas la licence.<br>
        Paiement sécurisé par Stripe · TVA collectée via Stripe Tax ·
        Questions : <a href="mailto:contact@gael-berru.com">contact@gael-berru.com</a>
    </p>
</div>
</body>
</html>