<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$title = "Base de données 2 - Vue combinée";
require_once __DIR__ . '/../includes/header.php';

try {
    $config2 = require __DIR__ . '/../db2_config.php';
    $pdo2 = new PDO(
        "mysql:host={$config2['host']};dbname={$config2['db']};charset={$config2['charset']}",
        $config2['user'],
        $config2['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // Requête JOIN pour combiner les tables
    $query = "
        SELECT 
            u.id as user_id,
            u.name as user_name,
            u.email,
            c.id as comment_id,
            SUBSTRING(c.content, 1, 50) as comment_preview,
            COUNT(l.id) as likes_count
        FROM users u
        LEFT JOIN comments c ON c.user_id = u.id
        LEFT JOIN likes l ON l.comment_id = c.id
        GROUP BY u.id, c.id
        LIMIT 50
    ";

    $combinedData = $pdo2->query($query)->fetchAll();

} catch (PDOException $e) {
    die("<div class='error'>Erreur : " . $e->getMessage() . "</div>");
}
?>

<style>
.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
.data-table th, .data-table td {
    padding: 12px;
    border: 1px solid #ddd;
    text-align: left;
}
.data-table th {
    background-color: #6c5ce7;
    color: white;
}
.data-table tr:nth-child(even) {
    background-color: #f9f9f9;
}
</style>

<div class="content-body">
    <h2>Utilisateurs et commentaires</h2>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>ID User</th>
                <th>Nom</th>
                <th>Email</th>
                <th>ID Comment</th>
                <th>Commentaire</th>
                <th>Likes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($combinedData as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['user_id']) ?></td>
                <td><?= htmlspecialchars($row['user_name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= $row['comment_id'] ? htmlspecialchars($row['comment_id']) : '-' ?></td>
                <td><?= $row['comment_preview'] ? htmlspecialchars($row['comment_preview']) . '...' : '-' ?></td>
                <td><?= $row['likes_count'] ?? 0 ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>