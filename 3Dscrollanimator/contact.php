<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'PointsManager.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $type = $_POST['type'];
    $message_content = trim($_POST['message']);
    $user_id = Auth::isLoggedIn() ? $_SESSION['user_id'] : null;

    if (empty($name) || empty($email) || empty($message_content)) {
        $message = 'Veuillez remplir tous les champs obligatoires.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Adresse email invalide.';
        $message_type = 'error';
    } else {
        try {
            $db = getDB();
            $db->beginTransaction();

            // Insérer le feedback
            $stmt = $db->prepare("
                INSERT INTO feedback 
                (user_id, name, email, type, message, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$user_id, $name, $email, $type, $message_content]);

            // Ajouter les points si utilisateur connecté
            if ($user_id) {
                $result = PointsManager::addPoints($user_id, 500);
                if (!$result['success']) {
                    throw new Exception($result['message']);
                }
            }

            $db->commit();

            $message = 'Merci pour votre feedback ! ' .
                ($user_id ? '500 points ont été ajoutés à votre compte.' : '');
            $message_type = 'success';

        } catch (Exception $e) {
            $db->rollBack();
            $message = 'Erreur lors de l\'envoi du message: ' . $e->getMessage();
            $message_type = 'error';
            error_log("Feedback Error: " . $e->getMessage());
        }
    }
}

// Récupérer les stats utilisateur pour l'affichage
$user_stats = [
    'points' => 0,
    'feedback_count' => 0,
    'total_points_earned' => 0
];

if (Auth::isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $db = getDB();

    // Points actuels
    $user_stats['points'] = PointsManager::getPoints($user_id);

    // Nombre de feedbacks
    $stmt = $db->prepare("SELECT COUNT(*) FROM feedback WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_stats['feedback_count'] = $stmt->fetchColumn();

    // Total points gagnés
    $user_stats['total_points_earned'] = $user_stats['feedback_count'] * 500;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - 3D Scroll Animator</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --accent: #10b981;
            --accent-dark: #059669;
            --warning: #f59e0b;
            --warning-dark: #d97706;
            --error: #ef4444;
            --error-dark: #dc2626;

            --background: #0f172a;
            --surface: #1e293b;
            --surface-light: #334155;
            --surface-dark: #0f172a;
            --card: rgba(255, 255, 255, 0.05);

            --text: #f8fafc;
            --text-light: #94a3b8;
            --text-lighter: #cbd5e1;
            --text-muted: #64748b;

            --border: #475569;
            --border-light: #64748b;
            --border-dark: #374151;

            --radius: 8px;
            --radius-sm: 4px;
            --radius-lg: 12px;
            --radius-xl: 16px;

            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);

            --transition: all 0.2s ease-in-out;
            --transition-slow: all 0.3s ease-in-out;

            --font-sans: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-mono: 'Monaco', 'Consolas', 'Monaco', monospace;

            --space-xs: 0.25rem;
            --space-sm: 0.5rem;
            --space-md: 1rem;
            --space-lg: 1.5rem;
            --space-xl: 2rem;
            --space-2xl: 3rem;

            --text-xs: 0.75rem;
            --text-sm: 0.875rem;
            --text-base: 1rem;
            --text-lg: 1.125rem;
            --text-xl: 1.25rem;
            --text-2xl: 1.5rem;

            --container-max: 1200px;
            --sidebar-width: 380px;
            --header-height: 60px;
        }

        .contact-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 3rem;
            align-items: start;
        }

        .hero-section {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem;
            background: linear-gradient(135deg, var(--surface) 0%, var(--surface-light) 100%);
            border-radius: var(--radius);
            border: 1px solid var(--border);
        }

        .mascotte-section {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .mascotte-img {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
        }

        .mascotte-text {
            flex: 1;
        }

        .rewards-card {
            background: linear-gradient(135deg, var(--primary-dark) 40%, var(---primary-light) 100%);
            border: 2px solid var(--primary);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            color: var(--text);
        }

        .rewards-card h3 {
            color: var(--text);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text);
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--surface);
            color: var(--text);
            font-size: 0.875rem;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .form-textarea {
            height: 120px;
            resize: vertical;
            font-family: inherit;
        }

        .submit-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
        }

        .submit-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .user-stats {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border);
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        .gems-badge {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: var(--);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .message {
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .message.success {
            background: #dcfce7;
            color: #4cb675ff;
            border: 1px solid #bbf7d0;
        }

        .message.error {
            background: #fee2e2;
            color: #dc5555ff;
            border: 1px solid #fecaca;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }

        .feature-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            text-align: center;
        }

        .feature-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .contact-container {
                grid-template-columns: 1fr;
                padding: 1rem;
            }

            .mascotte-section {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <?php require_once 'header.php'; ?>

    <div class="contact-container">
        <div>
            <div class="hero-section">
                <h1>Améliorez 3D Scroll Animator avec nous</h1>
                <p>Votre feedback est essentiel pour créer l'outil no-code 3D parfait</p>
            </div>

            <div class="mascotte-section">
                <img src="../img/mascotte-sav.png"
                    alt="Mascotte 3D Scroll Animator" class="mascotte-img">
                <div class="mascotte-text">
                    <h2 style="color: grey;">Salut ! Je suis Scrollizy, votre assistant 3D</h2>
                    <p style="color: white;">Chez 3D Scroll Animator, nous croyons que les meilleures idées viennent de notre communauté.
                        Partagez vos suggestions, bug rencontrés ou idées de fonctionnalités - chaque feedback nous aide
                        à améliorer l'outil pour tous !</p>
                </div>
            </div>

            <div class="rewards-card">
                <h3 style="color: grey;">Récompense spéciale : 500 💎 par feedback</h3>
                <p>Pour chaque feedback constructif que vous nous envoyez, nous vous offrons <strong>500 crédits</strong>
                    à utiliser sur la plateforme !</p>
                <p><small>Les gemmes vous permettent de débloquer des fonctionnalités premium et des modèles
                        exclusifs.</small></p>
            </div>

            <?php if ($message): ?>
                <div class="message <?= $message_type ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="feedback-form">
                <div class="form-group">
                    <label class="form-label" for="name">Votre nom *</label>
                    <input type="text" id="name" name="name" class="form-input"
                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Votre email *</label>
                    <input type="email" id="email" name="email" class="form-input"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="type">Type de feedback *</label>
                    <select id="type" name="type" class="form-select" required>
                        <option value="">Sélectionnez...</option>
                        <option value="bug" <?= ($_POST['type'] ?? '') === 'bug' ? 'selected' : '' ?>>🐛 Rapport de bug
                        </option>
                        <option value="feature" <?= ($_POST['type'] ?? '') === 'feature' ? 'selected' : '' ?>>💡 Idée de
                            fonctionnalité</option>
                        <option value="improvement" <?= ($_POST['type'] ?? '') === 'improvement' ? 'selected' : '' ?>>✨
                            Suggestion d'amélioration</option>
                        <option value="uiux" <?= ($_POST['type'] ?? '') === 'uiux' ? 'selected' : '' ?>>🎨 Feedback
                            design/UX</option>
                        <option value="other" <?= ($_POST['type'] ?? '') === 'other' ? 'selected' : '' ?>>💬 Autre</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="message">Votre message *</label>
                    <textarea id="message" name="message" class="form-textarea"
                        placeholder="Décrivez en détail votre suggestion, le bug rencontré, ou votre idée d'amélioration..."
                        required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="submit-btn">
                    Envoyer mon feedback & Recevoir 500 💎
                </button>
            </form>

            <?php if ($user_id && $message_type === 'success'): ?>
                <script>
                    // Afficher l'animation après l'envoi du formulaire
                    document.addEventListener('DOMContentLoaded', function () {
                        setTimeout(() => {
                            showPointsAnimation(500, 'Merci pour votre feedback !');
                        }, 1000);
                    });
                </script>
            <?php endif; ?>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-regular fa-map"></i></div>
                    <h4 style="color: grey;">Roadmap Publique</h4>
                    <p>Suivez l'évolution des features demandées par la communauté</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-trophy"></i></div>
                    <h4 style="color: grey;">Top Contributeurs</h4>
                    <p>Les meilleurs feedbacks sont récompensés mensuellement</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-regular fa-message"></i></div>
                    <h4 style="color: grey;">Réponse Garantie</h4>
                    <p>Nous répondons à tous les messages sous 48h</p>
                </div>
            </div>
        </div>

        <div class="sidebar-info">
            <?php if (Auth::isLoggedIn()): ?>
                <div class="user-stats">
                    <h3>Votre profil</h3>
                    <div class="stat-item">
                        <span>Points actuels :</span>
                        <span class="points-badge"><?= $user_stats['points'] ?> 💎</span>
                    </div>
                    <div class="stat-item">
                        <span>Feedback envoyés :</span>
                        <span><?= $user_stats['feedback_count'] ?></span>
                    </div>
                    <div class="stat-item">
                        <span>Points gagnés :</span>
                        <span class="points-badge"><?= $user_stats['total_points_earned'] ?> 💎</span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="feature-card">
                <h3><i class="fa-solid fa-pencil"></i> Que partager ?</h3>
                <ul style="text-align: left; padding-left: 1rem;">
                    <li>Bugs techniques rencontrés</li>
                    <li>Idées de nouvelles fonctionnalités</li>
                    <li>Améliorations de l'interface</li>
                    <li>Problèmes de performance</li>
                    <li>Suggestions de modèles 3D</li>
                </ul>
            </div>

            <div class="feature-card">
                <h3><i class="fa-solid fa-star"></i> Les meilleurs feedbacks</h3>
                <p>Les feedbacks les plus utiles reçoivent des récompenses supplémentaires !</p>
                <ul style="text-align: left; padding-left: 1rem;">
                    <li>Précision du problème</li>
                    <li>Solutions proposées</li>
                    <li>Captures d'écran</li>
                    <li>Étapes pour reproduire</li>
                </ul>
            </div>
        </div>
    </div>
    <?php require_once 'footer.php'; ?>
    <script src="scriptV2.js"></script>

    <script>
        // Animation simple pour le formulaire
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('.feedback-form');
            form.style.opacity = '0';
            form.style.transform = 'translateY(20px)';

            setTimeout(() => {
                form.style.transition = 'all 0.5s ease';
                form.style.opacity = '1';
                form.style.transform = 'translateY(0)';
            }, 300);
        });
    </script>

</body>

</html>