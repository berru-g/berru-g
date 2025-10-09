<?php
// project.php
require_once 'config.php';
require_once 'auth.php';
require_once 'projects.php';

$projectId = $_GET['id'] ?? 0;
$project = ProjectManager::getProject($projectId);

if (!$project) {
    header('HTTP/1.0 404 Not Found');
    die('Projet non trouvé');
}

// Vérifier les permissions
if (!$project['is_public'] && (!Auth::isLoggedIn() || $project['user_id'] != $_SESSION['user_id'])) {
    header('HTTP/1.0 403 Forbidden');
    die('Accès non autorisé');
}

// Gestion des commentaires
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment']) && Auth::isLoggedIn()) {
    $comment = $_POST['comment'] ?? '';
    if (!empty($comment)) {
        ProjectManager::addComment($projectId, $_SESSION['user_id'], $comment);
        header("Location: project.php?id=$projectId");
        exit;
    }
}

$comments = ProjectManager::getProjectComments($projectId);
$isLiked = Auth::isLoggedIn() ? ProjectManager::isLikedByUser($projectId, $_SESSION['user_id']) : false;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($project['title']) ?> - 3D Scroll Animator</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <a href="index.php" class="logo">3D Scroll Animator</a>
        <nav class="nav-links">
            <a href="index.php">Éditeur</a>
            <a href="gallery.php">Galerie</a>
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

    <main style="padding: 2rem; max-width: 800px; margin: 0 auto;">
        <article style="background: var(--dark); border: 1px solid var(--border); border-radius: 12px; padding: 2rem;">
            <!-- En-tête du projet -->
            <header style="margin-bottom: 2rem;">
                <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                    <img src="<?= htmlspecialchars($project['avatar_url']) ?>" 
                         alt="<?= htmlspecialchars($project['username']) ?>"
                         style="width: 50px; height: 50px; border-radius: 50%; margin-right: 1rem;">
                    <div>
                        <h1 style="margin: 0; color: var(--rose);"><?= htmlspecialchars($project['title']) ?></h1>
                        <p style="margin: 0; color: var(--primary);">
                            par <strong><?= htmlspecialchars($project['username']) ?></strong>
                            <?php if ($project['website']): ?>
                                • <a href="<?= htmlspecialchars($project['website']) ?>" style="color: var(--primary);">Site web</a>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <?php if (!empty($project['description'])): ?>
                    <p style="color: #a6adc8; line-height: 1.6;"><?= htmlspecialchars($project['description']) ?></p>
                <?php endif; ?>

                <div style="display: flex; gap: 2rem; margin-top: 1rem; color: #a6adc8;">
                    <span>📅 <?= date('d/m/Y à H:i', strtotime($project['created_at'])) ?></span>
                    <span>👁️ <?= $project['view_count'] ?> vues</span>
                </div>
            </header>

            <!-- Actions -->
            <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                <?php if (Auth::isLoggedIn()): ?>
                    <button class="btn <?= $isLiked ? 'btn-primary' : 'btn-secondary' ?>" 
                            onclick="toggleLike(<?= $project['id'] ?>)">
                        <i class="<?= $isLiked ? 'fas' : 'far' ?> fa-heart"></i>
                        <?= $isLiked ? 'Liké' : 'Like' ?>
                    </button>
                <?php endif; ?>
                
                <a href="index.php?load_project=<?= $project['id'] ?>" class="btn btn-secondary">
                    <i class="fas fa-edit"></i> Éditer ce projet
                </a>
            </div>

            <!-- Section commentaires -->
            <section>
                <h3 style="color: var(--primary); margin-bottom: 1rem;">Commentaires</h3>
                
                <?php if (Auth::isLoggedIn()): ?>
                    <form method="POST" style="margin-bottom: 2rem;">
                        <textarea name="comment" placeholder="Ajouter un commentaire..." 
                                  rows="3" style="width: 100%; padding: 1rem; border-radius: 8px; border: 1px solid var(--border); background: var(--grey); color: white; resize: vertical;"></textarea>
                        <button type="submit" name="add_comment" class="btn btn-primary" style="margin-top: 0.5rem;">
                            <i class="fas fa-paper-plane"></i> Publier
                        </button>
                    </form>
                <?php else: ?>
                    <p style="text-align: center; color: #a6adc8; padding: 2rem;">
                        <a href="login.php" style="color: var(--primary);">Connectez-vous</a> pour commenter
                    </p>
                <?php endif; ?>

                <div class="comments-list">
                    <?php if (empty($comments)): ?>
                        <p style="text-align: center; color: #a6adc8; padding: 2rem;">
                            Aucun commentaire pour le moment. Soyez le premier à commenter !
                        </p>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                            <div style="border-bottom: 1px solid var(--border); padding: 1rem 0;">
                                <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                    <img src="<?= htmlspecialchars($comment['avatar_url']) ?>" 
                                         alt="<?= htmlspecialchars($comment['username']) ?>"
                                         style="width: 30px; height: 30px; border-radius: 50%; margin-right: 0.5rem;">
                                    <strong style="color: var(--rose);"><?= htmlspecialchars($comment['username']) ?></strong>
                                    <span style="color: #a6adc8; margin-left: auto; font-size: 0.9rem;">
                                        <?= date('d/m/Y H:i', strtotime($comment['created_at'])) ?>
                                    </span>
                                </div>
                                <p style="color: #a6adc8; margin: 0; line-height: 1.5;"><?= htmlspecialchars($comment['comment']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </article>
    </main>

    <script>
    async function toggleLike(projectId) {
        try {
            const formData = new FormData();
            const action = document.querySelector('.btn').classList.contains('btn-primary') ? 'unlike_project' : 'like_project';
            
            formData.append('action', action);
            formData.append('project_id', projectId);

            const response = await fetch('api.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                location.reload(); // Simple refresh pour mettre à jour l'interface
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