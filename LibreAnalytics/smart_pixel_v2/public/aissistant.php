<?php

// LibreAssistant v2 — assistant 100% local, zéro dépendance, zéro requête externe
// (la doc GitHub est optionnelle et dégrade proprement si indisponible)

// Données attendues (transmises par dashboard.php) — avec valeurs de repli
$emailParts = explode('@', $_SESSION['user_email'] ?? 'user@libre');
$userName = $emailParts[0];

$topPages            = $topPages ?? [];
$countries           = $countries ?? [];
$devices             = $devices ?? [];
$browsers            = $browsers ?? [];
$sources             = $sources ?? [];
$dailyStats          = $dailyStats ?? [];
$sessionData         = $sessionData ?? [];
$uniqueVisitorsPeriod = $uniqueVisitorsPeriod ?? 0;
$avgSessionTime       = $avgSessionTime ?? 0;
$period               = $period ?? 30;

/** Échappement HTML pour tout ce qui vient de la base ou de l'utilisateur */
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --first-color: #ab9ff2;
            --pink: #ff86e9;
            --just-primary: rgba(156, 134, 255, 0.53);
            --back-color: #f5f5f5;
            --text-color: #0b0d12;
            --border-color: #e0e0e0;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --back-color: #0b0d12;
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
        }

        .ai-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--pink);
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
            border-radius: 12px;
            box-shadow: var(--shadow);
            display: none;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease-out;
            transform: translateY(20px);
            opacity: 0;
            background-color: var(--back-color);
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
        }

        .ai-message {
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 18px;
            max-width: 90%;
            line-height: 1.5;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .ai-message.user {
            margin-left: auto;
            background: var(--first-color);
            color: white;
            border-bottom-right-radius: 4px;
            word-break: break-word;
        }

        .ai-message.bot {
            background: var(--just-primary);
            color: var(--text-color);
            border-bottom-left-radius: 4px;
        }

        .ai-message.bot h4 {
            margin: 0 0 8px;
            font-size: 0.95rem;
        }

        .ai-message.bot ul {
            margin: 6px 0;
            padding-left: 18px;
        }

        .ai-message.bot li { margin: 3px 0; }

        .ai-message.bot .ai-note {
            font-size: 0.8rem;
            opacity: 0.75;
            margin-top: 8px;
        }

        .ai-message.bot .ai-doc {
            display: inline-block;
            margin-top: 8px;
            font-size: 0.85rem;
        }

        .ai-suggestions { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }

        .ai-suggestion {
            padding: 5px 10px;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            background: transparent;
            color: var(--text-color);
            cursor: pointer;
            font-size: 0.8rem;
        }

        .ai-suggestion:hover { border-color: var(--first-color); color: var(--first-color); }

        .ai-input-area {
            padding: 15px;
            border-top: 1px solid var(--border-color);
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
        }

        .quick-question:hover { background: var(--first-color); color: white; }

        .ai-input-wrapper { display: flex; gap: 10px; }

        #aiInput {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            outline: none;
            font-size: 0.95rem;
            background-color: var(--back-color);
            color: var(--text-color);
        }

        #aiInput:focus { border-color: var(--first-color); }

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

        .typing-indicator { display: flex; gap: 4px; padding: 10px; }

        .typing-indicator span {
            width: 8px;
            height: 8px;
            background: var(--first-color);
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }

        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-5px); }
        }

        .ai-sparkline { margin: 8px 0; display: block; }

        @media (max-width: 768px) {
            .ai-panel {
                width: 95vw;
                height: 80vh;
                bottom: 10px;
                right: 2.5vw;
            }
        }

        a { text-decoration: none; color: var(--first-color); font-weight: 700; }
    </style>
</head>

<body>
    <div class="ai-assistant-container">
        <button class="ai-toggle-btn" id="aiToggle" aria-label="Ouvrir l'assistant">
            <i class="fa-regular fa-message"></i>
            <span class="ai-badge">AI</span>
        </button>

        <div class="ai-panel" id="aiPanel">
            <div class="ai-header">
                <h3>LibreAssistant</h3>
                <button class="ai-close" id="aiClose" aria-label="Fermer">×</button>
            </div>

            <div class="ai-conversation" id="aiConversation"></div>

            <div class="ai-input-area">
                <div class="ai-quick-questions">
                    <button class="quick-question" data-q="Résume-moi mes statistiques">📊 Résumé</button>
                    <button class="quick-question" data-q="Quelles sont mes pages les plus visitées ?">📄 Top pages</button>
                    <button class="quick-question" data-q="D'où vient mon trafic ?">🧭 Sources</button>
                    <button class="quick-question" data-q="Quelle est la tendance sur les derniers jours ?">📈 Tendance</button>
                </div>
                <div class="ai-input-wrapper">
                    <input type="text" id="aiInput"
                        placeholder="Ex : tendance, top pages, sources, pays, appareils…"
                        autocomplete="off">
                    <button id="aiSend" aria-label="Envoyer"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    </div>

    <script>
    'use strict';

    // ============================================================
    // 1. DONNÉES — source de vérité unique, échappées côté PHP
    // ============================================================
    const aiData = {
        topPages: <?= json_encode($topPages, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        countries: <?= json_encode($countries, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        devices: <?= json_encode($devices, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        browsers: <?= json_encode($browsers, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        sources: <?= json_encode($sources, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        dailyStats: <?= json_encode($dailyStats, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        sessionData: <?= json_encode($sessionData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        totalVisits: <?= (int)$uniqueVisitorsPeriod ?>,
        avgSessionTime: <?= (float)$avgSessionTime ?>,
        period: <?= (int)$period ?>,
        userName: <?= json_encode($userName, JSON_UNESCAPED_UNICODE) ?>
    };

    const DOC_SITE = 'https://gael-berru.com/LibreAnalytics/doc/';

    // Échappement HTML côté JS (défense en profondeur)
    const esc = (s) => String(s).replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));

    const pct = (part, total) => total > 0 ? Math.round(part / total * 100) : 0;
    const fmt = (n) => new Intl.NumberFormat('fr-FR').format(Number(n) || 0);

    // ============================================================
    // 2. CALCULS RÉELS — aucune statistique inventée
    // ============================================================
    const Compute = {
        /** Croissance : moyenne des derniers jours vs jours précédents (sur dailyStats, ordonné ASC) */
        growth() {
            const d = aiData.dailyStats.map(x => Number(x.visits) || 0);
            if (d.length < 2) return null;
            const half = Math.floor(d.length / 2);
            if (half === 0) return null;
            const avg = a => a.reduce((s, v) => s + v, 0) / a.length;
            const before = avg(d.slice(0, half)), after = avg(d.slice(half));
            if (before === 0) return after > 0 ? null : 0;
            return Math.round(((after - before) / before) * 1000) / 10;
        },
        /** Projection linéaire naïve sur 7 jours (présentée comme estimation) */
        projection7d() {
            const d = aiData.dailyStats.map(x => Number(x.visits) || 0);
            if (d.length < 2) return null;
            const avg = d.reduce((s, v) => s + v, 0) / d.length;
            return Math.round(avg * 7);
        },
        pagesParSession() {
            if (!aiData.sessionData.length) return null;
            const total = aiData.sessionData.reduce((s, x) => s + (Number(x.page_views) || 0), 0);
            return Math.round((total / aiData.sessionData.length) * 10) / 10;
        }
    };

    // ============================================================
    // 3. SPARKLINE SVG — mini-graphe dans le chat
    // ============================================================
    function sparkline(values, w = 230, h = 44) {
        if (!values || values.length < 2) return '';
        const max = Math.max(...values), min = Math.min(...values);
        const pts = values.map((v, i) => {
            const x = (i / (values.length - 1)) * (w - 6) + 3;
            const y = h - 3 - ((v - min) / (max - min || 1)) * (h - 8);
            return `${x.toFixed(1)},${y.toFixed(1)}`;
        });
        return `<svg class="ai-sparkline" width="${w}" height="${h}" role="img" aria-label="Évolution du trafic">
            <polyline fill="none" stroke="var(--first-color)" stroke-width="2" points="${pts.join(' ')}"/>
        </svg>`;
    }

    // ============================================================
    // 4. MOTEUR D'INTENTS — scoring par termes pondérés, pas includes()
    // ============================================================
    // score : { terme: poids } — le terme matche sur mot entier ou préfixe
    const INTENTS = [
        {
            id: 'resume',
            terms: { resume: 3, resume: 3, global: 2, vue: 2, ensemble: 2, synthese: 3, overview: 3, situation: 2, stats: 1, statistique: 1 },
            build: () => ({
                title: '📊 Vue d\'ensemble',
                html: `
                    <ul>
                        <li>👥 <strong>${fmt(aiData.totalVisits)}</strong> visiteurs uniques (${aiData.period} jours)</li>
                        <li>⏱️ <strong>${aiData.avgSessionTime} min</strong> en moyenne par session</li>
                        <li>📄 <strong>${fmt(aiData.topPages[0]?.views ?? 0)}</strong> vues sur <a href="${esc(aiData.topPages[0]?.page_url ?? '#')}" target="_blank" rel="noopener">${esc(aiData.topPages[0]?.page_url ?? '—')}</a></li>
                        <li>🧭 Source n°1 : <strong>${esc(aiData.sources[0]?.source ?? 'direct')}</strong></li>
                        <li>📱 Appareil dominant : <strong>${esc(aiData.devices[0]?.device ?? '—')}</strong></li>
                    </ul>
                    <span class="ai-note">Toutes les valeurs viennent de votre base, rien n'est estimé.</span>`
            })
        },
        {
            id: 'top_pages',
            terms: { page: 3, pages: 3, top: 2, performant: 2, populaire: 2, visite: 1, url: 2, contenu: 1, article: 1 },
            build: () => {
                if (!aiData.topPages.length) return { title: '📄 Top pages', html: 'Aucune donnée de page pour cette période.' };
                const total = aiData.topPages.reduce((s, p) => s + Number(p.views), 0);
                return {
                    title: '📄 Pages les plus visitées',
                    html: `<ul>${aiData.topPages.slice(0, 5).map(p =>
                        `<li><a href="${esc(p.page_url)}" target="_blank" rel="noopener">${esc(p.page_url)}</a> — ${fmt(p.views)} vues (${pct(p.views, total)}%)</li>`
                    ).join('')}</ul>
                    <span class="ai-note">Astuce : une page à fort trafic et un faible temps de session = promesse non tenue. Vérifiez son contenu en priorité.</span>`
                };
            }
        },
        {
            id: 'sources',
            terms: { source: 3, provenance: 3, referral: 3, trafic: 1, visite: 1, google: 1, vient: 2, arrivent: 2, acquisition: 2, utm: 2, campagne: 1 },
            build: () => {
                if (!aiData.sources.length) return { title: '🧭 Sources de trafic', html: 'Aucune source enregistrée pour cette période.' };
                const total = aiData.sources.reduce((s, x) => s + Number(x.count), 0);
                return {
                    title: '🧭 D\'où vient votre trafic',
                    html: `<ul>${aiData.sources.slice(0, 5).map(x =>
                        `<li><strong>${esc(x.source || 'direct')}</strong> — ${fmt(x.count)} visites (${pct(x.count, total)}%)</li>`
                    ).join('')}</ul>
                    <span class="ai-note">Le canal qui amène le plus de monde mérite vos prochains efforts d'optimisation.</span>`
                };
            }
        },
        {
            id: 'tendance',
            terms: { tendance: 3, evolution: 3, croissance: 3, progres: 2, chute: 3, baisse: 3, jour: 1, semaine: 1, historique: 2, serie: 1 },
            build: () => {
                if (aiData.dailyStats.length < 2) return { title: '📈 Tendance', html: 'Pas encore assez d\'historique. Revenez dans quelques jours.' };
                const g = Compute.growth();
                const proj = Compute.projection7d();
                const arrow = g === null ? '〰️' : g > 5 ? '📈' : g < -5 ? '📉' : '➡️';
                const verdict = g === null ? 'Trafic récent, pas de comparaison possible.' :
                    g > 5 ? 'Bonne croissance, continuez.' :
                    g < -5 ? 'Baisse à investiguer (contenu, saisonnalité, source perdue).' :
                    'Trafic stable.';
                return {
                    title: `📈 Tendance sur ${aiData.dailyStats.length} jours`,
                    html: `${sparkline(aiData.dailyStats.map(x => Number(x.visits) || 0))}
                        <ul>
                            <li>${arrow} Évolution : <strong>${g === null ? '—' : (g > 0 ? '+' : '') + g + '%'}</strong> (2e moitié vs 1re moitié de la période)</li>
                            <li>📅 Total : <strong>${fmt(aiData.dailyStats.reduce((s, x) => s + Number(x.visits), 0))}</strong> visites</li>
                            ${proj !== null ? `<li>🔮 Projection 7 jours (linéaire) : ~<strong>${fmt(proj)}</strong> visites</li>` : ''}
                        </ul>
                        <span class="ai-note">${verdict} Les projections sont de simples estimations linéaires de VOS données.</span>`
                };
            }
        },
        {
            id: 'pays',
            terms: { pays: 3, geographie: 3, localisation: 2, monde: 1, france: 1, etranger: 2, visiteur: 1 },
            build: () => {
                if (!aiData.countries.length) return { title: '🌍 Répartition géographique', html: 'Aucune donnée géographique pour cette période.' };
                const total = aiData.countries.reduce((s, x) => s + Number(x.visits), 0);
                return {
                    title: '🌍 Vos visiteurs par pays',
                    html: `<ul>${aiData.countries.slice(0, 5).map(x =>
                        `<li><strong>${esc(x.country || 'Inconnu')}</strong> — ${fmt(x.visits)} visites (${pct(x.visits, total)}%)</li>`
                    ).join('')}</ul>`
                };
            }
        },
        {
            id: 'appareils',
            terms: { appareil: 3, mobile: 3, telephone: 2, tablette: 2, desktop: 2, ordinateur: 2, navigateur: 3, browser: 3, chrome: 2, safari: 2 },
            build: () => {
                if (!aiData.devices.length) return { title: '📱 Appareils & navigateurs', html: 'Aucune donnée d\'appareil pour cette période.' };
                const totalD = aiData.devices.reduce((s, x) => s + Number(x.count), 0);
                const totalB = aiData.browsers.reduce((s, x) => s + Number(x.count), 0);
                const mobile = aiData.devices.find(x => x.device === 'Mobile');
                return {
                    title: '📱 Appareils & navigateurs',
                    html: `<ul>
                        ${aiData.devices.slice(0, 3).map(x => `<li>💻 <strong>${esc(x.device)}</strong> — ${pct(x.count, totalD)}%</li>`).join('')}
                        ${aiData.browsers.slice(0, 3).map(x => `<li>🌐 <strong>${esc(x.browser)}</strong> — ${pct(x.count, totalB)}%</li>`).join('')}
                    </ul>
                    ${mobile && pct(mobile.count, totalD) > 60
                        ? '<span class="ai-note">Majorité mobile : vérifiez que vos pages phares chargent vite en 4G.</span>' : ''}`
                };
            }
        },
        {
            id: 'sessions',
            terms: { session: 3, temps: 2, duree: 3, engagement: 3, rebond: 2, reste: 1, temps: 2 },
            build: () => {
                const pps = Compute.pagesParSession();
                return {
                    title: '⏱️ Sessions & engagement',
                    html: `<ul>
                        <li>⏳ Temps moyen par session : <strong>${aiData.avgSessionTime} min</strong></li>
                        ${pps !== null ? `<li>📄 Pages par session (top sessions) : <strong>${pps}</strong></li>` : ''}
                        <li>🏆 Session la plus active : <strong>${fmt(aiData.sessionData[0]?.page_views ?? 0)}</strong> pages vues</li>
                    </ul>
                    <span class="ai-note">Un visiteur qui voit plusieurs pages a trouvé son chemin. Rendez ce chemin visible depuis votre page d'accueil.</span>`
                };
            }
        },
        {
            id: 'conseil',
            terms: { conseil: 3, recommandation: 3, ameliorer: 3, optimiser: 3, strategie: 2, astuce: 2, priorite: 2, que: 1, faire: 1 },
            build: () => {
                const tips = [];
                if (aiData.topPages[0]) tips.push(`Renforcez <a href="${esc(aiData.topPages[0].page_url)}" target="_blank" rel="noopener">${esc(aiData.topPages[0].page_url)}</a> : c'est votre vitrine la plus vue.`);
                if (aiData.sources[0]) tips.push(`Dédiez du temps au canal <strong>${esc(aiData.sources[0].source || 'direct')}</strong>, il amène le plus de monde.`);
                if (aiData.devices[0]) tips.push(`Testez vos parcours sur <strong>${esc(aiData.devices[0].device)}</strong> en priorité.`);
                const g = Compute.growth();
                if (g !== null && g < -5) tips.push('Investiguez la baisse de trafic : quelle source a chuté ?');
                if (!tips.length) tips.push('Collectez encore quelques jours de données pour des conseils personnalisés.');
                return {
                    title: '🎯 Priorités concrètes (basées sur vos données)',
                    html: `<ul>${tips.map(t => `<li>${t}</li>`).join('')}</ul>
                        <a class="ai-doc" href="${DOC_SITE}" target="_blank" rel="noopener">📖 Documentation complète</a>`
                };
            }
        }
    ];

    /** Normalisation : minuscules + suppression des accents */
    function normalize(q) {
        return q.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    /** Détection : score chaque intent, retourne les meilleurs triés */
    function detectIntents(question) {
        const words = normalize(question).split(/[^a-z0-9]+/).filter(Boolean);
        const stems = words.map(w => w.slice(0, 6)); // match court = tolérant aux pluriels/conjugaisons
        return INTENTS.map(intent => {
            let s = 0;
            for (const [term, weight] of Object.entries(intent.terms)) {
                const t = normalize(term);
                const match = (w) => w === t || (t.length >= 4 && w.length >= 4 && (w.startsWith(t) || t.startsWith(w)));
                if (words.some(match) || stems.some(st => st.startsWith(t) && t.length >= 4)) s += weight;
            }
            return { intent, score: s };
        }).filter(x => x.score > 0).sort((a, b) => b.score - a.score);
    }

    // ============================================================
    // 5. CONTEXTE — questions de relance ("et par pays ?")
    // ============================================================
    const REFINE_TERMS = { pays: 'pays', mobile: 'appareils', appareil: 'appareils', navigateur: 'appareils', source: 'sources', provenance: 'sources', session: 'sessions', temps: 'sessions', jour: 'tendance' };

    let lastIntent = null;

    function resolve(question) {
        const ranked = detectIntents(question);
        if (ranked.length && ranked[0].score >= 2) {
            lastIntent = ranked[0].intent;
            return { intent: ranked[0].intent, suggestions: [] };
        }
        // Relance : mot d'affinage + intent précédent
        const nq = normalize(question);
        for (const [term, intentId] of Object.entries(REFINE_TERMS)) {
            if (nq.includes(term)) {
                const it = INTENTS.find(i => i.id === intentId);
                if (it) { lastIntent = it; return { intent: it, suggestions: [] }; }
            }
        }
        // Aucun match : proposer les intentions proches ou les classiques
        const suggestions = ranked.length
            ? ranked.slice(0, 2).map(r => r.intent).map(i => SUGGEST_LABELS[i.id] || i.id)
            : ['Résume-moi mes statistiques', 'Quelle est la tendance ?', 'Quelles sont mes pages les plus visitées ?'];
        return { intent: null, suggestions };
    }

    const SUGGEST_LABELS = {
        resume: 'Résume-moi mes statistiques',
        top_pages: 'Top pages',
        sources: 'Sources de trafic',
        tendance: 'Tendance',
        pays: 'Par pays',
        appareils: 'Appareils & navigateurs',
        sessions: 'Sessions & engagement',
        conseil: 'Conseils personnalisés'
    };

    // ============================================================
    // 6. UI — rendu des réponses structurées
    // ============================================================
    const elements = {
        toggleBtn: document.getElementById('aiToggle'),
        closeBtn: document.getElementById('aiClose'),
        panel: document.getElementById('aiPanel'),
        conversation: document.getElementById('aiConversation'),
        input: document.getElementById('aiInput'),
        sendBtn: document.getElementById('aiSend')
    };
    let isPanelOpen = false;

    function togglePanel() {
        isPanelOpen = !isPanelOpen;
        elements.panel.classList.toggle('active', isPanelOpen);
        if (isPanelOpen && !document.querySelector('.ai-message')) {
            setTimeout(() => {
                addBot(`
                    <h4>👋 Salut ${esc(aiData.userName)} !</h4>
                    <p>Je lis <strong>votre base LibreAnalytics</strong> — aucune donnée ne quitte ce serveur.</p>
                    <p>Essayez : « Quelle est la tendance ? », « Top pages », ou « Que puis-je améliorer ? »</p>`);
            }, 250);
        }
    }

    function addBot(html) {
        const div = document.createElement('div');
        div.className = 'ai-message bot';
        div.innerHTML = html;
        elements.conversation.appendChild(div);
        elements.conversation.scrollTop = elements.conversation.scrollHeight;
    }

    function addUser(text) {
        const div = document.createElement('div');
        div.className = 'ai-message user';
        div.textContent = text; // textContent = zéro injection HTML
        elements.conversation.appendChild(div);
        elements.conversation.scrollTop = elements.conversation.scrollHeight;
    }

    function showTyping() {
        const div = document.createElement('div');
        div.className = 'ai-message bot';
        div.innerHTML = '<div class="typing-indicator"><span></span><span></span><span></span></div>';
        elements.conversation.appendChild(div);
        elements.conversation.scrollTop = elements.conversation.scrollHeight;
        return div;
    }

    function ask(question) {
        question = (question || '').trim();
        if (!question) return;
        addUser(question);
        const typing = showTyping();

        // Calcul synchrone — l'indicateur reflète le vrai temps de rendu, pas un faux délai
        requestAnimationFrame(() => {
            typing.remove();
            const { intent, suggestions } = resolve(question);
            if (intent) {
                const r = intent.build();
                addBot(`<h4>${r.title}</h4>${r.html}`);
            } else {
                addBot(`
                    <p>Je n'ai pas de réponse précise à ça, mais je peux vous dire :</p>
                    <div class="ai-suggestions">${suggestions.map(s =>
                        `<button class="ai-suggestion">${esc(s)}</button>`).join('')}</div>`);
                elements.conversation.querySelectorAll('.ai-suggestion').forEach(btn =>
                    btn.addEventListener('click', () => ask(btn.textContent)));
            }
        });
    }

    // ============================================================
    // 7. DOC (optionnelle) — UTF-8 correct via TextDecoder
    // ============================================================
    async function fetchDoc() {
        try {
            const res = await fetch('https://api.github.com/repos/berru-g/LibreAnalytics/readme', {
                headers: { 'Accept': 'application/vnd.github.v3+json' }
            });
            if (!res.ok) throw new Error(res.status);
            const data = await res.json();
            // atob seul casse l'UTF-8 ; on décode proprement
            const bytes = Uint8Array.from(atob(data.content), c => c.charCodeAt(0));
            const md = new TextDecoder('utf-8').decode(bytes);
            return md; // exploitable plus tard pour une recherche dans la doc
        } catch (e) {
            console.warn('Doc indisponible, l\'assistant reste 100% fonctionnel.');
            return null;
        }
    }

    function init() {
        fetchDoc(); // préchauffage, non bloquant
        elements.toggleBtn.addEventListener('click', togglePanel);
        elements.closeBtn.addEventListener('click', togglePanel);
        elements.sendBtn.addEventListener('click', () => {
            ask(elements.input.value);
            elements.input.value = '';
        });
        elements.input.addEventListener('keypress', e => {
            if (e.key === 'Enter') elements.sendBtn.click();
        });
        document.querySelectorAll('.quick-question').forEach(btn =>
            btn.addEventListener('click', () => ask(btn.dataset.q)));
    }

    document.addEventListener('DOMContentLoaded', init);
    </script>
</body>

</html>