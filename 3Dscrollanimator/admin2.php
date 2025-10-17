<?php
require_once 'config.php';
require_once 'auth.php';

if (!Auth::isLoggedIn() || $_SESSION['user_id'] != 1) {
    header('Location: index.php');
    exit;
}

$db = getDB();
$action = $_GET['action'] ?? '';

/* Gestion des actions de messagerie
if ($_POST['action'] === 'reply_feedback') {
    $feedback_id = $_POST['feedback_id'];
    $reply_message = trim($_POST['reply_message']);
    
    if (!empty($reply_message)) {
        $stmt = $db->prepare("UPDATE feedback SET admin_reply = ?, replied_at = NOW() WHERE id = ?");
        $stmt->execute([$reply_message, $feedback_id]);
        $message = "Réponse envoyée !";
    }
}
*/
// Récupération des données
$stats = [
    'total_users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_projects' => $db->query("SELECT COUNT(*) FROM projects")->fetchColumn(),
    'total_likes' => $db->query("SELECT COUNT(*) FROM project_likes")->fetchColumn(),
    'total_comments' => $db->query("SELECT COUNT(*) FROM project_comments")->fetchColumn(),
    'public_projects' => $db->query("SELECT COUNT(*) FROM projects WHERE is_public = 1")->fetchColumn(),
    'private_projects' => $db->query("SELECT COUNT(*) FROM projects WHERE is_public = 0")->fetchColumn()
   // 'unread_feedback' => $db->query("SELECT COUNT(*) FROM feedback WHERE admin_reply IS NULL")->fetchColumn()
];

// Messagerie - Récupération des feedbacks
$feedback_filter = $_GET['filter'] ?? 'all';
$filter_conditions = [
    'all' => "1=1",
    'unread' => "admin_reply IS NULL",
    'replied' => "admin_reply IS NOT NULL",
    'bugs' => "type = 'bug'",
    'features' => "type = 'feature'"
];

$feedback_condition = $filter_conditions[$feedback_filter] ?? "1=1";

$feedbacks = $db->query("
    SELECT f.*, u.username, u.points 
    FROM feedback f 
    LEFT JOIN users u ON f.user_id = u.id 
    WHERE $feedback_condition 
    ORDER BY f.created_at DESC 
    LIMIT 50
")->fetchAll();

// Top données
$top_users = $db->query("SELECT username, COUNT(p.id) as projects FROM users u LEFT JOIN projects p ON u.id = p.user_id GROUP BY u.id ORDER BY projects DESC LIMIT 5")->fetchAll();
$popular_projects = $db->query("SELECT p.title, u.username, COUNT(l.id) as likes FROM projects p JOIN users u ON p.user_id = u.id LEFT JOIN project_likes l ON p.id = l.project_id WHERE p.is_public = 1 GROUP BY p.id ORDER BY likes DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin 2.0 - 3D Scroll Animator</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-container { display: grid; grid-template-columns: 280px 1fr; gap: 2rem; padding: 2rem; max-width: 1600px; margin: 0 auto; }
        .sidebar { background: var(--surface); border-radius: 12px; padding: 1.5rem; }
        .main-content { display: flex; flex-direction: column; gap: 1.5rem; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        .stat-card { background: var(--card); padding: 1rem; border-radius: 8px; text-align: center; border: 1px solid var(--border); }
        .stat-number { font-size: 1.8rem; font-weight: bold; color: var(--primary); }
        .stat-label { font-size: 0.8rem; color: var(--text-light); margin-top: 0.5rem; }
        
        .section { background: var(--surface); border-radius: 12px; padding: 1.5rem; border: 1px solid var(--border); }
        .section-title { font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem; color: var(--text); display: flex; align-items: center; gap: 0.5rem; }
        
        .nav-tabs { display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .nav-tab { padding: 0.5rem 1rem; background: var(--card); border: 1px solid var(--border); border-radius: 6px; cursor: pointer; font-size: 0.8rem; }
        .nav-tab.active { background: var(--primary); color: white; }
        
        .feedback-list { max-height: 600px; overflow-y: auto; }
        .feedback-item { background: var(--card); border: 1px solid var(--border); border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
        .feedback-header { display: flex; justify-content: between; align-items: start; margin-bottom: 0.5rem; }
        .feedback-user { font-weight: 600; color: var(--primary); }
        .feedback-meta { font-size: 0.8rem; color: var(--text-light); }
        .feedback-type { background: var(--primary); color: white; padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.7rem; }
        .feedback-message { margin: 0.5rem 0; line-height: 1.4; }
        .feedback-reply { background: rgba(16, 185, 129, 0.1); border-left: 3px solid var(--accent); padding: 0.75rem; margin-top: 0.5rem; border-radius: 0 4px 4px 0; }
        
        .reply-form { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border); }
        .reply-input { width: 100%; padding: 0.5rem; border: 1px solid var(--border); border-radius: 4px; background: var(--background); color: var(--text); margin-bottom: 0.5rem; }
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; font-size: 0.8rem; }
        .btn-primary { background: var(--primary); color: white; }
        
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table td, .data-table th { padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border); }
        .data-table th { color: var(--primary); font-weight: 600; }
        
        .compact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        
        @media (max-width: 1024px) {
            .admin-container { grid-template-columns: 1fr; }
            .compact-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php require_once 'header.php'; ?>
    
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h3 style="margin-bottom: 1rem; color: var(--primary);">📊 Dashboard</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total_users'] ?></div>
                    <div class="stat-label">Utilisateurs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total_projects'] ?></div>
                    <div class="stat-label">Projets</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['unread_feedback'] ?></div>
                    <div class="stat-label">Messages non lus</div>
                </div>
            </div>
            
            <div style="margin-top: 1.5rem;">
                <h4 style="margin-bottom: 0.5rem; color: var(--text-light);">Quick Actions</h4>
                <button class="btn btn-primary" style="width: 100%; margin-bottom: 0.5rem;" onclick="showSection('messaging')">📨 Messagerie</button>
                <button class="btn btn-primary" style="width: 100%; margin-bottom: 0.5rem;" onclick="showSection('stats')">📈 Statistiques</button>
                <button class="btn btn-primary" style="width: 100%;" onclick="showSection('users')">👥 Utilisateurs</button>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="main-content">
            <!-- Messagerie -->
            <div class="section" id="messaging-section">
                <div class="section-title">📨 Messagerie - Feedback Utilisateurs</div>
                
                <div class="nav-tabs">
                    <div class="nav-tab <?= $feedback_filter === 'all' ? 'active' : '' ?>" onclick="setFilter('all')">Tous (<?= count($feedbacks) ?>)</div>
                    <div class="nav-tab <?= $feedback_filter === 'unread' ? 'active' : '' ?>" onclick="setFilter('unread')">Non lus (<?= $stats['unread_feedback'] ?>)</div>
                    <div class="nav-tab <?= $feedback_filter === 'replied' ? 'active' : '' ?>" onclick="setFilter('replied')">Répondu</div>
                    <div class="nav-tab <?= $feedback_filter === 'bugs' ? 'active' : '' ?>" onclick="setFilter('bugs')">Bugs</div>
                    <div class="nav-tab <?= $feedback_filter === 'features' ? 'active' : '' ?>" onclick="setFilter('features')">Features</div>
                </div>

                <div class="feedback-list">
                    <?php foreach ($feedbacks as $feedback): ?>
                        <div class="feedback-item">
                            <div class="feedback-header">
                                <div>
                                    <span class="feedback-user"><?= $feedback['username'] ?: $feedback['name'] ?></span>
                                    <span class="feedback-meta"> - <?= date('d/m/Y H:i', strtotime($feedback['created_at'])) ?></span>
                                </div>
                                <span class="feedback-type"><?= $feedback['type'] ?></span>
                            </div>
                            
                            <div class="feedback-message"><?= nl2br(htmlspecialchars($feedback['message'])) ?></div>
                            
                            <?php if ($feedback['admin_reply']): ?>
                                <div class="feedback-reply">
                                    <strong>👨‍💼 Réponse :</strong><br>
                                    <?= nl2br(htmlspecialchars($feedback['admin_reply'])) ?>
                                </div>
                            <?php else: ?>
                                <form class="reply-form" method="POST">
                                    <input type="hidden" name="action" value="reply_feedback">
                                    <input type="hidden" name="feedback_id" value="<?= $feedback['id'] ?>">
                                    <textarea class="reply-input" name="reply_message" placeholder="Tapez votre réponse..." rows="3" required></textarea>
                                    <button type="submit" class="btn btn-primary">📤 Répondre</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="section" id="stats-section" style="display: none;">
                <div class="section-title">📈 Statistiques Globales</div>
                <div class="compact-grid">
                    <div>
                        <h4>🏆 Top Créateurs</h4>
                        <table class="data-table">
                            <?php foreach ($top_users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= $user['projects'] ?> projets</td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                    <div>
                        <h4>🔥 Projets Populaires</h4>
                        <table class="data-table">
                            <?php foreach ($popular_projects as $project): ?>
                                <tr>
                                    <td><?= htmlspecialchars($project['title']) ?></td>
                                    <td><?= $project['likes'] ?> ❤️</td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showSection(section) {
            document.querySelectorAll('.section').forEach(s => s.style.display = 'none');
            document.getElementById(section + '-section').style.display = 'block';
        }
        
        function setFilter(filter) {
            window.location.href = '?filter=' + filter;
        }
        
        // Afficher la messagerie par défaut
        showSection('messaging');
    </script>
</body>
</html>