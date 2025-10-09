<?php
// config.php
class Config {
    const DB_HOST = 'localhost';
    const DB_NAME = 'u667977963_scroll3d_edit';
    const DB_USER = 'u667977963_3dedit';
    const DB_PASS = '@m#ur2Saas';
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

/* Démarrer la session ou laisser aut s'en charger 
session_name(Config::SESSION_NAME);
session_set_cookie_params(Config::SESSION_LIFETIME);
session_start();
*/
?>