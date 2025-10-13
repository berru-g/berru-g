<?php
// config.php
class Config {
    const DB_HOST = 'localhost';
    const DB_NAME = '#';
    const DB_USER = '#';
    const DB_PASS = 'root';
    const SESSION_NAME = 'scroll3d_auth';
    const SESSION_LIFETIME = 86400; // 24 heures
}

// Connexion PDO
function getDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new PDO(
                "mysql:host=" . Config::DB_HOST . ";dbname=" . Config::DB_NAME . ";charset=utf8",
                Config::DB_USER,
                Config::DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            die("Erreur BDD: " . $e->getMessage());
        }
    }
    return $db;
}
?>