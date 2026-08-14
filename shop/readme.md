/shop/
│
├── /inc/
│   ├── config.php          # Config DB + clés API
│   ├── db.php              # Connexion SQL
│   ├── coingecko.php       # Récupération des taux (SOL/USDC → USD)
│   ├── solana.php          # Fonctions pour interagir avec Solana (cURL)
│   └── functions.php       # Fonctions utilitaires
│
├── /assets/
│   ├── css/                # Styles (Picocss)
│   └── js/                 # Web3.js + ton script
│
├── index.php               # Liste des produits
├── product.php             # Détails d’un produit
├── checkout.php            # Page de paiement
├── success.php             # Confirmation de paiement
└── verify_payment.php      # Script de vérification (appelé par le frontend)