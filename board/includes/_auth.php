<?php
session_start();

// Protection contre les attaques de fixation de session
if (empty($_SESSION['IPfixed'])) {
    $_SESSION['IPfixed'] = $_SERVER['REMOTE_ADDR'];
} elseif ($_SESSION['IPfixed'] !== $_SERVER['REMOTE_ADDR']) {
    session_destroy();
    header('Location: /login.php?error=session');
    exit();
}

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: board/login.php');
    exit();
}