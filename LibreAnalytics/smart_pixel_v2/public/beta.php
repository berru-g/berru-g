<?php
// 
session_start();

// --- Vérification de l'authentification ---
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (!Auth::isLoggedIn() || $_SESSION['user_email'] !== 'contact@gael-berru.com') {
    header('Location: account.php');
    exit;
}

// --- Connexion à la base de données ---
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// --- Récupération des données ---
try {
    // Données pour le graphique 4 courbes (7 derniers jours)
    $stmt = $pdo->query("
        SELECT
            DATE(CURDATE() - INTERVAL n DAY) AS date,
            (SELECT COUNT(*) FROM users WHERE DATE(created_at) <= DATE(CURDATE() - INTERVAL n DAY)) AS cumulative_users,
            (SELECT COUNT(*) FROM user_sites WHERE DATE(created_at) <= DATE(CURDATE() - INTERVAL n DAY)) AS cumulative_sites,
            (SELECT COUNT(*) FROM smart_pixel_tracking WHERE DATE(timestamp) <= DATE(CURDATE() - INTERVAL n DAY)) AS cumulative_visits,
            (SELECT COUNT(DISTINCT ip_address) FROM smart_pixel_tracking WHERE DATE(timestamp) <= DATE(CURDATE() - INTERVAL n DAY)) AS cumulative_unique_visitors
        FROM (
            SELECT 0 AS n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION
            SELECT 4 UNION SELECT 5 UNION SELECT 6
        ) AS numbers
        ORDER BY date ASC
    ");
    $historicalData = $stmt->fetchAll();

    // Top 5 des sites
    $stmt = $pdo->query("
        SELECT s.site_name, s.id, COUNT(t.id) AS total_visits
        FROM user_sites s
        LEFT JOIN smart_pixel_tracking t ON s.id = t.site_id
        GROUP BY s.id
        ORDER BY total_visits DESC
        LIMIT 5
    ");
    $topSites = $stmt->fetchAll();

    // Pays visités (top 20)
    $stmt = $pdo->query("
        SELECT country, COUNT(*) AS visits
        FROM smart_pixel_tracking
        WHERE country IS NOT NULL
        GROUP BY country
        ORDER BY visits DESC
        LIMIT 20
    ");
    $visitedCountries = $stmt->fetchAll();

    // Liste des utilisateurs
    $stmt = $pdo->query("
        SELECT
            u.id, u.email, u.created_at, u.plan, u.last_login,
            COUNT(s.id) AS site_count,
            (SELECT COUNT(*) FROM smart_pixel_tracking t WHERE t.site_id IN (SELECT id FROM user_sites WHERE user_id = u.id)) AS total_visits
        FROM users u
        LEFT JOIN user_sites s ON u.id = s.user_id
        GROUP BY u.id
        ORDER BY site_count DESC
    ");
    $usersList = $stmt->fetchAll();

    // Statistiques par plan
    $stmt = $pdo->query("SELECT plan, COUNT(*) AS count FROM users GROUP BY plan");
    $plansStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Activité récente
    $stmt = $pdo->query("
        SELECT DATE(created_at) AS date, COUNT(*) AS count
        FROM users
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $recentActivity = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur lors de la récupération des données : " . $e->getMessage());
}

// --- Export des emails ---
if (isset($_GET['export_emails'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="smartpixel_users_emails.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Email', 'Plan', 'Nombre de sites', 'Date d\'inscription', 'Dernière connexion']);
    foreach ($usersList as $user) {
        fputcsv($out, [
            $user['email'],
            strtoupper($user['plan']),
            $user['site_count'],
            $user['created_at'],
            $user['last_login'] ?? 'Jamais'
        ]);
    }
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LibreAnalytics - Administration</title>
    <!-- Police moderne et icônes pour une meilleure identité visuelle -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- CSS existant -->
    <link rel="stylesheet" href="https://gael-berru.com/LibreAnalytics/smart_pixel_v2/assets/dashboard.css">
    <!-- CDN optimisés -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/map.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/geodata/worldLow.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
    <!-- Wallet crypto : SweetAlert2 + Toastify -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <style>
        /* ===== VARIABLES & RESET ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f6f8fb;
            color: #1e293b;
            line-height: 1.5;
        }

        .main-content {
            padding: 2rem;
            max-width: 1600px;
            margin: 0 auto;
        }

        /* ===== BOUTONS NAVIGATION ===== */
        .nav-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border-radius: 12px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
        }

        .btn i {
            font-size: 1rem;
        }

        .btn-secondary {
            background: white;
            color: #475569;
            border-color: #e2e8f0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .btn-secondary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        /* ===== GRILLE STATISTIQUES ===== */
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem 1.2rem;
            border-radius: 20px;
            text-align: left;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(0, 0, 0, 0.03);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
        }

        .stat-card h3 {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-card h3 i {
            color: #6366f1;
            font-size: 1rem;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
        }

        /* ===== CARTES GÉNÉRIQUES ===== */
        .card {
            background: white;
            border-radius: 24px;
            padding: 1.8rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.02);
            border: 1px solid #f1f5f9;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-title i {
            color: #6366f1;
            background: #eef2ff;
            padding: 0.5rem;
            border-radius: 12px;
            font-size: 1rem;
        }

        /* ===== TABLEAUX ===== */
        .table-responsive {
            overflow-x: auto;
            border-radius: 18px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        .data-table th {
            text-align: left;
            padding: 1rem 1rem;
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
        }

        .data-table td {
            padding: 1rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }

        .data-table tbody tr {
            transition: background-color 0.15s;
        }

        .data-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .data-table code {
            background: #f1f5f9;
            padding: 0.2rem 0.4rem;
            border-radius: 6px;
            font-size: 0.85rem;
            color: #0f172a;
        }

        /* ===== BADGES ===== */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-free {
            background: #f1f5f9;
            color: #475569;
        }

        .badge-pro {
            background: #e0f2fe;
            color: #0369a1;
        }

        .badge-business {
            background: #fef3c7;
            color: #92400e;
        }

        /* fallback pour d'autres plans */
        .badge-premium {
            background: #f1f0ff;
            color: #4f46e5;
        }

        /* ===== BOUTON EXPORT ===== */
        .export-btn {
            background: white;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            padding: 0.6rem 1.2rem;
            border-radius: 30px;
            font-weight: 500;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
        }

        .export-btn:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
        }

        .export-btn i {
            color: #6366f1;
        }

        /* ===== CARTE MONDE ===== */
        #worldMap {
            width: 100%;
            height: 400px;
            border-radius: 18px;
            overflow: hidden;
            background: #fafcff;
        }

        /* ===== MESSAGE AUCUNE DONNÉE ===== */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #94a3b8;
            font-style: italic;
        }

        /* ==========================================
           ===== WALLET CRYPTO (fusion) =============
           Adapte le thème sombre du wallet au thème
           clair du dashboard : cartes blanches,
           accent #6366f1, badges, tables.
           ========================================== */

        .crypto-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 1.5rem;
        }

        @media (max-width: 900px) {
            .crypto-grid {
                grid-template-columns: 1fr;
            }
        }

        #portfolio-total {
            background: linear-gradient(135deg, #eef2ff, #f5f3ff);
            border: 1px solid #e0e7ff;
            border-radius: 18px;
            padding: 1.2rem 1.5rem;
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        #portfolio-total .label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
        }

        #portfolio-total h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #6366f1;
        }

        #crypto-prices {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .crypto-item {
            display: grid;
            grid-template-columns: 34px 1fr auto auto;
            align-items: center;
            gap: 12px;
            padding: 0.8rem 1rem;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 14px;
            transition: border-color 0.2s;
        }

        .crypto-item:hover {
            border-color: #c7d2fe;
        }

        .crypto-item img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
        }

        .crypto-item .name {
            display: flex;
            flex-direction: column;
        }

        .crypto-item .symbol {
            font-size: 0.95rem;
            font-weight: 600;
            color: #0f172a;
        }

        .crypto-item .price {
            font-size: 0.8rem;
            color: #64748b;
        }

        .crypto-item .holdings {
            display: flex;
            flex-direction: column;
            text-align: right;
        }

        .crypto-item .holdings .amount {
            font-size: 0.85rem;
            color: #64748b;
        }

        .crypto-item .holdings .value {
            font-size: 0.95rem;
            font-weight: 700;
            color: #6366f1;
        }

        .crypto-item .change {
            font-size: 0.9rem;
            font-weight: 600;
            min-width: 70px;
            text-align: right;
        }

        .crypto-item .change.positive { color: #3ad38b; }
        .crypto-item .change.negative { color: #f56545; }

        /* Bouton liens wallet (ex-hamburger) */
        .wallet-links-btn {
            background: white;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            padding: 0.6rem 1.2rem;
            border-radius: 30px;
            font-weight: 500;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
        }

        .wallet-links-btn:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }

        .wallet-links-btn i { color: #6366f1; }

        /* Adresses + bouton copier */
        .wallet-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.7rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .wallet-row:last-child { border-bottom: none; }

        .wallet-row .network {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #6366f1;
            background: #eef2ff;
            border-radius: 8px;
            padding: 0.25rem 0.5rem;
            min-width: 62px;
            text-align: center;
        }

        .wallet-row .address {
            flex: 1;
            font-family: monospace;
            font-size: 0.78rem;
            color: #64748b;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .copy-button {
            border: 1px solid #c7d2fe;
            background: transparent;
            color: #6366f1;
            border-radius: 8px;
            padding: 0.35rem 0.8rem;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }

        .copy-button:hover {
            background: #6366f1;
            color: white;
        }

        /* SweetAlert personnalisé (thème dashboard) */
        .custom-swal-popup {
            border-radius: 20px !important;
            font-family: 'Inter', sans-serif !important;
        }

        .custom-swal-content ul {
            list-style: none;
            padding: 0;
            text-align: left;
        }

        .custom-swal-content li {
            padding: 0.7rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .custom-swal-content li:last-child { border-bottom: none; }

        .custom-swal-content a {
            color: #6366f1;
            text-decoration: none;
            font-weight: 600;
        }

        .custom-swal-content a:hover { text-decoration: underline; }
    </style>
</head>

<body>
    <div class="main-content">
        <div class="container">
            <div class="nav-buttons">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
                <a href="../campain/rapport.php" class="btn btn-secondary">
                    <i class="fas fa-file-alt"></i> Rapport id5
                </a>
                <a href="../campain/rapport_golden.php" class="btn btn-secondary">
                    <i class="fas fa-file-alt"></i> Rapport id4
                </a>
                <a href="../campain/prospect_template.php" class="btn btn-secondary">
                    <i class="fa-regular fa-file-code"></i> Script prospection
                </a>
            </div>

            <!-- Statistiques globales -->
            <div class="admin-stats">
                <div class="stat-card">
                    <h3><i class="fas fa-users"></i> Utilisateurs</h3>
                    <div class="stat-value"><?= number_format(count($usersList)) ?></div>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-globe"></i> Sites</h3>
                    <div class="stat-value"><?= number_format(array_sum(array_column($topSites, 'total_site'))) ?></div>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-eye"></i> Visites</h3>
                    <div class="stat-value"><?= number_format(array_sum(array_column($visitedCountries, 'visits'))) ?></div>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-user-check"></i> Visiteurs uniques</h3>
                    <div class="stat-value"><?= number_format(end($historicalData)['cumulative_unique_visitors']) ?></div>
                </div>
            </div>

            <!-- ===== WALLET CRYPTO (fusion) ===== -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-wallet"></i> Portefeuille Crypto</h2>
                    <button class="wallet-links-btn" id="wallet-links-btn">
                        <i class="fas fa-link"></i> Mes liens 0x
                    </button>
                </div>
                <div class="crypto-grid">
                    <div>
                        <!-- Total + tokens injectés par le JS -->
                        <div id="crypto-wallet">
                            <div id="crypto-prices"></div>
                        </div>
                    </div>
                    <div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Réseau</th>
                                    <th>Adresse</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="wallet-row">
                                    <td><span class="network">Sol</span></td>
                                    <td><span class="address" id="sol-address">D6khWoqvc2zX46HVtSZcNrPumnPLPM72SnSuDhBrZeTC</span></td>
                                    <td><button class="copy-button" data-target="sol-address">Copier</button></td>
                                </tr>
                                <tr class="wallet-row">
                                    <td><span class="network">BTC</span></td>
                                    <td><span class="address" id="btc-address">bc1qxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx</span></td>
                                    <td><button class="copy-button" data-target="btc-address">Copier</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- ===== /WALLET CRYPTO ===== -->

            <!-- Graphique 4 courbes -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-chart-line"></i> Croissance générale</h2>
                    <span style="font-size:0.85rem; color:#64748b;">Évolution cumulative</span>
                </div>
                <canvas id="globalStatsChart" height="110" style="max-height:300px; width:100%;"></canvas>
            </div>

            <!-- Top 5 des sites + Carte -->
            <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 1.5rem; margin-bottom: 2.5rem;">
                <div class="card" style="padding: 1.5rem;">
                    <div class="card-header" style="margin-bottom: 0.5rem;">
                        <h3 class="card-title"><i class="fas fa-trophy"></i> Top 5 sites</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Visites</th>
                                    <th>ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($topSites)): ?>
                                    <?php foreach ($topSites as $site): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($site['site_name']) ?></td>
                                            <td><strong><?= number_format($site['total_visits']) ?></strong></td>
                                            <td><code><?= htmlspecialchars($site['id']) ?></code></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="empty-state">Aucune donnée</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card" style="padding: 1.5rem;">
                    <div class="card-header" style="margin-bottom: 0.5rem;">
                        <h3 class="card-title"><i class="fas fa-map-marked-alt"></i> Pays visités (Top 20)</h3>
                    </div>
                    <div id="worldMap"></div>
                </div>
            </div>

            <!-- Liste des utilisateurs -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-address-card"></i> Utilisateurs (<?= count($usersList) ?>)</h2>
                    <a href="?export_emails=1" class="export-btn">
                        <i class="fas fa-download"></i> Exporter les emails
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Plan</th>
                                <th>Sites</th>
                                <th>Visites</th>
                                <th>Inscription</th>
                                <th>Dernière connexion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($usersList)): ?>
                                <?php foreach ($usersList as $user):
                                    $planClass = 'free';
                                    if (isset($user['plan'])) {
                                        $planClass = strtolower($user['plan']);
                                    }
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><span class="badge badge-<?= $planClass ?>"><?= strtoupper($user['plan'] ?? 'free') ?></span></td>
                                        <td><?= (int)($user['site_count'] ?? 0) ?></td>
                                        <td><?= number_format($user['total_visits'] ?? 0) ?></td>
                                        <td><?= isset($user['created_at']) ? (new DateTime($user['created_at']))->format('d/m/Y') : '-' ?></td>
                                        <td><?= isset($user['last_login']) && $user['last_login'] ? (new DateTime($user['last_login']))->format('d/m/Y H:i') : '<span style="color:#94a3b8;">Jamais</span>' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-state">Aucun utilisateur enregistré</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div> <!-- .container -->
    </div> <!-- .main-content -->

    <script>
        // ===== WALLET CRYPTO =====
        const tokenHoldings = {
            bitcoin: 0,
            solana: 4.65,
            sui: 613,
        };

        function refreshCryptoPrices() {
            fetch('https://api.coingecko.com/api/v3/coins/markets?vs_currency=eur&ids=bitcoin,solana,sui')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('crypto-prices');
                    let totalPortfolioValue = 0;

                    // Total en tête de la liste
                    let totalElement = document.querySelector('#portfolio-total');
                    if (!totalElement) {
                        totalElement = document.createElement('div');
                        totalElement.id = 'portfolio-total';
                        container.prepend(totalElement);
                    }

                    data.forEach(crypto => {
                        const price = crypto.current_price;
                        const change24h = crypto.price_change_percentage_24h.toFixed(2);
                        const holdings = tokenHoldings[crypto.id] || 0;
                        const totalValue = (price * holdings).toFixed(2);
                        const imageUrl = crypto.image;

                        totalPortfolioValue += parseFloat(totalValue);

                        let cryptoElement = document.querySelector(`#${crypto.id}`);

                        if (!cryptoElement) {
                            cryptoElement = document.createElement('div');
                            cryptoElement.id = crypto.id;
                            cryptoElement.classList.add('crypto-item');
                            cryptoElement.innerHTML = `
                                <img src="${imageUrl}" alt="${crypto.id} logo">
                                <div class="name">
                                    <span class="symbol">${crypto.symbol.toUpperCase()}</span>
                                    <span class="price">${price.toLocaleString('fr-FR', {minimumFractionDigits: 2})} €</span>
                                </div>
                                <div class="holdings">
                                    <span class="amount">${holdings} ${crypto.symbol.toUpperCase()}</span>
                                    <span class="value">${totalValue} €</span>
                                </div>
                                <p class="change">${change24h}%</p>
                            `;
                            container.appendChild(cryptoElement);
                        } else {
                            cryptoElement.querySelector('.price').textContent = `${price.toLocaleString('fr-FR', {minimumFractionDigits: 2})} €`;
                            cryptoElement.querySelector('.amount').textContent = `${holdings} ${crypto.symbol.toUpperCase()}`;
                            cryptoElement.querySelector('.value').textContent = `${totalValue} €`;
                            cryptoElement.querySelector('.change').textContent = `${change24h}%`;
                        }

                        const changeElement = cryptoElement.querySelector('.change');
                        changeElement.classList.toggle('positive', change24h >= 0);
                        changeElement.classList.toggle('negative', change24h < 0);
                    });

                    totalElement.innerHTML = `<div class="label">Valeur totale (temps réel)</div><h3>${totalPortfolioValue.toFixed(2)} €</h3>`;
                })
                .catch(error => console.error('Erreur lors de la récupération des données:', error));
        }

        refreshCryptoPrices();
        // Rafraîchissement toutes les 60s (limite gratuite CoinGecko)
        setInterval(refreshCryptoPrices, 60000);

        // Liens 0x (ex-hamburger menu) via SweetAlert2
        document.getElementById('wallet-links-btn').addEventListener('click', () => {
            Swal.fire({
                title: '0x',
                html: '<ul><li><a href="https://accounts.binance.com/register?ref=">Binance</a>.com</li><li><a href="https://shop.ledger.com/?r=">Ledger</a>/live</li><li><a href="https://app.uniswap.org">Uniswap</a>.org<li><a href="#">Phantom</a>/app</li><li><a href="https://solscan.io/account/D6khWoqvc2zX46HVtSZcNrPumnPLPM72SnSuDhBrZeTC#portfolio">Solscan</a>.io</li><li><a href="https://pump.fun/profile/D6khWo">Pump</a>.fun</li><li><a href="https://jup.ag">jup</a>.ag</li></ul>',
                showCloseButton: true,
                showConfirmButton: false,
                customClass: {
                    popup: 'custom-swal-popup',
                    closeButton: 'custom-swal-close-button',
                    content: 'custom-swal-content',
                }
            });
        });

        // Copie des adresses (API Clipboard moderne + fallback)
        document.querySelectorAll('.copy-button').forEach(button => {
            button.addEventListener('click', function () {
                const address = document.getElementById(this.getAttribute('data-target')).textContent;

                const done = () => Toastify({
                    text: "✅ Adresse copiée !",
                    duration: 2000,
                    gravity: "center",
                    position: "center",
                    backgroundColor: "#6366f1",
                }).showToast();

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(address).then(done).catch(() => fallbackCopy(address, done));
                } else {
                    fallbackCopy(address, done);
                }
            });
        });

        function fallbackCopy(text, callback) {
            const tempInput = document.createElement('input');
            tempInput.value = text;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            callback();
        }

        // ===== DASHBOARD PIXEL =====
        // --- Graphique 4 courbes ---
        const globalCtx = document.getElementById('globalStatsChart').getContext('2d');
        new Chart(globalCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($historicalData, 'date')) ?>,
                datasets: [{
                        label: 'Utilisateurs',
                        data: <?= json_encode(array_column($historicalData, 'cumulative_users')) ?>,
                        borderColor: '#9d86ff',
                        backgroundColor: 'rgba(244, 63, 94, 0.05)',
                        tension: 0.2,
                        fill: true,
                        pointRadius: 2
                    },
                    {
                        label: 'Sites',
                        data: <?= json_encode(array_column($historicalData, 'cumulative_sites')) ?>,
                        borderColor: '#86baff',
                        backgroundColor: 'rgba(59, 130, 246, 0.05)',
                        tension: 0.2,
                        fill: true,
                        pointRadius: 2
                    },
                    {
                        label: 'Visites',
                        data: <?= json_encode(array_column($historicalData, 'cumulative_visits')) ?>,
                        borderColor: '#86ff94',
                        backgroundColor: 'rgba(16, 185, 129, 0.05)',
                        tension: 0.2,
                        fill: true,
                        pointRadius: 2
                    },
                    {
                        label: 'Visiteurs uniques',
                        data: <?= json_encode(array_column($historicalData, 'cumulative_unique_visitors')) ?>,
                        borderColor: '#ff9686',
                        backgroundColor: 'rgba(245, 158, 11, 0.05)',
                        tension: 0.2,
                        fill: true,
                        pointRadius: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f1f5f9'
                        },
                        title: {
                            display: true,
                            text: 'Nombre'
                        }
                    }
                }
            }
        });

        // --- Carte du monde ---
        document.addEventListener('DOMContentLoaded', function() {
            const countries = <?= json_encode($visitedCountries ?? []) ?>;
            if (!countries.length) {
                document.getElementById('worldMap').innerHTML = '<div class="empty-state">Données géographiques indisponibles</div>';
                return;
            }

            const getCountryCode = (countryName) => {
                const map = {
                    'france': 'FR',
                    'united states': 'US',
                    'usa': 'US',
                    'germany': 'DE',
                    'united kingdom': 'GB',
                    'uk': 'GB',
                    'canada': 'CA',
                    'australia': 'AU',
                    'japan': 'JP',
                    'china': 'CN',
                    'brazil': 'BR',
                    'india': 'IN',
                    'italy': 'IT',
                    'spain': 'ES',
                    'netherlands': 'NL',
                    'belgium': 'BE',
                    'switzerland': 'CH',
                    'portugal': 'PT',
                    'russia': 'RU',
                    'mexico': 'MX',
                    'south korea': 'KR',
                    'singapore': 'SG'
                };
                return map[countryName.toLowerCase().trim()] || null;
            };

            const countryData = countries.map(country => ({
                id: getCountryCode(country.country),
                value: country.visits
            })).filter(item => item.id !== null);

            if (countryData.length === 0) {
                document.getElementById('worldMap').innerHTML = '<div class="empty-state">Pays non mappés</div>';
                return;
            }

            const root = am5.Root.new("worldMap");
            root.setThemes([am5themes_Animated.new(root)]);

            const chart = root.container.children.push(
                am5map.MapChart.new(root, {
                    panX: "rotateX",
                    panY: "translateY",
                    projection: am5map.geoMercator(),
                    layout: root.horizontalLayout
                })
            );

            const polygonSeries = chart.series.push(
                am5map.MapPolygonSeries.new(root, {
                    geoJSON: am5geodata_worldLow,
                    exclude: ["AQ"]
                })
            );

            polygonSeries.mapPolygons.template.setAll({
                tooltipText: "{name}: {value} visites",
                interactive: true,
                fill: am5.color(0x9d86ff),
                stroke: am5.color(0xffffff),
                strokeWidth: 0.5
            });

            polygonSeries.data.setAll(countryData);
            polygonSeries.set("heatRules", [{
                target: polygonSeries.mapPolygons.template,
                min: am5.color(0x3333aa),
                max: am5.color(0x5f3dc4),
                dataField: "value"
            }]);

            polygonSeries.mapPolygons.template.states.create("hover", {
                fill: am5.color(0x3333aa)
            });
        });
    </script>
</body>

</html>