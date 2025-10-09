<?php
// config.php
class Config {
    const DB_HOST = 'localhost';
    const DB_NAME = 'scroll3d_editor';
    const DB_USER = 'ton_username';
    const DB_PASS = 'ton_password';
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
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            die("Erreur base de données: " . $e->getMessage());
        }
    }
    return $db;
}

// Démarrer la session
session_name(Config::SESSION_NAME);
session_set_cookie_params(Config::SESSION_LIFETIME);
session_start();
?>