<?php
// RewardSystem.php
require_once 'PointsManager.php';

class RewardSystem {
    
    // Points de bienvenue pour nouvelle inscription
    public static function addWelcomePoints($userId) {
        return PointsManager::addPoints($userId, 100);
    }
    
    // Récompense de connexion quotidienne
    public static function addDailyLogin($userId) {
        // Vérifier si déjà connecté aujourd'hui
        $db = getDB();
        $stmt = $db->prepare("SELECT last_login FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        $today = date('Y-m-d');
        $lastLogin = $user['last_login'] ?? null;
        
        if ($lastLogin !== $today) {
            // Mettre à jour la date de connexion
            $stmt = $db->prepare("UPDATE users SET last_login = ? WHERE id = ?");
            $stmt->execute([$today, $userId]);
            
            // Ajouter les points
            return PointsManager::addPoints($userId, 10);
        }
        
        return ['success' => false, 'message' => 'Déjà connecté aujourdhui'];
    }
    
    /* Récompense pour partage sur les réseaux
    public static function addSocialSharePoints($userId, $platform) {
        $points = match($platform) {
            'twitter' => 25,
            'facebook' => 20,
            'linkedin' => 30,
            default => 15
        };
        
        return PointsManager::addPoints($userId, $points);
    }*/
    
    // Récompense pour complétion de tutoriel
    public static function addTutorialCompletion($userId, $tutorialId) {
        $points = 50; // Points pour finir un tutoriel
        return PointsManager::addPoints($userId, $points);
    }
    
    // Récompense de parrainage
    public static function addReferralPoints($referrerId) {
        return PointsManager::addPoints($referrerId, 200);
    }
    
    // Bonus de fidélité (chaque mois d'activité)
    public static function addLoyaltyBonus($userId) {
        return PointsManager::addPoints($userId, 100);
    }
}
?>