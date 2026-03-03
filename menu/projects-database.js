const projectsDatabase = [
    {
        id: 'berru-clothing',
        title: 'Berru Clothing',
        shortDesc: 'Création d\'une marque de fringue et site e-commerce de vêtements',
        longDesc: 'Création d\'une marque de vêtements et site e-commerce de 2016 à 2018.',
        keywords: ['e-commerce', 'vetement', 'fringue', 'marque'],
        image: './img/berru-clothing-2017.png',
        link: 'https://berru-clothing.com',
        category: 'e-commerce',
        features: ['Création de la marque', 'Développement du site e-commerce'],
        tags: ['e-commerce', 'vetement', 'fringue', 'marque']
    },
    {
        id: 'les-flaneries',
        title: 'Les Flaneries',
        shortDesc: 'Création d\'une guinguette open air.',
        longDesc: 'Création d\'une guinguette open air au parc de la gaudiniére à Nantes. de 2018 à 2022.',
        keywords: ['guinguette', 'organisation', 'asso', 'concert', 'open-air'],
        image: './img/flanerie22.gif',
        link: 'https://berru-g.github.io/assoberru/', 
        category: 'guinguette',
        features: ['guinguette', 'concert', 'open-air'],
        tags: ['guinguette', 'concert', 'open-ai', 'dj set']
    },
    {
        id: '3dscrollanimator',
        title: '3D Scroll Animator',
        shortDesc: 'SAAS - Création d\'animations 3D synchronisées au scroll',
        longDesc: 'Créez des animations 3D synchronisées au scroll de votre site en 2 minutes. Interface facile avec génération automatique des script à integrer.',
        keywords: ['3d', 'animation', 'scroll', 'saas', 'webgl', 'threejs', 'creative', 'design'],
        image: './img/tutoexpress.gif',
        link: 'https://3dscrollanimator.com',
        category: 'saas',
        features: ['Interface drag & drop', 'Export code prêt', 'Gamification crédits', 'Paiement Euro/Solana'],
        tags: ['SAAS', 'WebGL', 'Animation', '3d', 'threejs', 'génerateur de code', 'php']
    },
   /* {
        id: 'animation',
        title: 'animation stop motion',
        shortDesc: 'Création d\'animations stop motion',
        longDesc: 'Créez des animations stop motion avec une interface simple et intuitive.',
        keywords: ['animation', 'stop motion', 'video', 'creative', 'design'],
        image: './img/jeanjean2024.mp4',
        link: 'https://youtube.com/shorts/XRlEr9VIdyc?si=SYAYvrhyyUXAokOh',
        category: 'animation',
        features: ['animation', 'stop motion', 'video', 'creative', 'design'],
        tags: ['animation', 'stop motion', 'video', 'creative', 'design']
    },*/
    {
        id: 'smart-pixel',
        title: 'Libre Analytics',
        shortDesc: 'Alternative souveraine à Google Analytics',
        longDesc: 'Analysez vos données et soyez réelement le seul à pouvoir les exploiter. Integration en 2 min, dashboard complet, conforme RGPD et open source. Doc compléte d\'integration. Analysez votre trafic sans compromettre la vie privée de vos visiteurs, avec un outil 100% Souverains et open source.',
        keywords: ['analytics', 'tracking', 'privacy', 'api', 'dashboard', 'data', 'open source'],
        image: './img/demo_dashboard.gif',
        link: 'https://gael-berru.com/LibreAnalytics/?utm_source=monsite',
        category: 'saas',
        features: ['Auto-hébergé', 'RGPD friendly', 'Dashboard complet', 'Open source'],
        tags: ['SAAS', 'Analytics', 'Privacy', 'google', 'analytics', 'gafam', 'philosophie', 'ethique', 'éthique', 'php']
    },
    {
        id: 'sql-editor',
        title: 'SQL Editor to Map',
        shortDesc: 'SAAS - Visualisation de données SQL/JSON',
        longDesc: 'Plateforme de partage et visualisation graphique de fichiers CSV, Excel, JSON avec éditeur SQL to Map.',
        keywords: ['sql', 'database', 'visualization', 'data', 'chart', 'map', 'json'],
        image: './img/sql-editor.png',
        link: 'https://agora-dataviz.com',
        category: 'saas',
        features: ['Éditeur SQL visuel', 'Import CSV/Excel/JSON', 'Cartographie automatique'],
        tags: ['SAAS', 'dataViz', 'data', 'sql']
    },
    {
        id: 'agora-dataviz',
        title: 'Agora Dataviz',
        shortDesc: 'SAAS - Social network et Visualisation de données SQL/JSON',
        longDesc: 'Réseau social, plateforme de partage et visualisation graphique de fichiers CSV, Excel, JSON avec éditeur SQL to Map.',
        keywords: ['messagerie', 'social-network', 'resau social', 'visualization', 'data', 'chart', 'map', 'json'],
        image: './img/agora.png',
        link: 'https://agora-dataviz.com',
        category: 'saas',
        features: ['Éditeur SQL visuel', 'Import CSV/Excel/JSON', 'Cartographie automatique'],
        tags: ['SAAS','messagerie', 'reseau-social', 'social-network', 'sql', 'dataviz', 'data', 'json', 'editor-sql']
    },
    {
        id: 'blockchain-explorer',
        title: 'Blockchain Explorer',
        shortDesc: 'Outil d\'investigation blockchain',
        longDesc: 'Suivi de transactions Bitcoin, identification d\'exchanges, création de diagrammes automatiques.',
        keywords: ['blockchain', 'bitcoin', 'crypto', 'investigation', 'transactions', 'security'],
        image: './img/V2.png',
        link: 'https://crypto-free-tools.netlify.app/scam-radar?utm_source=monsite/',
        category: 'tool',
        features: ['Recherche transaction', 'Diagrammes automatiques', 'Identification KYC'],
        tags: ['tool', 'Blockchain', 'btc']
    },
    {
        id: 'admin-dashboard',
        title: 'Interface Admin',
        shortDesc: 'Dashboard de gestion de bases de données',
        longDesc: 'Accédez à toutes vos bases de données dans un dashboard unique avec fonctionnalités développées sur mesure.',
        keywords: ['admin', 'dashboard', 'database', 'management', 'interface', 'panel'],
        image: './img/admin2.png',
        link: './board/login.php',
        category: 'tool',
        features: ['Multi-bases de données', 'Interface personnalisable', 'Export des données'],
        tags: ['tool', 'Admin', 'Database']
    },
    {
        id: 'golden-dessert',
        title: 'Golden Dessert CMS',
        shortDesc: 'Site vitrine avec CMS pour chef pâtissier',
        longDesc: 'Site avec formulaire, galerie photo CMS, envoi automatique de mails.',
        keywords: ['cms', 'website', 'portfolio', 'vitrine', 'restaurant', 'gallery'],
        image: './img/devgoldendessert.png',
        link: 'https://goldendessert.fr?utm_source=monsite',
        category: 'website',
        features: ['Galerie CMS', 'Formulaire', 'Analytics', 'Responsive design'],
        tags: ['SITE', 'CMS', 'Vitrine']
    },
    {
        id: 'crypto-tools',
        title: 'Crypto Free tools',
        shortDesc: 'Suite d\'outils pour traders crypto',
        longDesc: 'Suite d\'outils pour trader. Utilisation des API TradingView, CoinGecko et Binance pour analyse de marché et trading.',
        keywords: ['fibonnacci', 'tradingview', 'tool', 'binance', 'coingecko', 'market'],
        image: './img/crypto-tool.gif',
        link: 'https://crypto-free-tools.netlify.app/?utm_source=monsite',
        category: 'tool',
        features: ['API CoinGecko', 'API Binance', 'API TradingView'],
        tags: ['tool', 'Crypto', 'Trading', 'api', 'btc', 'chart']
    },
    {
        id: 'smb-chat',
        title: 'Local Network Chat',
        shortDesc: 'Chat chiffré en réseau local',
        longDesc: 'Communication en réseau local via le protocole SMB,  avec messages chiffrés AES-128 + HMAC-SHA256.',
        keywords: ['chat', 'local', 'network', 'encryption', 'security', 'smb', 'chiffrement'],
        image: './img/smbchat.png',
        link: 'https://berru-g.github.io/OTTO/SMBchat/SMBchatV2/',
        category: 'tool',
        features: ['Chiffrement AES-128', 'Transfert de fichiers', 'Pas de serveur externe'],
        tags: ['tool', 'Security', 'Network', 'smb', 'protocol', '445']
    },
    {
        id: 'Enquete',
        title: 'Enquete blockchain',
        shortDesc: 'Outil d\'investigation blockchain',
        longDesc: 'Outil open source d\'investigation blockchain pour tracer des transactions Bitcoin et identifier les plateformes KYC.',
        keywords: ['blockchain', 'bitcoin', 'enquete', 'investigation', 'api'],
        image: './img/enquete.png',
        link: 'https://github.com/berru-g/crypto-tool/blob/main/scam-radar/enquete/readme.md/',
        category: 'tool',
        features: ['API', 'API Blockstream', 'Diagramme', 'Investigation'],
        tags: ['tool', 'Forensic', 'investigation', 'btc', 'bitcoin', 'crypto', 'enquete', 'api']
    },
    {
        id: 'Heatmap',
        title: 'Heat map 3D',
        shortDesc: 'Explorer les volumes comme jamais',
        longDesc: 'Observer la carte termiques des capitalisation crypto sous forme de foret 3D.',
        keywords: ['blockchain', 'bitcoin', '3d', 'threejs', 'api'],
        image: './img/heatmap-forest.gif',
        link: 'https://crypto-free-tools.netlify.app/heatmap-forest/',
        category: 'tool',
        features: ['API CoinGecko', '3D', 'Threejs', 'map', 'webgl'],
        tags: ['tool', '3D', 'crypto', 'heatmap', 'api', 'threejs']
    },
    /* {
         id: 'guide-crypto',
         title: 'Guide Crypto débutant',
         shortDesc: 'Guide crypto débutant',
         longDesc: 'Guide crypto débutant. Pas pour devenir riche mais pour apprendre à naviguer en sécurité.',
         keywords: ['guide','crypto', 'solana'],
         image: './img/guide.png',
         link: 'https://crypto-free-tools.netlify.app/guide-pour-debutants/',
         category: 'guide',
         features: ['Guide de stacking Solana', 'Apprendre à faire des transactions sécurisé'],
         tags: ['guide','crypto', 'solana']
     },*/
    {
        id: 'guide-crypto',
        title: 'Guide Crypto débutant',
        shortDesc: 'Guide crypto débutant',
        longDesc: 'Guide crypto débutant. Pas pour devenir riche mais pour apprendre à faire des transactions sécurisées, ouvrir un wallet décentralisé sans KYC, stacker sur le web3 et récupérer vos fonds.',
        keywords: ['guide', 'crypto', 'solana'],
        image: './img/guide.png',
        link: 'https://crypto-free-tools.netlify.app/guide-pour-debutants/',
        category: 'guide',
        features: ['Guide de stacking Solana', 'Apprendre à faire des transactions sécurisé'],
        tags: ['guide', 'crypto', 'solana']
    },
    {
        id: 'Contact',
        title: 'Contact',
        shortDesc: 'Vous voulez discuter ',
        longDesc: 'Le formulaire ce situe dans le menu ou via \'contact@gael-berru.com\'. Commencez par me parler de votre problématique actuel et en quoi je peut vous aider. ',
        keywords: ['contact', 'message'],
        image: './img/faceMorph.gif',
        link: '#contact',
        category: 'contact',
        features: ['Votre projet match avec mes compétences ?'],
        tags: ['contact']
    },
    {
        id: 'jeu-3d',
        title: 'Immersion 3D',
        shortDesc: 'Vous voulez discuter ',
        longDesc: 'Visitez mon site en immersion. Plongez dans un univers avec des missions et système de gamification pour récupérez le trésor caché.',
        keywords: ['3d', 'jeu', 'immersion', 'proto', 'glb', 'threejs', 'webgl'],
        image: './img/berru3D.gif',
        link: './3D/',
        category: 'jeu dans navigateur',
        features: ['Proto', 'jeu dans navigateur', '3D'],
        tags: ['Proto', '3D', 'jeu', 'immersion', 'glb', 'threejs', 'webgl']
    },
    {
        id: 'help-desk',
        title: 'Help Desk',
        shortDesc: 'HelpDesk tool ',
        longDesc: 'Terminal de surveillance système HELP DESK avec SCAN Réseau style NMAP',
        keywords: ['help', 'desk', 'nmap', 'python', 'console', 'cmd', 'scan', 'exe', 'tool'],
        image: './img/helpdesk.png',
        link: 'https://github.com/berru-g/OTTO/tree/main/secu',
        category: 'tool',
        features: ['helpdesk', 'python', 'terminal'],
        tags: ['help', 'desk', 'nmap', 'python', 'console', 'cmd', 'scan', 'exe', 'tool']
    },
    {
        id: 'help-desk-basic',
        title: 'help desk basic js',
        shortDesc: 'Connaitre votre machine  2021',
        longDesc: 'Accédez aux information basique de votre machine via un pseudo terminal.',
        keywords: ['help', 'desk', 'tool'],
        image: '#',
        link: 'https://berru-g.github.io/console-interactive/',
        category: 'tool',
        features: ['helpdesk', 'javascript', 'terminal'],
        tags: ['help', 'console', 'tool']
    },
    {
        id: 'github',
        title: 'Visitez mon github',
        shortDesc: 'Explorer mes travaux ligne aprés ligne depuis 2020.',
        longDesc: 'Toute mes script visible ici',
        keywords: ['github', 'code', 'travaux', 'code source', 'skill'],
        image: './img/profilgit.png',
        link: 'https://github.com/berru-g/',
        category: 'github',
        features: ['Mes travaux sont ici github.com/berru-g'],
        tags: ['code', 'github', 'programmation', "berru-g"]
    },
    {
        id: 'Qrcode',
        title: 'Qr-code generator',
        shortDesc: 'Créz votre QRcode 2021',
        longDesc: 'Créez votre QRcode sans pub et gratuitement',
        keywords: ['help', 'desk', 'nmap', 'python', 'console', 'cmd', 'scan', 'exe', 'tool'],
        image: './img/btcP.png',
        link: 'https://berru-g.github.io/generate-qrcode/',
        category: 'QRcode',
        features: ['génerateur de QRcode'],
        tags: ['qrcode', 'generat']
    },
    {
        id: 'npm or pip',
        title: 'Philosophie et algorithme',
        shortDesc: 'install ce que tu veux',
        longDesc: 'npm install resilience',
        keywords: ['philosophie', 'npm', 'pip', 'install'],
        image: 'https://user-images.githubusercontent.com/61543927/230213178-fda2cb13-9329-49e6-9712-83f87fe51736.png',
        link: 'https://github.com/berru-g/phylorythme/tree/main',
        category: 'Phylorythme',
        features: ['philosophie', 'envie', 'joie', 'resilience'],
        tags: ['philosophie', 'npm', 'pip', 'install']
    },
    {
        id: 'arduino-micro',
        title: 'Controller pseudo MIDI - USB C with Arduino',
        shortDesc: 'Plug & play controller pour DAW',
        longDesc: 'Microcontrôleur usb midi lowcost. Plug &play sur tout logiciel DAW comme Ableton, Arena ou tout autre logiciel de création musicale ou vidéo.',
        keywords: ['arduino', 'electronic', "pcb", 'daw', 'music', 'hackster', 'mapping', 'ableton', 'arena'],
        image: './img/make&play.jpg',
        link: 'https://www.hackster.io/gleberruyer',
        category: 'Arduino',
        features: ['arduino', 'electronic'],
        tags: ['arduino', 'electronic', "pcb", 'daw', 'music', 'hackster']
    },
    {
        id: 'arduino',
        title: 'Controller with Arduino',
        shortDesc: 'Controller pour DAW',
        longDesc: 'Microcontrôleur tout logiciel DAW comme Ableton. (Code de 2020, des maj sont certainement nécessaire)',
        keywords: ['arduino', 'electronic', "pcb", 'daw', 'cuivre', 'music', 'hackster'],
        image: './img/make&play_cooper.jpg',
        link: 'https://www.hackster.io/gleberruyer/midi-copper-key-144c42',
        category: 'Arduino',
        features: ['arduino', 'electronic'],
        tags: ['arduino', 'electronic', "cooper", 'cuivre', 'daw', 'music', 'hackster']
    },
    {
        id: 'advent-calendar',
        title: 'Advent calendar',
        shortDesc: 'Calendrier de l\'avent',
        longDesc: 'Calendrier de l\'avent de mes projets open source',
        keywords: ['surprise', 'calendrier', 'noël', 'advent'],
        image: './img/advent-calendar.png',
        link: './advent-calendar/index.html',
        category: 'site',
        features: ['Calendrier de l\'avent'],
        tags: ['surprise', 'calendrier', 'noël', 'advent']
    },
    {
        id: 'what',
        title: 'C\'est ça que tu cherche ?',
        shortDesc: '...',
        longDesc: '...',
        keywords: ['wikipédia', 'wikipedia'],
        image: 'https://upload.wikimedia.org/wikipedia/commons/a/aa/Wikipedia-logo-v2-o50.svg',
        link: 'https://fr.wikipedia.org/wiki/Algorithme',
        category: 'Wikipedia',
        features: ['quoi d\'autre ?'],
        tags: ['wikipédia', 'wikipedia']
    },
    {
        id: 'welcome',
        title: 'Bienvenue',
        shortDesc: 'bienvenue',
        longDesc: `<strong>Qui suis-je ?</strong> Développeur full-stack autodidacte depuis 2020. Artisan du code souverain.
<div id="welcome-section">    
<h3>Ma méthode : la souveraineté technique</h3>

<p>Depuis 2020, j'ai choisi une voie singulière :</p>

<ul>
    <li>• <strong>Ni frameworks ni template</strong> → Un contrôle total sur chaque section de votre site/outils.</li>
    <li>• <strong>Pas de dépendances superflues</strong> → Le code source, vos données et celle de vos clients vous appartienent vraiment.</li>
    <li>• <strong>Pas de Lovable</strong> → Pas de copier coller de code que je ne comprends pas = pas de boite noire.</li>
</ul>

<h3>Mon atelier</h3>

<p>Je travaille avec les technologies fondamentales :</p>

<ul>
    <li>• <strong>Frontend</strong> : HTML, CSS, JavaScript vanilla</li>
    <li>• <strong>Backend</strong> : PHP, Python</li>
    <li>• <strong>Données</strong> : SQL, architectures sur mesure</li>
</ul>

<h3>Ce que ça change pour vous</h3>

<p><strong>Si vous cherchez :</strong></p>

<ul>
    <li>• Une solution <strong>sur-mesure, car vous ne trouvez pas le template idéal.</strong></li>
    <li>• Un projet <strong> dont le code source vous appartient à 100%</strong></li>
    <li>• Une alternative <strong>souveraine aux solutions standards</strong></li>
</ul>
<p>→ Vous êtes au bon endroit.</p>

<p><em>Exemple : LibreAnalytics, mon alternative à Google Analytics</em></p>

<h3>Comment ça marche ?</h3>

<p>Cherchez vos besoins dans la barre de recherche ou tapez "/". Selon les résultats :</p>

<ol>
    <li>1. <strong>Des projets similaires</strong> → Parlons réalisation</li>
    <li>2. <strong>Peu de résultats</strong> → Explorons l'adaptation</li>
    <li>3. <strong>Aucun projet correspondant</strong> → Créons quelque chose d'unique</li>
</ol>

<p class="disclaimer"><small>Pour les tech : ma pseudo API est accessible, vous savez où chercher </small></p></div>`,
        keywords: ['bienvenue'],
        image: './img/wam.gif',
        link: '#',
        category: 'Message de bienvenue',
        features: ['Développement souverain'],
        tags: ['bienvenue']
    }

];

// Services/Expertise
const servicesDatabase = [
    {
        id: '3d-integration',
        title: 'Intégration 3D Web',
        desc: 'Intégration Three.js/WebGL pour sites immersifs',
        keywords: ['3d', 'webgl', 'threejs', 'immersion', 'interactive', 'animation'],
        icon: '🎮'
    },
    {
        id: 'dashboard-dev',
        title: 'Dashboards sur mesure',
        desc: 'Tableaux de bord personnalisés avec Chart, dataviz etc.',
        keywords: ['dashboard', 'analytics', 'charts', 'data', 'visualization'],
        icon: '📊'
    },
    {
        id: 'privacy-tools',
        title: 'Outils éthiques & RGPD',
        desc: 'Solutions respectueuses de la vie privée Smart_Pixel',
        keywords: ['privacy', 'rgpd', 'ethical', 'open source', 'data protection'],
        icon: '🛡️'
    },
    {
        id: 'api-integration',
        title: 'Intégration API',
        desc: 'Connexion à des APIs tierces (Binance, CoinGecko, blockstream.) et API privé.',
        keywords: ['api', 'integration', 'rest', 'websocket', 'third-party'],
        icon: '🔌'
    }
];

// Exporter pour usage global
window.projectsDB = projectsDatabase;
window.servicesDB = servicesDatabase;