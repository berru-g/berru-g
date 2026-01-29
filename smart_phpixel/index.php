<?php
// ====== CONFIGURATION & INCLUDES ======
$dashboard_url = 'http://' . $_SERVER['HTTP_HOST'] . '/smart_phpixel/smart_pixel_v2/public/dashboard.php?user_id=2&demo=true';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Pixel Analytics — L'alternative souveraine à Google Analytics</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Ton style dashboard -->
    <link rel="stylesheet" href="./assets/dashboard.css">
    <script data-sp-id="SP_671f3190" src="https://gael-berru.com/smart_phpixel/smart_pixel_v2/public/tracker.js" async></script>
    
    <!-- Style landing page -->
    <style>
        /* ====== OVERRIDE & EXTENSIONS ====== */
        :root {
            --primary: #7c3aed;
            --primary-dark: #5b21b6;
            --secondary: #0f172a;
            --accent: #06d6a0;
            --light: #f8fafc;
            --gray: #64748b;
            --gray-light: #e2e8f0;
            --dark: #0f172a;
            --border: 1px solid rgba(255, 255, 255, 0.08);
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.25);
            --radius: 8px;
            --transition: all 0.3s ease;
        }

        body.landing-page {
            background: var(--secondary);
            color: var(--light);
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .landing-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* ====== HEADER LANDING ====== */
        .landing-header {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: var(--border);
            padding: 1rem 0;
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
            gap: 10px;
            font-weight: 700;
            font-size: 1.5rem;
            color: white;
            text-decoration: none;
        }

        .landing-logo-icon {
            background: var(--primary);
            width: 36px;
            height: 36px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .landing-cta-button {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
        }

        .landing-cta-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* ====== HERO SECTION ====== */
        .landing-hero {
            padding: 140px 0 80px;
            text-align: center;
        }

        .landing-hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            background: linear-gradient(90deg, #a78bfa 0%, #7dd3fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .landing-hero-subtitle {
            font-size: 1.2rem;
            color: var(--gray-light);
            max-width: 700px;
            margin: 0 auto 3rem;
            line-height: 1.6;
        }

        /* ====== DEMO SECTION ====== */
        .landing-demo {
            background: rgba(30, 41, 59, 0.5);
            border-radius: var(--radius);
            border: var(--border);
            padding: 2rem;
            margin: 3rem 0;
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

        /* ====== CODE DEMO SECTION ====== */
        .landing-code-demo {
            background: rgba(15, 23, 42, 0.7);
            border-radius: var(--radius);
            border: var(--border);
            padding: 2rem;
            margin: 3rem 0;
        }

        .code-snippet {
            background: var(--dark);
            border-radius: 6px;
            padding: 1.5rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
            line-height: 1.8;
            overflow-x: auto;
            margin: 1.5rem 0;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* ====== PRICING SECTION ====== */
        .landing-pricing {
            margin: 4rem 0;
        }

        .pricing-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .pricing-card {
            background: rgba(30, 41, 59, 0.6);
            border: var(--border);
            border-radius: var(--radius);
            padding: 2rem;
            transition: var(--transition);
        }

        .pricing-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
        }

        .pricing-card.featured {
            border-color: var(--accent);
            background: rgba(30, 41, 59, 0.8);
        }

        .price-tag {
            font-size: 3rem;
            font-weight: 700;
            margin: 1rem 0;
        }

        .price-tag span {
            font-size: 1rem;
            color: var(--gray);
        }

        /* ====== FOOTER ====== */
        .landing-footer {
            border-top: var(--border);
            padding: 3rem 0;
            margin-top: 4rem;
            text-align: center;
            color: var(--gray);
        }

        /* ====== RESPONSIVE ====== */
        @media (max-width: 768px) {
            .landing-hero h1 {
                font-size: 2.5rem;
            }

            .landing-hero-subtitle {
                font-size: 1rem;
            }

            .dashboard-iframe-container {
                height: 400px;
            }

            .pricing-cards {
                grid-template-columns: 1fr;
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
                    <div class="landing-logo-icon">◰</div>
                    Smart Pixel
                </a>
                <a href="#pricing" class="landing-cta-button">Voir les offres</a>
            </nav>
        </div>
    </header>

    <!-- ====== HERO ====== -->
    <section class="landing-hero">
        <div class="landing-container">
            <h1>Analytics souverains<br>pour développeurs conscients</h1>
            <p class="landing-hero-subtitle">
                Alternative éthique à Google Analytics. Code open-source, hébergement français,
                données chiffrées. Pour ceux qui refusent la confiscation de leurs données.
            </p>
            <a href="#demo" class="landing-cta-button" style="padding: 1rem 2rem; font-size: 1.1rem;">
                Voir le dashboard en direct →
            </a>
        </div>
    </section>

    <!-- ====== DEMO LIVE ====== -->
    <section id="demo" class="landing-container">
        <h2 style="text-align: center; font-size: 2.5rem; margin-bottom: 1rem;">Votre futur tableau de bord</h2>
        <p style="text-align: center; color: var(--gray-light); margin-bottom: 2rem;">
            Interface réelle, données de démonstration pour l'utilisateur #2
        </p>

        <div class="landing-demo">
            <div class="dashboard-iframe-container">
                <!-- IFRAME AVEC LE DASHBOARD RÉEL -->
                <iframe
                    id="dashboardLivePreview"
                    src="<?php echo htmlspecialchars($dashboard_url); ?>"
                    title="Tableau de bord Smart Pixel en direct"
                    loading="lazy">
                </iframe>
            </div>
            <p style="text-align: center; color: var(--gray); margin-top: 1rem; font-size: 0.9rem;">
                <i class="fas fa-info-circle"></i> Dashboard fonctionnel avec données simulées
            </p>
        </div>
    </section>

    <!-- ====== INTÉGRATION ====== -->
    <section class="landing-container">
        <div class="landing-code-demo">
            <h2 style="margin-bottom: 1.5rem;">Intégration en 30 secondes</h2>
            <p style="color: var(--gray-light); margin-bottom: 1.5rem;">
                Remplacez Google Analytics par une seule ligne de code
            </p>

            <div class="code-snippet">
                &lt;!-- Smart Pixel Analytics --&gt;
                &lt;script src="https://cdn.smart-pixel.fr/v2/pixel.js"
                data-sp-id="VOTRE_ID"
                data-domain="votresite.com"&gt;
                &lt;/script&gt;

                &lt;!-- Configuration optionnelle --&gt;
                &lt;script&gt;
                window.SP_CONFIG = {
                respectDNT: true,
                server: 'fr-par-1', // Hébergement France
                autoTrack: true
                };
                &lt;/script&gt;
            </div>

            <div style="display: flex; align-items: center; gap: 2rem; margin-top: 2rem; padding: 1.5rem; background: rgba(124, 58, 237, 0.1); border-radius: var(--radius);">
                <div style="flex: 1;">
                    <h3 style="margin-bottom: 0.5rem;">🎬 Démo d'installation</h3>
                    <p style="color: var(--gray-light); font-size: 0.9rem;">
                        GIF montrant l'installation en 3 clics (à produire)
                    </p>
                </div>
                <div style="width: 200px; height: 150px; background: linear-gradient(45deg, #1e293b, #334155); border-radius: var(--radius); display: flex; align-items: center; justify-content: center; color: var(--gray); border: 2px dashed var(--gray);">
                    <div style="text-align: center;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">📹</div>
                        <div>GIF démo</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== PRICING ====== -->
    <section id="pricing" class="landing-pricing landing-container">
        <h2 style="text-align: center; font-size: 2.5rem; margin-bottom: 1rem;">Plans transparents</h2>
        <p style="text-align: center; color: var(--gray-light); margin-bottom: 3rem;">
            Payez pour l'hébergement et le support, pas pour vos données
        </p>

        <div class="pricing-cards">
            <div class="pricing-card">
                <h3>Free</h3>
                <div class="price-tag">0€<span>/mois</span></div>
                <ul style="list-style: none; padding: 0; margin: 1.5rem 0;">
                    <li style="padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.05);">✓ 1 site</li>
                    <li style="padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.05);">✓ 10k vues/mois</li>
                    <li style="padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.05);">✓ Dashboard basique</li>
                    <li style="padding: 0.5rem 0;">✓ Support communautaire</li>
                </ul>
                <a href="./smart_pixel_v2/public/login.php" class="landing-cta-button" style="width: 100%; text-align: center;">Commencer</a>
            </div>

            <div class="pricing-card featured">
                <div style="background: var(--accent); color: var(--dark); padding: 0.25rem 1rem; border-radius: 20px; display: inline-block; margin-bottom: 1rem; font-weight: 600; font-size: 0.9rem;">
                    POPULAIRE
                </div>
                <h3>Pro</h3>
                <div class="price-tag">9€<span>/mois</span></div>
                <ul style="list-style: none; padding: 0; margin: 1.5rem 0;">
                    <li style="padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.05);">✓ <strong>10 sites</strong></li>
                    <li style="padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.05);">✓ 100k vues/mois</li>
                    <li style="padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.05);">✓ Dashboard complet</li>
                    <li style="padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.05);">✓ Export CSV/PDF</li>
                    <li style="padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.05);">✓ API d'accès</li>
                    <li style="padding: 0.5rem 0;">✓ Support prioritaire</li>
                </ul>
                <a href="#" class="landing-cta-button" style="width: 100%; text-align: center; background: var(--accent); color: var(--dark);">Essai 14 jours</a>
            </div>

            <div class="pricing-card">
                <h3>Business</h3>
                <div class="price-tag">29€<span>/mois</span></div>
                <ul style="list-style: none; padding: 0; margin: 1.5rem 0;">
                    <li style="padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.05);">✓ <strong>50 sites</strong></li>
                    <li style="padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.05);">✓ Visites illimitées</li>
                    <li style="padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.05);">✓ Toutes features Pro</li>
                    <li style="padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.05);">✓ Collaboration équipe</li>
                    <li style="padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.05);">✓ Support téléphone</li>
                    <li style="padding: 0.5rem 0;">✓ Intégrations custom</li>
                </ul>
                <a href="#" class="landing-cta-button" style="width: 100%; text-align: center;">Contact</a>
            </div>
        </div>
    </section>

    <!-- ====== FOOTER ====== -->
    <footer class="landing-footer">
        <div class="landing-container">
            <div style="margin-bottom: 2rem;">
                <div class="landing-logo" style="justify-content: center; margin-bottom: 1rem;">
                    <div class="landing-logo-icon">◰</div>
                    Smart Pixel Analytics
                </div>
                <p style="color: var(--gray); max-width: 600px; margin: 0 auto;">
                    Alternative open-source aux GAFAM. Code propre, données souveraines.
                </p>
            </div>

            <div style="display: flex; justify-content: center; gap: 2rem; margin-bottom: 2rem;">
                <a href="https://github.com/berru-g" style="color: var(--gray);">GitHub</a>
                <a href="#" style="color: var(--gray);">Documentation</a>
                <a href="#" style="color: var(--gray);">RGPD</a>
                <a href="#" style="color: var(--gray);">Contact</a>
            </div>

            <p style="color: var(--gray); font-size: 0.9rem;">
                © 2024 Smart Pixel Analytics — Développé avec <span style="color: #ef4444;">♥</span> et du code sur-mesure
            </p>
        </div>
    </footer>

    <script>
        // ====== GESTION IFRAME FALLBACK ======
        const dashboardIframe = document.getElementById('dashboardLivePreview');

        // Fallback si l'iframe ne charge pas (localhost en production)
        dashboardIframe.addEventListener('error', function() {
            console.log('Fallback pour dashboard');
            this.srcdoc = `
            <!DOCTYPE html>
            <html>
            <head>
                <link rel="stylesheet" href="https://raw.githubusercontent.com/berru-g/smart_pixel_v2/refs/heads/main/assets/dashboard.css">
                <style>
                    body { font-family: 'Inter', sans-serif; padding: 20px; background: #f8fafc; color: #0f172a; }
                    .demo-header { text-align: center; margin-bottom: 30px; }
                    .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
                    .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                </style>
            </head>
            <body>
                <div class="demo-header">
                    <h2 style="color: #7c3aed;">Dashboard Smart Pixel (Démo)</h2>
                    <p>Données simulées pour l'utilisateur #2</p>
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>1,247</h3>
                        <p>Visiteurs uniques</p>
                    </div>
                    <div class="stat-card">
                        <h3>4,892</h3>
                        <p>Pages vues</p>
                    </div>
                    <div class="stat-card">
                        <h3>2m 41s</h3>
                        <p>Durée moyenne</p>
                    </div>
                </div>
                <p style="text-align: center; color: #64748b; font-style: italic;">
                    En production: vos données en temps réel
                </p>
            </body>
            </html>
        `;
        });

        // ====== SMOOTH SCROLL ======
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;

                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>

</html>