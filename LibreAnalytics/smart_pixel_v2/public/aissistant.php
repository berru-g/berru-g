<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Vérifie si connecté
if (!Auth::isLoggedIn()) {
    // Redirige UNIQUEMENT si pas connecté
    header('Location: login.php');
    exit;
}

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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            /* Couleurs Light Mode */
            --primary-color: #9d86ff;
            --primary-light: rgba(138, 111, 248, 0.1);
            --bg-color: #f5f5f5;
            --sidebar-bg: #ffffff;
            --text-color: #333333;
            --text-secondary: #666666;
            --border-color: rgba(0, 0, 0, 0.1);
            --hover-bg: rgba(0, 0, 0, 0.05);
            --shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            --search-bg: #f8f8f8;
            --positive: #4ecdc4;
            --negative: #ff6b8b;
            --warning: #f59e0b;
            --gradient-pro: linear-gradient(135deg, #9d86ff 0%, #7c6bd9 100%);
            --gradient-business: linear-gradient(135deg, #4ecdc4 0%, #3bb4ad 100%);
            --p-rose: #ff86e9;
            --p-bleu: #4ecdc4;
            --p-jaune: #ffe66d;
            --p-rouge: #ff6b8b;
        }

        /* Dark Mode Variables */
        @media (prefers-color-scheme: dark) {
            :root {
                --primary-color: #9d86ff;
                --primary-light: rgba(157, 134, 255, 0.15);
                --bg-color: #151515;
                --sidebar-bg: #1d1d1e;
                --text-color: #f0f0f0;
                --text-secondary: #b0b0b0;
                --border-color: rgba(255, 255, 255, 0.15);
                --hover-bg: rgba(255, 255, 255, 0.08);
                --shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
                --search-bg: #1e1e1e;
                --positive: #34d399;
                --negative: #f87171;
                --warning: #fbbf24;
            }
        }

        /* Styles pour l'assistant IA */
        .ai-assistant-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }

        .ai-toggle-btn {
            background: var(--text-color);
            color: var(--bg-color);
            border: 1px solid var(--border-color);
            padding: 12px 20px;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            transition: transform 0.3s;
        }

        .ai-toggle-btn:hover {
            transform: translateY(-2px);
        }

        .ai-badge {
            background: var(--text-color);
            color: var(--bg-color);
            padding: 2px 8px;
            border-radius: 8px;
            font-size: 0.8em;
            animation: pulse 2s infinite;
        }

        .ai-panel {
            position: absolute;
            bottom: 60px;
            right: 0;
            width: 400px;
            height: 500px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            display: none;
            flex-direction: column;
            overflow: hidden;
        }

        .ai-panel.active {
            display: flex;
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .ai-header {
            background: var(--text-color);
            color: var(--bg-color);
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .ai-avatar {
            font-size: 1.5em;
            margin-right: 10px;
        }

        .ai-title h3 {
            margin: 0;
            font-size: 1.2em;
        }

        .ai-title small {
            opacity: 0.9;
            font-size: 0.9em;
        }

        .ai-close {
            background: none;
            border: none;
            color: var(--chart-color-2);
            font-size: 1.5em;
            cursor: pointer;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .ai-close:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .ai-conversation {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background: var(--bg-color);
        }

        .ai-message {
            margin-bottom: 15px;
            max-width: 85%;
            animation: messageAppear 0.3s ease-out;
        }

        @keyframes messageAppear {
            from {
                transform: translateY(10px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .ai-message.user {
            margin-left: auto;
        }

        .ai-message.user .message-content {
            background: var(--primary-color);
            color: var(--bg-color);
            border-radius: 15px 15px 0 15px;
        }

        .ai-message.bot .message-content {
            background: var(--bg-color);
            color: var(--text-color);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .message-content {
            padding: 12px 15px;
            line-height: 1.5;
        }

        .message-time {
            font-size: 0.8em;
            color: #6c757d;
            margin-top: 5px;
            text-align: right;
        }

        .typing-indicator {
            display: flex;
            gap: 5px;
            padding: 15px;
        }

        .typing-indicator span {
            width: 8px;
            height: 8px;
            background: var(--primary-color);
            border-radius: 12px;
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

        .ai-input-area {
            border-top: 1px solid var(--border-color);
            padding: 15px;
            background: var(--bg-color);
        }

        .ai-quick-questions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
        }

        .quick-question {
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 8px 12px;
            font-size: 0.9em;
            cursor: pointer;
            transition: all 0.2s;
        }

        .quick-question:hover {
            background: var(--primary-color);
            color: var(--bg-color);
            border-color: var(--primary-color);
        }

        .ai-input-wrapper {
            display: flex;
            gap: 10px;
        }

        #aiInput {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            outline: none;
            font-size: 0.95em;
        }

        #aiInput:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .ai-send-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .ai-send-btn:hover {
            background: var(--p-bleu);
        }

        /* Emojis stylisés */
        .emoji-success {
            color: var(--p-bleu);
        }

        .emoji-error {
            color: var(--p-rouge);
        }

        .emoji-target {
            color: var(--p-rose);
        }

        .emoji-money {
            color: var(--p-jaune);
        }

        /* Message de suggestion */
        .ai-message.suggestion {
            background: #fff7ed;
            border: 1px solid #fdba74;
            border-radius: 10px;
            margin-top: 10px;
        }

        /* ===== RESPONSIVE ASSISTANT IA ===== */

        /* Tablettes (768px - 1024px) */
        @media screen and (max-width: 1024px) {
            .ai-assistant-container {
                bottom: 15px;
                right: 15px;
            }

            .ai-panel {
                width: 380px;
                height: 750px;
            }

            .ai-toggle-btn {
                padding: 10px 16px;
                font-size: 0.9em;
            }

            .ai-badge {
                font-size: 0.7em;
                padding: 1px 6px;
            }
        }

        /* Mobiles (480px - 767px) */
        @media screen and (max-width: 767px) {
            .ai-assistant-container {
                bottom: 10px;
                right: 10px;
                left: 10px;
                width: calc(100% - 20px);
            }

            .ai-toggle-btn {
                width: 100%;
                justify-content: center;
                padding: 12px;
                border-radius: 12px;
            }

            .ai-panel {
                position: fixed;
                width: calc(100% - 20px);
                height: 70vh;
                max-height: 500px;
                bottom: 70px;
                right: 10px;
                left: 10px;
                border-radius: 12px;
            }

            .ai-header {
                border-radius: 12px 12px 0 0;
            }

            .ai-quick-questions {
                gap: 6px;
                margin-bottom: 12px;
            }

            .quick-question {
                font-size: 0.8em;
                padding: 6px 10px;
                flex: 1;
                min-width: calc(50% - 6px);
                text-align: center;
            }

            #aiInput {
                font-size: 0.9em;
            }

            .ai-send-btn {
                width: 40px;
                height: 40px;
            }

            /* Optimisation messages */
            .ai-message {
                max-width: 90%;
            }

            .message-content {
                font-size: 0.95em;
            }

            /* Cacher le texte sur petit mobile */
            @media screen and (max-width: 480px) {
                .ai-text {
                    display: none;
                }

                .ai-toggle-btn {
                    width: auto;
                    padding: 12px 15px;
                }

                .ai-panel {
                    height: 65vh;
                    max-height: 400px;
                }

                .quick-question {
                    font-size: 0.75em;
                    padding: 5px 8px;
                }
            }

            /* Très petits mobiles (< 360px) */
            @media screen and (max-width: 360px) {
                .ai-panel {
                    height: 60vh;
                    max-height: 350px;
                    bottom: 60px;
                }

                .ai-toggle-btn {
                    padding: 10px 12px;
                }

                .ai-badge {
                    display: none;
                }

                .ai-conversation {
                    padding: 10px;
                }

                .message-content {
                    padding: 8px 10px;
                    font-size: 0.9em;
                }

                .ai-quick-questions {
                    flex-direction: column;
                }

                .quick-question {
                    min-width: 100%;
                    margin-bottom: 4px;
                }
            }
        }

        /* Orientation paysage mobile */
        @media screen and (max-height: 500px) and (orientation: landscape) {
            .ai-panel {
                height: 85vh;
                max-height: none;
                bottom: 80px;
            }

            .ai-conversation {
                max-height: 60vh;
            }
        }

        /* Support tactile */
        @media (hover: none) and (pointer: coarse) {

            .ai-toggle-btn,
            .ai-close,
            .quick-question,
            .ai-send-btn {
                min-height: 44px;
            }

            #aiInput {
                min-height: 44px;
                font-size: 16px;
            }
        }

        /* Mode sombre système */
        @media (prefers-color-scheme: dark) {
            .ai-panel {
                background: var(--text-secondary);
                border: 1px solid var(--border-color);
            }

            .ai-conversation {
                background: var(--bg-color);
            }

            .ai-message.bot .message-content {
                background: var(--text-color);
                color: var(--bg-color);
                border-color: var(--border-color);
            }

            .ai-input-area {
                background: var(--text-color);
                border-color: var(--border-color);
            }

            .quick-question {
                background: var(--text-color);
                border-color: var(--border-color);
                color: var(--bg-color);
            }

            #aiInput {
                background: var(--text-color);
                border-color: var(--border-color);
                color: var(--bg-color);
            }

            .ai-message.suggestion {
                background: rgba(45, 55, 72, 0.5);
                border-color: #4F46E5;
            }
        }

        /* Animations mobile spécifiques */
        @media screen and (max-width: 767px) {
            .ai-panel.active {
                animation: slideUpMobile 0.3s ease-out;
            }

            @keyframes slideUpMobile {
                from {
                    transform: translateY(100%);
                    opacity: 0;
                }

                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
        }

        /* Optimisation iOS */
        @supports (-webkit-touch-callout: none) {
            .ai-conversation {
                -webkit-overflow-scrolling: touch;
            }
        }

        /* Support safe areas (notch) */
        @supports (padding: max(0px)) {
            .ai-assistant-container {
                padding-right: max(10px, env(safe-area-inset-right));
                padding-left: max(10px, env(safe-area-inset-left));
                padding-bottom: max(10px, env(safe-area-inset-bottom));
            }

            .ai-panel {
                margin-bottom: env(safe-area-inset-bottom);
            }
        }
    </style>
</head>
<!-- ASSISTANT PSEUDO IA EN COURS -->
<div class="ai-assistant-container" id="aiAssistantContainer">
    <button class="ai-toggle-btn" onclick="toggleAIAssistant()">
        <span class="ai-icon"><i class="fa-regular fa-message"></i></span>
        <span class="ai-text"></span>
        <span class="ai-badge">NEW</span>
    </button>

    <div class="ai-panel" id="aiPanel">
        <div class="ai-header">
            <div class="ai-title">
                <!--<span class="ai-avatar">🫡</span>-->
                <h3>Smart Assistant</h3>
                <small>- fonction en developpement -</small>
            </div>
            <button class="ai-close" onclick="toggleAIAssistant()">×</button>
        </div>

        <div class="ai-conversation" id="aiConversation">
            <!-- Messages seront ajoutés ici -->
        </div>

        <div class="ai-input-area">
            <div class="ai-quick-questions">
                <button class="quick-question" onclick="askAI('Quelle est ma page la plus performante ?')">
                    📈 Top pages
                </button>
                <button class="quick-question" onclick="askAI('Comment améliorer mon taux de conversion ?')">
                    💰 Optimisation
                </button>
                <button class="quick-question" onclick="askAI('Quelles sont les tendances cette semaine ?')">
                    📊 Tendances
                </button>
                <button class="quick-question" onclick="askAI('Donne-moi des recommandations marketing')">
                    🎯 Recommandations
                </button>
            </div>

            <div class="ai-input-wrapper">
                <input type="text"
                    id="aiInput"
                    placeholder="Posez votre question (ex: 'Où dois-je investir en pub ?')..."
                    onkeypress="if(event.key === 'Enter') sendAIQuestion()">
                <button class="ai-send-btn" onclick="sendAIQuestion()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>


<script>
    // Configuration des données réelles accessibles à l'assistant PSEUDO IA
    const aiData = {
        topPages: <?= json_encode($topPages) ?>,
        countries: <?= json_encode($countries) ?>,
        devices: <?= json_encode($devices) ?>,
        browsers: <?= json_encode($browsers) ?>,
        sources: <?= json_encode($sources) ?>,
        dailyStats: <?= json_encode($dailyStats) ?>,
        sessionData: <?= json_encode($sessionData) ?>,
        totalVisits: <?= $uniqueVisitorsPeriod ?>,
        avgSessionTime: <?= $avgSessionTime ?>,
        period: <?= $period ?>
    };

    // Dictionnaire de réponses intelligentes
    const aiKnowledgeBase = {
        // Mots-clés et réponses associées
        keywords: {
            'page performante|meilleur page|top page': function() {
                if (aiData.topPages.length > 0) {
                    const page = aiData.topPages[0];
                    return `**Votre page la plus performante est :**  
🔗 *${page.page_url}*  
👁️ **${page.views} vues** (${Math.round((page.views / aiData.totalVisits) * 100)}% du trafic)  

**Recommandation :**  
✅ Optimisez cette page avec des Call-To-Actions clairs  
✅ Ajoutez des témoignages clients  
✅ Testez différentes versions (A/B testing)`;
                }
                return "Je n'ai pas encore assez de données sur vos pages.";
            },

            'investir|pub|publicité|ads|campagne': function() {
                if (aiData.sources.length > 0) {
                    const bestSource = aiData.sources[0];
                    return `**💰 Recommandations d'investissement :**  

1. **Source actuelle la plus performante :**  
   📊 *${bestSource.source}* (${bestSource.count} visites)  

2. **Meilleur appareil cible :**  
   📱 *${aiData.devices[0]?.device || 'Desktop'}* (${aiData.devices[0]?.count || 0} utilisations)  

3. **Heures d'engagement :**  
   ⏰ *14h-18h* (pic d'activité détecté)  

**Stratégie recommandée :**  
🎯 Doublez votre budget sur **${bestSource.source}**  
🎯 Ciblez **${aiData.countries[0]?.country || 'France'}**  
🎯 Créez des annonces optimisées pour **${aiData.devices[0]?.device || 'Desktop'}**`;
                }
                return "Analysez d'abord vos sources de trafic pour mieux ciblervos investissements.";
            },

            'conversion|convertir|taux': function() {
                const estimatedRate = (aiData.totalVisits > 100) ? '2-5%' : '1-3%';
                return `**📊 Analyse de conversion :**  

**Taux estimé :** ${estimatedRate}  
**Potentiel d'amélioration :** ${(aiData.totalVisits * 0.05).toFixed(0)} conversions/mois  

**🎯 Actions rapides :**  
1. **Simplifiez votre formulaire** (moins de champs)  
2. **Ajoutez des garanties visibles**  
3. **Testez différents boutons** (couleur, texte)  
4. **Implémentez le retargeting**  

**📈 Objectif SMART :**  
Augmenter le taux de conversion de 1% dans les 30 jours`;
            },

            'tendance|évolution|croissance': function() {
                if (aiData.dailyStats.length >= 2) {
                    const firstDay = aiData.dailyStats[0].visits;
                    const lastDay = aiData.dailyStats[aiData.dailyStats.length - 1].visits;
                    const growth = ((lastDay - firstDay) / firstDay * 100).toFixed(1);

                    return `**📈 Tendances ${aiData.period} jours :**  

📊 **Évolution trafic :** ${growth}%  
👥 **Visiteurs uniques :** ${aiData.totalVisits}  
⏱️ **Engagement :** ${aiData.avgSessionTime} min/session  

**📅 Prévision semaine prochaine :**  
${Math.round(aiData.totalVisits / aiData.period * 7 * 1.1)} visites estimées  
(+10% si vous maintenez la tendance)  

**🔥 Insight :**  
Votre croissance est ${growth > 0 ? 'positive' : 'à améliorer'}. ${growth > 20 ? 'Excellente performance !' : 'Pensez à relancer vos canaux.'}`;
                }
                return "Collectez plus de données pour analyser les tendances.";
            },

            'recommandation|conseil|astuce': function() {
                const tips = [
                    `**🎯 Conseil #1 :** Ciblez **${aiData.countries[1]?.country || 'votre 2ème pays'}** avec du contenu localisé. Potentiel inexploité !`,

                    `**📱 Conseil #2 :** Optimisez pour **${aiData.devices[0]?.device || 'mobile'}** (${Math.round((aiData.devices[0]?.count / aiData.totalVisits) * 100)}% de votre trafic).`,

                    `**🔍 Conseil #3 :** Améliorez le SEO de votre page **"${aiData.topPages[2]?.page_url?.split('/').pop() || 'à fort potentiel'}"** pour +30% de trafic organique.`,

                    `**💰 Conseil #4 :** Testez une offre spéciale le **${['lundi', 'mercredi', 'vendredi'][Math.floor(Math.random() * 3)]}**, jour de plus forte activité.`,

                    `**📊 Conseil #5 :** Créez un rapport automatisé pour suivre vos KPIs clés chaque lundi matin.`
                ];

                return tips[Math.floor(Math.random() * tips.length)];
            },

            'pays|géographie|international': function() {
                if (aiData.countries.length > 0) {
                    let response = `**🌍 Répartition géographique :**\n\n`;
                    aiData.countries.slice(0, 3).forEach((country, index) => {
                        const percentage = Math.round((country.visits / aiData.totalVisits) * 100);
                        response += `${index + 1}. **${country.country}** : ${country.visits} visites (${percentage}%)\n`;
                    });

                    response += `\n**💡 Opportunité :** Développez du contenu en ${aiData.countries[1]?.language || 'anglais'} pour toucher ${aiData.countries[1]?.country || 'de nouveaux marchés'}.`;
                    return response;
                }
                return "Vos visiteurs viennent de divers pays. Analysez la carte pour plus de détails.";
            }
        },

        // Réponses par défaut intelligentes
        defaultResponses: [
            "D'après vos données, je vois que **{device}** est votre principal appareil. Assurez-vous que l'expérience mobile est parfaite !",

            "Vos visiteurs viennent principalement de **{country}**. Avez-vous pensé à localiser votre contenu ?",

            "Je détecte que **{source}** est votre meilleure source de trafic. Pensez à y investir davantage !",

            "Avec {visits} visites en {period} jours, vous pourriez générer environ {conversions} conversions avec un taux de 3%.",

            "Le temps moyen de session est de {time} minutes. C'est {verdict} pour votre secteur !",

            "Pour maximiser vos résultats, concentrez-vous sur l'amélioration de votre taux de conversion actuel.",

            "Cette Assistant IA est en développement ... il se peut qu'il ne réponde pas toujours de manière pertinente. Pour toute question, contactez le developpeur contact@gael-berru.com"
        ]
    };

    // Fonction principale de l'assistant
    async function askAI(question) {
        const conversation = document.getElementById('aiConversation');

        // Ajouter la question
        conversation.innerHTML += `
        <div class="ai-message user">
            <div class="message-content">${question}</div>
            <div class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
        </div>
    `;

        // Simuler un "typing" de l'IA
        conversation.innerHTML += `
        <div class="ai-message bot typing">
            <div class="typing-indicator">
                <span></span><span></span><span></span>
            </div>
        </div>
    `;

        conversation.scrollTop = conversation.scrollHeight;

        // Générer une réponse intelligente après un délai
        setTimeout(() => {
            // Retirer l'indicateur de typing
            document.querySelector('.typing')?.remove();

            // Générer la réponse
            const response = generateAIResponse(question);

            // Ajouter la réponse
            conversation.innerHTML += `
            <div class="ai-message bot">
                <div class="message-content">${formatResponse(response)}</div>
                <div class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
            </div>
        `;

            conversation.scrollTop = conversation.scrollHeight;
        }, 1000 + Math.random() * 1000); // Délai aléatoire pour paraître naturel
    }

    function generateAIResponse(question) {
        const questionLower = question.toLowerCase();

        // Chercher une correspondance de mots-clés
        for (const [pattern, responseFunc] of Object.entries(aiKnowledgeBase.keywords)) {
            const patterns = pattern.split('|');
            if (patterns.some(p => questionLower.includes(p))) {
                return responseFunc();
            }
        }

        // Sinon, générer une réponse contextuelle par défaut
        return generateDefaultResponse();
    }

    function generateDefaultResponse() {
        const template = aiKnowledgeBase.defaultResponses[
            Math.floor(Math.random() * aiKnowledgeBase.defaultResponses.length)
        ];

        return template
            .replace('{device}', aiData.devices[0]?.device || 'mobile')
            .replace('{country}', aiData.countries[0]?.country || 'France')
            .replace('{source}', aiData.sources[0]?.source || 'recherche organique')
            .replace('{visits}', aiData.totalVisits)
            .replace('{period}', aiData.period)
            .replace('{conversions}', Math.round(aiData.totalVisits * 0.03))
            .replace('{time}', aiData.avgSessionTime)
            .replace('{verdict}', aiData.avgSessionTime > 3 ? 'excellent' : 'moyen');
    }

    function formatResponse(text) {
        // Convertir le markdown simple en HTML
        return text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/✅/g, '<span class="emoji-success">✅</span>')
            .replace(/❌/g, '<span class="emoji-error">❌</span>')
            .replace(/🎯/g, '<span class="emoji-target">🎯</span>')
            .replace(/💰/g, '<span class="emoji-money">💰</span>')
            .replace(/\n\n/g, '</p><p>')
            .replace(/\n/g, '<br>');
    }

    function sendAIQuestion() {
        const input = document.getElementById('aiInput');
        if (input.value.trim()) {
            askAI(input.value);
            input.value = '';
        }
    }

    function toggleAIAssistant() {
        const panel = document.getElementById('aiPanel');
        panel.classList.toggle('active');

        // Initialiser avec un message de bienvenue
        if (panel.classList.contains('active') && !document.querySelector('.ai-message.bot')) {
            setTimeout(() => {
                askAI("Bonjour ! Que pouvez-vous m'apprendre sur mes données ?");
            }, 500);
        }
    }

    // Questions automatiques périodiques (simule une IA proactive)
    setTimeout(() => {
        if (Math.random() > 0.7 && document.getElementById('aiPanel')?.classList.contains('active')) {
            const proactiveQuestions = [
                "J'ai remarqué que votre trafic augmente. Voulez-vous des conseils pour capitaliser dessus ?",
                "Votre taux d'engagement est intéressant. Puis-je vous suggérer des optimisations ?",
                "Je vois une opportunité sur votre source de trafic principale. En discuter ?"
            ];

            // Simuler une suggestion de l'IA
            const conversation = document.getElementById('aiConversation');
            conversation.innerHTML += `
            <div class="ai-message bot suggestion">
                <div class="message-content">
                    💡 <strong>Suggestion proactive :</strong><br>
                    ${proactiveQuestions[Math.floor(Math.random() * proactiveQuestions.length)]}
                </div>
            </div>
        `;
            conversation.scrollTop = conversation.scrollHeight;
        }
    }, 15000); // Toutes les 15 secondes
    // FIN DU TEST PSEUDO IA
</script>