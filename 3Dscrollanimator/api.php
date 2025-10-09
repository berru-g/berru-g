<?php
// api.php
require_once 'auth.php';
require_once 'projects.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save_project':
            if (!Auth::isLoggedIn()) {
                echo json_encode(['success' => false, 'message' => 'Non connecté']);
                exit;
            }
            
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $modelData = $_POST['model_data'] ?? '{}';
            $isPublic = isset($_POST['is_public']) && $_POST['is_public'] == 'true';
            
            if (empty($title)) {
                echo json_encode(['success' => false, 'message' => 'Titre requis']);
                exit;
            }
            
            $success = ProjectManager::saveProject(
                $_SESSION['user_id'],
                $title,
                $description,
                json_decode($modelData, true),
                $isPublic
            );
            
            echo json_encode(['success' => $success, 'message' => $success ? 'Projet sauvegardé' : 'Erreur sauvegarde']);
            break;
            
        case 'like_project':
            if (!Auth::isLoggedIn()) {
                echo json_encode(['success' => false, 'message' => 'Non connecté']);
                exit;
            }
            
            $projectId = $_POST['project_id'] ?? 0;
            $success = ProjectManager::likeProject($projectId, $_SESSION['user_id']);
            echo json_encode(['success' => $success]);
            break;
            
        case 'unlike_project':
            if (!Auth::isLoggedIn()) {
                echo json_encode(['success' => false, 'message' => 'Non connecté']);
                exit;
            }
            
            $projectId = $_POST['project_id'] ?? 0;
            $success = ProjectManager::unlikeProject($projectId, $_SESSION['user_id']);
            echo json_encode(['success' => $success]);
            break;
            
        case 'add_comment':
            if (!Auth::isLoggedIn()) {
                echo json_encode(['success' => false, 'message' => 'Non connecté']);
                exit;
            }
            
            $projectId = $_POST['project_id'] ?? 0;
            $comment = $_POST['comment'] ?? '';
            
            if (empty($comment)) {
                echo json_encode(['success' => false, 'message' => 'Commentaire vide']);
                exit;
            }
            
            $success = ProjectManager::addComment($projectId, $_SESSION['user_id'], $comment);
            echo json_encode(['success' => $success, 'message' => $success ? 'Commentaire ajouté' : 'Erreur']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action inconnue']);
    }
} else {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_project':
            $projectId = $_GET['id'] ?? 0;
            $project = ProjectManager::getProject($projectId);
            
            if ($project) {
                // Vérifier si public ou propriétaire
                if ($project['is_public'] || (Auth::isLoggedIn() && $project['user_id'] == $_SESSION['user_id'])) {
                    echo json_encode(['success' => true, 'project' => $project]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Projet non trouvé']);
            }
            break;
            
        case 'get_public_projects':
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 20;
            $offset = ($page - 1) * $limit;
            
            $projects = ProjectManager::getPublicProjects($limit, $offset);
            echo json_encode(['success' => true, 'projects' => $projects]);
            break;
            
        case 'get_user_projects':
            if (!Auth::isLoggedIn()) {
                echo json_encode(['success' => false, 'message' => 'Non connecté']);
                exit;
            }
            
            $projects = ProjectManager::getUserProjects($_SESSION['user_id']);
            echo json_encode(['success' => true, 'projects' => $projects]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action inconnue']);
    }
}
?>