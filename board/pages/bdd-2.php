<?php
// Debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

$title = "Base de données 2";
require_once __DIR__ . '/../includes/header.php';

// Connexion sécurisée
try {
    $config2 = require __DIR__ . '/../config2.php';
    
    $dsn = "mysql:host={$config2['host']};dbname={$config2['db']};charset={$config2['charset']}";
    $pdo2 = new PDO($dsn, $config2['user'], $config2['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Requêtes avec gestion d'erreur individuelle
    $tables = ['users', 'comments', 'likes'];
    $data = [];
    
    foreach ($tables as $table) {
        try {
            $data[$table] = $pdo2->query("SELECT * FROM $table LIMIT 50")->fetchAll();
        } catch (PDOException $e) {
            $data[$table] = ["Erreur" => $e->getMessage()];
        }
    }
    
} catch (PDOException $e) {
    die("<div class='error'>Erreur critique : " . $e->getMessage() . "</div>");
}
?>

<div class="content-body">
    <?php foreach ($data as $table => $rows): ?>
    <h2>Table <?= htmlspecialchars($table) ?></h2>
    <table>
        <?php if (isset($rows[0])): ?>
        <thead>
            <tr>
                <?php foreach (array_keys($rows[0]) as $col): ?>
                <th><?= htmlspecialchars($col) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <?php endif; ?>
        <tbody>
            <?php foreach ($rows as $row): ?>
            <tr>
                <?php foreach ($row as $val): ?>
                <td><?= is_array($val) ? '[...]' : htmlspecialchars($val) ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endforeach; ?>
</div>
<script>
    function openTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.style.display = 'none';
        });
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.getElementById(tabName).style.display = 'block';
        event.currentTarget.classList.add('active');
    }
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>