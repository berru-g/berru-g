<?php
//require_once __DIR__ . '/smart_pixel_v2/includes/config.php';

//$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
//$total = $pdo->query("SELECT COUNT(*) FROM user_sites")->fetchColumn();

// ====== CONFIGURATION IFRAME ======
//$dashboard_url = 'http://' . $_SERVER['HTTP_HOST'] . '/smart_phpixel/smart_pixel_v2/public/dashboard.php?user_id=2&demo=true';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Pixel Analytics - Alternative Française à Google Analytics | RGPD Compliant</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Style dashboard -->
    <link rel="stylesheet" href="./assets/dashboard.css">
    <script data-sp-id="SP_79747769" src="https://gael-berru.com/smart_phpixel/smart_pixel_v2/public/tracker.js"
        async></script>
    <link rel="stylesheet" href="./RGPD/cookie.css">

    <style>
        /* ====== VARIABLES & RESET ====== */
        :root {
            --primary: #7c3aed;
            --primary-light: #8b5cf6;
            --primary-dark: #5b21b6;
            --secondary: #0f172a;
            --accent: #06d6a0;
            --accent-dark: #059669;
            --light: #f8fafc;
            --gray: #64748b;
            --gray-light: #e2e8f0;
            --dark: #0f172a;
            --success: #10b981;
            --warning: #f59e0b;
            --border: 1px solid rgba(255, 255, 255, 0.1);
            --shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.3);
            --shadow-light: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            --radius: 12px;
            --radius-lg: 20px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --container-width: 1200px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body.landing-page {
            background: var(--secondary);
            background: linear-gradient(145deg, #0f172a 0%, #1e293b 100%);
            color: var(--light);
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            overflow-x: hidden;
        }

        .landing-container {
            max-width: var(--container-width);
            margin: 0 auto;
            padding: 0 24px;
        }

        /* ====== TYPOGRAPHY ====== */
        h1,
        h2,
        h3,
        h4 {
            font-weight: 700;
            line-height: 1.2;
        }

        h1 {
            font-size: 3.5rem;
            letter-spacing: -0.02em;
        }

        h2 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
        }

        h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        p {
            margin-bottom: 1.5rem;
            color: var(--gray-light);
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title h2 {
            background: linear-gradient(90deg, #a78bfa 0%, #7dd3fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }

        .section-subtitle {
            font-size: 1.1rem;
            max-width: 700px;
            margin: 0 auto;
            color: var(--gray);
        }

        /* ====== BUTTONS ====== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px 32px;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .btn-accent {
            background: var(--accent);
            color: var(--dark);
        }

        .btn-accent:hover {
            background: var(--accent-dark);
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .btn-outline:hover {
            border-color: var(--primary);
            background: rgba(124, 58, 237, 0.1);
            transform: translateY(-3px);
        }

        .btn-small {
            padding: 10px 20px;
            font-size: 0.9rem;
        }

        /* ====== HEADER ====== */
        .landing-header {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: var(--border);
            padding: 20px 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .landing-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .landing-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            font-size: 1.5rem;
            color: white;
            text-decoration: none;
        }

        .landing-logo-icon {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: var(--gray-light);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .nav-links a:hover {
            color: white;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* ====== HERO SECTION ====== */
        .landing-hero {
            padding: 160px 0 100px;
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(6, 214, 160, 0.1);
            color: var(--accent);
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 2rem;
        }

        .hero-badge i {
            font-size: 0.8rem;
        }

        .hero-title {
            font-size: 3.8rem;
            margin-bottom: 1.5rem;
            line-height: 1.1;
        }

        .gradient-text {
            background: linear-gradient(90deg, #a78bfa 0%, #7dd3fc 50%, #06d6a0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-size: 200% auto;
            animation: gradient 3s ease infinite;
        }

        @keyframes gradient {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .hero-subtitle {
            font-size: 1.3rem;
            max-width: 700px;
            margin: 0 auto 3rem;
            color: var(--gray-light);
        }

        .hero-cta {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 4rem;
            flex-wrap: wrap;
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-top: 4rem;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ====== VALUE PROPOSITIONS ====== */
        .value-section {
            padding: 100px 0;
            background: rgba(15, 23, 42, 0.7);
        }

        .value-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .value-card {
            background: rgba(30, 41, 59, 0.5);
            border: var(--border);
            border-radius: var(--radius);
            padding: 2.5rem 2rem;
            transition: var(--transition);
        }

        .value-card:hover {
            transform: translateY(-10px);
            border-color: var(--primary);
            box-shadow: var(--shadow);
        }

        .value-icon {
            width: 70px;
            height: 70px;
            background: rgba(124, 58, 237, 0.1);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            color: var(--primary);
        }

        /* ====== COMPARISON SECTION ====== */
        .comparison-section {
            padding: 100px 0;
        }

        .comparison-table {
            background: rgba(30, 41, 59, 0.5);
            border-radius: var(--radius);
            border: var(--border);
            overflow: hidden;
            margin-top: 3rem;
        }

        .comparison-header {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            background: rgba(15, 23, 42, 0.9);
            padding: 1.5rem 2rem;
            border-bottom: var(--border);
        }

        .comparison-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            padding: 1.5rem 2rem;
            border-bottom: var(--border);
            align-items: center;
        }

        .comparison-row:last-child {
            border-bottom: none;
        }

        .comparison-row:nth-child(even) {
            background: rgba(15, 23, 42, 0.2);
        }

        .check {
            color: var(--accent);
            font-weight: bold;
        }

        .cross {
            color: #ef4444;
            font-weight: bold;
        }

        /* ====== DEMO SECTION ====== */
        .demo-section {
            padding: 100px 0;
            background: rgba(15, 23, 42, 0.7);
        }

        .demo-container {
            background: rgba(30, 41, 59, 0.5);
            border-radius: var(--radius-lg);
            border: var(--border);
            padding: 3rem;
            margin-top: 3rem;
        }

        .dashboard-iframe-container {
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            background: white;
            position: relative;
            height: 600px;
        }

        #dashboardLivePreview {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
        }

        /* ====== INTEGRATION SECTION ====== */
        .integration-section {
            padding: 100px 0;
        }

        .integration-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin: 3rem 0;
        }

        .step {
            text-align: center;
            padding: 2rem;
            background: rgba(30, 41, 59, 0.5);
            border-radius: var(--radius);
            border: var(--border);
        }

        .step-number {
            width: 50px;
            height: 50px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin: 0 auto 1.5rem;
        }

        .code-snippet {
            background: #0f172a;
            border-radius: 10px;
            padding: 1.5rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
            line-height: 1.8;
            overflow-x: auto;
            margin: 2rem 0;
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }

        .code-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .copy-btn {
            background: rgba(124, 58, 237, 0.2);
            color: var(--primary);
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .copy-btn:hover {
            background: rgba(124, 58, 237, 0.3);
        }

        /* ====== PRICING SECTION ====== */
        .pricing-section {
            padding: 100px 0;
            background: rgba(15, 23, 42, 0.7);
        }

        .pricing-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .pricing-card {
            background: rgba(30, 41, 59, 0.6);
            border: var(--border);
            border-radius: var(--radius-lg);
            padding: 3rem 2rem;
            transition: var(--transition);
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .pricing-card:hover {
            border-color: var(--primary);
            transform: translateY(-10px);
            box-shadow: var(--shadow);
        }

        .pricing-card.featured {
            border-color: var(--accent);
            background: rgba(30, 41, 59, 0.8);
            transform: scale(1.05);
        }

        .pricing-card.featured:hover {
            transform: scale(1.05) translateY(-10px);
        }

        .featured-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--accent);
            color: var(--dark);
            padding: 6px 20px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .price-tag {
            font-size: 3.5rem;
            font-weight: 800;
            margin: 1.5rem 0;
            color: white;
        }

        .price-tag span {
            font-size: 1rem;
            color: var(--gray);
            font-weight: 500;
        }

        .pricing-features {
            list-style: none;
            padding: 0;
            margin: 2rem 0;
            flex-grow: 1;
        }

        .pricing-features li {
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pricing-features li:last-child {
            border-bottom: none;
        }

        .feature-check {
            color: var(--accent);
            font-size: 1rem;
        }

        /* ====== TESTIMONIALS ====== */
        .testimonials-section {
            padding: 100px 0;
        }

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .testimonial-card {
            background: rgba(30, 41, 59, 0.5);
            border-radius: var(--radius);
            border: var(--border);
            padding: 2rem;
        }

        .testimonial-text {
            font-style: italic;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .testimonial-text:before {
            content: '"';
            font-size: 4rem;
            color: var(--primary);
            opacity: 0.2;
            position: absolute;
            top: -20px;
            left: -10px;
            font-family: serif;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        /* ====== CTA SECTION ====== */
        .cta-section {
            padding: 120px 0;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-card {
            background: rgba(30, 41, 59, 0.8);
            border-radius: var(--radius-lg);
            border: var(--border);
            padding: 4rem 2rem;
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
            backdrop-filter: blur(10px);
        }

        .cta-title {
            font-size: 2.8rem;
            margin-bottom: 1.5rem;
        }

        .cta-subtitle {
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto 3rem;
        }

        /* ====== FOOTER ====== */
        .landing-footer {
            border-top: var(--border);
            padding: 4rem 0 2rem;
            background: rgba(15, 23, 42, 0.9);
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 1.5rem;
            color: white;
            text-decoration: none;
            margin-bottom: 1.5rem;
        }

        .footer-description {
            color: var(--gray);
            margin-bottom: 1.5rem;
        }

        .footer-links h4 {
            margin-bottom: 1.5rem;
            color: white;
        }

        .footer-links ul {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 0.75rem;
        }

        .footer-links a {
            color: var(--gray);
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        .footer-bottom {
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-links a {
            color: var(--gray);
            font-size: 1.2rem;
            transition: var(--transition);
        }

        .social-links a:hover {
            color: var(--primary);
        }

        /* ====== RESPONSIVE ====== */
        @media (max-width: 1024px) {
            h1 {
                font-size: 3rem;
            }

            h2 {
                font-size: 2.2rem;
            }

            .pricing-card.featured {
                transform: none;
            }

            .pricing-card.featured:hover {
                transform: translateY(-10px);
            }
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 2.5rem;
            }

            h2 {
                font-size: 2rem;
            }

            .hero-title {
                font-size: 2.8rem;
            }

            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background: rgba(15, 23, 42, 0.98);
                flex-direction: column;
                padding: 2rem;
                border-top: var(--border);
            }

            .nav-links.active {
                display: flex;
            }

            .mobile-menu-btn {
                display: block;
            }

            .hero-cta {
                flex-direction: column;
                align-items: center;
            }

            .dashboard-iframe-container {
                height: 400px;
            }

            .comparison-header,
            .comparison-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .landing-container {
                padding: 0 16px;
            }

            h1 {
                font-size: 2.2rem;
            }

            h2 {
                font-size: 1.8rem;
            }

            .hero-title {
                font-size: 2.2rem;
            }

            .btn {
                padding: 14px 24px;
                width: 100%;
            }

            .dashboard-iframe-container {
                height: 300px;
            }

            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>

<body class="landing-page">
    <!-- ====== HEADER ====== -->
    <header class="landing-header">
        <div class="landing-container">
            <nav class="landing-nav">
                <a href="#" class="landing-logo">
                    <div class="landing-logo-icon">
                        <i class="fas fa-chart-network"></i>
                    </div>
                    Smart Pixel
                </a>

                <div class="nav-links" id="navLinks">
                    <a href="#features">Fonctionnalités</a>
                    <a href="#demo">Démo</a>
                    <a href="#comparison">Comparaison</a>
                    <a href="#pricing">Tarifs</a>
                    <a href="./smart_pixel_v2/public/login.php" class="btn btn-outline btn-small">Connexion</a>
                </div>

                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
            </nav>
        </div>
    </header>

    <!-- ====== HERO SECTION ====== -->
    <section class="landing-hero">
        <div class="landing-container">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="fas fa-shield-check"></i>
                    Alternative 100% française à Google Analytics
                </div>

                <h1 class="hero-title">
                    Reprenez le contrôle de vos
                    <span class="gradient-text">données analytics</span>
                </h1>

                <p class="hero-subtitle">
                    Smart Pixel est la solution analytics souveraine, open-source et RGPD-compliant.
                    Analysez votre trafic sans compromettre la vie privée de vos visiteurs,
                    avec un hébergement 100% français.
                </p>

                <div class="hero-cta">
                    <a href="#pricing" class="btn btn-primary">
                        <i class="fas fa-rocket"></i>
                        Premier pixel gratuit
                    </a>
                    <a href="#demo" class="btn btn-outline">
                        <i class="fas fa-play-circle"></i>
                        Voir la démo
                    </a>
                </div>

                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-number">100%</div>
                        <div class="stat-label">RGPD Compliant</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">0</div>
                        <div class="stat-label">Cookies tiers</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">2.5x</div>
                        <div class="stat-label">Plus rapide que la V1</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $total; ?></div><!--<span class="counter"><?php echo $total; ?> sites créés</span>-->
                        <div class="stat-label">Dashboard crée</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== VALUE PROPOSITIONS ====== -->
    <section id="features" class="value-section">
        <div class="landing-container">
            <div class="section-title">
                <h2>Pourquoi choisir Smart Pixel ?</h2>
                <p class="section-subtitle">
                    Nous avons conçu notre propre API pour ne transmettre aucune data à des tiers. Les données recolté par vos pixels sont uniquement visible via votre dashboard.
                </p>
            </div>

            <div class="value-grid">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3>Respect de la vie privée</h3>
                    <p>Collecte anonymisée, pas de cookies invasifs, conformité RGPD par défaut. Vos données restent vos
                        données.</p>
                </div>

                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <h3>Performance optimale</h3>
                    <p>Script léger, chargement asynchrone, 0 impact sur votre Core Web Vitals. Votre site reste
                        rapide.</p>
                </div>

                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-server"></i>
                    </div>
                    <h3>Souveraineté des données</h3>
                    <p>Hébergement 100% français, 0 tiers, 0 GAFAM. Vos données sont vos données.
                    </p>
                </div>

                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-code"></i>
                    </div>
                    <h3>Open Source</h3>
                    <p>Code transparent, auditable. Pas de boîte noire, vous savez exactement ce qui se
                        passe. Documentation pour les dev à /doc/</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== COMPARISON SECTION ====== -->
    <section id="comparison" class="comparison-section">
        <div class="landing-container">
            <div class="section-title">
                <h2>Smart Pixel vs Google Analytics</h2>
                <p class="section-subtitle">
                    Comparez et découvrez pourquoi nous sommes l'alternative préférée des développeurs
                </p>
            </div>

            <div class="comparison-table">
                <div class="comparison-header">
                    <div><strong>Fonctionnalité</strong></div>
                    <div><strong>Smart Pixel</strong></div>
                    <div><strong>Google Analytics</strong></div>
                </div>

                <div class="comparison-row">
                    <div>Conforme RGPD par défaut</div>
                    <div class="check">✓ Inclus</div>
                    <div class="cross">✗ Configuration complexe</div>
                </div>

                <div class="comparison-row">
                    <div>Impact performance</div>
                    <div class="check">≤ 5KB, Async</div>
                    <div class="cross">≥ 45KB, Bloquer le rendu</div>
                </div>

                <div class="comparison-row">
                    <div>Hébergement des données</div>
                    <div class="check">France (souverain)</div>
                    <div class="cross">États-Unis (Cloud Act)</div>
                </div>

                <div class="comparison-row">
                    <div>Modèle économique</div>
                    <div class="check">Abonnement transparent</div>
                    <div class="cross">Vos données sont le produit</div>
                </div>

                <div class="comparison-row">
                    <div>Dashboard personnalisable</div>
                    <div class="check">Illimité</div>
                    <div class="cross">Limité</div>
                </div>

                <div class="comparison-row">
                    <div>Export des données</div>
                    <div class="check">CSV, PDF, API complète</div>
                    <div class="cross">Limité (sans GA360)</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== DEMO SECTION ====== 
    <section id="demo" class="demo-section">
        <div class="landing-container">
            <div class="section-title">
                <h2>Essayez notre dashboard en direct</h2>
                <p class="section-subtitle">
                    Interface intuitive, données en temps réel, prise en main immédiate
                </p>
            </div>

            <div class="demo-container">
                <div class="dashboard-iframe-container">
                   <iframe id="dashboardLivePreview" src="<?php echo htmlspecialchars($dashboard_url); ?>"
                        title="Tableau de bord Smart Pixel en direct" loading="lazy">
                    </iframe>
                </div>

                <div style="text-align: center; margin-top: 2rem;">
                    <a href="<?php echo htmlspecialchars($dashboard_url); ?>" target="_blank" class="btn btn-outline">
                        <i class="fas fa-external-link-alt"></i>
                        Ouvrir en plein écran
                    </a>
                </div>
            </div>
        </div>
    </section>-->

    <!-- ====== INTEGRATION SECTION ====== -->
    <section class="integration-section">
        <div class="landing-container">
            <div class="section-title">
                <h2>Intégration en 2 minutes</h2>
                <p class="section-subtitle">
                    Remplacez Google Analytics par une seule ligne de code
                </p>
            </div>

            <div class="integration-steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Créez votre compte</h3>
                    <p>Inscription gratuite en 30 secondes, aucun paiement requis</p>
                </div>

                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Ajoutez votre site</h3>
                    <p>Donnez un nom à votre site et récupérez votre ID de tracking</p>
                </div>

                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Installez le script</h3>
                    <p>Copiez-collez une ligne de code dans votre site</p>
                </div>
            </div>

            <div class="code-snippet">
                <div class="code-header">
                    <span>Code d'intégration Smart Pixel</span>
                    <button class="copy-btn" onclick="copyCode()">
                        <i class="fas fa-copy"></i> Copier
                    </button>
                </div>
                <pre><code>&lt;!-- Smart Pixel Analytics --&gt;
    &lt;script data-sp-id="TON_ID_ICI" src="https://gael-berru.com/smart_phpixel/smart_pixel_v2/public/tracker.js" async&gt;&lt;/script&gt;</code></pre>
            </div>

            <div style="text-align: center; margin-top: 3rem;">
                <a href="./smart_pixel_v2/public/login.php" class="btn btn-primary"
                    style="padding: 18px 40px; font-size: 1.1rem;">
                    <i class="fas fa-bolt"></i>
                    Commencez gratuitement
                </a>
            </div>
        </div>
    </section>

    <!-- ====== PRICING SECTION ====== -->
    <section id="pricing" class="pricing-section">
        <div class="landing-container">
            <div class="section-title">
                <h2>Tarifs transparents</h2>
                <p class="section-subtitle">
                    Payez pour l'hébergement et le support, pas pour vos données
                </p>
            </div>

            <div class="pricing-cards">
                <!-- Plan Free -->
                <div class="pricing-card">
                    <h3>Free</h3>
                    <div class="price-tag">0€<span>/mois</span></div>
                    <p>Pour les petits sites et découverte</p>

                    <ul class="pricing-features">
                        <li><i class="fas fa-check feature-check"></i> 1 site web</li>
                        <li><i class="fas fa-check feature-check"></i> 10 000 vues/mois</li>
                        <li><i class="fas fa-check feature-check"></i> Dashboard basique</li>
                        <li><i class="fas fa-check feature-check"></i> 7 jours de rétention</li>
                        <li><i class="fas fa-check feature-check"></i> Support communautaire</li>
                    </ul>

                    <a href="./smart_pixel_v2/public/index.php" class="btn btn-outline" style="margin-top: auto;">
                        Commencer gratuitement
                    </a>
                </div>

                <!-- Plan Pro -->
                <div class="pricing-card featured">
                    <div class="featured-badge">A venir</div>
                    <h3>Pro</h3>
                    <div class="price-tag">9€<span>/mois</span></div>
                    <p>Pour les sites professionnels</p>

                    <ul class="pricing-features">
                        <li><i class="fas fa-check feature-check"></i> <strong>10 sites web</strong></li>
                        <li><i class="fas fa-check feature-check"></i> 100 000 vues/mois</li>
                        <li><i class="fas fa-check feature-check"></i> Dashboard complet</li>
                        <li><i class="fas fa-check feature-check"></i> 30 jours de rétention</li>
                        <li><i class="fas fa-check feature-check"></i> Export CSV/PDF</li>
                        <li><i class="fas fa-check feature-check"></i> API d'accès</li>
                        <li><i class="fas fa-check feature-check"></i> Support prioritaire</li>
                    </ul>

                    <a href="./smart_pixel_v2/public/index.php?plan=pro" class="btn btn-accent"
                        style="margin-top: auto;">
                        <i class="fas fa-gem"></i>
                        Essai 14 jours
                    </a>
                </div>

                <!-- Plan Business -->
                <div class="pricing-card">
                    <h3>Business</h3>
                    <div class="price-tag">29€<span>/mois</span></div>
                    <p>Pour les entreprises et agences</p>

                    <ul class="pricing-features">
                        <li><i class="fas fa-check feature-check"></i> <strong>50 sites web</strong></li>
                        <li><i class="fas fa-check feature-check"></i> Vues illimitées</li>
                        <li><i class="fas fa-check feature-check"></i> Toutes features Pro</li>
                        <li><i class="fas fa-check feature-check"></i> 90 jours de rétention</li>
                        <li><i class="fas fa-check feature-check"></i> Accès multi-utilisateurs</li>
                        <li><i class="fas fa-check feature-check"></i> Support téléphone</li>
                        <li><i class="fas fa-check feature-check"></i> Intégrations custom</li>
                    </ul>

                    <a href="#" class="btn btn-outline" style="margin-top: auto;">
                        <i class="fas fa-phone-alt"></i>
                        Nous contacter
                    </a>
                </div>
            </div>

            <div style="text-align: center; margin-top: 3rem;">
                <p style="color: var(--gray);">
                    <i class="fas fa-sync-alt"></i> Tous les plans incluent l'essai gratuit 14 jours ·
                    <i class="fas fa-ban"></i> Pas de carte bancaire requise pour commencer
                </p>
            </div>
        </div>
    </section>

    <!-- ====== TESTIMONIALS ====== -->
    <section class="testimonials-section">
        <div class="landing-container">
            <div class="section-title">
                <h2>Ils nous font confiance</h2>
                <p class="section-subtitle">
                    Découvrez ce que pensent les développeurs et entreprises qui utilisent Smart Pixel
                </p>
            </div>

            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <div class="testimonial-text">
                        "Enfin une alternative sérieuse à Google Analytics qui respecte vraiment le RGPD.
                        L'installation a pris 2 minutes et le dashboard est ultra intuitif."
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">MB</div>
                        <div>
                            <h4>Marc B.</h4>
                            <p>CTO, Startup Tech</p>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <div class="testimonial-text">
                        "J'ai migré tous les sites de mes clients vers Smart Pixel.
                        Gain de performance immédiat et plus de problèmes de conformité RGPD."
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">SD</div>
                        <div>
                            <h4>Sarah D.</h4>
                            <p>Freelance Web Dev</p>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <div class="testimonial-text">
                        "Le support technique est réactif et l'API est bien documentée.
                        Parfait pour notre équipe de développement."
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">PL</div>
                        <div>
                            <h4>Pierre L.</h4>
                            <p>Lead Developer, Agence</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== FINAL CTA ====== -->
    <section class="cta-section">
        <div class="landing-container">
            <div class="cta-card">
                <h2 class="cta-title">Prêt à reprendre le contrôle de vos données ?</h2>
                <p class="cta-subtitle">
                    Rejoignez plus de 500 développeurs et entreprises qui ont choisi
                    l'analytics souverain. Aucune carte bancaire requise pour votre premier dashboard !
                </p>

                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="./smart_pixel_v2/public/index.php" class="btn btn-primary"
                        style="padding: 20px 40px; font-size: 1.1rem;">
                        <i class="fas fa-user-plus"></i>
                        Créer mon compte gratuit
                    </a>
                    <!-- <a href="#demo" class="btn btn-outline" style="padding: 20px 40px; font-size: 1.1rem;">
                        <i class="fas fa-play-circle"></i>
                        Voir la démo complète
                    </a>-->
                </div>

                <p style="margin-top: 2rem; font-size: 0.9rem; color: var(--gray);">
                    <i class="fas fa-clock"></i> Configuration en 2 minutes ·
                    <i class="fas fa-shield-alt"></i> RGPD garanti ·
                    <i class="fas fa-comments"></i> Support en français
                </p>
            </div>
        </div>
    </section>

    <!-- ====== FOOTER ====== -->
    <footer class="landing-footer">
        <div class="landing-container">
            <div class="footer-grid">
                <div>
                    <a href="#" class="footer-logo">
                        <div class="landing-logo-icon">
                            <i class="fas fa-chart-network"></i>
                        </div>
                        Smart Pixel
                    </a>
                    <p class="footer-description">
                        Alternative open-source et souveraine à Google Analytics.
                        Code propre, données protégées, analytics éthique.
                    </p>

                    <div class="social-links">
                        <a href="https://github.com/berru-g/smart_pixel_v2"><i class="fab fa-github"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-product-hunt"></i></a>
                    </div>
                </div>

                <div class="footer-links">
                    <h4>Produit</h4>
                    <ul>
                        <li><a href="#features">Fonctionnalités</a></li>
                        <li><a href="#demo">Démo</a></li>
                        <li><a href="#pricing">Tarifs</a></li>
                        <li><a href="./doc/auto-heberge/index.html">Documentation</a></li>
                        <li><a href="https://github.com/berru-g/smart_pixel_v2/blob/main/public/pixel.php">API</a></li>
                    </ul>
                </div>

                <div class="footer-links">
                    <h4>Entreprise</h4>
                    <ul>
                        <li><a href="#">À propos</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Carrières</a></li>
                        <li><a href="https://gael-berru.com">Contact</a></li>
                        <li><a href="#">Presse</a></li>
                    </ul>
                </div>

                <div class="footer-links">
                    <h4>Légal</h4>
                    <ul>
                        <li><a href="#">Mentions légales</a></li>
                        <li><a href="#">Confidentialité</a></li>
                        <li><a href="#">RGPD</a></li>
                        <li><a href="#">Cookies</a></li>
                        <li><a href="#">CGU</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p style="color: var(--gray);">
                    © 2025 Smart Pixel Analytics. Développé avec <i class="fas fa-heart" style="color: #ef4444;"></i>
                    par <a href="https://gael-berru.com" target="_blank">Berru-g</a>.
                </p>
                <p style="color: var(--gray);">
                    <i class="fas fa-map-marker-alt"></i> Hébergé en France ·
                    <i class="fas fa-leaf"></i> Serveurs eco-responsables
                </p>
            </div>
        </div>
    </footer>

    <div id="cookie-banner" style="display: none;">
        <div class="cookie-container">
            <div class="cookie-header">
                <div class="cookie-icon">🛡️</div>
                <div class="cookie-title-wrapper">
                    <h3 class="cookie-title">Transparence totale sur vos données</h3>
                    <p class="cookie-subtitle">Respect RGPD • Open source</p>
                </div>
            </div>

            <div class="cookie-content">
                <p class="cookie-description">
                    <strong>Ici, aucun de vos clics n'est vendu à Google ou Facebook.</strong><br>
                    J'utilise <strong>Smart Pixel</strong>, mon propre système d'analyse développé avec éthique, dans le respect
                    des lois RGPD.
                </p>
                <p class="cookie-description">
                    En autorisant l'analyse, vous m'aidez à améliorer ce site <strong>sans enrichir les GAFAM de vos
                        données</strong>.
                </p>
            </div>

            <div class="cookie-buttons">
                <button class="cookie-btn accept-necessary" onclick="acceptCookies('necessary')">
                    Non merci
                </button>
                <button class="cookie-btn accept-all" onclick="acceptCookies('all')">
                    Ok pour moi
                </button>
            </div>

            <div class="cookie-footer">
                <a href="https://github.com/berru-g/smart_phpixel" target="_blank" class="cookie-link">
                    Voir le code source de Smart Pixel
                </a>
            </div>
        </div>
    </div>
    <script src="./RGPD/cookie.js"></script>

    <script>
        // ====== MOBILE MENU ======
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navLinks = document.getElementById('navLinks');

        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            mobileMenuBtn.innerHTML = navLinks.classList.contains('active') ?
                '<i class="fas fa-times"></i>' :
                '<i class="fas fa-bars"></i>';
        });

        // ====== COPY CODE FUNCTION ======
        function copyCode() {
            const code = `<script data-sp-id="TON_ID_ICI" src="https://gael-berru.com/smart_phpixel/smart_pixel_v2/public/tracker.js" async><\/script>
    `;

            navigator.clipboard.writeText(code).then(() => {
                const btn = document.querySelector('.copy-btn');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copié !';
                btn.style.background = 'rgba(6, 214, 160, 0.2)';
                btn.style.color = 'var(--accent)';

                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.style.background = '';
                    btn.style.color = '';
                }, 2000);
            });
        }

        // ====== IFRAME FALLBACK ======
        const dashboardIframe = document.getElementById('dashboardLivePreview');

        dashboardIframe.addEventListener('error', function() {
            console.log('Fallback pour dashboard');
            this.srcdoc = `
    <!DOCTYPE html>
    <html>

    <head>
        <link rel="stylesheet"
            href="https://raw.githubusercontent.com/berru-g/smart_pixel_v2/refs/heads/main/assets/dashboard.css">
        <style>
            body {
                font-family: 'Inter', sans-serif;
                padding: 40px;
                background: #f8fafc;
                color: #0f172a;
            }

            .demo-container {
                max-width: 800px;
                margin: 0 auto;
            }

            .demo-header {
                text-align: center;
                margin-bottom: 40px;
                padding: 30px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            }

            .stats-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
                margin-bottom: 30px;
            }

            .stat-card {
                background: white;
                padding: 25px;
                border-radius: 12px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
                text-align: center;
                border-top: 4px solid #7c3aed;
            }

            .stat-card h3 {
                font-size: 2.5rem;
                color: #7c3aed;
                margin-bottom: 10px;
            }

            .chart-placeholder {
                background: white;
                padding: 30px;
                border-radius: 12px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
                margin-top: 30px;
                text-align: center;
                color: #64748b;
            }
        </style>
    </head>

    <body>
        <div class="demo-container">
            <div class="demo-header">
                <h1 style="color: #7c3aed;">Dashboard Smart Pixel (Démo)</h1>
                <p>Données simulées pour montrer l'interface réelle</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>1,847</h3>
                    <p>Visiteurs uniques (7j)</p>
                </div>
                <div class="stat-card">
                    <h3>6,429</h3>
                    <p>Pages vues</p>
                </div>
                <div class="stat-card">
                    <h3>2m 51s</h3>
                    <p>Durée moyenne</p>
                </div>
                <div class="stat-card">
                    <h3>34.7%</h3>
                    <p>Taux de rebond</p>
                </div>
            </div>

            <div class="chart-placeholder">
                <h3 style="margin-bottom: 20px; color: #0f172a;">Graphique des visites (7 derniers jours)</h3>
                <div
                    style="height: 200px; background: linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%); border-radius: 8px; display: flex; align-items: flex-end; padding: 20px; gap: 10px;">
                    <div style="flex: 1; background: #7c3aed; height: 80%; border-radius: 4px;"></div>
                    <div style="flex: 1; background: #8b5cf6; height: 65%; border-radius: 4px;"></div>
                    <div style="flex: 1; background: #a78bfa; height: 90%; border-radius: 4px;"></div>
                    <div style="flex: 1; background: #7c3aed; height: 75%; border-radius: 4px;"></div>
                    <div style="flex: 1; background: #8b5cf6; height: 85%; border-radius: 4px;"></div>
                    <div style="flex: 1; background: #a78bfa; height: 60%; border-radius: 4px;"></div>
                    <div style="flex: 1; background: #7c3aed; height: 95%; border-radius: 4px;"></div>
                </div>
            </div>

            <p style="text-align: center; color: #64748b; font-style: italic; margin-top: 30px;">
                Dashboard réel avec vos données en production
            </p>
        </div>
    </body>

    </html>
    `;
        });

        // ====== SMOOTH SCROLL ======
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;

                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    e.preventDefault();

                    // Close mobile menu if open
                    if (navLinks.classList.contains('active')) {
                        navLinks.classList.remove('active');
                        mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
                    }

                    window.scrollTo({
                        top: targetElement.offsetTop - 100,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // ====== ANIMATE STATS ON SCROLL ======
        const observerOptions = {
            threshold: 0.5
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const statNumbers = entry.target.querySelectorAll('.stat-number');
                    statNumbers.forEach(stat => {
                        const finalValue = stat.textContent;
                        const duration = 2000;
                        const steps = 60;
                        const increment = parseInt(finalValue) / steps;
                        let current = 0;

                        const timer = setInterval(() => {
                            current += increment;
                            if (current >= parseInt(finalValue)) {
                                stat.textContent = finalValue;
                                clearInterval(timer);
                            } else {
                                stat.textContent = Math.floor(current);
                            }
                        }, duration / steps);
                    });
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        const statsSection = document.querySelector('.hero-stats');
        if (statsSection) observer.observe(statsSection);
    </script>
</body>

</html>