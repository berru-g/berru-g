Solution OPTIMALE (sur O2Switch)

    Dans ton panel O2Switch :

        Va dans MySQL > Gestion des bases

        Crée un utilisateur dédié (pas "root") avec des permissions restrictives

    Utilise leurs variables prédéfinies :

php

$pdo = new PDO(
    "mysql:host=localhost;dbname=o2switch_golden;charset=utf8mb4",
    $_SERVER['BDD_LOGIN'], // Auto-généré par O2Switch
    $_SERVER['BDD_PASSWORD'] // Stocké sécurisé par eux
);

🔐 Bonnes pratiques supplémentaires :

    Permissions minimales :
    sql

GRANT SELECT, INSERT, UPDATE ON golden_dessert.* TO 'user_web'@'localhost';

Protection du fichier config :
php

// db_config.php
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    die('Accès interdit');
}

Sur production :

    Demande à O2Switch de créer un utilisateur spécifique

    Utilise toujours $_SERVER pour les accès BDD

    Active les requêtes préparées partout :
    php

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);

Pourquoi c'est plus sûr ?

    🚫 Plus de mots de passe en clair dans le code

    🔄 Séparation claire entre configuration et logique

    🔑 Permissions adaptées (lecture/écriture seule si besoin)