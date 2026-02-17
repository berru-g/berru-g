<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Vérifie si connecté
if (!Auth::isLoggedIn()) {
    // Redirige UNIQUEMENT si pas connecté
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);

// 2. Récupérer les sites de l'utilisateur // dégager query pour prepare 
$stmt = $pdo->prepare("SELECT * FROM user_sites WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$userSites = $stmt->fetchAll();

// 3. Gérer la création de site (TOUJOURS disponible, pas seulement si empty($userSites))
// === CORRECTION DE LA LOGIQUE DE CRÉATION DE SITE ===

// 1. TOUJOURS traiter la création de site (même si déjà des sites)
if (isset($_POST['create_site'])) {
    // AJOUTER LA VÉRIFICATION DE LIMITE ICI
    $stmt = $pdo->prepare("SELECT plan, sites_limit FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $userPlan = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_sites WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $siteCount = $stmt->fetchColumn();

    // Vérifier la limite
    if ($siteCount >= ($userPlan['sites_limit'] ?? 1)) {
        $_SESSION['limit_reached'] = true;
        $_SESSION['error_message'] = "Limite atteinte pour le plan " . strtoupper($userPlan['plan'] ?? 'free');
        header('Location: dashboard.php?create=site');
        exit();
    }

    // Créer le site
    $tracking_code = 'SP_' . bin2hex(random_bytes(4));
    $public_key = bin2hex(random_bytes(32));

    $stmt = $pdo->prepare("INSERT INTO user_sites (user_id, site_name, domain, tracking_code, public_key) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $_POST['site_name'], $_POST['site_domain'], $tracking_code, $public_key]);

    header('Location: dashboard.php?site_created=' . $tracking_code);
    exit();
}

// 2. Si pas de sites, FORCER l'affichage du formulaire
// 2. Si pas de sites ET qu'on n'est pas déjà sur la page de création
// 2. Si pas de sites, afficher le formulaire de création
if (empty($userSites)) {
    // Afficher directement le formulaire, pas de redirection
    $showCreateForm = true;
}

/* 3. Site sélectionné (depuis GET ou premier site)
$selectedSiteId = $_GET['site_id'] ?? $userSites[0]['id'];
    ?>
        <h2>Créez votre premier site</h2>
        <form method="POST">
            <input type="text" name="site_name" placeholder="Nom du site" required>
            <input type="text" name="site_domain" placeholder="mondomaine.com" required>
            <button type="submit" name="create_site">Créer</button>
        </form>
    <?php
    exit();
}*/

// 4. Site sélectionné (depuis GET ou premier site)
$selectedSiteId = $_GET['site_id'] ?? $userSites[0]['id'];
// RÉCUPÉRER LES DONNÉES UTILISATEUR POUR LA SIDEBAR
$stmt = $pdo->prepare("SELECT plan, sites_limit FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$userPlan = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM user_sites WHERE user_id = ?");
$stmt->execute([$user_id]);
$siteCount = $stmt->fetchColumn();
// === SÉCURITÉ : Vérifier que l'utilisateur possède ce site ===
$stmt = $pdo->prepare("SELECT id FROM user_sites WHERE id = ? AND user_id = ?");
$stmt->execute([$selectedSiteId, $user_id]);
if (!$stmt->fetch()) {
    die("Accès interdit à ce site");
}

// === PERIOD ===
$period = isset($_GET['period']) ? $_GET['period'] : 365; // Par défaut 1 an
$dateFilter = date('Y-m-d H:i:s', strtotime("-$period days"));

// REPERER LE DASHOARD : Ajout de WHERE site_id = ?
$stmt = $pdo->prepare("SELECT COUNT(*) FROM smart_pixel_tracking WHERE site_id = ?");
$stmt->execute([$selectedSiteId]);
$totalViews = $stmt->fetchColumn();

// REPERER L'IP
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT ip_address) FROM smart_pixel_tracking WHERE site_id = ?");
$stmt->execute([$selectedSiteId]);
$uniqueVisitors = $stmt->fetchColumn();

// REPERER LES SOURCES
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT ip_address) 
    FROM smart_pixel_tracking 
    WHERE site_id = ? AND timestamp >= ?
");
$stmt->execute([$selectedSiteId, $dateFilter]);
$uniqueVisitorsPeriod = $stmt->fetchColumn();

// REPERER LES SOURCES
$stmt = $pdo->prepare("
    SELECT source, COUNT(*) as count 
    FROM smart_pixel_tracking 
    WHERE site_id = ? AND timestamp >= ?
    GROUP BY source 
    ORDER BY count DESC
");
$stmt->execute([$selectedSiteId, $dateFilter]);
$sources = $stmt->fetchAll();

// REPERER LES PAGES
$stmt = $pdo->prepare("
    SELECT page_url, COUNT(*) as views 
    FROM smart_pixel_tracking 
    WHERE site_id = ? AND page_url != 'direct' AND timestamp >= ?
    GROUP BY page_url 
    ORDER BY views DESC 
    LIMIT 10
");
$stmt->execute([$selectedSiteId, $dateFilter]);
$topPages = $stmt->fetchAll();

// REPERER LES PAYS
$stmt = $pdo->prepare("
    SELECT country, COUNT(*) as visits 
    FROM smart_pixel_tracking 
    WHERE site_id = ? AND timestamp >= ?
    GROUP BY country 
    ORDER BY visits DESC 
    LIMIT 10
");
$stmt->execute([$selectedSiteId, $dateFilter]);
$countries = $stmt->fetchAll();

// REPERER LES APPAREILS
$stmt = $pdo->prepare("
    SELECT 
        CASE 
            WHEN user_agent LIKE '%Mobile%' THEN 'Mobile'
            WHEN user_agent LIKE '%Tablet%' THEN 'Tablet'
            ELSE 'Desktop'
        END as device,
        COUNT(*) as count
    FROM smart_pixel_tracking
    WHERE site_id = ? AND timestamp >= ?
    GROUP BY device
    ORDER BY count DESC
");
$stmt->execute([$selectedSiteId, $dateFilter]);
$devices = $stmt->fetchAll();

// REPERER LES NAVIGATEURS
$stmt = $pdo->prepare("
    SELECT 
        CASE 
            WHEN user_agent LIKE '%Chrome%' THEN 'Chrome'
            WHEN user_agent LIKE '%Firefox%' THEN 'Firefox'
            WHEN user_agent LIKE '%Safari%' THEN 'Safari'
            WHEN user_agent LIKE '%Edge%' THEN 'Edge'
            ELSE 'Other'
        END as browser,
        COUNT(*) as count
    FROM smart_pixel_tracking
    WHERE site_id = ? AND timestamp >= ?
    GROUP BY browser
    ORDER BY count DESC
");
$stmt->execute([$selectedSiteId, $dateFilter]);
$browsers = $stmt->fetchAll();

// REPERER LES STATISTIQUES JOURNALIÈRES
$stmt = $pdo->prepare("
    SELECT 
        DATE(timestamp) as date,
        COUNT(*) as visits,
        COUNT(DISTINCT ip_address) as unique_visitors
    FROM smart_pixel_tracking
    WHERE site_id = ? AND timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(timestamp)
    ORDER BY date ASC
");
$stmt->execute([$selectedSiteId]);
$dailyStats = $stmt->fetchAll();

// REPERER LES CLICS AVEC DATA
$stmt = $pdo->prepare("
    SELECT click_data
    FROM smart_pixel_tracking
    WHERE site_id = ? AND click_data IS NOT NULL AND click_data != '' AND timestamp >= ?
    LIMIT 100
");
$stmt->execute([$selectedSiteId, $dateFilter]);
$clickData = $stmt->fetchAll();

// REPREER LES SESSIONS
$stmt = $pdo->prepare("
    SELECT 
        session_id,
        COUNT(*) as page_views,
        MIN(timestamp) as first_visit,
        MAX(timestamp) as last_visit
    FROM smart_pixel_tracking
    WHERE site_id = ? AND session_id != '' AND timestamp >= ?
    GROUP BY session_id
    ORDER BY page_views DESC
    LIMIT 10
");
$stmt->execute([$selectedSiteId, $dateFilter]);
$sessionData = $stmt->fetchAll();

// RECUP LES DONNÉES DÉTAILLÉES
$stmt = $pdo->prepare("
    SELECT 
        ip_address,
        country,
        city,
        page_url,
        timestamp,
        user_agent,
        source,
        session_id
    FROM smart_pixel_tracking
    WHERE site_id = ? AND timestamp >= ?
    ORDER BY timestamp DESC
    LIMIT 250
");
$stmt->execute([$selectedSiteId, $dateFilter]);
$detailedData = $stmt->fetchAll();

// Calcul du temps moyen de session (identique)
$avgSessionTime = 0;
if (count($sessionData) > 0) {
    $totalSessionTime = 0;
    foreach ($sessionData as $session) {
        $first = strtotime($session['first_visit']);
        $last = strtotime($session['last_visit']);
        $totalSessionTime += ($last - $first);
    }
    $avgSessionTime = round($totalSessionTime / count($sessionData) / 60, 1);
}
// MAP
function getCountryCodeSimple($countryName)
{
    $countryMap = [
        'france' => 'FR',
        'united states' => 'US',
        'germany' => 'DE',
        'united kingdom' => 'GB',
        'canada' => 'CA',
        'australia' => 'AU',
        'japan' => 'JP',
        'china' => 'CN',
        'brazil' => 'BR',
        'india' => 'IN',
        'italy' => 'IT',
        'spain' => 'ES',
        'netherlands' => 'NL',
        'belgium' => 'BE',
        'switzerland' => 'CH',
        'portugal' => 'PT',
        'russia' => 'RU',
        'mexico' => 'MX',
        'south korea' => 'KR',
        'singapore' => 'SG',
        'usa' => 'US',
        'uk' => 'GB',
    ];

    $normalized = strtolower(trim($countryName));
    return $countryMap[$normalized] ?? null;
}

?>
<!DOCTYPE html>
<html lang="fr">
<!-- 
    ╔══════════════════════════════════════════════════╗
    ║                       ██                         ║
    ╠══════════════════════════════════════════════════╣
    ║  Project      : Analytics Souverains             ║
    ║  First commit : February 27, 2025                ║ 
    ║  Version      : 2.1.0                            ║
    ║  Copyright    : 2025 https://github.com/berru-g/ ║
    ╚══════════════════════════════════════════════════╝
-->

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Pixel Analytics - Tableau de bord</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <link rel="stylesheet" href="../assets/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

    <!-- amCharts 5 (version complète sans modules séparés) -->
    <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/map.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/geodata/worldLow.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/gantt.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
</head>

<body>

    <!-- === Sidebar redessinée - Design moderne & rétractable === -->
    <div class="sidebar-wrapper">
        <div class="sidebar <?= isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true' ? 'collapsed' : '' ?>" id="sidebar">
            <!-- En-tête de la sidebar -->
            <div class="sidebar-header">
                <div class="logo-container">
                    <!--<div class="logo-icon">◰</div>-->
                    <div class="logo-text">
                        <h3><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg></h3>
                        <small class="user-email"><?= htmlspecialchars($_SESSION['user_email'] ?? 'Utilisateur') ?></small>
                    </div>
                </div>
                <button class="sidebar-toggle" id="sidebarToggle" title="Réduire/Étendre">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="2" y="2" width="20" height="20" rx="2" stroke-linejoin="round" />
                        <path stroke-linecap="round" d="M6.8 2V22" />
                    </svg>
                </button>
            </div>

            <!-- Carte d'abonnement -->
            <div class="subscription-card">
                <div class="subscription-header">
                    <small class="subscription-label">Abonnement</small>
                    <div class="subscription-plan">
                        <span class="plan-badge"><?= strtoupper($userPlan['plan'] ?? 'free') ?></span>
                        <?php if (($userPlan['plan'] ?? 'free') == 'free'): ?>
                            <a href="upgrade.php" class="upgrade-link">Upgrade</a>
                        <?php else: ?>
                            <a href="upgrade.php?manage=1" class="manage-link">Gérer</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="subscription-details">
                    <div class="site-count">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                            <polyline points="9 22 9 12 15 12 15 22" />
                        </svg>
                        <span>Sites: <?= $siteCount ?? 0 ?>/<?= $userPlan['sites_limit'] ?? 1 ?></span>
                    </div>
                    <?php if (($userPlan['plan'] ?? 'free') != 'free'): ?>
                        <div class="next-payment">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10" />
                                <polyline points="12 6 12 12 16 14" />
                            </svg>
                            <span>Paiement: <?= date('d/m/Y', strtotime('+1 month')) ?></span>
                        </div>
                    <?php endif; ?>
                </div>


            </div>


            <!-- Section des sites -->
            <div class="sites-section">
                <div class="section-header">
                    <h4>Mes Sites</h4>
                    <button class="add-site-btn" onclick="window.location.href='?create=site'">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19" />
                            <line x1="5" y1="12" x2="19" y2="12" />
                        </svg>
                        <span>Ajouter</span>
                    </button>
                </div>

                <div class="sites-list">
                    <?php foreach ($userSites as $site): ?>
                        <a href="?site_id=<?= $site['id'] ?>"
                            class="site-item <?= $selectedSiteId == $site['id'] ? 'active' : '' ?>"
                            title="<?= htmlspecialchars($site['domain']) ?>">
                            <div class="site-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                                </svg>
                            </div>
                            <div class="site-info">
                                <strong><?= htmlspecialchars($site['site_name']) ?></strong>
                                <small><?= htmlspecialchars($site['domain']) ?></small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Code snippet d'intégration -->
            <?php
            $currentSite = array_filter($userSites, fn($s) => $s['id'] == $selectedSiteId);
            $currentSite = reset($currentSite);
            if ($currentSite): ?>
                <div class="integration-card">
                    <div class="integration-header">
                        <h4>Code d'intégration</h4>
                        <button class="copy-btn" onclick="copyCode()" title="Copier le code">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                            </svg>
                        </button>
                    </div>
                    <code class="integration-code">
                        &lt;!-- à intégrer dans la balise &lt;head&gt; de votre site --&gt;
                        &lt;script data-sp-id="<?= $currentSite['tracking_code'] ?>" src="<?= APP_URL ?>tracker.js" async&gt;&lt;/script&gt;
                    </code>
                </div>
            <?php endif; ?>

            <!-- Bouton de déconnexion -->
            <div class="logout-section">

                <button class="logout-btn" onclick="confirmParametre()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                    </svg>
                    <span>Parametre</span>
                </button>
                <br>
                <button class="logout-btn" onclick="confirmLogout()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <polyline points="16 17 21 12 16 7" />
                        <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                    <span>Déconnexion</span>
                </button>
            </div>
        </div>

        <!-- Overlay pour mobile -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Bouton menu mobile -->
        <button class="mobile-menu-btn" id="mobileMenuBtn">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="2" y="2" width="20" height="20" rx="2" stroke-linejoin="round" />
                <path stroke-linecap="round" d="M6.8 2V22" />
            </svg>
        </button>
    </div>

    <!-- === MAIN CONTENT === -->
    <div class="main-content">
        <?php if (isset($_GET['create']) || (isset($showCreateForm) && $showCreateForm)): ?>
            <?php
            // Récupérer les infos de limite si elles existent
            $limitReached = $_SESSION['limit_reached'] ?? false;
            $errorMessage = $_SESSION['error_message'] ?? '';

            // Nettoyer la session après utilisation
            unset($_SESSION['limit_reached'], $_SESSION['error_message']);
            ?>

            <?php if ($limitReached): ?>
                <!-- Afficher les options d'upgrade (refaire css car ce fdp ia est teubé) -->
                <div style="background: var(--bg-color); border: 1px solid #ffeaa7; color: var(--text-color); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <h3>Limite atteinte</h3>
                    <p><?= $errorMessage ?></p>

                    <div style="margin-top: 15px; padding: 15px; background: white; border-radius: 5px;">
                        <h4>Passez à un plan supérieur</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 15px;">
                            <!-- Plan PRO -->
                            <div style="border: 2px solid var(--primary-color); border-radius: 8px; padding: 15px;">
                                <h3>PRO</h3>
                                <p><strong>9€/mois</strong></p>
                                <ul style="padding-left: 20px;">
                                    <li>12 sites maximum</li>
                                    <li>10 000 visites/mois</li>
                                    <li>Stats avancées</li>
                                    <li>Export PDF</li>
                                </ul>
                                <button onclick="showUpgradeForm('pro')" style="width: 100%; padding: 10px; background: var(--primary-color); color: var(--text-color); border: none; border-radius: 5px; cursor: pointer;">
                                    Choisir PRO
                                </button>
                            </div>

                            <!-- Plan BUSINESS -->
                            <div style="border: 2px solid #4ecdc4; border-radius: 8px; padding: 15px;">
                                <h3>BUSINESS</h3>
                                <p><strong>29€/mois</strong></p>
                                <ul style="padding-left: 20px;">
                                    <li>50 sites maximum</li>
                                    <li>1M de visites/mois</li>
                                    <li>Support prioritaire</li>
                                    <li>API complète</li>
                                </ul>
                                <button onclick="showUpgradeForm('business')" style="width: 100%; padding: 10px; background: #4ecdc4; color: var(--text-color); border: none; border-radius: 5px; cursor: pointer;">
                                    Choisir BUSINESS
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulaire caché pour l'upgrade -->
                <div id="upgradeForm" style="display: none; background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px;">
                    <h3>Mise à niveau du plan</h3>
                    <form id="upgradeFormContent">
                        <input type="hidden" name="new_plan" id="newPlanInput">
                        <div style="margin: 15px 0;">
                            <label>Email de facturation</label>
                            <input type="email" name="billing_email" required style="width: 100%; padding: 10px;">
                        </div>
                        <button type="button" onclick="submitUpgrade()" style="padding: 10px 20px; background: var(--primary-color); color: var(--text-color); border: none; border-radius: 5px; cursor: pointer;">
                            Confirmer la mise à niveau
                        </button>
                    </form>
                </div>

            <?php else: ?>
                <!-- Formulaire de création normal -->
                <div style="background-color: var(--bg-color); color: var(--text-color); text-align: center; padding: 2rem; max-width: 500px; margin: 0 auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h2 style="margin-bottom: 1.5rem; font-size: 1.5rem; font-weight: 600;">
                        <i class="fa-regular fa-square-plus"></i>
                        <?= isset($_GET['first']) ? 'Créez votre premier site' : 'Ajouter un nouveau site' ?>
                    </h2>

                    <form method="POST" class="login-form" style="display: flex; flex-direction: column; gap: 1rem;">
                        <input type="text" name="site_name" placeholder="Nom du site" required
                            style="padding: 0.75rem 1rem; border: 1px solid grey; border-radius: 4px; font-size: 1rem;">

                        <input type="text" name="site_domain" placeholder="mondomaine.com" required
                            style="padding: 0.75rem 1rem; border: 1px solid grey; border-radius: 4px; font-size: 1rem;">

                        <button type="submit" name="create_site" class="btn-primary"
                            style="background-color: var(--primary-color); color: var(--text-color); border: none; padding: 0.75rem 1.5rem; border-radius: 4px; font-size: 1rem; font-weight: 500; cursor: pointer; margin-top: 0.5rem;">
                            Créer le site
                        </button>
                    </form>
                </div>

            <?php endif; ?>
        <?php else: ?>
            <!-- Header existant -->
            <header>
                <div class="container">
                    <div class="header-content">
                        <h1>Smart Pixel Analytics</h1>
                        <div style="color:grey;"></div>
                        <div class="period-filter">
                            <span>Période :</span>
                            <select id="periodSelect" onchange="changePeriod(this.value)">
                                <option value="7" <?= $period == 7 ? 'selected' : '' ?>>7 jours</option>
                                <option value="30" <?= $period == 30 ? 'selected' : '' ?>>30 jours</option>
                                <option value="90" <?= $period == 90 ? 'selected' : '' ?>>90 jours</option>
                                <option value="365" <?= $period == 365 ? 'selected' : '' ?>>1 an</option>
                            </select>
                        </div>
                    </div>
                </div>
            </header>
            <!-- CONTAINER PRINCIPAL DU DASHBOARD -->
            <div class="container">
                <div class="dashboard-tabs">
                    <div class="tabs">
                        <div class="tab active" onclick="openTab('overview')">Aperçu</div>
                        <div class="tab" onclick="openTab('geography')">Géographie</div>
                        <div class="tab" onclick="openTab('traffic')">Trafic</div>
                        <div class="tab" onclick="openTab('devices')">Appareils</div>
                        <div class="tab" onclick="openTab('content')">Contenu</div>
                        <div class="tab" onclick="openTab('sessions')">Sessions</div>
                        <div class="tab" onclick="openTab('details')">Détails</div>
                        <div class="tab" onclick="openTab('insights')">Insights</div>
                        <!--<div class="tab" onclick="openTab('AgendaReco')">Agenda</div>-->
                    </div>

                    <!-- ONGLET APERÇU -->
                    <div id="overview" class="tab-content active">
                        <div class="stats-grid">
                            <div class="stat-card">
                                <h3>Vues totales</h3>
                                <div class="stat-value"><?= number_format($totalViews) ?></div>
                                <div class="stat-change positive">+12%</div>
                            </div>
                            <div class="stat-card">
                                <h3>Visiteurs uniques</h3>
                                <div class="stat-value"><?= number_format($uniqueVisitorsPeriod) ?></div>
                                <div class="stat-change positive">+8%</div>
                            </div>
                            <div class="stat-card">
                                <h3>Pages vues/session</h3>
                                <div class="stat-value">2.4</div>
                                <div class="stat-change negative">-3%</div>
                            </div>
                            <div class="stat-card">
                                <h3>Temps moyen</h3>
                                <div class="stat-value"><?= $avgSessionTime ?> min</div>
                                <div class="stat-change positive">+5%</div>
                            </div>
                        </div>

                        <div class="chart-container">
                            <h3 class="chart-title">Évolution du trafic (7 derniers jours)</h3>
                            <canvas id="trafficChart" height="80"></canvas>
                        </div>

                        <!-- Section 2: Corrélation Trafic & Tendances -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Analyse des Tendances</h3>
                            </div>
                            <div class="card-body">

                                <div class="insight-tip">
                                    <strong>Insight :</strong>
                                    <?php
                                    if (count($dailyStats) >= 2) {
                                        $firstDay = $dailyStats[0]['visits'];
                                        $lastDay = end($dailyStats)['visits'];
                                        $growth = $firstDay > 0 ? (($lastDay - $firstDay) / $firstDay) * 100 : 0;

                                        if ($growth > 20) {
                                            echo "Votre trafic a augmenté de <strong>" . round($growth) . "%</strong> cette semaine ! Excellente progression.";
                                        } elseif ($growth > 0) {
                                            echo "Votre trafic progresse doucement (+" . round($growth) . "%). Continuez vos efforts !";
                                        } else {
                                            echo "Votre trafic est stable. Pensez à lancer de nouvelles campagnes pour stimuler la croissance.";
                                        }
                                    } else {
                                        echo "Collectez plus de données pour obtenir des insights détaillés sur vos tendances.";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Points d'Amélioration</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-container">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Métrique</th>
                                                <th>Valeur actuelle</th>
                                                <th>Cible idéale</th>
                                                <th>Statut</th>
                                                <th>Action recommandée</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Calculer les métriques
                                            $avgPagesPerSession = $sessionData ? array_sum(array_column($sessionData, 'page_views')) / count($sessionData) : 0;
                                            $avgSessionTime = $avgSessionTime; // Déjà calculé
                                            $bounceRateEstimate = 100 - ($avgPagesPerSession > 1 ? 70 : 40); // Estimation simplifiée

                                            // Liste des métriques à analyser
                                            $metrics = [
                                                [
                                                    'name' => 'Pages/Session',
                                                    'current' => round($avgPagesPerSession, 1),
                                                    'target' => '3.0+',
                                                    'status' => $avgPagesPerSession >= 2.5 ? 'good' : ($avgPagesPerSession >= 1.5 ? 'average' : 'poor'),
                                                    'action' => $avgPagesPerSession >= 2.5 ?
                                                        'Excellent engagement !' :
                                                        'Ajoutez des liens internes et du contenu intéressant.'
                                                ],
                                                [
                                                    'name' => 'Temps moyen',
                                                    'current' => $avgSessionTime . ' min',
                                                    'target' => '3+ min',
                                                    'status' => $avgSessionTime >= 3 ? 'good' : ($avgSessionTime >= 1.5 ? 'average' : 'poor'),
                                                    'action' => $avgSessionTime >= 3 ?
                                                        'Temps d\'engagement optimal.' :
                                                        'Améliorez la qualité du contenu pour retenir les visiteurs.'
                                                ],
                                                [
                                                    'name' => 'Taux de rebond (est.)',
                                                    'current' => round($bounceRateEstimate) . '%',
                                                    'target' => '< 40%',
                                                    'status' => $bounceRateEstimate < 40 ? 'good' : ($bounceRateEstimate < 60 ? 'average' : 'poor'),
                                                    'action' => $bounceRateEstimate < 40 ?
                                                        'Très bon taux de rétention.' :
                                                        'Optimisez les pages d\'atterrissage et le contenu.'
                                                ]
                                            ];

                                            foreach ($metrics as $metric) {
                                                $statusClass = $metric['status'] == 'good' ? 'tip-success' : ($metric['status'] == 'average' ? 'tip-info' : 'tip-warning');

                                                echo '<tr>';
                                                echo '<td>' . $metric['name'] . '</td>';
                                                echo '<td><strong>' . $metric['current'] . '</strong></td>';
                                                echo '<td>' . $metric['target'] . '</td>';
                                                echo '<td><span class="' . $statusClass . '">' .
                                                    ($metric['status'] == 'good' ? '✅ Bon' : ($metric['status'] == 'average' ? '⚠️ Moyen' : '❌ À améliorer')) .
                                                    '</span></td>';
                                                echo '<td>' . htmlspecialchars($metric['action']) . '</td>';
                                                echo '</tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="data-grid compact">
                            <div class="chart-container small">
                                <h3 class="chart-title">Sources de trafic</h3>
                                <canvas id="sourcesChart"></canvas>
                            </div>

                            <div class="chart-container small">
                                <h3 class="chart-title">Appareils utilisés</h3>
                                <canvas id="devicesChart"></canvas>
                            </div>

                            <div class="chart-container small">
                                <h3 class="chart-title">Top pays</h3>
                                <canvas id="countriesOverviewChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <!-- LISTE DES ONGLETS -->
                    <!-- ONGLET TRAFIC -->
                    <div id="traffic" class="tab-content">
                        <div class="data-grid">
                            <div class="chart-container">
                                <h3 class="chart-title">Sources de trafic</h3>
                                <canvas id="sourcesTrafficChart" height="200"></canvas>
                            </div>

                            <div class="chart-container">
                                <h3 class="chart-title">Navigateurs utilisés</h3>
                                <canvas id="browsersChart" height="200"></canvas>
                            </div>
                        </div>

                        <div class="chart-container">
                            <h3 class="chart-title">Détail des sources</h3>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Source</th>
                                        <th>Visites</th>
                                        <th>Pourcentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sources as $source): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($source['source']) ?></td>
                                            <td><?= number_format($source['count']) ?></td>
                                            <td><?= round(($source['count'] / $uniqueVisitorsPeriod) * 100, 1) ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>


                    <!-- ONGLET GÉOGRAPHIE -->
                    <div id="geography" class="tab-content">
                        <div class="data-grid">
                            <!-- Colonne gauche : Map -->
                            <div class="chart-container">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                    <h3 class="chart-title">Carte mondiale des visites</h3>
                                    <!--<div class="map-controls">
                                        <button onclick="zoomIn()" class="map-btn" title="Zoom avant">
                                            <i class="fas fa-search-plus"></i>
                                        </button>
                                        <button onclick="zoomOut()" class="map-btn" title="Zoom arrière">
                                            <i class="fas fa-search-minus"></i>
                                        </button>
                                        <button onclick="resetMap()" class="map-btn" title="Réinitialiser">
                                            <i class="fas fa-home"></i>
                                        </button>
                                    </div>-->
                                </div>
                                <div id="mapChart" style="width: 100%; height: 400px; background: var(--bg-color); border-radius: 8px;"></div>
                                <div id="mapLegend" style="margin-top: 10px; text-align: center; font-size: 12px; color: #666;">
                                    <span style="background: #ff6b8b; width: 12px; height: 12px; display: inline-block; border-radius: 50%; margin-right: 5px;"></span> Haut
                                    <span style="margin: 0 10px;">→</span>
                                    <span style="background: #6772e5; width: 12px; height: 12px; display: inline-block; border-radius: 50%; margin-right: 5px;"></span> Bas
                                </div>
                            </div>

                            <!-- Colonne droite : Top pays -->
                            <div class="chart-container">
                                <h3 class="chart-title">Top pays par visites</h3>
                                <canvas id="countriesChart" height="300"></canvas>
                            </div>
                        </div>

                        <!-- Tableau en dessous -->
                        <div class="chart-container" style="margin-top: 30px;">
                            <h3 class="chart-title">Répartition géographique détaillée</h3>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Pays</th>
                                        <th>Visites</th>
                                        <th>Part du trafic</th>
                                        <th>Code pays</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($countries as $country):
                                        $countryCode = getCountryCodeSimple($country['country']);
                                    ?>
                                        <tr>
                                            <td>
                                                <?php if ($countryCode): ?>
                                                    <span class="flag-icon" style="margin-right: 8px;"><?= $countryCode ?></span>
                                                <?php endif; ?>
                                                <?= htmlspecialchars($country['country']) ?>
                                            </td>
                                            <td><?= number_format($country['visits']) ?></td>
                                            <td><?= round(($country['visits'] / $uniqueVisitorsPeriod) * 100, 1) ?>%</td>
                                            <td><code><?= $countryCode ?: 'N/A' ?></code></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    </div>

                    <!-- ONGLET APPAREILS -->
                    <div id="devices" class="tab-content">
                        <div class="data-grid">
                            <div class="chart-container">
                                <h3 class="chart-title">Types d'appareils</h3>
                                <canvas id="deviceTypesChart" height="200"></canvas>
                            </div>

                            <div class="chart-container">
                                <h3 class="chart-title">Navigateurs</h3>
                                <canvas id="browserTypesChart" height="200"></canvas>
                            </div>
                        </div>

                        <div class="data-grid">
                            <div class="chart-container">
                                <h3 class="chart-title">Détail des appareils</h3>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Appareil</th>
                                            <th>Visites</th>
                                            <th>Pourcentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($devices as $device): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($device['device']) ?></td>
                                                <td><?= number_format($device['count']) ?></td>
                                                <td><?= round(($device['count'] / $uniqueVisitorsPeriod) * 100, 1) ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="chart-container">
                                <h3 class="chart-title">Détail des navigateurs</h3>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Navigateur</th>
                                            <th>Utilisations</th>
                                            <th>Pourcentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($browsers as $browser): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($browser['browser']) ?></td>
                                                <td><?= number_format($browser['count']) ?></td>
                                                <td><?= round(($browser['count'] / $uniqueVisitorsPeriod) * 100, 1) ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ONGLET CONTENU -->
                    <div id="content" class="tab-content">
                        <div class="chart-container">
                            <h3 class="chart-title">Pages les plus populaires</h3>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Page</th>
                                        <th>Vues</th>
                                        <th>Pourcentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topPages as $page): ?>
                                        <tr>
                                            <td class="url-truncate" title="<?= htmlspecialchars($page['page_url']) ?>">
                                                <?= htmlspecialchars($page['page_url']) ?>
                                            </td>
                                            <td><?= number_format($page['views']) ?></td>
                                            <td><?= round(($page['views'] / $uniqueVisitorsPeriod) * 100, 1) ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if (count($clickData) > 0): ?>
                            <div class="chart-container">
                                <h3 class="chart-title"><i class="fa-solid fa-arrow-pointer"></i> Données de clics récentes</h3>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Élément</th>
                                            <th>Texte</th>
                                            <th>Position</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $clickCount = 0;
                                        foreach ($clickData as $click):
                                            if ($clickCount >= 10)
                                                break;
                                            $data = json_decode($click['click_data'], true);
                                            if (is_array($data)):
                                        ?>
                                                <tr>
                                                    <td><span class="badge badge-primary"><?= htmlspecialchars($data['element']) ?></span>
                                                    </td>
                                                    <td><?= htmlspecialchars(substr($data['text'], 0, 30)) . (strlen($data['text']) > 30 ? '...' : '') ?>
                                                    </td>
                                                    <td><?= $data['x'] ?>x<?= $data['y'] ?></td>
                                                </tr>
                                        <?php
                                                $clickCount++;
                                            endif;
                                        endforeach;
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- ONGLET SESSIONS -->
                    <div id="sessions" class="tab-content">
                        <div class="chart-container">
                            <h3 class="chart-title"><i class="fa-solid fa-user-clock"></i> Sessions les plus actives</h3>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID Session</th>
                                        <th>Pages vues</th>
                                        <th>Première visite</th>
                                        <th>Dernière visite</th>
                                        <th>Durée</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sessionData as $session):
                                        $first = strtotime($session['first_visit']);
                                        $last = strtotime($session['last_visit']);
                                        $duration = round(($last - $first) / 60, 1); // en minutes
                                    ?>
                                        <tr>
                                            <td><?= substr($session['session_id'], 0, 8) ?>...</td>
                                            <td><?= $session['page_views'] ?></td>
                                            <td><?= date('H:i', $first) ?></td>
                                            <td><?= date('H:i', $last) ?></td>
                                            <td><?= $duration ?> min</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ONGLET DÉTAILS -->

                    <div id="details" class="tab-content">
                        <div class="chart-container">
                            <h3 class="chart-title">Détails des visites récentes (250 dernières)</h3>
                            <table class="data-table">
                                <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>IP</th>
                                                <th>Pays</th>
                                                <th>Ville</th>
                                                <th>Page visitée</th>
                                                <th>Heure</th>
                                                <th>Source</th>
                                                <th>Session</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($detailedData as $visit):
                                                $visitTime = strtotime($visit['timestamp']);
                                            ?>
                                                <tr>
                                                    <td class="ip-address"><?= htmlspecialchars($visit['ip_address']) ?></td>
                                                    <td><?= htmlspecialchars($visit['country']) ?></td>
                                                    <td><?= htmlspecialchars($visit['city']) ?></td>
                                                    <td class="url-truncate" title="<?= htmlspecialchars($visit['page_url']) ?>">
                                                        <?= htmlspecialchars($visit['page_url']) ?>
                                                    </td>
                                                    <td><?= date('H:i', $visitTime) ?></td>
                                                    <td><span
                                                            class="badge badge-primary"><?= htmlspecialchars($visit['source']) ?></span>
                                                    </td>
                                                    <td><?= substr($visit['session_id'], 0, 8) ?>...</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </table>
                        </div>
                    </div>

                    <!-- ONGLET INSIGHTS AVANCÉS ===== -->
                    <div id="insights" class="tab-content">
                        <div class="chart-container">
                            <!-- Section 1: Performance Marketing -->
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Performance Marketing & Recommendations</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-container">
                                        <table class="data-table">
                                            <thead>
                                                <tr>
                                                    <th>Page</th>
                                                    <th>Visites</th>
                                                    <th>% du trafic</th>
                                                    <th>Potentiel</th>
                                                    <th>Recommandation</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Récupérer les données nécessaires


                                                $totalVisits = 0;
                                                foreach ($topPages as $page) {
                                                    $totalVisits += $page['views'];
                                                }

                                                $insights = [];
                                                foreach ($topPages as $page) {
                                                    $pageUrl = $page['page_url'];
                                                    $views = $page['views'];

                                                    // Calcul du pourcentage
                                                    $percentage = $totalVisits > 0 ? ($views / $totalVisits) * 100 : 0;

                                                    // Évaluation du potentiel
                                                    $potential = '';
                                                    $tipClass = 'tip-info';

                                                    if ($percentage > 20) {
                                                        $potential = '<span class="tip-success">Très haute</span>';
                                                        $recommendation = "Page principale ! Optimisez la conversion avec des CTA clairs.";
                                                    } elseif ($percentage > 10) {
                                                        $potential = '<span class="tip-success">Haute</span>';
                                                        $recommendation = "Bon trafic. Testez des variantes de contenu pour améliorer l'engagement.";
                                                    } elseif ($percentage > 5) {
                                                        $potential = '<span class="tip-info">Moyenne</span>';
                                                        $recommendation = "Trafic modéré. Améliorez le SEO et les liens internes.";
                                                    } else {
                                                        $potential = '<span class="tip-warning">Faible</span>';
                                                        $recommendation = "Peu de trafic. Considérez une refonte ou une meilleure promotion.";
                                                    }

                                                    // Limiter la longueur de l'URL pour l'affichage
                                                    $displayUrl = strlen($pageUrl) > 40 ? substr($pageUrl, 0, 40) . '...' : $pageUrl;

                                                    echo '<tr>';
                                                    echo '<td title="' . htmlspecialchars($pageUrl) . '">' . htmlspecialchars($displayUrl) . '</td>';
                                                    echo '<td>' . number_format($views) . '</td>';
                                                    echo '<td>' . round($percentage, 1) . '%</td>';
                                                    echo '<td>' . $potential . '</td>';
                                                    echo '<td><span class="' . $tipClass . '">' . htmlspecialchars($recommendation) . '</span></td>';
                                                    echo '</tr>';
                                                }

                                                if (empty($topPages)) {
                                                    echo '<tr><td colspan="5" class="text-center">Aucune donnée disponible pour l\'analyse</td></tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 2: Corrélation Trafic & Tendances -->
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Analyse des Tendances</h3>
                                </div>
                                <div class="card-body">

                                    <?php
                                    // Préparer les données pour les tendances
                                    $trendLabels = [];
                                    $trendVisits = [];
                                    $trendUnique = [];

                                    foreach ($dailyStats as $stat) {
                                        $trendLabels[] = date('d/m', strtotime($stat['date']));
                                        $trendVisits[] = $stat['visits'];
                                        $trendUnique[] = $stat['unique_visitors'];
                                    }
                                    ?>
                                    <div class="insight-tip">
                                        <strong>Insight :</strong>
                                        <?php
                                        if (count($dailyStats) >= 2) {
                                            $firstDay = $dailyStats[0]['visits'];
                                            $lastDay = end($dailyStats)['visits'];
                                            $growth = $firstDay > 0 ? (($lastDay - $firstDay) / $firstDay) * 100 : 0;

                                            if ($growth > 20) {
                                                echo "Votre trafic a augmenté de <strong>" . round($growth) . "%</strong> cette semaine ! Excellente progression.";
                                            } elseif ($growth > 0) {
                                                echo "Votre trafic progresse doucement (+" . round($growth) . "%). Continuez vos efforts !";
                                            } else {
                                                echo "Votre trafic est stable. Pensez à lancer de nouvelles campagnes pour stimuler la croissance.";
                                            }
                                        } else {
                                            echo "Collectez plus de données pour obtenir des insights détaillés sur vos tendances.";
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 3: Analyse des Performances -->
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Points d'Amélioration</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-container">
                                        <table class="data-table">
                                            <thead>
                                                <tr>
                                                    <th>Métrique</th>
                                                    <th>Valeur actuelle</th>
                                                    <th>Cible idéale</th>
                                                    <th>Statut</th>
                                                    <th>Action recommandée</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Calculer les métriques
                                                $avgPagesPerSession = $sessionData ? array_sum(array_column($sessionData, 'page_views')) / count($sessionData) : 0;
                                                $avgSessionTime = $avgSessionTime; // Déjà calculé
                                                $bounceRateEstimate = 100 - ($avgPagesPerSession > 1 ? 70 : 40); // Estimation simplifiée

                                                // Liste des métriques à analyser
                                                $metrics = [
                                                    [
                                                        'name' => 'Pages/Session',
                                                        'current' => round($avgPagesPerSession, 1),
                                                        'target' => '3.0+',
                                                        'status' => $avgPagesPerSession >= 2.5 ? 'good' : ($avgPagesPerSession >= 1.5 ? 'average' : 'poor'),
                                                        'action' => $avgPagesPerSession >= 2.5 ?
                                                            'Excellent engagement !' :
                                                            'Ajoutez des liens internes et du contenu intéressant.'
                                                    ],
                                                    [
                                                        'name' => 'Temps moyen',
                                                        'current' => $avgSessionTime . ' min',
                                                        'target' => '3+ min',
                                                        'status' => $avgSessionTime >= 3 ? 'good' : ($avgSessionTime >= 1.5 ? 'average' : 'poor'),
                                                        'action' => $avgSessionTime >= 3 ?
                                                            'Temps d\'engagement optimal.' :
                                                            'Améliorez la qualité du contenu pour retenir les visiteurs.'
                                                    ],
                                                    [
                                                        'name' => 'Taux de rebond (est.)',
                                                        'current' => round($bounceRateEstimate) . '%',
                                                        'target' => '< 40%',
                                                        'status' => $bounceRateEstimate < 40 ? 'good' : ($bounceRateEstimate < 60 ? 'average' : 'poor'),
                                                        'action' => $bounceRateEstimate < 40 ?
                                                            'Très bon taux de rétention.' :
                                                            'Optimisez les pages d\'atterrissage et le contenu.'
                                                    ]
                                                ];

                                                foreach ($metrics as $metric) {
                                                    $statusClass = $metric['status'] == 'good' ? 'tip-success' : ($metric['status'] == 'average' ? 'tip-info' : 'tip-warning');

                                                    echo '<tr>';
                                                    echo '<td>' . $metric['name'] . '</td>';
                                                    echo '<td><strong>' . $metric['current'] . '</strong></td>';
                                                    echo '<td>' . $metric['target'] . '</td>';
                                                    echo '<td><span class="' . $statusClass . '">' .
                                                        ($metric['status'] == 'good' ? '✅ Bon' : ($metric['status'] == 'average' ? '⚠️ Moyen' : '❌ À améliorer')) .
                                                        '</span></td>';
                                                    echo '<td>' . htmlspecialchars($metric['action']) . '</td>';
                                                    echo '</tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ONGLET Agenda -->
                    <div id="AgendaReco" class="tab-content">
                        <h2>Calendrier de publication</h2>

                        <!-- Heatmap avec vos données réelles -->
                        <div class="chart-container">
                            <h3>Fréquentation par heure</h3>
                            <canvas id="heatmapChart" height="250"></canvas>
                        </div>

                        <!-- Planning Gantt -->
                        <div class="chart-container">
                            <h3>Planning recommandé</h3>
                            <div id="ganttChart"></div>
                        </div>

                        <!-- Top contenus - Utilise vos données existantes -->
                        <div class="chart-container">
                            <h3>Contenus performants</h3>
                            <div id="topPagesList"></div>
                        </div>
                    </div>

                    <!-- ONGLET AGENDA ? -->



                </div>
            </div>

        <?php endif; ?>

        <!-- La modale (cachée par défaut) -->
        <dialog id="modalParametres" class="settings-modal">
            <div class="modal-content">
                <h2>Besoin d'aide ?</h2>
                <p>Trouvez toutes les réponses dans la documentation complète ou accédez aux autres sections utiles :</p>

                <nav class="modal-nav">
                    <a href="../../doc/" class="modal-link primary" id="linkToDoc">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 12px; vertical-align: middle;">
                            <path d="M4 20V8a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z" />
                            <path d="M4 11h16" />
                        </svg>
                        <span class="subtitle"> Doc complète. Installation, configuration...</span>
                    </a>

                    <a href="#" class="modal-link"> <!-- adapte selon ton URL réelle -->
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg> 
                        <span class="subtitle">Gérer mon compte, mot de passe, sites connectés</span>
                    </a>

                    <a href="./contact/" class="modal-link"> <!-- adapte selon ton URL réelle -->
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="4" width="20" height="16" rx="2" />
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                        </svg> 
                        <span class="subtitle">Contact / Support, signaler un bug</span>
                    </a>
                </nav>

                <div class="modal-actions">
                    <button type="button" id="btnCancel" class="btn-cancel">Annuler</button>
                </div>
            </div>
        </dialog>
    </div>

    <!-- ASSISTANT PSEUDO IA EN COURS -->
    <div class="ai-assistant-container" id="aiAssistantContainer">
        <button class="ai-toggle-btn" onclick="toggleAIAssistant()">
            <span class="ai-icon"><i class="fa-regular fa-message"></i></span>
            <span class="ai-text"></span>
            <span class="ai-badge">NEW</span>
        </button>

        <div class="ai-panel" id="aiPanel">
            <div class="ai-header">
                <div class="ai-title">
                    <!--<span class="ai-avatar">🫡</span>-->
                    <h3>Smart Assistant</h3>
                    <small>- fonction en developpement -</small>
                </div>
                <button class="ai-close" onclick="toggleAIAssistant()">×</button>
            </div>

            <div class="ai-conversation" id="aiConversation">
                <!-- Messages seront ajoutés ici -->
            </div>

            <div class="ai-input-area">
                <div class="ai-quick-questions">
                    <button class="quick-question" onclick="askAI('Quelle est ma page la plus performante ?')">
                        📈 Top pages
                    </button>
                    <button class="quick-question" onclick="askAI('Comment améliorer mon taux de conversion ?')">
                        💰 Optimisation
                    </button>
                    <button class="quick-question" onclick="askAI('Quelles sont les tendances cette semaine ?')">
                        📊 Tendances
                    </button>
                    <button class="quick-question" onclick="askAI('Donne-moi des recommandations marketing')">
                        🎯 Recommandations
                    </button>
                </div>

                <div class="ai-input-wrapper">
                    <input type="text"
                        id="aiInput"
                        placeholder="Posez votre question (ex: 'Où dois-je investir en pub ?')..."
                        onkeypress="if(event.key === 'Enter') sendAIQuestion()">
                    <button class="ai-send-btn" onclick="sendAIQuestion()">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Configuration des données réelles accessibles à l'assistant PSEUDO IA
        const aiData = {
            topPages: <?= json_encode($topPages) ?>,
            countries: <?= json_encode($countries) ?>,
            devices: <?= json_encode($devices) ?>,
            browsers: <?= json_encode($browsers) ?>,
            sources: <?= json_encode($sources) ?>,
            dailyStats: <?= json_encode($dailyStats) ?>,
            sessionData: <?= json_encode($sessionData) ?>,
            totalVisits: <?= $uniqueVisitorsPeriod ?>,
            avgSessionTime: <?= $avgSessionTime ?>,
            period: <?= $period ?>
        };

        // Dictionnaire de réponses intelligentes
        const aiKnowledgeBase = {
            // Mots-clés et réponses associées
            keywords: {
                'page performante|meilleur page|top page': function() {
                    if (aiData.topPages.length > 0) {
                        const page = aiData.topPages[0];
                        return `**Votre page la plus performante est :**  
🔗 *${page.page_url}*  
👁️ **${page.views} vues** (${Math.round((page.views / aiData.totalVisits) * 100)}% du trafic)  

**Recommandation :**  
✅ Optimisez cette page avec des Call-To-Actions clairs  
✅ Ajoutez des témoignages clients  
✅ Testez différentes versions (A/B testing)`;
                    }
                    return "Je n'ai pas encore assez de données sur vos pages.";
                },

                'investir|pub|publicité|ads|campagne': function() {
                    if (aiData.sources.length > 0) {
                        const bestSource = aiData.sources[0];
                        return `**💰 Recommandations d'investissement :**  

1. **Source actuelle la plus performante :**  
   📊 *${bestSource.source}* (${bestSource.count} visites)  

2. **Meilleur appareil cible :**  
   📱 *${aiData.devices[0]?.device || 'Desktop'}* (${aiData.devices[0]?.count || 0} utilisations)  

3. **Heures d'engagement :**  
   ⏰ *14h-18h* (pic d'activité détecté)  

**Stratégie recommandée :**  
🎯 Doublez votre budget sur **${bestSource.source}**  
🎯 Ciblez **${aiData.countries[0]?.country || 'France'}**  
🎯 Créez des annonces optimisées pour **${aiData.devices[0]?.device || 'Desktop'}**`;
                    }
                    return "Analysez d'abord vos sources de trafic pour mieux ciblervos investissements.";
                },

                'conversion|convertir|taux': function() {
                    const estimatedRate = (aiData.totalVisits > 100) ? '2-5%' : '1-3%';
                    return `**📊 Analyse de conversion :**  

**Taux estimé :** ${estimatedRate}  
**Potentiel d'amélioration :** ${(aiData.totalVisits * 0.05).toFixed(0)} conversions/mois  

**🎯 Actions rapides :**  
1. **Simplifiez votre formulaire** (moins de champs)  
2. **Ajoutez des garanties visibles**  
3. **Testez différents boutons** (couleur, texte)  
4. **Implémentez le retargeting**  

**📈 Objectif SMART :**  
Augmenter le taux de conversion de 1% dans les 30 jours`;
                },

                'tendance|évolution|croissance': function() {
                    if (aiData.dailyStats.length >= 2) {
                        const firstDay = aiData.dailyStats[0].visits;
                        const lastDay = aiData.dailyStats[aiData.dailyStats.length - 1].visits;
                        const growth = ((lastDay - firstDay) / firstDay * 100).toFixed(1);

                        return `**📈 Tendances ${aiData.period} jours :**  

📊 **Évolution trafic :** ${growth}%  
👥 **Visiteurs uniques :** ${aiData.totalVisits}  
⏱️ **Engagement :** ${aiData.avgSessionTime} min/session  

**📅 Prévision semaine prochaine :**  
${Math.round(aiData.totalVisits / aiData.period * 7 * 1.1)} visites estimées  
(+10% si vous maintenez la tendance)  

**🔥 Insight :**  
Votre croissance est ${growth > 0 ? 'positive' : 'à améliorer'}. ${growth > 20 ? 'Excellente performance !' : 'Pensez à relancer vos canaux.'}`;
                    }
                    return "Collectez plus de données pour analyser les tendances.";
                },

                'recommandation|conseil|astuce': function() {
                    const tips = [
                        `**🎯 Conseil #1 :** Ciblez **${aiData.countries[1]?.country || 'votre 2ème pays'}** avec du contenu localisé. Potentiel inexploité !`,

                        `**📱 Conseil #2 :** Optimisez pour **${aiData.devices[0]?.device || 'mobile'}** (${Math.round((aiData.devices[0]?.count / aiData.totalVisits) * 100)}% de votre trafic).`,

                        `**🔍 Conseil #3 :** Améliorez le SEO de votre page **"${aiData.topPages[2]?.page_url?.split('/').pop() || 'à fort potentiel'}"** pour +30% de trafic organique.`,

                        `**💰 Conseil #4 :** Testez une offre spéciale le **${['lundi', 'mercredi', 'vendredi'][Math.floor(Math.random() * 3)]}**, jour de plus forte activité.`,

                        `**📊 Conseil #5 :** Créez un rapport automatisé pour suivre vos KPIs clés chaque lundi matin.`
                    ];

                    return tips[Math.floor(Math.random() * tips.length)];
                },

                'pays|géographie|international': function() {
                    if (aiData.countries.length > 0) {
                        let response = `**🌍 Répartition géographique :**\n\n`;
                        aiData.countries.slice(0, 3).forEach((country, index) => {
                            const percentage = Math.round((country.visits / aiData.totalVisits) * 100);
                            response += `${index + 1}. **${country.country}** : ${country.visits} visites (${percentage}%)\n`;
                        });

                        response += `\n**💡 Opportunité :** Développez du contenu en ${aiData.countries[1]?.language || 'anglais'} pour toucher ${aiData.countries[1]?.country || 'de nouveaux marchés'}.`;
                        return response;
                    }
                    return "Vos visiteurs viennent de divers pays. Analysez la carte pour plus de détails.";
                }
            },

            // Réponses par défaut intelligentes
            defaultResponses: [
                "D'après vos données, je vois que **{device}** est votre principal appareil. Assurez-vous que l'expérience mobile est parfaite !",

                "Vos visiteurs viennent principalement de **{country}**. Avez-vous pensé à localiser votre contenu ?",

                "Je détecte que **{source}** est votre meilleure source de trafic. Pensez à y investir davantage !",

                "Avec {visits} visites en {period} jours, vous pourriez générer environ {conversions} conversions avec un taux de 3%.",

                "Le temps moyen de session est de {time} minutes. C'est {verdict} pour votre secteur !",

                "Pour maximiser vos résultats, concentrez-vous sur l'amélioration de votre taux de conversion actuel.",

                "Cette Assistant IA est en développement ... il se peut qu'il ne réponde pas toujours de manière pertinente. Pour toute question, contactez le developpeur contact@gael-berru.com"
            ]
        };

        // Fonction principale de l'assistant
        async function askAI(question) {
            const conversation = document.getElementById('aiConversation');

            // Ajouter la question
            conversation.innerHTML += `
        <div class="ai-message user">
            <div class="message-content">${question}</div>
            <div class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
        </div>
    `;

            // Simuler un "typing" de l'IA
            conversation.innerHTML += `
        <div class="ai-message bot typing">
            <div class="typing-indicator">
                <span></span><span></span><span></span>
            </div>
        </div>
    `;

            conversation.scrollTop = conversation.scrollHeight;

            // Générer une réponse intelligente après un délai
            setTimeout(() => {
                // Retirer l'indicateur de typing
                document.querySelector('.typing')?.remove();

                // Générer la réponse
                const response = generateAIResponse(question);

                // Ajouter la réponse
                conversation.innerHTML += `
            <div class="ai-message bot">
                <div class="message-content">${formatResponse(response)}</div>
                <div class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
            </div>
        `;

                conversation.scrollTop = conversation.scrollHeight;
            }, 1000 + Math.random() * 1000); // Délai aléatoire pour paraître naturel
        }

        function generateAIResponse(question) {
            const questionLower = question.toLowerCase();

            // Chercher une correspondance de mots-clés
            for (const [pattern, responseFunc] of Object.entries(aiKnowledgeBase.keywords)) {
                const patterns = pattern.split('|');
                if (patterns.some(p => questionLower.includes(p))) {
                    return responseFunc();
                }
            }

            // Sinon, générer une réponse contextuelle par défaut
            return generateDefaultResponse();
        }

        function generateDefaultResponse() {
            const template = aiKnowledgeBase.defaultResponses[
                Math.floor(Math.random() * aiKnowledgeBase.defaultResponses.length)
            ];

            return template
                .replace('{device}', aiData.devices[0]?.device || 'mobile')
                .replace('{country}', aiData.countries[0]?.country || 'France')
                .replace('{source}', aiData.sources[0]?.source || 'recherche organique')
                .replace('{visits}', aiData.totalVisits)
                .replace('{period}', aiData.period)
                .replace('{conversions}', Math.round(aiData.totalVisits * 0.03))
                .replace('{time}', aiData.avgSessionTime)
                .replace('{verdict}', aiData.avgSessionTime > 3 ? 'excellent' : 'moyen');
        }

        function formatResponse(text) {
            // Convertir le markdown simple en HTML
            return text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/✅/g, '<span class="emoji-success">✅</span>')
                .replace(/❌/g, '<span class="emoji-error">❌</span>')
                .replace(/🎯/g, '<span class="emoji-target">🎯</span>')
                .replace(/💰/g, '<span class="emoji-money">💰</span>')
                .replace(/\n\n/g, '</p><p>')
                .replace(/\n/g, '<br>');
        }

        function sendAIQuestion() {
            const input = document.getElementById('aiInput');
            if (input.value.trim()) {
                askAI(input.value);
                input.value = '';
            }
        }

        function toggleAIAssistant() {
            const panel = document.getElementById('aiPanel');
            panel.classList.toggle('active');

            // Initialiser avec un message de bienvenue
            if (panel.classList.contains('active') && !document.querySelector('.ai-message.bot')) {
                setTimeout(() => {
                    askAI("Bonjour ! Que pouvez-vous m'apprendre sur mes données ?");
                }, 500);
            }
        }

        // Questions automatiques périodiques (simule une IA proactive)
        setTimeout(() => {
            if (Math.random() > 0.7 && document.getElementById('aiPanel')?.classList.contains('active')) {
                const proactiveQuestions = [
                    "J'ai remarqué que votre trafic augmente. Voulez-vous des conseils pour capitaliser dessus ?",
                    "Votre taux d'engagement est intéressant. Puis-je vous suggérer des optimisations ?",
                    "Je vois une opportunité sur votre source de trafic principale. En discuter ?"
                ];

                // Simuler une suggestion de l'IA
                const conversation = document.getElementById('aiConversation');
                conversation.innerHTML += `
            <div class="ai-message bot suggestion">
                <div class="message-content">
                    💡 <strong>Suggestion proactive :</strong><br>
                    ${proactiveQuestions[Math.floor(Math.random() * proactiveQuestions.length)]}
                </div>
            </div>
        `;
                conversation.scrollTop = conversation.scrollHeight;
            }
        }, 15000); // Toutes les 15 secondes
        // FIN DU TEST PSEUDO IA

        // Fonction pour changer d'onglet
        function openTab(tabName) {
            // Masquer tous les contenus d'onglets
            const tabContents = document.getElementsByClassName('tab-content');
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove('active');
            }

            // Désactiver tous les onglets
            const tabs = document.getElementsByClassName('tab');
            for (let i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }

            // Activer l'onglet sélectionné
            document.getElementById(tabName).classList.add('active');
            event.currentTarget.classList.add('active');
        }

        // Fonction pour changer la période
        function changePeriod(period) {
            window.location.href = `?period=${period}`;
        }

        // Données pour les graphiques
        const dailyStats = <?= json_encode($dailyStats) ?>;
        const sources = <?= json_encode($sources) ?>;
        const devices = <?= json_encode($devices) ?>;
        const browsers = <?= json_encode($browsers) ?>;
        const countries = <?= json_encode($countries) ?>;

        // Configuration commune pour les petits graphiques
        const smallChartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        };

        // Graphique d'évolution du trafic
        const trafficCtx = document.getElementById('trafficChart').getContext('2d');
        const trafficChart = new Chart(trafficCtx, {
            type: 'line',
            data: {
                labels: dailyStats.map(stat => stat.date),
                datasets: [{
                        label: 'Visites',
                        data: dailyStats.map(stat => stat.visits),
                        borderColor: '#9d86ff',
                        backgroundColor: 'rgba(67, 97, 238, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Visiteurs uniques',
                        data: dailyStats.map(stat => stat.unique_visitors),
                        borderColor: '#4ecdc4',
                        backgroundColor: 'rgba(76, 201, 240, 0.1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Graphique des sources (aperçu)
        const sourcesCtx = document.getElementById('sourcesChart').getContext('2d');
        const sourcesChart = new Chart(sourcesCtx, {
            type: 'doughnut',
            data: {
                labels: sources.map(s => s.source),
                datasets: [{
                    data: sources.map(s => s.count),
                    backgroundColor: [
                        '#9d86ff', '#4ecdc4', '#ff6b8b', '#ffe66d', '#ff86e9'
                    ]
                }]
            },
            options: smallChartOptions
        });

        // Graphique des appareils (aperçu)
        const devicesCtx = document.getElementById('devicesChart').getContext('2d');
        const devicesChart = new Chart(devicesCtx, {
            type: 'pie',
            data: {
                labels: devices.map(d => d.device),
                datasets: [{
                    data: devices.map(d => d.count),
                    backgroundColor: ['#9d86ff', '#4ecdc4', '#ff86e9']
                }]
            },
            options: smallChartOptions
        });

        // Graphique des pays (aperçu)
        const countriesOverviewCtx = document.getElementById('countriesOverviewChart').getContext('2d');
        const countriesOverviewChart = new Chart(countriesOverviewCtx, {
            type: 'bar',
            data: {
                labels: countries.map(c => c.country),
                datasets: [{
                    label: 'Visites',
                    data: countries.map(c => c.visits),
                    backgroundColor: ['#4ecdc4', '#ff6b8b', '#ffe66d', '#ff86e9']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Graphique des sources (trafic)
        const sourcesTrafficCtx = document.getElementById('sourcesTrafficChart').getContext('2d');
        const sourcesTrafficChart = new Chart(sourcesTrafficCtx, {
            type: 'doughnut',
            data: {
                labels: sources.map(s => s.source),
                datasets: [{
                    data: sources.map(s => s.count),
                    backgroundColor: [
                        '#9d86ff', '#4ecdc4', '#ff6b8b', '#ffe66d', '#ff86e9'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });

        // Graphique des navigateurs
        const browsersCtx = document.getElementById('browsersChart').getContext('2d');
        const browsersChart = new Chart(browsersCtx, {
            type: 'bar',
            data: {
                labels: browsers.map(b => b.browser),
                datasets: [{
                    label: 'Utilisations',
                    data: browsers.map(b => b.count),
                    backgroundColor: ['#9d86ff', '#4ecdc4', '#ff86e9']
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Graphique des pays
        const countriesCtx = document.getElementById('countriesChart').getContext('2d');
        const countriesChart = new Chart(countriesCtx, {
            type: 'bar',
            data: {
                labels: countries.map(c => c.country),
                datasets: [{
                    label: 'Visites',
                    data: countries.map(c => c.visits),
                    backgroundColor: ['#9d86ff', '#4ecdc4', '#ff6b8b', '#ffe66d', '#ff86e9']
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });
        // ============================================
        // FONCTIONS POUR LA MAP AMCHARTS
        // ============================================

        // Helper simplifié pour les codes pays
        function getCountryCodeSimple(countryName) {
            const countryMap = {
                // Noms complets
                'france': 'FR',
                'united states': 'US',
                'germany': 'DE',
                'united kingdom': 'GB',
                'canada': 'CA',
                'australia': 'AU',
                'japan': 'JP',
                'china': 'CN',
                'brazil': 'BR',
                'india': 'IN',
                'italy': 'IT',
                'spain': 'ES',
                'netherlands': 'NL',
                'belgium': 'BE',
                'switzerland': 'CH',
                'portugal': 'PT',
                'russia': 'RU',
                'mexico': 'MX',
                'south korea': 'KR',
                'singapore': 'SG',

                // Variantes
                'usa': 'US',
                'uk': 'GB',
                'deutschland': 'DE',
                'italia': 'IT',
                'españa': 'ES',
                'españa': 'ES',
                'nederland': 'NL',
                'schweiz': 'CH',
                'suisse': 'CH',
                'brasil': 'BR'
            };

            const normalized = countryName.toLowerCase().trim();
            return countryMap[normalized] || null;
        }

        // Données de pays préparées pour amCharts
        function prepareMapData() {
            return countries.map(country => {
                const code = getCountryCodeSimple(country.country);
                return code ? {
                    id: code,
                    name: country.country,
                    value: country.visits
                } : null;
            }).filter(item => item !== null);
        }

        // Variables globales pour la map
        let mapRoot = null;
        let mapChart = null;

        // Initialisation de la map
        function initMapChart() {
            // Nettoyer l'ancienne map si elle existe
            if (mapRoot) {
                try {
                    mapRoot.dispose();
                } catch (e) {
                    console.log('Nettoyage map précédente');
                }
            }

            // Préparer les données
            const mapData = prepareMapData();

            if (mapData.length === 0) {
                document.getElementById('mapChart').innerHTML =
                    '<div style="text-align: center; padding: 50px; color: #666;">' +
                    'Aucune donnée géographique disponible pour afficher la carte.' +
                    '</div>';
                return;
            }

            try {
                // Créer la racine
                mapRoot = am5.Root.new("mapChart");

                // Thème
                mapRoot.setThemes([
                    am5themes_Animated.new(mapRoot)
                ]);

                // Créer la carte
                mapChart = mapRoot.container.children.push(
                    am5map.MapChart.new(mapRoot, {
                        panX: "rotateX",
                        panY: "rotateY",
                        projection: am5map.geoMercator(),
                        paddingBottom: 20,
                        paddingTop: 20,
                        paddingLeft: 20,
                        paddingRight: 20
                    })
                );

                // Série des polygones (pays)
                const polygonSeries = mapChart.series.push(
                    am5map.MapPolygonSeries.new(mapRoot, {
                        geoJSON: am5geodata_worldLow,
                        exclude: ["AQ"] // Exclure Antarctique
                    })
                );

                polygonSeries.mapPolygons.template.setAll({
                    tooltipText: "{name}: {value} visites",
                    fill: am5.color(0xe0e0e0),
                    stroke: am5.color(0xffffff),
                    strokeWidth: 1
                });

                // État hover
                polygonSeries.mapPolygons.template.states.create("hover", {
                    fill: am5.color(0x6772e5)
                });

                // Définir les données
                polygonSeries.data.setAll(mapData);

                // Configurer les couleurs basées sur les valeurs
                polygonSeries.mapPolygons.template.adapters.add("fill", function(fill, target) {
                    const dataItem = target.dataItem;
                    if (dataItem) {
                        const value = dataItem.dataContext.value;
                        const maxValue = Math.max(...mapData.map(d => d.value));
                        const ratio = value / maxValue;

                        // Définir les couleurs selon l'intensité
                        if (ratio > 0.8) return am5.color(0xff6b8b);
                        if (ratio > 0.6) return am5.color(0xff8e6b);
                        if (ratio > 0.4) return am5.color(0x9d86ff);
                        if (ratio > 0.2) return am5.color(0x4ecdc4);
                        return am5.color(0x6772e5);
                    }
                    return fill;
                });

                // Série des points (bulles)
                const pointSeries = mapChart.series.push(
                    am5map.MapPointSeries.new(mapRoot, {})
                );

                pointSeries.bullets.push(function(root, series, dataItem) {
                    const value = dataItem.dataContext.value;
                    const size = Math.max(15, Math.min(50, Math.sqrt(value) * 0.7));

                    const circle = am5.Circle.new(root, {
                        radius: size,
                        fill: am5.color(0xff6b8b),
                        stroke: am5.color(0xffffff),
                        strokeWidth: 2,
                        tooltipText: "{name}: {value} visites"
                    });

                    const label = am5.Label.new(root, {
                        text: value.toString(),
                        fill: am5.color(0xffffff),
                        fontSize: Math.max(10, size / 3),
                        centerY: am5.p50,
                        centerX: am5.p50
                    });

                    return am5.Bullet.new(root, {
                        sprite: am5.Container.new(root, {
                            children: [circle, label]
                        })
                    });
                });

                pointSeries.data.setAll(mapData);

                // Zoom au démarrage
                polygonSeries.events.on("datavalidated", function() {
                    mapChart.goHome();
                    mapChart.zoomToGeoPoint({
                        latitude: 20,
                        longitude: 0
                    }, 2);
                });

                // Contrôles de zoom intégrés
                mapChart.set("zoomControl", am5map.ZoomControl.new(mapRoot, {}));

                console.log('Map amCharts initialisée avec succès');

            } catch (error) {
                console.error('Erreur lors de l\'initialisation de la map:', error);
                document.getElementById('mapChart').innerHTML =
                    '<div style="text-align: center; padding: 50px; color: #ff6b6b;">' +
                    'Erreur lors du chargement de la carte.<br>' +
                    '<small>' + error.message + '</small>' +
                    '</div>';
            }
        }

        // Fonctions de contrôle de la map
        function zoomIn() {
            if (mapChart) {
                mapChart.zoomIn();
            }
        }

        function zoomOut() {
            if (mapChart) {
                mapChart.zoomOut();
            }
        }

        function resetMap() {
            if (mapChart) {
                mapChart.goHome();
            }
        }


        // ===== SECTION AJOUTEE: Graphique Revenus vs Trafic =====
        document.addEventListener('DOMContentLoaded', function() {
            // Récupérer les données passées depuis PHP
            const chartDates = <?php echo json_encode($chartDates); ?>;
            const chartVisits = <?php echo json_encode($chartVisits); ?>;
            const chartRevenue = <?php echo json_encode($chartRevenue); ?>;

            if (chartDates.length > 0) {
                const ctx = document.getElementById('revenueTrafficChart').getContext('2d');

                // Formater les dates pour l'affichage
                const formattedDates = chartDates.map(date => {
                    const d = new Date(date);
                    return d.toLocaleDateString('fr-FR', {
                        day: '2-digit',
                        month: '2-digit'
                    });
                });

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: formattedDates,
                        datasets: [{
                                label: 'Visites',
                                data: chartVisits,
                                borderColor: 'rgb(54, 162, 235)',
                                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                                tension: 0.3,
                                yAxisID: 'y'
                            },
                            {
                                label: 'Revenus (€)',
                                data: chartRevenue,
                                borderColor: 'rgb(75, 192, 192)',
                                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                                tension: 0.3,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.datasetIndex === 0) {
                                            label += context.parsed.y + ' visites';
                                        } else {
                                            label += context.parsed.y.toFixed(2) + '€';
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: true
                                }
                            },
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Visites'
                                },
                                grid: {
                                    drawOnChartArea: true
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Revenus (€)'
                                },
                                grid: {
                                    drawOnChartArea: false
                                }
                            }
                        }
                    }
                });
            }

            // ===== ONGLET : INSIGHTS =====

            // Ajouter des tooltips aux recommandations
            document.querySelectorAll('.tip-success, .tip-warning, .tip-info, .tip-neutral').forEach(tip => {
                tip.style.cursor = 'pointer';
                tip.title = 'Cliquez pour en savoir plus';

                tip.addEventListener('click', function() {
                    const message = this.textContent;
                    alert('Recommandation détaillée:\n\n' + message + '\n\nCette analyse est basée sur vos données de trafic et transactions.');
                });
            });

            // Calculer et afficher un résumé des insights
            function calculateDailyInsights() {
                const visitsToday = chartVisits[chartVisits.length - 1] || 0;
                const revenueToday = chartRevenue[chartRevenue.length - 1] || 0;

                const avgVisits = chartVisits.reduce((a, b) => a + b, 0) / chartVisits.length;
                const avgRevenue = chartRevenue.reduce((a, b) => a + b, 0) / chartRevenue.length;

                let insightText = '';

                if (visitsToday > avgVisits * 1.5 && revenueToday > avgRevenue * 1.5) {
                    insightText = '🔥 Excellente journée ! Trafic et revenus bien au-dessus de la moyenne.';
                } else if (visitsToday > avgVisits * 1.2 && revenueToday < avgRevenue * 0.8) {
                    insightText = '⚠️ Trafic élevé mais revenus bas. Vérifiez votre taux de conversion.';
                } else if (visitsToday < avgVisits * 0.8 && revenueToday > avgRevenue * 1.2) {
                    insightText = '💰 Peu de visites mais conversion excellente. Qualité > Quantité !';
                } else {
                    insightText = '📊 Journée dans la moyenne. Continuez vos efforts !';
                }

                // Créer un élément d'insight si nécessaire
                let insightElement = document.querySelector('.daily-insight');
                if (!insightElement) {
                    insightElement = document.createElement('div');
                    insightElement.className = 'daily-insight insight-tip';
                    document.querySelector('#revenueTrafficChart').closest('.card-body').appendChild(insightElement);
                }

                insightElement.innerHTML = `<strong>📅 Aujourd'hui :</strong> ${visitsToday} visites, ${revenueToday.toFixed(2)}€ de revenus. ${insightText}`;
            }

            // Exécuter l'analyse quotidienne
            if (chartVisits.length > 0) {
                calculateDailyInsights();
            }
        });

        // Gestion des onglets - Version simplifiée sans MutationObserver
        function setupMapTabListener() {
            const tabs = document.querySelectorAll('.tab');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Vérifier si c'est l'onglet Géographie
                    const tabText = this.textContent || '';
                    if (tabText.toLowerCase().includes('géographie') ||
                        tabText.toLowerCase().includes('geographie')) {

                        // Petit délai pour laisser le DOM se mettre à jour
                        setTimeout(() => {
                            // Vérifier si le conteneur existe
                            const mapContainer = document.getElementById('mapChart');
                            if (mapContainer && !mapRoot) {
                                initMapChart();
                            }
                        }, 100);
                    } else {
                        // Nettoyer la map si on quitte l'onglet
                        if (mapRoot) {
                            setTimeout(() => {
                                try {
                                    mapRoot.dispose();
                                    mapRoot = null;
                                    mapChart = null;
                                } catch (e) {
                                    console.log('Map déjà nettoyée');
                                }
                            }, 500);
                        }
                    }
                });
            });
        }

        // Détecter quand l'onglet Géographie devient actif
        function checkGeographyTabActive() {
            const geographyTab = document.getElementById('geography');
            if (geographyTab && geographyTab.classList.contains('active')) {
                // Attendre un peu pour être sûr que tout est chargé
                setTimeout(() => {
                    if (!mapRoot && document.getElementById('mapChart')) {
                        initMapChart();
                    }
                }, 300);
            }
        }

        // Initialisation au chargement
        document.addEventListener('DOMContentLoaded', function() {
            // Configurer les écouteurs d'onglets
            setupMapTabListener();

            // Vérifier si l'onglet Géographie est actif au départ
            checkGeographyTabActive();

            // Observer les changements d'URL (pour les filtres de période)
            const originalOpenTab = window.openTab;
            window.openTab = function(tabName) {
                originalOpenTab(tabName);

                // Si c'est l'onglet Géographie, initialiser la map
                if (tabName === 'geography') {
                    setTimeout(() => {
                        if (!mapRoot && document.getElementById('mapChart')) {
                            initMapChart();
                        }
                    }, 200);
                }
            };
        });



        // Graphique des types d'appareils
        const deviceTypesCtx = document.getElementById('deviceTypesChart').getContext('2d');
        const deviceTypesChart = new Chart(deviceTypesCtx, {
            type: 'doughnut',
            data: {
                labels: devices.map(d => d.device),
                datasets: [{
                    data: devices.map(d => d.count),
                    backgroundColor: ['#9d86ff', '#4ecdc4', '#ff6b8b']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });

        // Graphique des types de navigateurs
        const browserTypesCtx = document.getElementById('browserTypesChart').getContext('2d');
        const browserTypesChart = new Chart(browserTypesCtx, {
            type: 'pie',
            data: {
                labels: browsers.map(b => b.browser),
                datasets: [{
                    data: browsers.map(b => b.count),
                    backgroundColor: [
                        '#9d86ff', '#ff86e9', '#4ecdc4', '#ff6b8b', '#ffe66d'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });

        function confirmLogout() {
            if (confirm('Voulez-vous vraiment vous déconnecter ?')) {
                window.location.href = 'logout.php';
            }
        }

        function confirmParametre() {
            const modal = document.getElementById('modalParametres');
            if (!modal) {
                // Fallback si la modale n'existe pas (comportement original)
                if (confirm('Trouver vos réponses dans la doc complète ?')) {
                    window.location.href = '../../doc/';
                }
                return;
            }

            // Ouvre la modale
            modal.showModal();

            // Gestion du clic sur "Documentation complète" → redirection
            const linkDoc = document.getElementById('linkToDoc');
            if (linkDoc) {
                linkDoc.addEventListener('click', function(e) {
                    e.preventDefault(); // empêche le lien direct
                    modal.close(); // ferme la modale
                    window.location.href = '../../doc/'; // redirige
                }, {
                    once: true
                }); // écouteur jetable
            }

            // Bouton Annuler → ferme juste la modale
            const btnCancel = document.getElementById('btnCancel');
            if (btnCancel) {
                btnCancel.addEventListener('click', function() {
                    modal.close();
                }, {
                    once: true
                });
            }

            // Optionnel : fermer en cliquant sur le backdrop
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.close();
                }
            }, {
                once: true
            });
        }


        function showUpgradeForm(plan) {
            document.getElementById('newPlanInput').value = plan;
            document.getElementById('upgradeForm').style.display = 'block';
            window.scrollTo(0, document.getElementById('upgradeForm').offsetTop);
        }

        function submitUpgrade() {
            const form = document.getElementById('upgradeFormContent');
            const formData = new FormData(form);

            fetch('upgrade.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Plan mis à jour avec succès ! Vous pouvez maintenant ajouter votre site.');
                        window.location.reload();
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                });
        }

        // Gestion du toggle de la sidebar
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            sidebar.classList.toggle('collapsed');

            // Sauvegarder l'état dans un cookie (valide 30 jours)
            const isCollapsed = sidebar.classList.contains('collapsed');
            document.cookie = `sidebar_collapsed=${isCollapsed}; path=/; max-age=${60*60*24*30}`;

            // Mettre à jour le contenu principal
            const mainContent = document.querySelector('.main-content');
            if (mainContent) {
                mainContent.style.marginLeft = isCollapsed ? '70px' : '280px';
            }
        }

        function toggleMobileMenu() {
            sidebar.classList.toggle('mobile-open');
        }

        function closeMobileMenu() {
            sidebar.classList.remove('mobile-open');
        }

        // Événements
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', toggleSidebar);
        }

        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', toggleMobileMenu);
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', closeMobileMenu);
        }

        // Fermer le menu mobile en cliquant sur un lien
        document.querySelectorAll('.sidebar a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 1024) {
                    closeMobileMenu();
                }
            });
        });

        // Fonction de copie du code
        function copyCode() {
            const codeElement = document.querySelector('.integration-code');
            if (!codeElement) return;

            const textToCopy = codeElement.innerText;
            navigator.clipboard.writeText(textToCopy).then(() => {
                // Feedback visuel
                const copyBtn = document.querySelector('.copy-btn');
                if (copyBtn) {
                    const originalHTML = copyBtn.innerHTML;
                    copyBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>';
                    copyBtn.style.color = '#34d399';
                    setTimeout(() => {
                        copyBtn.innerHTML = originalHTML;
                        copyBtn.style.color = '';
                    }, 2000);
                }
            }).catch(err => {
                console.error('Erreur lors de la copie : ', err);
            });
        }

        // Fonction de déconnexion
        function confirmLogout() {
            Swal.fire({
                title: 'Êtes-vous sûr ',
                text: 'de vouloir vous déconnecter ?',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#f87171', // Rouge pour le bouton "Oui"
                cancelButtonColor: '#34d399', // Bleu pour "Annuler"
                confirmButtonText: 'Oui',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Si l'utilisateur clique sur "Oui, déconnecter"
                    window.location.href = 'logout.php';
                }
                // Sinon, rien ne se passe, le popup disparaît
            });
        }


        // Fermer le menu mobile lors du redimensionnement
        window.addEventListener('resize', () => {
            if (window.innerWidth > 1024) {
                closeMobileMenu();
            }
        });

        // ===== GRAPHIQUE DES TENDANCES =====
        document.addEventListener('DOMContentLoaded', function() {
            // Attendre que l'onglet insights soit chargé
            setTimeout(() => {
                const trendsCtx = document.getElementById('trendsChart');
                if (trendsCtx) {
                    const trendChart = new Chart(trendsCtx.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: <?= json_encode($trendLabels) ?>,
                            datasets: [{
                                    label: 'Visites totales',
                                    data: <?= json_encode($trendVisits) ?>,
                                    borderColor: '#9d86ff',
                                    backgroundColor: 'rgba(157, 134, 255, 0.1)',
                                    tension: 0.3,
                                    fill: true
                                },
                                {
                                    label: 'Visiteurs uniques',
                                    data: <?= json_encode($trendUnique) ?>,
                                    borderColor: '#4ecdc4',
                                    backgroundColor: 'rgba(78, 205, 196, 0.1)',
                                    tension: 0.3,
                                    fill: true
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Nombre de visites'
                                    }
                                }
                            }
                        }
                    });
                }

                // Ajouter des tooltips aux recommandations
                document.querySelectorAll('.tip-success, .tip-warning, .tip-info, .tip-neutral').forEach(tip => {
                    tip.style.cursor = 'pointer';
                    tip.title = 'Cliquez pour en savoir plus';

                    tip.addEventListener('click', function() {
                        const message = this.textContent;
                        alert('Recommandation détaillée:\n\n' + message);
                    });
                });

                // Calculer et afficher des insights automatiques
                function generateAutoInsights() {
                    const insightsContainer = document.querySelector('#insights .insight-tip');
                    if (!insightsContainer) return;

                    // Analyser les sources de trafic
                    const directTraffic = sources.find(s => s.source.toLowerCase().includes('direct'));
                    const socialTraffic = sources.find(s =>
                        s.source.toLowerCase().includes('facebook') ||
                        s.source.toLowerCase().includes('twitter') ||
                        s.source.toLowerCase().includes('instagram')
                    );

                    let additionalInsight = '';

                    if (directTraffic && directTraffic.count > (sources[0]?.count || 0) * 0.5) {
                        additionalInsight += " Beaucoup de trafic direct - vos utilisateurs vous connaissent déjà !";
                    }

                    if (socialTraffic && socialTraffic.count < (sources[0]?.count || 0) * 0.1) {
                        additionalInsight += " Peu de trafic social - développez votre présence sur les réseaux.";
                    }

                    if (additionalInsight) {
                        const newInsight = document.createElement('div');
                        newInsight.className = 'insight-tip';
                        newInsight.style.marginTop = '15px';
                        newInsight.style.padding = '10px';
                        newInsight.style.background = '#2436f95b';
                        newInsight.style.borderRadius = '5px';
                        newInsight.innerHTML = `<strong>Opportunité :</strong>${additionalInsight}`;

                        insightsContainer.parentNode.insertBefore(newInsight, insightsContainer.nextSibling);
                    }
                }

                // Exécuter l'analyse automatique
                generateAutoInsights();

            }, 100);
        });

        // 1. HEATMAP avec vos données existantes
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof visitsByHour !== 'undefined') {
                const ctx = document.getElementById('heatmapChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: Object.keys(visitsByHour),
                        datasets: [{
                            label: 'Visites',
                            data: Object.values(visitsByHour),
                            backgroundColor: 'rgba(54, 162, 235, 0.8)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            // 2. Afficher le top des pages (données existantes)
            if (typeof pagesData !== 'undefined') {
                const topPages = pagesData.slice(0, 5);
                const listHtml = topPages.map(page =>
                    `<div class="page-item">${page.page} - <strong>${page.views} vues</strong></div>`
                ).join('');
                document.getElementById('topPagesList').innerHTML = listHtml;
            }

            // 3. GANTT CHART avec amCharts
            // 3. GANTT CHART avec amCharts
            document.addEventListener('DOMContentLoaded', function() {

                // Variable pour éviter les initialisations multiples
                window.ganttInitialized = false;

                // Fonction d'initialisation du Gantt
                function initGantt() {
                    if (window.ganttInitialized) return;

                    console.log('Initialisation du Gantt...');

                    // Vérifier que amCharts est chargé
                    if (typeof am5 === 'undefined') {
                        console.error('amCharts non chargé !');
                        document.getElementById('ganttChart').innerHTML =
                            '<div style="text-align: center; padding: 20px;">⚠️ Chargement du planning...</div>';
                        return;
                    }

                    try {
                        am5.ready(function() {
                            // Nettoyer le conteneur
                            const container = document.getElementById("ganttChart");
                            if (!container) return;
                            container.innerHTML = '';

                            // Créer la racine
                            var root = am5.Root.new("ganttChart");

                            // Appliquer le thème
                            root.setThemes([
                                am5themes_Animated.new(root)
                            ]);

                            // Créer le Gantt avec configuration simplifiée
                            var gantt = root.container.children.push(
                                am5gantt.Gantt.new(root, {
                                    layout: root.verticalLayout,
                                    headerHeight: 60
                                })
                            );

                            // Données des catégories
                            gantt.get("yAxis").data.setAll([{
                                    name: "Idée",
                                    id: "gantt_0"
                                },
                                {
                                    name: "Lancement MVP",
                                    id: "gantt_1"
                                },
                                {
                                    name: "Planification",
                                    id: "gantt_2"
                                },
                                {
                                    name: "Développement",
                                    id: "gantt_3"
                                },
                                {
                                    name: "Tests",
                                    id: "gantt_4"
                                },
                                {
                                    name: "Finalisation",
                                    id: "gantt_5"
                                },
                                {
                                    name: "Publication",
                                    id: "gantt_6"
                                }
                            ]);

                            // Données des séries
                            gantt.series.data.setAll([{
                                    start: 1758142800000,
                                    duration: 0,
                                    progress: 1,
                                    id: "gantt_0",
                                    linkTo: ["gantt_1"]
                                },
                                {
                                    start: 1758142800000,
                                    duration: 2,
                                    progress: 1,
                                    id: "gantt_1",
                                    linkTo: ["gantt_2"]
                                },
                                {
                                    start: 1758488400000,
                                    duration: 2,
                                    progress: 0.2,
                                    id: "gantt_2",
                                    linkTo: ["gantt_3"]
                                },
                                {
                                    start: 1758661200000,
                                    duration: 1,
                                    progress: 0.8,
                                    id: "gantt_3",
                                    linkTo: ["gantt_4"]
                                },
                                {
                                    start: 1758747600000,
                                    duration: 3,
                                    progress: 0,
                                    id: "gantt_4",
                                    linkTo: ["gantt_5"]
                                },
                                {
                                    start: 1759179600000,
                                    duration: 0,
                                    progress: 0,
                                    id: "gantt_5",
                                    linkTo: ["gantt_6"]
                                },
                                {
                                    start: 1759179600000,
                                    duration: 4,
                                    progress: 0,
                                    id: "gantt_6"
                                }
                            ]);

                            gantt.appear(1000);

                            window.ganttInitialized = true;
                            console.log('✅ Gantt initialisé avec succès');
                        });
                    } catch (error) {
                        console.error('❌ Erreur Gantt:', error);
                        document.getElementById('ganttChart').innerHTML =
                            '<div style="text-align: center; padding: 20px; color: #ff6b6b;">' +
                            'Erreur de chargement du planning' +
                            '</div>';
                    }
                }

                // Initialiser si l'onglet AgendaReco est actif au chargement
                const agendaReco = document.getElementById('AgendaReco');
                if (agendaReco && agendaReco.classList.contains('active')) {
                    setTimeout(initGantt, 500);
                }

                // Observer les clics sur les onglets
                const tabs = document.querySelectorAll('.tab');
                tabs.forEach(tab => {
                    tab.addEventListener('click', function() {
                        const tabText = this.textContent.trim();
                        if (tabText === 'Agenda' || tabText === 'AgendaReco') {
                            setTimeout(() => {
                                if (!window.ganttInitialized) {
                                    initGantt();
                                }
                            }, 300);
                        }
                    });
                });

                // Observer les changements de classe de l'onglet
                if (agendaReco) {
                    const observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.attributeName === 'class' &&
                                agendaReco.classList.contains('active') &&
                                !window.ganttInitialized) {
                                setTimeout(initGantt, 200);
                            }
                        });
                    });

                    observer.observe(agendaReco, {
                        attributes: true
                    });
                }
            });


        });

        // Fonctions utilitaires
        function getPeakHour(hourData) {
            if (!hourData) return 14;
            const entries = Object.entries(hourData);
            const peak = entries.reduce((max, [hour, count]) =>
                count > max.count ? {
                    hour: parseInt(hour),
                    count
                } : max, {
                    hour: 14,
                    count: 0
                }
            );
            return peak.hour;
        }

        function setHour(date, hour) {
            const newDate = new Date(date);
            newDate.setHours(hour, 0, 0, 0);
            return newDate;
        }

        function addDays(date, days) {
            const newDate = new Date(date);
            newDate.setDate(newDate.getDate() + days);
            return newDate;
        }
        // Fonction pour basculer vers l'onglet AgendaReco (au cas où)
        function openAgendaReco() {
            openTab('AgendaReco');
        }
    </script>
</body>

</html>