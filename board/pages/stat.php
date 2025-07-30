<?php
$title = "Tableau de bord statistique";
require_once __DIR__.'/../includes/_header.php';

// Récupération données BDD 1
$statsBdd1 = $pdo->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM contacts 
    GROUP BY DATE(created_at)
    LIMIT 7
")->fetchAll();

// Récupération données BDD 2
$statsBdd2 = $pdo2->query("
    SELECT u.name, COUNT(c.id) as comment_count
    FROM users u
    LEFT JOIN comments c ON c.user_id = u.id
    GROUP BY u.id
    LIMIT 10
")->fetchAll();

// Contacts par mois
$pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month, 
        COUNT(*) as count
    FROM contacts
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
");

// Statut des contacts
$pdo->query("
    SELECT 
        status,
        COUNT(*) as count
    FROM contacts
    GROUP BY status
");

// Commentaires actifs/inactifs
$pdo2->query("
    SELECT
        IF(is_active > 0, 'Actif', 'Inactif') as state,
        COUNT(*) as count
    FROM comments
    GROUP BY state
");

// Top 5 utilisateurs
$pdo2->query("
    SELECT
        u.name,
        COUNT(c.id) as comments,
        COUNT(l.id) as likes
    FROM users u
    LEFT JOIN comments c ON c.user_id = u.id
    LEFT JOIN likes l ON l.comment_id = c.id
    GROUP BY u.id
    ORDER BY comments DESC
    LIMIT 5
");
?>

<div class="content-body">
    <h2>Statistiques combinées</h2>
    
    <!-- Graphique 1: Contacts par date (BDD1) -->
    <div id="chartContacts" class="chart"></div>
    
    <!-- Graphique 2: Commentaires par utilisateur (BDD2) -->
    <div id="chartComments" class="chart"></div>
</div>

<script>
// Données PHP vers JS
const statsData = {
    contacts: <?= json_encode($statsBdd1) ?>,
    comments: <?= json_encode($statsBdd2) ?>
};

// Graphique 1
am5.ready(function() {
    let root = am5.Root.new("chartContacts");
    root.setThemes([am5themes_Animated.new(root)]);
    
    let chart = root.container.children.push(
        am5xy.XYChart.new(root, {
            panX: false,
            panY: false,
            wheelX: "none",
            wheelY: "none"
        })
    );
    
    // Axes et séries...
    let xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
        categoryField: "date",
        renderer: am5xy.AxisRendererX.new(root, {})
    }));
    
    let yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
        renderer: am5xy.AxisRendererY.new(root, {})
    }));
    
    let series = chart.series.push(am5xy.ColumnSeries.new(root, {
        name: "Contacts",
        xAxis: xAxis,
        yAxis: yAxis,
        valueYField: "count",
        categoryXField: "date"
    }));
    
    series.data.setAll(statsData.contacts);
    xAxis.data.setAll(statsData.contacts);
});

// Graphique 2 (camembert)
am5.ready(function() {
    let root = am5.Root.new("chartComments");
    root.setThemes([am5themes_Animated.new(root)]);
    
    let chart = root.container.children.push(
        am5percent.PieChart.new(root, {
            layout: root.verticalLayout
        })
    );
    
    let series = chart.series.push(
        am5percent.PieSeries.new(root, {
            name: "Commentaires",
            valueField: "comment_count",
            categoryField: "name"
        })
    );
    
    series.data.setAll(statsData.comments);
    series.appear(1000, 100);
});
</script>

<div class="kpi-cards">
    <div class="kpi">
        <h3>Contacts total</h3>
        <p><?= $pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn() ?></p>
    </div>
    <div class="kpi">
        <h3>Utilisateurs actifs</h3>
        <p><?= $pdo2->query("SELECT COUNT(*) FROM users WHERE last_login > NOW() - INTERVAL 30 DAY")->fetchColumn() ?></p>
    </div>
</div>

