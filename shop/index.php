<?php
/**
 * /shop/index.php — Catalogue Berru Shop
 * Vanilla PHP + MySQL. Affiche les fiches produits et envoie l'achat
 * vers le Payment Link Stripe correspondant (TVA gérée par Stripe Tax).
 *
 * Refonte : le CSS reprend à l'identique celui de la galerie
 * (#galerie-section, .galerie-grid, .galerie-item, etc.).
 * La logique PHP / SQL est inchangée.
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
    <meta name="description"
        content="Créations électroniques de Berru : badges NFC, kits radio commandés, contrôleurs MIDI, shields Arduino. Fabrication maison, open source.">
    <link rel="stylesheet" href="../menu/galerie-shop.css">

    <!-- Polices de la galerie (inutile si déjà chargées par ton site) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Space+Grotesk:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            background: var(--bg-color);
        }
/*
        #galerie-section {
            background: var(--bg-color);
            min-height: 100vh;
        }

        .galerie-item {
            display: flex;
            flex-direction: column;
            cursor: default;
        }
*/
        .galerie-caption {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .galerie-caption p.desc {
            flex: 1;
        }

        .tag.low {
            color: var(--jaune);
        }


        .card .stock::before {
            content: '●';
            font-size: .6rem;
        }

        .card .stock.low {
            color: var(--jaune);
        }

        .card .stock.low::before {
            color: var(--jaune);
        }

        .card .foot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .8rem;
            margin-top: .3rem;
            padding-top: .9rem;
            border-top: 1px solid var(--line);
        }

        .price {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--text);
        }

        .price small {
            display: block;
            color: var(--violet);
            font-weight: 400;
            font-size: .72rem;
        }

        .buy {
            display: inline-block;
            padding: .7rem 1.2rem;
            background: var(--violet);
            color: #fff;
            text-align: center;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: .9rem;
            box-shadow: 0 4px 16px rgba(157, 134, 255, .25);
            transition: transform .2s, box-shadow .2s;
        }

        .buy:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255, 134, 233, .35);
        }

        /* ---- Pied de page ---- */
        .oss {
            text-align: center;
            margin-top: 3.5rem;
            padding: 1.5rem 1rem 0;
            color: var(--muted);
            font-size: .85rem;
            border-top: 1px solid var(--border);
        }

        .oss a {
            color: var(--violet);
            text-decoration: none;
        }

        .oss a:hover {
            color: var(--rose);
        }

        .oss .links {
            margin-bottom: .6rem;
        }

        .oss .links a {
            margin: 0 .6rem;
        }
    </style>
</head>

<body>

    <section id="galerie-section">
        <div class="shell">
            <section class="galerie-report" data-aos="fade-up">
                <h2 id="shop" class="galerie-title" data-aos="fade-right" data-aos-delay="100">Le shop<br><span
                        class="stroke">DIY</span>
                </h2>
                <p class="galerie-meta" data-aos="fade-right" data-aos-delay="200">Toute ces créations sont conçu dans
                    mon atelier au milieu des vignes nantaise.
                </p>
                <p class="galerie-meta" data-aos="fade-right" data-aos-delay="300">Pour toute commande particulière,
                    n'hésitez pas à me contacter.</p><br>
                <p class="galerie-meta" data-aos="fade-right" data-aos-delay="400"><span>Paiement sécurisé
                        <strong>Stripe</strong><br>
                        Envoi France, Belgique, Suisse · Prix TTC</span></p>
            </section>
        </div>

        <!-- ===== GRILLE ===== -->
        <div class="galerie-grid">
            <?php foreach ($products as $i => $p): ?>
                <article class="galerie-item"
                    style="animation-delay: <?= number_format(min($i, 10) * 0.07 + 0.05, 2, '.', '') ?>s">
                    <div class="galerie-image-container">
                        <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>"
                            loading="lazy">
                        <span class="galerie-index"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                    </div>
                    <div class="galerie-caption">
                        <h4><?= htmlspecialchars($p['name']) ?></h4>
                        <p class="desc"><?= htmlspecialchars($p['description']) ?></p>

                        <div class="galerie-tags">
                            <span class="tag <?= $p['stock'] <= 3 ? 'low' : '' ?>">
                                <?= $p['stock'] > 0 ? 'En stock (' . (int) $p['stock'] . ')' : 'Fabrication sur commande' ?>
                            </span>
                        </div>

                        <div class="galerie-foot">
                            <div class="galerie-price">
                                <?= price_eur((int) $p['price_cents']) ?>
                                <small>TTC · livraison incluse</small>
                            </div>
                            <a class="galerie-buy" href="<?= htmlspecialchars($p['stripe_payment_link']) ?>" rel="noopener" target="_blank">
                                Commander
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <p class="oss">
            <span class="links">
                <a href="../project-base-of-berru-g.php?utm_source=shop">Bdd</a>
                <a href="https://github.com/berru-g/?utm_source=shop">GitHub</a>
                <a href="mailto:contact@gael-berru.com?subject=Question concernant ma commande">Contact</a>
            </span>
            Tous mes projets restent open source — ici, vous achetez l'objet assemblé et testé, pas la licence.<br>
            Paiement sécurisé par Stripe · TVA collectée via Stripe Tax · Prix TTC
        </p>

        </div>
    </section>

</body>

</html>