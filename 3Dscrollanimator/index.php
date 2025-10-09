<?php
// index.php
require_once 'config.php';
require_once 'auth.php';

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
    <link rel="shortcut icon" href="/img/logo.png" />
    <link rel="apple-touch-icon" href="/img/logo.png" />
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

    <!-- Schema.org JSON-LD (identique à avant) -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "SoftwareApplication",
      "name": "ScrollForge 3D",
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
      "softwareVersion": "1.0",
      "releaseNotes": "Version initiale avec éditeur 3D, système de keyframes et export de code",
      "downloadUrl": "https://scrollforge3d.com",
      "url": "https://scrollforge3d.com",
      "keywords": "animation 3D, scroll, no-code, three.js, web design, creative coding",
      "memoryRequirements": "2GB RAM",
      "processorRequirements": "Processeur moderne avec WebGL",
      "permissions": "Accès au stockage local pour sauvegarde"
    }
    </script>
</head>

<body>
    <!-- Notifications Toast -->
    <div id="notification-container" class="notification-container"></div>

    <header class="header">
        <a href="index.php" class="logo">3D Scroll Animator</a>

        <nav class="nav-links">
            <a href="#editor">Éditeur</a>
            <a href="gallery.php">Galerie</a>
            <a href="dashboard.php">Dashboard</a>
        </nav>

        <div class="auth-section">
            <?php if (Auth::isLoggedIn()): ?>
                <!-- État connecté -->
                <div id="user-menu" class="user-menu">
                    <span class="user-avatar" id="user-avatar">
                        <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                    </span>
                    <span class="user-name" id="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
                    <a href="?logout" class="btn btn-secondary">Déconnexion</a>
                </div>
            <?php else: ?>
                <!-- État non connecté -->
                <div id="guest-menu" class="auth-buttons">
                    <button class="btn btn-secondary" onclick="showAuthModal()">
                        <i class="fas fa-sign-in-alt"></i> Connexion
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </header>

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

    <div class="top-section">
        <div class="sidebar">
            <div class="section">
                <h2 class="section-title">Importation 3D</h2>
                <input type="file" id="model-input" accept=".glb,.gltf" style="display: none;">
                <button class="btn" id="import-btn">Importer un modèle 3D</button>
                <div class="input-group">
                    <label for="model-scale">Échelle du modèle</label>
                    <input type="range" id="model-scale" min="0.1" max="3" step="0.1" value="1">
                </div>
                <button class="btn btn-secondary" onclick="loadTestModel()">Charger modèle test</button>

                <!-- Bouton Record pour utilisateurs connectés -->
                <?php if (Auth::isLoggedIn()): ?>
                    <button class="btn" id="record-btn" onclick="saveProject()" style="margin-top: 10px;">
                        💾 Enregistrer le projet
                    </button>
                    <div class="input-group" style="margin-top: 10px; margin-left: 20px;">Rendre public
                        <label>
                            <input type="checkbox" id="make-public"> 
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
                <h2 class="section-title">Animation par Scroll</h2>
                <div class="tab-container">
                    <div class="tab active" data-tab="position">Position</div>
                    <div class="tab" data-tab="rotation">Rotation</div>
                    <div class="tab" data-tab="scale">Échelle</div>
                </div>

                <div class="input-group">
                    <label for="keyframe-percentage">Pourcentage de scroll</label>
                    <input type="range" id="keyframe-percentage" min="0" max="100" value="0">
                    <div style="text-align: center; margin-top: 5px;" id="percentage-value">0%</div>
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
                <div class="guest-icon">🔒</div>
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
            style="<?= (Auth::isLoggedIn() && $_SESSION['subscription'] === 'free') ? 'display:flex;' : 'display:none;' ?>">
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
                style="<?= (Auth::isLoggedIn() && $_SESSION['subscription'] === 'pro') ? 'display:block;' : 'display:none;' ?>">
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

        <br>
        <p style="text-align: center">Dev by <a href="https://gael-berru.com"
                style="color: white; text-decoration: none;">berru-g</a></p>

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

        <script>
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
        </script>

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
        </script>
</body>

</html>