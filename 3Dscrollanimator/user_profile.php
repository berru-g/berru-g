<?php
// user_profile.php
require_once 'config.php';
require_once 'auth.php';
require_once 'projects.php';

$userId = $_GET['id'] ?? 0;

// Récupérer les infos de l'utilisateur
$db = getDB();
$stmt = $db->prepare("
    SELECT id, username, avatar_url, website, bio, created_at 
    FROM users 
    WHERE id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('HTTP/1.0 404 Not Found');
    die('Utilisateur non trouvé');
}

// Récupérer les projets publics de l'utilisateur
$userProjects = ProjectManager::getUserPublicProjects($userId);

// Récupérer les stats
$stmt = $db->prepare("
    SELECT 
        COUNT(p.id) as project_count,
        COUNT(DISTINCT pl.id) as total_likes,
        COUNT(DISTINCT pc.id) as total_comments
    FROM users u
    LEFT JOIN projects p ON u.id = p.user_id AND p.is_public = 1
    LEFT JOIN project_likes pl ON p.id = pl.project_id
    LEFT JOIN project_comments pc ON p.id = pc.project_id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil de <?= htmlspecialchars($user['username']) ?> - 3D Scroll Animator</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <?php require_once 'header.php'; ?>
    <!--<header class="header">
        <a href="index.php" class="logo">3D Scroll Animator</a>
        <nav class="nav-links">
            <a href="index.php">Éditeur</a>
            <a href="gallery.php">Galerie</a>
            <?php if (Auth::isLoggedIn()): ?>
                <a href="dashboard.php">Mon Profil</a>
                
            <?php endif; ?>
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
    </header>-->

    <main style="padding: 2rem; max-width: 1000px; margin: 0 auto;">
        <div class="profile-header" style="
            background: var(--dark);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        ">
            <div style="display: flex; align-items: center; justify-content: center; gap: 2rem; margin-bottom: 1.5rem;">
                <img src="<?= htmlspecialchars($user['avatar_url']) ?>" 
                     alt="<?= htmlspecialchars($user['username']) ?>"
                     style="width: 100px; height: 100px; border-radius: 50%; border: 3px solid var(--primary);">
                
                <div style="text-align: left;">
                    <h1 style="color: var(--rose); margin: 0 0 0.5rem 0;"><?= htmlspecialchars($user['username']) ?></h1>
                    
                    <?php if (!empty($user['website'])): ?>
                        <p style="margin: 0 0 0.5rem 0;">
                            <a href="<?= htmlspecialchars($user['website']) ?>" 
                               target="_blank" 
                               style="color: var(--primary); text-decoration: none;">
                                <i class="fas fa-globe"></i> Site web
                            </a>
                        </p>
                    <?php endif; ?>
                    
                    <p style="color: #a6adc8; margin: 0;">
                        <i class="fas fa-calendar"></i> Membre depuis <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                    </p>
                </div>
            </div>

            <?php if (!empty($user['bio'])): ?>
                <div style="
                    background: var(--grey-light);
                    padding: 1rem;
                    border-radius: 8px;
                    margin-top: 1rem;
                    text-align: left;
                ">
                    <p style="color: #a6adc8; margin: 0; line-height: 1.5;"><?= htmlspecialchars($user['bio']) ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Statistiques -->
        <div class="stats-grid" style="
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        ">
            <div style="background: var(--dark); border: 1px solid var(--border); border-radius: 8px; padding: 1rem; text-align: center;">
                <div style="font-size: 1.5rem; color: var(--primary); font-weight: bold;"><?= $stats['project_count'] ?></div>
                <div style="color: var(--rose); font-size: 0.9rem;">Projets</div>
            </div>
            
            <div style="background: var(--dark); border: 1px solid var(--border); border-radius: 8px; padding: 1rem; text-align: center;">
                <div style="font-size: 1.5rem; color: var(--primary); font-weight: bold;"><?= $stats['total_likes'] ?></div>
                <div style="color: var(--rose); font-size: 0.9rem;">Likes reçus</div>
            </div>
            
            <div style="background: var(--dark); border: 1px solid var(--border); border-radius: 8px; padding: 1rem; text-align: center;">
                <div style="font-size: 1.5rem; color: var(--primary); font-weight: bold;"><?= $stats['total_comments'] ?></div>
                <div style="color: var(--rose); font-size: 0.9rem;">Commentaires</div>
            </div>
        </div>

        <!-- Projets de l'utilisateur -->
        <h2 style="color: var(--primary); margin-bottom: 1.5rem;">Projets publics</h2>
        
        <?php if (empty($userProjects)): ?>
            <div style="text-align: center; padding: 3rem; color: var(--rose);">
                <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <h3>Aucun projet public</h3>
                <p>Cet utilisateur n'a pas encore partagé de projets.</p>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
                <?php foreach ($userProjects as $project): ?>
                    <div style="background: var(--dark); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem;">
                        <h3 style="margin: 0 0 1rem 0; color: var(--rose);"><?= htmlspecialchars($project['title']) ?></h3>
                        
                        <?php if (!empty($project['description'])): ?>
                            <p style="color: #a6adc8; margin-bottom: 1rem; font-size: 0.9rem;">
                                <?= htmlspecialchars($project['description']) ?>
                            </p>
                        <?php endif; ?>
                        
                        <div style="display: flex; justify-content: space-between; color: #a6adc8; font-size: 0.8rem;">
                            <span>❤️ <?= $project['like_count'] ?? 0 ?> likes</span>
                            <span>💬 <?= $project['comment_count'] ?? 0 ?> commentaires</span>
                            <span>👁️ <?= $project['view_count'] ?> vues</span>
                        </div>
                        
                        <a href="project.php?id=<?= $project['id'] ?>" 
                           class="btn btn-secondary" 
                           style="width: 100%; margin-top: 1rem; text-align: center;">
                            <i class="fas fa-eye"></i> Voir le projet
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        
    </main>
</body>
</html>