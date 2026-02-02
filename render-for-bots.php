<?php
header('Content-Type: text/html; charset=utf-8');

$slug = $_GET['slug'] ?? '';

// RÉELLE BDD - À ADAPTER AVEC TES DONNÉES
$projectsData = [
    'smart-pixel-analytics' => [
        'title' => 'Smart Pixel - Alternative Souveraine à Google Analytics',
        'description' => 'Solution analytics open source 100% française, hébergée en Europe, respectueuse RGPD.',
        'technologies' => ['PHP', 'JavaScript', 'MySQL', 'Redis'],
        'url' => 'https://gael-berru.com/smart_phpixel/',
        'image' => 'https://gael-berru.com/img/smart-pixel.png',
        'date' => '2024-01-15'
    ],
    'blockchain-explorer' => [
        'title' => 'Blockchain Explorer - Outil d\'enquête Crypto',
        'description' => 'Outil forensic pour analyser les transactions blockchain Bitcoin, Ethereum.',
        'technologies' => ['JavaScript', 'API Blockchain', 'React'],
        'url' => 'https://crypto-free-tools.netlify.app/scam-radar/enquete/',
        'image' => 'https://gael-berru.com/img/enquete.png',
        'date' => '2024-02-20'
    ],
    'sql-editor' => [
        'title' => 'Éditeur SQL avec Diagrammes Automatiques',
        'description' => 'Outil visuel pour construire, visualiser et optimiser des bases de données SQL.',
        'technologies' => ['SQL', 'JavaScript', 'D3.js'],
        'url' => 'https://agora-dataviz.com/',
        'image' => 'https://gael-berru.com/img/sql-editor.png',
        'date' => '2024-03-10'
    ],
    '3d-animator' => [
        'title' => '3D Scroll Animation Creator',
        'description' => 'Créateur d\'animations 3D interactives au défilement, sans code requis.',
        'technologies' => ['Three.js', 'JavaScript', 'WebGL'],
        'url' => 'https://3dscrollanimator.com/',
        'image' => 'https://gael-berru.com/img/3dscrollanimator.png',
        'date' => '2024-04-05'
    ],
    'advent-calendar-2025' => [
        'title' => 'Advent Calendar 2025 - Calendrier de l\'Avent Digital',
        'description' => 'Calendrier de l\'avent interactif avec surprises technologiques quotidiennes.',
        'technologies' => ['JavaScript', 'CSS3', 'HTML5'],
        'url' => 'https://gael-berru.com/advent-calendar/',
        'image' => 'https://gael-berru.com/img/advent.jpg',
        'date' => '2024-11-30'
    ],
    'data-visualization-tool' => [
        'title' => 'Data Visualization Tool - Outil de Visualisation de Données',
        'description' => 'Outil puissant pour transformer des données complexes en visualisations claires.',
        'technologies' => ['D3.js', 'Chart.js', 'PHP'],
        'url' => 'https://gael-berru.com/data-viz/',
        'image' => 'https://gael-berru.com/img/data-viz.jpg',
        'date' => '2024-05-20'
    ]
];

$project = $projectsData[$slug] ?? null;

if (!$project) {
    http_response_code(404);
    exit('Projet non trouvé');
}

// Détecte si c'est un bot
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isBot = preg_match('/bot|crawl|spider|google|yahoo|bing|duckduckgo|baidu|yandex|facebot|facebook|twitter|linkedin|whatsapp|telegram|discord|slack|pinterest|applebot|ia_archiver|Mediapartners\-Google/i', $userAgent);

if ($isBot) {
    header('X-Robots-Tag: index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1');
} else {
    header('X-Robots-Tag: noindex, follow');
}
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr" <?php if (!$isBot): ?>class="human-version" style="opacity:0;"<?php endif; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Meta SEO Essentielles -->
    <title><?= htmlspecialchars($project['title']) ?> | Gaël Berru - Développeur Web Vertou</title>
    <meta name="description" content="<?= htmlspecialchars($project['description']) ?>">
    <link rel="canonical" href="https://gael-berru.com/projet/<?= $slug ?>">
    
    <!-- Open Graph -->
    <meta property="og:locale" content="fr_FR">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($project['title']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($project['description']) ?>">
    <meta property="og:url" content="https://gael-berru.com/projet/<?= $slug ?>">
    <meta property="og:site_name" content="Gaël Berru - Développeur Web">
    <?php if (!empty($project['image'])): ?>
    <meta property="og:image" content="<?= htmlspecialchars($project['image']) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?= htmlspecialchars($project['title']) ?>">
    <?php endif; ?>
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($project['title']) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($project['description']) ?>">
    
    <!-- JSON-LD Structuré -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@graph": [
            {
                "@type": "SoftwareApplication",
                "@id": "https://gael-berru.com/projet/<?= $slug ?>#software",
                "name": "<?= addslashes($project['title']) ?>",
                "description": "<?= addslashes($project['description']) ?>",
                "applicationCategory": "DeveloperApplication",
                "operatingSystem": "Web",
                "author": {
                    "@type": "Person",
                    "@id": "https://gael-berru.com/#person",
                    "name": "Gaël Berru",
                    "url": "https://gael-berru.com",
                    "jobTitle": "Développeur Web Full Stack",
                    "worksFor": {
                        "@type": "Organization",
                        "name": "berru-g"
                    }
                },
                "datePublished": "<?= $project['date'] ?>",
                "url": "https://gael-berru.com/projet/<?= $slug ?>",
                "offers": {
                    "@type": "Offer",
                    "price": "0",
                    "priceCurrency": "EUR"
                },
                "applicationSuite": "Outils Souverains"
            },
            {
                "@type": "BreadcrumbList",
                "@id": "https://gael-berru.com/projet/<?= $slug ?>#breadcrumb",
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
                        "name": "<?= addslashes($project['title']) ?>",
                        "item": "https://gael-berru.com/projet/<?= $slug ?>"
                    }
                ]
            }
        ]
    }
    </script>
    
    <style>
        /* ===== RÉINITIALISATION ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* ===== MASQUAGE IMMÉDIAT POUR HUMAINS ===== */
        .human-version .bot-content {
            display: none !important;
        }
        
        .human-version {
            transition: opacity 0.3s ease;
        }
        
        /* ===== STYLES POUR LES BOTS (toujours visibles) ===== */
        .bot-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
            font-family: system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            color: #1e293b;
            min-height: 100vh;
            background: #ffffff;
        }
        .bot-title {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: #0f172a;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 1rem;
        }
        .bot-description {
            font-size: 1.25rem;
            color: #475569;
            margin-bottom: 2rem;
        }
        .bot-image {
            max-width: 100%;
            height: auto; 
            border-radius: 12px;
            margin: 2rem 0;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .bot-technologies {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin: 2rem 0;
        }
        .bot-tech-tag {
            background: #e0e7ff;
            color: #3730a3;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .bot-button {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 1rem 2rem;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.125rem;
            transition: background 0.2s;
            margin-top: 1rem;
        }
        .bot-button:hover {
            background: #2563eb;
        }
        .bot-author {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 0.875rem;
        }
        
        /* ===== STYLES POUR LES HUMAINS (spinner) ===== */
        .human-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            font-family: 'Segoe UI', system-ui, sans-serif;
            opacity: 1;
            transition: opacity 0.5s ease;
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
            top: 15%;
            left: 15%;
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
</head>
<body>
    <!-- ===== CONTENU POUR LES BOTS (Google voit ça) ===== -->
    <div class="bot-content">
        <h1 class="bot-title"><?= htmlspecialchars($project['title']) ?></h1>
        
        <p class="bot-description"><?= nl2br(htmlspecialchars($project['description'])) ?></p>
        
        <?php if (!empty($project['image'])): ?>
        <img src="<?= htmlspecialchars($project['image']) ?>" 
             alt="<?= htmlspecialchars($project['title']) ?>" 
             class="bot-image">
        <?php endif; ?>
        
        <?php if (!empty($project['technologies'])): ?>
        <div class="bot-technologies">
            <?php foreach ($project['technologies'] as $tech): ?>
            <span class="bot-tech-tag"><?= htmlspecialchars($tech) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <a href="<?= htmlspecialchars($project['url']) ?>" class="bot-button">
            🚀 Accéder au projet
        </a>
        
        <div class="bot-author">
            <p><strong>Développeur :</strong> Gaël Berru (berru-g) - Créateur d'outils web souverains et éthiques.</p>
            <p>📍 Vertou, France | 🔓 Open source | 🛡️ RGPD compliant</p>
        </div>
    </div>
    
    <!-- ===== SPINNER POUR LES HUMAINS ===== -->
    <?php if (!$isBot): ?>
    <div class="human-loader">
        <div style="text-align: center;">
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
    
    <script>
        // Affiche la page immédiatement
        document.documentElement.style.opacity = '1';
        
        // Redirection fluide après 1.2s
        setTimeout(() => {
            const loader = document.querySelector('.human-loader');
            loader.style.opacity = '0';
            
            setTimeout(() => {
                window.location.href = 'https://gael-berru.com';
            }, 500);
        }, 1200);
    </script>
    <?php endif; ?>
</body>
</html>