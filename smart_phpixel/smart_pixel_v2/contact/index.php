<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Vérifie si connecté
if (!Auth::isLoggedIn()) {
    // Redirige UNIQUEMENT si pas connecté
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);

?>
<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Pixel Analytics - Contact</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <link rel="stylesheet" href="../assets/login.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

</head>

<body>

<!--FORMULAIRE-->
        <div class="nav-section">
          <div class="nav-section-title">Contact</div>

          <div class="contact-form-inside"
            style="padding: 1rem; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 10px; margin: 0.5rem 0;">
            <form id="inlineContactForm" action="https://formspree.io/f/#" method="POST">
              <div class="form-group" style="margin-bottom: 1rem;">
                <input type="email" name="email"
                  style="width: 100%; padding: 0.625rem 0.75rem; background: var(--search-bg); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-color); font-size: 0.875rem;"
                  placeholder="Votre email" required>
              </div>
              <div class="form-group" style="margin-bottom: 1rem;">
                <textarea name="message"
                  style="width: 100%; min-height: 80px; padding: 0.625rem 0.75rem; background: var(--search-bg); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-color); font-size: 0.875rem; resize: vertical;"
                  placeholder="Votre message..." required></textarea>
              </div>
              <button type="submit"
                style="width: 100%; padding: 0.625rem; background: var(--primary-color); color: white; border: none; border-radius: 6px; font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 0.5rem; cursor: pointer; transition: opacity 0.2s;">
                <span>Envoyer</span>
                <!--<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>-->
                <i class="fa-regular fa-paper-plane"></i>
              </button>
            </form>
            <div id="formMessage"
              style="margin-top: 0.5rem; font-size: 0.75rem; color: var(--text-secondary); text-align: center;"></div>
          </div>
        </div>

</body>
</html>