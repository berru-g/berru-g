<?php
require_once __DIR__ . '/inc/functions.php';
$products = getProducts();
$rates = getExchangeRates();
$solToUsd = $rates['sol'];
$usdcToUsd = $rates['usdc'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shop - Gael Berru</title>
    <link rel="stylesheet" href="https://unpkg.com/pico@latest/css/pico.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Shop - Paiement Solana/USDC</h1>
        <p>Créations open-source, souveraines, sans compromis.</p>
    </header>

    <main>
        <div class="products">
            <?php while ($product = $products->fetch_assoc()): ?>
                <article class="product-card">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" width="300" style="max-width: 100%;">
                    <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    <p>
                        <span class="price"><?php echo number_format($product['price_sol'], 4); ?> SOL</span>
                        <span class="price-usd"> (~$<?php echo number_format($product['price_sol'] * $solToUsd, 2); ?>)</span>
                    </p>
                    <a href="product.php?id=<?php echo $product['id']; ?>" role="button">Voir les détails</a>
                </article>
            <?php endwhile; ?>
        </div>
    </main>

    <footer>
        <p>Paiement 100% décentralisé. Pas de banques, pas de GAFAM.</p>
    </footer>
</body>
</html>