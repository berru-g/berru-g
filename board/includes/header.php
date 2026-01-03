<?php
require_once __DIR__ . '/../includes/_auth.php';


// Configuration DB unique
try {
    $dbConfig = require __DIR__ . '/../db_config.php';
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['db']};charset={$dbConfig['charset']}",
        $dbConfig['user'],
        $dbConfig['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    header('Location: /error.php?code=db');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | GDbdd</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../logobdd.png" />
    <link rel="apple-touch-icon" href="../logobdd.png" />
    <meta name="description" content="Tableau de bord admin">
    <link href="/board/assets/css/styles.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- amCharts -->
    <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
    <style>
        .chart {
            width: 100%;
            height: 400px;
            margin-bottom: 30px;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1><i class="fas fa-database"></i> Data</h1>
                <p class="version">v2.0</p>
            </div>

            <nav class="sidebar-nav">
                <ul>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'bdd-1.php' ? 'active' : '' ?>">
                        <a href="bdd-1.php"><i class="fas fa-table"></i> Bdd Golden</a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'bdd-2.php' ? 'active' : '' ?>">
                        <a href="bdd-2.php"><i class="fas fa-table"></i> Bdd Agora</a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'bdd-3.php' ? 'active' : '' ?>">
                        <a href="../../smart_phpixel/dashboard.php"><i class="fas fa-table"></i> Smart Pixel</a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'bdd-4.php' ? 'active' : '' ?>">
                        <a href="monitor_bot.php"><i class="fas fa-shield-alt"></i> Monitor_bot</a>
                    </li>
                    <li>
                        <a href="stat.php"><i class="fas fa-chart-line"></i> Statistiques</a>
                    </li>
                    <li>
                        <a href="./facture.html"><i class="fas fa-file-invoice"></i> Factures</a>
                    </li>
                    <li>
                        <a href="./php-generate-hash.php"><i class="fa-solid fa-hashtag"></i> Hash</a>
                    </li>
                </ul>
            </nav>

            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
                <div class="developer-info">
                    <a href="https://gael-berru.com" target="_blank">
                        <i class="fas fa-code"></i> par berru-g
                    </a>
                </div>
            </div>
        </aside>
        <div class="sidebar-overlay"></div>
        <!-- Main Content Area -->
        <main class="main-content">
            <header class="content-header">
                <div class="header-left">
                    <button id="mobileMenuBtn" class="mobile-menu-btn">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2>Dashboard</h2>
                </div>

                <div class="header-right">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Rechercher...">
                    </div>

                    <div class="header-actions">
                        <button id="importJsonBtn" class="action-btn import" title="Importer">
                            <i class="fas fa-file-import"></i>
                        </button>
                        <button id="exportJsonBtn" class="action-btn export" title="Exporter">
                            <i class="fas fa-file-export"></i>
                        </button>

                        <div class="notification-badge" id="unreadBadge" title="Messages non lus">
                            <i class="fas fa-envelope"></i>
                            <span id="unreadCount"><?= $unread_count ?></span>
                        </div>
                    </div>
                </div>
            </header>