<?php
header('Content-Type: text/plain');
echo "=== DEBUG PATHS ===\n";
echo "URL actuelle : " . $_SERVER['REQUEST_URI'] . "\n";
echo "Script path : " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "Physical path : " . __DIR__ . "\n\n";

$files = [
    'style.css',
    'logobdd.png',
    'script.js'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    echo "$file : " . (file_exists($path) ? 'EXISTE' : 'MANQUANT') . "\n";
    if (file_exists($path)) {
        echo "   Chemin absolu : $path\n";
        echo "   Chemin web : " . dirname($_SERVER['SCRIPT_NAME']) . "/$file\n";
        echo "   Permission : " . substr(sprintf('%o', fileperms($path)), -4) . "\n";
    }
}