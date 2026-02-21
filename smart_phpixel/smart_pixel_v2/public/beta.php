<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Vérifie si connecté et si admin
if (!Auth::isLoggedIn() || $_SESSION['user_email'] !== 'contact@gael-berru.com') {
    header('Location: index.php');
    exit;
}

$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);

// Nombre total de users
$stmt = $pdo->query("SELECT COUNT(*) AS total_users FROM users");
$totalUsers = $stmt->fetch()['total_users'];

// Nombre total de sites
$stmt = $pdo->query("SELECT COUNT(*) AS total_sites FROM user_sites");
$totalSites = $stmt->fetch()['total_sites'];

// Nombre total de visiteurs (tous sites confondus)
$stmt = $pdo->query("SELECT COUNT(*) AS total_visits FROM smart_pixel_tracking");
$totalVisits = $stmt->fetch()['total_visits'];

// Nombre total de visiteurs uniques (tous sites confondus)
$stmt = $pdo->query("SELECT COUNT(DISTINCT ip_address) AS total_unique_visitors FROM smart_pixel_tracking");
$totalUniqueVisitors = $stmt->fetch()['total_unique_visitors'];

// Top 5 des sites les plus visités
$stmt = $pdo->query("
    SELECT s.site_name, s.id, COUNT(t.id) AS total_visits
    FROM user_sites s
    LEFT JOIN smart_pixel_tracking t ON s.id = t.site_id
    GROUP BY s.id
    ORDER BY total_visits DESC
    LIMIT 5
");
$topSites = $stmt->fetchAll();

// Liste des pays visités (pour la carte)
$stmt = $pdo->query("SELECT DISTINCT country FROM smart_pixel_tracking WHERE country IS NOT NULL");
$visitedCountries = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Liste des utilisateurs (classés par activité ou date)
$stmt = $pdo->query("
    SELECT u.id, u.email, u.created_at, COUNT(s.id) AS site_count
    FROM users u
    LEFT JOIN user_sites s ON u.id = s.user_id
    GROUP BY u.id
    ORDER BY site_count DESC
");
$usersList = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Pixel - Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/map.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/geodata/worldLow.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/gantt.js"></script>
</head>
<body>
    <div class="main-content">
        <header>
            <div class="container">
                <div class="header-content">
                    <h1>Smart Pixel - Admin Dashboard</h1>
                </div>
            </div>
        </header>

        <div class="container">
            <!-- Graphique global -->
            <div class="chart-container">
                <h3 class="chart-title">Statistiques globales</h3>
                <canvas id="globalStatsChart" height="80"></canvas>
            </div>

            <!-- Top 5 des sites -->
            <div class="card">
                <h3 class="card-title">Top 5 des sites les plus visités</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Site</th>
                            <th>Visites</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topSites as $site): ?>
                            <tr>
                                <td><?= htmlspecialchars($site['site_name']) ?></td>
                                <td><?= $site['total_visits'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Carte des pays visités -->
            <div class="card">
                <h3 class="card-title">Pays visités</h3>
                <div id="worldMap" style="width: 100%; height: 400px;"></div>
            </div>

            <!-- Liste des utilisateurs -->
            <div class="card">
                <h3 class="card-title">Liste des utilisateurs</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Date d'inscription</th>
                            <th>Nombre de sites</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usersList as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= $user['created_at'] ?></td>
                                <td><?= $user['site_count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Graphique global
        const ctx = document.getElementById('globalStatsChart').getContext('2d');
        const globalStatsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Total'],
                datasets: [
                    { label: 'Users', data: [<?= $totalUsers ?>], borderColor: 'red', fill: false },
                    { label: 'Sites', data: [<?= $totalSites ?>], borderColor: 'blue', fill: false },
                    { label: 'Visiteurs', data: [<?= $totalVisits ?>], borderColor: 'green', fill: false },
                    { label: 'Visiteurs uniques', data: [<?= $totalUniqueVisitors ?>], borderColor: 'orange', fill: false }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { mode: 'index', intersect: false }
                }
            }
        });

        // Carte du monde
        document.addEventListener('DOMContentLoaded', function() {
            const countries = <?= json_encode($visitedCountries) ?>;
            const countryData = countries.map(country => ({
                id: getCountryCode(country),
                value: 1 // Valeur arbitraire pour l'affichage
            })).filter(item => item.id !== null);

            const root = am5.Root.new("worldMap");
            const chart = root.container.children.push(
                am5map.MapChart.new(root, {
                    panX: "translateX",
                    panY: "translateY",
                    projection: am5map.geoNaturalEarth1()
                })
            );

            const polygonSeries = chart.series.push(
                am5map.MapPolygonSeries.new(root, {
                    geoJSON: am5geodata_worldLow,
                    exclude: ["AQ"]
                })
            );

            polygonSeries.mapPolygons.template.setAll({
                tooltipText: "{name}",
                fill: am5.color(0x000000),
                stroke: am5.color(0xffffff),
                strokeWidth: 0.5
            });

            polygonSeries.mapPolygons.template.states.create("hover", {
                fill: am5.color(0x3a92ff)
            });

            polygonSeries.data.setAll(countryData);

            // Fonction pour convertir le nom du pays en code ISO
            function getCountryCode(countryName) {
                const countryMap = {
                    'france': 'FR', 'united states': 'US', 'germany': 'DE', 'united kingdom': 'GB',
                    'canada': 'CA', 'australia': 'AU', 'japan': 'JP', 'china': 'CN',
                    'brazil': 'BR', 'india': 'IN', 'italy': 'IT', 'spain': 'ES',
                    'netherlands': 'NL', 'belgium': 'BE', 'switzerland': 'CH',
                    'portugal': 'PT', 'russia': 'RU', 'mexico': 'MX',
                    'south korea': 'KR', 'singapore': 'SG', 'usa': 'US', 'uk': 'GB'
                };
                const normalized = countryName.toLowerCase().trim();
                return countryMap[normalized] || null;
            }
        });
    </script>
</body>
</html>
