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

    <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "3D Scroll Animator",
  "applicationCategory": "MultimediaApplication",
  "operatingSystem": "Web Browser",
  "description": "Éditeur no-code pour créer des animations 3D interactives basées sur le scroll. Créez des expériences web immersives sans programmation.",
  "offers": {
    "@type": "Offer",
    "price": "0",
    "priceCurrency": "EUR",
    "description": "Plan gratuit avec projet illimité à vie + fonctionnalités premium"
  },
  "author": {
    "@type": "Organization",
    "name": "Berru-G",
    "url": "https://gael-berru.com"
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.8",
    "ratingCount": "150"
  },
  "featureList": [
    "Éditeur visuel 3D no-code",
    "Animation basée sur le scroll",
    "Import modèles GLB/GLTF",
    "Export code Three.js prêt à l'emploi",
    "Galerie communautaire",
    "Sauvegarde cloud"
  ],
  "screenshot": [
    {
      "@type": "ImageObject",
      "caption": "Interface de l'éditeur 3D no-code",
      "url": "https://gael-berru.com/img/3Dscrollanimator.png"
    },
    {
      "@type": "ImageObject", 
      "caption": "Galerie des créations utilisateurs",
      "url": "https://gael-berru.com/img/3Dscrollanimator.png"
    }
  ],
  "softwareVersion": "1.0",
  "releaseNotes": "Version initiale avec éditeur 3D, système de keyframes et export de code",
  "downloadUrl": "https://gael-berru.com/3Dscrollanimator/",
  "url": "https://gael-berru.com/3Dscrollanimator/",
  "keywords": "animation 3D, scroll, no-code, three.js, web design, creative coding",
  "memoryRequirements": "2GB RAM",
  "processorRequirements": "Processeur moderne avec WebGL",
  "permissions": "Accès au stockage local pour sauvegarde"
}
</script>

    <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization", 
  "name": "3D Scroll Animator",
  "url": "https://gael-berru.com/3Dscrollanimator/",
  "logo": "https://gael-berru.com/img/drone-logo.png",
  "description": "Plateforme no-code de création d'animations 3D interactives pour le web",
  "address": {
    "@type": "PostalAddress",
    "addressCountry": "FR"
  },
  "contactPoint": {
    "@type": "ContactPoint",
    "contactType": "support technique",
    "email": "support@gael-berru.com",
    "availableLanguage": ["French", "English"]
  },
  "sameAs": [
    "https://twitter.com/#",
    "https://github.com/berru-g"
  ]
}
</script>

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-1ZHDWFP01H"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());

        gtag('config', 'G-1ZHDWFP01H');
    </script>

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
    <!-- system de points
    <?php if (Auth::isLoggedIn()): ?>
        <div id="user-menu" class="user-menu">
            <span class="user-avatar" id="user-avatar">
                <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
            </span>
            <span class="user-name" id="user-name">
                <?= htmlspecialchars($_SESSION['user_name']) ?>
                <span class="user-points" id="user-points">
                    💎 <?= $_SESSION['user_points'] ?? 200 ?>
                </span>
            </span>
            <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
            <a href="?logout" class="btn btn-secondary">Déconnexion</a>
        </div>
    <?php endif; ?>-->



    <!--<header class="header">
        <a href="index.php" class="logo">3D Scroll Animator</a>

        <nav class="nav-links">
            <a href="#editor">Éditeur</a>
            <a href="gallery.php">Galerie</a>
            <a href="dashboard.php">Dashboard</a>
        </nav>

        <div class="auth-section">
            <?php if (Auth::isLoggedIn()): ?>
                
                <div id="user-menu" class="user-menu">
                    <span class="user-avatar" id="user-avatar">
                        <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                    </span>
                    <span class="user-name" id="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
                    <a href="?logout" class="btn btn-secondary">Déconnexion</a>
                </div>
            <?php else: ?>
               
                <div id="guest-menu" class="auth-buttons">
                    <button class="btn btn-secondary" onclick="showAuthModal()">
                        <i class="fas fa-sign-in-alt"></i> Connexion
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </header>-->

    <!-- Modal d'Authentification simplifié -->
    <div id="auth-modal" class="auth-modal" style="display: none;">
        <div class="auth-modal-content">
            <div class="auth-modal-header">
                <h2>Connectez-vous pour exporter votre code</h2>
                <button class="close-btn" onclick="closeAuthModal()">×</button>
            </div>

            <div class="auth-options">
                <a href="login.php" class="auth-btn" style="text-decoration: none; text-align: center;">
                    <i class="fas fa-sign-in-alt"></i>
                    Se connecter
                </a>

                <a href="register.php" class="auth-btn" style="text-decoration: none; text-align: center;">
                    <i class="fas fa-user-plus"></i>
                    Créer un compte
                </a>
            </div>

            <div class="auth-benefits">
                <h4>En vous connectant, vous pourrez :</h4>
                <ul>
                    <li>✅ Voir le code complet de votre animation</li>
                    <li>✅ Exporter vers CodePen en 1 clic</li>
                    <li>✅ Sauvegarder vos projets</li>
                    <li>🎁 <strong>Gratuit</strong> - Aucune carte requise</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Modal de sauvegarde de projet -->
    <div id="save-project-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Sauvegarder le projet</h3>
                <button class="close-btn" onclick="closeSaveModal()">×</button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label for="project-title">Titre du projet *</label>
                    <input type="text" id="project-title" placeholder="Mon animation 3D" maxlength="100">
                    <div class="char-count"><span id="title-chars">0</span>/100</div>
                </div>

                <div class="form-group">
                    <label for="project-description">Description (optionnelle)</label>
                    <textarea id="project-description" placeholder="Décrivez votre projet..." rows="3"
                        maxlength="500"></textarea>
                    <div class="char-count"><span id="desc-chars">0</span>/500</div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="modal-make-public">
                        <span class="checkmark"></span>
                        Rendre ce projet public
                    </label>
                    <small style="color: var(--text-light); margin-top: 0.5rem; display: block;">
                        Contribué dans la communauté
                    </small>
                </div>

                <div class="reward-notice">
                    <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--accent);">
                        <span>💎</span>
                        <strong>Bonus : +10 crédits pour chaque sauvegarde !</strong>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeSaveModal()">Annuler</button>
                <button class="btn btn-primary" onclick="confirmSaveProject()">
                    Sauvegarder le projet
                </button>
            </div>
        </div>
    </div>
    <style>
        /* Styles pour la modal de sauvegarde */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }

        .modal-content {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .modal-header h3 {
            margin: 0;
            color: var(--text);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-light);
            cursor: pointer;
            padding: 0.25rem;
        }

        .close-btn:hover {
            color: var(--text);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text);
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: var(--background);
            color: var(--text);
            font-size: 0.875rem;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .char-count {
            text-align: right;
            font-size: 0.75rem;
            color: var(--text-light);
            margin-top: 0.25rem;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            font-weight: normal;
        }

        .checkbox-label input {
            width: auto;
            margin: 0;
        }

        .reward-notice {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid var(--accent);
            border-radius: 6px;
            padding: 1rem;
            margin-top: 1rem;
        }
    </style>

    <!-- ÉDITEUR PARAMETRE -->
    <div class="top-section">
        <div class="sidebar">
            <h1>3D Scroll Animator</h1>
            <div class="section">
                <div class="instructions">
                    <p><strong>Instructions :</strong></p>
                    <p>1. Importez un modèle 3D (GLB/GLTF)</p>
                    <p>2. Utilisez les contrôles pour positionner votre modèle</p>
                    <p>3. Définissez le pourcentage de scroll et ajustez les propriétés</p>
                    <p>4. Ajoutez des keyframes pour créer l'animation</p>
                    <p>5. Copiez le code généré pour l'utiliser sur votre site</p>
                    <?php if (!Auth::isLoggedIn()): ?>
                        <p style="color: var(--primary); margin-top: 10px;">
                            <strong>💡 Astuce :</strong> <a href="register.php"
                                style="color: var(--primary);">Inscrivez-vous</a> pour sauvegarder vos projets !
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            <br>
            <h2 class="section-title">Importation 3D</h2>
            <div class="section">
                <input type="file" id="model-input" accept=".glb,.gltf" style="display: none;">
                <button class="btn" id="import-btn">Importer un modèle 3D</button>

                <button class="btn btn-secondary" onclick="loadTestModel()">Charger modèle test</button>

                <!-- Bouton Record pour utilisateurs connectés -->
                <?php if (Auth::isLoggedIn()): ?>
                    <button class="btn" id="record-btn" onclick="openSaveModal()" style="margin-top: 10px;">
                        <i class="fa-solid fa-floppy-disk"></i> Save Project
                        <span> +10 💎</span>
                    </button>

                    <div class="toggle-container" style="margin-top: 10px; margin-left: 0px;">
                        <label class="toggle-switch">
                            <input type="checkbox" id="make-public" class="toggle-input">
                            <span class="toggle-slider"></span>
                            <span class="toggle-text">Rendre public</span>
                        </label>
                    </div>

                <?php else: ?>
                    <div style="background: var(--grey-light); padding: 10px; border-radius: 6px; margin-top: 10px;">
                        <p style="margin: 0; color: var(--rose); font-size: 0.9rem;">
                            <a href="login.php" style="color: var(--primary);">Connectez-vous</a> pour sauvegarder vos
                            projets
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="section">
                <div class="input-group">
                    <label for="model-scale">Échelle du modèle</label>
                    <input type="range" id="model-scale" min="0.1" max="3" step="0.1" value="1">
                </div>

                <div class="input-group">
                    <label for="keyframe-percentage">Pourcentage de scroll</label>
                    <input type="range" id="keyframe-percentage" min="0" max="100" value="0">
                    <div style="text-align: center; margin-top: 5px;" id="percentage-value">0%</div>
                </div>

            </div>

            <h2 class="section-title">Animation par Scroll</h2>

            <div class="section">

                <div class="tab-container">
                    <div class="tab active" data-tab="position">Position</div>
                    <div class="tab" data-tab="rotation">Rotation</div>
                    <div class="tab" data-tab="scale">Échelle</div>
                </div>



                <div id="position-controls" class="tab-content">
                    <div class="input-group">
                        <label for="pos-x">Position X</label>
                        <input type="range" id="pos-x" min="-10" max="10" step="0.1" value="0">
                    </div>
                    <div class="input-group">
                        <label for="pos-y">Position Y</label>
                        <input type="range" id="pos-y" min="-10" max="10" step="0.1" value="0">
                    </div>
                    <div class="input-group">
                        <label for="pos-z">Position Z</label>
                        <input type="range" id="pos-z" min="-10" max="10" step="0.1" value="0">
                    </div>
                </div>

                <div id="rotation-controls" class="tab-content" style="display: none;">
                    <div class="input-group">
                        <label for="rot-x">Rotation X (degrés)</label>
                        <input type="range" id="rot-x" min="0" max="360" step="1" value="0">
                    </div>
                    <div class="input-group">
                        <label for="rot-y">Rotation Y (degrés)</label>
                        <input type="range" id="rot-y" min="0" max="360" step="1" value="0">
                    </div>
                    <div class="input-group">
                        <label for="rot-z">Rotation Z (degrés)</label>
                        <input type="range" id="rot-z" min="0" max="360" step="1" value="0">
                    </div>
                </div>

                <div id="scale-controls" class="tab-content" style="display: none;">
                    <div class="input-group">
                        <label for="scale-x">Échelle X</label>
                        <input type="range" id="scale-x" min="0.1" max="3" step="0.1" value="1">
                    </div>
                    <div class="input-group">
                        <label for="scale-y">Échelle Y</label>
                        <input type="range" id="scale-y" min="0.1" max="3" step="0.1" value="1">
                    </div>
                    <div class="input-group">
                        <label for="scale-z">Échelle Z</label>
                        <input type="range" id="scale-z" min="0.1" max="3" step="0.1" value="1">
                    </div>
                </div>

                <button class="btn" id="add-keyframe">Ajouter une keyframe</button>
            </div>

            <div class="section">
                <h2 class="section-title">Keyframes</h2>
                <div class="keyframes-list" id="keyframes-list">
                    <div style="text-align: center; color: #a6adc8; padding: 20px;">Aucune keyframe ajoutée</div>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">Code Généré</h2>
                <textarea class="code-editor" id="generated-code"
                    readonly>// Importez un modèle et ajoutez des keyframes pour générer le code</textarea>
                <button class="btn btn-secondary" id="copy-code">Copier le code</button>
            </div>


        </div>

        <div class="main-content">
            <div class="viewer-container">
                <div id="viewer"></div>
                <div id="loading" class="loading" style="display: none;">Chargement...</div>
                <div class="preview-container">
                    <div class="preview-title">Aperçu du Scroll</div>
                    <div class="preview-scroll" id="preview-scroll">
                        <div class="preview-handle" id="preview-handle"></div>
                    </div>
                    <div class="preview-percentage" id="preview-percentage">0%</div>
                </div>
            </div>
            <div class="scroll-ruler">
                <div class="ruler-track" id="ruler-track">
                    <div class="ruler-handle" id="ruler-handle"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Éditeur de code complet -->
    <div class="code-exporter">
        <h2 class="section-title">Code Complet à Copier</h2>

        <!-- État non connecté -->
        <div id="code-guest" class="code-guest" style="display: none;"> <!-- Toujours caché -->
            <div class="guest-message">
                <div class="guest-icon"><img src="../img/mascotte-code.png"></div>
                <h3>Connectez-vous pour débloquer l'export</h3>
                <p>Accédez au code complet et à l'export CodePen en vous connectant gratuitement</p>
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </a>
                <p style="margin-top: 1rem; font-size: 0.9rem;">
                    Pas encore de compte ? <a href="register.php" style="color: var(--primary);">Inscription
                        gratuite</a>
                </p>
            </div>
        </div>

        <!-- État connecté (gratuit) -->
        <div id="code-free-user" class="code-editors"
            style="<?= (Auth::isLoggedIn() && $_SESSION['subscription'] === 'pro') ? 'display:flex;' : 'display:none;' ?>">
            <div class="code-box">
                <div class="code-box-title">HTML</div>
                <div class="copy-icon" onclick="copyCode('full-html-code')" title="Copier le HTML">
                    <i class="fa-regular fa-copy"></i>
                </div>
                <textarea id="full-html-code" readonly></textarea>
            </div>

            <div class="code-box">
                <div class="code-box-title">CSS</div>
                <div class="copy-icon" onclick="copyCode('full-css-code')" title="Copier le CSS">
                    <i class="fa-regular fa-copy"></i>
                </div>
                <textarea id="full-css-code" readonly></textarea>
            </div>

            <div class="code-box">
                <div class="code-box-title">JavaScript</div>
                <div class="copy-icon" onclick="copyCode('full-js-code')" title="Copier le JS">
                    <i class="fa-regular fa-copy"></i>
                </div>
                <textarea id="full-js-code" readonly></textarea>
            </div>

            <button class="btn" id="open-codepen">
                <i class="fa-brands fa-codepen" style="margin-right:6px;"></i>Ouvrir dans CodePen 
                
                <span> -50 💎</span>
            </button>

            <!-- Call to Action Upgrade 
            <div class="upgrade-cta">
                <div class="upgrade-badge">💎 PRO</div>
                <h4>Passez Pro pour sauvegarder et partager</h4>
                <p>Avec le plan Pro à 9,90€/mois, vous pourrez :</p>
                <ul>
                    <li>✅ Sauvegarder vos projets dans le cloud</li>
                    <li>✅ Apparaître dans la galerie communautaire</li>
                    <li>✅ Accéder à votre historique complet</li>
                    <li>✅ Obtenir un profil public</li>
                </ul>
                <button class="btn btn-premium" onclick="openPricing()">
                    <i class="fas fa-crown"></i> Devenir Pro - 9,90€/mois
                </button>
            </div>
        </div>-->

            <!-- État connecté (Pro) -->
            <div id="code-pro-user" class="code-pro-features"
                style="<?= (Auth::isLoggedIn() && $_SESSION['subscription'] === 'free') ? 'display:block;' : 'display:none;' ?>">
                <div class="code-editors">
                    <div class="code-box">
                        <div class="code-box-title">HTML</div>
                        <div class="copy-icon" onclick="copyCode('full-html-code')" title="Copier le HTML">
                            <i class="fa-regular fa-copy"></i>
                        </div>
                        <textarea id="full-html-code" readonly></textarea>
                    </div>

                    <div class="code-box">
                        <div class="code-box-title">CSS</div>
                        <div class="copy-icon" onclick="copyCode('full-css-code')" title="Copier le CSS">
                            <i class="fa-regular fa-copy"></i>
                        </div>
                        <textarea id="full-css-code" readonly></textarea>
                    </div>

                    <div class="code-box">
                        <div class="code-box-title">JavaScript</div>
                        <div class="copy-icon" onclick="copyCode('full-js-code')" title="Copier le JS">
                            <i class="fa-regular fa-copy"></i>
                        </div>
                        <textarea id="full-js-code" readonly></textarea>
                    </div>
                </div>

                <button class="btn" id="open-codepen">
                    <i class="fa-brands fa-codepen" style="margin-right:6px;"></i>Ouvrir dans CodePen
                </button>

                <!-- Boutons Pro -->
                <div class="pro-actions">
                    <button class="btn btn-primary" onclick="saveProject()">
                        <i class="fas fa-cloud-upload-alt"></i> Sauvegarder le projet
                    </button>
                    <button class="btn btn-secondary" onclick="publishToGallery()">
                        <i class="fas fa-share"></i> Publier dans la galerie
                    </button>
                </div>
            </div>
        </div>



        <!-- Achat de points à config avec stripe ou lemonsqueezie -->
        <div class="points-shop">
            <h3>💎 Gagnez du temps avec les Packs ! Bientôt disponible</h3>
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


        <script>
            function copyCode(id) {
                const textarea = document.getElementById(id);
                textarea.select();
                document.execCommand("copy");

                const icon = event.currentTarget;
                const old = icon.textContent;
                icon.textContent = "✅";
                setTimeout(() => (icon.textContent = old), 1000);
            }
        </script>

        <!--<script>
            // Export dans codepen
            document.getElementById("open-codepen").addEventListener("click", () => {
                const html = document.getElementById("full-html-code").value;
                const css = document.getElementById("full-css-code").value;
                const js = document.getElementById("full-js-code").value;

                const data = {
                    title: "Animation 3D Scroll – Export",
                    html: html,
                    css: css,
                    js: js,
                    editors: "101", // HTML/CSS/JS actifs
                };

                const form = document.createElement("form");
                form.method = "POST";
                form.action = "https://codepen.io/pen/define";
                form.target = "_blank";

                const input = document.createElement("input");
                input.type = "hidden";
                input.name = "data";
                input.value = JSON.stringify(data);
                form.appendChild(input);

                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            });
        </script>-->

        <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.min.js"></script>
        <script src="scriptV2.js"></script>

        <script>
            // Variable globale pour le chargement de projet
            const loadProjectId = <?= $loadProjectId ? $loadProjectId : 'null' ?>;

            // Après l'initialisation de l'application
            setTimeout(() => {
                if (loadProjectId) {
                    loadProject(loadProjectId);
                }
            }, 1000);


            // DEBUG
            async function debugAll() {
                console.log('=== DEBUG COMPLET ===');

                // Test 1: Points actuels
                const response1 = await fetch('api.php?action=get_user_points');
                const points = await response1.json();
                console.log('1. Points actuels:', points);

                /* Test 2: Déduction
                const response2 = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=deduct_points'
                });
                const deduct = await response2.json();
                console.log('2. Déduction:', deduct);
*/
                // Test 3: Vérifie la session
                console.log('3. Session PHP:', <?= json_encode($_SESSION ?? []) ?>);
            }
            debugAll();
        </script>
</body>

</html>