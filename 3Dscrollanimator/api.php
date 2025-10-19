<?php
// api.php - VERSION CORRIGÉE
require_once 'auth.php';
require_once 'projects.php';
require_once 'PointsManager.php';
require_once 'RewardSystem.php';

header('Content-Type: application/json');

// DÉTERMINER SI C'EST POST OU GET
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$action = $isPost ? ($_POST['action'] ?? '') : ($_GET['action'] ?? '');

error_log("=== API CALL ===");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Action: " . $action);
error_log("User ID: " . ($_SESSION['user_id'] ?? 'non connecté'));

switch ($action) {
    // === ACTIONS POST ===
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


    case 'daily_login_bonus':
        if (!Auth::isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Non connecté']);
            exit;
        }

        $result = RewardSystem::addDailyLogin($_SESSION['user_id']);
        echo json_encode($result);
        break;

    case 'social_share':
        if (!Auth::isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Non connecté']);
            exit;
        }

        $platform = $_POST['platform'] ?? 'generic';
        $result = RewardSystem::addSocialSharePoints($_SESSION['user_id'], $platform);
        echo json_encode($result);
        break;

    // === GESTION DES POINTS (POST) ===
    case 'deduct_points':
        error_log("=== DEDUCT_POINTS CALLED ===");
        if (!Auth::isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Non connecté']);
            exit;
        }

        error_log("User ID: " . $_SESSION['user_id']);
        $result = PointsManager::deductPoints($_SESSION['user_id'], 50);
        error_log("Result: " . json_encode($result));
        echo json_encode($result);
        break;

    case 'add_points':
        if (!Auth::isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Non connecté']);
            exit;
        }

        $points = intval($_POST['points'] ?? 0);
        $result = PointsManager::addPoints($_SESSION['user_id'], $points);
        echo json_encode($result);
        break;

    // === CHART du CA ===
    case 'get_revenue_stats':
        if (!Auth::isLoggedIn() || $_SESSION['user_id'] != 1) {
            echo json_encode(['success' => false, 'message' => 'Non autorisé']);
            exit;
        }

        $period = intval($_GET['period'] ?? 30); // Jours
        $db = getDB();

        // Calculer la date de début
        $startDate = date('Y-m-d', strtotime("-$period days"));

        // Requête pour les données quotidiennes
        $sql = "SELECT 
                DATE(created_at) as date,
                SUM(amount_eur) as daily_revenue,
                COUNT(*) as transaction_count,
                SUM(points_amount) as points_sold
            FROM point_transactions 
            WHERE status = 'completed' 
            AND created_at >= ?
            GROUP BY DATE(created_at)
            ORDER BY date";

        $stmt = $db->prepare($sql);
        $stmt->execute([$startDate]);
        $dailyData = $stmt->fetchAll();

        // Générer toutes les dates de la période (même celles sans transactions)
        $allDates = [];
        $currentDate = $startDate;
        $today = date('Y-m-d');

        while ($currentDate <= $today) {
            $allDates[] = $currentDate;
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }

        // Fusionner avec les données réelles
        $dataByDate = [];
        foreach ($dailyData as $row) {
            $dataByDate[$row['date']] = $row;
        }

        $chartData = [
            'labels' => [],
            'revenue' => [],
            'transactions' => [],
            'points' => []
        ];

        foreach ($allDates as $date) {
            $chartData['labels'][] = date('d/m', strtotime($date));
            $chartData['revenue'][] = floatval($dataByDate[$date]['daily_revenue'] ?? 0);
            $chartData['transactions'][] = intval($dataByDate[$date]['transaction_count'] ?? 0);
            $chartData['points'][] = intval($dataByDate[$date]['points_sold'] ?? 0);
        }

        // Stats supplémentaires
        $statsSql = "SELECT 
                    SUM(amount_eur) as total_revenue,
                    COUNT(*) as total_transactions,
                    AVG(amount_eur) as avg_transaction_value
                FROM point_transactions 
                WHERE status = 'completed' 
                AND created_at >= ?";

        $statsStmt = $db->prepare($statsSql);
        $statsStmt->execute([$startDate]);
        $periodStats = $statsStmt->fetch();

        echo json_encode([
            'success' => true,
            'chartData' => $chartData,
            'periodStats' => $periodStats,
            'period' => $period
        ]);
        break;

    // === ACTIONS GET ===
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

    case 'get_user_points':
        if (!Auth::isLoggedIn()) {
            echo json_encode(['success' => false, 'points' => 0]);
            exit;
        }

        $points = PointsManager::getPoints($_SESSION['user_id']);
        echo json_encode(['success' => true, 'points' => $points]);
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
        echo json_encode(['success' => false, 'message' => 'Action inconnue: ' . $action]);
        break;
}

?>