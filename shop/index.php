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
:root {
    color-scheme: dark;
    --violet:  #9d86ff;
    --p-rose:  #ff86e9;
    --p-bleu:  #4ecdc4;
    --p-jaune: #ffe66d;
    --p-rouge: #ff6b8b;
    --bg:      #0c0b14;
    --surface: #14121f;
    --surface-2: #1a1730;
    --border:  #2a2545;
    --text:    #eae7f7;
    --muted:   #9d94b8;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    background: var(--bg); color: var(--text);
    font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
    line-height: 1.6;
}
.wrap { max-width: 1100px; margin: 0 auto; padding: 1.5rem 1.25rem 3rem; }

/* ---- Barre de navigation ---- */
nav {
    display: flex; justify-content: space-between; align-items: center;
    padding: .9rem 1.4rem; margin-bottom: 2.5rem;
    background: rgba(20, 18, 31, .85);
    border: 1px solid var(--border); border-radius: 14px;
    backdrop-filter: blur(8px);
    position: sticky; top: .8rem; z-index: 10;
}
nav .logo {
    font-weight: 800; font-size: 1.05rem; letter-spacing: .3px;
    color: var(--text); text-decoration: none;
}
nav .logo span { color: var(--violet); }
nav .back {
    display: inline-flex; align-items: center; gap: .4rem;
    color: var(--muted); text-decoration: none; font-size: .9rem;
    padding: .45rem .9rem; border: 1px solid var(--border);
    border-radius: 999px; transition: all .2s;
}
nav .back:hover { color: var(--text); border-color: var(--violet); }

/* ---- Hero ---- */
header { text-align: center; margin-bottom: 3rem; padding: 1rem 0 2rem; }
header .eyebrow {
    display: inline-block; font-size: .78rem; font-weight: 600;
    letter-spacing: 1.5px; text-transform: uppercase;
    color: var(--p-bleu);
    border: 1px solid rgba(78, 205, 196, .35);
    background: rgba(78, 205, 196, .08);
    padding: .3rem .9rem; border-radius: 999px; margin-bottom: 1.1rem;
}
header h1 {
    font-size: clamp(1.9rem, 4vw, 2.8rem);
    letter-spacing: -.5px; font-weight: 800;
    background: var(--violet);
    -webkit-background-clip: text; background-clip: text;
    -webkit-text-fill-color: transparent;
}
header .sub { color: var(--muted); max-width: 620px; margin: .8rem auto 0; }
header .guarantees {
    display: flex; flex-wrap: wrap; justify-content: center; gap: .6rem;
    margin-top: 1.6rem;
}
header .guarantees span {
    font-size: .8rem; color: var(--text);
    background: var(--surface-2); border: 1px solid var(--border);
    padding: .35rem .85rem; border-radius: 999px;
}

/* ---- Grille produits ---- */
.grid {
    display: grid; gap: 1.4rem;
    grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
}
.card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px; overflow: hidden;
    display: flex; flex-direction: column;
    transition: transform .25s, border-color .25s, box-shadow .25s;
    position: relative;
}
.card::before {
    content: ''; position: absolute; inset: 0 0 auto 0; height: 3px;
    background: var(--violet);
    opacity: 0; transition: opacity .25s;
}
.card:hover {
    transform: translateY(-4px);
    border-color: rgba(157, 134, 255, .55);
    box-shadow: 0 12px 32px rgba(157, 134, 255, .12);
}
.card:hover::before { opacity: 1; }
.card img {
    width: 100%; height: 210px; object-fit: cover;
    background: var(--surface-2); border-bottom: 1px solid var(--border);
}
.card .body {
    padding: 1.2rem; flex: 1;
    display: flex; flex-direction: column; gap: .55rem;
}
.card h2 { font-size: 1.02rem; font-weight: 700; letter-spacing: .1px; }
.card .desc { color: var(--muted); font-size: .9rem; flex: 1; }
.card .stock {
    font-size: .78rem; font-weight: 600; display: inline-flex; align-items: center; gap: .35rem;
    color: var(--p-bleu);
}
.card .stock::before { content: '●'; font-size: .6rem; }
.card .stock.low { color: var(--p-jaune); }
.card .stock.low::before { color: var(--p-jaune); }
.card .foot {
    display: flex; justify-content: space-between; align-items: center;
    gap: .8rem; margin-top: .3rem; padding-top: .9rem;
    border-top: 1px solid var(--border);
}
.price { font-size: 1.35rem; font-weight: 800; color: var(--text); }
.price small {
    display: block; color: var(--muted); font-weight: 400; font-size: .72rem;
}
.buy {
    display: inline-block; padding: .7rem 1.2rem;
    background: var(--violet);
    color: #fff; text-align: center; text-decoration: none;
    border-radius: 10px; font-weight: 700; font-size: .9rem;
    box-shadow: 0 4px 16px rgba(157, 134, 255, .25);
    transition: transform .2s, box-shadow .2s;
}
.buy:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(255, 134, 233, .35);
}

/* ---- Pied de page ---- */ 
.oss {
    text-align: center; margin-top: 3.5rem; padding: 1.5rem 1rem 0;
    color: var(--muted); font-size: .85rem;
    border-top: 1px solid var(--border);
}
.oss a { color: var(--violet); text-decoration: none; }
.oss a:hover { color: var(--p-rose); }
.oss .links { margin-bottom: .6rem; }
.oss .links a { margin: 0 .6rem; }
</style>
</head>
<body>
<div class="wrap">

    <nav>
        <a class="logo" href="index.php">berru<span>.shop</span></a>
        <a class="back" href="../index.php">← Retour au site</a>
    </nav>

    <header>
        <span class="eyebrow">Électronique · Fabriqué à Nantes</span>
        <h1>Créations électroniques open source</h1>
        <p class="sub">
            Badges NFC, kits radio commandés, contrôleurs MIDI, shields éducatifs —
            chaque objet est conçu, soudé et testé maison, par paire avant expédition.
        </p>
        <div class="guarantees">
            <span>🔒 Paiement sécurisé Stripe</span>
            <span>📦 Envoi France · Belgique · Suisse</span>
            <span>🛠️ Testé unitairement</span>
            <span>eur Prix TTC, TVA incluse</span>
        </div>
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
                <div class="foot">
                    <div class="price">
                        <?= price_eur((int) $p['price_cents']) ?>
                        <small>TTC · livraison incluse</small>
                    </div>
                    <a class="buy" href="<?= htmlspecialchars($p['stripe_payment_link']) ?>" rel="noopener">
                        Commander
                    </a>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
    </div>

    <p class="oss">
        <span class="links">
            <a href="../index.php">← Retour à mon site</a>
            <a href="https://github.com/berru-g/">GitHub</a>
            <a href="mailto:contact@gael-berru.com?subject=Question concernant ma commande">Contact</a>
        </span>
        Tous mes projets restent open source — ici, vous achetez l'objet assemblé et testé, pas la licence.<br>
        Paiement sécurisé par Stripe · TVA collectée via Stripe Tax · Prix TTC
    </p>
</div>
</body>
</html>