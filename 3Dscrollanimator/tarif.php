<?php
// index.php
require_once 'config.php';
require_once 'auth.php';
require_once 'PointsManager.php';

// Vérifier si un projet doit être chargé
$loadProjectId = $_GET['load_project'] ?? null;
// DEBUG
error_log("=== INDEX.PHP ===");
error_log("Session ID: " . session_id());
error_log("Logged in: " . (Auth::isLoggedIn() ? 'YES' : 'NO'));
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Éditeur d'Animation 3D avec Scroll</title>
    <link rel="shortcut icon" href="/img/drone-logo.png" />
    <link rel="apple-touch-icon" href="/img/drone-logo.png" />
    <meta name="description"
        content="Créez des animations 3D époustouflantes sans une ligne de code. Importez vos modèles 3D, définissez des keyframes et générez du code prêt à l'emploi pour vos projets web.">
    <meta name="keywords"
        content="3D generator scroll, Animation, Scroll, WebGL, Three.js, GLTF, GLB, Keyframes, Code Generator, Web Development, Interactive, Visual Effects">
    <meta name="author" content="berru-g">
    <meta name="robots" content="noai">
    <meta property="og:title" content="Éditeur d'Animation 3D avec Scroll">
    <meta property="og:description"
        content="Créez des animations 3D sans une ligne de code. Importez vos modèles 3D, définissez des keyframes et générez du code prêt à l'emploi pour vos projets web.">
    <meta property="og:image"
        content="https://raw.githubusercontent.com/berru-g/berru-g/refs/heads/main/img/3dscrollanimator_preview.png">
    <meta property="og:url" content="https://gael-berru.com">
    <link rel="canonical" href="https://gael-berru.com" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <!-- Notifications Toast -->
    <div id="notification-container" class="notification-container"></div>

    <?php require_once 'header.php'; ?>

    <!-- INITIALISATION DES VARIABLES AUTH POUR JAVASCRIPT -->
    <script>
        // Ces variables sont utilisées par scriptV2.js pour savoir si l'utilisateur est connecté
        window.currentUser = <?= Auth::isLoggedIn() ? json_encode([
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'subscription' => $_SESSION['subscription'],
            'points' => $_SESSION['user_points'] ?? 200
        ]) : 'null' ?>;
        window.userSubscription = '<?= Auth::isLoggedIn() ? $_SESSION['subscription'] : 'free' ?>';

        console.log('Auth initialized:', window.currentUser);
    </script>
    <!-- Achat de points à config avec stripe ou lemonsqueezie -->
    <div class="points-shop">
        <h3>💎 Gagnez du temps avec les Packs </h3>
        <?php if (Auth::isLoggedIn()): ?>
            <div id="user-menu" class="user-menu">

                <span class="user-name" id="user-name">
                    <?= htmlspecialchars($_SESSION['user_name']) ?>
                    <span class="user-points" id="user-points">
                        💎 <?= $_SESSION['user_points'] ?? 200 ?>
                    </span>
                </span>
            </div>
        <?php endif; ?>
        </p>


        <div class="point-packs">

            <div class="point-pack" data-pack-id="1">
                <h4>Pack Starter</h4>
                <div class="points-amount">100 💎</div>
                <div class="price">4,90 €</div>
                <button class="btn btn-primary buy-points">Obtenir</button>
            </div>

            <div class="point-pack popular" data-pack-id="2">
                <div class="badge">Populaire</div>
                <h4>Pack Pro</h4>
                <div class="points-amount">500 💎</div>
                <div class="price">19,90 €</div>
                <button class="btn btn-primary buy-points">Obtenir</button>
            </div>

            <div class="point-pack" data-pack-id="3">
                <h4>Pack Expert</h4>
                <div class="points-amount">1500 💎</div>
                <div class="price">49,90 €</div>
                <button class="btn btn-primary buy-points">Obtenir</button>
            </div>
        </div>
    </div>

    <br>
    <?php require_once 'footer.php'; ?>
</body>