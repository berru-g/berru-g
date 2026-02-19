<?php
// public/account.php
require_once '../includes/auth.php';
require_once '../includes/config.php';

if (!Auth::isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);

// Récupérer les infos utilisateur
$stmt = $pdo->prepare("
    SELECT email, api_token, created_at, plan,
           (SELECT COUNT(*) FROM user_sites WHERE user_id = users.id) as sites_count
    FROM users WHERE id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Régénérer la clé API si demandé
if (isset($_POST['regenerate_api_token'])) {
    $newApiKey = 'sk_' . bin2hex(random_bytes(16));
    $stmt = $pdo->prepare("UPDATE users SET api_token = ? WHERE id = ?");
    $stmt->execute([$newApiKey, $userId]);
    $user['api_token'] = $newApiKey;
    $success = "Votre clé API a été régénérée avec succès.";
}
?>

<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon compte - Smart Pixel Analytics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #9d86ff;
            --primary-dark: #9d86ff;
            --secondary: #f8f9fa;
            --text: #333;
            --text-light: #666;
            --border: #e9ecef;
            --success: #4ecdc4;
            --warning: #f59e0b;
            --danger: #ff6b8b;
            --radius: 8px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
        }

        [data-theme="dark"] {
            --primary: #9d86ff;
            --primary-dark: #9d86ff;
            --secondary: #1e1e2d;
            --text: #f8f9fa;
            --text-light: #adb5bd;
            --border: #343a40;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: var(--transition);
        }

        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            background-color: var(--secondary);
            color: var(--text);
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border);
        }

        [data-theme="dark"] .card {
            background: #2d2d3d;
            border-color: #444;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--text);
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            background: var(--secondary);
            color: var(--primary);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: 1px solid var(--border);
            transition: var(--transition);
        }

        .back-button:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .user-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-card {
            padding: 1.5rem;
            border-radius: var(--radius);
            background: var(--secondary);
            border: 1px solid var(--border);
        }

        .info-card h3 {
            color: var(--text-light);
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-card p {
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
        }

        .info-card .value {
            font-weight: 500;
            color: var(--text);
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-free {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }

        .status-pro {
            background: rgba(0, 184, 163, 0.1);
            color: #00b8a3;
        }

        .status-business {
            background: rgba(21, 166, 139, 0.1);
            color: #15a689;
        }

        .api-section {
            margin-top: 2rem;
        }

        .api-section h2 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: var(--text);
        }

        .api-key-container {
            position: relative;
            margin: 1.5rem 0;
        }

        .api-key-display {
            display: flex;
            align-items: center;
            background: var(--secondary);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1rem;
            word-break: break-all;
        }

        .api-key-display code {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            flex-grow: 1;
        }

        .copy-button {
            background: none;
            border: none;
            color: var(--primary);
            cursor: pointer;
            padding: 0.5rem;
            margin-left: 1rem;
            border-radius: 4px;
            transition: var(--transition);
        }

        .copy-button:hover {
            background: rgba(67, 97, 238, 0.1);
        }

        .regenerate-button {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: var(--radius);
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .regenerate-button:hover {
            background: var(--primary-dark);
        }

        .regenerate-button i {
            font-size: 0.9rem;
        }

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--success);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            transform: translateX(200%);
            transition: transform 0.3s ease-out;
            z-index: 1000;
        }

        .toast.show {
            transform: translateX(0);
        }

        @media (max-width: 768px) {
            .user-section {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="card">
            <div class="header">
                <h1>Mon compte</h1>
                <a href="dashboard.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Retour au dashboard
                </a>
            </div>

            <?php if (isset($success)): ?>
                <div class="toast show" id="toast">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <div class="user-section">
                <div class="info-card">
                    <h3>Informations personnelles</h3>
                    <p>
                        <span>Email</span>
                        <span class="value"><?= htmlspecialchars($user['email']) ?></span>
                    </p>
                    <p>
                        <span>Date d'inscription</span>
                        <span class="value"><?= (new DateTime($user['created_at']))->format('d M Y') ?></span>
                    </p>
                    <p>
                        <span>Sites connectés</span>
                        <span class="value"><?= $user['sites_count'] ?> site(s)</span>
                    </p>
                </div>

                <div class="info-card">
                    <h3>Abonnement</h3>
                    <p>
                        <span>Statut</span>
                        <span class="value">
                            <span class="status-badge status-<?= htmlspecialchars(strtolower($user['plan'])) ?>">
                                <?= htmlspecialchars(ucfirst($user['plan'])) ?>
                            </span>
                        </span>
                    </p>
                </div>
            </div>

            <div class="api-section">
                <h2>Clé API</h2>
                <p>Utilisez cette clé pour accéder à l'API de Smart Pixel. <strong>Ne la partagez jamais.</strong></p>

                <div class="api-key-container">
                    <div class="api-key-display">
                        <code id="apiKey"><?= htmlspecialchars($user['api_token']) ?></code>
                        <button class="copy-button" onclick="copyToClipboard()">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>

                <form method="POST" style="display: inline;">
                    <button type="submit" name="regenerate_api_token" class="regenerate-button">
                        <i class="fas fa-sync-alt"></i> Régénérer la clé
                    </button>
                </form>
            </div>

            <div class="api-key-container">
                    <div class="api-key-display">
                        <h3>Exemple d'utilisation de l'API :</h3>
                        <code id="apiKey">https://gael-berru.com/smart_phpixel/smart_pixel_v2/public/api.php?site_id=SP_12345&start_date=2026-01-01&end_date=2026-02-01&api_token=TON_TOKEN</code>
                        <button class="copy-button" onclick="copyToClipboard()">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>

        </div>
    </div>

    <script>
        // Copier la clé API dans le presse-papiers
        function copyToClipboard() {
            const apiKey = document.getElementById('apiKey').textContent;
            navigator.clipboard.writeText(apiKey)
                .then(() => {
                    const toast = document.createElement('div');
                    toast.className = 'toast';
                    toast.innerHTML = '<i class="fas fa-check-circle"></i> Clé API copiée !';
                    document.body.appendChild(toast);
                    setTimeout(() => {
                        toast.classList.add('show');
                    }, 10);
                    setTimeout(() => {
                        toast.classList.remove('show');
                        setTimeout(() => toast.remove(), 300);
                    }, 2000);
                })
                .catch(err => {
                    console.error('Échec de la copie: ', err);
                });
        }

        // Masquer le toast après 3 secondes
        setTimeout(() => {
            const toast = document.getElementById('toast');
            if (toast) {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }
        }, 3000);
    </script>
</body>
</html>
