<?php
require __DIR__.'/config-secret.php';
try {
    $pdo = new PDO(
        "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']}",
        $env['DB_USER'],
        $env['DB_PASS']
    );
    echo "Connexion OK ! Tables : " . implode(', ', $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN));
} catch (PDOException $e) {
    die("Erreur DB : " . $e->getMessage());
}