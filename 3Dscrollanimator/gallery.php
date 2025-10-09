<?php
// gallery.php - VERSION SIMPLIFIÉE
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'auth.php';

echo "<!-- Debug: Session started -->";

$projects = [];
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT p.*, u.username, u.avatar_url 
        FROM projects p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.is_public = 1 
        ORDER BY p.created_at DESC 
        LIMIT 20
    ");
    $stmt->execute();
    $projects = $stmt->fetchAll();
} catch (Exception $e) {
    echo "<!-- Error: " . $e->getMessage() . " -->";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galerie - 3D Scroll Animator</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <a href="index.php" class="logo">3D Scroll Animator</a>
        <nav class="nav-links">
            <a href="index.php">Éditeur</a>
            <a href="gallery.php" class="active">Galerie</a>
            <a href="dashboard.php">Dashboard</a>
        </nav>
        <div class="auth-section">
            <?php if (Auth::isLoggedIn()): ?>
                <div class="user-menu">
                    <span class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></span>
                    <span class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
                    <a href="?logout" class="btn btn-secondary">Déconnexion</a>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="login.php" class="btn btn-secondary">Connexion</a>
                    <a href="register.php" class="btn btn-primary">Inscription</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <main style="padding: 2rem; max-width: 1200px; margin: 0 auto;">
        <h1 style="text-align: center; margin-bottom: 2rem; color: var(--primary);">
            Galerie des Projets Communautaires
        </h1>

        <?php if (empty($projects)): ?>
            <div style="text-align: center; padding: 4rem; color: var(--rose);">
                <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <h3>Aucun projet public pour le moment</h3>
                <p>Soyez le premier à partager votre création !</p>
                <a href="index.php" class="btn btn-primary" style="margin-top: 1rem;">
                    Créer un projet
                </a>
            </div>
        <?php else: ?>
            <div class="gallery-grid" style="
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 2rem;
                margin-bottom: 3rem;
            ">
                <?php foreach ($projects as $project): ?>
                    <div class="project-card" style="
                        background: var(--dark);
                        border: 1px solid var(--border);
                        border-radius: 12px;
                        padding: 1.5rem;
                        transition: transform 0.2s, border-color 0.2s;
                    ">
                        <div class="project-header" style="
                            display: flex;
                            align-items: center;
                            margin-bottom: 1rem;
                        ">
                            <img src="<?= htmlspecialchars($project['avatar_url']) ?>" 
                                 alt="<?= htmlspecialchars($project['username']) ?>"
                                 style="width: 40px; height: 40px; border-radius: 50%; margin-right: 1rem;">
                            <div>
                                <h4 style="margin: 0; color: var(--rose);"><?= htmlspecialchars($project['title']) ?></h4>
                                <p style="margin: 0; color: var(--primary); font-size: 0.9rem;">
                                    par <?= htmlspecialchars($project['username']) ?>
                                </p>
                            </div>
                        </div>

                        <?php if (!empty($project['description'])): ?>
                            <p style="color: #a6adc8; margin-bottom: 1rem; font-size: 0.9rem;">
                                <?= htmlspecialchars($project['description']) ?>
                            </p>
                        <?php endif; ?>

                        <div class="project-stats" style="
                            display: flex;
                            justify-content: space-between;
                            color: #a6adc8;
                            font-size: 0.8rem;
                            margin-bottom: 1rem;
                        ">
                            <span>❤️ <?= $project['like_count'] ?> likes</span>
                            <span>💬 <?= $project['comment_count'] ?> commentaires</span>
                            <span>👁️ <?= $project['view_count'] ?> vues</span>
                        </div>

                        <div class="project-actions" style="display: flex; gap: 0.5rem;">
                            <button class="btn btn-secondary" style="flex: 1; font-size: 0.8rem;"
                                    onclick="viewProject(<?= $project['id'] ?>)">
                                <i class="fas fa-eye"></i> Voir
                            </button>
                            <button class="btn btn-secondary" style="flex: 1; font-size: 0.8rem;"
                                    onclick="likeProject(<?= $project['id'] ?>, this)">
                                <i class="far fa-heart"></i> Like
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div style="text-align: center;">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>" 
                           class="btn <?= $i == $page ? 'btn-primary' : 'btn-secondary' ?>"
                           style="margin: 0 0.25rem;">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <script>
    function viewProject(projectId) {
        window.location.href = `project.php?id=${projectId}`;
    }

    async function likeProject(projectId, button) {
        try {
            const formData = new FormData();
            formData.append('action', 'like_project');
            formData.append('project_id', projectId);

            const response = await fetch('api.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                button.innerHTML = '<i class="fas fa-heart"></i> Liké!';
                button.style.background = 'var(--primary)';
            } else {
                alert('Erreur: ' + result.message);
            }
        } catch (error) {
            console.error('Erreur:', error);
            alert('Erreur réseau');
        }
    }
    </script>
</body>
</html>