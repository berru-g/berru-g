<?php
echo "=== DÉBUT DEBUG ===<br>";

// Test config.php
echo "1. Chargement config.php... ";
require_once 'config.php';
echo "OK<br>";

// Test auth.php  
echo "2. Chargement auth.php... ";
require_once 'auth.php';
echo "OK<br>";

// Test si Auth fonctionne
echo "3. Test Auth::isLoggedIn()... ";
var_dump(Auth::isLoggedIn());
echo "<br>";

echo "4. Session data: ";
var_dump($_SESSION);
echo "<br>";

echo "=== FIN DEBUG ===<br>";
?>