<?php
require_once __DIR__ . '/inc/security.php';
require_once __DIR__ . '/inc/functions.php';

sendSecurityHeaders();

// Valider l'ID du produit
$productId = sanitizeInput($_GET['id'] ?? 0, 'int');
if ($productId <= 0) {
    header('Location: index.php');
    exit;
}

$product = getProduct($productId);
if (!$product) {
    header('Location: index.php');
    exit;
}

$rates = getExchangeRates();
$solToUsd = $rates['sol'];
$usdcToUsd = $rates['usdc'];

// Générer un token CSRF
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' https://unpkg.com; style-src 'self' 'unsafe-inline' https://unpkg.com; img-src 'self' data:; font-src 'self' https://unpkg.com; connect-src 'self' https://api.coingecko.com https://api.mainnet-beta.solana.com;">
    <title><?php echo htmlspecialchars($product['name']); ?> - Shop</title>
    <link rel="stylesheet" href="https://unpkg.com/pico@latest/css/pico.min.css">
    <script src="https://unpkg.com/@solana/web3.js@latest/lib/index.iife.js"></script>
    <link rel="stylesheet" href="styles.css">
    
</head>
<body>
    <header>
        <h1><?php echo htmlspecialchars($product['name']); ?></h1>
        <a href="index.php" role="button">← Retour aux produits</a>
    </header>

    <main>
        <div class="product-detail">
            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="max-width: 100%; margin-bottom: 1rem;">

            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

            <div class="price-container">
                <p>
                    <span class="price"><?php echo number_format($product['price_sol'], 4); ?> SOL</span>
                    <span class="price-usd"> (~$<?php echo number_format($product['price_sol'] * $solToUsd, 2); ?>)</span>
                </p>
                <p>
                    <span class="price"><?php echo number_format($product['price_sol'] * $solToUsd / $usdcToUsd, 2); ?> USDC</span>
                    <span class="price-usd"> (~$<?php echo number_format($product['price_sol'] * $solToUsd, 2); ?>)</span>
                </p>
            </div>

            <div id="checkout-form">
                <h3>Payer avec Solana</h3>
                <p>Connectez votre wallet (Phantom, Solflare) et payez en SOL ou USDC.</p>

                <button id="connectWallet" role="button">Se connecter avec Solana</button>

                <div id="paymentSection" style="display: none; margin-top: 1rem;">
                    <p>Wallet connecté: <span id="walletAddress" style="font-family: monospace;"></span></p>

                    <div style="margin: 1rem 0;">
                        <label>
                            <input type="radio" name="currency" value="sol" checked> SOL
                        </label>
                        <label style="margin-left: 1rem;">
                            <input type="radio" name="currency" value="usdc"> USDC
                        </label>
                    </div>

                    <p>Montant à payer: <span id="amountToPay" style="font-weight: bold;"></span></p>

                    <form id="paymentForm" method="post" action="verify_payment.php" style="margin-top: 1rem;">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                        <input type="hidden" name="tx_hash" id="txHashInput">
                        <input type="hidden" name="buyer_wallet" id="buyerWalletInput">
                        <input type="hidden" name="amount" id="amountInput">
                        <input type="hidden" name="currency" id="currencyInput">
                        <button type="submit" id="payButton" role="button">Confirmer le paiement</button>
                    </form>
                </div>

                <div id="txStatus" style="margin-top: 1rem; display: none;"></div>
            </div>
        </div>
    </main>

    <footer>
        <p>Paiement 100% décentralisé. Pas de banques, pas de GAFAM.</p>
    </footer>

    <script>
        // Configuration
        const productId = <?php echo $productId; ?>;
        const productPriceSol = <?php echo $product['price_sol']; ?>;
        const solToUsd = <?php echo $solToUsd; ?>;
        const usdcToUsd = <?php echo $usdcToUsd; ?>;
        const myWallet = "<?php echo htmlspecialchars(MY_SOLANA_WALLET, ENT_QUOTES, 'UTF-8'); ?>";
        const usdcMint = "EPjFWdd5AufqSSqeM2qN1xzybapC8G4wEGGkZwyTDt1v"; // Adresse USDC sur Solana

        // Initialiser Web3.js
        let wallet;
        let connection;
        let amount, currency;

        // Se connecter au wallet
        document.getElementById('connectWallet').addEventListener('click', async () => {
            if (window.solana && window.solana.isPhantom) {
                try {
                    wallet = window.solana;
                    const response = await wallet.connect();
                    const publicKey = new solanaWeb3.PublicKey(response.publicKey.toString());

                    // Vérifier que l'adresse est valide
                    if (!/^[1-9A-HJ-NP-Za-km-z]{32,44}$/.test(publicKey.toString())) {
                        throw new Error("Adresse Solana invalide");
                    }

                    document.getElementById('walletAddress').textContent = publicKey.toString();
                    document.getElementById('buyerWalletInput').value = publicKey.toString();
                    document.getElementById('paymentSection').style.display = 'block';
                    document.getElementById('connectWallet').style.display = 'none';

                    // Mettre à jour le montant à payer
                    updateAmountToPay();
                } catch (err) {
                    document.getElementById('txStatus').innerHTML = `
                        <p style="color: red;">❌ Erreur: ${err.message}</p>
                    `;
                    document.getElementById('txStatus').style.display = 'block';
                }
            } else {
                document.getElementById('txStatus').innerHTML = `
                    <p style="color: red;">❌ Installez Phantom Wallet: <a href="https://phantom.app/" target="_blank">phantom.app</a></p>
                `;
                document.getElementById('txStatus').style.display = 'block';
            }
        });

        // Mettre à jour le montant à payer
        function updateAmountToPay() {
            currency = document.querySelector('input[name="currency"]:checked').value;
            document.getElementById('currencyInput').value = currency;

            if (currency === 'sol') {
                amount = productPriceSol;
                document.getElementById('amountToPay').textContent = amount.toFixed(4) + " SOL";
            } else {
                amount = productPriceSol * solToUsd / usdcToUsd;
                document.getElementById('amountToPay').textContent = amount.toFixed(2) + " USDC";
            }
            document.getElementById('amountInput').value = amount;
        }

        // Écouter les changements de devise
        document.querySelectorAll('input[name="currency"]').forEach(radio => {
            radio.addEventListener('change', updateAmountToPay);
        });

        // Effectuer le paiement
        document.getElementById('paymentForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!wallet) {
                document.getElementById('txStatus').innerHTML = `
                    <p style="color: red;">❌ Connectez d'abord votre wallet!</p>
                `;
                document.getElementById('txStatus').style.display = 'block';
                return;
            }

            try {
                connection = new solanaWeb3.Connection(solanaWeb3.clusterApiUrl('mainnet-beta'));
                const publicKey = new solanaWeb3.PublicKey((await wallet.connect()).publicKey.toString());
                const myPublicKey = new solanaWeb3.PublicKey(myWallet);

                let transaction;
                if (currency === 'sol') {
                    // Paiement en SOL
                    const lamports = Math.round(amount * solanaWeb3.LAMPORTS_PER_SOL);
                    transaction = new solanaWeb3.Transaction().add(
                        solanaWeb3.SystemProgram.transfer({
                            fromPubkey: publicKey,
                            toPubkey: myPublicKey,
                            lamports,
                        })
                    );
                } else {
                    // Paiement en USDC (nécessite de vérifier l'ATA)
                    const usdcAmount = Math.round(amount * 1e6); // USDC a 6 décimales

                    // Vérifier que l'utilisateur a un ATA pour USDC
                    const associatedTokenAccount = await solanaWeb3.PublicKey.findProgramAddress(
                        [
                            publicKey.toBuffer(),
                            new solanaWeb3.PublicKey(usdcMint).toBuffer(),
                            new solanaWeb3.PublicKey("ATokenGPvbdGVxr1b2hvZbsiqW5xWH25efTNsLJA8knL").toBuffer(),
                        ],
                        new solanaWeb3.PublicKey("ATokenGPvbdGVxr1b2hvZbsiqW5xWH25efTNsLJA8knL")
                    );

                    // Créer l'instruction de transfert USDC
                    const transferInstruction = new solanaWeb3.TransactionInstruction({
                        keys: [
                            { pubkey: associatedTokenAccount[0], isSigner: false, isWritable: true },
                            { pubkey: myPublicKey, isSigner: false, isWritable: true },
                            { pubkey: publicKey, isSigner: true, isWritable: false },
                        ],
                        programId: new solanaWeb3.PublicKey("TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA"), // Programme Token
                        data: Buffer.concat([
                            Buffer.from([0]), // Instruction Transfer
                            Buffer.from(new BN(usdcAmount).toArray("le", 8)),
                        ]),
                    });

                    transaction = new solanaWeb3.Transaction().add(transferInstruction);
                }

                // Signer et envoyer la transaction
                const { blockhash } = await connection.getRecentBlockhash();
                transaction.recentBlockhash = blockhash;
                transaction.feePayer = publicKey;

                const signedTx = await wallet.signTransaction(transaction);
                const txHash = await connection.sendRawTransaction(signedTx.serialize());

                document.getElementById('txHashInput').value = txHash;
                document.getElementById('txStatus').innerHTML = `
                    <p>Transaction envoyée! Hash: <code style="word-break: break-all;">${txHash}</code></p>
                    <p>En attente de confirmation...</p>
                `;
                document.getElementById('txStatus').style.display = 'block';

                // Attendre la confirmation (avec timeout)
                const startTime = Date.now();
                const timeout = 30000; // 30 secondes

                while (Date.now() - startTime < timeout) {
                    const confirmation = await connection.confirmTransaction(txHash);
                    if (confirmation.value.err === null) {
                        document.getElementById('paymentForm').submit();
                        return;
                    }
                    await new Promise(resolve => setTimeout(resolve, 1000));
                }

                throw new Error("Timeout: Transaction non confirmée après 30 secondes");

            } catch (err) {
                document.getElementById('txStatus').innerHTML = `
                    <p style="color: red;">❌ Erreur: ${err.message}</p>
                `;
                document.getElementById('txStatus').style.display = 'block';
            }
        });
    </script>
</body>
</html>