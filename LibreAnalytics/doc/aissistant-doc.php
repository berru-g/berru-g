<?php



?>
<!DOCTYPE html>
<html lang="fr">
<!-- 
    ╔══════════════════════════════════════════════════╗
    ║                       ██                         ║
    ╠══════════════════════════════════════════════════╣
    ║  Project      : Aissistant             ║
    ║  First commit : February 27, 2025                ║ 
    ║  Version      : 2.1.0                            ║
    ║  Copyright    : 2025 https://github.com/berru-g/ ║
    ╚══════════════════════════════════════════════════╝
-->

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <!-- assistant.php -->
<style>
    /* Variables et styles de base */
    :root {
        --chat-primary: #9d86ff;
        --chat-primary-hover: #681dd8;
        --chat-bg: #ffffff;
        --chat-text: #111827;
        --chat-text-secondary: #6b7280;
        --chat-border: #e5e7eb;
        --chat-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
        --chat-user-bg: #9d86ff;
        --chat-user-text: #ffffff;
        --chat-bot-bg: #f3f4f6;
        --chat-bot-text: #111827;
    }

    /* Bouton flottant */
    .chat-assistant-button {
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 60px;
        height: 60px;
        border-radius: 30px;
        background: var(--chat-primary);
        color: white;
        border: none;
        cursor: pointer;
        box-shadow: var(--chat-shadow);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 9998;
        border: 2px solid white;
    }

    .chat-assistant-button:hover {
        background: var(--chat-primary-hover);
        transform: scale(1.1);
    }

    .chat-assistant-button.active {
        background: var(--chat-primary-hover);
        transform: rotate(45deg);
    }

    /* Badge de notification */
    .chat-notification {
        position: absolute;
        top: -5px;
        right: -5px;
        width: 20px;
        height: 20px;
        background: #ef4444;
        border-radius: 10px;
        border: 2px solid white;
        color: white;
        font-size: 11px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Fenêtre de chat */
    .chat-assistant-window {
        position: fixed;
        bottom: 100px;
        right: 24px;
        width: 380px;
        height: 600px;
        max-height: calc(100vh - 150px);
        background: var(--chat-bg);
        border-radius: 24px;
        box-shadow: var(--chat-shadow);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        z-index: 9999;
        opacity: 0;
        visibility: hidden;
        transform: translateY(20px) scale(0.95);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid var(--chat-border);
    }

    .chat-assistant-window.open {
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
    }

    /* Header de la fenêtre */
    .chat-window-header {
        padding: 20px;
        background: var(--chat-primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        user-select: none;
    }

    .chat-header-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .chat-header-avatar {
        width: 40px;
        height: 40px;
        background: white;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--chat-primary);
        font-size: 20px;
    }

    .chat-header-text h3 {
        font-size: 16px;
        font-weight: 600;
        margin: 0;
    }

    .chat-header-text p {
        font-size: 13px;
        margin: 4px 0 0;
        opacity: 0.9;
    }

    .chat-header-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s;
        font-size: 16px;
    }

    .chat-header-close:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    /* Messages */
    .chat-window-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        background: #f9fafb;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .chat-message {
        display: flex;
        gap: 8px;
        max-width: 85%;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .chat-message.user {
        margin-left: auto;
        flex-direction: row-reverse;
    }

    .message-avatar {
        width: 28px;
        height: 28px;
        border-radius: 14px;
        background: var(--chat-text-secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px;
        flex-shrink: 0;
    }

    .chat-message.user .message-avatar {
        background: var(--chat-primary);
    }

    .message-content {
        padding: 10px 14px;
        border-radius: 18px;
        background: var(--chat-bg);
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        font-size: 13px;
        line-height: 1.5;
    }

    .chat-message.user .message-content {
        background: var(--chat-primary);
        color: white;
    }

    .message-time {
        font-size: 10px;
        color: var(--chat-text-secondary);
        margin-top: 4px;
        text-align: right;
    }

    .chat-message.user .message-time {
        color: rgba(255, 255, 255, 0.7);
    }

    /* Quick actions */
    .quick-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 0 20px 10px;
    }

    .quick-action {
        padding: 6px 12px;
        background: #f3f4f6;
        border: 1px solid var(--chat-border);
        border-radius: 16px;
        font-size: 12px;
        color: var(--chat-text);
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }

    .quick-action:hover {
        background: var(--chat-primary);
        color: white;
        border-color: var(--chat-primary);
    }

    /* Input area */
    .chat-input-area {
        padding: 16px;
        border-top: 1px solid var(--chat-border);
        background: white;
    }

    .input-wrapper {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        background: #f9fafb;
        border: 1px solid var(--chat-border);
        border-radius: 24px;
        padding: 4px 4px 4px 16px;
        transition: all 0.2s;
    }

    .input-wrapper:focus-within {
        border-color: var(--chat-primary);
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
    }

    .message-input {
        flex: 1;
        border: none;
        background: transparent;
        padding: 8px 0;
        font-size: 13px;
        color: var(--chat-text);
        outline: none;
        resize: none;
        max-height: 100px;
        font-family: inherit;
    }

    .message-input::placeholder {
        color: #9ca3af;
    }

    .btn-send {
        width: 36px;
        height: 36px;
        border-radius: 18px;
        background: var(--chat-primary);
        border: none;
        color: white;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }

    .btn-send:hover {
        background: var(--chat-primary-hover);
        transform: scale(1.05);
    }

    .btn-send:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Typing indicator */
    .typing-indicator {
        display: flex;
        gap: 4px;
        padding: 8px 12px;
        background: var(--chat-bg);
        border-radius: 18px;
        width: fit-content;
    }

    .typing-dot {
        width: 6px;
        height: 6px;
        background: var(--chat-text-secondary);
        border-radius: 3px;
        animation: typingBounce 1.4s infinite ease-in-out;
    }

    .typing-dot:nth-child(1) { animation-delay: -0.32s; }
    .typing-dot:nth-child(2) { animation-delay: -0.16s; }

    @keyframes typingBounce {
        0%, 80%, 100% { transform: scale(0); }
        40% { transform: scale(1); }
    }

    /* Search results */
    .search-results {
        position: absolute;
        bottom: 100%;
        left: 16px;
        right: 16px;
        background: white;
        border: 1px solid var(--chat-border);
        border-radius: 12px;
        box-shadow: var(--chat-shadow);
        max-height: 250px;
        overflow-y: auto;
        margin-bottom: 8px;
        display: none;
        z-index: 10000;
    }

    .search-result-item {
        padding: 10px 14px;
        border-bottom: 1px solid var(--chat-border);
        cursor: pointer;
        transition: background 0.2s;
    }

    .search-result-item:last-child {
        border-bottom: none;
    }

    .search-result-item:hover {
        background: #f9fafb;
    }

    .search-result-item h4 {
        font-size: 13px;
        margin: 0 0 4px;
        color: var(--chat-text);
    }

    .search-result-item p {
        font-size: 11px;
        color: var(--chat-text-secondary);
        margin: 0;
    }

    /* Documentation styles */
    .doc-content {
        font-size: 13px;
    }

    .doc-content h1 {
        font-size: 16px;
        margin: 0 0 10px;
    }

    .doc-content h2 {
        font-size: 15px;
        margin: 15px 0 8px;
    }

    .doc-content h3 {
        font-size: 14px;
        margin: 12px 0 6px;
    }

    .doc-content .card {
        background: #f9fafb;
        border-radius: 8px;
        padding: 12px;
        margin: 8px 0;
        border: 1px solid var(--chat-border);
    }

    .doc-content .code-block {
        background: #1e1e1e;
        color: #d4d4d4;
        border-radius: 8px;
        margin: 8px 0;
        font-size: 12px;
    }

    .doc-content .code-header {
        padding: 6px 12px;
        background: #2d2d2d;
        border-radius: 8px 8px 0 0;
        color: #fff;
        font-size: 11px;
        display: flex;
        justify-content: space-between;
    }

    .doc-content .copy-btn {
        background: var(--chat-primary);
        color: white;
        border: none;
        padding: 2px 8px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 10px;
    }

    .doc-content pre {
        padding: 12px;
        overflow-x: auto;
        margin: 0;
    }

    .doc-content code {
        font-family: monospace;
    }

    .doc-content table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        margin: 8px 0;
    }

    .doc-content th {
        background: #f9fafb;
        padding: 6px;
        text-align: left;
        font-weight: 600;
    }

    .doc-content td {
        padding: 6px;
        border-bottom: 1px solid var(--chat-border);
    }

    .doc-content .alert {
        padding: 10px;
        border-radius: 6px;
        margin: 8px 0;
        font-size: 12px;
    }

    .doc-content .alert-info {
        background: rgba(37, 99, 235, 0.1);
        border: 1px solid var(--chat-primary);
    }

    .doc-content .alert-success {
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid #10b981;
    }

    .doc-content .features-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
        margin: 8px 0;
    }

    .doc-content .feature-item {
        text-align: center;
        padding: 8px;
        background: #f9fafb;
        border-radius: 6px;
    }

    .doc-content .feature-icon {
        font-size: 20px;
        color: var(--chat-primary);
        margin-bottom: 4px;
    }

    /* Mobile responsive */
    @media (max-width: 640px) {
        .chat-assistant-window {
            width: calc(100% - 32px);
            right: 16px;
            left: 16px;
            bottom: 90px;
            max-height: calc(100vh - 120px);
        }
        
        .chat-assistant-button {
            bottom: 16px;
            right: 16px;
        }
    }
</style>

</head>

<!-- Interface du chat -->
<div class="chat-assistant" id="chatAssistant">
    <!-- Bouton flottant -->
    <button class="chat-assistant-button" id="chatToggleBtn" onclick="toggleChat()">
        <i class="fas fa-comment" id="chatIcon"></i>
        <span class="chat-notification" id="chatNotification" style="display: none;">1</span>
    </button>

    <!-- Fenêtre de chat -->
    <div class="chat-assistant-window" id="chatWindow">
        <!-- Header cliquable -->
        <div class="chat-window-header" onclick="toggleChat()">
            <div class="chat-header-info">
                <div class="chat-header-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="chat-header-text">
                    <h3>Assistant LibreAnalytics</h3>
                    <p><i class="fas fa-circle" style="font-size: 8px; color: #10b981;"></i> En ligne</p>
                </div>
            </div>
            <button class="chat-header-close" onclick="event.stopPropagation(); toggleChat()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Messages -->
        <div class="chat-window-messages" id="chatMessages">
            <div class="chat-message bot">
                <div class="message-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="message-content">
                    👋 Bonjour <?php echo isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'non connecté'; ?>! Je suis l'assistant LibreAnalytics. Comment puis-je vous aider ?
                    <div class="message-time">À l'instant</div>
                </div>
            </div>
        </div>

        <!-- Quick actions -->
        <div class="quick-actions" id="quickActions">
            <span class="quick-action" onclick="askQuestion('Comment installer ?')">📦 Installation</span>
            <span class="quick-action" onclick="askQuestion('Quels sont les tarifs ?')">💰 Tarifs</span>
            <span class="quick-action" onclick="askQuestion('Comment utiliser l\'API ?')">🔌 API</span>
            <span class="quick-action" onclick="askQuestion('RGPD')">🔒 RGPD</span>
        </div>

        <!-- Input area avec recherche -->
        <div class="chat-input-area">
            <div class="search-results" id="searchResults"></div>
            <div class="input-wrapper">
                <textarea 
                    class="message-input" 
                    id="messageInput" 
                    placeholder="Posez votre question..." 
                    rows="1"
                    oninput="autoResize(this)"
                ></textarea>
                <button class="btn-send" id="sendButton" onclick="sendMessage()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>


<script>
    // Données de recherche
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

// Contenu complet des sections (version simplifiée mais fonctionnelle)
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

// Compléter avec les autres sections de la version précédente
sectionContent.evenements = `<h2>Tracking des clics</h2><p>LibreAnalytics tracke tous les clics automatiquement.</p><div class="code-block"><pre><code>SmartPixel.trackEvent('inscription', { method: 'email' });</code></pre></div>`;
sectionContent.geolocalisation = `<h2>Géolocalisation</h2><p>Basée sur l'IP via ip-api.com. Stockage pays/ville uniquement.</p>`;
sectionContent.sources = `<h2>Sources UTM</h2><p>Capture automatique de utm_source, utm_medium, utm_campaign.</p>`;
sectionContent.scriptJs = `<h2>Tracker.js</h2><p>Script de 4KB, asynchrone.</p><div class="code-block"><pre><code>SmartPixel.load('SP_XXXXXX');</code></pre></div>`;
sectionContent.pixelPhp = `<h2>Pixel.php</h2><p>Point d'entrée serveur, retourne un GIF 1x1.</p>`;
sectionContent.webhooks = `<h2>Webhooks</h2><p>Notifications temps réel sur vos événements.</p>`;
sectionContent.rgpd = `<h2>RGPD</h2><p>Hébergement France, pas de cookies tiers.</p>`;
sectionContent.faq = `<h2>FAQ</h2><p>LibreAnalytics est gratuit pour 1 site/1000 visites. Open source sur GitHub.</p>`;
sectionContent.support = `<h2>Support</h2><p>Email, GitHub Issues, Discord.</p>`;

// État du chat
let isChatOpen = false;
let isTyping = false;
let unreadCount = 1; // Pour la notification

// Éléments DOM
const chatWindow = document.getElementById('chatWindow');
const chatToggleBtn = document.getElementById('chatToggleBtn');
const chatIcon = document.getElementById('chatIcon');
const chatNotification = document.getElementById('chatNotification');
const chatMessages = document.getElementById('chatMessages');
const searchInput = document.getElementById('messageInput');
const searchResults = document.getElementById('searchResults');

// Toggle chat
function toggleChat() {
    isChatOpen = !isChatOpen;
    
    if (isChatOpen) {
        chatWindow.classList.add('open');
        chatToggleBtn.classList.add('active');
        chatIcon.className = 'fas fa-times';
        chatNotification.style.display = 'none';
        unreadCount = 0;
        
        // Focus sur l'input
        setTimeout(() => {
            document.getElementById('messageInput').focus();
        }, 300);
    } else {
        chatWindow.classList.remove('open');
        chatToggleBtn.classList.remove('active');
        chatIcon.className = 'fas fa-comment';
    }
}

// Auto-resize textarea
function autoResize(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = (textarea.scrollHeight) + 'px';
    performSearch(textarea.value);
}

// Recherche en temps réel
function performSearch(query) {
    if (!query.trim() || query.length < 2 || !isChatOpen) {
        searchResults.style.display = 'none';
        return;
    }

    const results = searchData.filter(item =>
        item.title.toLowerCase().includes(query.toLowerCase()) ||
        item.content.toLowerCase().includes(query.toLowerCase())
    ).slice(0, 3);

    if (results.length === 0) {
        searchResults.innerHTML = '<div class="search-result-item">Aucun résultat</div>';
        searchResults.style.display = 'block';
        return;
    }

    searchResults.innerHTML = results.map(r => `
        <div class="search-result-item" data-section="${r.section}">
            <h4>${r.title}</h4>
            <p>${r.content.substring(0, 50)}...</p>
        </div>
    `).join('');
    searchResults.style.display = 'block';

    document.querySelectorAll('.search-result-item').forEach(el => {
        el.addEventListener('click', function() {
            const section = this.dataset.section;
            showSection(section);
            searchResults.style.display = 'none';
            searchInput.value = '';
            autoResize(searchInput);
        });
    });
}

// Ajouter un message
function addMessage(text, sender, isHtml = false) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `chat-message ${sender}`;
    
    const time = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    
    let avatar = sender === 'user' 
        ? '<div class="message-avatar"><i class="fas fa-user"></i></div>'
        : '<div class="message-avatar"><i class="fas fa-robot"></i></div>';
    
    let messageContent = isHtml ? text : `<p>${text}</p>`;
    
    messageDiv.innerHTML = `
        ${avatar}
        <div class="message-content">
            ${messageContent}
            <div class="message-time">${time}</div>
        </div>
    `;
    
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Afficher une section
function showSection(section) {
    const sectionData = searchData.find(d => d.section === section);
    if (!sectionData) return;
    
    addMessage(`Afficher: ${sectionData.title}`, 'user');
    
    // Simuler la frappe
    showTypingIndicator();
    
    setTimeout(() => {
        removeTypingIndicator();
        
        const content = sectionContent[section] || sectionContent.default;
        const response = `
            <div class="doc-content">
                ${content}
                <div style="margin-top: 10px; display: flex; gap: 5px;">
                    <span class="quick-action" onclick="copySection('${section}')">Copier</span>
                    <span class="quick-action" onclick="askQuestion('En savoir plus sur ${sectionData.title}')">❓ Question</span>
                </div>
            </div>
        `;
        addMessage(response, 'bot', true);
    }, 600);
}

// Copier une section
function copySection(section) {
    const content = sectionContent[section] || '';
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = content;
    const text = tempDiv.textContent || tempDiv.innerText || '';
    copyToClipboard(text);
}

// Copier dans le presse-papier
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        addMessage('✅ Texte copié !', 'bot');
    });
}

// Afficher l'indicateur de frappe
function showTypingIndicator() {
    if (isTyping) return;
    isTyping = true;
    
    const indicator = document.createElement('div');
    indicator.className = 'chat-message bot';
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
    
    if (!message || !isChatOpen) return;
    
    addMessage(message, 'user');
    input.value = '';
    autoResize(input);
    searchResults.style.display = 'none';
    
    // Réponse automatique
    showTypingIndicator();
    
    setTimeout(() => {
        removeTypingIndicator();
        
        // Chercher une correspondance
        const result = searchData.find(item => 
            message.toLowerCase().includes(item.title.toLowerCase()) ||
            item.title.toLowerCase().includes(message.toLowerCase())
        );
        
        if (result) {
            const response = `
                <p>${result.content}</p>
                <div style="margin-top: 8px;">
                    <span class="quick-action" onclick="showSection('${result.section}')">
                        Voir documentation complète
                    </span>
                </div>
            `;
            addMessage(response, 'bot', true);
        } else {
            addMessage("Je peux vous aider avec l'installation, les tarifs, l'API, ou la configuration. Que souhaitez-vous savoir ?", 'bot');
        }
    }, 800);
}

// Question rapide
window.askQuestion = function(question) {
    if (!isChatOpen) {
        toggleChat();
    }
    setTimeout(() => {
        document.getElementById('messageInput').value = question;
        sendMessage();
    }, 300);
};

// Fermer les résultats en cliquant ailleurs
document.addEventListener('click', (e) => {
    if (!searchResults.contains(e.target) && e.target !== searchInput) {
        searchResults.style.display = 'none';
    }
});

// Envoyer avec Entrée
document.getElementById('messageInput').addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

// Notification périodique (optionnel)
setInterval(() => {
    if (!isChatOpen && unreadCount === 0) {
        unreadCount = 1;
        chatNotification.style.display = 'flex';
        chatNotification.textContent = '1';
    }
}, 30000); // Toutes les 30 secondes

// Version pour mobile
if (window.innerWidth <= 640) {
    // Ajustements mobiles
}

// Passer les données PHP au JavaScript
const userData = {
    email: '<?php echo isset($userEmail) ? addslashes($userEmail) : ""; ?>',
    name: '<?php echo addslashes($userName); ?>'
};

// Personnaliser le message de bienvenue si utilisateur connecté
if (userData.email) {
    setTimeout(() => {
        addMessage(`Bonjour ${userData.name} ! 👋 Comment puis-je vous aider avec LibreAnalytics ?`, 'bot');
    }, 1000);
}
</script>