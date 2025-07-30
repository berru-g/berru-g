<?php
$title = "Base de données 2";
require_once __DIR__ . '/../includes/header.php';

// Connexion à la seconde BDD
$config2 = require __DIR__ . '/../config2.php';
try {
    $pdo2 = new PDO(
        "mysql:host={$config2['host']};dbname={$config2['db']};charset={$config2['charset']}",
        $config2['user'],
        $config2['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // Récupération des données combinées
    $users = $pdo2->query("SELECT * FROM users LIMIT 50")->fetchAll();
    $comments = $pdo2->query("SELECT * FROM comments LIMIT 50")->fetchAll();
    $likes = $pdo2->query("SELECT * FROM likes LIMIT 50")->fetchAll();
} catch (PDOException $e) {
    die("Erreur BDD distante : " . $e->getMessage());
}
?>

<div class="content-body">
    <!-- Onglets -->
    <div class="tab-container">
        <button class="tab-btn active" onclick="openTab('users')">Utilisateurs</button>
        <button class="tab-btn" onclick="openTab('comments')">Commentaires</button>
        <button class="tab-btn" onclick="openTab('likes')">Likes</button>
    </div>

    <!-- Tableau Users -->
    <div id="users" class="tab-content" style="display:block;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Tableau Comments -->
    <div id="comments" class="tab-content" style="display:none;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User ID</th>
                    <th>Contenu</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comments as $comment): ?>
                    <tr>
                        <td><?= $comment['id'] ?></td>
                        <td><?= $comment['user_id'] ?></td>
                        <td><?= htmlspecialchars(substr($comment['content'], 0, 50)) ?>...</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Tableau Likes -->
    <div id="likes" class="tab-content" style="display:none;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User ID</th>
                    <th>Comment ID</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($likes as $like): ?>
                    <tr>
                        <td><?= $like['id'] ?></td>
                        <td><?= $like['user_id'] ?></td>
                        <td><?= $like['comment_id'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
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