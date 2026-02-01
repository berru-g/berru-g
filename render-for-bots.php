<?php
header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex, follow'); // Cache aux utilisateurs normaux

$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

// Simule ta BDD - TU DOIS ADAPTER CE TABLEAU AVEC TES VRAIES DONNÉES
$projectsData = [
    'smart-pixel' => [
        'title' => 'Smart Pixel - Alternative Souveraine à Google Analytics',
        'description' => 'Solution d\'analytics open source respectueuse de la vie privée, hébergée en France.',
        'technologies' => ['PHP', 'JavaScript', 'MySQL', 'Redis'],
        'url' => 'https://gael-berru.com/smart_phpixel/',
        'image' => 'https://gael-berru.com/img/smart-pixel-preview.jpg',
        'date' => '2024-01-15'
    ],
    'blockchain-explorer' => [
        'title' => 'Blockchain Explorer - Outil d\'enquête Crypto',
        'description' => 'Outil forensic pour analyser les transactions blockchain et détecter les arnaques.',
        'technologies' => ['JavaScript', 'API Blockchain', 'React'],
        'url' => 'https://crypto-free-tools.netlify.app/scam-radar/enquete/',
        'image' => 'https://gael-berru.com/img/blockchain-preview.jpg',
        'date' => '2024-02-20'
    ],
    'sql-editor' => [
        'title' => 'Éditeur SQL avec Diagrammes Automatiques',
        'description' => 'Outil visuel pour construire et visualiser des bases de données SQL en temps réel.',
        'technologies' => ['SQL', 'JavaScript', 'D3.js'],
        'url' => 'https://agora-dataviz.com/',
        'image' => 'https://gael-berru.com/img/sql-editor-preview.jpg',
        'date' => '2024-03-10'
    ],
    '3d-animator' => [
        'title' => '3D Scroll Animation Creator',
        'description' => 'Outil pour créer des animations 3D interactives au défilement sans code.',
        'technologies' => ['Three.js', 'JavaScript', 'WebGL'],
        'url' => 'https://3dscrollanimator.com/',
        'image' => 'https://gael-berru.com/img/3d-preview.jpg',
        'date' => '2024-04-05'
    ]
];

$project = isset($projectsData[$slug]) ? $projectsData[$slug] : null;

if (!$project) {
    header("HTTP/1.0 404 Not Found");
    echo '<!DOCTYPE html><html><head><title>Projet non trouvé</title></head><body><h1>404 - Projet non trouvé</h1></body></html>';
    exit;
}

// Rendu HTML pour les bots
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['title']); ?> - Gaël Berru</title>
    <meta name="description" content="<?php echo htmlspecialchars($project['description']); ?>">
    <link rel="canonical" href="https://gael-berru.com/projet/<?php echo $slug; ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo htmlspecialchars($project['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($project['description']); ?>">
    <meta property="og:url" content="https://gael-berru.com/projet/<?php echo $slug; ?>">
    <meta property="og:type" content="website">
    <?php if (!empty($project['image'])): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($project['image']); ?>">
    <meta property="og:image:alt" content="<?php echo htmlspecialchars($project['title']); ?>">
    <?php endif; ?>
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($project['title']); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($project['description']); ?>">
    
    <!-- JSON-LD -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "<?php echo addslashes($project['title']); ?>",
        "description": "<?php echo addslashes($project['description']); ?>",
        "applicationCategory": "DeveloperApplication",
        "operatingSystem": "Web",
        "author": {
            "@type": "Person",
            "name": "Gaël Berru (Berru-G)",
            "url": "https://gael-berru.com",
            "sameAs": [
                "https://github.com/berru-g",
                "https://medium.com/@gael-berru"
            ]
        },
        "datePublished": "<?php echo $project['date']; ?>",
        "url": "https://gael-berru.com/projet/<?php echo $slug; ?>",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "EUR"
        }
    }
    </script>
    
    <!-- Breadcrumb -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "Accueil",
                "item": "https://gael-berru.com"
            },
            {
                "@type": "ListItem",
                "position": 2,
                "name": "Projets",
                "item": "https://gael-berru.com/#projets"
            },
            {
                "@type": "ListItem",
                "position": 3,
                "name": "<?php echo addslashes($project['title']); ?>",
                "item": "https://gael-berru.com/projet/<?php echo $slug; ?>"
            }
        ]
    }
    </script>
</head>
<body>
    <div style="max-width: 800px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
        <h1 style="color: #333; border-bottom: 2px solid #007acc; padding-bottom: 10px;">
            <?php echo htmlspecialchars($project['title']); ?>
        </h1>
        
        <div style="margin: 30px 0; font-size: 18px; line-height: 1.6; color: #555;">
            <?php echo nl2br(htmlspecialchars($project['description'])); ?>
        </div>
        
        <?php if (!empty($project['image'])): ?>
        <div style="margin: 30px 0; text-align: center;">
            <img src="<?php echo htmlspecialchars($project['image']); ?>" 
                 alt="<?php echo htmlspecialchars($project['title']); ?>"
                 style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        </div>
        <?php endif; ?>
        
        <?php if (!empty($project['technologies'])): ?>
        <div style="margin: 30px 0; padding: 20px; background: #f5f7fa; border-radius: 8px;">
            <h3 style="margin-top: 0; color: #333;">🛠️ Technologies utilisées</h3>
            <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
                <?php foreach ($project['technologies'] as $tech): ?>
                <span style="background: #007acc; color: white; padding: 6px 12px; border-radius: 20px; font-size: 14px;">
                    <?php echo htmlspecialchars($tech); ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div style="margin: 40px 0; text-align: center;">
            <a href="<?php echo htmlspecialchars($project['url']); ?>" 
               style="display: inline-block; background: #007acc; color: white; padding: 14px 28px; 
                      text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px;
                      transition: background 0.3s;"
               onmouseover="this.style.backgroundColor='#005a99'"
               onmouseout="this.style.backgroundColor='#007acc'">
                🚀 Accéder au projet
            </a>
            <p style="margin-top: 15px; color: #666; font-size: 14px;">
                Lien direct vers l'outil fonctionnel
            </p>
        </div>
        
        <div style="margin-top: 50px; padding-top: 20px; border-top: 1px solid #eee; color: #777; font-size: 14px;">
            <p><strong>À propos de l'auteur :</strong> Gaël Berru (Berru-G) est un développeur spécialisé dans la création d'outils web souverains et éthiques, alternatives aux solutions des GAFAM.</p>
            <p>📍 Basé à Vertou (France) | 🔒 Open source & RGPD compliant</p>
        </div>
    </div>
    
    <!-- Redirection pour les humains -->
   <!-- Chargement avec branding -->
<script>
const isHuman = !/bot|crawl|spider|google|yahoo|bing|duckduckgo|baidu|yandex|facebot|facebook|twitter/i.test(navigator.userAgent);

if (isHuman) {
    // Crée l'animation de chargement
    const loader = document.createElement('div');
    loader.innerHTML = `
        <style>
            .berru-loading {
                position: fixed;
                top: 0; left: 0;
                width: 100%; height: 100%;
                background: #0f172a;
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: 'Segoe UI', system-ui, sans-serif;
            }
            .loading-content {
                text-align: center;
                color: white;
            }
            .logo-animation {
                width: 80px;
                height: 80px;
                margin: 0 auto 30px;
                position: relative;
            }
            .logo-circle {
                width: 100%;
                height: 100%;
                border: 2px solid rgba(255,255,255,0.1);
                border-radius: 50%;
                position: absolute;
            }
            .logo-inner {
                width: 70%;
                height: 70%;
                border: 3px solid transparent;
                border-top: 3px solid #9d86ff;
                border-right: 3px solid #9d86ff;
                border-radius: 50%;
                position: absolute;
                top: 15%; left: 15%;
                animation: rotate 1.5s linear infinite;
            }
            @keyframes rotate {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            .loading-text {
                font-size: 22px;
                font-weight: 600;
                margin-bottom: 10px;
                background: linear-gradient(90deg, #9d86ff, #4ecdc4);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
            .loading-dots {
                display: flex;
                justify-content: center;
                gap: 8px;
                margin: 20px 0;
            }
            .dot {
                width: 8px;
                height: 8px;
                background: #9d86ff;
                border-radius: 50%;
                opacity: 0.4;
                animation: pulse 1.4s ease-in-out infinite;
            }
            .dot:nth-child(2) { animation-delay: 0.2s; }
            .dot:nth-child(3) { animation-delay: 0.4s; }
            @keyframes pulse {
                0%, 100% { opacity: 0.4; transform: scale(1); }
                50% { opacity: 1; transform: scale(1.2); }
            }
            .loading-subtitle {
                color: #9d86ff;
                font-size: 14px;
                max-width: 300px;
                margin: 0 auto;
                line-height: 1.5;
            }
            .tech-tag {
                display: inline-block;
                background: rgba(157, 134, 255, 0.1);
                color: #9d86ff;
                padding: 4px 12px;
                border-radius: 20px;
                font-size: 12px;
                margin: 5px;
                border: 1px solid rgba(157, 134, 255, 0.3);
            }
        </style>
        
        <div class="berru-loading">
            <div class="loading-content">
                <div class="logo-animation">
                    <div class="logo-circle"></div>
                    <div class="logo-inner"></div>
                </div>
                
                <div class="loading-text">berru-g</div>
                
                <div class="loading-dots">
                    <div class="dot"></div>
                    <div class="dot"></div>
                    <div class="dot"></div>
                </div>
                
                <div style="margin: 25px 0;">
                    <span class="tech-tag">PHP</span>
                    <span class="tech-tag">JavaScript</span>
                    <span class="tech-tag">SQL</span>
                    <span class="tech-tag">API</span>
                </div>
                
                <div class="loading-subtitle">
                    Préparation de l'expérience interactive<br>
                    <span style="font-size: 12px; opacity: 0.7;">Redirection vers l'interface principale...</span>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(loader);
    
    // Redirection fluide
    setTimeout(() => {
        loader.style.opacity = '0';
        loader.style.transition = 'opacity 0.5s ease';
        setTimeout(() => {
            window.location.href = 'https://gael-berru.com/#project-<?php echo $slug; ?>';
        }, 500);
    }, 1200);
}
</script>
</body>
</html>