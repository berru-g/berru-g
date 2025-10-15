<?php
// auth.php - VERSION CORRIGÉE
require_once 'config.php';

// DÉMARRER LA SESSION TOUJOURS EN PREMIER
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Auth {
    
    public static function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function login($email, $password) {
        self::initSession(); // AJOUTÉ
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['username'];
            $_SESSION['user_avatar'] = $user['avatar_url'];
            $_SESSION['subscription'] = $user['subscription_type'];
            $_SESSION['user_points'] = $user['points'];
            $_SESSION['logged_in'] = true;
            
            return true;
        }
        return false;
    }
    
    public static function register($username, $email, $password) {
        self::initSession(); // RESTE
        $db = getDB();
        
        // Vérifier si l'email ou username existe déjà
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            return false; // Déjà existant
        }
        
        // Créer l'utilisateur
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $result = $stmt->execute([$username, $email, $passwordHash]);
        
        if ($result) {
            // Se connecter automatiquement après l'inscription
            return self::login($email, $password);
        }
        return false;
    }
    
    public static function logout() {
        self::initSession(); // RESTE
        $_SESSION = array();
        session_destroy();
    }
    
    public static function isLoggedIn() {
        self::initSession(); // RESTE
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) return null;
        
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    
    public static function updateProfile($userId, $data) {
        $db = getDB();
        $allowedFields = ['username', 'website', 'bio', 'avatar_url'];
        $updates = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "$key = ?";
                $params[] = $value;
            }
        }
        
        if (empty($updates)) return false;
        
        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }
}

// Gestion de la déconnexion
if (isset($_GET['logout'])) {
    Auth::logout();
    header('Location: index.php');
    exit;
}
?>