<?php
// footer.php
require_once 'config.php';

// Récupérer les stats
$db = getDB();

// Nombre d'utilisateurs
$stmt = $db->query("SELECT COUNT(*) as total_users FROM users");
$totalUsers = $stmt->fetch()['total_users'];

// Nombre de projets publics
$stmt = $db->query("SELECT COUNT(*) as public_projects FROM projects WHERE is_public = true");
$publicProjects = $stmt->fetch()['public_projects'];

// Nombre d'achats de points (estimation)
$stmt = $db->query("SELECT COUNT(*) as point_purchases FROM point_transactions WHERE status = 'completed'");
$pointPurchases = $stmt->fetch()['point_purchases'];

// Nombre total de points distribués
$stmt = $db->query("SELECT SUM(points_amount) as total_points FROM point_transactions WHERE status = 'completed'");
$totalPoints = $stmt->fetch()['total_points'] ?? 0;
?>

<footer class="footer">
    <div class="footer-container">
        <!-- Section Statistiques -->
        <div class="footer-stats">
            <h3>Notre Communauté</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?= $totalUsers ?></div>
                    <div class="stat-label">Beta Tester</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $publicProjects ?></div>
                    <div class="stat-label">Projets Publics</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $pointPurchases ?></div>
                    <div class="stat-label">Achetés</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= number_format($totalPoints) ?></div>
                    <div class="stat-label">Points Distribués</div>
                </div>
            </div>
        </div>

        <!-- Section Présentation -->
        <div class="footer-about">
            <div class="footer-brand">
                <h3>3D Scroll Animator</h3>
                <p class="footer-description">
                    Plateforme no-code pour créer des animations 3D synchronisées au scroll.
                    Importez vos modèles, définissez des keyframes et générez du code prêt à l'emploi.
                </p>
                <div class="footer-features">
                    <span class="feature-tag">No-Code</span>
                    <span class="feature-tag">Real-time</span>
                    <span class="feature-tag">Export Instant</span>
                    <span class="feature-tag">Professional</span>
                </div>
            </div>
        </div>

        <!-- Liens Rapides -->
        <div class="footer-links">
            <div class="link-group">
                <h4>Navigation</h4>
                <ul>
                    <li><a href="index.php">Éditeur</a></li>
                    <li><a href="gallery.php">Galerie</a></li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="tarif.php">Points & Tarifs</a></li>
                </ul>
            </div>

            <div class="link-group">
                <h4>Ressources à venir</h4>
                <ul>
                    <li><a href="docs.php">Documentation</a></li>
                    <li><a href="tutorials.php">Tutoriels</a></li>
                    <li><a href="templates.php">Templates</a></li>
                    <li><a href="gallery.php">Communauté</a></li>
                </ul>
            </div>

            <div class="link-group">
                <h4>Support</h4>
                <ul>
                    <li><a href="contact.php">Centre d'aide</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="contact.php">Status</a></li>
                    <li><a href="contact.php">Feedback</a></li>
                </ul>
            </div>
        </div>

        <!-- FAQ -->
        <div class="footer-faq">
            <h4>FAQ Rapide</h4>
            <div class="faq-items">
                <details class="faq-item">
                    <summary>Comment importer mon modèle 3D ?</summary>
                    <p>Utilisez le bouton "Importer un modèle 3D" et sélectionnez votre fichier .glb ou .gltf</p>
                </details>

                <details class="faq-item">
                    <summary>Les points sont-ils obligatoires ?</summary>
                    <p>Les 200 points offerts à l'inscription permettent de tester la plateforme. Les points
                        supplémentaires permettent de sauvegarder plus de projets.</p>
                </details>

                <details class="faq-item">
                    <summary>Puis-je utiliser le code généré commercialement ?</summary>
                    <p>Oui ! Tout le code généré est 100% vôtre, sans restriction d'usage.</p>
                </details>
            </div>
        </div>

        <!-- Liens Utiles avec Logos -->
        <div class="footer-resources">
            <h4>Ressources</h4>
            <div class="resource-links">
                <a href="https://github.com/berru-g/3Dscrollanimator/" target="_blank" class="resource-link" title="GitHub - Dépôts de code">
                    <img src="https://cdn.jsdelivr.net/npm/simple-icons@v8/icons/github.svg" alt="GitHub">
                    <span>GitHub</span>
                </a>

                <a href="https://codepen.io/h-lautre/" target="_blank" class="resource-link" title="CodePen - Éditeur en ligne">
                    <img src="https://cdn.jsdelivr.net/npm/simple-icons@v8/icons/codepen.svg" alt="CodePen">
                    <span>CodePen</span>
                </a>

                <a href="https://sketchfab.com" target="_blank" class="resource-link" title="Sketchfab - Modèles 3D">
                    <img src="https://cdn.jsdelivr.net/npm/simple-icons@v8/icons/sketchfab.svg" alt="Sketchfab">
                    <span>Sketchfab</span>
                </a>

                <a href="https://threejs.org" target="_blank" class="resource-link" title="Three.js - Librairie 3D">
                    <img src="https://cdn.jsdelivr.net/npm/simple-icons@v8/icons/threedotjs.svg" alt="Three.js">
                    <span>Three.js</span>
                </a>

                <a href="https://blender.org" target="_blank" class="resource-link" title="Blender - Modélisation 3D">
                    <img src="https://cdn.jsdelivr.net/npm/simple-icons@v8/icons/blender.svg" alt="Blender">
                    <span>Blender</span>
                </a>
            </div>
        </div>

        <!-- Copyright & Legal -->
        <div class="footer-bottom">
            <div class="footer-copyright">
                <p>&copy; 2025 3D Scroll Animator V_1.0. Créé par <a href="https://gael-berru.com" target="_blank">berru-g</a>
                </p>
                <p>Fait pour les créateurs, par les créateurs. Saas en phase de test. Faites nous vos retours.</p>
            </div>

            <div class="footer-legal">
                <a href="privacy.php">Confidentialité</a>
                <a href="terms.php">Conditions</a>
                <a href="cookies.php">Cookies</a>
            </div>

            <div class="footer-social">
                <span>Suivez-nous :</span>
                <a href="#" title="Twitter" class="social-link">🟦</a>
                <a href="#" title="GitHub" class="social-link">🟪</a>
                <a href="#" title="Discord" class="social-link">⬛</a>
            </div>
        </div>
    </div>
</footer>

<style>
    @import url('https://fonts.googleapis.com/css?family=Muli&display=swap');
@import url('https://fonts.googleapis.com/css?family=Quicksand&display=swap');
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Muli', sans-serif;
    }

    /* Styles pour le footer */
    .footer {
        background: linear-gradient(135deg, #151517 70%, #ab9ff2 100%);
        color: #cdd6f4;
        border-top: 1px solid #313244;
        margin-top: 4rem;
        padding: 3rem 0 1rem;
    }

    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3rem;
    }

    .footer-stats {
        grid-column: 1 / -1;
        text-align: center;
        padding-bottom: 2rem;
        border-bottom: 1px solid #313244;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 2rem;
        margin-top: 1.5rem;
    }

    .stat-item {
        background: rgba(108, 112, 134, 0.1);
        padding: 1.5rem 1rem;
        border-radius: 12px;
        border: 1px solid #313244;
        transition: transform 0.3s ease;
    }

    .stat-item:hover {
        transform: translateY(-5px);
        border-color: #ab9ff2;
    }

    .stat-number {
        font-size: 2.5rem;
        font-weight: bold;
        background: linear-gradient(135deg, #cba6f7 0%, #f5c2e7 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .stat-label {
        font-size: 0.9rem;
        color: #a6adc8;
        margin-top: 0.5rem;
    }

    .footer-about {
        grid-column: 1;
    }

    .footer-brand h3 {
        color: #ab9ff2;
        margin-bottom: 1rem;
        font-size: 1.5rem;
    }

    .footer-description {
        line-height: 1.6;
        color: #a6adc8;
        margin-bottom: 1.5rem;
    }

    .footer-features {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .feature-tag {
        background: rgba(203, 166, 247, 0.1);
        color: #ab9ff2;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
        border: 1px solid rgba(203, 166, 247, 0.3);
    }

    .footer-links {
        grid-column: 2;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
    }

    .link-group h4 {
        color: #ab9ff2;
        margin-bottom: 1rem;
        font-size: 1.1rem;
    }

    .link-group ul {
        list-style: none;
        padding: 0;
    }

    .link-group li {
        margin-bottom: 0.5rem;
    }

    .link-group a {
        color: #a6adc8;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .link-group a:hover {
        color: #ab9ff2;
    }

    .footer-faq {
        grid-column: 1;
        margin-top: 1rem;
    }

    .footer-faq h4 {
        color: #ab9ff2;
        margin-bottom: 1rem;
    }

    .faq-items {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .faq-item {
        background: rgba(108, 112, 134, 0.1);
        border-radius: 8px;
        border: 1px solid #313244;
    }

    .faq-item summary {
        padding: 1rem;
        cursor: pointer;
        font-weight: 500;
        color: #cdd6f4;
        list-style: none;
        position: relative;
    }

    .faq-item summary::-webkit-details-marker {
        display: none;
    }

    .faq-item summary::after {
        content: '➕';
        position: absolute;
        right: 1rem;
        transition: transform 0.3s ease;
    }

    .faq-item[open] summary::after {
        content: '➖';
    }

    .faq-item p {
        padding: 0 1rem 1rem;
        margin: 0;
        color: #a6adc8;
        line-height: 1.5;
    }

    .footer-resources {
        grid-column: 2;
        margin-top: 1rem;
    }

    .footer-resources h4 {
        color: #ab9ff2;
        margin-bottom: 1rem;
    }

    .resource-links {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
    }

    .resource-link {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.8rem;
        background: rgba(108, 112, 134, 0.1);
        border-radius: 8px;
        text-decoration: none;
        color: #a6adc8;
        transition: all 0.3s ease;
        border: 1px solid transparent;
    }

    .resource-link:hover {
        background: rgba(203, 166, 247, 0.1);
        color: #ab9ff2;
        border-color: #ab9ff2;
        transform: translateY(-2px);
    }

    .resource-link img {
        width: 20px;
        height: 20px;
        filter: brightness(0.8);
    }

    .resource-link:hover img {
        filter: brightness(1);
    }

    .footer-bottom {
        grid-column: 1 / -1;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 2rem;
        border-top: 1px solid #313244;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .footer-copyright p {
        margin: 0.25rem 0;
        color: #a6adc8;
    }

    .footer-copyright a {
        color: #ab9ff2;
        text-decoration: none;
    }

    .footer-copyright a:hover {
        text-decoration: underline;
    }

    .footer-legal {
        display: flex;
        gap: 1.5rem;
    }

    .footer-legal a {
        color: #a6adc8;
        text-decoration: none;
        font-size: 0.9rem;
    }

    .footer-legal a:hover {
        color: #ab9ff2;
    }

    .footer-social {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .social-link {
        font-size: 1.2rem;
        text-decoration: none;
        transition: transform 0.3s ease;
    }

    .social-link:hover {
        transform: scale(1.2);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .footer-container {
            grid-template-columns: 1fr;
            gap: 2rem;
            padding: 0 1rem;
        }

        .footer-links,
        .footer-about,
        .footer-faq,
        .footer-resources {
            grid-column: 1;
        }

        .footer-links {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .footer-bottom {
            flex-direction: column;
            text-align: center;
        }

        .resource-links {
            grid-template-columns: repeat(3, 1fr);
        }
    }
</style>