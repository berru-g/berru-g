<?php
require_once '../includes/config.php';
// requete à cron pour automatiser l'envoie du rapport tout les vendredi à 17h :
// Avec email() pour le moment, passer à phpmailer par la suite !
// 0 17 * * 5 /public_html/LibreAnalytics/smart_pixel_v2/cron/send_sequential_email.php
// | |  | | | - FORMAT CRON 😂  **for sure** :
// │ │  │ │ └── Jour de la semaine (0-6, 0=dimanche, 1=lundi, ..., 5=dredi, 6=samedi)
// │ │  │ └──── Mois (1-12)
// │ │  └────── Jour du mois (1-31)
// │ └───────── Heure (0-23)
// └─────────── Minute (0-59)


// 1. Créer les dossiers (avec chemins absolus pour Hebergeur)
$graphDir = __DIR__ . "/../tmp/graphs/";
$logDir = __DIR__ . "/../logs/";
if (!file_exists($graphDir)) mkdir($graphDir, 0755, true);
if (!file_exists($logDir)) mkdir($logDir, 0755, true);

// 2. Fonction de log 
function logMessage($message, $isError = false) {
    global $logDir;
    $logFile = $logDir . "/cron_debug_" . date('Y-m-d') . ".log";
    $prefix = $isError ? "[ERREUR]" : "[INFO]";
    file_put_contents($logFile, date('Y-m-d H:i:s') . " $prefix " . $message . "\n", FILE_APPEND);
}

// 3. Vérifier les limites d'envoi (4 emails/minute, 90 emails/jour)
function checkEmailLimits() {
    global $logDir;
    $today = date('Y-m-d');
    $currentMinute = date('Y-m-d H:i');
    $logFile = $logDir . "/email_limits.log";

    // Lire les logs pour compter les emails envoyés à cause de cette fucking limite d'envoie 
    $logs = file_exists($logFile) ? file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    $todayCount = 0;
    $minuteCount = 0;

    foreach ($logs as $log) {
        if (strpos($log, $today) !== false) $todayCount++;
        if (strpos($log, $currentMinute) !== false) $minuteCount++;
    }

    if ($todayCount >= 90) {
        logMessage("Limite quotidienne de 90 emails atteinte.", true);
        return false;
    }
    if ($minuteCount >= 4) {
        logMessage("Limite de 4 emails/minute atteinte. Attente de 60 secondes...", true);
        sleep(60); // Pause de 60 secondes si dépassement
        return checkEmailLimits(); // Re-vérifier après la pausee
    }
    return true;
}

// 4. Récupérer TOUS les utilisateur (avec le nom du user)
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("
        SELECT u.id, u.email, u.api_key, us.tracking_code as site_id, u.name
        FROM users u
        JOIN user_sites us ON u.id = us.user_id
        WHERE DATEDIFF(NOW(), u.created_at) >= 7
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($users)) {
        logMessage("Aucun utilisateur éligible (inscrit depuis moins de 7 jours).");
        die();
    }

    logMessage("Nombre d'utilisateurs à traiter : " . count($users));

    // 5. Traiter chaque utilisateur (même avec 0 visites)
    foreach ($users as $user) {
        $userId = $user['id'];
        $email = $user['email'];
        $name = $user['name']; // Nom du user ajouté
        $siteId = $user['site_id'];
        $apiKey = $user['api_key'];

        logMessage("Début du traitement pour $name ($email) (site: $siteId)");

        // 6. Vérifier les limites avant envoi
        if (!checkEmailLimits()) {
            logMessage("Arrêt du traitement pour respecter les limites d'envoi.", true);
            break;
        }

        // 7. Récupérer les stats via l'API
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $apiUrl = "https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/api.php?site_id=$siteId&api_key=$apiKey&start_date=$startDate&end_date=$endDate";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200) {
            logMessage("Erreur API ($httpCode) pour $email. Réponse : " . substr($response, 0, 200), true);
            continue;
        }

        if ($curlError) {
            logMessage("Erreur cURL pour $email : $curlError", true);
            continue;
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            logMessage("Erreur JSON pour $email : " . json_last_error_msg() . " | Réponse : " . substr($response, 0, 200), true);
            continue;
        }

        if (!isset($data['data']) || !isset($data['meta'])) {
            logMessage("Réponse API mal formatée pour $email. Structure : " . print_r($data, true), true);
            continue;
        }

        $totalVisits = $data['meta']['total_visits'] ?? 0;
        logMessage("Stats récupérées pour $email : $totalVisits visites.");

        // 8. Générer le graphique (même si 0 visites)
        try {
            $svgContent = '<svg width="600" height="300" viewBox="0 0 600 300" xmlns="http://www.w3.org/2000/svg">
                <rect width="100%" height="100%" fill="#fff" />
                <line x1="40" y1="20" x2="40" y2="260" stroke="#ccc" />
                <line x1="40" y1="260" x2="560" y2="260" stroke="#ccc" />
                <text x="200" y="20" text-anchor="middle" font-size="14">Trafic Hebdomadaire</text>
                <text x="200" y="50" text-anchor="middle" font-size="12">Site : ' . htmlspecialchars($siteId) . '</text>
                <text x="200" y="70" text-anchor="middle" font-size="12">Visites : ' . $totalVisits . '</text>
                <polyline fill="none" stroke="#9d86ff" stroke-width="2" points="';

            $points = [];
            if (!empty($data['data'])) {
                $maxVisits = max(array_column($data['data'], 'visits')) ?: 1; // Éviter division par 0
                foreach ($data['data'] as $day) {
                    $x = 40 + (strtotime($day['date']) - strtotime($startDate)) * (520 / 6);
                    $y = 260 - (($day['visits'] ?? 0) / $maxVisits) * 220;
                    $points[] = "$x,$y";
                }
            } else {
                $points = ["40,260", "560,260"]; // Ligne plate si pas de données
            }

            $svgContent .= implode(' ', $points) . '" /></svg>';
            $graphPath = $graphDir . "graph_$userId.svg";
            file_put_contents($graphPath, $svgContent);
            logMessage("Graphique généré pour $email : $graphPath");
        } catch (Exception $e) {
            logMessage("Erreur génération SVG pour $email : " . $e->getMessage(), true);
            continue;
        }

        // 9. Envoyer l'email (avec le nom du user)
        $graphUrl = "https://gael-berru.com/LibreAnalytics/tmp/graphs/graph_$userId.svg";
        $subject = "Ton rapport hebdomadaire - $totalVisits visites, $name";
        $message = "<html><body>
            <h2>Rapport Hebdomadaire LibreAnalytics</h2>
            <p>Bonjour $name,</p>
            <p>Voici ton rapport du $startDate au $endDate :</p>
            <p><strong>$totalVisits</strong> visites</p>
            <p><img src='$graphUrl' alt='Graphique de trafic' style='max-width:100%;' /></p>
            <p><a href='https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/dashboard.php'>Accéder à ton dashboard</a></p>
        </body></html>";

        $headers = "From: LibreAnalytics <contact@gael-berru.com>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        $mailSent = mail($email, $subject, $message, $headers);
        if (!$mailSent) {
            logMessage("Échec de l'envoi d'email à $email", true);
        } else {
            logMessage("Email envoyé avec succès à $email");
            // Loguer l'envoi pour les limites
            file_put_contents($logDir . "/email_limits.log", date('Y-m-d H:i:s') . " - $email\n", FILE_APPEND);
        }

        // 10. Pause de 15 secondes entre chaque email (pour respecter 4/min)
        sleep(15);
    }

} catch (Exception $e) {
    logMessage("Erreur critique : " . $e->getMessage(), true);
}
?>
