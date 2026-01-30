# Smart Pixel

Une alternative souveraine à Google Analytics.
Vos données restent sur nos serveurs, pas chez les GAFAM. Conforme RGPD, open source, dashboard integré, doc complète.

## [Voir la doc](https://gael-berru.com/smart_phpixel/)

### Disponible

    V.0.0.1 auto hebergé | statut - gratuit open source
    V.0.1.0 software     | statut - gratuit pour 1 dashboard
    V.1.0.1 en cours     | statut - gratuit pour 1 dashboard + abonnement

#### STEP :
- system d'abonnement
- améliorer les chart et croiser les data
- landing page
- migrer bdd et site sur smartpixel.io
- ads budget
- seo


graph TD
    A[Utilisateur s'inscrit] --> B[Création compte + clé API]
    B --> C[Ajoute son site web]
    C --> D[Reçoit snippet personnalisé]
    D --> E[Colle snippet dans son site]
    
    E --> F[Première visite trackée]
    F --> G[Traitement async par workers]
    G --> H[(Stockage Redis/MySQL)]
    H --> I[Aggrégation périodique]
    I --> J[Cache dashboard]
    
    J --> K[Utilisateur ouvre dashboard]
    K --> L{Affichage données}
    L --> M[Temps réel: dernière heure]
    L --> N[Historique: jour/semaine/mois]
    
    M --> O[Graphiques interactifs]
    N --> O
    O --> P[Export/API/Alertes]



/smart_phpixel_v2/
├── app/                          # Backend sécurisé
│   ├── config/                  # Configs sensibles (.gitignore)
│   │   └── config.php
│   ├── auth/                    # Authentification
│   │   ├── Auth.php
│   │   └── SessionManager.php
│   ├── database/               # Gestion DB
│   │   ├── DB.php
│   │   └── Migrations.php
│   ├── security/               # Sécurité offensive
│   │   ├── Validator.php
│   │   ├── RateLimiter.php
│   │   └── Anonymizer.php
│   └── services/               # Services métier
│       ├── AnalyticsService.php
│       ├── SiteService.php
│       └── NotificationService.php
├── public/                      # Point d'entrée web
│   ├── index.php              # Landing page
│   ├── login.php
│   ├── register.php
│   ├── dashboard.php
│   ├── logout.php
│   ├── snippet-generator.php
│   └── verify-install.php
├── smart_pixel/                 # Tracking pixel
│   ├── pixel.php              # Endpoint principal
│   ├── pixel-debug.php        # Version debug
│   ├── smart-pixel-v2.js      # Script client
│   └── smart-pixel-loader.js  # Loader minimal
├── assets/
│   ├── css/
│   │   ├── style.css         # Global
│   │   ├── dashboard.css     # Dashboard spécifique
│   │   └── auth.css          # Pages auth
│   └── js/
│       ├── dashboard.js      # Charts & interactivité
│       ├── auth.js           # Validation forms
│       └── notifications.js  # Système notifs
├── data/                       # Données utilisateurs (.gitignore)
│   ├── logs/
│   │   ├── security/
│   │   └── analytics/
│   └── backups/
├── scripts/                    # Scripts maintenance
│   ├── setup-database.php
│   ├── backup-data.php
│   └── cleanup-old-data.php
└── docs/                       # Documentation
    ├── api.md
    ├── integration-guide.md
    └── privacy-policy.md