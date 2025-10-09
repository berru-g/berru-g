<?php
// projects.php
require_once 'config.php';

class ProjectManager {
    public static function saveProject($userId, $title, $description, $modelData, $isPublic = false) {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO projects (user_id, title, description, model_data, is_public) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $userId, 
            $title, 
            $description, 
            json_encode($modelData), 
            $isPublic ? 1 : 0
        ]);
    }
    
    public static function getUserProjects($userId) {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT * FROM projects 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public static function getPublicProjects($limit = 20, $offset = 0) {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT p.*, u.username, u.avatar_url,
            (SELECT COUNT(*) FROM project_likes WHERE project_id = p.id) as like_count,
            (SELECT COUNT(*) FROM project_comments WHERE project_id = p.id) as comment_count
            FROM projects p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.is_public = TRUE 
            ORDER BY p.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public static function getProject($projectId) {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT p.*, u.username, u.avatar_url, u.website
            FROM projects p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$projectId]);
        return $stmt->fetch();
    }
    
    public static function likeProject($projectId, $userId) {
        $db = getDB();
        try {
            $stmt = $db->prepare("
                INSERT INTO project_likes (project_id, user_id) 
                VALUES (?, ?)
            ");
            return $stmt->execute([$projectId, $userId]);
        } catch (PDOException $e) {
            // Déjà liké (contrainte unique)
            return false;
        }
    }
    
    public static function unlikeProject($projectId, $userId) {
        $db = getDB();
        $stmt = $db->prepare("
            DELETE FROM project_likes 
            WHERE project_id = ? AND user_id = ?
        ");
        return $stmt->execute([$projectId, $userId]);
    }
    
    public static function addComment($projectId, $userId, $comment) {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO project_comments (project_id, user_id, comment) 
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$projectId, $userId, $comment]);
    }
    
    public static function getProjectComments($projectId) {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT c.*, u.username, u.avatar_url
            FROM project_comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.project_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll();
    }
    
    public static function isLikedByUser($projectId, $userId) {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT id FROM project_likes 
            WHERE project_id = ? AND user_id = ?
        ");
        $stmt->execute([$projectId, $userId]);
        return $stmt->fetch() !== false;
    }
}
?>