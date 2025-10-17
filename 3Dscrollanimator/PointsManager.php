<?php
require_once 'config.php';

class PointsManager {
    public static function deductPoints($userId, $points = 50) {//deduit des point lors de la naviguation...bug a trouvé icic 
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $db = getDB();
            
            $stmt = $db->prepare("SELECT points FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Utilisateur non trouvé'];
            }
            
            $currentPoints = $user['points'];
            
            if ($currentPoints < $points) {
                return [
                    'success' => false, 
                    'message' => 'Points insuffisants. Il vous reste ' . $currentPoints . ' points'
                ];
            }
            
            $newPoints = $currentPoints - $points;
            $stmt = $db->prepare("UPDATE users SET points = ? WHERE id = ?");
            $stmt->execute([$newPoints, $userId]);
            
            $_SESSION['user_points'] = $newPoints;
            
            return [
                'success' => true, 
                'new_balance' => $newPoints,
                'message' => 'Points déduits avec succès'
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur déduction points: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur système'];
        }
    }
    
    public static function getPoints($userId) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT points FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $user ? $user['points'] : 0;
            
        } catch (PDOException $e) {
            error_log("Erreur récupération points: " . $e->getMessage());
            return 0;
        }
    }
    
    public static function addPoints($userId, $points) {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $db = getDB();
            
            $stmt = $db->prepare("UPDATE users SET points = points + ? WHERE id = ?");
            $stmt->execute([$points, $userId]);
            
            // Mettre à jour la session
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                $_SESSION['user_points'] = self::getPoints($userId);
            }
            
            return ['success' => true, 'message' => 'Points ajoutés avec succès'];
            
        } catch (PDOException $e) {
            error_log("Erreur ajout points: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur système'];
        }
    }
}
?>