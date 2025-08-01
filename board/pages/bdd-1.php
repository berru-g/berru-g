<?php
$title = "Base de données 1";
require_once __DIR__ . '/../includes/header.php';

try {
    $messages = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC")->fetchAll();
    $unread_count = $pdo->query("SELECT COUNT(*) FROM contacts WHERE is_read = 0")->fetchColumn();
    $total_messages = count($messages);
} catch (PDOException $e) {
    error_log('Query error: ' . $e->getMessage());
    header('Location: error.php?code=query');
    exit();
}
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<div class="content-body">
    <div class="data-filters">
        <div class="filter-group">
            <label for="statusFilter">Statut :</label>
            <select id="statusFilter">
                <option value="all">Tous</option>
                <option value="new">Nouveau</option>
                <option value="in_progress">En cours</option>
                <option value="completed">Terminé</option>
            </select>
        </div>

        <div class="filter-group">
            <label for="dateFilter">Date :</label>
            <select id="dateFilter">
                <option value="all">Toutes</option>
                <option value="today">Aujourd'hui</option>
                <option value="week">Cette semaine</option>
                <option value="month">Ce mois</option>
            </select>
        </div>

        <button id="clearFilters" class="clear-filters">
            <i class="fas fa-times"></i> Réinitialiser
        </button>
    </div>

    <div class="content-body">
        <div class="table-container">
            <table id="messagesTable">
                <thead>
                    <tr>
                        <th data-column="0">Date <i class="fas fa-sort"></i></th>
                        <th data-column="1">Nom <i class="fas fa-sort"></i></th>
                        <th data-column="2">Mail</th>
                        <!--<th data-column="3">-</th>-->
                        <th data-column="4">Tél</th>
                        <th data-column="5">URL <i class="fas fa-sort"></i></th>
                        <th data-column="6">Message</th>

                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $msg): ?>
                        <tr class="message-row <?= $msg['is_read'] ? '' : 'unread' ?>"
                            data-id="<?= $msg['id'] ?>"
                            data-read="<?= $msg['is_read'] ?>"
                            data-fullmessage="<?= htmlspecialchars($msg['message']) ?>">
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
                            <td><?= nl2br(htmlspecialchars(substr($msg['message'], 0, 50) . (strlen($msg['message']) > 50 ? '...' : ''))) ?></td>
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
    <!-- Pagination -->
    <div class="table-footer">
        <div class="table-info">
            Affichage de <span id="startItem">1</span> à <span id="endItem">10</span> sur <span
                id="totalItems"><?= count($messages) ?></span> entrées
        </div>

        <div class="pagination" id="pagination">
            <button id="firstBtn" class="page-btn" disabled>
                <i class="fas fa-angle-double-left"></i>
            </button>
            <button id="prevBtn" class="page-btn" disabled>
                <i class="fas fa-angle-left"></i>
            </button>
            <div id="pageNumbers" class="page-numbers"></div>
            <button id="nextBtn" class="page-btn">
                <i class="fas fa-angle-right"></i>
            </button>
            <button id="lastBtn" class="page-btn">
                <i class="fas fa-angle-double-right"></i>
            </button>
        </div>
    </div>

    <?php require __DIR__ . '/../includes/footer.php'; ?>