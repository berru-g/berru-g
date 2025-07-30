<?php
// Active le debuggage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$title = "Statistiques";
require_once __DIR__.'/../includes/header.php';

// 1. Charge les configs EXISTANTES
$config1 = require __DIR__.'/../db_config.php';
$config2 = require __DIR__.'/../db2_config.php';

// 2. Connexion aux BDD (sans modifier votre structure)
try {
    // BDD 1 (principale)
    $pdo1 = new PDO(
        "mysql:host={$config1['host']};dbname={$config1['db']};charset={$config1['charset']}",
        $config1['user'],
        $config1['pass']
    );
    
    // BDD 2 (messagerie)
    $pdo2 = new PDO(
        "mysql:host={$config2['host']};dbname={$config2['db']};charset={$config2['charset']}",
        $config2['user'],
        $config2['pass']
    );

    // 3. Récupère les données
    $contacts = $pdo1->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM contacts GROUP BY date LIMIT 7")->fetchAll();
    $users = $pdo2->query("SELECT name, COUNT(comments.id) as comments FROM users LEFT JOIN comments ON users.id = comments.user_id GROUP BY users.id LIMIT 5")->fetchAll();

} catch (PDOException $e) {
    die("Erreur BDD: " . $e->getMessage());
}
?>

<!-- Intègre amCharts -->
<script src="https://cdn.amcharts.com/lib/5/index.js"></script>
<script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
<script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>

<div class="content-body">
    <h2>Statistiques</h2>

    <!-- Graphique Contacts -->
    <div id="chartContacts" style="width:100%; height:400px;"></div>
    
    <!-- Graphique Utilisateurs -->
    <div id="chartUsers" style="width:100%; height:400px;"></div>
</div>

<script>
// Données PHP -> JS
const stats = {
    contacts: <?= json_encode($contacts) ?>,
    users: <?= json_encode($users) ?>
};

// Graphique Contacts
am5.ready(function() {
    let root = am5.Root.new("chartContacts");
    root.setThemes([am5.themes.Animated.new(root)]);
    
    let chart = root.container.children.push(am5xy.XYChart.new(root, {}));
    
    // Axe X
    let xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
        categoryField: "date",
        renderer: am5xy.AxisRendererX.new(root, {})
    }));
    
    // Axe Y
    let yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
        renderer: am5xy.AxisRendererY.new(root, {})
    }));
    
    // Série
    let series = chart.series.push(am5xy.ColumnSeries.new(root, {
        xAxis: xAxis,
        yAxis: yAxis,
        valueYField: "count",
        categoryXField: "date"
    }));
    
    series.data.setAll(stats.contacts);
    xAxis.data.setAll(stats.contacts);
});

// Graphique Utilisateurs
am5.ready(function() {
    let root = am5.Root.new("chartUsers");
    root.setThemes([am5.themes.Animated.new(root)]);
    
    let chart = root.container.children.push(am5xy.XYChart.new(root, {}));
    
    // Axe X
    let xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
        categoryField: "name",
        renderer: am5xy.AxisRendererX.new(root, {})
    }));
    
    // Axe Y
    let yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
        renderer: am5xy.AxisRendererY.new(root, {})
    }));
    
    // Série
    let series = chart.series.push(am5xy.ColumnSeries.new(root, {
        xAxis: xAxis,
        yAxis: yAxis,
        valueYField: "comments",
        categoryXField: "name"
    }));
    
    series.data.setAll(stats.users);
    xAxis.data.setAll(stats.users);
});
</script>

<?php require_once __DIR__.'/../includes/footer.php'; ?>