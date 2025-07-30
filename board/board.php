<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

//voir secur.md pour finir la config 
//require __DIR__.'./db_config.php'; 
//$dsn = "mysql:host={$config['host']};dbname={$config['db']};charset={$config['charset']}";
//$pdo = new PDO($dsn, $config['user'], $config['pass']);

$host = 'localhost';
$db = 'u667977963_golden_dessert';
$user = 'u667977963_berru_nico';
$pass = 'm@bddSQL25'; // 
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);// récupérer l'état 'is_read'
    $messages = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC")->fetchAll();
    $unread_count = $pdo->query("SELECT COUNT(*) FROM contacts WHERE is_read = 0")->fetchColumn();
    $total_messages = count($messages);
} catch (PDOException $e) {
    die("Erreur DB : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
    <!-- 
    ============================================
       Developed by : https://github.com/berru-g/
       Project : Interface Admin
       Version : 1.0.2  | 10/06/2025
       Licence : The MIT License (MIT)
       Copyright (c) 2025 Berru
    ============================================
-->

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | GDbdd</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../logobdd.png" />
    <link rel="apple-touch-icon" href="../logobdd.png" />
    <meta name="description" content="Tableau de bord admin pour gérer la base de données client">
    <meta name="keywords" content="admin, dashboard, gestion base de données, interface administrateur">
    <meta name="author" content="Gael Berru">
    <meta name="robots" content="noai">
    <link href="styles.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1><i class="fas fa-database"></i> GDbdd</h1>
                <p class="version">v2.0</p>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="active">
                        <a href="#"><i class="fas fa-table"></i> Tableau de données</a>
                    </li>
                    <li>
                        <a href="#"><i class="fas fa-chart-line"></i> Statistiques</a>
                    </li>
                    <li>
                        <a href="#"><i class="fas fa-users"></i> Clients</a>
                    </li>
                    <li>
                        <a href="./facture.html"><i class="fas fa-file-invoice"></i> Factures</a>
                    </li>
                    <li>
                        <a href="./php-generate-hash.php"><i class="fas fa-key"></i> Générateur</a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
                <div class="developer-info">
                    <a href="https://gael-berru.netlify.app" target="_blank">
                        <i class="fas fa-code"></i> par berru-g
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <header class="content-header">
                <div class="header-left">
                    <button id="mobileMenuBtn" class="mobile-menu-btn">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2>InterSQL</h2>
                </div>
                
                <div class="header-right">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Rechercher...">
                    </div>
                    
                    <div class="header-actions">
                        <button id="importJsonBtn" class="action-btn import" title="Importer une base de données">
                            <i class="fas fa-file-import"></i>
                            <span class="tooltip">Importer</span>
                        </button>
                        <button id="exportJsonBtn" class="action-btn export" title="Exporter la base de données">
                            <i class="fas fa-file-export"></i>
                            <span class="tooltip">Exporter</span>
                        </button>
                        
                        <div class="notification-badge" id="unreadBadge" title="Messages non lus">
                            <i class="fas fa-envelope"></i>
                            <span id="unreadCount"><?= $unread_count ?></span>
                        </div>
                    </div>
                </div>
            </header>
            
            <div class="content-body">
                <div class="data-filters">
                    <div class="filter-group">
                        <label for="statusFilter">Statut :</label>
                        <select id="statusFilter">
                            <option value="all">Tous</option>
                            <option value="new">Nouveau</option>
                            <option value="in_progress">En cours</option>
                            <option value="completed">Terminé</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="dateFilter">Date :</label>
                        <select id="dateFilter">
                            <option value="all">Toutes</option>
                            <option value="today">Aujourd'hui</option>
                            <option value="week">Cette semaine</option>
                            <option value="month">Ce mois</option>
                        </select>
                    </div>
                    
                    <button id="clearFilters" class="clear-filters">
                        <i class="fas fa-times"></i> Réinitialiser
                    </button>
                </div>
                
                <div class="table-container">
                    <table id="messagesTable">
                        <thead>
                            <tr>
                                <th data-column="0">Date <i class="fas fa-sort"></i></th>
                                <th data-column="1">Nom <i class="fas fa-sort"></i></th>
                                <th data-column="2">Mail</th>
                                <!--<th data-column="3">-</th>-->
                                <th data-column="4">Tél</th>
                                <th data-column="5">URL <i class="fas fa-sort"></i></th>
                                <th data-column="6">Message</th>
                                
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $msg): ?>
                            <tr class="message-row <?= $msg['is_read'] ? '' : 'unread' ?>" 
                                data-id="<?= $msg['id'] ?>"
                                data-read="<?= $msg['is_read'] ?>"
                                data-fullmessage="<?= htmlspecialchars($msg['message']) ?>">
                                <td><?= htmlspecialchars($msg['created_at'] ?? '') ?></td>
                                <td><?= htmlspecialchars($msg['fullname']) ?></td>
                                <td>
                                    <a href="mailto:<?= htmlspecialchars($msg['email']) ?>" class="email-link">
                                        <i class="fas fa-envelope"></i> <?= htmlspecialchars($msg['email']) ?>
                                    </a>
                                </td>
                                <!--<td>
                                    <span class="status-badge status-<?= strtolower(str_replace(' ', '_', htmlspecialchars($msg['statut']))) ?>">
                                        <?= htmlspecialchars($msg['statut']) ?>
                                    </span>
                                </td>-->
                                <td><?= htmlspecialchars($msg['telephone']) ?></td>
                                <td><?= htmlspecialchars($msg['siteweb']) ?> </td>
                                <td><?= nl2br(htmlspecialchars(substr($msg['message'], 0, 50) . (strlen($msg['message']) > 50 ? '...' : ''))) ?></td>
                                <td class="actions">
                                    <button class="view-btn" title="Voir le message complet">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="status-btn" title="Changer le statut">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="table-footer">
                    <div class="table-info">
                        Affichage de <span id="startItem">1</span> à <span id="endItem">10</span> sur <span id="totalItems"><?= count($messages) ?></span> entrées
                    </div>
                    
                    <div class="pagination" id="pagination">
                        <button id="firstBtn" class="page-btn" disabled>
                            <i class="fas fa-angle-double-left"></i>
                        </button>
                        <button id="prevBtn" class="page-btn" disabled>
                            <i class="fas fa-angle-left"></i>
                        </button>
                        <div id="pageNumbers" class="page-numbers"></div>
                        <button id="nextBtn" class="page-btn">
                            <i class="fas fa-angle-right"></i>
                        </button>
                        <button id="lastBtn" class="page-btn">
                            <i class="fas fa-angle-double-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Message Detail Modal -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Détails du message</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="message-meta">
                    <div class="meta-item">
                        <span class="meta-label">De :</span>
                        <span id="modal-name" class="meta-value"></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Email :</span>
                        <a id="modal-email" class="meta-value email-link" href="#"></a>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Téléphone :</span>
                        <span id="modal-phone" class="meta-value"></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Site :</span>
                        <span id="modal-budget" class="meta-value"></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Date :</span>
                        <span id="modal-date" class="meta-value"></span>
                    </div>
                </div>
                
                <div class="message-content">
                    <h4>Message :</h4>
                    <p id="modal-message"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn reply-btn">
                    <i class="fas fa-reply"></i> Répondre
                </button>
                <button class="btn mark-read-btn">
                    <i class="fas fa-check"></i> Marquer comme lu
                </button>
                <button class="btn close-btn">
                    <i class="fas fa-times"></i> Fermer
                </button>
            </div>
        </div>
    </div>

    <script src="script2.js"></script>
</body>
</html>
