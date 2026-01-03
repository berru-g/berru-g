<?php
// ====================================================
// FICHIER : dashboard-bots.php
// DASHBOARD DE SURVEILLANCE ANTI-BOTS du form de nico
// ====================================================

$title = "Dashboard Anti-Bots";
require_once __DIR__ . '/../includes/header.php';

// Fonction pour lire les logs
function getSecurityLogs($lines = 1000) {
    $logFile = __DIR__ . '/../security.log';
    $honeypotLog = __DIR__ . '/../honeypot_caught.log';
    $ipLog = __DIR__ . '/../ip_data/';
    
    $data = [
        'logs' => [],
        'stats' => [
            'total_attacks' => 0,
            'today_attacks' => 0,
            'blocked_ips' => 0,
            'top_attack_types' => [],
            'recent_activity' => []
        ],
        'ips' => [],
        'honeypot_caught' => []
    ];
    
    // Lire le fichier security.log
    if (file_exists($logFile)) {
        $content = file_get_contents($logFile);
        $linesArray = array_reverse(explode("\n", $content));
        
        foreach ($linesArray as $line) {
            if (trim($line) && count($data['logs']) < $lines) {
                $data['logs'][] = parseLogLine($line);
            }
        }
        
        // Calculer les statistiques
        calculateStats($data);
    }
    
    // Lire les IPs bloquées
    if (is_dir($ipLog)) {
        $files = scandir($ipLog);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                $ipData = json_decode(file_get_contents($ipLog . $file), true);
                $ipHash = pathinfo($file, PATHINFO_FILENAME);
                
                if ($ipData && isset($ipData['attempts']) && $ipData['attempts'] > 2) {
                    $data['ips'][] = [
                        'hash' => $ipHash,
                        'attempts' => $ipData['attempts'],
                        'last_attempt' => date('Y-m-d H:i:s', $ipData['last_attempt'] ?? 0),
                        'last_success' => $ipData['last_success'] ?? null,
                        'blocked' => ($ipData['attempts'] >= 3)
                    ];
                    if ($ipData['attempts'] >= 3) {
                        $data['stats']['blocked_ips']++;
                    }
                }
            }
        }
    }
    
    // Lire les honeypot catches
    if (file_exists($honeypotLog)) {
        $honeypotContent = file($honeypotLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $data['honeypot_caught'] = array_slice(array_reverse($honeypotContent), 0, 50);
    }
    
    return $data;
}

// Parser une ligne de log
function parseLogLine($line) {
    preg_match('/\[(.*?)\] \| TYPE: (.*?) \| IP: (.*?) \| UA: (.*?) \| DETAILS: (.*)/', $line, $matches);
    
    if (count($matches) === 6) {
        return [
            'timestamp' => $matches[1],
            'type' => $matches[2],
            'ip' => $matches[3],
            'user_agent' => $matches[4],
            'details' => json_decode($matches[5], true) ?: $matches[5]
        ];
    }
    
    return ['raw' => $line];
}

// Calculer les statistiques
function calculateStats(&$data) {
    $today = date('Y-m-d');
    $attackTypes = [];
    
    foreach ($data['logs'] as $log) {
        $data['stats']['total_attacks']++;
        
        // Attaques aujourd'hui
        if (strpos($log['timestamp'], $today) === 0) {
            $data['stats']['today_attacks']++;
        }
        
        // Types d'attaques
        if (isset($log['type'])) {
            $attackTypes[$log['type']] = ($attackTypes[$log['type']] ?? 0) + 1;
        }
        
        // Activité récente (dernières 2 heures)
        $logTime = strtotime($log['timestamp']);
        if ($logTime > time() - 7200) {
            $data['stats']['recent_activity'][] = $log;
        }
    }
    
    // Trier les types d'attaques
    arsort($attackTypes);
    $data['stats']['top_attack_types'] = array_slice($attackTypes, 0, 5, true);
}

// Récupérer les données
$dashboardData = getSecurityLogs(500);

// Statistiques temps réel
$totalBotsBlocked = $dashboardData['stats']['total_attacks'];
$botsToday = $dashboardData['stats']['today_attacks'];
$ipsBlocked = $dashboardData['stats']['blocked_ips'];
$topAttackType = !empty($dashboardData['stats']['top_attack_types']) ? 
                  key($dashboardData['stats']['top_attack_types']) : 'Aucune';

// Activité dernière heure
$lastHourActivity = array_filter($dashboardData['stats']['recent_activity'], function($log) {
    $logTime = strtotime($log['timestamp'] ?? '');
    return $logTime > time() - 3600;
});
$activityLastHour = count($lastHourActivity);
?>

<!-- ==================================================== -->
<!-- HTML DASHBOARD -->
<!-- ==================================================== -->

<div class="content-body">
    <div class="dashboard-header">
        <h1><i class="fas fa-shield-alt"></i> Surveillance Anti-Bots</h1>
        <div class="dashboard-subtitle">
            Protection en temps réel du formulaire Golden Dessert
        </div>
    </div>
    
    <!-- ==================== KPI CARDS ==================== -->
    <div class="kpi-cards">
        <div class="kpi-card danger">
            <div class="kpi-icon">
                <i class="fas fa-robot"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-value"><?= $totalBotsBlocked ?></div>
                <div class="kpi-label">Bots bloqués (total)</div>
            </div>
            <div class="kpi-trend">
                <i class="fas fa-chart-line"></i> Aujourd'hui: <?= $botsToday ?>
            </div>
        </div>
        
        <div class="kpi-card warning">
            <div class="kpi-icon">
                <i class="fas fa-ban"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-value"><?= $ipsBlocked ?></div>
                <div class="kpi-label">IPs bloquées</div>
            </div>
            <div class="kpi-trend">
                <i class="fas fa-globe"></i> Actuellement actives
            </div>
        </div>
        
        <div class="kpi-card info">
            <div class="kpi-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-value"><?= $activityLastHour ?></div>
                <div class="kpi-label">Dernière heure</div>
            </div>
            <div class="kpi-trend">
                <i class="fas fa-bolt"></i> Activité en temps réel
            </div>
        </div>
        
        <div class="kpi-card success">
            <div class="kpi-icon">
                <i class="fas fa-bug"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-value"><?= htmlspecialchars($topAttackType) ?></div>
                <div class="kpi-label">Type d'attaque principal</div>
            </div>
            <div class="kpi-trend">
                <i class="fas fa-exclamation-triangle"></i> Vigilance accrue
            </div>
        </div>
    </div>
    
    <!-- ==================== FILTRES ==================== -->
    <div class="data-filters">
        <div class="filter-group">
            <label for="timeFilter">Période :</label>
            <select id="timeFilter">
                <option value="1">Dernière heure</option>
                <option value="24" selected>24 dernières heures</option>
                <option value="168">7 derniers jours</option>
                <option value="720">30 derniers jours</option>
                <option value="all">Tout</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="typeFilter">Type d'attaque :</label>
            <select id="typeFilter">
                <option value="all">Tous les types</option>
                <option value="BOT_DETECTED">Bot détecté</option>
                <option value="HONEYPOT_TRIGGERED">Honeypot déclenché</option>
                <option value="RATE_LIMIT">Limite dépassée</option>
                <option value="CSRF_FAILED">CSRF échoué</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="ipFilter">Adresse IP :</label>
            <input type="text" id="ipFilter" placeholder="Filtrer par IP...">
        </div>
        
        <button id="exportLogs" class="action-btn">
            <i class="fas fa-download"></i> Exporter les logs
        </button>
        
        <button id="clearOldLogs" class="action-btn danger">
            <i class="fas fa-trash"></i> Nettoyer les vieux logs
        </button>
        
        <button id="refreshDashboard" class="action-btn success">
            <i class="fas fa-sync"></i> Actualiser
        </button>
    </div>
    
    <!-- ==================== ONGLETS ==================== -->
    <div class="tabs">
        <button class="tab-btn active" data-tab="logs">Logs des attaques</button>
        <button class="tab-btn" data-tab="ips">IPs bloquées</button>
        <button class="tab-btn" data-tab="honeypot">Honeypot captures</button>
        <button class="tab-btn" data-tab="stats">Statistiques</button>
        <button class="tab-btn" data-tab="live">Monitoring temps réel</button>
    </div>
    
    <!-- ==================== ONGLET 1 : LOGS ==================== -->
    <div id="logs-tab" class="tab-content active">
        <div class="table-container">
            <table id="logsTable" class="styled-table">
                <thead>
                    <tr>
                        <th data-column="0">Date/Heure <i class="fas fa-sort"></i></th>
                        <th data-column="1">Type d'attaque <i class="fas fa-sort"></i></th>
                        <th data-column="2">Adresse IP <i class="fas fa-sort"></i></th>
                        <th data-column="3">User-Agent</th>
                        <th data-column="4">Détails</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dashboardData['logs'] as $log): ?>
                        <?php if (is_array($log) && isset($log['type'])): ?>
                        <tr class="log-row <?= $log['type'] === 'BOT_DETECTED' ? 'danger-row' : 'warning-row' ?>"
                            data-timestamp="<?= htmlspecialchars($log['timestamp']) ?>"
                            data-type="<?= htmlspecialchars($log['type']) ?>"
                            data-ip="<?= htmlspecialchars($log['ip']) ?>">
                            <td>
                                <span class="timestamp"><?= htmlspecialchars($log['timestamp']) ?></span>
                                <?php if (strpos($log['timestamp'], date('Y-m-d')) === 0): ?>
                                    <span class="badge new">Aujourd'hui</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="attack-type <?= strtolower(str_replace('_', '-', $log['type'])) ?>">
                                    <i class="fas fa-<?= getAttackIcon($log['type']) ?>"></i>
                                    <?= htmlspecialchars($log['type']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="ip-info">
                                    <span class="ip-address"><?= htmlspecialchars($log['ip']) ?></span>
                                    <?php if (shouldBlockIP($log['ip'], $dashboardData['logs'])): ?>
                                        <span class="badge blocked">BLOQUÉ</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="user-agent" title="<?= htmlspecialchars($log['user_agent']) ?>">
                                    <?= htmlspecialchars(truncate($log['user_agent'], 50)) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (is_array($log['details'])): ?>
                                    <div class="details-popup">
                                        <button class="details-btn" onclick="showDetails(this)">
                                            <i class="fas fa-info-circle"></i> Voir détails
                                        </button>
                                        <div class="details-content">
                                            <?php foreach ($log['details'] as $key => $value): ?>
                                                <div><strong><?= htmlspecialchars($key) ?>:</strong> 
                                                    <?= is_array($value) ? htmlspecialchars(json_encode($value)) : htmlspecialchars($value) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?= htmlspecialchars(truncate($log['details'], 30)) ?>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <button class="btn small-btn block-ip-btn" 
                                        data-ip="<?= htmlspecialchars($log['ip']) ?>"
                                        title="Bloquer cette IP">
                                    <i class="fas fa-ban"></i>
                                </button>
                                <button class="btn small-btn info-btn" 
                                        onclick="showIPDetails('<?= htmlspecialchars($log['ip']) ?>')"
                                        title="Infos IP">
                                    <i class="fas fa-search"></i>
                                </button>
                                <button class="btn small-btn copy-btn" 
                                        data-clipboard-text="<?= htmlspecialchars($log['ip']) ?>"
                                        title="Copier IP">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="table-footer">
            <div class="table-info">
                Affichage de <span id="logsStart">1</span> à <span id="logsEnd">50</span> 
                sur <span id="logsTotal"><?= count($dashboardData['logs']) ?></span> logs
            </div>
            <div class="pagination" id="logsPagination">
                <button id="logsFirst" class="page-btn" disabled><i class="fas fa-angle-double-left"></i></button>
                <button id="logsPrev" class="page-btn" disabled><i class="fas fa-angle-left"></i></button>
                <div id="logsPages" class="page-numbers"></div>
                <button id="logsNext" class="page-btn"><i class="fas fa-angle-right"></i></button>
                <button id="logsLast" class="page-btn"><i class="fas fa-angle-double-right"></i></button>
            </div>
        </div>
    </div>
    
    <!-- ==================== ONGLET 2 : IPs BLOQUÉES ==================== -->
    <div id="ips-tab" class="tab-content">
        <div class="table-container">
            <table id="ipsTable" class="styled-table">
                <thead>
                    <tr>
                        <th>Adresse IP</th>
                        <th>Tentatives</th>
                        <th>Dernière tentative</th>
                        <th>Dernier succès</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dashboardData['ips'] as $ipData): ?>
                        <?php 
                        // Essayer de récupérer l'IP depuis les logs
                        $ipAddress = 'HASH:' . substr($ipData['hash'], 0, 8);
                        foreach ($dashboardData['logs'] as $log) {
                            if (isset($log['ip']) && md5($log['ip']) === $ipData['hash']) {
                                $ipAddress = $log['ip'];
                                break;
                            }
                        }
                        ?>
                        <tr class="ip-row <?= $ipData['blocked'] ? 'blocked-row' : 'warning-row' ?>">
                            <td>
                                <span class="ip-address"><?= htmlspecialchars($ipAddress) ?></span>
                                <?php if ($ipData['blocked']): ?>
                                    <span class="badge blocked">BLOQUÉ</span>
                                <?php else: ?>
                                    <span class="badge warning">SURVEILLANCE</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="attempts-count <?= $ipData['attempts'] > 5 ? 'danger' : 'warning' ?>">
                                    <?= $ipData['attempts'] ?> tentatives
                                </span>
                            </td>
                            <td><?= htmlspecialchars($ipData['last_attempt']) ?></td>
                            <td><?= htmlspecialchars($ipData['last_success'] ?? 'Jamais') ?></td>
                            <td>
                                <div class="status-indicator">
                                    <span class="status-dot <?= $ipData['blocked'] ? 'status-danger' : 'status-warning' ?>"></span>
                                    <?= $ipData['blocked'] ? 'Bloqué' : 'Sous surveillance' ?>
                                </div>
                            </td>
                            <td class="actions">
                                <?php if ($ipData['blocked']): ?>
                                    <button class="btn small-btn unblock-btn" 
                                            data-ip="<?= htmlspecialchars($ipAddress) ?>"
                                            title="Débloquer cette IP">
                                        <i class="fas fa-unlock"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn small-btn block-now-btn" 
                                            data-ip="<?= htmlspecialchars($ipAddress) ?>"
                                            title="Bloquer maintenant">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                <?php endif; ?>
                                <button class="btn small-btn delete-ip-btn" 
                                        data-hash="<?= htmlspecialchars($ipData['hash']) ?>"
                                        title="Supprimer les données">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- ==================== ONGLET 3 : HONEYPOT ==================== -->
    <div id="honeypot-tab" class="tab-content">
        <div class="honeypot-container">
            <div class="honeypot-stats">
                <div class="stat-card">
                    <h3><i class="fas fa-bug"></i> Piégés aujourd'hui</h3>
                    <div class="stat-value">
                        <?= count(array_filter($dashboardData['honeypot_caught'], function($item) {
                            return strpos($item, date('Y-m-d')) === 0;
                        })) ?>
                    </div>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-history"></i> Total piégés</h3>
                    <div class="stat-value"><?= count($dashboardData['honeypot_caught']) ?></div>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-robot"></i> Dernier piège</h3>
                    <div class="stat-value">
                        <?= !empty($dashboardData['honeypot_caught']) ? 
                            htmlspecialchars(explode(' | ', $dashboardData['honeypot_caught'][0])[0] ?? 'N/A') : 
                            'Aucun' ?>
                    </div>
                </div>
            </div>
            
            <div class="honeypot-logs">
                <h3><i class="fas fa-list"></i> Dernières captures</h3>
                <div class="logs-list">
                    <?php foreach (array_slice($dashboardData['honeypot_caught'], 0, 20) as $capture): ?>
                        <div class="capture-item">
                            <div class="capture-time">
                                <i class="fas fa-clock"></i>
                                <?= htmlspecialchars(explode(' | ', $capture)[0] ?? $capture) ?>
                            </div>
                            <div class="capture-details">
                                <?= htmlspecialchars(explode(' | ', $capture)[1] ?? 'Détails non disponibles') ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($dashboardData['honeypot_caught'])): ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i>
                            <p>Aucun bot n'a été piégé récemment. Le système fonctionne !</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ==================== ONGLET 4 : STATISTIQUES ==================== -->
    <div id="stats-tab" class="tab-content">
        <div class="stats-container">
            <div class="stats-charts">
                <div class="chart-card">
                    <h3><i class="fas fa-chart-bar"></i> Attaques par type</h3>
                    <canvas id="attacksByTypeChart" width="400" height="200"></canvas>
                </div>
                <div class="chart-card">
                    <h3><i class="fas fa-chart-line"></i> Activité dernière semaine</h3>
                    <canvas id="activityChart" width="400" height="200"></canvas>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stats-card">
                    <h3><i class="fas fa-crown"></i> Top 5 IPs attaquantes</h3>
                    <ul class="top-list">
                        <?php 
                        $ipCounts = [];
                        foreach ($dashboardData['logs'] as $log) {
                            if (isset($log['ip'])) {
                                $ipCounts[$log['ip']] = ($ipCounts[$log['ip']] ?? 0) + 1;
                            }
                        }
                        arsort($ipCounts);
                        $topIPs = array_slice($ipCounts, 0, 5, true);
                        ?>
                        <?php foreach ($topIPs as $ip => $count): ?>
                            <li>
                                <span class="ip-rank"><?= htmlspecialchars($ip) ?></span>
                                <span class="ip-count"><?= $count ?> attaques</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="stats-card">
                    <h3><i class="fas fa-user-secret"></i> User-Agents suspects</h3>
                    <ul class="ua-list">
                        <?php 
                        $uaCounts = [];
                        foreach ($dashboardData['logs'] as $log) {
                            if (isset($log['user_agent'])) {
                                $uaKey = truncate($log['user_agent'], 30);
                                $uaCounts[$uaKey] = ($uaCounts[$uaKey] ?? 0) + 1;
                            }
                        }
                        arsort($uaCounts);
                        $topUAs = array_slice($uaCounts, 0, 5, true);
                        ?>
                        <?php foreach ($topUAs as $ua => $count): ?>
                            <li>
                                <span class="ua-text" title="<?= htmlspecialchars($ua) ?>">
                                    <?= htmlspecialchars($ua) ?>
                                </span>
                                <span class="ua-count"><?= $count ?> fois</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="stats-card">
                    <h3><i class="fas fa-calendar-alt"></i> Activité horaire</h3>
                    <div class="hourly-activity">
                        <?php 
                        $hourly = array_fill(0, 24, 0);
                        foreach ($dashboardData['logs'] as $log) {
                            if (isset($log['timestamp'])) {
                                $hour = (int)date('H', strtotime($log['timestamp']));
                                $hourly[$hour]++;
                            }
                        }
                        ?>
                        <?php foreach ($hourly as $hour => $count): ?>
                            <div class="hour-bar" title="<?= sprintf('%02d:00', $hour) ?> - <?= $count ?> attaques">
                                <div class="bar-label"><?= sprintf('%02dh', $hour) ?></div>
                                <div class="bar-container">
                                    <div class="bar-fill" style="height: <?= min($count * 10, 100) ?>%"></div>
                                </div>
                                <div class="bar-count"><?= $count ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ==================== ONGLET 5 : TEMPS RÉEL ==================== -->
    <div id="live-tab" class="tab-content">
        <div class="live-monitoring">
            <div class="live-header">
                <h3><i class="fas fa-broadcast-tower"></i> Monitoring Temps Réel</h3>
                <div class="live-status">
                    <span class="status-indicator active"></span>
                    <span>CONNECTÉ</span>
                    <span id="lastUpdate">Dernière mise à jour : <?= date('H:i:s') ?></span>
                </div>
            </div>
            
            <div class="live-grid">
                <div class="live-card">
                    <h4><i class="fas fa-exclamation-circle"></i> Alertes récentes</h4>
                    <div id="liveAlerts" class="alerts-container">
                        <!-- Les alertes seront injectées ici par JavaScript -->
                        <div class="alert-item info">
                            <i class="fas fa-info-circle"></i>
                            <div class="alert-content">
                                <div class="alert-title">Système démarré</div>
                                <div class="alert-time"><?= date('H:i:s') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="live-card">
                    <h4><i class="fas fa-shield-alt"></i> État de la protection</h4>
                    <div class="protection-status">
                        <div class="status-item active">
                            <i class="fas fa-check-circle"></i>
                            <span>Système anti-bot</span>
                            <span class="status-badge success">ACTIF</span>
                        </div>
                        <div class="status-item active">
                            <i class="fas fa-check-circle"></i>
                            <span>Honeypot</span>
                            <span class="status-badge success">ARMÉ</span>
                        </div>
                        <div class="status-item active">
                            <i class="fas fa-check-circle"></i>
                            <span>Rate limiting</span>
                            <span class="status-badge success">ACTIF</span>
                        </div>
                        <div class="status-item active">
                            <i class="fas fa-check-circle"></i>
                            <span>CSRF Protection</span>
                            <span class="status-badge success">ACTIF</span>
                        </div>
                    </div>
                </div>
                
                <div class="live-card wide">
                    <h4><i class="fas fa-stream"></i> Flux d'activité</h4>
                    <div id="activityStream" class="activity-stream">
                        <!-- Le flux d'activité sera injecté ici -->
                        <div class="stream-item">
                            <div class="stream-time"><?= date('H:i:s') ?></div>
                            <div class="stream-content">
                                <i class="fas fa-play-circle"></i>
                                <span>Dashboard anti-bots initialisé</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="live-controls">
                <button id="startMonitoring" class="btn success">
                    <i class="fas fa-play"></i> Démarrer le monitoring
                </button>
                <button id="stopMonitoring" class="btn danger" disabled>
                    <i class="fas fa-stop"></i> Arrêter le monitoring
                </button>
                <button id="testAttack" class="btn warning">
                    <i class="fas fa-bug"></i> Simuler une attaque
                </button>
                <button id="clearAlerts" class="btn">
                    <i class="fas fa-broom"></i> Effacer les alertes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ==================================================== -->
<!-- MODAL DÉTAILS IP -->
<!-- ==================================================== -->
<div id="ipModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Détails de l'adresse IP</h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <div id="ipDetailsContent">
                <!-- Les détails seront chargés ici -->
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn close-btn">
                <i class="fas fa-times"></i> Fermer
            </button>
        </div>
    </div>
</div>

<!-- ==================================================== -->
<!-- FONCTIONS UTILITAIRES PHP (à mettre dans un fichier séparé ou en bas) -->
<!-- ==================================================== -->
<?php
function getAttackIcon($type) {
    $icons = [
        'BOT_DETECTED' => 'robot',
        'HONEYPOT_TRIGGERED' => 'bug',
        'RATE_LIMIT' => 'tachometer-alt',
        'CSRF_FAILED' => 'shield-alt',
        'EMPTY_FIELD' => 'exclamation-triangle'
    ];
    return $icons[$type] ?? 'exclamation-circle';
}

function shouldBlockIP($ip, $logs) {
    $count = 0;
    $recentLogs = array_filter($logs, function($log) {
        return isset($log['timestamp']) && 
               strtotime($log['timestamp']) > time() - 3600; // Dernière heure
    });
    
    foreach ($recentLogs as $log) {
        if (isset($log['ip']) && $log['ip'] === $ip) {
            $count++;
        }
    }
    
    return $count >= 3; // Bloquer si 3+ tentatives en 1h
}

function truncate($string, $length) {
    if (strlen($string) <= $length) {
        return $string;
    }
    return substr($string, 0, $length) . '...';
}
?>

<!-- ==================================================== -->
<!-- JAVASCRIPT DU DASHBOARD -->
<!-- ==================================================== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des onglets
    initTabs();
    
    // Initialisation des tableaux avec tri
    initDataTables();
    
    // Initialisation des graphiques
    initCharts();
    
    // Gestion des événements
    initEventHandlers();
    
    // Monitoring temps réel (optionnel)
    initLiveMonitoring();
});

function initTabs() {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Désactiver tous les onglets
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            // Activer cet onglet
            this.classList.add('active');
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId + '-tab').classList.add('active');
        });
    });
}

function initDataTables() {
    // Table des logs
    const logsTable = document.getElementById('logsTable');
    if (logsTable) {
        const headers = logsTable.querySelectorAll('th[data-column]');
        headers.forEach(header => {
            header.addEventListener('click', function() {
                const column = parseInt(this.getAttribute('data-column'));
                sortTable(logsTable, column);
            });
        });
    }
    
    // Pagination
    initPagination('logs', 50);
}

function initCharts() {
    // Graphique des types d'attaques
    const ctx1 = document.getElementById('attacksByTypeChart')?.getContext('2d');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_keys($dashboardData['stats']['top_attack_types'])) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($dashboardData['stats']['top_attack_types'])) ?>,
                    backgroundColor: [
                        '#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    // Graphique d'activité
    const ctx2 = document.getElementById('activityChart')?.getContext('2d');
    if (ctx2) {
        // Préparer les données des 7 derniers jours
        const last7Days = [];
        for (let i = 6; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            last7Days.push(date.toISOString().split('T')[0]);
        }
        
        const dailyCounts = last7Days.map(day => {
            return countLogsForDay(day, <?= json_encode($dashboardData['logs']) ?>);
        });
        
        new Chart(ctx2, {
            type: 'line',
            data: {
                labels: last7Days.map(d => d.split('-')[2] + '/' + d.split('-')[1]),
                datasets: [{
                    label: 'Attaques par jour',
                    data: dailyCounts,
                    borderColor: '#4ecdc4',
                    backgroundColor: 'rgba(78, 205, 196, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
}

function countLogsForDay(day, logs) {
    return logs.filter(log => {
        return log.timestamp && log.timestamp.startsWith(day);
    }).length;
}

function initEventHandlers() {
    // Blocage d'IP
    document.querySelectorAll('.block-ip-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const ip = this.getAttribute('data-ip');
            if (confirm(`Bloquer l'IP ${ip} ?`)) {
                blockIP(ip);
            }
        });
    });
    
    // Actualisation
    document.getElementById('refreshDashboard')?.addEventListener('click', function() {
        location.reload();
    });
    
    // Export logs
    document.getElementById('exportLogs')?.addEventListener('click', function() {
        exportLogs();
    });
    
    // Détails IP
    window.showIPDetails = function(ip) {
        fetch(`/admin/ip-info.php?ip=${encodeURIComponent(ip)}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('ipDetailsContent').innerHTML = html;
                document.getElementById('ipModal').style.display = 'block';
            });
    };
    
    // Fermer modals
    document.querySelectorAll('.close-modal, .close-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.modal').style.display = 'none';
        });
    });
}

function initLiveMonitoring() {
    let monitoringInterval = null;
    const startBtn = document.getElementById('startMonitoring');
    const stopBtn = document.getElementById('stopMonitoring');
    
    if (startBtn) {
        startBtn.addEventListener('click', function() {
            startMonitoring();
            startBtn.disabled = true;
            stopBtn.disabled = false;
        });
    }
    
    if (stopBtn) {
        stopBtn.addEventListener('click', function() {
            stopMonitoring();
            startBtn.disabled = false;
            stopBtn.disabled = true;
        });
    }
    
    function startMonitoring() {
        monitoringInterval = setInterval(updateLiveData, 5000); // Toutes les 5 secondes
    }
    
    function stopMonitoring() {
        if (monitoringInterval) {
            clearInterval(monitoringInterval);
        }
    }
    
    function updateLiveData() {
        // Simuler des mises à jour (à remplacer par du vrai AJAX)
        const now = new Date().toLocaleTimeString();
        document.getElementById('lastUpdate').textContent = `Dernière mise à jour : ${now}`;
        
        // Ajouter une activité simulée
        const activities = [
            'Tentative de formulaire vide détectée',
            'Honeypot déclenché sur IP suspecte',
            'Requête curl bloquée',
            'Rate limit activé pour une IP',
            'Nouveau bot détecté et redirigé'
        ];
        
        const randomActivity = activities[Math.floor(Math.random() * activities.length)];
        addToActivityStream(randomActivity);
    }
    
    function addToActivityStream(activity) {
        const stream = document.getElementById('activityStream');
        const time = new Date().toLocaleTimeString();
        
        const item = document.createElement('div');
        item.className = 'stream-item';
        item.innerHTML = `
            <div class="stream-time">${time}</div>
            <div class="stream-content">
                <i class="fas fa-bolt"></i>
                <span>${activity}</span>
            </div>
        `;
        
        stream.insertBefore(item, stream.firstChild);
        
        // Limiter à 10 éléments
        if (stream.children.length > 10) {
            stream.removeChild(stream.lastChild);
        }
    }
}

function blockIP(ip) {
    fetch('/admin/block-ip.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ ip: ip })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`IP ${ip} bloquée avec succès !`);
            location.reload();
        } else {
            alert('Erreur lors du blocage de l\'IP');
        }
    });
}

function exportLogs() {
    const logs = <?= json_encode($dashboardData['logs']) ?>;
    const csv = convertToCSV(logs);
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'security-logs-' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
}

function convertToCSV(logs) {
    const headers = ['Timestamp', 'Type', 'IP', 'User-Agent', 'Details'];
    const rows = logs.map(log => [
        log.timestamp || '',
        log.type || '',
        log.ip || '',
        log.user_agent || '',
        typeof log.details === 'object' ? JSON.stringify(log.details) : log.details || ''
    ]);
    
    return [
        headers.join(','),
        ...rows.map(row => row.map(field => `"${field}"`).join(','))
    ].join('\n');
}

function sortTable(table, column) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const isAsc = table.getAttribute('data-sort-dir') !== 'asc';
    
    rows.sort((a, b) => {
        const aVal = a.children[column]?.textContent || '';
        const bVal = b.children[column]?.textContent || '';
        return isAsc ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
    });
    
    rows.forEach(row => tbody.appendChild(row));
    table.setAttribute('data-sort-dir', isAsc ? 'asc' : 'desc');
}

function initPagination(tableId, itemsPerPage) {
    // Implémentation de la pagination similaire à bdd-1.php
    const table = document.getElementById(tableId + 'Table');
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    const totalItems = rows.length;
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    let currentPage = 1;
    
    updatePagination();
    
    function updatePagination() {
        const start = (currentPage - 1) * itemsPerPage;
        const end = Math.min(start + itemsPerPage, totalItems);
        
        // Masquer toutes les lignes
        rows.forEach((row, index) => {
            row.style.display = (index >= start && index < end) ? '' : 'none';
        });
        
        // Mettre à jour les infos
        document.getElementById(tableId + 'Start').textContent = start + 1;
        document.getElementById(tableId + 'End').textContent = end;
        document.getElementById(tableId + 'Total').textContent = totalItems;
        
        // Mettre à jour les boutons de pagination
        updatePaginationButtons(tableId, currentPage, totalPages);
    }
}

function updatePaginationButtons(tableId, currentPage, totalPages) {
    const prevBtn = document.getElementById(tableId + 'Prev');
    const nextBtn = document.getElementById(tableId + 'Next');
    const firstBtn = document.getElementById(tableId + 'First');
    const lastBtn = document.getElementById(tableId + 'Last');
    
    if (prevBtn) prevBtn.disabled = currentPage === 1;
    if (nextBtn) nextBtn.disabled = currentPage === totalPages;
    if (firstBtn) firstBtn.disabled = currentPage === 1;
    if (lastBtn) lastBtn.disabled = currentPage === totalPages;
}
</script>

<!-- ==================================================== -->
<!-- STYLE CSS (à ajouter dans ton fichier style.css) -->
<!-- ==================================================== -->
<style>
.dashboard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 10px;
    margin-bottom: 2rem;
}

.dashboard-subtitle {
    opacity: 0.9;
    font-size: 1.1rem;
}

.kpi-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.kpi-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border-left: 4px solid;
}

.kpi-card.danger { border-left-color: #ff6b6b; }
.kpi-card.warning { border-left-color: #ffd166; }
.kpi-card.info { border-left-color: #118ab2; }
.kpi-card.success { border-left-color: #06d6a0; }

.kpi-icon {
    font-size: 2rem;
    margin-bottom: 1rem;
    color: #667eea;
}

.kpi-value {
    font-size: 2.5rem;
    font-weight: bold;
    line-height: 1;
}

.kpi-label {
    color: #666;
    margin-top: 0.5rem;
}

.kpi-trend {
    margin-top: 1rem;
    font-size: 0.9rem;
    color: #888;
}

.tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
    border-bottom: 2px solid #eee;
    padding-bottom: 0.5rem;
}

.tab-btn {
    padding: 0.75rem 1.5rem;
    background: #f8f9fa;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s;
}

.tab-btn.active {
    background: #667eea;
    color: white;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.danger-row { background-color: #fff5f5 !important; }
.warning-row { background-color: #fff9db !important; }
.info-row { background-color: #e7f5ff !important; }

.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
    font-size: 0.75rem;
    font-weight: bold;
    margin-left: 0.5rem;
}

.badge.new { background: #38d9a9; color: white; }
.badge.blocked { background: #ff6b6b; color: white; }
.badge.warning { background: #ffd166; color: #333; }

.attack-type {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
}

.attack-type.bot-detected { background: #ffe3e3; color: #c92a2a; }
.attack-type.honeypot-triggered { background: #fff3bf; color: #e67700; }
.attack-type.rate-limit { background: #d0ebff; color: #1864ab; }

.ip-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.details-popup {
    position: relative;
}

.details-content {
    display: none;
    position: absolute;
    background: white;
    border: 1px solid #ddd;
    padding: 1rem;
    border-radius: 5px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 100;
    min-width: 300px;
    max-width: 500px;
}

.details-btn:hover + .details-content {
    display: block;
}

.actions {
    display: flex;
    gap: 0.25rem;
}

.small-btn {
    padding: 0.25rem 0.5rem !important;
    font-size: 0.875rem !important;
}

.live-monitoring {
    background: #1a1a2e;
    color: white;
    border-radius: 10px;
    padding: 1.5rem;
}

.live-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.live-status {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.status-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #38d9a9;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.live-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.live-card {
    background: rgba(255,255,255,0.1);
    border-radius: 8px;
    padding: 1rem;
}

.live-card.wide {
    grid-column: 1 / -1;
}

.alerts-container {
    max-height: 300px;
    overflow-y: auto;
}

.alert-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    border-radius: 5px;
    margin-bottom: 0.5rem;
    background: rgba(255,255,255,0.05);
}

.alert-item.info { border-left: 3px solid #4dabf7; }
.alert-item.warning { border-left: 3px solid #ffd43b; }
.alert-item.danger { border-left: 3px solid #ff6b6b; }

.protection-status {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.status-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem;
    background: rgba(255,255,255,0.05);
    border-radius: 5px;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: bold;
}

.status-badge.success { background: #38d9a9; color: #333; }

.activity-stream {
    max-height: 300px;
    overflow-y: auto;
}

.stream-item {
    display: flex;
    gap: 1rem;
    padding: 0.75rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.stream-time {
    color: #aaa;
    min-width: 70px;
}

.live-controls {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.stats-container {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.stats-charts {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

.chart-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.stats-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.top-list, .ua-list {
    list-style: none;
    padding: 0;
}

.top-list li, .ua-list li {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
}

.hourly-activity {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    height: 150px;
}

.hour-bar {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
}

.bar-container {
    width: 20px;
    height: 100px;
    background: #eee;
    border-radius: 3px;
    margin: 0.5rem 0;
    position: relative;
}

.bar-fill {
    position: absolute;
    bottom: 0;
    width: 100%;
    background: #667eea;
    border-radius: 3px;
    transition: height 0.3s;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #666;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #38d9a9;
}

.action-btn {
    padding: 0.5rem 1rem;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.action-btn.danger { background: #ff6b6b; }
.action-btn.success { background: #38d9a9; }
</style>

<?php require __DIR__ . '/../includes/footer.php'; ?>