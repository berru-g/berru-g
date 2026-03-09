<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Vérifie si connecté
if (!Auth::isLoggedIn()) {
    // Redirige UNIQUEMENT si pas connecté
    header('Location: login.php');
    exit;
}

// 2. Extraire le nom avant @ dans l'email
$emailParts = explode('@', $user['email']);
$name = $emailParts[0]; // Prénom = partie avant le "@"

// Récupération des données avec vérifications
$topPages = $topPages ?? [];
$countries = $countries ?? [];
$devices = $devices ?? [];
$browsers = $browsers ?? [];
$sources = $sources ?? [];
$dailyStats = $dailyStats ?? [];
$sessionData = $sessionData ?? [];
$uniqueVisitorsPeriod = $uniqueVisitorsPeriod ?? 0;
$avgSessionTime = $avgSessionTime ?? 0;
$period = $period ?? 30;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LibreAnalytics Assistant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS complet ci-dessous */
        :root {
            --first-color: #86acff;
            --just-primary: rgba(156, 134, 255, 0.53);
             --back-color: #f5f5f5;
            --text-color: #333;
            --border-color: #e0e0e0;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --first-color: #8688ff;
                --just-primary: rgba(140, 134, 255, 0.5);
                 --back-color: #2b2a2a;
                --text-color: #ffffff;
                --border-color: #444;
            }
        }


        .ai-assistant-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
        }

        .ai-toggle-btn {
            background: var(--first-color);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .ai-toggle-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .ai-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ffe66d;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
        }

        .ai-panel {
            position: fixed;
            bottom: 80px;
            right: 20px;
            width: clamp(300px, 90vw, 400px);
            height: clamp(300px, 70vh, 500px);
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            display: none;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease-out;
            transform: translateY(20px);
            opacity: 0;
            background-color: var( --back-color);
            color: var(--text-color);
        }

        .ai-panel.active {
            display: flex;
            transform: translateY(0);
            opacity: 1;
        }

        .ai-header {
            background: var(--first-color);
            color: white;
            padding: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }

        .ai-header h3 {
            margin: 0;
            font-size: 1.1rem;
        }

        .ai-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px;
        }

        .ai-conversation {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background-color: var( --back-color);
        }

        .ai-message {
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 18px;
            max-width: 85%;
            line-height: 1.5;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .ai-message.user {
            margin-left: auto;
            background: var(--first-color);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .ai-message.bot {
            background: var(--just-primary);
            color: var(--text-color);
            border-bottom-left-radius: 4px;
        }

        .ai-input-area {
            padding: 15px;
            border-top: 1px solid var(--border-color);
            background-color: var( --back-color);
        }

        .ai-quick-questions {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .quick-question {
            flex: 1 1 auto;
            min-width: 100px;
            padding: 8px 12px;
            border: 1px solid grey;
            border-radius: 20px;
            background: transparent;
            color: var(--first-color);
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .quick-question:hover {
            background: var(--first-color);
            color: white;
        }

        .ai-input-wrapper {
            display: flex;
            gap: 10px;
        }

        #aiInput {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            outline: none;
            font-size: 0.95rem;
            background-color: var( --back-color);
            color: var(--text-color);
        }

        #aiInput:focus {
            border-color: var(--first-color);
        }

        #aiSend {
            background: var(--first-color);
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .typing-indicator {
            display: flex;
            gap: 4px;
            padding: 10px;
        }

        .typing-indicator span {
            width: 8px;
            height: 8px;
            background: var(--first-color);
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }

        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing {

            0%,
            60%,
            100% {
                transform: translateY(0);
            }

            30% {
                transform: translateY(-5px);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .ai-panel {
                width: 95vw;
                height: 80vh;
                bottom: 10px;
                right: 2.5vw;
                border-radius: 12px;
            }

            .ai-toggle-btn {
                bottom: calc(10px + env(safe-area-inset-bottom, 0));
                right: 20px;
            }

            .quick-question {
                min-width: 80px;
                font-size: 0.8rem;
                padding: 6px 10px;
            }
        }

        @media (max-width: 480px) {
            .ai-quick-questions {
                flex-direction: column;
            }

            .quick-question {
                min-width: 100%;
                margin-bottom: 5px;
            }

            .ai-header h3 {
                font-size: 1rem;
            }
        }

        /* Support dark mode et safe areas */
        @supports (padding: max(0px)) {
            .ai-assistant-container {
                padding-bottom: max(10px, env(safe-area-inset-bottom));
            }
        }
        a {
            text-decoration: none;
            color: var(--first-color);
            font-weight: 700;
        }
    </style>
</head>

<body>
    <!-- Ton contenu existant ici -->

    <div class="ai-assistant-container">
        <button class="ai-toggle-btn" id="aiToggle">
            <i class="fa-regular fa-message"></i>
            <span class="ai-badge">API</span>
        </button>

        <div class="ai-panel" id="aiPanel">
            <div class="ai-header">
                <h3>LibreAnalytics Assistant</h3>
                <button class="ai-close" id="aiClose">×</button>
            </div>

            <div class="ai-conversation" id="aiConversation">
                <!-- Messages générés dynamiquement -->
            </div>

            <div class="ai-input-area">
                <div class="ai-quick-questions">
                    <button class="quick-question" onclick="askAI('Quelle est ma page la plus performante ?')">
                        📈
                    </button>
                    <button class="quick-question" onclick="askAI('Comment améliorer mon taux de conversion ?')">
                        💰
                    </button>
                    <button class="quick-question" onclick="askAI('Où investir en publicité ?')">
                        📢
                    </button>
                </div>

                <div class="ai-input-wrapper">
                    <input
                        type="text"
                        id="aiInput"
                        placeholder="Posez votre question (ex: 'Quelles sont mes meilleures sources de trafic ?')..."
                        autocomplete="off">
                    <button id="aiSend"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Données utilisateur
        const aiData = {
            topPages: <?= json_encode($topPages ?? []) ?>,
            countries: <?= json_encode($countries ?? []) ?>,
            devices: <?= json_encode($devices ?? []) ?>,
            browsers: <?= json_encode($browsers ?? []) ?>,
            sources: <?= json_encode($sources ?? []) ?>,
            dailyStats: <?= json_encode($dailyStats ?? []) ?>,
            totalVisits: <?= $uniqueVisitorsPeriod ?? 0 ?>,
            avgSessionTime: <?= $avgSessionTime ?? 0 ?>,
            period: <?= $period ?? 30 ?>
        };

        // URL de la documentation
        const DOC_URL = 'https://raw.githubusercontent.com/berru-g/LibreAnalytics/refs/heads/main/readme.md';
        const DOC_API_URL = 'https://api.github.com/repos/berru-g/LibreAnalytics/readme';
        let docSections = {};
        //const userName = userEmail.split('@')[0] || 'Utilisateur';
        
        // Fonction pour récupérer et parser la documentation (version API GitHub)
        async function fetchDoc() {
            try {
                const response = await fetch(DOC_API_URL, {
                    headers: {
                        'Accept': 'application/vnd.github.v3+json'
                    }
                });
                if (!response.ok) throw new Error(`Erreur API GitHub : ${response.status}`);
                const data = await response.json();
                const htmlContent = atob(data.content); // Décoder le base64

                const parser = new DOMParser();
                const doc = parser.parseFromString(htmlContent, 'text/html');
                const sections = {};
                const headers = doc.querySelectorAll('h2, h3');

                headers.forEach(header => {
                    const sectionTitle = header.textContent.trim().toLowerCase();
                    let content = '';
                    let nextNode = header.nextElementSibling;
                    while (nextNode && !['H2', 'H3'].includes(nextNode.tagName)) {
                        content += nextNode.outerHTML;
                        nextNode = nextNode.nextElementSibling;
                    }
                    sections[sectionTitle] = {
                        title: header.textContent.trim(),
                        content: content.trim(),
                        level: parseInt(header.tagName.substring(1))
                    };
                });

                docSections = Object.keys(sections).length > 0 ? sections : {
                    'documentation': {
                        title: 'Documentation',
                        content: `<p><a href="https://gael-berru.com/LibreAnalytics/doc/" target="_blank">Consultez la documentation complète</a>.</p>`,
                        level: 2
                    }
                };
            } catch (error) {
                console.error("Erreur doc :", error);
                docSections = {
                    'documentation': {
                        title: 'Documentation',
                        content: `<p>Documentation indisponible. <a href="https://gael-berru.com/LibreAnalytics/doc/" target="_blank">Voir sur GitHub</a>.</p>`,
                        level: 2
                    }
                };
            }
        }

        // Module pour gérer l'assistant
        const AIAssistant = (function() {
            const elements = {
                toggleBtn: document.getElementById('aiToggle'),
                closeBtn: document.getElementById('aiClose'),
                panel: document.getElementById('aiPanel'),
                conversation: document.getElementById('aiConversation'),
                input: document.getElementById('aiInput'),
                sendBtn: document.getElementById('aiSend'),
            };
            let isPanelOpen = false;

            function togglePanel() {
                isPanelOpen = !isPanelOpen;
                elements.panel.classList.toggle('active', isPanelOpen);
                if (isPanelOpen && !document.querySelector('.ai-message')) {
                    setTimeout(() => {
                        addMessage('bot', "Bonjour <?= htmlspecialchars($_SESSION['user_email'] ?? 'UserLibre') ?>! Comment puis-je vous aider ?");
                    }, 300);
                }
            }

            function addMessage(sender, content, isHTML = false) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `ai-message ${sender}`;
                messageDiv.innerHTML = isHTML ?
                    content :
                    content.replace(/\n/g, '<br>')
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.*?)\*/g, '<em>$1</em>')
                    .replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" target="_blank">$1</a>');
                elements.conversation.appendChild(messageDiv);
                elements.conversation.scrollTop = elements.conversation.scrollHeight;
            }

            function showTyping() {
                const typingDiv = document.createElement('div');
                typingDiv.className = 'ai-message bot typing-indicator';
                typingDiv.innerHTML = '<div class="typing-indicator"><span></span><span></span><span></span></div>';
                elements.conversation.appendChild(typingDiv);
                elements.conversation.scrollTop = elements.conversation.scrollHeight;
                return typingDiv;
            }

            function generateResponse(question) {
                const q = question.toLowerCase();
                const data = aiData;
                const responses = [{
                        keywords: ['page', 'top', 'meilleur', 'performant', 'url', 'trafic'],
                        check: () => data.topPages && data.topPages.length > 0,
                        response: () => {
                            const topPage = data.topPages[0];
                            const percentage = Math.round((topPage.views / data.totalVisits) * 100);
                            const docLink = docSections['optimisation'] ?
                                `\n\n📖 <a href="${DOC_URL}#optimisation" target="_blank">Voir la section "Optimisation"</a>` : '';
                            return `
                           <p>📊 Page la plus performante : 
                            🔗 <a href="${topPage.page_url}" target="_blank">${topPage.page_url}</a></p><br>
                            👁️<p>${topPage.views} vues  (${percentage}% du trafic)</p><br>
                           <p>Recommandations : </p><br>
                          <p>✅ Optimisez les CTA
                            ${docLink}</p><br>
                        `;
                        },
                        fallback: "Aucune donnée de page disponible."
                    },
                    {
                        keywords: ['conversion', 'taux', 'visiteur', 'client', 'vente'],
                        check: () => data.totalVisits > 0,
                        response: () => {
                            const estimatedRate = data.totalVisits > 100 ? "2-5%" : "1-3%";
                            const docLink = docSections['conversion'] ?
                                `\n\n📖 [Améliorer votre taux de conversion](${DOC_URL}#conversion)` : '';

                            return `
                       <p>🎯 Taux de conversion estimé : ${estimatedRate}</p><br>
                       <p>Potentiel : Jusqu’à<p>${Math.round(data.totalVisits * 0.05)} conversions/mois</p><br>

                       <p>Actions recommandées : </p><br>
                       <p> 1. Simplifiez vos formulaires</p><br>
                       <p> 2. Ajoutez des garanties (ex: "Satisfait ou remboursé")</p><br>
                       <p> 3. Testez différents boutons (couleur, texte)
                        ${docLink}</p><br>
                    `;
                        },
                        fallback: "Données de trafic manquantes pour calculer le taux de conversion."
                    },
                    {
                        keywords: ['pays', 'géographie', 'visiteur', 'localisation', 'pays visiteurs'],
                        check: () => data.countries && data.countries.length > 0,
                        response: () => {
                            let response = "🌍 Répartition géographique :\n\n";
                            data.countries.slice(0, 3).forEach((country, index) => {
                                const percentage = Math.round((country.visits / data.totalVisits) * 100);
                                response += `${index + 1}. ${country.country}  : ${country.visits} visites (${percentage}%)\n`;
                            });
                            return response;
                        },
                        fallback: "Aucune donnée géographique disponible."
                    },
                    {
                        keywords: ['investir', 'pub', 'publicité', 'ads', 'campagne', 'budget'],
                        check: () => data.sources && data.sources.length > 0,
                        response: () => {
                            const bestSource = data.sources[0];
                            const docLink = docSections['publicité'] ?
                                `\n\n📖 [Stratégies publicitaires](${DOC_URL}#publicité)` : '';

                            return `
                       <p>💰 Recommandations publicitaires :</p>

                        1.<p>Source actuelle la plus performante :</p>
                          <p> 📊 ${bestSource.source} (${bestSource.count} visites)</p><br>

                        2.<p>Meilleur appareil cible :</p>
                          <p> 📱 ${data.devices[0]?.device || 'Desktop'}</p><br>

                       <p>Stratégie recommandée :</p>
                        <p>🎯 Doublez votre budget sur ${bestSource.source}</p>
                        <p>🎯 Ciblez ${data.countries[0]?.country || 'France'}</p>
                        ${docLink}
                    `;
                        },
                        fallback: "Analysez d'abord vos sources de trafic pour mieux cibler vos investissements."
                    },
                    {
                        keywords: ['tendance', 'évolution', 'croissance', 'statistiques', 'analytique'],
                        check: () => data.dailyStats && data.dailyStats.length >= 2,
                        response: () => {
                            const firstDay = data.dailyStats[0].visits;
                            const lastDay = data.dailyStats[data.dailyStats.length - 1].visits;
                            const growth = ((lastDay - firstDay) / firstDay * 100).toFixed(1);
                            const docLink = docSections['analytique'] ?
                                `\n\n📖 [Analyse des tendances](${DOC_URL}#analytique)` : '';

                            return `
                       <p>📈 Tendances (${data.period} jours) :</p>

                        <p>Évolution trafic : ${growth}%</p><br>
                        <p>Visiteurs uniques : ${data.totalVisits}</p><br>
                        <p>Engagement : ${data.avgSessionTime} min/session</p><br>

                       <p>Prévision semaine prochaine : 
                        ~${Math.round(data.totalVisits / data.period * 7 * 1.1)} visites
                        ${growth > 0 ? '✅ Bonne croissance !' : '⚠️ À améliorer'}
                        ${docLink}</p>
                    `;
                        },
                        fallback: "Collectez plus de données pour analyser les tendances."
                    },
                    {
                        keywords: ['recommandation', 'conseil', 'astuce', 'amélioration', 'stratégie'],
                        response: () => {
                            const docLink = docSections['stratégie'] ?
                                `\n\n📖 [Stratégies recommandées](${DOC_URL}#stratégie)` : '';

                            return `
                       <p>🎯 Recommandations personnalisées : </p><br>

                       <p> 1. Ciblez ${data.countries[0]?.country || 'de nouveaux marchés'}</p><br> 
                       <p> 2. Optimisez pour ${data.devices[0]?.device || 'mobile'}</p><br> 
                       <p> 3. Améliorez le SEO de vos pages les plus visitées</p><br>
                       <p> 4. Créez des campagnes sur ${data.sources[0]?.source || 'vos meilleures sources'} 
                        ${docLink}</p><br>
                    `;
                        }
                    }
                ];

                // Logique de matching
                for (const {
                        keywords,
                        check,
                        response,
                        fallback
                    }
                    of responses) {
                    if (keywords.some(keyword => q.includes(keyword))) {
                        if (!check || check()) return response();
                        if (fallback) return fallback;
                    }
                }

                // Recherche dans la documentation
                for (const [title, section] of Object.entries(docSections)) {
                    if (q.includes(title)) {
                        return `
                       <p>📚 ${section.title} </p><br>
                       <p> ${section.content}</p><br>
                        <p>[<a href="https://gael-berru.com/LibreAnalytics/doc/#${encodeURIComponent(title)}" target="_blank">Lire la suite</a>]</p>
                    `;
                    }
                }

                return `Je ne peux pas répondre. Consultez la <a href="https://gael-berru.com/LibreAnalytics/doc/" target="_blank">documentation</a>.`;
            }

            async function askAI(question) {
                if (!question.trim()) return;
                addMessage('user', question);
                const typingIndicator = showTyping();
                setTimeout(() => {
                    typingIndicator.remove();
                    const response = generateResponse(question);
                    addMessage('bot', response, true);
                }, 800);
            }

            function init() {
                fetchDoc().then(() => {
                    console.log("Documentation chargée :", Object.keys(docSections));
                }).catch(error => {
                    console.error("Erreur :", error);
                });
                elements.toggleBtn.addEventListener('click', togglePanel);
                elements.closeBtn.addEventListener('click', togglePanel);
                elements.sendBtn.addEventListener('click', () => {
                    askAI(elements.input.value);
                    elements.input.value = '';
                });
                elements.input.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') elements.sendBtn.click();
                });
            }

            return {
                init,
                askAI,
                togglePanel
            };
        })();

        // Initialisation
        document.addEventListener('DOMContentLoaded', () => {
            AIAssistant.init();
        });

        // Fonction globale pour les quick questions
        function askAI(question) {
            AIAssistant.askAI(question);
        }
    </script>

</body>

</html>