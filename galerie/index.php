<?php
session_start();
require_once 'figueconfite.php';

$pdo = getDbConnection();

// Créer la table si elle n'existe pas
$pdo->exec("CREATE TABLE IF NOT EXISTS galerie_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_path VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Initialiser images par défaut
$count = $pdo->query("SELECT COUNT(*) FROM galerie_images")->fetchColumn();
if ($count == 0) {
    $defaultImages = [
        ['../img/3Dscrollanimator.png', 'Saas no code'],
        ['../img/Interface-3Dscrollanimator.png', 'Interface facile'],
        ['../img/mascotte-easy2.png', 'Géneration de code auto'],
        ['../img/mascotte-sav.png', 'Gagne des crédits comme beta tester']
    ];

    $stmt = $pdo->prepare("INSERT INTO galerie_images (image_path, title, display_order) VALUES (?, ?, ?)");
    foreach ($defaultImages as $index => $image) {
        $stmt->execute([$image[0], $image[1], $index]);
    }
}

// Traitement login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (verifyCsrfToken($csrf)) {
        if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
            $_SESSION['galerie_admin'] = true;
            $_SESSION['admin_ip'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['admin_login_time'] = time();
            header('Location: index.php');
            exit;
        } else {
            $loginError = "Identifiants incorrects";
        }
    } else {
        $loginError = "Token CSRF invalide";
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$isAdmin = isAdmin();

// Récupérer les images
$stmt = $pdo->query("SELECT * FROM galerie_images ORDER BY display_order, created_at DESC");
$images = $stmt->fetchAll();

$csrfToken = generateCsrfToken();
?>
<doctype html>
    <html lang="fr">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gaël Leberruyer | Créateur de solutions</title>
        <link rel="shortcut icon" href="/img/logo.png" />
        <link rel="apple-touch-icon" href="/img/logo.png" />
        <meta name="description"
            content="Développeur autodidacte et passionné par les solutions web et web3. Je conçois des projets concrets alliant technologie, durabilité et impact social.">
        <meta name="keywords"
            content="site web sur mesure, dev web sur mesure, Gael Leberruyer, Gael Berru, web3, crypto enthusiast, vente de pixel, codeur de site nantes, developpeur nantes,">
        <meta name="author" content="Gael Berru.">
        <meta name="robots" content="noai">
        <meta property="og:url" content="https://gael-berru.com">
        <link rel="canonical" href="https://gael-berru.com" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.20/dist/sweetalert2.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.20/dist/sweetalert2.all.min.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basiclightbox@5.0.4/dist/basicLightbox.min.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
        <link rel="stylesheet" href="../new.css">
        <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.min.js"></script>
        <style>
            /* Galerie */
            .gallery {
                padding: 8rem 10%;
                text-align: center;
                background-color: var(--dark-1);
                border-radius: 12px;
                margin: 20px;
            }

            .gallery h2 {
                font-size: 2.5rem;
                margin-bottom: 3rem;
                color: linear-gradient(45deg, #eceaea, #c9a769);
            }

            .gallery-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 1.5rem;
            }

            .gallery-item {
                overflow: hidden;
                position: relative;
                border-radius: 12px;
            }

            .gallery-item img {
                width: 100%;
                height: 300px;
                object-fit: cover;
                transition: transform 0.5s, filter 0.3s;
                filter: brightness(0.8);
                border-radius: 12px;
            }

            .gallery-item:hover img {
                transform: scale(1.05);
                filter: brightness(1);
            }

            .gallery-item-caption {
                position: absolute;
                bottom: 0;
                left: 0;
                width: 100%;
                padding: 1rem;
                background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
                color: white;
                transform: translateY(100%);
                transition: transform 0.3s;
                border-radius: 0 0 12px 12px;
            }

            .gallery-item:hover .gallery-item-caption {
                transform: translateY(0);
            }

            /* ADMIN */
            .admin-bar {
                position: relative;
                width: 100%;
                padding: 0.5rem 2%;
                display: flex;
                justify-content: center;
                align-items: center;
                margin-bottom: 1rem;
                margin-top: 1rem;
            }

            .admin-controls {
                position: absolute;
                top: 20px;
                right: 20px;
                display: flex;
                gap: 5px;
                opacity: 0;
                transition: opacity 0.3s;
            }

            .gallery-item:hover .admin-controls {
                opacity: 1;
            }

            .edit-btn,
            .delete-btn {
                border: none;
                color: white;
                width: 30px;
                height: 30px;
                padding: 5px 5px;
                border-radius: 50%;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .edit-btn {
                background: #ab9ff2;
            }

            .delete-btn {
                background: #ee6055;
            }

            .edit-title-input {
                width: 100%;
                padding: 8px;
                border: 1px solid #444;
                border-radius: 4px;
                background: rgba(255, 255, 255, 0.9);
                text-align: center;
                font-size: 14px;
            }

            .popup {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                z-index: 1000;
            }

            .popup-content {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: #1a1a1a;
                padding: 30px;
                border-radius: 12px;
                width: 90%;
                max-width: 400px;
                color: white;
            }

            .popup h3 {
                color: #ab9ff2;
                margin-bottom: 20px;
                text-align: center;
            }

            .popup input,
            .popup button {
                width: 100%;
                padding: 12px;
                margin: 10px 0;
                border: 1px solid #444;
                border-radius: 6px;
                background: #2a2a2a;
                color: white;
            }

            .popup button {
                background: #2575fc;
                border: none;
                cursor: pointer;
                font-weight: 600;
            }

            .message {
                padding: 10px;
                margin: 10px 0;
                border-radius: 4px;
                text-align: center;
            }

            .success {
                background: #60d394;
                color: white;
            }

            .error {
                background: #ee6055;
                color: white;
            }
        </style>
    </head>

    <body>

        <!-- original by https://codepen.io/themrsami/pen/KwpGYNX?editors=1100 -->
        <div class="floating-elements">
            <div class="floating-circle"></div>
            <div class="floating-circle"></div>
            <div class="floating-circle"></div>
        </div>

        <!-- Advanced Navigation Bar -->
        <nav class="navbar-container">
            <div class="navbar">
                <a href="#" class="navbar-brand"><!--code-brush-palette-->
                    <div class="logo-icon"><i class="fa-solid fa-palette"></i></div>
                    <span class="brand-text">Web artisan</span>
                </a>

                <!-- Navigation Links -->
                <ul class="navbar-nav" id="navbarNav">
                    <li class="nav-item">
                        <a href="../index.html" class="nav-link active">
                            <svg class="nav-icon" viewBox="0 0 24 24">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9,22 9,12 15,12 15,22"></polyline>
                            </svg>
                            <span>Home</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../index.html#skill" class="nav-link">
                            <i class="fa-solid fa-palette"></i>
                            <span>skill</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../devis/index.html" class="nav-link">
                            <i class="fa-solid fa-list"></i>
                            <span>Devis</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../index.html#avis" class="nav-link">
                            <i class="fa-solid fa-comment"></i>

                            <span>avis</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../index.html#contact" class="nav-link">
                            <i class="fa-solid fa-envelope"></i>
                            <span>Contact</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../3D/index.html" class="nav-link">
                            <i class="fa-solid fa-cubes"></i>
                            <span>3D</span>
                        </a>
                        <div class="tooltip">Essayer l'expérience immersive</div>
                    </li>
                    <li class="nav-item"><!--fr.fiverr.com/berruaka/code-your-static-site-app-or-tools-->
                        <a href="../index.html#contact" rel="noopener" target="_blank" class="cta-button">
                            Je veux un site
                        </a>
                    </li>
                </ul>

                <!-- Menu mobile -->
                <button class="menu-mobile" id="mobileToggle">
                    <div class="hamburger">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </button>
            </div>
        </nav>

        <!-- Mobile Menu Overlay -->
        <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

        <!-- Mobile Menu -->
        <div class="mobile-menu" id="mobileMenu">
            <div class="mobile-menu-header">
                <a href="../paint/index.html" class="mobile-menu-brand" title="Egg#2">
                    <div class="logo-icon"><i class="fa-solid fa-brush"></i></div>
                    <span class="brand-text"></span>
                </a>
                <button class="mobile-menu-close" id="mobileMenuClose">
                    <span>×</span>
                </button>
            </div>

            <ul class="mobile-menu-nav">
                <li class="mobile-menu-item">
                    <a href="../index.html#home" class="mobile-menu-link active">
                        <svg class="mobile-menu-icon" viewBox="0 0 24 24">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9,22 9,12 15,12 15,22"></polyline>
                        </svg>
                        <span>Home</span>
                    </a>
                </li>
                <li class="mobile-menu-item">
                    <a href="../index.html#skill" class="mobile-menu-link">
                        <i class="fa-solid fa-palette"></i>
                        <span>skill</span>
                    </a>
                </li>
                <li class="mobile-menu-item">
                    <a href="../devis/index.html" class="mobile-menu-link">
                        <i class="fa-solid fa-list"></i>
                        <span>Devis</span>
                    </a>
                </li>
                <li class="mobile-menu-item">
                    <a href="../index.html#avis" class="mobile-menu-link">
                        <i class="fa-solid fa-comment"></i>

                        <span>avis</span>
                    </a>
                </li>
                <li class="mobile-menu-item">
                    <a href="../index.html#contact" class="mobile-menu-link">
                        <i class="fa-solid fa-envelope"></i>
                        <span>Contact</span>
                    </a>
                </li>
            </ul>

            <div class="mobile-cta">
                <a href="../index.html#contact" rel="noopener" target="_blank" class="mobile-cta-button">
                    Je veux un site
                </a>
            </div>
        </div>

        <!-- GALLERY INTERACTIV -->
        <section class="gallery">
            <h2 class="section-title">Mes derniers travaux :</h2>
            <!-- connexion au CMS -->
        <div class="admin-bar">
            <?php if (!$isAdmin): ?>
                <button onclick="showLogin()"
                    style="background: transparent; color: white; border: 1px solid white; padding: 10px 10px; border-radius: 50%;">
                    <i class="fas fa-cog"></i>
                </button>
            <?php else: ?>
                <a href="?logout=1" class="cta-button" style="background: #ff977dff; border: none;">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
                <button class="cta-button" onclick="showAddImage()" style="background: #6ea3feff; border: none; margin-left: 10px;">
                    <i class="fas fa-plus"></i> Ajouter
                </button>
            <?php endif; ?>
        </div>

            <div class="gallery-grid" id="galleryGrid">
                <?php foreach ($images as $image): ?>
                    <div class="gallery-item" data-id="<?= htmlspecialchars($image['id']) ?>">
                        <img src="<?= htmlspecialchars($image['image_path']) ?>"
                            alt="<?= htmlspecialchars($image['title']) ?>" data-aos="fade-up" data-aos-delay="300" />
                        <?php if ($isAdmin): ?>
                            <div class="admin-controls">
                                <button class="edit-btn" onclick="changeImage(<?= htmlspecialchars($image['id']) ?>)">
                                    <i class="fas fa-image"></i>
                                </button>
                                <button class="delete-btn" onclick="deleteImage(<?= htmlspecialchars($image['id']) ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        <?php endif; ?>
                        <div class="gallery-item-caption">
                            <?php if ($isAdmin): ?>
                                <input type="text" class="edit-title-input" value="<?= htmlspecialchars($image['title']) ?>"
                                    data-id="<?= htmlspecialchars($image['id']) ?>"
                                    onchange="updateTitle(<?= htmlspecialchars($image['id']) ?>, this.value)">
                            <?php else: ?>
                                <?= htmlspecialchars($image['title']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </section>

        <section id="Devis" class="content-section" data-aos="zoom-in" data-aos-delay="100">
            <a href="../index.html#contact" rel="noopener" target="_blank" class="cta-button">Discutons de votre projet ?</a>
        </section>

        <!-- POPUP LOGIN -->
        <div id="loginPopup" class="popup">
            <div class="popup-content">
                <h3>Connexion Admin</h3>
                <?php if (isset($loginError)): ?>
                    <div class="message error"><?= htmlspecialchars($loginError) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="text" name="username" placeholder="Nom d'utilisateur" required autocomplete="username">
                    <input type="password" name="password" placeholder="Mot de passe" required
                        autocomplete="current-password">
                    <button type="submit" name="login_submit">Se connecter</button>
                </form>
                <button onclick="hideLogin()" style="background: #777;">Annuler</button>
            </div>
        </div>

        <!-- POPUP UPLOAD IMAGE -->
        <div id="uploadPopup" class="popup">
            <div class="popup-content">
                <h3>Changer l'image</h3>
                <form id="uploadForm" enctype="multipart/form-data">
                    <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" required>
                    <input type="hidden" name="id" id="uploadImageId">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <button type="submit">Uploader</button>
                </form>
                <div id="uploadMessage"></div>
                <button onclick="hideUpload()" style="background: #777;">Annuler</button>
            </div>
        </div>

        <!-- POPUP AJOUT IMAGE -->
        <div id="addImagePopup" class="popup">
            <div class="popup-content">
                <h3>Ajouter une image</h3>
                <form id="addImageForm" enctype="multipart/form-data">
                    <input type="text" name="title" placeholder="Titre de l'image" required maxlength="255">
                    <input type="file" name="new_image" accept="image/jpeg,image/png,image/gif,image/webp" required>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <button type="submit">Ajouter</button>
                </form>
                <div id="addImageMessage"></div>
                <button onclick="hideAddImage()" style="background: #777;">Annuler</button>
            </div>
        </div>


        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js"></script>
        <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
        <script>
            const CSRF_TOKEN = '<?= htmlspecialchars($csrfToken) ?>';
            let currentImageId = null;

            function showLogin() { document.getElementById('loginPopup').style.display = 'block'; }
            function hideLogin() { document.getElementById('loginPopup').style.display = 'none'; }
            function showUpload() { document.getElementById('uploadPopup').style.display = 'block'; }
            function hideUpload() { document.getElementById('uploadPopup').style.display = 'none'; }
            function showAddImage() { document.getElementById('addImagePopup').style.display = 'block'; }
            function hideAddImage() { document.getElementById('addImagePopup').style.display = 'none'; }

            function changeImage(id) {
                currentImageId = id;
                document.getElementById('uploadImageId').value = id;
                showUpload();
            }

            async function updateTitle(id, newTitle) {
                const formData = new FormData();
                formData.append('action', 'update_title');
                formData.append('id', id);
                formData.append('title', newTitle);
                formData.append('csrf_token', CSRF_TOKEN);

                try {
                    const response = await fetch('galerie-actions.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (!result.success) {
                        alert('Erreur: ' + (result.error || 'Échec de la mise à jour'));
                        location.reload();
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    alert('Erreur de connexion');
                }
            }

            document.getElementById('uploadForm').addEventListener('submit', async function (e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'upload_image');

                try {
                    const response = await fetch('galerie-actions.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.success) {
                        const img = document.querySelector(`[data-id="${currentImageId}"] img`);
                        img.src = result.new_path + '?t=' + new Date().getTime();
                        hideUpload();
                        showMessage('uploadMessage', 'Image mise à jour avec succès', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showMessage('uploadMessage', result.error, 'error');
                    }
                } catch (error) {
                    showMessage('uploadMessage', 'Erreur de connexion', 'error');
                }
            });

            document.getElementById('addImageForm').addEventListener('submit', async function (e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'add_image');

                try {
                    const response = await fetch('galerie-actions.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.success) {
                        showMessage('addImageMessage', 'Image ajoutée avec succès', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showMessage('addImageMessage', result.error, 'error');
                    }
                } catch (error) {
                    showMessage('addImageMessage', 'Erreur de connexion', 'error');
                }
            });

            async function deleteImage(id) {
                if (!confirm('Supprimer cette image ?')) return;

                const formData = new FormData();
                formData.append('action', 'delete_image');
                formData.append('id', id);
                formData.append('csrf_token', CSRF_TOKEN);

                try {
                    const response = await fetch('galerie-actions.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.success) {
                        document.querySelector(`[data-id="${id}"]`).remove();
                    } else {
                        alert('Erreur: ' + (result.error || 'Échec de la suppression'));
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    alert('Erreur de connexion');
                }
            }

            function showMessage(elementId, message, type) {
                const element = document.getElementById(elementId);
                element.innerHTML = `<div class="message ${type}">${message}</div>`;
                setTimeout(() => element.innerHTML = '', 3000);
            }

            window.onclick = function (event) {
                if (event.target.classList.contains('popup')) {
                    event.target.style.display = 'none';
                }
            }

            <?php if (isset($loginError)): ?>
                showLogin();
            <?php endif; ?>

            // Mobile Navigation Toggle
            document.addEventListener('DOMContentLoaded', function () {
                const mobileToggle = document.getElementById('mobileToggle');
                const mobileMenu = document.getElementById('mobileMenu');
                const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
                const mobileMenuClose = document.getElementById('mobileMenuClose');
                const mobileMenuLinks = document.querySelectorAll('.mobile-menu-link');
                const navLinks = document.querySelectorAll('.nav-link');

                // Function to open mobile menu
                function openMobileMenu() {
                    console.log('Opening mobile menu');
                    mobileToggle.classList.add('active');
                    mobileMenu.classList.add('active');
                    mobileMenuOverlay.classList.add('active');
                    document.body.style.overflow = 'hidden'; // Prevent body scroll
                }

                // Function to close mobile menu
                function closeMobileMenu() {
                    console.log('Closing mobile menu');
                    mobileToggle.classList.remove('active');
                    mobileMenu.classList.remove('active');
                    mobileMenuOverlay.classList.remove('active');
                    document.body.style.overflow = ''; // Restore body scroll
                }

                // Toggle mobile menu when hamburger is clicked
                mobileToggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    if (mobileMenu.classList.contains('active')) {
                        closeMobileMenu();
                    } else {
                        openMobileMenu();
                    }
                });

                // Close mobile menu when close button is clicked
                mobileMenuClose.addEventListener('click', function (e) {
                    e.preventDefault();
                    closeMobileMenu();
                });

                // Close mobile menu when overlay is clicked
                mobileMenuOverlay.addEventListener('click', function () {
                    closeMobileMenu();
                });

                // Close mobile menu when clicking on mobile menu links
                mobileMenuLinks.forEach(link => {
                    link.addEventListener('click', function () {
                        closeMobileMenu();

                        // Remove active class from all mobile links
                        mobileMenuLinks.forEach(l => l.classList.remove('active'));
                        // Add active class to clicked link
                        this.classList.add('active');

                        // Also update desktop nav active state
                        const href = this.getAttribute('href');
                        navLinks.forEach(navLink => {
                            navLink.classList.remove('active');
                            if (navLink.getAttribute('href') === href) {
                                navLink.classList.add('active');
                            }
                        });
                    });
                });

                // Close mobile menu when clicking on desktop nav links
                navLinks.forEach(link => {
                    link.addEventListener('click', function () {
                        closeMobileMenu();

                        // Remove active class from all links
                        navLinks.forEach(l => l.classList.remove('active'));
                        // Add active class to clicked link (except CTA button)
                        if (!this.classList.contains('cta-button')) {
                            this.classList.add('active');

                            // Also update mobile nav active state
                            const href = this.getAttribute('href');
                            mobileMenuLinks.forEach(mobileLink => {
                                mobileLink.classList.remove('active');
                                if (mobileLink.getAttribute('href') === href) {
                                    mobileLink.classList.add('active');
                                }
                            });
                        }
                    });
                });

                // Close mobile menu on escape key
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
                        closeMobileMenu();
                    }
                });

                // Navbar scroll effect - Remove auto-hide, keep it sticky
                window.addEventListener('scroll', function () {
                    const navbar = document.querySelector('.navbar-container');
                    let scrollTop = window.pageYOffset || document.documentElement.scrollTop;

                    // Add/remove scroll class for styling changes if needed
                    if (scrollTop > 50) {
                        navbar.classList.add('scrolled');
                    } else {
                        navbar.classList.remove('scrolled');
                    }
                });

                // Add hover effect to floating circles
                const floatingCircles = document.querySelectorAll('.floating-circle');
                floatingCircles.forEach(circle => {
                    circle.addEventListener('mouseenter', function () {
                        this.style.transform = 'scale(1.2)';
                    });

                    circle.addEventListener('mouseleave', function () {
                        this.style.transform = 'scale(1)';
                    });
                });

                // Smooth scrolling for navigation links
                document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                    anchor.addEventListener('click', function (e) {
                        e.preventDefault();
                        const target = document.querySelector(this.getAttribute('href'));
                        if (target) {
                            target.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    });
                });

                // Handle window resize
                window.addEventListener('resize', function () {
                    if (window.innerWidth > 992 && mobileMenu.classList.contains('active')) {
                        closeMobileMenu();
                    }
                });
            });


            // Initialisation de AOS
            document.addEventListener('DOMContentLoaded', function () {
                AOS.init({
                    duration: 1200,
                    once: true,
                    easing: 'ease-out-back'
                });
            });
        </script>
    </body>
    </html>