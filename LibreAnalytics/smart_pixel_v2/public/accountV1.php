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
    SELECT email, api_key, created_at, plan,
           (SELECT COUNT(*) FROM user_sites WHERE user_id = users.id) as sites_count
    FROM users WHERE id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Régénérer la clé API si demandé
if (isset($_POST['regenerate_api_key'])) {
    $newApiKey = 'sk_' . bin2hex(random_bytes(16));
    $stmt = $pdo->prepare("UPDATE users SET api_key = ? WHERE id = ?");
    $stmt->execute([$newApiKey, $userId]);
    $user['api_key'] = $newApiKey;
    $success = "Votre clé API a été régénérée avec succès.";
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon compte - Smart Pixel Analytics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #9d86ff;
            --primary-dark: #9d86ff;
            --bg: #f8f9fa;
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

        @media (prefers-color-scheme: dark) {
            :root {
                --primary: #9d86ff;
                --primary-dark: #9d86ff;
                --bg: #151515;
                --text: #f8f9fa;
                --text-light: #1d1d1e;
                --border: #343a40;
                --shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: var(--transition);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
            
        }

        .card {
            background-color: var(--text-light);
            color: var(--text);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border);
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
            background: var(--bg);
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
            background: var(--bg);
            border: 1px solid var(--border);
        }

        .info-card h3 {
            color: var(--text);
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
            background: var(--bg);
            color: var(--text);
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

        /* Styles pour le tutoriel */
        .tutorial-section {
            margin-top: 3rem;
            border-top: 1px solid var(--border);
            padding-top: 2rem;
        }

        .tutorial-section h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tutorial-section h2 i {
            color: var(--primary);
        }

        .tutorial-step {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .tutorial-step h3 {
            color: var(--primary);
            font-size: 1.2rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tutorial-step h3 i {
            color: var(--primary);
        }

        .tutorial-step p {
            margin-bottom: 0.8rem;
            line-height: 1.6;
        }

        .tutorial-step code {
            display: block;
            background-color: var(--text-light);
            color: var(--text);
            padding: 0.8rem;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            margin: 0.5rem 0;
            overflow-x: auto;
        }

        .tutorial-step ul {
            margin: 0.8rem 0;
            padding-left: 1.5rem;
        }

        .tutorial-step li {
            margin-bottom: 0.5rem;
        }

        .example-url {
            background-color: var(--text-light);
            color: var(--text);
            padding: 0.5rem;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9rem;
            word-break: break-all;
            margin: 0.5rem 0;
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
        a {
            color: var(--primary);
            text-decoration: none;
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="card">
            <div class="header">
                <h1>Mon API</h1>
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
                <p>Utilisez cette clé pour accéder à l'API de Smart Pixel. <strong>Ne la partagez jamais. En cas de partage public (push git, article, etc), régénérez immédiatement votre clé et changer votre mdp.</strong></p>

                <div class="api-key-container">
                    <div class="api-key-display">
                        <code id="apiKey"><?= htmlspecialchars($user['api_key']) ?></code>
                        <button class="copy-button" onclick="copyToClipboard('apiKey')">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>

                <form method="POST" style="display: inline;">
                    <button type="submit" name="regenerate_api_key" class="regenerate-button">
                        <i class="fas fa-sync-alt"></i> Régénérer la clé
                    </button>
                </form>
            </div>

            <!-- Exemple d'URL -->
            <div class="api-key-container">
                <h3>Exemple d'URL</h3>
                <div class="api-key-display">
                    <code id="apiUrlExample">
                        https://gael-berru.com/smart_phpixel/smart_pixel_v2/public/api.php?&
                        site_id=<strong>SP_<?= htmlspecialchars(substr($user['api_key'], 0, 6)) ?></strong>?&
                        api_key=<strong>VOTRE_CLE_API</strong>?&
                        start_date=2026-01-01&
                        end_date=2026-02-01
                    </code>
                    <button class="copy-button" onclick="copyToClipboard('apiUrlExample')">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>

            <!-- Section Tutoriel -->
            <div class="tutorial-section">
                <h2><i class="fas fa-graduation-cap"></i> Tutoriel : Utiliser l'API Smart Pixel</h2>

                <!-- Étape 1 : Récupérer les identifiants -->
                <div class="tutorial-step">
                    <h3><i class="fas fa-key"></i> 1. Récupérer tes identifiants</h3>
                    <p>Pour utiliser l'API, tu as besoin de :</p>
                    <ul>
                        <li><strong>Code de tracking</strong> : Identifiant de ton site (ex: <code>SP_24m87bb</code>).</li>
                        <li><strong>Clé API</strong> : Clé secrète pour authentifier tes requêtes (ci-dessus).</li>
                    </ul>
                    <p>Tu peux trouver ton <strong>code de tracking</strong> dans la section "Mes sites" du dashboard.</p>
                </div>

                <!-- Étape 2 : Construire l'URL -->
                <div class="tutorial-step">
                    <h3><i class="fas fa-link"></i> 2. Construire l'URL de l'API</h3>
                    <p>L'URL de base est :</p>
                    <code>https://gael-berru.com/smart_phpixel/smart_pixel_v2/public/api.php</code>
                    <p>Ajoute les paramètres suivants :</p>
                    <ul>
                        <li><code>site_id</code> : Ton code de tracking (ex: <code>SP_24m87bb</code>).</li>
                        <li><code>api_key</code> : Ta clé API (copie-la ci-dessus).</li>
                        <li><code>start_date</code> (optionnel) : Date de début (ex: <code>2026-01-01</code>).</li>
                        <li><code>end_date</code> (optionnel) : Date de fin (ex: <code>2026-02-01</code>).</li>
                    </ul>
                    <p>Exemple d'URL complète :</p>
                    <div class="example-url">
                        https://gael-berru.com/smart_phpixel/smart_pixel_v2/public/api.php?
                        site_id=<strong>SP_24m87bb</strong>&
                        api_key=<strong>sk_1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p</strong>&
                        start_date=<strong>2026-01-01</strong>&
                        end_date=<strong>2026-02-01</strong>
                    </div>
                </div>

                <!-- Étape 3 : Récupérer les données -->
                <div class="tutorial-step">
                    <h3><i class="fas fa-download"></i> 3. Récupérer les données</h3>
                    <p>Tu peux récupérer les données de 3 manières :</p>
                    <ul>
                        <li><strong>Depuis un navigateur</strong> : Copie-colle l'URL dans la barre d'adresse, ou crée ton propre dashboard,</li><strong><a href="https://codepen.io/h-lautre/pen/EayBqeE?editors=1000" target="_blank">A partir de ton template</a></strong>.</li>
                        <li><strong>Avec cURL</strong> (terminal) :
                            <code>curl "https://gael-berru.com/.../api.php?site_id=SP_24m87bb&api_key=sk_1a2b3c..."</code>
                        </li>
                        <li><strong>Avec JavaScript</strong> (fetch) :
                            <code>
                                fetch(`https://gael-berru.com/.../api.php?site_id=SP_24m87bb&api_key=sk_1a2b3c...`)
                                .then(response => response.json())
                                .then(data => console.log(data));
                            </code>
                        </li>
                    </ul>
                </div>

                <!-- Étape 4 : Exemple de réponse -->
                <div class="tutorial-step">
                    <h3><i class="fas fa-file-code"></i> 4. Exemple de réponse JSON</h3>
                    <p>Voici un exemple de réponse :</p>
                    <code>
                        {
                        "success": true,
                        "data": [
                        {
                        "date": "2026-01-01",
                        "visits": 42,
                        "unique_visitors": 30,
                        "sessions": 35
                        },
                        {
                        "date": "2026-01-02",
                        "visits": 50,
                        "unique_visitors": 38,
                        "sessions": 40
                        }
                        ],
                        "meta": {
                        "site_id": "SP_24m87bb",
                        "start_date": "2026-01-01",
                        "end_date": "2026-02-01",
                        "total_visits": 92,
                        "total_unique_visitors": 68
                        }
                        }
                    </code>
                    <p>Les champs disponibles :</p>
                    <ul>
                        <li><code>date</code> : Date des données.</li>
                        <li><code>visits</code> : Nombre total de visites.</li>
                        <li><code>unique_visitors</code> : Visiteurs uniques (par IP).</li>
                        <li><code>sessions</code> : Nombre de sessions.</li>
                    </ul>
                </div>

                <!-- Étape 5 : Intégration avec des outils -->
                <div class="tutorial-step">
                    <h3><i class="fas fa-tools"></i> 5. Intégrer avec des outils</h3>
                    <p>Tu peux utiliser ces données avec :</p>
                    <ul>
                        <li><strong>Google Data Studio</strong> : Crée une source de données personnalisée.</li>
                        <li><strong>Excel/Google Sheets</strong> : Utilise <code>=IMPORTDATA("https://...")</code>.</li>
                        <li><strong>Tableau de bord custom</strong> : Utilise Chart.js (voir ci-dessous).</li>
                    </ul>
                    <p>Exemple de code pour un graphique avec Chart.js :</p>
                    <code>
                        &lt;canvas id="visitsChart" width="800" height="400"&gt;&lt;/canvas&gt;
                        &lt;script src="https://cdn.jsdelivr.net/npm/chart.js"&gt;&lt;/script&gt;
                        &lt;script&gt;
                        fetch(`https://gael-berru.com/.../api.php?site_id=SP_24m87bb&api_key=sk_1a2b3c...`)
                        .then(response => response.json())
                        .then(data => {
                        const labels = data.data.map(item => item.date);
                        const visits = data.data.map(item => item.visits);
                        new Chart(document.getElementById('visitsChart'), {
                        type: 'line',
                        data: { labels, datasets: [{ label: 'Visites', data: visits }] }
                        });
                        });
                        &lt;/script&gt;
                    </code>
                </div>

                <!-- Étape 6 : Gérer les erreurs -->
                <div class="tutorial-step">
                    <h3><i class="fas fa-exclamation-triangle"></i> 6. Gérer les erreurs</h3>
                    <p>Voici les erreurs possibles et leurs solutions :</p>
                    <ul>
                        <li><strong>400</strong> : Paramètres manquants. Vérifie l'URL.</li>
                        <li><strong>403</strong> : Clé API ou code de tracking invalide. Vérifie tes identifiants.</li>
                        <li><strong>404</strong> : Site non trouvé. Vérifie le <code>site_id</code>.</li>
                        <li><strong>500</strong> : Erreur serveur. Contacte le support.</li>
                    </ul>
                </div>

                <!-- Étape 7 : Doc -->
                <div class="tutorial-step">
                    <h3><i class="fa-regular fa-folder-open"></i></i> 7. Documentation complète :</h3>
                    <p>Pour plus de détails sur les paramètres, les data, et les limites de l'API, consulte notre documentation complète :</p>
                    <a href="dashboard.php" class="back-button">
                        <i class="fas fa-arrow-left"></i> Retour au dashboard
                    </a>
                    <a href="../../doc/" class="back-button">
                        La Documentation <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

            </div>
        </div>
    </div>

    <script>
        // Copier dans le presse-papiers
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent || element.value;
            navigator.clipboard.writeText(text)
                .then(() => {
                    const toast = document.createElement('div');
                    toast.className = 'toast';
                    toast.innerHTML = '<i class="fas fa-check-circle"></i> Copié dans le presse-papiers !';
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