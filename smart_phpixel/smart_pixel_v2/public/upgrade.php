<?php
// public/upgrade.php
require_once '../includes/config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);

// Récupérer les infos utilisateur
$stmt = $pdo->prepare("SELECT email, plan, billing_cycle FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Configuration Lemon Squeezy
$lemonApiKey = 'TU_CLE_API_LEMON'; // À mettre dans config.php
$storeId = 'TU_STORE_ID'; // À mettre dans config.php
$variantIds = [
    'monthly' => 'TU_VARIANT_ID_MENSUEL', // Variant pour 9€/mois
    'yearly' => 'TU_VARIANT_ID_ANNUEL'   // Variant pour 90€/an
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $billing_cycle = $_POST['billing_cycle'] ?? 'monthly'; // mensuel par défaut
    $billing_email = $_POST['billing_email'] ?? $user['email'];

    // Valider le cycle de facturation
    if (!isset($variantIds[$billing_cycle])) {
        die(json_encode(['success' => false, 'message' => 'Cycle de facturation invalide']));
    }

    // Créer un checkout Lemon Squeezy
    $checkoutData = [
        'data' => [
            'type' => 'checkouts',
            'attributes' => [
                'custom_price' => $billing_cycle === 'monthly' ? 900 : 9000, // 9€ ou 90€ en centimes
                'product_options' => [
                    'enabled_variants' => [$variantIds[$billing_cycle]]
                ],
                'checkout_options' => [
                    'embed' => true,
                    'media' => false,
                    'button_color' => '#9d86ff'
                ],
                'checkout_data' => [
                    'email' => $billing_email,
                    'custom' => [
                        'user_id' => $user_id,
                        'billing_cycle' => $billing_cycle
                    ]
                ],
                'expires_at' => date('Y-m-d H:i:s', strtotime('+30 minutes')),
                'preview' => false
            ],
            'relationships' => [
                'store' => ['data' => ['type' => 'stores', 'id' => $storeId]],
                'variant' => ['data' => ['type' => 'variants', 'id' => $variantIds[$billing_cycle]]]
            ]
        ]
    ];

    // Envoyer à l'API Lemon Squeezy
    $ch = curl_init('https://api.lemonsqueezy.com/v1/checkouts');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/vnd.api+json',
            'Content-Type: application/vnd.api+json',
            'Authorization: Bearer ' . $lemonApiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($checkoutData)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 201) {
        $responseData = json_decode($response, true);
        $checkoutUrl = $responseData['data']['attributes']['url'];
        echo json_encode([
            'success' => true,
            'checkout_url' => $checkoutUrl
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la création du checkout',
            'details' => $response
        ]);
    }
    exit();
}

// Récupérer le nombre de sites de l'utilisateur
$stmt = $pdo->prepare("SELECT COUNT(*) as site_count FROM user_sites WHERE user_id = ?");
$stmt->execute([$user_id]);
$siteCount = $stmt->fetch()['site_count'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mise à niveau - Smart Pixel Analytics</title>
    <link rel="stylesheet" href="../assets/dashboard.css">
    <style>
        :root {
            --primary: #9d86ff;
            --secondary: #f8f9fa;
            --text: #333;
            --border: #e9ecef;
        }

        body {
            font-family: 'Segoe UI', Roboto, sans-serif;
            background-color: var(--secondary);
            color: var(--text);
            line-height: 1.6;
        }

        .upgrade-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .upgrade-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .upgrade-header h1 {
            font-size: 2rem;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .upgrade-header p {
            color: var(--text-light);
            font-size: 1.1rem;
        }

        .current-plan-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
        }

        .current-plan-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .current-plan-icon {
            background: #f0f0ff;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: var(--primary);
        }

        .current-plan-details h3 {
            margin: 0;
            font-size: 1.1rem;
        }

        .current-plan-badge {
            background: #e9ecef;
            color: #6c757d;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .plan-stats {
            display: flex;
            gap: 1.5rem;
        }

        .plan-stat {
            text-align: center;
        }

        .plan-stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text);
        }

        .plan-stat-label {
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .plan-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
            position: relative;
            margin-bottom: 1.5rem;
        }

        .plan-card.recommended {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(157, 134, 255, 0.2);
        }

        .plan-badge {
            position: absolute;
            top: -10px;
            right: 1rem;
            background: var(--primary);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .plan-card-header {
            margin-bottom: 1rem;
        }

        .plan-card-header h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--text);
        }

        .plan-price {
            display: flex;
            align-items: baseline;
            margin-bottom: 0.5rem;
        }

        .plan-price .amount {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text);
        }

        .plan-price .period {
            font-size: 1rem;
            color: var(--text-light);
            margin-left: 0.3rem;
        }

        .plan-features {
            margin: 1.5rem 0;
        }

        .plan-features ul {
            list-style: none;
            padding: 0;
        }

        .plan-features li {
            padding: 0.3rem 0;
            color: var(--text-light);
            position: relative;
            padding-left: 1.5rem;
        }

        .plan-features li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: var(--primary);
            font-weight: bold;
        }

        .billing-options {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .billing-option {
            flex: 1;
            padding: 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .billing-option.selected {
            border-color: var(--primary);
            background: rgba(157, 134, 255, 0.05);
        }

        .billing-option h3 {
            margin-bottom: 0.5rem;
            color: var(--text);
        }

        .billing-option .price {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary);
        }

        .billing-option .savings {
            font-size: 0.9rem;
            color: var(--primary);
        }

        .btn-select {
            width: 100%;
            padding: 0.8rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: background 0.2s;
        }

        .btn-select:hover {
            background: #8a6ff0;
        }

        .btn-select svg {
            width: 18px;
            height: 18px;
        }

        .checkout-form-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
            margin-top: 2rem;
            display: none;
        }

        .checkout-form-header h3 {
            margin-bottom: 0.5rem;
            color: var(--text);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn-secondary {
            flex: 1;
            padding: 0.8rem;
            background: white;
            color: var(--primary);
            border: 1px solid var(--border);
            border-radius: 8px;
            cursor: pointer;
        }

        .btn-primary {
            flex: 1;
            padding: 0.8rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .loading-state {
            text-align: center;
            padding: 2rem;
            display: none;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(157, 134, 255, 0.2);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s ease-in-out infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="upgrade-container">
        <div class="upgrade-header">
            <h1>Passez à Premium</h1>
            <p>Débloquez toutes les fonctionnalités pour vos sites</p>
        </div>

        <!-- Carte du plan actuel -->
        <div class="current-plan-card">
            <div class="current-plan-header">
                <div class="current-plan-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                </div>
                <div class="current-plan-details">
                    <h3>Votre plan actuel</h3>
                    <span class="current-plan-badge"><?= strtoupper($user['plan'] ?? 'free') ?></span>
                </div>
            </div>
            <div class="plan-stats">
                <div class="plan-stat">
                    <div class="plan-stat-value"><?= $siteCount ?></div>
                    <div class="plan-stat-label">Sites actifs</div>
                </div>
                <div class="plan-stat">
                    <div class="plan-stat-value"><?= $user['plan'] === 'free' ? 1 : 'Illimité' ?></div>
                    <div class="plan-stat-label">Limite de sites</div>
                </div>
            </div>
        </div>

        <!-- Carte du plan Premium -->
        <div class="plan-card recommended">
            <div class="plan-badge">Recommandé</div>
            <div class="plan-card-header">
                <h2>PREMIUM</h2>
                <p>Tout ce dont vous avez besoin pour analyser vos sites</p>
            </div>

            <!-- Options de facturation -->
            <div class="billing-options">
                <div class="billing-option selected" onclick="selectBillingCycle('monthly')">
                    <h3>Mensuel</h3>
                    <div class="price">9€ <span class="period">/mois</span></div>
                </div>
                <div class="billing-option" onclick="selectBillingCycle('yearly')">
                    <h3>Annuel</h3>
                    <div class="price">90€ <span class="period">/an</span></div>
                    <div class="savings">Économisez 16%</div>
                </div>
            </div>

            <div class="plan-features">
                <ul>
                    <li>Sites illimités</li>
                    <li>1M de visites/mois</li>
                    <li>Accès complet à l'API</li>
                    <li>Export CSV/JSON</li>
                    <li>Support prioritaire</li>
                    <li>Stats temps réel</li>
                    <li>30 jours d'essai gratuit</li>
                </ul>
            </div>

            <input type="hidden" id="selectedBillingCycle" value="monthly">
            <button class="btn-select" onclick="showCheckoutForm()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 6L9 17l-5-5" />
                </svg>
                Choisir Premium
            </button>
        </div>

        <!-- Formulaire de checkout -->
        <div id="checkoutForm" class="checkout-form-container">
            <div class="checkout-form-header">
                <h3>Finalisez votre mise à niveau</h3>
                <p>Un dernier pas pour débloquer toutes les fonctionnalités</p>
            </div>
            <form id="upgradeForm" class="checkout-form">
                <input type="hidden" name="billing_cycle" id="billingCycleInput" value="monthly">
                <div class="form-group">
                    <label>Email de facturation</label>
                    <input type="email" name="billing_email"
                        value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                        required>
                </div>
                <div class="form-actions">
                    <button type="button" onclick="cancelCheckout()" class="btn-secondary">
                        Annuler
                    </button>
                    <button type="submit" class="btn-primary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                        Poursuivre le paiement
                    </button>
                </div>
            </form>
            <div id="loading" class="loading-state">
                <div class="loading-spinner"></div>
                <p>Création de votre checkout sécurisé...</p>
            </div>
        </div>
    </div>

    <script>
        // Sélection du cycle de facturation
        function selectBillingCycle(cycle) {
            document.querySelectorAll('.billing-option').forEach(option => {
                option.classList.remove('selected');
            });
            document.querySelector(`.billing-option[onclick="selectBillingCycle('${cycle}')"]`).classList.add('selected');
            document.getElementById('selectedBillingCycle').value = cycle;
            document.getElementById('billingCycleInput').value = cycle;
        }

        // Afficher le formulaire de checkout
        function showCheckoutForm() {
            const cycle = document.getElementById('selectedBillingCycle').value;
            document.getElementById('checkoutForm').style.display = 'block';
            document.getElementById('checkoutForm').scrollIntoView({
                behavior: 'smooth'
            });
        }

        // Annuler le checkout
        function cancelCheckout() {
            document.getElementById('checkoutForm').style.display = 'none';
        }

        // Soumission du formulaire
        document.getElementById('upgradeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = e.target;
            const loading = document.getElementById('loading');

            loading.style.display = 'block';
            form.style.display = 'none';

            fetch('upgrade.php', {
                method: 'POST',
                body: new FormData(form)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.checkout_url;
                } else {
                    alert('Erreur: ' + (data.message || 'Veuillez réessayer plus tard.'));
                    loading.style.display = 'none';
                    form.style.display = 'block';
                }
            })
            .catch(error => {
                alert('Erreur réseau: ' + error.message);
                loading.style.display = 'none';
                form.style.display = 'block';
            });
        });
    </script>
</body>
</html>
