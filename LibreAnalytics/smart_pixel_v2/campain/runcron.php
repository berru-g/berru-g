<?php
// Simuler un environnement CLI
$_SERVER['argv'] = [__DIR__ . '/send_sequential_email.php'];
$_SERVER['argc'] = 1;

// Rediriger la sortie vers un fichier log
ob_start();
require __DIR__ . '/send_sequential_email.php';
$output = ob_get_clean();

// Afficher la sortie (pour débogage PAS EN PROD)
header('Content-Type: text/plain');
echo "=== Sortie du script ===\n";
echo $output ?: "Script exécuté (voir logs pour détails).";

?>