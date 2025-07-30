<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Configuration de la base de données
$host = 'localhost';
$db = 'u667977963_golden_dessert';
$user = 'u667977963_berru_nico';
$pass = 'm@bddSQL25';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    die("Erreur DB : " . $e->getMessage());
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
    <link href="../styles.css" rel="stylesheet">
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
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'bdd-1.php' ? 'active' : '' ?>">
                        <a href="bdd-1.php"><i class="fas fa-table"></i> Base de données 1</a>
                    </li>
                    <li class="<?= basename($_SERVER['PHP_SELF']) === 'bdd-2.php' ? 'active' : '' ?>">
                        <a href="bdd-2.php"><i class="fas fa-table"></i> Base de données 2</a>
                    </li>
                    <li>
                        <a href="#"><i class="fas fa-chart-line"></i> Statistiques</a>
                    </li>
                    <li>
                        <a href="./facture.html"><i class="fas fa-file-invoice"></i> Factures</a>
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
                        <button id="importJsonBtn" class="action-btn import" title="Importer">
                            <i class="fas fa-file-import"></i>
                        </button>
                        <button id="exportJsonBtn" class="action-btn export" title="Exporter">
                            <i class="fas fa-file-export"></i>
                        </button>
                    </div>
                </div>
            </header>