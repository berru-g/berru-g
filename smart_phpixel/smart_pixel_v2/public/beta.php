<?php
// 
session_start();

// --- Vérification de l'authentification ---
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (!Auth::isLoggedIn() || $_SESSION['user_email'] !== 'contact@gael-berru.com') {
    header('Location: login.php');
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

    // Activité récente (7 jours)
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

// --- Gestion des leads ---
// Ajouter un lead (formulaire ou manuellement)
if (isset($_POST['add_lead'])) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO leads (company_name, email, sector, website, status, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['company_name'],
            $_POST['email'],
            $_POST['sector'],
            $_POST['website'],
            $_POST['status'] ?? 'à faire',
            $_POST['notes'] ?? ''
        ]);
        header("Location: " . $_SERVER['PHP_SELF'] . "?lead_added=1");
        exit;
    } catch (PDOException $e) {
        $error = "Erreur lors de l'ajout du lead : " . $e->getMessage();
    }
}

// Mettre à jour le statut d'un lead
if (isset($_POST['update_status'])) {
    try {
        $stmt = $pdo->prepare("
            UPDATE leads SET status = ?, notes = ? WHERE id = ?
        ");
        $stmt->execute([
            $_POST['status'],
            $_POST['notes'],
            $_POST['lead_id']
        ]);
        header("Location: " . $_SERVER['PHP_SELF'] . "?status_updated=1");
        exit;
    } catch (PDOException $e) {
        $error = "Erreur lors de la mise à jour : " . $e->getMessage();
    }
}

// Supprimer un lead
if (isset($_GET['delete_lead'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM leads WHERE id = ?");
        $stmt->execute([$_GET['delete_lead']]);
        header("Location: " . $_SERVER['PHP_SELF'] . "?lead_deleted=1");
        exit;
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Récupérer tous les leads
$stmt = $pdo->query("SELECT * FROM leads ORDER BY
    CASE status
        WHEN 'à faire' THEN 1
        WHEN 'envoyé' THEN 2
        WHEN 'répondu' THEN 3
        WHEN 'relancé' THEN 4
        WHEN 'client' THEN 5
    END, updated_at DESC");
$leads = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Pixel - Beta Admin</title>
    <link rel="stylesheet" href="../assets/dashboard.css">
    <!-- CDN optimisés -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/map.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/geodata/worldLow.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
    <style>
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-color);
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0.5rem 0;
        }

        .export-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
        }

        #worldMap {
            width: 100%;
            height: 400px;
            margin: 1rem 0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }

        .data-table th,
        .data-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .badge-free {
            background: #f0f0f0;
            color: #333;
        }

        .badge-pro {
            background: #4ecdc4;
            color: white;
        }

        .badge-business {
            background: #ff6b6b;
            color: white;
        }

        /* Badges pour les statuts des leads */
        .badge-warning {
            background: #ffc107;
            color: #212529;
        }

        .badge-info {
            background: #17a2b8;
            color: white;
        }

        .badge-success {
            background: #28a745;
            color: white;
        }

        .badge-secondary {
            background: #6c757d;
            color: white;
        }

        .badge-primary {
            background: #007bff;
            color: white;
        }

        .form-input {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
    </style>
</head>

<body>
    <div class="main-content">
        <!-- Ajoute ce bouton dans la section des leads 
         auto via cron job pour lancer la requete auto le lundi matin : 
         "0 9 * * 1 /usr/bin/php /chemin/vers/ton/dossier/agent_leads.php > /dev/null 2>&1
"-->
        <div style="margin-bottom: 1rem;">
            <form action="agent_leads.php" method="POST" target="_blank">
                <button type="submit" class="export-btn" style="background: #9d86ff;">
                    🤖 Lancer l'agent de recherche de leads
                </button>
            </form>
            <p style="font-size: 0.9rem; color: #6c757d; margin-top: 0.5rem;">
                L'agent va rechercher des agences, devs et PME françaises utilisant GA et te les envoyer par email.
                <a href="dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Retour au dashboard
        </a>
            </p>
        </div>


        <div class="container">


            <!-- Statistiques globales -->
            <div class="admin-stats">
                <div class="stat-card">
                    <h3>Utilisateurs</h3>
                    <div class="stat-value"><?= count($usersList) ?></div>
                </div>
                <div class="stat-card">
                    <h3>Sites</h3>
                    <div class="stat-value"><?= array_sum(array_column($topSites, 'total_site')) ?></div>
                </div>
                <div class="stat-card">
                    <h3>Visiteurs</h3>
                    <div class="stat-value"><?= array_sum(array_column($visitedCountries, 'visits')) ?></div>
                </div>
                <div class="stat-card">
                    <h3>Visiteurs uniques</h3>
                    <div class="stat-value"><?= end($historicalData)['cumulative_unique_visitors'] ?></div>
                </div>
            </div>

            <!-- Graphique 4 courbes -->
            <div class="card">
                <h3 class="card-title">Statistiques globales </h3>
                <canvas id="globalStatsChart" height="100"></canvas>
            </div>

            <!-- Top 5 des sites -->
            <div class="card">
                <h3 class="card-title">Top 5 des sites</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Site</th>
                            <th>Visites</th>
                            <th>ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topSites as $site): ?>
                            <tr>
                                <td><?= htmlspecialchars($site['site_name']) ?></td>
                                <td><?= $site['total_visits'] ?></td>
                                <td><code><?= $site['id'] ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Carte des pays -->
            <div class="card">
                <h3 class="card-title">Pays visités (top 20)</h3>
                <div id="worldMap"></div>
            </div>

            <!-- Liste des utilisateurs -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 class="card-title">Utilisateurs (<?= count($usersList) ?>)</h3>
                    <a href="?export_emails=1" class="export-btn">Exporter les emails</a>
                </div>
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
                        <?php foreach ($usersList as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><span class="badge badge-<?= $user['plan'] ?>"><?= strtoupper($user['plan']) ?></span></td>
                                <td><?= $user['site_count'] ?></td>
                                <td><?= $user['total_visits'] ?></td>
                                <td><?= (new DateTime($user['created_at']))->format('d/m/Y') ?></td>
                                <td><?= $user['last_login'] ? (new DateTime($user['last_login']))->format('d/m/Y H:i') : 'Jamais' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tableau de suivi des leads -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 class="card-title">Suivi des Leads (<?= count($leads) ?>)</h3>
                <button class="export-btn" onclick="document.getElementById('addLeadForm').style.display='block'">+ Ajouter un lead</button>
            </div>

            <!-- Formulaire d'ajout de lead (caché par défaut) -->
            <div id="addLeadForm" style="display: none; background: var(--bg-color); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                <form method="POST">
                    <input type="hidden" name="add_lead" value="1">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <label>Nom de l'entreprise</label>
                            <input type="text" name="company_name" required class="form-input" style="width: 100%;">
                        </div>
                        <div>
                            <label>Email</label>
                            <input type="email" name="email" required class="form-input" style="width: 100%;">
                        </div>
                        <div>
                            <label>Secteur</label>
                            <input type="text" name="sector" class="form-input" style="width: 100%;">
                        </div>
                        <div>
                            <label>Site web</label>
                            <input type="url" name="website" class="form-input" style="width: 100%;">
                        </div>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label>Notes</label>
                        <textarea name="notes" class="form-input" style="width: 100%; min-height: 60px;"></textarea>
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="export-btn">Ajouter</button>
                        <button type="button" class="export-btn" style="background: #6c757d;" onclick="document.getElementById('addLeadForm').style.display='none'">Annuler</button>
                    </div>
                </form>
            </div>

            <!-- Tableau des leads -->
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Entreprise</th>
                            <th>Email</th>
                            <th>Secteur</th>
                            <th>Site</th>
                            <th>Statut</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leads as $lead): ?>
                            <tr>
                                <td><?= htmlspecialchars($lead['company_name']) ?></td>
                                <td><a href="mailto:<?= htmlspecialchars($lead['email']) ?>"><?= htmlspecialchars($lead['email']) ?></a></td>
                                <td><?= htmlspecialchars($lead['sector'] ?? '') ?></td>
                                <td><a href="<?= htmlspecialchars($lead['website']) ?>" target="_blank"><?= parse_url($lead['website'], PHP_URL_HOST) ?? '' ?></a></td>
                                <td>
                                    <span class="badge
                            <?= $lead['status'] === 'à faire' ? 'badge-warning' : ($lead['status'] === 'envoyé' ? 'badge-info' : ($lead['status'] === 'répondu' ? 'badge-success' : ($lead['status'] === 'relancé' ? 'badge-secondary' : 'badge-primary'))) ?>">
                                        <?= ucfirst($lead['status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars(substr($lead['notes'], 0, 30)) ?></td>
                                <td>
                                    <button class="export-btn" style="background: #6c757d; font-size: 0.8rem; padding: 0.3rem 0.5rem;"
                                        onclick="document.getElementById('editLead_<?= $lead['id'] ?>').style.display='block'">
                                        Éditer
                                    </button>
                                    <a href="?delete_lead=<?= $lead['id'] ?>" class="export-btn" style="background: #dc3545; font-size: 0.8rem; padding: 0.3rem 0.5rem;"
                                        onclick="return confirm('Supprimer ce lead ?')">
                                        Supprimer
                                    </a>
                                </td>
                            </tr>

                            <!-- Formulaire d'édition (caché) -->
                            <tr>
                                <td colspan="7" style="padding: 0;">
                                    <div id="editLead_<?= $lead['id'] ?>" style="display: none; background: var(--bg-color); padding: 1rem; border-radius: 8px; margin: 0.5rem 0;">
                                        <form method="POST">
                                            <input type="hidden" name="update_status" value="1">
                                            <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
                                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                                <div>
                                                    <label>Statut</label>
                                                    <select name="status" class="form-input" style="width: 100%;">
                                                        <option value="à faire" <?= $lead['status'] === 'à faire' ? 'selected' : '' ?>>À faire</option>
                                                        <option value="envoyé" <?= $lead['status'] === 'envoyé' ? 'selected' : '' ?>>Envoyé</option>
                                                        <option value="répondu" <?= $lead['status'] === 'répondu' ? 'selected' : '' ?>>Répondu</option>
                                                        <option value="relancé" <?= $lead['status'] === 'relancé' ? 'selected' : '' ?>>Relancé</option>
                                                        <option value="client" <?= $lead['status'] === 'client' ? 'selected' : '' ?>>Client</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label>Notes</label>
                                                    <textarea name="notes" class="form-input" style="width: 100%; min-height: 60px;"><?= htmlspecialchars($lead['notes']) ?></textarea>
                                                </div>
                                            </div>
                                            <div style="display: flex; gap: 1rem;">
                                                <button type="submit" class="export-btn">Mettre à jour</button>
                                                <button type="button" class="export-btn" style="background: #6c757d;"
                                                    onclick="document.getElementById('editLead_<?= $lead['id'] ?>').style.display='none'">
                                                    Annuler
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>



    </div>

    <script>
        // --- Graphique 4 courbes ---
        const globalCtx = document.getElementById('globalStatsChart').getContext('2d');
        new Chart(globalCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($historicalData, 'date')) ?>,
                datasets: [{
                        label: 'Utilisateurs',
                        data: <?= json_encode(array_column($historicalData, 'cumulative_users')) ?>,
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        tension: 0.1,
                        fill: true
                    },
                    {
                        label: 'Sites',
                        data: <?= json_encode(array_column($historicalData, 'cumulative_sites')) ?>,
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        tension: 0.1,
                        fill: true
                    },
                    {
                        label: 'Visiteurs',
                        data: <?= json_encode(array_column($historicalData, 'cumulative_visits')) ?>,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1,
                        fill: true
                    },
                    {
                        label: 'Visiteurs uniques',
                        data: <?= json_encode(array_column($historicalData, 'cumulative_unique_visitors')) ?>,
                        borderColor: 'rgb(255, 205, 86)',
                        backgroundColor: 'rgba(255, 205, 86, 0.2)',
                        tension: 0.1,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
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
            const countries = <?= json_encode($visitedCountries) ?>;
            const countryData = countries.map(country => ({
                id: getCountryCode(country.country),
                value: country.visits
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
                tooltipText: "{name}: {value} visites",
                fill: am5.color(0x9d86ff),
                stroke: am5.color(0xffffff),
                strokeWidth: 0.5
            });

            polygonSeries.data.setAll(countryData);
            polygonSeries.set("heatRules", [{
                target: polygonSeries.mapPolygons.template,
                min: am5.color(0x4ecdc4),
                max: am5.color(0xffffff),
                dataField: "value"
            }]);

            function getCountryCode(countryName) {
                const countryMap = {
                    'france': 'FR',
                    'united states': 'US',
                    'germany': 'DE',
                    'united kingdom': 'GB',
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
                    'singapore': 'SG',
                    'usa': 'US',
                    'uk': 'GB'
                };
                return countryMap[countryName.toLowerCase().trim()] || null;
            }
        });
    </script>
</body>

</html>