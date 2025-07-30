<?php
$errorCodes = [
    'db' => 'Erreur de connexion à la base de données',
    'query' => 'Erreur de requête SQL',
    'auth' => 'Accès non autorisé'
];
$code = $_GET['code'] ?? 'unknown';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Erreur</title>
</head>
<body>
    <h1>Erreur : <?= $errorCodes[$code] ?? 'Erreur inconnue' ?></h1>
    <p>Contactez l'administrateur</p>
</body>
</html>