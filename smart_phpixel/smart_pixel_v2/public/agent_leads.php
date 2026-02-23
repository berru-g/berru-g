<?php
// agent_leads.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Vérification de l'authentification (seulement toi peux lancer l'agent)
if (!Auth::isLoggedIn() || $_SESSION['user_email'] !== 'contact@gael-berru.com') {
    die("Accès non autorisé.");
}

// Fonction pour rechercher des sites utilisant GA (simulation avec des requêtes Google et analyse de code)
function findGASites($keywords) {
    // En production, utiliser une API comme SerpAPI ou ScraperAPI pour faire des requêtes Google
    // Exemple : recherche "agence web nantes site:.fr" + vérification de la présence de GA dans le code
    // Ici, on simule avec des données statiques pour l'exemple
    $mockResults = [
        [
            'url' => 'https://agence-web-nantes.fr',
            'title' => 'Agence Web Créative - Nantes',
            'ga_detected' => true,
            'rgpd_mention' => true,
            'sector' => 'Agence Web',
            'email' => 'contact@agence-web-nantes.fr'
        ],
        [
            'url' => 'https://dev-freelance-bordeaux.fr',
            'title' => 'Développeur Freelance Bordeaux',
            'ga_detected' => true,
            'rgpd_mention' => false,
            'sector' => 'Développeur Indépendant',
            'email' => 'hello@dev-freelance-bordeaux.fr'
        ],
        [
            'url' => 'https://boutique-bio-loire.fr',
            'title' => 'Boutique Bio en Loire-Atlantique',
            'ga_detected' => true,
            'rgpd_mention' => true,
            'sector' => 'PME E-commerce',
            'email' => 'bonjour@boutique-bio-loire.fr'
        ]
        // Ajoute d'autres résultats mockés ou réels ici
    ];

    // Filtrer les résultats pour ne garder que ceux avec GA + mention RGPD
    $filtered = array_filter($mockResults, function($site) {
        return $site['ga_detected'] && $site['rgpd_mention'];
    });

    return array_values($filtered);
}

// Fonction pour envoyer les résultats par email
function sendLeadsByEmail($leads) {
    $to = 'contact@gael-berru.com';
    $subject = '[Smart Pixel] Nouvelle liste de leads qualifiés (' . count($leads) . ')';

    // Créer le tableau HTML pour l'email
    $tableRows = '';
    foreach ($leads as $lead) {
        $tableRows .= "
        <tr>
            <td>{$lead['title']}</td>
            <td><a href='{$lead['url']}'>{$lead['url']}</a></td>
            <td>{$lead['sector']}</td>
            <td><a href='mailto:{$lead['email']}'>{$lead['email']}</a></td>
        </tr>";
    }

    $message = "
    <html>
        <head><title>Nouveaux Leads Qualifiés</title></head>
        <body>
            <h2>Nouveaux leads qualifiés pour Smart Pixel</h2>
            <p>Voici une liste de sites utilisant Google Analytics et mentionnant le RGPD :</p>
            <table border='1' cellpadding='5' style='border-collapse: collapse;'>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Site Web</th>
                        <th>Secteur</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>$tableRows</tbody>
            </table>
            <p>Tu peux maintenant rédiger des emails personnalisés pour ces leads.</p>
        </body>
    </html>";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: Smart Pixel Agent <noreply@gael-berru.com>' . "\r\n";

    return mail($to, $subject, $message, $headers);
}

// Fonction pour sauvegarder les leads dans la base de données
function saveLeadsToDB($pdo, $leads) {
    foreach ($leads as $lead) {
        $stmt = $pdo->prepare("
            INSERT INTO leads (company_name, email, sector, website, status, notes)
            VALUES (?, ?, ?, ?, 'à faire', 'Lead trouvé par l\'agent automatique')
            ON DUPLICATE KEY UPDATE status = VALUES(status)
        ");
        $stmt->execute([
            $lead['title'],
            $lead['email'],
            $lead['sector'],
            $lead['url']
        ]);
    }
    return true;
}

// --- Exécution de l'agent ---
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // 1. Recherche des leads (ici, on utilise des données mockées)
    $keywords = [
        'agence web site:.fr',
        'développeur freelance site:.fr',
        'boutique en ligne loire-atlantique site:.fr',
        'pme française site:.fr'
    ];
    $leads = findGASites($keywords);

    // 2. Sauvegarde dans la base de données
    saveLeadsToDB($pdo, $leads);

    // 3. Envoi par email
    $emailSent = sendLeadsByEmail($leads);

    if ($emailSent) {
        echo "✅ Succès : " . count($leads) . " nouveaux leads qualifiés ont été trouvés et envoyés à ton email.";
    } else {
        echo "⚠️ Erreur : Impossible d'envoyer l'email. Les leads ont été sauvegardés dans la base de données.";
    }

    // Affichage des résultats dans la page (optionnel)
    echo "<h3>Nouveaux leads trouvés :</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin: 1rem 0;'>";
    echo "<thead><tr><th>Nom</th><th>Site Web</th><th>Secteur</th><th>Email</th></tr></thead><tbody>";
    foreach ($leads as $lead) {
        echo "<tr>
            <td>{$lead['title']}</td>
            <td><a href='{$lead['url']}' target='_blank'>{$lead['url']}</a></td>
            <td>{$lead['sector']}</td>
            <td><a href='mailto:{$lead['email']}'>{$lead['email']}</a></td>
        </tr>";
    }
    echo "</tbody></table>";

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
