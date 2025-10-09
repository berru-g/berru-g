<?php
// dashboard.php
require_once 'config.php';
require_once 'auth.php';
require_once 'projects.php';

if (!Auth::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = Auth::getCurrentUser();
$userProjects = ProjectManager::getUserProjects($_SESSION['user_id']);

// Gestion de la mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $updateData = [
        'username' => $_POST['username'] ?? $user['username'],
        'website' => $_POST['website'] ?? '',
        'bio' => $_POST['bio'] ?? ''
    ];
    
    // Gestion de l'avatar (simplifié)
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/avatars/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $fileExt = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $fileName = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $fileExt;
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $filePath)) {
            $updateData['avatar_url'] = '/' . $filePath;
        }
    }
    
    $success = Auth::updateProfile($_SESSION['user_id'], $updateData);
    if ($success) {
        header('Location: dashboard.php?success=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - 3D Scroll Animator</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <a href="index.php" class="logo">3D Scroll Animator</a>
        <nav class="nav-links">
            <a href="index.php">Éditeur</a>
            <a href="gallery.php">Galerie</a>
            <a href="dashboard.php" class="active">Dashboard</a>
        </nav>
        <div class="auth-section">
            <div class="user-menu">
                <span class="user-avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></span>
                <span class="user-name"><?= htmlspecialchars($user['username']) ?></span>
                <a href="?logout" class="btn btn-secondary">Déconnexion</a>
            </div>
        </div>
    </header>

    <main style="padding: 2rem; max-width: 1000px; margin: 0 auto;">
        <?php if (isset($_GET['success'])): ?>
            <div style="
                background: var(--success);
                color: white;
                padding: 1rem;
                border-radius: 8px;
                margin-bottom: 2rem;
                text-align: center;
            ">
                ✅ Profil mis à jour avec succès !
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
            <!-- Profil -->
            <div class="profile-section" style="
                background: var(--dark);
                border: 1px solid var(--border);
                border-radius: 12px;
                padding: 2rem;
            ">
                <h2 style="color: var(--primary); margin-bottom: 1.5rem;">Mon Profil</h2>
                
                <form method="POST" enctype="multipart/form-data">
                    <div style="text-align: center; margin-bottom: 2rem;">
                        <img src="<?= htmlspecialchars($user['avatar_url']) ?>" 
                             alt="Avatar"
                             style="width: 100px; height: 100px; border-radius: 50%; margin-bottom: 1rem;">
                        <input type="file" name="avatar" accept="image/*" style="margin-top: 0.5rem;">
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--rose);">Nom d'utilisateur</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" 
                               style="width: 100%; padding: 0.5rem; border-radius: 6px; border: 1px solid var(--border); background: var(--grey); color: white;">
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--rose);">Email</label>
                        <input type="email" value="<?= htmlspecialchars($user['email']) ?>" 
                               style="width: 100%; padding: 0.5rem; border-radius: 6px; border: 1px solid var(--border); background: var(--grey-light); color: #a6adc8;" disabled>
                        <small style="color: #a6adc8;">L'email ne peut pas être modifié</small>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--rose);">Site Web</label>
                        <input type="url" name="website" value="<?= htmlspecialchars($user['website'] ?? '') ?>" 
                               style="width: 100%; padding: 0.5rem; border-radius: 6px; border: 1px solid var(--border); background: var(--grey); color: white;">
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--rose);">Bio</label>
                        <textarea name="bio" rows="4" style="width: 100%; padding: 0.5rem; border-radius: 6px; border: 1px solid var(--border); background: var(--grey); color: white; resize: vertical;"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-save"></i> Mettre à jour le profil
                    </button>
                </form>

                <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--border);">
                    <h4 style="color: var(--primary); margin-bottom: 1rem;">Statistiques</h4>
                    <div style="color: #a6adc8;">
                        <p>📊 Projets créés: <?= count($userProjects) ?></p>
                        <p>⭐ Abonnement: <?= ucfirst($user['subscription_type']) ?></p>
                        <p>📅 Membre depuis: <?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
                    </div>
                </div>
            </div>

            <!-- Projets -->
            <div class="projects-section" style="
                background: var(--dark);
                border: 1px solid var(--border);
                border-radius: 12px;
                padding: 2rem;
            ">
                <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 1.5rem;">
                    <h2 style="color: var(--primary); margin: 0;">Mes Projets</h2>
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nouveau projet
                    </a>
                </div>

                <?php if (empty($userProjects)): ?>
                    <div style="text-align: center; padding: 3rem; color: var(--rose);">
                        <i class="fas fa-cube" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <h3>Aucun projet créé</h3>
                        <p>Commencez par créer votre première animation !</p>
                        <a href="index.php" class="btn btn-primary" style="margin-top: 1rem;">
                            Créer un projet
                        </a>
                    </div>
                <?php else: ?>
                    <div class="projects-list" style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($userProjects as $project): ?>
                            <div class="project-item" style="
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                padding: 1rem;
                                border: 1px solid var(--border);
                                border-radius: 8px;
                                transition: border-color 0.2s;
                            ">
                                <div>
                                    <h4 style="margin: 0; color: var(--rose);"><?= htmlspecialchars($project['title']) ?></h4>
                                    <p style="margin: 0; color: #a6adc8; font-size: 0.9rem;">
                                        Créé le <?= date('d/m/Y à H:i', strtotime($project['created_at'])) ?>
                                        <?= $project['is_public'] ? '• 🌍 Public' : '• 🔒 Privé' ?>
                                    </p>
                                </div>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="project.php?id=<?= $project['id'] ?>" class="btn btn-secondary">
                                        <i class="fas fa-eye"></i> Voir
                                    </a>
                                    <a href="index.php?load_project=<?= $project['id'] ?>" class="btn btn-secondary">
                                        <i class="fas fa-edit"></i> Éditer
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>