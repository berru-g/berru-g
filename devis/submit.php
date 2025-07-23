<?php
/* Connexion DB test. pour le deploy créer un config pour cacher les log 
$pdo = new PDO('mysql:host=localhost;dbname= namedevis', 'root', 'root');*/
$dbHost = getenv('DB_HOST');
$dbName = getenv('DB_NAME');
$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASS');

$pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
// step 2 restreindre l'accés IP et utiliser un préfixe de table perso
// Récupération des données
$data = [
    'numero' => $_POST['numero'],
    'date' => DateTime::createFromFormat('d/m/Y', $_POST['date'])->format('Y-m-d'),
    'client_nom' => $_POST['client_nom'],
    'client_email' => $_POST['client_email'],
    'total' => (float)str_replace(['€', ' '], '', $_POST['total']),
    'developpement_vitrine' => isset($_POST['developpement_vitrine']) ? 1 : 0,
    'formulaire_simple' => isset($_POST['formulaire_simple']) ? 1 : 0,
    'formulaire_complexe' => isset($_POST['formulaire_complexe']) ? 1 : 0,
    'optimisation_seo' => isset($_POST['optimisation_seo']) ? 1 : 0,
    'systeme_paiement' => isset($_POST['systeme_paiement']) ? 1 : 0,
    'interface_admin' => isset($_POST['interface_admin']) ? 1 : 0,
    'nom_domaine' => isset($_POST['nom_domaine']) ? 1 : 0,
    'hebergement' => isset($_POST['hebergement']) ? 1 : 0
];

// Insertion en base
$stmt = $pdo->prepare("INSERT INTO devis VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
$stmt->execute(array_values($data));

// Préparation de l'email
$message = "DEVIS COMPLET\n\n";
$message .= "Numéro: ".$data['numero']."\n";
$message .= "Date: ".$_POST['date']."\n";
$message .= "Client: ".$data['client_nom']."\n";
$message .= "Email: ".$data['client_email']."\n\n";

$message .= "SERVICES SELECTIONNES:\n";
if ($data['developpement_vitrine']) $message .= "- Développement site vitrine (550 €)\n";
if ($data['formulaire_simple']) $message .= "- Formulaire de contact simple (50 €)\n";
if ($data['formulaire_complexe']) $message .= "- Formulaire de contact complexe (400 €)\n";
if ($data['optimisation_seo']) $message .= "- Optimisation SEO (100 €)\n";
if ($data['systeme_paiement']) $message .= "- Système de paiement (500 €)\n";
if ($data['interface_admin']) $message .= "- Interface admin (100 €/an)\n";
if ($data['nom_domaine']) $message .= "- Nom de domaine (10 €/an)\n";
if ($data['hebergement']) $message .= "- Hébergement (80 €/an)\n";

$message .= "\nTOTAL: ".$_POST['total']."\n";

// Envoi email
mail('g.leberruyer@gmail.com', 'Nouveau devis #'.$data['numero'], $message);

// Réponse
echo json_encode(['success' => true]);
?>