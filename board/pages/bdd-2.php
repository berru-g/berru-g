<?php 
$title = "Base de données 2"; 
require __DIR__.'/../includes/header.php';

// Ici tu changeras pour une autre table/BDD
$other_data = $pdo->query("SELECT * FROM autre_table ORDER BY date DESC")->fetchAll();
?>

<div class="content-body">
    <div class="table-container">
        <table id="otherTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Description</th>
                    <!-- Adapte les colonnes -->
                </tr>
            </thead>
            <tbody>
                <?php foreach ($other_data as $item): ?>
                <tr>
                    <td><?= $item['id'] ?></td>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= htmlspecialchars($item['description']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__.'/../includes/footer.php'; ?>