<?php
// ====================================================
// FICHIER : ip-info.php
// INFORMATIONS DÉTAILLÉES SUR UNE IP
// ====================================================

session_start();

// Vérifier les permissions
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    die('Accès interdit');
}

// Récupérer l'IP
$ip = $_GET['ip'] ?? '';

if (empty($ip)) {
    die('<div class="alert danger">IP manquante</div>');
}

// Valider l'IP
if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    die('<div class="alert danger">IP invalide</div>');
}

// Fonction pour récupérer les infos IP
function getIPInfo($ip) {
    $info = [
        'ip' => $ip,
        'is_private' => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false,
        'geo' => [],
        'threat' => [],
        'logs' => [],
        'stats' => []
    ];
    
    // ==================== 1. GÉOLOCALISATION ====================
    try {
        $geoData = @json_decode(file_get_contents("http://ip-api.com/json/$ip"), true);
        if ($geoData && $geoData['status'] === 'success') {
            $info['geo'] = [
                'country' => $geoData['country'] ?? 'Inconnu',
                'country_code' => $geoData['countryCode'] ?? '',
                'region' => $geoData['regionName'] ?? '',
                'city' => $geoData['city'] ?? '',
                'zip' => $geoData['zip'] ?? '',
                'lat' => $geoData['lat'] ?? 0,
                'lon' => $geoData['lon'] ?? 0,
                'timezone' => $geoData['timezone'] ?? '',
                'isp' => $geoData['isp'] ?? 'Inconnu',
                'org' => $geoData['org'] ?? '',
                'as' => $geoData['as'] ?? ''
            ];
        }
    } catch (Exception $e) {
        // Silencieux en cas d'erreur
    }
    
    // ==================== 2. CHECK LISTES NOIRES ====================
    $blacklists = [
        'Spamhaus' => "zen.spamhaus.org",
        'Barracuda' => "b.barracudacentral.org",
        'Sorbs' => "dnsbl.sorbs.net"
    ];
    
    $info['threat']['dnsbl'] = [];
    $reverseIP = implode('.', array_reverse(explode('.', $ip)));
    
    foreach ($blacklists as $name => $dnsbl) {
        $host = "$reverseIP.$dnsbl";
        $result = gethostbyname($host);
        $info['threat']['dnsbl'][$name] = ($result !== $host);
    }
    
    // ==================== 3. ANALYSE DES LOGS ====================
    $logFile = __DIR__ . '/security.log';
    if (file_exists($logFile)) {
        $logs = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $ipLogs = [];
        $firstSeen = null;
        $lastSeen = null;
        $attackCount = 0;
        $attackTypes = [];
        
        foreach ($logs as $log) {
            if (strpos($log, "IP: $ip") !== false) {
                preg_match('/\[(.*?)\] \| TYPE: (.*?) \|/', $log, $matches);
                if (count($matches) >= 3) {
                    $timestamp = $matches[1];
                    $type = $matches[2];
                    
                    $ipLogs[] = [
                        'timestamp' => $timestamp,
                        'type' => $type,
                        'raw' => $log
                    ];
                    
                    // Première vue
                    if (!$firstSeen || strtotime($timestamp) < strtotime($firstSeen)) {
                        $firstSeen = $timestamp;
                    }
                    
                    // Dernière vue
                    if (!$lastSeen || strtotime($timestamp) > strtotime($lastSeen)) {
                        $lastSeen = $timestamp;
                    }
                    
                    // Comptage
                    $attackCount++;
                    $attackTypes[$type] = ($attackTypes[$type] ?? 0) + 1;
                }
            }
        }
        
        $info['logs'] = array_slice(array_reverse($ipLogs), 0, 10); // 10 derniers logs
        $info['stats'] = [
            'first_seen' => $firstSeen,
            'last_seen' => $lastSeen,
            'total_attacks' => $attackCount,
            'attack_types' => $attackTypes,
            'logs_count' => count($ipLogs)
        ];
    }
    
    // ==================== 4. INFOS LOCALES ====================
    $ipFile = __DIR__ . '/ip_data/' . md5($ip) . '.json';
    if (file_exists($ipFile)) {
        $localData = json_decode(file_get_contents($ipFile), true) ?: [];
        $info['local'] = $localData;
    }
    
    // ==================== 5. CHECK TOR ====================
    $info['threat']['is_tor'] = false;
    $torExitNodes = @file_get_contents('https://check.torproject.org/torbulkexitlist');
    if ($torExitNodes && strpos($torExitNodes, $ip) !== false) {
        $info['threat']['is_tor'] = true;
    }
    
    return $info;
}

// Récupérer les informations
$ipInfo = getIPInfo($ip);
?>

<!-- ==================================================== -->
<!-- AFFICHAGE HTML DES INFORMATIONS IP -->
<!-- ==================================================== -->
<div class="ip-info-container">
    <div class="ip-header">
        <h3><i class="fas fa-network-wired"></i> Informations IP: <?= htmlspecialchars($ip) ?></h3>
        <div class="ip-badges">
            <?php if ($ipInfo['is_private']): ?>
                <span class="badge info">IP PRIVÉE</span>
            <?php endif; ?>
            
            <?php if ($ipInfo['threat']['is_tor']): ?>
                <span class="badge danger">TOR EXIT NODE</span>
            <?php endif; ?>
            
            <?php 
            $blacklistedCount = count(array_filter($ipInfo['threat']['dnsbl']));
            if ($blacklistedCount > 0): ?>
                <span class="badge danger">LISTE NOIRE (<?= $blacklistedCount ?>)</span>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- ==================== SECTION GÉOLOCALISATION ==================== -->
    <?php if (!empty($ipInfo['geo'])): ?>
    <div class="info-section">
        <h4><i class="fas fa-globe-europe"></i> Géolocalisation</h4>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Pays:</span>
                <span class="info-value">
                    <?= htmlspecialchars($ipInfo['geo']['country']) ?>
                    <?php if (!empty($ipInfo['geo']['country_code'])): ?>
                        <span class="flag"><?= htmlspecialchars($ipInfo['geo']['country_code']) ?></span>
                    <?php endif; ?>
                </span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Ville/Région:</span>
                <span class="info-value">
                    <?= htmlspecialchars($ipInfo['geo']['city']) ?>
                    <?php if (!empty($ipInfo['geo']['region'])): ?>
                        , <?= htmlspecialchars($ipInfo['geo']['region']) ?>
                    <?php endif; ?>
                </span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Fournisseur:</span>
                <span class="info-value"><?= htmlspecialchars($ipInfo['geo']['isp']) ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Organisation:</span>
                <span class="info-value"><?= htmlspecialchars($ipInfo['geo']['org']) ?></span>
            </div>
            
            <?php if (!empty($ipInfo['geo']['lat']) && !empty($ipInfo['geo']['lon'])): ?>
            <div class="info-item full-width">
                <span class="info-label">Coordonnées:</span>
                <span class="info-value">
                    <?= htmlspecialchars($ipInfo['geo']['lat']) ?>, <?= htmlspecialchars($ipInfo['geo']['lon']) ?>
                    <a href="https://www.google.com/maps?q=<?= $ipInfo['geo']['lat'] ?>,<?= $ipInfo['geo']['lon'] ?>" 
                       target="_blank" class="map-link">
                        <i class="fas fa-map-marked-alt"></i> Voir sur carte
                    </a>
                </span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- ==================== SECTION SÉCURITÉ ==================== -->
    <div class="info-section">
        <h4><i class="fas fa-shield-alt"></i> Analyse de sécurité</h4>
        
        <!-- Listes noires DNSBL -->
        <div class="security-item">
            <h5><i class="fas fa-ban"></i> Listes noires DNSBL</h5>
            <div class="blacklist-grid">
                <?php foreach ($ipInfo['threat']['dnsbl'] as $list => $blocked): ?>
                    <div class="blacklist-item <?= $blocked ? 'blocked' : 'clean' ?>">
                        <span class="list-name"><?= htmlspecialchars($list) ?></span>
                        <span class="list-status">
                            <?php if ($blocked): ?>
                                <i class="fas fa-times-circle"></i> LISTÉ
                            <?php else: ?>
                                <i class="fas fa-check-circle"></i> CLEAN
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Statistiques d'attaque -->
        <?php if (!empty($ipInfo['stats'])): ?>
        <div class="security-item">
            <h5><i class="fas fa-chart-bar"></i> Statistiques d'attaque</h5>
            <div class="stats-grid">
                <div class="stat-card mini">
                    <div class="stat-value"><?= $ipInfo['stats']['total_attacks'] ?></div>
                    <div class="stat-label">Tentatives totales</div>
                </div>
                
                <div class="stat-card mini">
                    <div class="stat-value"><?= count($ipInfo['stats']['attack_types'] ?? []) ?></div>
                    <div class="stat-label">Types d'attaque</div>
                </div>
                
                <div class="stat-card mini">
                    <div class="stat-value">
                        <?= $ipInfo['stats']['first_seen'] ? date('d/m/Y', strtotime($ipInfo['stats']['first_seen'])) : 'N/A' ?>
                    </div>
                    <div class="stat-label">Première vue</div>
                </div>
                
                <div class="stat-card mini">
                    <div class="stat-value">
                        <?= $ipInfo['stats']['last_seen'] ? date('d/m/Y H:i', strtotime($ipInfo['stats']['last_seen'])) : 'N/A' ?>
                    </div>
                    <div class="stat-label">Dernière activité</div>
                </div>
            </div>
            
            <!-- Types d'attaque -->
            <?php if (!empty($ipInfo['stats']['attack_types'])): ?>
            <div class="attack-types">
                <h6>Répartition des attaques:</h6>
                <div class="type-list">
                    <?php foreach ($ipInfo['stats']['attack_types'] as $type => $count): ?>
                        <div class="type-item">
                            <span class="type-name"><?= htmlspecialchars($type) ?></span>
                            <span class="type-count"><?= $count ?> fois</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Données locales -->
        <?php if (!empty($ipInfo['local'])): ?>
        <div class="security-item">
            <h5><i class="fas fa-database"></i> Données locales</h5>
            <div class="local-data">
                <?php foreach ($ipInfo['local'] as $key => $value): ?>
                    <?php if (!is_array($value) && $key !== 'ip'): ?>
                        <div class="data-item">
                            <span class="data-label"><?= htmlspecialchars($key) ?>:</span>
                            <span class="data-value">
                                <?php 
                                if (in_array($key, ['last_attempt', 'blocked_at', 'created_at'])) {
                                    echo date('d/m/Y H:i:s', is_numeric($value) ? $value : strtotime($value));
                                } elseif (is_bool($value)) {
                                    echo $value ? 'Oui' : 'Non';
                                } else {
                                    echo htmlspecialchars($value);
                                }
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- ==================== SECTION LOGS RÉCENTS ==================== -->
    <?php if (!empty($ipInfo['logs'])): ?>
    <div class="info-section">
        <h4><i class="fas fa-history"></i> Activité récente (10 derniers logs)</h4>
        <div class="logs-container">
            <?php foreach ($ipInfo['logs'] as $log): ?>
                <div class="log-entry <?= strtolower(str_replace('_', '-', $log['type'])) ?>">
                    <div class="log-time">
                        <i class="fas fa-clock"></i>
                        <?= htmlspecialchars($log['timestamp']) ?>
                    </div>
                    <div class="log-type">
                        <i class="fas fa-<?= getAttackIcon($log['type']) ?>"></i>
                        <?= htmlspecialchars($log['type']) ?>
                    </div>
                    <div class="log-preview">
                        <?= htmlspecialchars(substr($log['raw'], 0, 100)) ?>...
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- ==================== ACTIONS ==================== -->
    <div class="info-section actions-section">
        <h4><i class="fas fa-cogs"></i> Actions</h4>
        <div class="action-buttons">
            <button class="btn danger block-ip-action" data-ip="<?= htmlspecialchars($ip) ?>">
                <i class="fas fa-ban"></i> Bloquer cette IP
            </button>
            
            <button class="btn warning" onclick="window.open('https://www.abuseipdb.com/check/<?= urlencode($ip) ?>', '_blank')">
                <i class="fas fa-external-link-alt"></i> Vérifier sur AbuseIPDB
            </button>
            
            <button class="btn info" onclick="window.open('https://www.virustotal.com/gui/ip-address/<?= urlencode($ip) ?>', '_blank')">
                <i class="fas fa-search"></i> Analyser sur VirusTotal
            </button>
            
            <button class="btn" onclick="copyToClipboard('<?= htmlspecialchars($ip) ?>')">
                <i class="fas fa-copy"></i> Copier l'IP
            </button>
        </div>
    </div>
</div>

<!-- ==================================================== -->
<!-- JAVASCRIPT POUR LA PAGE -->
<!-- ==================================================== -->
<script>
// Copier IP dans le clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('IP copiée dans le presse-papier');
    }, function(err) {
        console.error('Erreur de copie: ', err);
    });
}

// Blocage d'IP
document.querySelector('.block-ip-action')?.addEventListener('click', function() {
    const ip = this.getAttribute('data-ip');
    if (confirm(`Voulez-vous vraiment bloquer l'IP ${ip} ?\n\nElle sera ajoutée aux listes noires et au .htaccess.`)) {
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
                alert(data.message);
                // Mettre à jour l'interface
                this.innerHTML = '<i class="fas fa-check"></i> IP BLOQUÉE';
                this.disabled = true;
                this.classList.remove('danger');
                this.classList.add('success');
            } else {
                alert('Erreur: ' + (data.error || 'Inconnue'));
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur réseau lors du blocage');
        });
    }
});

// Icons pour les types d'attaques
function getAttackIcon(type) {
    const icons = {
        'BOT_DETECTED': 'robot',
        'HONEYPOT_TRIGGERED': 'bug',
        'RATE_LIMIT': 'tachometer-alt',
        'CSRF_FAILED': 'shield-alt',
        'EMPTY_FIELD': 'exclamation-triangle'
    };
    return icons[type] || 'exclamation-circle';
}
</script>

<!-- ==================================================== -->
<!-- STYLE CSS POUR IP-INFO -->
<!-- ==================================================== -->
<style>
.ip-info-container {
    max-width: 1000px;
    margin: 0 auto;
}

.ip-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #667eea;
}

.ip-badges {
    display: flex;
    gap: 0.5rem;
}

.info-section {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.info-section h4 {
    color: #667eea;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
}

.info-item.full-width {
    grid-column: 1 / -1;
}

.info-label {
    font-weight: 600;
    color: #555;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.info-value {
    color: #333;
    font-size: 1rem;
}

.flag {
    display: inline-block;
    background: #eee;
    padding: 0.1rem 0.5rem;
    border-radius: 3px;
    font-size: 0.8rem;
    margin-left: 0.5rem;
}

.map-link {
    margin-left: 1rem;
    color: #667eea;
    text-decoration: none;
}

.map-link:hover {
    text-decoration: underline;
}

.security-item {
    margin-bottom: 1.5rem;
}

.security-item h5 {
    color: #555;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.blacklist-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.blacklist-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border-radius: 5px;
    background: #f8f9fa;
}

.blacklist-item.blocked {
    background: #fff5f5;
    color: #c92a2a;
}

.blacklist-item.clean {
    background: #ebfbee;
    color: #2b8a3e;
}

.list-name {
    font-weight: 500;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.stat-card.mini {
    text-align: center;
    padding: 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px;
}

.stat-card.mini .stat-value {
    font-size: 2rem;
    font-weight: bold;
    line-height: 1;
}

.stat-card.mini .stat-label {
    font-size: 0.8rem;
    opacity: 0.9;
}

.attack-types {
    margin-top: 1rem;
}

.attack-types h6 {
    color: #666;
    margin-bottom: 0.5rem;
}

.type-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.type-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 5px;
}

.type-name {
    font-weight: 500;
}

.local-data {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem;
}

.data-item {
    display: flex;
    flex-direction: column;
}

.data-label {
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 0.25rem;
}

.data-value {
    font-family: monospace;
    font-size: 0.9rem;
    color: #333;
}

.logs-container {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #eee;
    border-radius: 5px;
}

.log-entry {
    padding: 1rem;
    border-bottom: 1px solid #eee;
}

.log-entry:last-child {
    border-bottom: none;
}

.log-entry.bot-detected {
    background: #fff5f5;
}

.log-entry.honeypot-triggered {
    background: #fff9db;
}

.log-entry.rate-limit {
    background: #e7f5ff;
}

.log-time {
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.log-type {
    font-weight: 600;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.log-preview {
    font-size: 0.85rem;
    color: #555;
    font-family: monospace;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.actions-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.action-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.alert {
    padding: 1rem;
    border-radius: 5px;
    margin-bottom: 1rem;
}

.alert.danger {
    background: #fff5f5;
    color: #c92a2a;
    border-left: 4px solid #ff6b6b;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.btn.danger {
    background: #ff6b6b;
    color: white;
}

.btn.warning {
    background: #ffd166;
    color: #333;
}

.btn.info {
    background: #4dabf7;
    color: white;
}

.btn.success {
    background: #38d9a9;
    color: #333;
}
</style>