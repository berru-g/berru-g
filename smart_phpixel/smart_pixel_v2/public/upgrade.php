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
$stmt = $pdo->prepare("SELECT email, api_key FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Configuration Lemon Squeezy
$lemonApiKey = 'YOUR_LEMON_API_KEY'; // À mettre dans config.php
$storeId = 'YOUR_STORE_ID'; // À mettre dans config.php
$variantIds = [
    'pro' => 'YOUR_PRO_VARIANT_ID', // Variant ID pour Pro (9€/mois)
    'business' => 'YOUR_BUSINESS_VARIANT_ID' // Variant ID pour Business (29€/mois)
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_plan = $_POST['new_plan'] ?? '';
    $billing_email = $_POST['billing_email'] ?? $user['email'];

    // Valider le plan
    if (!isset($variantIds[$new_plan])) {
        die(json_encode(['success' => false, 'message' => 'Plan invalide']));
    }

    // Créer un checkout Lemon Squeezy
    $checkoutData = [
        'data' => [
            'type' => 'checkouts',
            'attributes' => [
                'custom_price' => $new_plan == 'pro' ? 900 : 2900, // en centimes (9€ = 900)
                'product_options' => [
                    'enabled_variants' => [$variantIds[$new_plan]]
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
                        'plan' => $new_plan,
                        'api_key' => $user['api_key']
                    ]
                ],
                'expires_at' => date('Y-m-d H:i:s', strtotime('+30 minutes')),
                'preview' => false
            ],
            'relationships' => [
                'store' => ['data' => ['type' => 'stores', 'id' => $storeId]],
                'variant' => ['data' => ['type' => 'variants', 'id' => $variantIds[$new_plan]]]
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

        // Rediriger vers le checkout
        echo json_encode([
            'success' => true,
            'checkout_url' => $checkoutUrl
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la création du checkout'
        ]);
    }
    exit();
}

// Si GET, afficher la page de sélection de plan
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mise à niveau - Smart Pixel Analytics</title>
    <link rel="stylesheet" href="../assets/dashboard.css">
</head>

<body>
    <!-- Remplacer ces sections dans ton HTML existant -->

    <!-- En-tête de page -->
    <div class="upgrade-container">
        <div class="upgrade-header">
            <h1>Mise à niveau de votre plan</h1>
            <p>Choisissez le plan qui correspond le mieux à vos besoins analytiques</p>
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
                    <span class="current-plan-badge"><?= strtoupper($_SESSION['user_plan'] ?? 'free') ?></span>
                </div>
            </div>
            <div class="plan-stats">
                <div class="plan-stat">
                    <div class="plan-stat-value"><?= $_SESSION['site_count'] ?? 0 ?></div>
                    <div class="plan-stat-label">Sites actifs</div>
                </div>
                <div class="plan-stat">
                    <div class="plan-stat-value"><?= $_SESSION['sites_limit'] ?? 1 ?></div>
                    <div class="plan-stat-label">Limite autorisée</div>
                </div>
            </div>
        </div>

        <!-- Grille des plans -->
        <div class="plans-container">
            <!-- Plan PRO -->
            <div class="plan-card pro">
                <div class="plan-card-header">
                    <h2>PRO</h2>
                    <div class="plan-price">
                        <span class="amount">9€</span>
                        <span class="period">/mois</span>
                    </div>
                    <p>Parfait pour les petites entreprises</p>
                </div>
                <div class="plan-features">
                    <ul>
                        <li>5 sites maximum</li>
                        <li>10 000 visites/mois</li>
                        <li>Stats avancées</li>
                        <li>Export PDF</li>
                        <li>Support email</li>
                        <li>30 jours d'essai gratuit</li>
                    </ul>
                </div>
                <button class="btn-select" onclick="selectPlan('pro')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 6L9 17l-5-5" />
                    </svg>
                    Choisir Pro - 9€/mois
                </button>
            </div>

            <!-- Plan BUSINESS -->
            <div class="plan-card business recommended">
                <div class="plan-badge">Recommandé</div>
                <div class="plan-card-header">
                    <h2>BUSINESS</h2>
                    <div class="plan-price">
                        <span class="amount">29€</span>
                        <span class="period">/mois</span>
                    </div>
                    <p>Pour les entreprises en croissance</p>
                </div>
                <div class="plan-features">
                    <ul>
                        <li>20 sites maximum</li>
                        <li>1M de visites/mois</li>
                        <li>Stats temps réel</li>
                        <li>API complète</li>
                        <li>Support prioritaire</li>
                        <li>30 jours d'essai gratuit</li>
                    </ul>
                </div>
                <button class="btn-select" onclick="selectPlan('business')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 6L9 17l-5-5" />
                    </svg>
                    Choisir Business - 29€/mois
                </button>
            </div>
        </div>

        <!-- Formulaire de checkout -->
        <div id="checkoutForm" class="checkout-form-container" style="display: none;">
            <div class="checkout-form-header">
                <h3>Finalisez votre mise à niveau</h3>
                <p>Un dernier pas pour débloquer toutes les fonctionnalités</p>
            </div>
            <form id="upgradeForm" class="checkout-form">
                <input type="hidden" name="new_plan" id="newPlanInput">
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
            <div id="loading" class="loading-state" style="display: none;">
                <div class="loading-spinner"></div>
                <p>Création de votre checkout sécurisé...</p>
            </div>
        </div>
    </div>

    <script>
        function selectPlan(plan) {
            document.getElementById('newPlanInput').value = plan;
            document.getElementById('checkoutForm').style.display = 'block';
            document.getElementById('checkoutForm').scrollIntoView({
                behavior: 'smooth'
            });
        }

        function cancelCheckout() {
            document.getElementById('checkoutForm').style.display = 'none';
        }

        document.getElementById('upgradeForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = e.target;
            const formData = new FormData(form);
            const loading = document.getElementById('loading');

            loading.style.display = 'block';
            form.style.display = 'none';

            fetch('upgrade.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Rediriger vers le checkout Lemon Squeezy
                        window.location.href = data.checkout_url;
                    } else {
                        alert('Erreur: ' + data.message);
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