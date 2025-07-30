<?php

$title = "Base de données 1";
require __DIR__ . '/../includes/header.php';
$dbConfig = require __DIR__ . '/db_config.php';
$pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['db']};charset={$dbConfig['charset']}", 
               $dbConfig['user'], $dbConfig['pass']);

// Récupérer les données spécifiques à cette base
$messages = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC")->fetchAll();
$unread_count = $pdo->query("SELECT COUNT(*) FROM contacts WHERE is_read = 0")->fetchColumn();
$total_messages = count($messages);
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);// récupérer l'état 'is_read'
    $messages = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC")->fetchAll();
    $unread_count = $pdo->query("SELECT COUNT(*) FROM contacts WHERE is_read = 0")->fetchColumn();
    $total_messages = count($messages);
} catch (PDOException $e) {
    die("Erreur DB : " . $e->getMessage());
}
?>

<div class="content-body">
    <div class="table-container">
        <table id="messagesTable">
            <!-- Table content same as before -->
            <thead>
                <tr>
                    <th data-column="0">Date <i class="fas fa-sort"></i></th>
                    <th data-column="1">Nom <i class="fas fa-sort"></i></th>
                    <th data-column="2">Mail</th>
                    <th data-column="4">Tél</th>
                    <th data-column="5">URL <i class="fas fa-sort"></i></th>
                    <th data-column="6">Message</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($messages as $msg): ?>
                    <tr class="message-row <?= $msg['is_read'] ? '' : 'unread' ?>" data-id="<?= $msg['id'] ?>"
                        data-read="<?= $msg['is_read'] ?>" data-fullmessage="<?= htmlspecialchars($msg['message']) ?>">
                        <!-- Rest of the table rows same as before -->
                        <td><?= htmlspecialchars($msg['created_at'] ?? '') ?></td>
                        <td><?= htmlspecialchars($msg['fullname']) ?></td>
                        <td>
                            <a href="mailto:<?= htmlspecialchars($msg['email']) ?>" class="email-link">
                                <i class="fas fa-envelope"></i> <?= htmlspecialchars($msg['email']) ?>
                            </a>
                        </td>
                        <!--<td>
                                    <span class="status-badge status-<?= strtolower(str_replace(' ', '_', htmlspecialchars($msg['statut']))) ?>">
                                        <?= htmlspecialchars($msg['statut']) ?>
                                    </span>
                                </td>-->
                        <td><?= htmlspecialchars($msg['telephone']) ?></td>
                        <td><?= htmlspecialchars($msg['siteweb']) ?> </td>
                        <td><?= nl2br(htmlspecialchars(substr($msg['message'], 0, 50) . (strlen($msg['message']) > 50 ? '...' : ''))) ?>
                        </td>
                        <td class="actions">
                            <button class="view-btn" title="Voir le message complet">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="status-btn" title="Changer le statut">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

 <!-- Message Detail Modal -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Détails du message</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="message-meta">
                    <div class="meta-item">
                        <span class="meta-label">De :</span>
                        <span id="modal-name" class="meta-value"></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Email :</span>
                        <a id="modal-email" class="meta-value email-link" href="#"></a>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Téléphone :</span>
                        <span id="modal-phone" class="meta-value"></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Site :</span>
                        <span id="modal-budget" class="meta-value"></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Date :</span>
                        <span id="modal-date" class="meta-value"></span>
                    </div>
                </div>
                
                <div class="message-content">
                    <h4>Message :</h4>
                    <p id="modal-message"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn reply-btn">
                    <i class="fas fa-reply"></i> Répondre
                </button>
                <button class="btn mark-read-btn">
                    <i class="fas fa-check"></i> Marquer comme lu
                </button>
                <button class="btn close-btn">
                    <i class="fas fa-times"></i> Fermer
                </button>
            </div>
        </div>
    </div>

<?php require __DIR__ . '/../includes/footer.php'; ?>