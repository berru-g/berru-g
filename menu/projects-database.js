const projectsDatabase = [
    {
        id: '3dscrollanimator',
        title: '3D Scroll Animator',
        shortDesc: 'SAAS - Création d\'animations 3D synchronisées au scroll',
        longDesc: 'Créez des animations 3D synchronisées au scroll de votre site en 2 minutes. Interface facile avec génération automatique des script à integrer.',
        keywords: ['3d', 'animation', 'scroll', 'saas', 'webgl', 'threejs', 'creative', 'design'],
        image: './img/Interface-3Dscrollanimator.png',
        link: 'https://3dscrollanimator.com',
        category: 'saas',
        features: ['Interface drag & drop', 'Export code prêt', 'Gamification crédits', 'Paiement Euro/Solana'],
        tags: ['SAAS', 'WebGL', 'Animation', '3d', 'threejs']
    },
    {
        id: 'smart-pixel',
        title: 'Smart Pixel Analytics',
        shortDesc: 'Alternative souveraine à Google Analytics',
        longDesc: 'Integrez un pixel sur votre site et analysez vos données sans les donner au GAFAM, elles restent sur vos serveurs. Conforme RGPD, open source, dashboard. Doc compléte d\'integration.',
        keywords: ['analytics', 'tracking', 'privacy', 'gdpr', 'dashboard', 'data', 'open source'],
        image: './img/smart-pixel.png',
        link: 'https://berru-g.github.io/smart_phpixel/',
        category: 'tool',
        features: ['Auto-hébergé', 'RGPD friendly', 'Dashboard complet', 'Open source'],
        tags: ['tool', 'Analytics', 'Privacy', 'google', 'analytics', 'gafam']
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
        tags: ['SAAS', 'DataViz', 'Database', 'sql']
    },
    {
        id: 'blockchain-explorer',
        title: 'Blockchain Explorer',
        shortDesc: 'Outil d\'investigation blockchain',
        longDesc: 'Suivi de transactions Bitcoin, identification d\'exchanges, création de diagrammes automatiques.',
        keywords: ['blockchain', 'bitcoin', 'crypto', 'investigation', 'transactions', 'security'],
        image: './img/V2.png',
        link: 'https://crypto-free-tools.netlify.app/scam-radar/',
        category: 'tool',
        features: ['Recherche transaction', 'Diagrammes automatiques', 'Identification KYC'],
        tags: ['tool', 'Blockchain', 'Security']
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
        link: 'https://goldendessert.fr',
        category: 'website',
        features: ['Galerie CMS', 'Formulaire','Analytics', 'Responsive design'],
        tags: ['SITE', 'CMS','Vitrine']
    },
    {
        id: 'crypto-tools',
        title: 'Crypto Free tools',
        shortDesc: 'Suite d\'outils pour traders crypto',
        longDesc: 'Utilisation des API CoinGecko et Binance pour analyse de marché et trading.',
        keywords: ['crypto', 'trading', 'tools', 'binance', 'coingecko', 'market'],
        image: './img/devcryptotool.png',
        link: 'https://crypto-free-tools.netlify.app/',
        category: 'tool',
        features: ['API CoinGecko', 'API Binance', 'Alertes personnalisées'],
        tags: ['tool', 'Crypto', 'Trading']
    },
    {
        id: 'smb-chat',
        title: 'Local Network Chat',
        shortDesc: 'Chat chiffré en réseau local',
        longDesc: 'Communication en réseau local avec messages chiffrés AES-128 + HMAC-SHA256.',
        keywords: ['chat', 'local', 'network', 'encryption', 'security', 'smb'],
        image: './img/smbchat.png',
        link: 'https://berru-g.github.io/OTTO/SMBchat/SMBchatV2/',
        category: 'tool',
        features: ['Chiffrement AES-128', 'Transfert de fichiers', 'Pas de serveur externe'],
        tags: ['tool', 'Security', 'Network']
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
        features: ['API CoinGecko', 'API Blockstream', 'Diagramme', 'Investigation'],
        tags: ['tool', 'Forensic', 'investigation', 'btc','bitcoin', 'crypto', 'enquete' ]
    },
    {
        id: 'Heatmap',
        title: 'Heat map 3D',
        shortDesc: 'Explorer les volumes comme jamais',
        longDesc: 'Observer la carte termiques des capitalisation crypto sous forme de foret 3D.',
        keywords: ['blockchain', 'bitcoin', 'enquete', 'investigation', 'api'],
        image: './img/heatmap-forest.png',
        link: 'https://crypto-free-tools.netlify.app/heatmap-forest/',
        category: 'tool',
        features: ['API CoinGecko', '3D', 'Threejs', 'map', 'webgl'],
        tags: ['tool', '3D', 'crypto', 'heatmap', 'api', 'threejs']
    },
     {
        id: 'Contact',
        title: 'Contact',
        shortDesc: 'Vous voulez discuter ',
        longDesc: 'Suivez moi ',
        keywords: ['contact', 'message'],
        image: './img/wamcache.png',
        link: 'portfolio.html#contact',
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
        image: './img/berru-site3D.png',
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
        keywords:  ['help', 'desk', 'nmap', 'python', 'console', 'cmd', 'scan', 'exe', 'tool'],
        image: './img/helpdesk.png',
        link: 'https://github.com/berru-g/OTTO/tree/main/secu',
        category: 'helpdesk terminal',
        features: ['helpdesk', 'python','terminal'],
        tags: ['help', 'desk', 'nmap', 'python', 'console', 'cmd', 'scan', 'exe', 'tool']
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
        desc: 'Tableaux de bord personnalisés avec Chart.js',
        keywords: ['dashboard', 'analytics', 'charts', 'data', 'visualization'],
        icon: '📊'
    },
    {
        id: 'privacy-tools',
        title: 'Outils éthiques & RGPD',
        desc: 'Solutions respectueuses de la vie privée',
        keywords: ['privacy', 'rgpd', 'ethical', 'open source', 'data protection'],
        icon: '🛡️'
    },
    {
        id: 'api-integration',
        title: 'Intégration API',
        desc: 'Connexion à des APIs tierces (Binance, CoinGecko, etc.)',
        keywords: ['api', 'integration', 'rest', 'websocket', 'third-party'],
        icon: '🔌'
    }
];

// Exporter pour usage global
window.projectsDB = projectsDatabase;
window.servicesDB = servicesDatabase;