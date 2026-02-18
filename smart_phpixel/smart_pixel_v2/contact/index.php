<?php
// Includes communs
require_once __DIR__ . '/../includes/config.php';

// Démarrer la session pour CAPTCHA et autres
session_start();

// Timestamp de chargement
$load_time = time();

// Générer CAPTCHA si pas déjà (ou régénérer en cas d'erreur)
if (!isset($_SESSION['captcha_code']) || isset($error)) {
    $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    $_SESSION['captcha_code'] = $code;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim(strip_tags($_POST['name'] ?? ''));
    $email = trim(strip_tags($_POST['email'] ?? ''));
    $subject = trim(strip_tags($_POST['subject'] ?? ''));
    $message = trim(strip_tags($_POST['message'] ?? ''));
    $type = $_POST['type'] ?? 'other';
    $honeypot = $_POST['website'] ?? '';
    $submitted_load_time = intval($_POST['load_time'] ?? 0);
    $captcha_input = $_POST['captcha'] ?? '';

    // Détection bot : temps < 4 secondes
    $elapsed_time = time() - $submitted_load_time;
    if ($elapsed_time < 4) {
        header('Location: ../404/4bot.php');
        exit;
    }

    if (!empty($honeypot)) {
        $error = "Requête invalide.";
    } elseif (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "Tous les champs sont requis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide.";
    } elseif (strtoupper($captcha_input) !== $_SESSION['captcha_code']) {
        $error = "Code CAPTCHA incorrect.";
        // Régénérer CAPTCHA pour prochaine tentative
        unset($_SESSION['captcha_code']);
    } else {
        try {
            // Insertion en DB
            $stmt = $pdo->prepare("INSERT INTO contacts (name, email, subject, message, type) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $subject, $message, $type]);
            $success = true;
            // Nettoyer session CAPTCHA après succès
            unset($_SESSION['captcha_code']);
        } catch (PDOException $e) {
            $error = "Erreur lors de l'envoi : " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Smart Pixel v2</title>
    <link rel="stylesheet" href="../assets/dashboard.css">
    <script data-sp-id="SP_79747769" src="https://gael-berru.com/smart_phpixel/smart_pixel_v2/public/tracker.js" async></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 850px;
            width: 100%;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 15px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .container > p {
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 30px;
            font-size: 1.1rem;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input, textarea, select {
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: white;
            font-family: inherit;
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea {
            min-height: 140px;
            resize: vertical;
        }

        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%232d3748' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 16px;
        }

        button {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 16px 32px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        button:active {
            transform: translateY(0);
        }

        .message {
            padding: 20px 24px;
            border-radius: 16px;
            margin-bottom: 30px;
            font-weight: 500;
            border: 2px solid transparent;
        }

        .success {
            background: linear-gradient(135deg, #f0fff4 0%, #dcfce7 100%);
            color: #166534;
            border-color: #86efac;
        }

        .error {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            color: #991b1b;
            border-color: #fca5a5;
        }

        .captcha-container {
            background: #f8fafc;
            padding: 20px;
            border-radius: 16px;
            border: 2px dashed #cbd5e1;
        }

        .captcha-img {
            display: block;
            margin: 15px 0;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            max-width: 100%;
            height: auto;
        }

        .honeypot {
            display: none;
        }

        .links {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
        }

        .links p {
            font-size: 1.1rem;
            color: #1e293b;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .links a {
            display: inline-block;
            margin-right: 20px;
            margin-bottom: 10px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            background: #f1f4f9;
            border-radius: 30px;
            transition: all 0.3s ease;
        }

        .links a:hover {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            transform: translateX(5px);
        }

        /* Style pour le timestamp (invisible) */
        input[name="load_time"] {
            display: none;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .container {
                padding: 25px;
            }

            h1 {
                font-size: 1.8rem;
            }

            .links a {
                display: block;
                margin-right: 0;
                text-align: center;
            }
        }

        /* Animation d'apparition */
        .container {
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Tooltip pour le RGPD */
        .rgpd-badge {
            display: inline-block;
            background: #e2e8f0;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 0.85rem;
            color: #475569;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Contactez-nous <span class="rgpd-badge">RGPD ✅</span></h1>
        <p>Pour toute question sur Smart Pixel, un bug, une suggestion ou du support, remplissez ce formulaire. Vos données restent privées et conformes RGPD.</p>

        <?php if (isset($error)): ?>
            <div class="message error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($success) && $success): ?>
            <div class="message success">
                Message envoyé avec succès !
                <div class="links">
                    <p>Continuer vers </p>
                    <a href="../../index.php">l'acceuil</a>
                    <a href="../public/dashboard.php">mon dashboard</a>
                    <a href="../../doc/">la documentation</a>
                </div>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label for="name">Nom complet *</label>
                    <input type="text" id="name" name="name" placeholder="Votre nom" required>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" placeholder="votre@email.com" required>
                </div>

                <div class="form-group">
                    <label for="type">Type de requête</label>
                    <select id="type" name="type">
                        <option value="bug">🐛 Signaler un bug</option>
                        <option value="feature">💡 Suggestion de fonctionnalité</option>
                        <option value="support">🛠️ Support technique</option>
                        <option value="other">❓ Autre</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="subject">Sujet *</label>
                    <input type="text" id="subject" name="subject" placeholder="Résumé de votre message" required>
                </div>

                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" name="message" placeholder="Décrivez votre demande en détails..." required></textarea>
                </div>

                <!-- CAPTCHA maison avec design amélioré -->
                <div class="captcha-container">
                    <label for="captcha">Vérification de sécurité *</label>
                    <img src="../404/captcha.php" alt="CAPTCHA" class="captcha-img">
                    <input type="text" id="captcha" name="captcha" placeholder="Recopiez le code ci-dessus" required>
                    <small style="color: #64748b; display: block; margin-top: 8px;">Cette vérification nous aide à lutter contre le spam</small>
                </div>

                <!-- Honeypot anti-spam (invisible) -->
                <div class="honeypot">
                    <label for="website">Site web (ne pas remplir) :</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <!-- Timestamp pour détection bot -->
                <input type="hidden" name="load_time" value="<?= $load_time ?>">

                <button type="submit">Envoyer le message</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>