<?php
// dashboard_admin.php
require_once 'config.php';
require_once 'auth.php';
require_once 'projects.php';

// Vérifier que c'est bien l'admin (ID 1)
if (!Auth::isLoggedIn() || $_SESSION['user_id'] != 1) {
    header('Location: index.php');
    exit;
}

if (!Auth::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Récupérer toutes les statistiques
$db = getDB();

// Statistiques générales
$stats = [];

// Nombre total d'utilisateurs
$stmt = $db->query("SELECT COUNT(*) as total_users FROM users");
$stats['total_users'] = $stmt->fetch()['total_users'];

// Nombre total de projets
$stmt = $db->query("SELECT COUNT(*) as total_projects FROM projects");
$stats['total_projects'] = $stmt->fetch()['total_projects'];

// Nombre total de likes
$stmt = $db->query("SELECT COUNT(*) as total_likes FROM project_likes");
$stats['total_likes'] = $stmt->fetch()['total_likes'];

// Nombre total de commentaires
$stmt = $db->query("SELECT COUNT(*) as total_comments FROM project_comments");
$stats['total_comments'] = $stmt->fetch()['total_comments'];

// Projets publics vs privés
$stmt = $db->query("SELECT is_public, COUNT(*) as count FROM projects GROUP BY is_public");
$public_private = $stmt->fetchAll();
$stats['public_projects'] = 0;
$stats['private_projects'] = 0;

foreach ($public_private as $row) {
    if ($row['is_public']) {
        $stats['public_projects'] = $row['count'];
    } else {
        $stats['private_projects'] = $row['count'];
    }
}

// Utilisateurs avec le plus de projets
$stmt = $db->query("
    SELECT u.username, COUNT(p.id) as project_count 
    FROM users u 
    LEFT JOIN projects p ON u.id = p.user_id 
    GROUP BY u.id 
    ORDER BY project_count DESC 
    LIMIT 10
");
$top_users = $stmt->fetchAll();

// Projets les plus populaires (plus de likes)
$stmt = $db->query("
    SELECT p.title, p.view_count, u.username,
    (SELECT COUNT(*) FROM project_likes WHERE project_id = p.id) as like_count,
    (SELECT COUNT(*) FROM project_comments WHERE project_id = p.id) as comment_count
    FROM projects p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.is_public = 1
    ORDER BY like_count DESC, view_count DESC 
    LIMIT 10
");
$popular_projects = $stmt->fetchAll();

// Évolution des inscriptions (30 derniers jours)
$stmt = $db->query("
    SELECT DATE(created_at) as date, COUNT(*) as signups 
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at) 
    ORDER BY date
");
$signups_evolution = $stmt->fetchAll();

// Projets créés par jour (30 derniers jours)
$stmt = $db->query("
    SELECT DATE(created_at) as date, COUNT(*) as projects 
    FROM projects 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at) 
    ORDER BY date
");
$projects_evolution = $stmt->fetchAll();

// Commentaires récents
$stmt = $db->query("
    SELECT c.comment, c.created_at, u.username, p.title 
    FROM project_comments c 
    JOIN users u ON c.user_id = u.id 
    JOIN projects p ON c.project_id = p.id 
    ORDER BY c.created_at DESC 
    LIMIT 10
");
$recent_comments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - 3D Scroll Animator</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--dark);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.2s, border-color 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--rose);
            font-size: 0.9rem;
        }
        
        .chart-container {
            background: var(--dark);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .table-container {
            background: var(--dark);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        .data-table th {
            color: var(--primary);
            font-weight: 600;
        }
        
        .data-table td {
            color: var(--rose);
        }
        
        .badge {
            background: var(--primary);
            color: var(--dark);
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .section-title {
            color: var(--primary);
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="index.php" class="logo">3D Scroll Animator</a>
        <nav class="nav-links">
            <a href="index.php">Éditeur</a>
            <a href="gallery.php">Galerie</a>
            <a href="dashboard.php">Mon Profil</a>
            <a href="dashboard_admin.php" class="active">Dashboard Admin</a>
        </nav>
        <div class="auth-section">
            <div class="user-menu">
                <span class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></span>
                <span class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                <a href="dashboard.php" class="btn btn-secondary">Mon Profil</a>
                <a href="?logout" class="btn btn-secondary">Déconnexion</a>
            </div>
        </div>
    </header>

    <main style="padding: 2rem; max-width: 1400px; margin: 0 auto;">
        <h1 style="text-align: center; color: var(--primary); margin-bottom: 2rem;">
            📊 Dashboard Administrateur
        </h1>

        <!-- Statistiques principales -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_users'] ?></div>
                <div class="stat-label"><i class="fas fa-users"></i> Utilisateurs</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_projects'] ?></div>
                <div class="stat-label"><i class="fas fa-cube"></i> Projets</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_likes'] ?></div>
                <div class="stat-label"><i class="fas fa-heart"></i> Likes</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_comments'] ?></div>
                <div class="stat-label"><i class="fas fa-comments"></i> Commentaires</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $stats['public_projects'] ?></div>
                <div class="stat-label"><i class="fas fa-globe"></i> Projets Publics</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $stats['private_projects'] ?></div>
                <div class="stat-label"><i class="fas fa-lock"></i> Projets Privés</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <!-- Top utilisateurs -->
            <div class="table-container">
                <h3 class="section-title"><i class="fas fa-trophy"></i> Top Créateurs</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Projets</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><span class="badge"><?= $user['project_count'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Projets populaires -->
            <div class="table-container">
                <h3 class="section-title"><i class="fas fa-fire"></i> Projets Populaires</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Projet</th>
                            <th>Auteur</th>
                            <th>❤️</th>
                            <th>💬</th>
                            <th>👁️</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($popular_projects as $project): ?>
                            <tr>
                                <td><?= htmlspecialchars($project['title']) ?></td>
                                <td><?= htmlspecialchars($project['username']) ?></td>
                                <td><?= $project['like_count'] ?></td>
                                <td><?= $project['comment_count'] ?></td>
                                <td><?= $project['view_count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Commentaires récents -->
        <div class="table-container">
            <h3 class="section-title"><i class="fas fa-comment-dots"></i> Commentaires Récents</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Projet</th>
                        <th>Commentaire</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_comments as $comment): ?>
                        <tr>
                            <td><?= htmlspecialchars($comment['username']) ?></td>
                            <td><?= htmlspecialchars($comment['title']) ?></td>
                            <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">
                                <?= htmlspecialchars($comment['comment']) ?>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($comment['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Graphiques -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <!-- Inscriptions -->
            <div class="chart-container">
                <h3 class="section-title"><i class="fas fa-chart-line"></i> Inscriptions (30 jours)</h3>
                <canvas id="signupsChart" height="200"></canvas>
            </div>

            <!-- Projets créés -->
            <div class="chart-container">
                <h3 class="section-title"><i class="fas fa-chart-bar"></i> Projets Créés (30 jours)</h3>
                <canvas id="projectsChart" height="200"></canvas>
            </div>
        </div>
    </main>

    <script>
        // Graphique des inscriptions
        const signupsCtx = document.getElementById('signupsChart').getContext('2d');
        new Chart(signupsCtx, {
            type: 'line',
            data: {
                labels: [<?= implode(',', array_map(function($item) { return "'" . date('d/m', strtotime($item['date'])) . "'"; }, $signups_evolution)) ?>],
                datasets: [{
                    label: 'Inscriptions',
                    data: [<?= implode(',', array_column($signups_evolution, 'signups')) ?>],
                    borderColor: '#cba6f7',
                    backgroundColor: 'rgba(203, 166, 247, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Graphique des projets
        const projectsCtx = document.getElementById('projectsChart').getContext('2d');
        new Chart(projectsCtx, {
            type: 'bar',
            data: {
                labels: [<?= implode(',', array_map(function($item) { return "'" . date('d/m', strtotime($item['date'])) . "'"; }, $projects_evolution)) ?>],
                datasets: [{
                    label: 'Projets',
                    data: [<?= implode(',', array_column($projects_evolution, 'projects')) ?>],
                    backgroundColor: '#ab9ff2',
                    borderColor: '#ab9ff2',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>