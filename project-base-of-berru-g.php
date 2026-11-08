<?php
// ============================================================================
// API GITHUB — repos publics de berru-g, injectés dans la barre de recherche.
// ----------------------------------------------------------------------------
// 1 seul appel (search/repositories, 100 repos), cache fichier 6 h,
// 0 token, 0 dépendance, respecte le rate limit (60 req/h/IP sans token).
// Les repos sont formatés EXACTEMENT comme projects-database.js (id, title,
// shortDesc, longDesc, keywords, image, link, category, features, tags) :
// le moteur de recherche les traite comme des résultats comme les autres.
// Si l'API échoue sans cache : tableau vide, le reste de la page fonctionne.
// Prérequis : dossier ./cache/ accessible en écriture (créé automatiquement).
// ============================================================================

function fr_ago(int $ts): string
{
    $d = max(0, time() - $ts);
    if ($d < 3600)    return 'il y a ' . max(1, intdiv($d, 60)) . ' min';
    if ($d < 86400)   return 'il y a ' . intdiv($d, 3600) . ' h';
    if ($d < 2592000) return 'il y a ' . intdiv($d, 86400) . ' j';
    return 'le ' . date('d/m/Y', $ts);
}

function gh_repos(string $user, int $ttl = 21600): array
{
    $dir  = __DIR__ . '/cache';
    $file = $dir . '/github_repos.json';

    // Cache frais → on ne touche pas au réseau
    if (is_file($file) && (time() - filemtime($file)) < $ttl) {
        $d = json_decode((string)file_get_contents($file), true);
        if (is_array($d)) return $d;
    }

    $ctx = stream_context_create(['http' => [
        'header'  => "User-Agent: gael-berru.com\r\nAccept: application/vnd.github+json\r\n",
        'timeout' => 5,
    ]]);

    $raw = @file_get_contents(
        "https://api.github.com/search/repositories?q=user%3A" . urlencode($user) . "&sort=stars&per_page=100",
        false, $ctx
    );

    // Échec réseau : cache périmé en secours, sinon rien (jamais de données inventées)
    if ($raw === false) {
        return is_file($file) ? (json_decode((string)file_get_contents($file), true) ?: []) : [];
    }

    $r = json_decode($raw, true);
    if (!is_array($r) || empty($r['items'])) return [];

    $repos = [];
    foreach ($r['items'] as $x) {
        $lang   = (string)($x['language'] ?? 'code');
        $topics = is_array($x['topics'] ?? null) ? $x['topics'] : [];
        $stars  = (int)($x['stargazers_count'] ?? 0);
        $push   = strtotime((string)($x['pushed_at'] ?? '')) ?: 0;

        $repos[] = [
            'id'        => 'gh-' . (string)($x['name'] ?? ''),
            'title'     => (string)($x['name'] ?? ''),
            'shortDesc' => (string)($x['description'] ?? 'Dépôt GitHub public.'),
            'longDesc'  => trim(($x['description'] ?? '') . ' Code source, releases et issues sur GitHub.'),
            'keywords'  => array_values(array_unique(array_filter(array_merge(
                [$lang], $topics, [(string)($x['name'] ?? '')]
            )))),
            'icon'      => '🐙',
            'image'     => null,
            'link'      => (string)($x['html_url'] ?? ''),
            'linkLabel' => 'Voir sur GitHub',
            'category'  => 'github',
            'features'  => array_values(array_filter([
                '★ ' . $stars . ' étoiles',
                $lang,
                $push ? 'dernier push ' . fr_ago($push) : null,
                !empty($x['fork']) ? 'fork' : null,
            ])),
            'tags'      => array_slice(array_values(array_filter(array_merge(
                [$lang], $topics, ['github']
            ))), 0, 5),
        ];
    }

    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    @file_put_contents($file, json_encode($repos, JSON_UNESCAPED_UNICODE));
    return $repos;
}

$githubRepos = gh_repos('berru-g');
?>
<html lang="fr">
<!-- 
    ╔══════════════════════════════════════════════════╗
    ║     ██████╗ ███████╗██████╗ ██████╗ ██╗   ██╗    ║
    ║     ██╔══██╗██╔════╝██╔══██╗██╔══██╗██║   ██║    ║
    ║     ██████╔╝█████╗  ██████╔╝██████╔╝██║   ██║    ║
    ║     ██╔══██╗██╔══╝  ██╔══██╗██╔══██╗██║   ██║    ║
    ║     ██████╔╝███████╗██║  ██║██║  ██║╚██████╔╝    ║
    ║     ╚═════╝ ╚══════╝╚═╝  ╚═╝╚═╝  ╚═╝ ╚═════╝     ║
    ╠══════════════════════════════════════════════════╣
    ║  Project      : project-base-of-berru-g          ║
    ║  First commit : February 27, 2020                ║
    ║  Version      : 5.0.0                            ║
    ║  Copyright    : 2026 Berru                       ║
    ║  GitHub       : https://github.com/berru-g/      ║
    ╚══════════════════════════════════════════════════╝
-->

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gaël Berru — Développeur web PHP &amp; logiciel à Vertou (44)</title>
  <link rel="shortcut icon" href="/img/logo.png" />
  <link rel="apple-touch-icon" href="/img/logo.png" />
  <meta name="keywords"
    content="site web souverains vertou, developpeur web Vertou, Gael Leberruyer, Gael Berru, outils open source, Libre Analytics, web3, crypto enthusiast, enquete blockchain, open source, developpeur Vertou,">
  <meta name="author" content="Gael Berru.">
  <meta name="robots" content="index, follow">
  <meta property="og:type" content="website">
  <meta property="og:title" content="Gaël Berru — Développeur web PHP &amp; logiciel à Vertou (44)">
  <meta property="og:description"
    content="Sites, API REST et logiciels sur mesure en PHP natif, sans framework. Open source et souveraineté numérique, depuis 2020.">
  <meta property="og:image" content="https://berru-g.github.io/berru-g/img/logo.png">
  <meta property="og:locale" content="fr_FR">
  <meta name="twitter:card" content="summary">
  <link rel="alternate" type="text/plain" href="https://gael-berru.com/llms.txt" title="llms.txt">
  <meta name="theme-color" content="#9d86ff">
  <meta property="og:url" content="https://gael-berru.com/">
  <link rel="canonical" href="https://gael-berru.com/">
  <link rel="stylesheet" href="./menu/dynamic-search.css">
  <!--<link rel="stylesheet" href="./LibreAnalytics/RGPD/cookie.css">-->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intro.js@7.2.0/minified/introjs.min.css">
  <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
  

  <link rel="sitemap" type="application/xml" title="Sitemap" href="/sitemap.xml">
  <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "Gaël Leberruyer (Berru-G)",
  "url": "https://gael-berru.com",
  "description": "Développeur web à Vertou : sites, API REST et logiciels sur mesure en PHP natif, sans framework.",
  "jobTitle": "Développeur web PHP fullstack",
  "image": "https://berru-g.github.io/berru-g/img/logo.png",
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "Vertou",
    "addressRegion": "Pays de la Loire",
    "postalCode": "44120",
    "addressCountry": "FR"
  },
  "knowsLanguage": "fr",
  "sameAs": [
    "https://github.com/berru-g",
    "https://codepen.io/h-lautre",
    "https://medium.com/@gael-berru",
    "https://www.instagram.com/berru_aka/",
    "https://www.hackster.io/gleberruyer",
    "https://www.tindie.com/stores/makeandplay/",
    "https://gael-berru.com/LibreAnalytics/"
  ],
  "skills": ["Développement web", "Développeur PHP", "API REST", "Analytics souverain"],
  "hasOccupation": {
    "@type": "Occupation",
    "name": "Développeur web et logiciel",
    "description": "Conception d'outils web, d'API REST et de logiciels sur mesure",
    "skills": ["PHP", "SQL", "API REST", "JavaScript", "PCB design", "Arduino", "Easy EDA", "Hardware", "Open source"]
  },
  "knowsAbout": [
    "Développement web",
    "PHP natif",
    "API REST",
    "Open source",
    "Web souverain",
    "Développement SAAS",
    "Création de circuits électroniques"
  ]
}
</script>
    <style>

        :root {
            --grot: -apple-system, BlinkMacSystemFont, sans-serif;
            --mono: ui-monospace, monospace;
            --radius: 14px;
            --max-search: 640px;
            --max-results: 1020px;
        }

        /* Dark (défaut) */
        body {
            --bg: #151515;
            --bg-card: #1d1d1e;
            --bg-input: #282829;
            --line: #232839;
            --txt: #e8eaf2;
            --txt-dim: #8b90a5;
            --primary: #ff86e9;
            --primary-dim: rgba(52, 211, 153, 0.14);
            --violet: #ab9ff2;
            --rose: #ff7a8a;
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.45);
        }

        /* Light */
        @media (prefers-color-scheme: light) {
            body {
                --bg: #f7f8fa;
                --bg-card: #ffffff;
                --bg-input: #ffffff;
                --line: #dfe3ec;
                --txt: #14161f;
                --txt-dim: #5a6070;
                --primary: #34d399;
                --primary-dim: rgba(52, 211, 153, 0.14);
                --violet: #ab9ff2;
                --rose: #ff7a8a;
                --shadow: 0 8px 32px rgba(20, 25, 40, 0.12);
            }
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--txt);
            font-family: var(--grot);
            min-height: 100vh;
            transition: background 0.4s ease, color 0.4s ease;
        }

        /* ===== LOGO BERRU ===== */
        .brand {
            text-align: center;
            padding: clamp(2rem, 8vh, 4rem) 1rem 1.2rem;
            user-select: none;
        }

        .brand-logo {
            display: inline-flex;
            align-items: baseline;
            gap: 0.15em;
            font-family: var(--grot);
            font-size: clamp(2.4rem, 7vw, 3.6rem);
            font-weight: 700;
            letter-spacing: -0.04em;
            line-height: 1;
        }

        .brand-logo .b1 {
            color: var(--txt);
        }

        .brand-logo .b2 {
            color: var(--primary);
        }

        .brand-logo .b3 {
            color: var(--violet);
        }

        .brand-tagline {
            margin: 0.6rem 0 0;
            font-family: var(--mono);
            font-size: 0.68rem;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--txt-dim);
        }

        /* ===== BARRE DE RECHERCHE ===== */
        .search-box {
            position: sticky;
            top: 1.4rem;
            margin: 0 auto;
            width: min(var(--max-search), calc(100% - 2rem));
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.85rem 1.2rem;
            background: var(--bg-input);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            z-index: 100;
            transition: border-color 0.25s ease, box-shadow 0.25s ease, width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .search-box:focus-within {
            border-color: var(--violet);
            box-shadow: 0 0 0 4px var(--primary-dim), var(--shadow);
        }

        .search-icon {
            color: var(--txt-dim);
            flex-shrink: 0;
            transition: color 0.25s;
        }

        .search-box:focus-within .search-icon {
            color: var(--violet);
        }

        .search-input {
            flex: 1;
            min-width: 0;
            background: transparent;
            border: none;
            outline: none;
            color: var(--txt);
            font-family: var(--grot);
            font-size: 1rem;
        }

        .search-input::placeholder {
            color: var(--txt-dim);
        }

        .search-shortcut {
            font-family: var(--mono);
            font-size: 0.7rem;
            color: var(--txt-dim);
            border: 1px solid var(--line);
            border-radius: 6px;
            padding: 0.1rem 0.45rem;
            flex-shrink: 0;
        }

        /* ===== ZONE RÉSULTATS ===== */
        .main-content {
            max-width: var(--max-results);
            margin: 0 auto;
            padding: 2rem 1rem 4rem;
        }

        .dynamic-preview {
            margin-top: 0.5rem;
        }

        /* Header : compteur + croix */
        .preview-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.2rem;
        }

        .preview-badge {
            font-family: var(--mono);
            font-size: 0.72rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--txt-dim);
            padding-bottom: 0.4rem;
            border-bottom: 2px solid var(--primary);
        }

        .preview-close {
            background: transparent;
            border: none;
            color: var(--txt-dim);
            font-size: 1.5rem;
            line-height: 1;
            cursor: pointer;
            transition: color 0.2s, transform 0.3s ease;
        }

        .preview-close:hover {
            color: var(--rose);
            transform: rotate(90deg);
        }

        /* ===== CARTE RÉSULTAT — style SERP ===== */
        .preview-content {
            display: flex;
            gap: 1.1rem;
            background: var(--bg-card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 1.1rem 1.2rem;
            margin-bottom: 1rem;
            transition: border-color 0.25s ease, transform 0.25s ease, box-shadow 0.25s ease;
        }

        .preview-content:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        /* Trait latéral façon "resultat selectionné" */
        .preview-content::before {
            content: '';
            position: absolute;
        }

        .preview-image {
            flex-shrink: 0;
        }

        .preview-image img {
            width: 120px;
            height: 84px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--line);
            filter: grayscale(45%) contrast(1.05);
            transition: filter 0.35s ease, transform 0.35s ease;
            cursor: zoom-in;
        }

        .preview-content:hover .preview-image img {
            filter: none;
            transform: scale(1.04);
        }

        /* Variante service / repo / assistant (icône) */
        .preview-icon {
            flex-shrink: 0;
            width: 58px;
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.7rem;
            background: var(--bg);
            border: 1px solid var(--line);
            border-radius: 12px;
        }

        .preview-details {
            flex: 1;
            min-width: 0;
        }

        .preview-title {
            margin: 0 0 0.45rem;
            font-family: var(--mono);
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: var(--txt);
        }

        .preview-title::before {
            content: '/ ';
            color: var(--primary);
        }

        .preview-tags,
        .preview-keywords {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            margin-bottom: 0.55rem;
        }

        .preview-tag,
        .preview-keyword {
            font-family: var(--mono);
            font-size: 0.62rem;
            letter-spacing: 0.5px;
            color: var(--violet);
            background: transparent;
            border: 1px solid var(--line);
            padding: 0.15rem 0.55rem;
            border-radius: 999px;
            transition: border-color 0.2s;
        }

        .preview-content:hover .preview-tag,
        .preview-content:hover .preview-keyword {
            border-color: var(--violet);
        }

        .preview-desc {
            margin: 0 0 0.6rem;
            font-size: 0.9rem;
            line-height: 1.6;
            color: var(--txt-dim);
        }

        .preview-features {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem 1.1rem;
            margin-bottom: 0.8rem;
        }

        .preview-feature {
            font-family: var(--mono);
            font-size: 0.72rem;
            color: var(--txt);
        }

        .preview-feature::before {
            content: '✓ ';
            color: var(--primary);
        }

        .preview-link {
            font-family: var(--mono);
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--violet);
            text-decoration: none;
            transition: color 0.2s;
        }

        .preview-link:hover {
            color: var(--violet);
            text-decoration: underline;
        }

        /* ===== ÉTAT VIDE (message "aucun résultat") ===== */
        .no-results {
            text-align: center;
            font-family: var(--mono);
            color: var(--txt-dim);
            font-size: 0.85rem;
            padding: 2.5rem 0;
        }

        /* ===== LIGHTBOX — image plein format au clic ===== */
        .lightbox {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(8, 10, 15, 0.92);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            cursor: zoom-out;
            animation: lightbox-in 0.25s ease;
        }

        @keyframes lightbox-in {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .lightbox figure {
            margin: 0;
            text-align: center;
        }

        .lightbox img {
            max-width: 100%;
            max-height: 80vh;
            border-radius: var(--radius);
            border: 1px solid var(--line);
            box-shadow: var(--shadow);
            cursor: default;
        }

        .lightbox figcaption {
            margin-top: 0.8rem;
            font-family: var(--mono);
            font-size: 0.72rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--txt-dim);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 600px) {
            .search-shortcut {
                display: none;
            }

            .preview-content {
                flex-direction: column;
                gap: 0.8rem;
            }

            .preview-image img {
                width: 100%;
                height: 150px;
            }

            .brand {
                padding-top: 1.5rem;
            }
        }

        /* Respect des préférences de réduction de mouvement */
        @media (prefers-reduced-motion: reduce) {
            * {
                transition: none !important;
                animation: none !important;
            }
        }
    </style>
</head>

<body>
    <!-- Logo -->
    <header class="brand">
        <div class="brand-logo">
            <span class="b1">Project</span><span class="b2"> </span><span class="b3">Base</span>
        </div>
        <p class="brand-tagline">bdd of berru-g - 2018/2026</p>
    </header>

    <!-- Barre de recherche -->
    <div class="search-box">
        <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input type="text" class="search-input" id="searchInput" placeholder="Cherche un projet ou pose ta question…"
            autocomplete="on">
        <kbd class="search-shortcut">/</kbd>
    </div>

    <!-- Résultats injectés ici par search.js -->
    <main class="main-content"></main>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            easing: 'fade-up',
            once: true,
            offset: 40
        });
    </script>
    <script src="./LibreAnalytics/RGPD/cookie.js"></script>
    <script src="./menu/projects-database.js"></script>
    <script src="./api/fetchapi.js"></script>
    <script src="./menu/honey.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // ======================================================================
        // SEARCH + ASSISTANT + GITHUB — tout passe par la barre de recherche.
        // Trois sources, un seul rendu (classes preview-*) :
        //   - INTENTS        : réponses assistant, au format projects-database
        //   - projectsDatabase / servicesDatabase : la base JS
        //   - githubRepos    : repos berru-g via l'API GitHub (injecté en PHP)
        // ======================================================================

        const searchInput = document.getElementById('searchInput');

        // Repos publics berru-g — injectés par le PHP (cache 6 h), même format
        // que projects-database.js : id, title, shortDesc, longDesc, keywords,
        // image, link, category, features, tags.
        const githubRepos = <?= json_encode($githubRepos, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        const MAX_RESULTS = 12; // plafond d'affichage ("github" matche tous les repos)

        const EASTER_EGG_PASSWORD = '--cmd';
        const EASTER_EGG_URL = '../LibreAnalytics/smart_pixel_v2/public/betaV2.php';

        function checkEasterEgg(term) {
            if (term.toLowerCase().trim() === EASTER_EGG_PASSWORD) {
                window.open(EASTER_EGG_URL, '_blank');
                searchInput.value = '';
                return true;
            }
            return false;
        }

        const CHAT_CFG = {
            mail: 'contact@gael-berru.com?subject=Salut Berru',
            restos: 'https://dons.restosducoeur.org/faire-un-don/~mon-don/?utm_source=gael-berru.com',
            github: 'https://github.com/berru-g/',
            libreanalytics: 'https://gael-berru.com/LibreAnalytics',
            cryptoTools: 'https://crypto-free-tools.netlify.app/'
        };

        const normalize = (s) => String(s).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

        // ======================================================================
        // ASSISTANT — INTENTS au même format que projects-database.js.
        // keywords = déclencheurs scorés (+3), title (+3), desc (+1), seuil 3.
        // Ajouter une réponse = ajouter un objet ici, rien d'autre.
        // ======================================================================
        const INTENTS = [
            {
                id: 'services',
                title: 'Mes services',
                shortDesc: 'Outils sur mesure — web, données, électronique.',
                longDesc: 'Je conçois des <strong>outils sur mesure</strong> — web, données, électronique, sur devis gratuit.',
                keywords: ['service', 'prestation', 'propose', 'expertise', 'competence', 'sais', 'faire', 'peux', 'aide', 'quoi'],
                icon: '🛠️',
                image: null,
                link: 'mailto:' + CHAT_CFG.mail + '?subject=Question depuis la Project Base',
                linkLabel: 'Me contacter',
                category: 'assistant',
                features: ['Intégration 3D web (Three.js / WebGL)', 'Dashboards sur mesure & dataviz', 'Outils éthiques & RGPD', 'Intégration API', 'Électronique / PCB', 'Sites vitrine & CMS'],
                tags: ['services', 'sur-mesure', 'devis']
            },
            {
                id: 'tarifs',
                title: 'Tarifs sur-mesure',
                shortDesc: 'Grille indicative, devis gratuit.',
                longDesc: "Grille indicative — <strong>chaque projet est chiffré sur devis, gratuitement</strong>. Mes outils existants restent gratuits et open source.",
                keywords: ['prix', 'tarif', 'coute', 'combien', 'budget', 'devis', 'cher', 'euro'],
                icon: '💰',
                image: null,
                link: 'mailto:' + CHAT_CFG.mail + '?subject=Demande de devis',
                linkLabel: 'Demander un devis',
                category: 'assistant',
                features: ['Site vitrine / CMS — ~600 €', 'Dashboard sur mesure — ~900 €', 'PCB / électronique — ~400 €', 'Intégration 3D — ~700 €', 'Devis gratuit, sans engagement'],
                tags: ['tarifs', 'prix', 'devis']
            },
            {
                id: 'contact',
                title: 'Contact',
                shortDesc: 'Décris-moi ta problématique.',
                longDesc: "Le plus simple : <strong>décris-moi ta problématique</strong> actuelle et en quoi je peux t'aider. Devis gratuit, réponse humaine — pas de tunnel de vente.",
                keywords: ['contact', 'mail', 'email', 'parler', 'discuter', 'joindre', 'message', 'ecrire', 'telephone'],
                icon: '✉️',
                image: null,
                link: 'mailto:' + CHAT_CFG.mail + '?subject=Salut Berru',
                linkLabel: "M'écrire",
                category: 'assistant',
                features: [CHAT_CFG.mail],
                tags: ['contact', 'mail']
            }
        ];

        /** Score chaque intent par ses keywords (+3), title (+3), desc (+1) */
        function detectIntent(query) {
            const words = normalize(query).split(/[^a-z0-9]+/).filter(Boolean);
            const stems = words.map(w => w.slice(0, 6));
            let best = null, bestScore = 0;
            for (const it of INTENTS) {
                let s = 0;
                const title = normalize(it.title);
                const desc = normalize(it.shortDesc + ' ' + it.longDesc);
                for (const kw of it.keywords) {
                    const t = normalize(kw);
                    const hit = (w) => w === t || (t.length >= 4 && w.length >= 4 && (w.startsWith(t) || t.startsWith(w)));
                    if (words.some(hit) || stems.some(st => st.startsWith(t) && t.length >= 4)) s += 3;
                }
                if (words.some(w => w.length >= 4 && title.includes(w))) s += 3;
                if (words.some(w => w.length >= 4 && desc.includes(w))) s += 1;
                if (s > bestScore) { bestScore = s; best = it; }
            }
            return bestScore >= 3 ? best : null; // seuil : au moins un mot-clé fort
        }

        // ======================================================================
        // RECHERCHE — projets + repos GitHub + services, un même scoring
        // ======================================================================

        function calculateRelevance(item, term) {
            let r = 0;
            if (item.title.toLowerCase().includes(term)) r += 5;
            if (item.keywords.some(kw => kw.toLowerCase() === term)) r += 3;
            if (item.longDesc.toLowerCase().includes(term)) r += 2;
            if (item.shortDesc.toLowerCase().includes(term)) r += 1;
            return r;
        }

        function searchSource(list, term, type, matches) {
            list.forEach(item => {
                const searchSpace = [item.title, item.shortDesc, item.longDesc,
                ...(item.keywords || []), ...(item.tags || []).map(t => t.toLowerCase())]
                    .join(' ').toLowerCase();
                if (searchSpace.includes(term)) {
                    matches.push({ type, data: item, relevance: calculateRelevance(item, term) });
                }
            });
        }

        function performExtendedSearch(searchTerm) {
            if (!searchTerm || searchTerm.length < 2) return hideProjectPreview();

            const term = searchTerm.toLowerCase();
            const intent = detectIntent(searchTerm);
            const matches = [];

            searchSource(projectsDatabase, term, 'project', matches);
            searchSource(githubRepos, term, 'repo', matches);

            // Les services doublonnent les réponses de l'assistant :
            // quand l'assistant répond, on les laisse de côté.
            if (!intent) searchSource(servicesDatabase, term, 'service', matches);

            matches.sort((a, b) => b.relevance - a.relevance);

            if (intent || matches.length) showProjectPreview(matches, intent);
            else showNoResults(); 
        }

        function showNoResults() {
            const mainContent = document.querySelector('.main-content');
            if (!mainContent) return;
            hideProjectPreview(true);
            let empty = document.getElementById('no-results');
            if (!empty) {
                empty = document.createElement('div');
                empty.id = 'no-results';
                empty.className = 'no-results';
                mainContent.prepend(empty);
            }
            const suggestions = ['tes services', 'tes tarifs', 'contact', 'github', 'nfc', 'blockchain'];
            const chips = suggestions.map(s =>
                `<span class="preview-tag" style="cursor:pointer" onclick="fillSearch('${s}')"># ${s}</span>`
            ).join('');
            empty.innerHTML = `⌘ Aucun résultat — cherche un projet (api, arduino, pcb …), <a href="index.php" target="_blank" style="text-decoration: none; color: #ff86e9;">visite le sites</a>, ou pose ta question :<br>
                <div style="display:flex; flex-wrap:wrap; gap:0.4rem; justify-content:center; margin-top:0.9rem;">${chips}</div>`;
            empty.setAttribute('data-aos', 'fade-up');
            AOS.refreshHard();
        }

        /** Suggestion cliquable → remplit la barre et relance la recherche */
        function fillSearch(term) {
            searchInput.value = term;
            performExtendedSearch(term);
            searchInput.focus();
        }

        function showProjectPreview(matches, intent) {
            const mainContent = document.querySelector('.main-content');
            if (!mainContent) return;

            document.getElementById('no-results')?.remove();

            let preview = document.getElementById('dynamic-preview');
            if (!preview) {
                preview = document.createElement('div');
                preview.id = 'dynamic-preview';
                preview.className = 'dynamic-preview';
                mainContent.prepend(preview);
            }

            const shown = matches.slice(0, MAX_RESULTS);
            const extra = matches.length - shown.length;
            const countLabel = extra > 0
                ? `${matches.length} résultats · ${shown.length} affichés`
                : (matches.length === 1 ? '1 résultat' : matches.length + ' résultats');
            const badge = intent && matches.length
                ? `Assistant · ${countLabel}`
                : intent
                    ? 'Assistant'
                    : (matches.length === 1 ? 'Projet correspondant' : countLabel);

            let html = `
    <div class="preview-header" data-aos="fade-down" data-aos-delay="100">
      <span class="preview-badge" data-aos="fade-down" data-aos-delay="200">${badge}</span>
      <button class="preview-close" onclick="hideProjectPreview()">×</button>
    </div>`;

            // --- Carte ASSISTANT (même gabarit qu'un résultat, en tête) ---
            if (intent) {
                html += `
        <div class="preview-content service-preview" data-aos="fade-up" data-aos-delay="500" data-aos-anchor-placement="top-bottom">
          <div class="preview-icon">${intent.icon}</div>
          <div class="preview-details">
            <h3 class="preview-title">${intent.title}</h3>
            <div class="preview-tags">${intent.tags.map(t => `<span class="preview-tag"># ${t}</span>`).join('')}</div>
            <p class="preview-desc">${intent.longDesc}</p>
            <div class="preview-features">${intent.features.map(f => `<span class="preview-feature">${f}</span>`).join('')}</div>
            <a href="${intent.link}" target="_blank" class="preview-link">${intent.linkLabel} →</a>
          </div>
        </div>`;
            }

            // --- Résultats projets / repos / services, en cascade derrière ---
            shown.forEach((m, i) => {
                const delay = (intent ? 620 : 500) + i * 120;

                if (m.type === 'project') {
                    const p = m.data;
                    html += `
        <div class="preview-content" data-aos="fade-up" data-aos-delay="${delay}" data-aos-anchor-placement="top-bottom">
          <div class="preview-image" data-aos="fade-down" data-aos-delay="150"><img src="${p.image}" alt="${p.title}" loading="lazy" onclick="openLightbox(this.src, this.alt)"></div>
          <div class="preview-details">
            <h3 class="preview-title" data-aos="fade-down" data-aos-delay="250">${p.title}</h3>
            <div class="preview-tags">${p.tags.map(t => `<span class="preview-tag"># ${t}</span>`).join('')}</div>
            <p class="preview-desc">${p.longDesc}</p>
            <div class="preview-features">${p.features.map(f => `<span class="preview-feature">${f}</span>`).join('')}</div>
            <a href="${p.link}" target="_blank" class="preview-link">Voir le projet →</a>
          </div>
        </div>`;
                } else {
                    const s = m.data;
                    html += `
        <div class="preview-content service-preview" data-aos="fade-up" data-aos-delay="${delay}">
          <div class="preview-icon">${s.icon}</div>
          <div class="preview-details">
            <h3 class="preview-title">${s.title}</h3>
            <div class="preview-tags">${(s.tags || s.keywords).map(t => `<span class="preview-tag"># ${t}</span>`).join('')}</div>
            <p class="preview-desc">${s.longDesc || s.desc}</p>
            ${s.features ? `<div class="preview-features">${s.features.map(f => `<span class="preview-feature">${f}</span>`).join('')}</div>` : ''}
            <a href="${s.link}" target="_blank" class="preview-link">${s.linkLabel || 'Voir'} →</a>
          </div>
        </div>`;
                }
            });

            preview.innerHTML = html;

            AOS.refreshHard();

            preview.style.opacity = '0';
            preview.style.transform = 'translateY(-14px)';
            requestAnimationFrame(() => {
                preview.style.transition = 'all 0.45s cubic-bezier(0.4, 0, 0.2, 1)';
                preview.style.opacity = '1';
                preview.style.transform = 'translateY(0)';
            });
        }

        function hideProjectPreview(instant = false) {
            document.getElementById('no-results')?.remove();
            const preview = document.getElementById('dynamic-preview');
            if (preview) {
                if (instant) return preview.remove();
                preview.style.opacity = '0';
                preview.style.transform = 'translateY(-14px)';
                setTimeout(() => preview.remove(), 400);
            }
        }

        // ======================================================================
        // LIGHTBOX — image plein format au clic sur une vignette
        // ======================================================================
        function openLightbox(src, title) {
            closeLightbox();
            const lb = document.createElement('div');
            lb.id = 'lightbox';
            lb.className = 'lightbox';
            const fig = document.createElement('figure');
            const img = document.createElement('img');
            img.src = src;
            img.alt = title || '';
            const cap = document.createElement('figcaption');
            cap.textContent = (title || 'image') + ' — clique ou Échap pour fermer';
            fig.appendChild(img);
            fig.appendChild(cap);
            lb.appendChild(fig);
            lb.addEventListener('click', closeLightbox);
            document.body.appendChild(lb);
        }

        function closeLightbox() {
            document.getElementById('lightbox')?.remove();
        }

        // ===== ÉVÉNEMENTS =====
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.trim();
            if (checkEasterEgg(term)) return hideProjectPreview();
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => performExtendedSearch(term), 150);
        });

        document.addEventListener('keydown', (e) => {
            const tag = e.target.tagName.toLowerCase();
            const isInput = tag === 'input' || tag === 'textarea';
            if (e.key === '/' && !isInput) {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
            if (e.key === 'Escape') {
                // priorité : fermer le lightbox s'il est ouvert
                if (document.getElementById('lightbox')) { closeLightbox(); return; }
                searchInput.value = '';
                hideProjectPreview();
            }
        });
    </script>
</body>

</html>