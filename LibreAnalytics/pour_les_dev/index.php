<?php
/*
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
$user = $stmt->fetch();*/
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LibreAnalytics - Assistant IA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="author" content="Gael Berru.">
    <meta name="robots" content="index, follow">
    <style>
        /* Styles chat ia */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f9fafb;
            --bg-sidebar: #f5f5f5;
            --text-primary: #333333;
            --text-secondary: #6b7280;
            --text-light: #9ca3af;
            --border-color: #e5e7eb;
            --accent-color: #9d86ff;
            --accent-hover: #4ecdc4;
            --success: #4ecdc4;
            --warning: #f59e0b;
            --error: #ff6b8b;
            --chat-user: #e5e7eb;
            --chat-bot: #9d86ff;
            --chat-user-text: #333333;
            --chat-bot-text: #ffffff;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --transition: all 0.2s ease;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.5;
            height: 100vh;
            overflow: hidden;
        }

        .app-container {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 300px;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            transition: var(--transition);
            position: relative;
            z-index: 10;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 1.25rem;
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            background: var(--accent-color);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem 1rem;
        }

        .nav-section {
            margin-bottom: 1.5rem;
        }

        .nav-section h3 {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            padding: 0 0.75rem;
            margin-bottom: 0.5rem;
        }

        .nav-section ul {
            list-style: none;
        }

        .nav-section li {
            margin-bottom: 0.25rem;
        }

        .nav-section a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.625rem 0.75rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 6px;
            transition: var(--transition);
            font-size: 0.875rem;
        }

        .nav-section a i {
            width: 20px;
            font-size: 1rem;
            color: var(--text-light);
        }

        .nav-section a:hover {
            background: var(--border-color);
            color: var(--text-primary);
        }

        .nav-section a.active {
            background: var(--accent-color);
            color: white;
        }

        .nav-section a.active i {
            color: white;
        }

        .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--accent-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .user-info {
            flex: 1;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-primary);
        }

        .user-email {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        /* Main Chat Area */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--bg-primary);
            position: relative;
            height: 100vh;
            overflow: hidden;
        }

        .chat-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background: var(--bg-primary);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chat-header-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .chat-header h2 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .online-status {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .chat-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: var(--bg-primary);
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-icon:hover {
            background: var(--bg-secondary);
            color: var(--text-primary);
        }
        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 0.5rem;
            padding: 0.5rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background: var(--bg-primary);
        }

        .quick-action {
            padding: 0.375rem 0.75rem;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            font-size: 0.75rem;
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition);
            white-space: nowrap;
        }

        .quick-action:hover {
            background: var(--accent-color);
            color: white;
            border-color: var(--accent-color);
        }

        /* Chat Messages */
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            background: var(--bg-secondary);
        }

        .message {
            display: flex;
            gap: 1rem;
            max-width: 80%;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.user {
            margin-left: auto;
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--text-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        .message.user .message-avatar {
            background: var(--accent-color);
        }

        .message.bot .message-avatar {
            background: var(--text-secondary);
        }

        .message-content {
            background: var(--bg-primary);
            padding: 0.75rem 1rem;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            position: relative;
        }

        .message.user .message-content {
            background: var(--chat-bot);
            color: var(--chat-user-text);
        }

        .message.bot .message-content {
            background: var(--chat-user);
            color: var(--chat-user-text);
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.375rem;
            font-size: 0.75rem;
        }

        .message.user .message-header {
            color: rgba(255, 255, 255, 0.8);
        }

        .message.bot .message-header {
            color: var(--text-secondary);
        }

        .message-author {
            font-weight: 600;
        }

        .message-time {
            font-size: 0.675rem;
        }

        .message-text {
            font-size: 0.9375rem;
            line-height: 1.5;
        }

        .message-text pre {
            background: rgba(0, 0, 0, 0.05);
            padding: 0.5rem;
            border-radius: 6px;
            overflow-x: auto;
            margin: 0.5rem 0;
        }

        .message.user .message-text pre {
            background: rgba(255, 255, 255, 0.1);
        }

        .message-text code {
            font-family: monospace;
            font-size: 0.875rem;
        }

        /* Chat Input Area */
        .chat-input-container {
            padding: 1rem 1.5rem;
            background: var(--bg-primary);
            border-top: 1px solid var(--border-color);
        }

        .input-wrapper {
            display: flex;
            gap: 0.75rem;
            align-items: flex-end;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 0.5rem;
            transition: var(--transition);
        }

        .input-wrapper:focus-within {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .message-input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 0.5rem;
            font-size: 0.9375rem;
            color: var(--text-primary);
            outline: none;
            resize: none;
            max-height: 120px;
            font-family: inherit;
        }

        .message-input::placeholder {
            color: var(--text-light);
        }

        .input-actions {
            display: flex;
            gap: 0.25rem;
            align-items: center;
        }

        .btn-send {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--accent-color);
            border: none;
            color: white;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-send:hover {
            background: var(--accent-hover);
            transform: scale(1.05);
        }

        .btn-send:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Search Results Dropdown */
        .search-results {
            position: absolute;
            bottom: 100%;
            left: 1.5rem;
            right: 1.5rem;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 0.5rem;
            display: none;
            z-index: 100;
        }

        .search-result-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: var(--transition);
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-item:hover {
            background: var(--bg-secondary);
        }

        .search-result-item h4 {
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
            color: var(--text-primary);
        }

        .search-result-item p {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        /* Typing Indicator */
        .typing-indicator {
            display: flex;
            gap: 0.375rem;
            padding: 0.5rem 1rem;
            background: var(--bg-primary);
            border-radius: 20px;
            width: fit-content;
        }

        .typing-dot {
            width: 8px;
            height: 8px;
            background: var(--text-light);
            border-radius: 50%;
            animation: typingBounce 1.4s infinite ease-in-out;
        }

        .typing-dot:nth-child(1) { animation-delay: -0.32s; }
        .typing-dot:nth-child(2) { animation-delay: -0.16s; }

        @keyframes typingBounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.5rem;
        }

        /* Styles pour la documentation dans le chat */
        .doc-section-content {
            background: var(--bg-primary);
            border-radius: 8px;
            padding: 1rem;
            margin: 0.5rem 0;
            border: 1px solid var(--border-color);
        }

        .doc-section-content h1 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .doc-section-content h2 {
            font-size: 1.25rem;
            margin: 1rem 0 0.5rem;
            color: var(--text-primary);
        }

        .doc-section-content h3 {
            font-size: 1.1rem;
            margin: 0.75rem 0 0.5rem;
            color: var(--text-primary);
        }

        .doc-section-content .card {
            background: var(--bg-secondary);
            border-radius: 8px;
            padding: 1rem;
            margin: 0.5rem 0;
            border: 1px solid var(--border-color);
        }

        .doc-section-content .card-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .doc-section-content .code-block {
            background: var(--bg-secondary);
            border-radius: 8px;
            margin: 0.5rem 0;
            border: 1px solid var(--border-color);
        }

        .doc-section-content .code-header {
            padding: 0.5rem 1rem;
            background: var(--border-color);
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
        }

        .doc-section-content .copy-btn {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.75rem;
            transition: var(--transition);
        }

        .doc-section-content .copy-btn:hover {
            background: var(--accent-hover);
        }

        .doc-section-content pre {
            padding: 1rem;
            overflow-x: auto;
            font-size: 0.875rem;
            background: var(--bg-primary);
            margin: 0;
        }

        .doc-section-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 0.5rem 0;
            font-size: 0.875rem;
        }

        .doc-section-content th {
            background: var(--bg-secondary);
            padding: 0.5rem;
            text-align: left;
            font-weight: 600;
        }

        .doc-section-content td {
            padding: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .doc-section-content .alert {
            padding: 1rem;
            border-radius: 8px;
            margin: 0.5rem 0;
        }

        .doc-section-content .alert-info {
            background: rgba(37, 99, 235, 0.1);
            border: 1px solid var(--accent-color);
        }

        .doc-section-content .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid var(--success);
        }

        .doc-section-content .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid var(--warning);
        }

        .doc-section-content .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }

        .doc-section-content .feature-item {
            text-align: center;
            padding: 1rem;
            background: var(--bg-secondary);
            border-radius: 8px;
        }

        .doc-section-content .feature-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--accent-color);
        }

        .doc-section-content .version-badge {
            background: var(--accent-color);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }

        .doc-section-content .tutorial-step {
            margin: 1.5rem 0;
            padding: 1rem;
            background: var(--bg-secondary);
            border-radius: 8px;
        }

        .doc-section-content .tutorial-step h3 {
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: -300px;
                top: 0;
                bottom: 0;
                box-shadow: var(--shadow-md);
            }

            .sidebar.active {
                left: 0;
            }

            .mobile-menu-btn {
                display: block;
            }

            .message {
                max-width: 90%;
            }
        }

        @media (max-width: 640px) {
            .sidebar {
                width: 100%;
                left: -100%;
            }

            .message {
                max-width: 95%;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="../index.php" class="logo">
                    <div class="logo-icon">
                        <i class="fa-regular fa-folder-open"></i>
                    </div>
                    <span>LibreAnalytics <span style="font-weight: 400;">Assistant</span></span>
                </a>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <h3>🚀 DÉMARRAGE</h3>
                    <ul>
                        <li><a href="#" class="nav-link" data-section="introduction"><i class="fas fa-rocket"></i> Introduction</a></li>
                        <li><a href="#" class="nav-link" data-section="installation"><i class="fas fa-code"></i> Installation 2min</a></li>
                        <li><a href="#" class="nav-link" data-section="premiers-pas"><i class="fas fa-shoe-prints"></i> Premiers pas</a></li>
                        <li><a href="#" class="nav-link" data-section="plans"><i class="fas fa-tags"></i> Plans & Tarifs</a></li>
                    </ul>
                </div>

                <div class="nav-section">
                    <h3>⚡ FONCTIONNALITÉS</h3>
                    <ul>
                        <li><a href="#" class="nav-link" data-section="dashboard"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
                        <li><a href="#" class="nav-link" data-section="evenements"><i class="fas fa-mouse-pointer"></i> Événements & Clics</a></li>
                        <li><a href="#" class="nav-link" data-section="geolocalisation"><i class="fas fa-map-marker-alt"></i> Géolocalisation</a></li>
                        <li><a href="#" class="nav-link" data-section="sources"><i class="fas fa-link"></i> Sources trafic & UTM</a></li>
                        <li><a href="#" class="nav-link" data-section="multi-sites"><i class="fas fa-globe"></i> Multi-sites</a></li>
                    </ul>
                </div>

                <div class="nav-section">
                    <h3>🔧 INTÉGRATION</h3>
                    <ul>
                        <li><a href="#" class="nav-link" data-section="script-js"><i class="fab fa-js"></i> Tracker.js</a></li>
                        <li><a href="#" class="nav-link" data-section="pixel-php"><i class="fas fa-database"></i> Pixel PHP</a></li>
                        <li><a href="#" class="nav-link" data-section="api"><i class="fas fa-plug"></i> API REST</a></li>
                        <li><a href="#" class="nav-link" data-section="webhooks"><i class="fas fa-webhook"></i> Webhooks</a></li>
                    </ul>
                </div>

                <div class="nav-section">
                    <h3>👤 ADMINISTRATION</h3>
                    <ul>
                        <li><a href="#" class="nav-link" data-section="compte"><i class="fas fa-user-cog"></i> Gestion compte</a></li>
                        <li><a href="#" class="nav-link" data-section="paiement"><i class="fas fa-credit-card"></i> Paiement</a></li>
                        <li><a href="#" class="nav-link" data-section="rgpd"><i class="fas fa-shield-alt"></i> RGPD</a></li>
                    </ul>
                </div>

                <div class="nav-section">
                    <h3>❓ DÉPANNAGE</h3>
                    <ul>
                        <li><a href="#" class="nav-link" data-section="faq"><i class="fas fa-question-circle"></i> FAQ</a></li>
                        <li><a href="#" class="nav-link" data-section="erreurs"><i class="fas fa-exclamation-triangle"></i> Codes erreur</a></li>
                        <li><a href="#" class="nav-link" data-section="support"><i class="fas fa-headset"></i> Support</a></li>
                    </ul>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-info">
                    <div class="user-name">Utilisateur</div>
                    <div class="user-email"><?php echo isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'non connecté'; ?></div>
                </div>
            </div>
        </aside>

        <!-- Main Chat Area -->
        <main class="chat-main">
            <!-- Chat Header -->
            <header class="chat-header">
                <div class="chat-header-left">
                    <button class="mobile-menu-btn" id="mobileMenuBtn">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h2>Assistant LibreAnalytics</h2>
                        <div class="online-status">
                            <span class="status-dot"></span>
                            <span>En ligne</span>
                        </div>
                    </div>
                </div>
                <div class="chat-actions">
                    <button class="btn-icon" onclick="clearChat()" title="Nouvelle conversation">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button class="btn-icon" onclick="window.open('https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/dashboard.php', '_blank')" title="Dashboard">
                        <i class="fas fa-chart-pie"></i>
                    </button>
                </div>
            </header>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <span class="quick-action" onclick="askQuestion('Comment installer LibreAnalytics ?')">Installation</span>
                <span class="quick-action" onclick="askQuestion('Quels sont les tarifs ?')">Tarifs</span>
                <span class="quick-action" onclick="askQuestion('Comment utiliser l\'API ?')">API</span>
                <span class="quick-action" onclick="askQuestion('Problème de tracking')">Support</span>
                <span class="quick-action" onclick="askQuestion('RGPD et conformité')">RGPD</span>
            </div>

            <!-- Chat Messages -->
            <div class="chat-messages" id="chatMessages">
                <!-- Welcome Message -->
                <div class="message bot">
                    <div class="message-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="message-content">
                        <div class="message-header">
                            <span class="message-author">Assistant LibreAnalytics</span>
                            <span class="message-time">À l'instant</span>
                        </div>
                        <div class="message-text">
                            <p>👋 Bonjour ! Je suis l'assistant virtuel de LibreAnalytics.</p>
                            <p>Je peux vous aider avec :</p>
                            <ul style="margin: 0.5rem 0 0 1.5rem;">
                                <li>L'installation et la configuration</li>
                                <li>Les fonctionnalités du dashboard</li>
                                <li>L'intégration technique (JS, PHP, API)</li>
                                <li>La gestion de compte et les tarifs</li>
                                <li>Le dépannage et la FAQ</li>
                            </ul>
                            <p>Comment puis-je vous aider aujourd'hui ?</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat Input with Search -->
            <div class="chat-input-container">
                <div class="search-results" id="searchResults"></div>
                <div class="input-wrapper">
                    <textarea 
                        class="message-input" 
                        id="messageInput" 
                        placeholder="Taper des mots clefs, ex : api" 
                        rows="1"
                        oninput="autoResize(this)"
                    ></textarea>
                    <div class="input-actions">
                        <button class="btn-send" id="sendButton" onclick="sendMessage()">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-light); margin-top: 0.5rem; text-align: center;">
                    <i class="fas fa-robot"></i> Assistant IA - Documentation interactive
                </div>
            </div>
        </main>
    </div>

    <script>
        // Données de recherche (conservées de la version originale)
        const searchData = [
            { title: "Installation en 2 minutes", section: "installation", content: "Créer un compte, récupérer tracking code, coller script", tags: "installer configurer tracker" },
            { title: "Plans et tarifs", section: "plans", content: "Gratuit 1 site, Pro 9€, Business 29€, limites visites", tags: "prix abonnement payer" },
            { title: "Tracking des clics", section: "evenements", content: "Clics automatiques, événements personnalisés, API JavaScript", tags: "click event conversion" },
            { title: "Géolocalisation", section: "geolocalisation", content: "Pays, ville via IP, ip-api.com, anonymisation", tags: "geo ip pays ville" },
            { title: "Sources UTM", section: "sources", content: "utm_source, utm_medium, utm_campaign, referrer", tags: "campagne marketing tracking" },
            { title: "Tracker.js", section: "script-js", content: "Fichier JS, fonctions, paramètres URL du pixel", tags: "javascript script api" },
            { title: "Pixel.php", section: "pixel-php", content: "Point d'entrée serveur, GIF 1x1, insertion base", tags: "backend php gif" },
            { title: "API REST", section: "api", content: "Endpoints, authentification, export JSON/CSV", tags: "api rest json csv developpeur" },
            { title: "Webhooks", section: "webhooks", content: "Notifications temps réel, événements, configuration", tags: "webhook realtime alert" },
            { title: "Multi-sites", section: "multi-sites", content: "Gérer plusieurs sites par compte, tracking code par site", tags: "plusieurs sites domains" },
            { title: "RGPD", section: "rgpd", content: "Conformité, données en France, pas de cookies tiers", tags: "gdpr privacy cookies" },
            { title: "Paiement LemonSqueezy", section: "paiement", content: "Processus checkout, webhook de confirmation", tags: "payment lemon squeezy carte" },
            { title: "FAQ", section: "faq", content: "Questions fréquentes : gratuit, auto-hébergement, données", tags: "questions aide" },
            { title: "Codes erreur", section: "erreurs", content: "ERR_INVALID_TRACKING, ERR_SITE_INACTIVE, dépannage", tags: "error bug problème" },
            { title: "Support", section: "support", content: "Email, GitHub, Discord, délais de réponse", tags: "contact aide assistance" },
            { title: "Dashboard", section: "dashboard", content: "Onglets, métriques, filtres période, gestion sites", tags: "interface graphique stats" },
            { title: "Premiers pas", section: "premiers-pas", content: "Comprendre les métriques, utiliser les filtres", tags: "debutant guide" },
            { title: "Gestion compte", section: "compte", content: "Mot de passe, ajout site, clé API", tags: "account profil settings" },
        ];

        // Contenu complet des sections (basé sur la doc originale)
        const sectionContent = {
            introduction: `
                <h1>Bienvenue sur LibreAnalytics <span class="version-badge">v2.0.1</span></h1>

                <div class="alert alert-info">
                    <strong>Mise à jour du 15/01/2026 :</strong> Le pixel est maintenant multi-tenant, l'API REST est en
                    bêta, et l'intégration LemonSqueezy sera active une fois la beta test terminée. Pour le moment
                    l'outils reste gratuit.
                </div>

                <p><strong>LibreAnalytics</strong> est une solution d'analytics web souveraine, open-source et respectueuse
                    de la vie privée. Conçue comme une alternative souveraine à Google Analytics, elle vous permet de
                    reprendre le contrôle de vos données tout en bénéficiant d'un dashboard simple et intuitif.</p>

                <div class="features-grid">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-flag"></i></div>
                        <h3>100% Français</h3>
                        <p>Code et données hébergés en France. Aucune fuite vers les GAFAM.</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                        <h3>RGPD natif</h3>
                        <p>Pas de cookie banner nécessaire. Anonymisation par défaut.</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                        <h3>Script 4KB</h3>
                        <p>Impact zéro sur les performances et le Core Web Vitals.</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-code-branch"></i></div>
                        <h3>Open source</h3>
                        <p>Code auditable sur GitHub. Vous pouvez même auto-héberger.</p>
                    </div>
                </div>
            `,
            
            installation: `
                <h2>Installation en 2 minutes</h2>

                <div class="card">
                    <div class="card-title">
                        <i class="fas fa-check-circle" style="color: var(--success);"></i>
                        Étape 1 : Créer un compte
                    </div>
                    <p>Rendez-vous sur <a href="../index.php">la page d'accueil</a> et cliquez sur "Créer mon premier
                        dashboard". Remplissez le formulaire avec votre email, choisissez un mot de passe et indiquez
                        l'URL de votre site.</p>
                </div>

                <div class="card">
                    <div class="card-title">
                        <i class="fas fa-check-circle" style="color: var(--success);"></i>
                        Étape 2 : Récupérer votre code de tracking
                    </div>
                    <p>Une fois connecté, vous arrivez sur le dashboard. Vous verrez votre <span
                            class="highlight">tracking code</span> (ex: <code>SP_79747769</code>), situé en bas à gauche
                        de l'écran.</p>
                </div>

                <div class="card">
                    <div class="card-title">
                        <i class="fas fa-check-circle" style="color: var(--success);"></i>
                        Étape 3 : Installer le script
                    </div>
                    <p>Copiez-collez la ligne suivante juste avant la balise <code>&lt;/head&gt;</code> de votre site :
                    </p>

                    <div class="code-block">
                        <div class="code-header">
                            <span><i class="fas fa-code"></i> tracker.js</span>
                            <button class="copy-btn" onclick="copyToClipboard('<!-- LibreAnalytics -->\\n<script data-sp-id=\\"SP_79747769\\" src=\\"https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/tracker.js\\" async><\\/script>')">
                                <i class="fas fa-copy"></i> Copier
                            </button>
                        </div>
                        <pre><code>&lt;!-- LibreAnalytics --&gt;
&lt;script data-sp-id="SP_24031987" src="https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/tracker.js" async&gt;&lt;/script&gt;</code></pre>
                    </div>
                </div>

                <div class="alert alert-success">
                    <strong>✅ C'est fini !</strong> Les premières données apparaîtront dans votre dashboard sous 1 à 2
                    minutes mais peuvent dans certain cas, prendre jusqu'à 24H. Le script collecte automatiquement :
                    pages vues, clics, source, UTM, géolocalisation, appareil, navigateur...
                </div>
            `,
            
            premiersPas: `
                <h2>Premiers pas</h2>

                <h3>Comprendre les métriques</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Métrique</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Visites</strong></td>
                                <td>Nombre total de sessions (une session = 30 min d'inactivité max).</td>
                            </tr>
                            <tr>
                                <td><strong>Visiteurs uniques</strong></td>
                                <td>Nombre d'utilisateurs distincts (basé sur session ID + empreinte).</td>
                            </tr>
                            <tr>
                                <td><strong>Pages vues</strong></td>
                                <td>Nombre total de pages consultées.</td>
                            </tr>
                            <tr>
                                <td><strong>Taux de rebond</strong></td>
                                <td>% de visites avec une seule page.</td>
                            </tr>
                            <tr>
                                <td><strong>Insight</strong></td>
                                <td>Actions à mettre en place selon vos data.</td>
                            </tr>
                            <tr>
                                <td><strong>Source</strong></td>
                                <td>D'où viennent vos visiteurs (Google, direct, réseau social...).</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3>Filtres de période</h3>
                <p>En haut du dashboard, vous pouvez sélectionner : Aujourd'hui, 7 derniers jours, 30 derniers jours, ou
                    une plage d'un an.</p>

                <div class="alert alert-info">
                    <i class="fas fa-lightbulb"></i> <strong>Astuce :</strong> Passez la souris sur les graphiques pour
                    voir les valeurs précises. Les tableaux sous les graphiques sont triables par colonne.
                </div>
            `,
            
            plans: `
                <h2>Plans et tarifs</h2>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Fonctionnalité</th>
                                <th>Gratuit</th>
                                <th>Pro (9€/mois) Version à venir !</th>
                                <th>Business (29€/mois) Version à venir !</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Nombre de sites</td>
                                <td>1</td>
                                <td>10</td>
                                <td>50</td>
                            </tr>
                            <tr>
                                <td>Visites / mois</td>
                                <td>1 000</td>
                                <td>100 000</td>
                                <td>Illimité</td>
                            </tr>
                            <tr>
                                <td>Dashboard temps réel</td>
                                <td>✅</td>
                                <td>✅</td>
                                <td>✅</td>
                            </tr>
                            <tr>
                                <td>Géolocalisation (pays/ville)</td>
                                <td>✅</td>
                                <td>✅</td>
                                <td>✅</td>
                            </tr>
                            <tr>
                                <td>Tracking UTM</td>
                                <td>✅</td>
                                <td>✅</td>
                                <td>✅</td>
                            </tr>
                            <tr>
                                <td>API REST</td>
                                <td>❌</td>
                                <td>✅</td>
                                <td>✅</td>
                            </tr>
                            <tr>
                                <td>Webhooks</td>
                                <td>❌</td>
                                <td>❌</td>
                                <td>✅</td>
                            </tr>
                            <tr>
                                <td>Support</td>
                                <td>Communauté</td>
                                <td>Email 24h</td>
                                <td>Téléphone prioritaire</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p>Le paiement est géré par <strong>Lemon Squeezy</strong> (paiement européen, pas de commission USA).
                    Nous ne stockons aucune information de carte bancaire.</p>

                <p>Pour passer en Pro/Business : <code>Dashboard → Mon compte → Mise à niveau</code>. Le changement est
                    instantané.</p>
            `,
            
            dashboard: `
                <h2>Utilisation du dashboard</h2>

                <h3>Onglets disponibles</h3>
                <ul>
                    <li><strong>Aperçu :</strong> Vue d'ensemble avec les métriques clés, graphique d'évolution, top
                        sources, top pages.</li>
                    <li><strong>Trafic :</strong> Analyse détaillée des sources (référents, réseaux sociaux, campagnes).
                    </li>
                    <li><strong>Audience :</strong> Géolocalisation, appareils, navigateurs, résolution d'écran.</li>
                    <li><strong>Comportement :</strong> Pages populaires, flux de navigation (à venir), clics
                        enregistrés.</li>
                    <li><strong>Événements :</strong> Liste de tous les événements personnalisés (clics, formulaires,
                        etc).</li>
                </ul>

                <div class="card">
                    <div class="card-title"><i class="fas fa-mouse-pointer"></i> Gestion des sites</div>
                    <p>Dans la colonne de gauche, vous voyez la liste de vos sites. Cliquez sur un site pour visualiser
                        ses données. Le <span class="badge badge-info">code de suivi</span> affiché est unique pour
                        chaque site.</p>
                </div>
            `,
            
            evenements: `
                <h2>Tracking des clics et événements</h2>

                <p>LibreAnalytics tracke automatiquement tous les clics sur les liens et boutons, CTA (sauf si vous avez
                    installé <code>data-sp-ignore</code>). Vous pouvez également envoyer des événements personnalisés.
                </p>

                <h3>Événements automatiques</h3>
                <ul>
                    <li><strong>Clics :</strong> tag, id, class, texte, href, position (x, y).</li>
                    <li><strong>Page view :</strong> titre, URL, referrer.</li>
                </ul>

                <h3>Événements personnalisés (JS)</h3>
                <div class="code-block">
                    <div class="code-header">
                        <span>JavaScript</span>
                        <button class="copy-btn"
                            onclick="copyToClipboard('// Envoyer un événement personnalisé\\nSmartPixel.trackEvent(\\'inscription\\', {\\n  method: \\'email\\',\\n  user_id: 123\\n});')">
                            <i class="fas fa-copy"></i> Copier
                        </button>
                    </div>
                    <pre><code>// Envoyer un événement personnalisé
SmartPixel.trackEvent('inscription', {
  method: 'email',
  user_id: 123
});</code></pre>
                </div>

                <div class="alert alert-warning">
                    <strong>Important :</strong> Les événements ne sont envoyés qu'après le chargement complet de la
                    page (évite les doublons). L'objet eventData est limité à 500 caractères.
                </div>
            `,
            
            geolocalisation: `
                <h2>Géolocalisation</h2>

                <p>La géolocalisation est effectuée côté serveur via l'API <code>ip-api.com</code> (limitation : 45
                    req/min en gratuit). Les données sont stockées en base (pays, ville).</p>

                <h3>Comment ça marche ?</h3>
                <ol>
                    <li>Le pixel reçoit l'IP du visiteur.</li>
                    <li>Une requête est faite à ip-api.com (timeout 1s pour ne pas bloquer).</li>
                    <li>Le pays et la ville sont enregistrés dans la table <code>smart_pixel_tracking</code>.</li>
                    <li>Si l'API échoue, la valeur par défaut est "Unknown".</li>
                </ol>

                <div class="alert alert-info">
                    <strong>Vie privée :</strong> Nous ne stockons que le pays et la ville. L'IP publique n'est pas
                    conservée dans les rapports (elle sert uniquement à la géoloc), concernant l'IP privée elle n'est
                    évidement pas accessible pour des raison de sécurité et de normes RGPD. Vous pouvez désactiver la
                    géoloc dans votre<code>config.php</code>.
                </div>
            `,
            
            sources: `
                <h2>Sources de trafic et paramètres UTM</h2>

                <p>LibreAnalytics capture automatiquement les paramètres UTM de l'URL et les sources.</p>

                <h3>Paramètres reconnus</h3>
                <ul>
                    <li><code>utm_source</code> → source (Google, newsletter, etc.)</li>
                    <li><code>utm_medium</code> → medium (cpc, email, social)</li>
                    <li><code>utm_campaign</code> → nom de la campagne</li>
                    <li><code>utm_term</code> → mots-clés</li>
                    <li><code>utm_content</code> → contenu spécifique</li>
                </ul>

                <h3>Source automatique</h3>
                <p>Cela vous permet de savoir laquelle de vos campagnes à le plus de trafic et d'où vient ce traffic. Si
                    aucun UTM n'est présent, la source est extraite du <code>document.referrer</code> :</p>
                <ul>
                    <li>Réseaux sociaux : Facebook, Twitter, LinkedIn → "social"</li>
                    <li>Moteurs de recherche : Google, Bing, DuckDuckGo → "organic"</li>
                    <li>Direct : pas de referrer → "direct"</li>
                </ul>
            `,
            
            scriptJs: `
                <h2>Tracker - Documentation technique</h2>

                <p>Notre code <code>JavaScript</code> est le cœur de la collecte côté client. Il est conçu pour être
                    léger (4KB) et asynchrone.</p>

                <h3>Fonctions disponibles</h3>
                <div class="code-block">
                    <div class="code-header">
                        <span>API JavaScript</span>
                        <button class="copy-btn"
                            onclick="copyToClipboard('SmartPixel.load(\\'SP_XXXXXX\\'); // Chargement manuel\\nSmartPixel.trackEvent(\\'eventName\\', {data}); // Événement personnalisé\\nSmartPixel.getOrCreateSessionId(); // Récupère l\\'ID de session')">
                            <i class="fas fa-copy"></i> Copier
                        </button>
                    </div>
                    <pre><code>SmartPixel.load('SP_XXXXXX'); // Chargement manuel
SmartPixel.trackEvent('eventName', {data}); // Événement personnalisé
SmartPixel.getOrCreateSessionId(); // Récupère l'ID de session</code></pre>
                </div>

                <h3>Paramètres de l'URL du pixel</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Paramètre</th>
                                <th>Description</th>
                                <th>Exemple</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>t</td>
                                <td>Tracking code (obligatoire)</td>
                                <td>SP_79747769</td>
                            </tr>
                            <tr>
                                <td>sid</td>
                                <td>Session ID</td>
                                <td>sess_abc123</td>
                            </tr>
                            <tr>
                                <td>viewport</td>
                                <td>Résolution écran</td>
                                <td>1920x1080</td>
                            </tr>
                            <tr>
                                <td>s</td>
                                <td>Source</td>
                                <td>google.com</td>
                            </tr>
                            <tr>
                                <td>utm_campaign</td>
                                <td>Campagne</td>
                                <td>ete2025</td>
                            </tr>
                            <tr>
                                <td>ref</td>
                                <td>Referrer complet</td>
                                <td>https://... </td>
                            </tr>
                            <tr>
                                <td>click</td>
                                <td>Données de clic (JSON)</td>
                                <td>{"tag":"A"}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `,
            
            pixelPhp: `
                <h2>Pixel.php - Point d'entrée serveur</h2>

                <p>Notre code <code>PHP</code> reçoit les données, valide le tracking code, enrichit avec la géoloc, et
                    insère en base. Il retourne toujours un Pixel transparent.</p>

                <h3>Fonctionnement</h3>
                <ol>
                    <li>Vérification du paramètre <code>t</code> (tracking code).</li>
                    <li>Requête en base pour trouver le <code>site_id</code> et <code>user_id</code>.</li>
                    <li>Récupération de l'IP et appel à ip-api.com pour la géoloc (timeout 1s).</li>
                    <li>Insertion en base avec toutes les données collectées.</li>
                    <li>Envoi du GIF 1x1.</li>
                </ol>

                <h3>Optimisation</h3>
                <ul>
                    <li>Le script est optimisé pour < 100ms de réponse.</li>
                    <li>Les erreurs sont loggées silencieusement (pas d'affichage).</li>
                    <li>Le cache est désactivé (headers no-cache).</li>
                </ul>
            `,
            
            api: `
                <h2>🔌 API REST (Pro & Business) Fonctionnalitées en beta test</h2>

                <p>L'API REST vous permet d'accéder à vos données programmatiquement. Elle est en bêta depuis janvier
                    2026.</p>

                <h3>Authentification</h3>
                <p>Utilisez votre <code>api_key</code> (disponible dans Mon compte <svg width="24" height="24"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg> → API).</p>

                <!-- Section Tutoriel -->
                <div class="tutorial-section">
                    <h2><i class="fas fa-graduation-cap"></i> Tutoriel : Utiliser l'API LibreAnalytics</h2>

                    <!-- Étape 1 : Récupérer les identifiants -->
                    <div class="tutorial-step">
                        <h3><i class="fas fa-key"></i> 1. Récupérer tes identifiants</h3>
                        <p>Pour utiliser l'API, tu as besoin de :</p>
                        <ul>
                            <li><strong>Code de tracking</strong> : Identifiant de ton site (ex:
                                <code>SP_24m87bb</code>).
                            </li>
                            <li><strong>Clé API</strong> : Clé secrète pour authentifier tes requêtes (ci-dessus).</li>
                        </ul>
                        <p>Tu peux trouver ton <strong>code de tracking</strong> dans la section "Mes sites" du
                            dashboard.</p>
                    </div>

                    <!-- Étape 2 : Construire l'URL -->
                    <div class="tutorial-step">
                        <h3><i class="fas fa-link"></i> 2. Construire l'URL de l'API</h3>
                        <p>L'URL de base est :</p>
                        <code>https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/api.php</code>
                        <p>Ajoute les paramètres suivants :</p>
                        <ul>
                            <li><code>site_id</code> : Ton code de tracking (ex: <code>SP_24m87bb</code>).</li>
                            <li><code>api_key</code> : Ta clé API (copie-la ci-dessus).</li>
                            <li><code>start_date</code> (optionnel) : Date de début (ex: <code>2026-01-01</code>).</li>
                            <li><code>end_date</code> (optionnel) : Date de fin (ex: <code>2026-02-01</code>).</li>
                        </ul>
                        <div class="code-block">
                            <div class="code-header">
                                <span>Exemple d'URL complète :</span>
                                <button class="copy-btn"
                                    onclick="copyToClipboard('https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/api.php?site_id=SP_24m87bb&api_key=sk_1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p&start_date=2026-01-01&end_date=2026-02-01')">
                                    <i class="fas fa-copy"></i> Copier
                                </button>
                            </div>
                            <pre><code>https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/api.php?site_id=<strong>SP_24m87bb</strong>&api_key=<strong>sk_1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p</strong>&start_date=<strong>2026-01-01</strong>&end_date=<strong>2026-02-01</strong></code></pre>
                        </div>

                        <!-- Étape 3 : Récupérer les données -->
                        <div class="tutorial-step">
                            <h3><i class="fas fa-download"></i> 3. Récupérer les données</h3>
                            <p>Tu peux récupérer les données de 3 manières :</p>
                            <ul>
                                <li><strong>Depuis un navigateur</strong> : Copie-colle l'URL dans la barre d'adresse,
                                    ou
                                    crée ton propre dashboard,</li>
                                <li><strong><a href="https://codepen.io/h-lautre/pen/EayBqeE?editors=1000">Avec notre
                                            template</a></strong>.</li>
                                <li><strong>Avec cURL</strong> (terminal) :
                                    <code>curl "https://gael-berru.com/.../api.php?site_id=SP_24m87bb&api_key=sk_1a2b3c..."</code>
                                </li>
                                <li><strong>Avec JavaScript</strong> (fetch) :
                                    <code>
fetch(\`https://gael-berru.com/.../api.php?site_id=SP_24m87bb&api_key=sk_1a2b3c...\`)
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
                                <li><strong>Excel/Google Sheets</strong> : Utilise
                                    <code>=IMPORTDATA("https://...")</code>.
                                </li>
                                <li><strong>Tableau de bord custom</strong> : Utilise Chart.js (voir ci-dessous).</li>
                            </ul>
                            <p>Exemple de code pour un graphique avec Chart.js :</p>
                            <code>
&lt;canvas id="visitsChart" width="800" height="400"&gt;&lt;/canvas&gt;
&lt;script src="https://cdn.jsdelivr.net/npm/chart.js"&gt;&lt;/script&gt;
&lt;script&gt;
  fetch(\`https://gael-berru.com/.../api.php?site_id=SP_24m87bb&api_key=sk_1a2b3c...\`)
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
                                <li><strong>403</strong> : Clé API ou code de tracking invalide. Vérifie tes
                                    identifiants.
                                </li>
                                <li><strong>404</strong> : Site non trouvé. Vérifie le <code>site_id</code>.</li>
                                <li><strong>500</strong> : Erreur serveur. Contacte le support.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            `,
            
            webhooks: `
                <h2>Webhooks (Business)</h2>

                <p>Recevez des notifications en temps réel sur vos événements analytics.</p>

                <h3>Événements disponibles</h3>
                <ul>
                    <li><code>page_view</code> - Nouvelle page vue</li>
                    <li><code>click</code> - Nouveau clic</li>
                    <li><code>daily_report</code> - Rapport quotidien (8h du matin)</li>
                </ul>

                <h3>Configuration</h3>
                <p>Dans Mon compte → Webhooks, ajoutez votre URL (ex: <code>https://mondomaine.com/webhook</code>). Nous
                    enverrons un POST avec un payload JSON contenant les données.</p>

                <div class="code-block">
                    <div class="code-header">
                        <span>Exemple de payload</span>
                    </div>
                    <pre><code>{
  "event": "page_view",
  "site_id": 42,
  "data": {
    "page_url": "/accueil",
    "timestamp": "2026-01-15T10:30:00Z",
    "visitor_id": "sess_abc123"
  }
}</code></pre>
                </div>
            `,
            
            compte: `
                <h2>Gestion de votre compte</h2>

                <h3>Changer de mot de passe</h3>
                <p>Allez dans <code>Dashboard → Mon compte → Sécurité</code>. Vous pouvez modifier votre mot de passe à
                    tout moment.</p>

                <h3>Ajouter/Supprimer un site</h3>
                <p>Dans la colonne de gauche, cliquez sur <i class="fas fa-plus-circle"></i> "Ajouter un site".
                    Remplissez le nom et l'URL. Le tracking code sera généré automatiquement. Pour supprimer, survolez
                    le site dans la liste et cliquez sur la corbeille.</p>

                <h3>Clé API ( en cours de dev, peut ne pas focntionner correctement )</h3>
                <p>Disponible dans Mon compte → API. Régénérez-la si nécessaire (cela cassera les anciennes
                    intégrations).</p>
            `,
            
            paiement: `
                <h2>Paiement avec Lemon Squeezy</h2>

                <p>Nous utilisons <a href="https://lemonsqueezy.com" target="_blank">Lemon Squeezy</a>, une plateforme
                    de paiement européenne (pas de frais cachés).</p>

                <h3>Processus</h3>
                <ol>
                    <li>Vous cliquez sur "Mettre à niveau" dans le dashboard.</li>
                    <li>Vous êtes redirigé vers une page de checkout hébergée par Lemon Squeezy.</li>
                    <li>Vous payez par carte ou PayPal.</li>
                    <li>Lemon Squeezy nous envoie un webhook pour confirmer le paiement.</li>
                    <li>Votre compte est automatiquement mis à niveau.</li>
                </ol>

                <h3>Gestion des abonnements</h3>
                <p>Vous pouvez annuler, modifier ou consulter votre abonnement directement sur le portail client Lemon
                    Squeezy (lien dans l'email de confirmation).</p>
            `,
            
            rgpd: `
                <h2>RGPD et conformité</h2>

                <div class="alert alert-success">
                    <strong>Conforme par conception</strong> - LibreAnalytics a été pensé pour respecter la vie privée dès
                    la base.
                </div>

                <h3>Ce que nous collectons</h3>
                <ul>
                    <li>Pages vues (URL, titre, referrer)</li>
                    <li>Informations techniques (navigateur, OS, écran)</li>
                    <li>Géolocalisation (pays et ville uniquement, pas d'adresse précise)</li>
                    <li>Clics (élément cliqué, pas de données personnelles)</li>
                </ul>

                <h3>Ce que nous ne collectons PAS</h3>
                <ul>
                    <li>Cookies tiers</li>
                    <li>Empreinte numérique complète (fingerprinting)</li>
                    <li>Données de formulaires (sauf si vous envoyez un événement custom)</li>
                </ul>

                <h3>Hébergement</h3>
                <p>Toutes les données sont hébergées sur des serveurs en France. Aucune donnée ne transite par les USA.
                </p>
            `,
            
            faq: `
                <h2>F.A.Q</h2>

                <div class="card">
                    <h4>LibreAnalytics est-il vraiment gratuit ?</h4>
                    <p>Oui, le plan gratuit est illimité dans le temps pour 1 site et 1000 visites/mois. Pas de carte
                        bleue demandée.</p>
                </div>

                <div class="card">
                    <h4>Puis-je auto-héberger LibreAnalytics ?</h4>
                    <p>Absolument ! Le code est open source (MIT). Suivez les instructions sur <a
                            href="https://github.com/berru-g/smart_pixel_v2" target="_blank">GitHub</a>.</p>
                </div>

                <div class="card">
                    <h4>Comment désinstaller le tracker ?</h4>
                    <p>Supprimez simplement la ligne de script de votre site. Les données historiques restent dans votre
                        dashboard.</p>
                </div>

                <div class="card">
                    <h4>Y a-t-il une application mobile ?</h4>
                    <p>Pas encore, mais le dashboard est responsive et fonctionne parfaitement sur mobile. Une app
                        Flutter est prévue pour 2027.</p>
                </div>

                <div class="card">
                    <h4>Que faire si mes données n'apparaissent pas ?</h4>
                    <p>Vérifiez : 1) que le tracking code est correct, 2) que le script est bien placé avant
                        <code>&lt;/head&gt;</code>, 3) que votre site n'est pas bloqué par un adblocker. Consultez la
                        console navigateur pour d'éventuelles erreurs.
                    </p>
                </div>
            `,
            
            erreurs: `
                <h2>Codes erreur et dépannage</h2>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Signification</th>
                                <th>Solution</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>ERR_INVALID_TRACKING</code></td>
                                <td>Tracking code invalide</td>
                                <td>Vérifiez que le code SP_XXXXXX est correct.</td>
                            </tr>
                            <tr>
                                <td><code>ERR_SITE_INACTIVE</code></td>
                                <td>Site désactivé</td>
                                <td>Activez le site dans le dashboard.</td>
                            </tr>
                            <tr>
                                <td><code>ERR_GEOLOC_FAILED</code></td>
                                <td>Géolocalisation impossible</td>
                                <td>L'API ip-api est peut-être down, les données sont marquées "Unknown".</td>
                            </tr>
                            <tr>
                                <td><code>ERR_DB_INSERT</code></td>
                                <td>Échec insertion base</td>
                                <td>Contactez le support si persistant.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `,
            
            support: `
                <h2>Support</h2>

                <div class="features-grid">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-envelope"></i></div>
                        <h3>Email</h3>
                        <p><a href="../smart_pixel_v2/contact/">contact</a><br>Réponse sous 24h</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fab fa-github"></i></div>
                        <h3>GitHub Issues</h3>
                        <p><a href="https://github.com/berru-g/smart_pixel_v2/issues" target="_blank">Ouvrez un
                                ticket</a><br>Suivi public</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-comment"></i></div>
                        <h3>Discord</h3>
                        <p><a href="#">Serveur communautaire</a><br>Entraide entre utilisateurs</p>
                    </div>
                </div>
            `,
            
            multiSites: `
                <h2>Gestion multi-sites</h2>
                
                <p>LibreAnalytics vous permet de gérer plusieurs sites web depuis un seul compte. Chaque site possède son propre code de tracking et ses statistiques indépendantes.</p>
                
                <h3>Ajouter un site</h3>
                <p>Dans la colonne de gauche du dashboard, cliquez sur le bouton <i class="fas fa-plus-circle"></i> "Ajouter un site". Remplissez les informations suivantes :</p>
                <ul>
                    <li><strong>Nom du site</strong> : Un nom pour identifier votre site (ex: "Blog personnel")</li>
                    <li><strong>URL du site</strong> : L'adresse web complète (ex: https://monblog.fr)</li>
                </ul>
                <p>Un nouveau code de tracking unique sera automatiquement généré pour ce site.</p>
                
                <h3>Basculer entre les sites</h3>
                <p>La liste de vos sites apparaît dans la colonne de gauche. Cliquez simplement sur un site pour afficher ses statistiques dans le dashboard principal.</p>
                
                <h3>Supprimer un site</h3>
                <p>Survolez un site dans la liste et cliquez sur l'icône de corbeille qui apparaît. Confirmez la suppression - attention, cette action est irréversible et toutes les données associées seront effacées.</p>
                
                <div class="alert alert-info">
                    <strong>Limites :</strong> Le nombre de sites disponibles dépend de votre formule d'abonnement :
                    <ul>
                        <li>Gratuit : 1 site</li>
                        <li>Pro : 10 sites</li>
                        <li>Business : 50 sites</li>
                    </ul>
                </div>
            `
        };

        // Éléments DOM
        const searchInput = document.getElementById('messageInput');
        const searchResults = document.getElementById('searchResults');
        const chatMessages = document.getElementById('chatMessages');
        const sendButton = document.getElementById('sendButton');
        const navLinks = document.querySelectorAll('.nav-link');

        // État du chat
        let isTyping = false;

        // Auto-resize textarea
        function autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = (textarea.scrollHeight) + 'px';
            performSearch(textarea.value);
        }

        // Recherche en temps réel
        function performSearch(query) {
            if (!query.trim() || query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            const results = searchData.filter(item =>
                item.title.toLowerCase().includes(query.toLowerCase()) ||
                item.content.toLowerCase().includes(query.toLowerCase()) ||
                (item.tags && item.tags.toLowerCase().includes(query.toLowerCase()))
            ).slice(0, 5);

            if (results.length === 0) {
                searchResults.innerHTML = '<div class="search-result-item">Aucun résultat dans la doc</div>';
                searchResults.style.display = 'block';
                return;
            }

            searchResults.innerHTML = results.map(r => `
                <div class="search-result-item" data-section="${r.section}" data-title="${r.title}" data-content="${r.content}">
                    <h4>${r.title}</h4>
                    <p>${r.content.substring(0, 60)}...</p>
                </div>
            `).join('');
            searchResults.style.display = 'block';

            // Ajouter événement clic sur les résultats
            document.querySelectorAll('.search-result-item').forEach(el => {
                el.addEventListener('click', function() {
                    const section = this.dataset.section;
                    const title = this.dataset.title;
                    showSectionContent(section, title);
                    searchResults.style.display = 'none';
                    searchInput.value = '';
                    autoResize(searchInput);
                });
            });
        }

        // Ajouter un message au chat
        function addMessage(text, sender, isHtml = false) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${sender}`;
            
            const time = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            
            let avatar = sender === 'user' 
                ? '<div class="message-avatar"><i class="fas fa-user"></i></div>'
                : '<div class="message-avatar"><i class="fas fa-robot"></i></div>';
            
            let messageContent = isHtml ? text : `<p>${text}</p>`;
            
            messageDiv.innerHTML = `
                ${avatar}
                <div class="message-content">
                    <div class="message-header">
                        <span class="message-author">${sender === 'user' ? 'Vous' : 'Assistant'}</span>
                        <span class="message-time">${time}</span>
                    </div>
                    <div class="message-text doc-section-content">
                        ${messageContent}
                    </div>
                </div>
            `;
            
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Afficher le contenu complet d'une section
        function showSectionContent(section, title) {
            // Ajouter le message de l'utilisateur
            addMessage(`Afficher la documentation : ${title}`, 'user');
            
            // Afficher l'indicateur de frappe
            showTypingIndicator();
            
            setTimeout(() => {
                removeTypingIndicator();
                
                // Récupérer le contenu de la section
                const content = sectionContent[section] || `<p>Contenu non disponible pour ${title}</p>`;
                
                // Ajouter la réponse avec le contenu complet
                const response = `
                    <div style="margin-bottom: 1rem;">
                        <h2>📄 ${title}</h2>
                    </div>
                    ${content}
                    <div style="margin-top: 1rem; padding-top: 0.5rem; border-top: 1px solid var(--border-color); display: flex; gap: 0.5rem;">
                        <span class="quick-action" onclick="copySectionContent('${section}')">📋 Copier tout</span>
                        <span class="quick-action" onclick="askQuestion('En savoir plus sur ${title}')">❓ Poser une question</span>
                    </div>
                `;
                
                addMessage(response, 'bot', true);
            }, 800);
        }

        // Copier tout le contenu d'une section
        window.copySectionContent = function(section) {
            const content = sectionContent[section];
            if (content) {
                // Nettoyer le HTML pour la copie
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = content;
                const text = tempDiv.textContent || tempDiv.innerText || '';
                copyToClipboard(text);
            }
        };

        // Ajouter une réponse du bot avec suggestion
        function addBotResponse(question, answer) {
            showTypingIndicator();
            
            setTimeout(() => {
                removeTypingIndicator();
                
                // Chercher dans searchData
                const result = searchData.find(item => 
                    item.title.toLowerCase().includes(question.toLowerCase()) ||
                    question.toLowerCase().includes(item.title.toLowerCase())
                );
                
                if (result) {
                    // Proposer d'afficher la section complète
                    const response = `
                        <p>${result.content}</p>
                        <div style="margin-top: 1rem; padding: 0.75rem; background: var(--accent-color); color: white; border-radius: 8px; cursor: pointer;" onclick="showSectionContent('${result.section}', '${result.title}')">
                            <i class="fas fa-book-open"></i> Cliquez ici pour voir la documentation complète sur "${result.title}"
                        </div>
                    `;
                    addMessage(response, 'bot', true);
                } else {
                    // Réponse générique avec suggestions
                    const suggestions = searchData.slice(0, 3).map(item => 
                        `<li><a href="#" onclick="showSectionContent('${item.section}', '${item.title}')">${item.title}</a></li>`
                    ).join('');
                    
                    const genericResponse = `
                        <p>Je n'ai pas trouvé de réponse exacte pour "${question}".</p>
                        <p>Voici quelques sections de documentation qui pourraient vous aider :</p>
                        <ul>
                            ${suggestions}
                        </ul>
                        <p>Ou consultez notre <a href="#" onclick="showSectionContent('faq', 'FAQ')">FAQ</a>.</p>
                    `;
                    addMessage(genericResponse, 'bot', true);
                }
            }, 1000);
        }

        // Afficher l'indicateur de frappe
        function showTypingIndicator() {
            if (isTyping) return;
            isTyping = true;
            
            const indicator = document.createElement('div');
            indicator.className = 'message bot';
            indicator.id = 'typingIndicator';
            indicator.innerHTML = `
                <div class="message-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="message-content">
                    <div class="typing-indicator">
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                    </div>
                </div>
            `;
            
            chatMessages.appendChild(indicator);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Supprimer l'indicateur de frappe
        function removeTypingIndicator() {
            const indicator = document.getElementById('typingIndicator');
            if (indicator) {
                indicator.remove();
            }
            isTyping = false;
        }

        // Envoyer un message
        function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            addMessage(message, 'user');
            input.value = '';
            autoResize(input);
            searchResults.style.display = 'none';
            
            addBotResponse(message, '');
        }

        // Fonction pour poser une question rapide
        window.askQuestion = function(question) {
            document.getElementById('messageInput').value = question;
            sendMessage();
        };

        // Copier dans le presse-papier
        window.copyToClipboard = function(text) {
            navigator.clipboard.writeText(text).then(() => {
                addMessage('📋 Texte copié dans le presse-papier !', 'bot');
            }).catch(() => {
                alert('Erreur de copie, sélectionnez manuellement.');
            });
        };

        // Effacer le chat
        window.clearChat = function() {
            chatMessages.innerHTML = `
                <div class="message bot">
                    <div class="message-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="message-content">
                        <div class="message-header">
                            <span class="message-author">Assistant LibreAnalytics</span>
                            <span class="message-time">À l'instant</span>
                        </div>
                        <div class="message-text">
                            <p>👋 Bonjour ! Je suis l'assistant virtuel de LibreAnalytics.</p>
                            <p>Je peux vous aider avec :</p>
                            <ul style="margin: 0.5rem 0 0 1.5rem;">
                                <li>L'installation et la configuration</li>
                                <li>Les fonctionnalités du dashboard</li>
                                <li>L'intégration technique (JS, PHP, API)</li>
                                <li>La gestion de compte et les tarifs</li>
                                <li>Le dépannage et la FAQ</li>
                            </ul>
                            <p>Comment puis-je vous aider aujourd'hui ?</p>
                        </div>
                    </div>
                </div>
            `;
        };

        // Navigation active
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Mise à jour active
                navLinks.forEach(l => l.classList.remove('active'));
                link.classList.add('active');
                
                // Afficher la section complète
                const section = link.dataset.section;
                const sectionTitle = link.textContent.trim();
                showSectionContent(section, sectionTitle);
                
                // Fermer le menu mobile si ouvert
                if (window.innerWidth <= 768) {
                    document.getElementById('sidebar').classList.remove('active');
                }
            });
        });

        // Menu mobile
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');

        if (mobileBtn) {
            mobileBtn.addEventListener('click', () => {
                sidebar.classList.toggle('active');
            });
        }

        // Envoyer avec Entrée (mais pas avec Shift+Entrée)
        document.getElementById('messageInput').addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Fermer les résultats en cliquant ailleurs
        document.addEventListener('click', (e) => {
            if (!searchResults.contains(e.target) && e.target !== searchInput) {
                searchResults.style.display = 'none';
            }
        });

        // Initialisation
        window.addEventListener('load', () => {
            // Simuler un message de bienvenue personnalisé si utilisateur connecté
            <?php if(isset($_SESSION['user_email'])): ?>
            setTimeout(() => {
                addMessage('Bienvenue <?php echo addslashes($_SESSION['user_email']); ?> ! Comment puis-je vous aider aujourd\'hui ?', 'bot');
            }, 500);
            <?php endif; ?>
        });

        // Redimensionnement pour mobile
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>