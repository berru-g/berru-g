<?php
// debug_sessions.php
session_start();
echo "<h1>Debug Sessions</h1>";

echo "<h3>Session ID: " . session_id() . "</h3>";
echo "<h3>Session Status: " . session_status() . "</h3>";

echo "<h3>Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Cookies:</h3>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

// Test de connexion manuelle
if (isset($_GET['test_login'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = "Test User";
    $_SESSION['logged_in'] = true;
    echo "<p style='color: green;'>Session forcée - Recharge la page</p>";
}
?>