<?php
// transactions_dashboard.php
require_once 'config.php';
require_once 'auth.php';

// Vérifier que l'utilisateur est admin
if (!Auth::isLoggedIn() || $_SESSION['user_id'] != 1) { // Adapte la condition admin
    header('Location: index.php');
    exit;
}

// Récupérer les paramètres de recherche
$searchUser = $_GET['user'] ?? '';
$searchStatus = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Construire la requête
$db = getDB();
$whereConditions = [];
$params = [];

if (!empty($searchUser)) {
    $whereConditions[] = "(u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%$searchUser%";
    $params[] = "%$searchUser%";
}

if (!empty($searchStatus) && in_array($searchStatus, ['pending', 'completed', 'failed'])) {
    $whereConditions[] = "pt.status = ?";
    $params[] = $searchStatus;
}

if (!empty($dateFrom)) {
    $whereConditions[] = "DATE(pt.created_at) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $whereConditions[] = "DATE(pt.created_at) <= ?";
    $params[] = $dateTo;
}

$whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Requête pour les transactions
$sql = "SELECT 
            pt.*,
            u.username,
            u.email,
            u.points as user_current_points
        FROM point_transactions pt
        LEFT JOIN users u ON pt.user_id = u.id
        $whereClause
        ORDER BY pt.created_at DESC
        LIMIT $limit OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Stats globales
$statsSql = "SELECT 
    COUNT(*) as total_transactions,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
    SUM(CASE WHEN status = 'completed' THEN points_amount ELSE 0 END) as total_points_sold,
    SUM(CASE WHEN status = 'completed' THEN amount_eur ELSE 0 END) as total_revenue
FROM point_transactions";

$statsStmt = $db->prepare($statsSql);
$statsStmt->execute();
$stats = $statsStmt->fetch();

// Pagination
$countSql = "SELECT COUNT(*) as total FROM point_transactions pt
             LEFT JOIN users u ON pt.user_id = u.id $whereClause";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalCount = $countStmt->fetch()['total'];
$totalPages = ceil($totalCount / $limit);


// Données pour les 30 derniers jours
$chartSql = "SELECT 
                DATE(created_at) as date,
                SUM(amount_eur) as daily_revenue,
                COUNT(*) as daily_transactions
            FROM point_transactions 
            WHERE status = 'completed' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date";

$chartStmt = $db->prepare($chartSql);
$chartStmt->execute();
$chartData = $chartStmt->fetchAll();

// Préparer les données pour Chart.js
$labels = [];
$revenue = [];
$transactions = [];

// Remplir avec les 30 derniers jours (même les jours sans transactions)
$startDate = new DateTime('-30 days');
$endDate = new DateTime();

for ($date = $startDate; $date <= $endDate; $date->modify('+1 day')) {
    $dateStr = $date->format('Y-m-d');
    $labels[] = $date->format('d/m');

    // Trouver les données pour cette date
    $found = false;
    foreach ($chartData as $row) {
        if ($row['date'] == $dateStr) {
            $revenue[] = floatval($row['daily_revenue']);
            $transactions[] = intval($row['daily_transactions']);
            $found = true;
            break;
        }
    }

    if (!$found) {
        $revenue[] = 0;
        $transactions[] = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Transactions - 3D Scroll Animator</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard-container {
            max-width: 1120px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--grey-light);
            padding: 1.5rem;
            border-radius: 10px;
            border-left: 4px solid var(--primary);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .filters {
            background: var(--grey-light);
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text);
        }

        .transactions-table {
            background: var(--grey-light);
            border-radius: 10px;
            overflow: hidden;
        }

        .table-header {
            display: grid;
            grid-template-columns: 80px 1fr 100px 100px 100px 120px 100px;
            gap: 1rem;
            padding: 1rem;
            background: var(--primary);
            color: white;
            font-weight: bold;
        }

        .transaction-row {
            display: grid;
            grid-template-columns: 80px 1fr 100px 100px 100px 120px 100px;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid var(--grey);
            align-items: center;
        }

        .transaction-row:hover {
            background: rgba(108, 112, 134, 0.1);
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-align: center;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid var(--grey);
            border-radius: 5px;
            text-decoration: none;
            color: var(--text);
        }

        .pagination a:hover {
            background: var(--primary);
            color: white;
        }

        .pagination .current {
            background: var(--primary);
            color: white;
        }

        .export-btn {
            background: var(--success);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {

            .table-header,
            .transaction-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }

            .table-header {
                display: none;
            }

            .transaction-row::before {
                content: attr(data-label);
                font-weight: bold;
                color: var(--primary);
            }
        }
    </style>
</head>

<body>
    <?php require_once 'header.php'; ?>

    <div class="dashboard-container">
        <h1>Dashboard Transactions</h1>

        <!-- Stats Globales -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_transactions'] ?></div>
                <div class="stat-label">Transactions Total</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #60d394;">
                <div class="stat-number"><?= $stats['completed'] ?></div>
                <div class="stat-label">Complétées</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #ffd97d;">
                <div class="stat-number"><?= $stats['pending'] ?></div>
                <div class="stat-label">En Attente</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #ee6055">
                <div class="stat-number"><?= $stats['failed'] ?></div>
                <div class="stat-label">Échouées</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= number_format($stats['total_points_sold']) ?></div>
                <div class="stat-label">💎 Vendus</div>
            </div>
            <div class="stat-card" style="background-color: cornflowerblue;">
                <div class="stat-number"><?= number_format($stats['total_revenue'], 2) ?>€</div>
                <div class="stat-label">C.A</div>
            </div>
        </div>



        <!-- HTML - Même structure mais plus simple -->
        <div class="chart-card">
            <h3>C.A</h3>
            <canvas id="revenueChart" data-labels='<?= json_encode($labels) ?>'
                data-revenue='<?= json_encode($revenue) ?>'
                data-transactions='<?= json_encode($transactions) ?>'></canvas>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const canvas = document.getElementById('revenueChart');
                const ctx = canvas.getContext('2d');

                // Récupérer les données depuis les data-attributs
                const labels = JSON.parse(canvas.dataset.labels);
                const revenue = JSON.parse(canvas.dataset.revenue);
                const transactions = JSON.parse(canvas.dataset.transactions);

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Chiffre d\'Affaires (€)',
                            data: revenue,
                            borderColor: '#ab9ff2',
                            backgroundColor: 'rgba(203, 166, 247, 0.1)',
                            borderWidth: 2,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function (value) {
                                        return value + '€';
                                    }
                                }
                            }
                        }
                    }
                });
            });
        </script>

        <!-- Filtres -->
        <div class="filters">
            <form method="GET" class="filter-grid">
                <div class="filter-group">
                    <label>Recherche Utilisateur</label>
                    <input type="text" name="user" value="<?= htmlspecialchars($searchUser) ?>"
                        placeholder="Username ou email...">
                </div>
                <div class="filter-group">
                    <label>Statut</label>
                    <select name="status">
                        <option value="">Tous</option>
                        <option value="completed" <?= $searchStatus === 'completed' ? 'selected' : '' ?>>Complétées
                        </option>
                        <option value="pending" <?= $searchStatus === 'pending' ? 'selected' : '' ?>>En Attente</option>
                        <option value="failed" <?= $searchStatus === 'failed' ? 'selected' : '' ?>>Échouées</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Date Début</label>
                    <input type="date" name="date_from" value="<?= $dateFrom ?>">
                </div>
                <div class="filter-group">
                    <label>Date Fin</label>
                    <input type="date" name="date_to" value="<?= $dateTo ?>">
                </div>
                <div class="filter-group" style="justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary">Appliquer</button>
                    <a href="history.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <!-- Bouton Export -->
        <button class="export-btn" onclick="exportToCSV()">Export CSV</button>
        <a href="admin.php" class="btn btn-secondary">Dashboard</a>
        <!-- Tableau des Transactions -->
        <div class="transactions-table">
            <div class="table-header">
                <div>ID</div>
                <div>Utilisateur</div>
                <div>Points</div>
                <div>Montant</div>
                <div>Statut</div>
                <div>Date</div>
                <div>Payment ID</div>
            </div>

            <?php foreach ($transactions as $transaction): ?>
                <div class="transaction-row" data-label="Transaction #<?= $transaction['id'] ?>">
                    <div>#<?= $transaction['id'] ?></div>
                    <div>
                        <strong><?= htmlspecialchars($transaction['username'] ?? 'N/A') ?></strong><br>
                        <small><?= htmlspecialchars($transaction['email'] ?? '') ?></small><br>
                        <small>Solde: <?= $transaction['user_current_points'] ?> 💎</small>
                    </div>
                    <div><?= $transaction['points_amount'] ?> 💎</div>
                    <div><?= $transaction['amount_eur'] ?> €</div>
                    <div>
                        <span class="status-badge status-<?= $transaction['status'] ?>">
                            <?= $transaction['status'] ?>
                        </span>
                    </div>
                    <div><?= date('d/m/Y H:i', strtotime($transaction['created_at'])) ?></div>
                    <div>
                        <small style="font-family: monospace;">
                            <?= substr($transaction['payment_intent_id'] ?? 'N/A', 0, 8) ?>...
                        </small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function exportToCSV() {
            // Récupérer les paramètres actuels
            const params = new URLSearchParams(window.location.search);

            // Rediriger vers l'export
            window.location.href = 'export_transactions.php?' + params.toString();
        }
    </script>
</body>

</html>