<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$title = "Tableau de bord statistique";
require_once __DIR__.'/../includes/header.php';

// 1. Récupération des données avec gestion d'erreur
try {
    // Données pour graphiques
    $stats = [
        'contacts' => $pdo->query("
            SELECT DATE(created_at) as date, COUNT(*) as count 
            FROM contacts 
            GROUP BY DATE(created_at)
            LIMIT 7
        ")->fetchAll(),
        
        'comments' => $pdo2->query("
            SELECT u.name, COUNT(c.id) as comment_count
            FROM users u
            LEFT JOIN comments c ON c.user_id = u.id
            GROUP BY u.id
            LIMIT 10
        ")->fetchAll(),
        
        // KPIs
        'total_contacts' => $pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn(),
        'active_users' => $pdo2->query("
            SELECT COUNT(*) 
            FROM users 
            WHERE last_login > NOW() - INTERVAL 30 DAY
        ")->fetchColumn()
    ];

} catch (PDOException $e) {
    die("<div class='error'>Erreur SQL: " . $e->getMessage() . "</div>");
}
?>

<div class="content-body">
    <h2>Statistiques combinées</h2>
    
    <!-- KPIs -->
    <div class="kpi-cards">
        <div class="kpi">
            <h3>Contacts total</h3>
            <p><?= htmlspecialchars($stats['total_contacts']) ?></p>
        </div>
        <div class="kpi">
            <h3>Utilisateurs actifs</h3>
            <p><?= htmlspecialchars($stats['active_users']) ?></p>
        </div>
    </div>
    
    <!-- Graphiques -->
    <div id="chartContacts" class="chart"></div>
    <div id="chartComments" class="chart"></div>
</div>

<script>
// Données sécurisées pour JS
const statsData = <?= json_encode($stats) ?>;

// Graphique Contacts
am5.ready(function() {
    const root = am5.Root.new("chartContacts");
    root.setThemes([am5.themes.Animated.new(root)]);
    
    const chart = root.container.children.push(
        am5xy.XYChart.new(root, {
            panX: false,
            panY: false
        })
    );
    
    // Configuration des axes...
    const xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
        categoryField: "date",
        renderer: am5xy.AxisRendererX.new(root, {})
    }));
    
    const yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
        renderer: am5xy.AxisRendererY.new(root, {})
    }));
    
    const series = chart.series.push(am5xy.ColumnSeries.new(root, {
        xAxis: xAxis,
        yAxis: yAxis,
        valueYField: "count",
        categoryXField: "date"
    }));
    
    series.data.setAll(statsData.contacts);
    xAxis.data.setAll(statsData.contacts);
});

// Graphique Commentaires
am5.ready(function() {
    const root = am5.Root.new("chartComments");
    root.setThemes([am5.themes.Animated.new(root)]);
    
    const chart = root.container.children.push(
        am5percent.PieChart.new(root, {
            layout: root.verticalLayout
        })
    );
    
    const series = chart.series.push(
        am5percent.PieSeries.new(root, {
            valueField: "comment_count",
            categoryField: "name"
        })
    );
    
    series.data.setAll(statsData.comments);
});
</script>