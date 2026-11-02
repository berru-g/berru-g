<?php

// LibreAssistant V2-CLI — assistant 100% local, front "terminal"
// IDENTIQUE à la V2 côté logique (doc GitHub scorée + cache localStorage,
// mémoire de conversation via assistant_log.php, alerte proactive).
// SEUL CHANGEMENT : le front. Plus de bulles / boutons / graphiques SVG :
// un vrai terminal — on tape des lignes, la réponse sort en texte ASCII.
// Zéro dépendance, aucune donnée ne quitte le serveur (hors doc publique GitHub, en lecture seule).

$emailParts = explode('@', $_SESSION['user_email'] ?? 'user@libre');
$userName = $emailParts[0];

$topPages             = $topPages ?? [];
$countries            = $countries ?? [];
$devices              = $devices ?? [];
$browsers             = $browsers ?? [];
$sources              = $sources ?? [];
$dailyStats           = $dailyStats ?? [];
$sessionData          = $sessionData ?? [];
$uniqueVisitorsPeriod = $uniqueVisitorsPeriod ?? 0;
$avgSessionTime       = $avgSessionTime ?? 0;
$period               = $period ?? 30;
$selectedSiteId       = $selectedSiteId ?? 0; // requis pour la mémoire de conversation

/** Échappement HTML pour tout ce qui vient de la base ou de l'utilisateur */
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* ============================================================
           FRONT TERMINAL — tout le style est ici, rien dans la logique
           ============================================================ */
        :root {
            --term-bg: #0b0d12;
            --term-fg: #c9f2d0;
            --term-green: #4af626;
            --term-accent: #ab9ff2;
            --term-pink: #ff86e9;
            --term-dim: #5c6a72;
            --term-amber: #ffd75f;
        }

        .ai-assistant-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
        }

        .ai-toggle-btn {
            background: var(--term-bg);
            color: var(--term-green);
            border: 1px solid var(--term-green);
            font-family: inherit;
            font-size: 0.85rem;
            padding: 10px 12px;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.4);
        }

        .ai-toggle-btn:hover { background: var(--term-green); color: var(--term-bg); }

        .ai-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--term-pink);
            color: #000;
            font-size: 9px;
            padding: 1px 4px;
            border: 1px solid var(--term-bg);
        }

        .ai-badge.alert { animation: blink-badge 1s steps(2) infinite; }

        @keyframes blink-badge { 50% { opacity: 0.2; } }

        .ai-panel {
            position: fixed;
            bottom: 70px;
            right: 20px;
            width: clamp(340px, 90vw, 680px);
            height: clamp(320px, 70vh, 560px);
            display: none;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid var(--term-green);
            background: var(--term-bg);
            color: var(--term-fg);
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.5);
        }

        .ai-panel.active { display: flex; }

        /* Barre de titre façon gestionnaire de fenêtres tmux */
        .ai-header {
            background: #141821;
            border-bottom: 1px solid var(--term-green);
            padding: 4px 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: var(--term-dim);
            user-select: none;
        }

        .ai-header-actions { display: flex; gap: 12px; }

        .ai-header button {
            background: none;
            border: none;
            color: var(--term-dim);
            font-family: inherit;
            font-size: 0.75rem;
            cursor: pointer;
        }

        .ai-header button:hover { color: var(--term-pink); }

        /* Zone de sortie : préformatée, scroll auto vers le bas */
        .ai-conversation {
            flex: 1;
            overflow-y: auto;
            padding: 10px 12px;
            font-size: 0.8rem;
            line-height: 1.5;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .ai-conversation::-webkit-scrollbar { width: 6px; }
        .ai-conversation::-webkit-scrollbar-thumb { background: var(--term-dim); }

        .t-user   { color: var(--term-accent); }
        .t-bot    { color: var(--term-fg); }
        .t-title  { color: var(--term-green); }
        .t-dim    { color: var(--term-dim); }
        .t-alert  { color: var(--term-pink); }
        .t-warn   { color: var(--term-amber); }

        .ai-cursor {
            display: inline-block;
            width: 7px;
            background: var(--term-green);
            animation: cursor-blink 1s steps(2) infinite;
        }

        @keyframes cursor-blink { 50% { opacity: 0; } }

        /* Ligne de saisie : prompt + champ transparent */
        .ai-input-area {
            border-top: 1px solid var(--term-green);
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: #0e1118;
        }

        .ai-prompt { color: var(--term-green); font-size: 0.8rem; white-space: nowrap; }

        #aiInput {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: var(--term-fg);
            font-family: inherit;
            font-size: 0.8rem;
            caret-color: var(--term-green);
        }

        @media (max-width: 768px) {
            .ai-panel { width: 95vw; height: 75vh; bottom: 65px; right: 2.5vw; }
        }
    </style>
</head>

<body>
    <div class="ai-assistant-container">
        <button class="ai-toggle-btn" id="aiToggle">[ asst ]</button>

        <div class="ai-panel" id="aiPanel">
            <div class="ai-header">
                <span>LibreAssistant — ttyLA0 — 100% local</span>
                <div class="ai-header-actions">
                    <button id="aiClear" title="Effacer la conversation (clear)">[clear]</button>
                    <button id="aiClose" title="Fermer (exit)">[exit]</button>
                </div>
            </div>

            <div class="ai-conversation" id="aiConversation"></div>

            <div class="ai-input-area">
                <span class="ai-prompt" id="aiPrompt">user@libreanalytics:~$</span>
                <input type="text" id="aiInput" autocomplete="off" spellcheck="false"
                    placeholder="tape ta question, ou 'help'…">
            </div>
        </div>
    </div>

    <script>
    'use strict';

    // ============================================================
    // 1. DONNÉES — source de vérité unique (INCHANGÉ)
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
        siteId: <?= (int)$selectedSiteId ?>,
        userName: <?= json_encode($userName, JSON_UNESCAPED_UNICODE) ?>
    };

    const DOC_SITE = 'https://gael-berru.com/LibreAnalytics/doc/';
    const LOG_URL = 'assistant_log.php';

    const esc = (s) => String(s).replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));

    const pct = (part, total) => total > 0 ? Math.round(part / total * 100) : 0;
    const fmt = (n) => new Intl.NumberFormat('fr-FR').format(Number(n) || 0);
    const normalize = (q) => q.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

    // ============================================================
    // 2. CALCULS RÉELS (INCHANGÉ)
    // ============================================================
    const Compute = {
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
        },
        /** Détection d'anomalie pour l'alerte proactive */
        anomaly() {
            const d = aiData.dailyStats.map(x => Number(x.visits) || 0);
            if (d.length < 4) return null;
            const half = Math.floor(d.length / 2);
            const avg = a => a.reduce((s, v) => s + v, 0) / a.length;
            const before = avg(d.slice(0, half)), after = avg(d.slice(half));
            if (before === 0) return after > 0 ? { type: 'pic', pct: 100 } : null;
            const change = ((after - before) / before) * 100;
            if (change <= -30) return { type: 'chute', pct: Math.round(change) };
            if (change >= 50) return { type: 'pic', pct: Math.round(change) };
            return null;
        }
    };

    /** Sparkline ASCII : remplace le SVG par un graphe en barres texte */
    function sparklineAscii(values, width = 40) {
        if (!values || values.length < 2) return '';
        const max = Math.max(...values);
        return values.map((v, i) => {
            const n = max > 0 ? Math.round((v / max) * (width - 14)) : 0;
            const bar = '█'.repeat(Math.max(n, v > 0 ? 1 : 0));
            const day = aiData.dailyStats[i]?.day ?? String(i + 1);
            return `  ${String(day).padEnd(10)} ${bar.padEnd(width - 14)} ${fmt(v)}`;
        }).join('\n');
    }

    // ============================================================
    // 3. INDEX DE DOCUMENTATION (cache localStorage 7j) (INCHANGÉ)
    // ============================================================
    const DocSearch = (function () {
        const CACHE_KEY = 'libreassistant_doc_v1';
        const CACHE_TTL = 7 * 24 * 3600 * 1000;
        let sections = [];

        async function load() {
            try {
                const cached = JSON.parse(localStorage.getItem(CACHE_KEY) || 'null');
                if (cached && Date.now() - cached.at < CACHE_TTL) {
                    sections = cached.sections;
                    return sections;
                }
            } catch (e) { /* cache corrompu : on re-fetch */ }

            try {
                const res = await fetch('https://api.github.com/repos/berru-g/LibreAnalytics/readme', {
                    headers: { 'Accept': 'application/vnd.github.v3+json' }
                });
                if (!res.ok) throw new Error(res.status);
                const data = await res.json();
                const bytes = Uint8Array.from(atob(data.content), c => c.charCodeAt(0));
                const md = new TextDecoder('utf-8').decode(bytes);
                sections = parseMarkdown(md);
                try {
                    localStorage.setItem(CACHE_KEY, JSON.stringify({ at: Date.now(), sections }));
                } catch (e) { /* quota : pas grave */ }
            } catch (e) {
                sections = [];
            }
            return sections;
        }

        function parseMarkdown(md) {
            const lines = md.split('\n');
            const out = [];
            let current = null;
            for (const line of lines) {
                const m = line.match(/^(#{2,3})\s+(.*)$/);
                if (m) {
                    if (current) out.push(current);
                    current = { title: m[2].trim(), body: [] };
                } else if (current && line.trim() !== '---') {
                    current.body.push(line);
                }
            }
            if (current) out.push(current);
            return out.map(s => ({
                title: s.title,
                excerpt: s.body.map(l => l.trim()).filter(l => l && !l.startsWith('!')).slice(0, 3).join(' ').slice(0, 300)
            }));
        }

        function search(question) {
            const words = normalize(question).split(/[^a-z0-9]+/).filter(w => w.length >= 3);
            if (!words.length || !sections.length) return null;
            const scored = sections.map(sec => {
                const t = normalize(sec.title), b = normalize(sec.excerpt);
                let score = 0;
                for (const w of words) {
                    if (t.includes(w)) score += 3;
                    else if (b.includes(w)) score += 1;
                }
                return { sec, score };
            }).filter(x => x.score > 0).sort((a, b) => b.score - a.score);
            return scored.length ? scored[0].sec : null;
        }

        return { load, search, isReady: () => sections.length > 0 };
    })();

    // ============================================================
    // 4. MÉMOIRE DE CONVERSATION (assistant_log.php) (INCHANGÉ)
    // ============================================================
    const Memory = (function () {
        let historyLoaded = false;

        async function loadHistory() {
            if (historyLoaded) return;
            historyLoaded = true;
            try {
                const res = await fetch(`${LOG_URL}?action=history&site_id=${aiData.siteId}`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) return;
                const data = await res.json();
                if (!data.messages || !data.messages.length) return;
                for (const m of data.messages) {
                    m.role === 'user' ? echo(aiData.userName + ' > ' + m.content, 't-user', { silent: true })
                                      : echo(m.content, 't-bot', { silent: true });
                }
            } catch (e) { /* silencieux : la mémoire est un bonus, pas une dépendance */ }
        }

        function log(role, content) {
            try {
                fetch(`${LOG_URL}?action=log`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        site_id: aiData.siteId,
                        role,
                        content: content.replace(/\s+/g, ' ').slice(0, 2000)
                    })
                }).catch(() => {});
            } catch (e) { /* offline : pas grave */ }
        }

        async function clear() {
            try {
                await fetch(`${LOG_URL}?action=clear&site_id=${aiData.siteId}`);
                historyLoaded = false;
            } catch (e) {}
        }

        return { loadHistory, log, clear };
    })();

    // ============================================================
    // 5. MOTEUR D'INTENTS — mêmes termes, mêmes poids.
    //    Seule différence : build() rend du TEXTE, plus du HTML.
    // ============================================================
    const INTENTS = [
        {
            id: 'resume',
            terms: { resume: 3, global: 2, vue: 2, ensemble: 2, synthese: 3, overview: 3, situation: 2, stats: 1, statistique: 1 },
            build: () => ({
                title: 'VUE D\'ENSEMBLE',
                lines: [
                    `  visiteurs uniques ......... ${fmt(aiData.totalVisits)} (${aiData.period} jours)`,
                    `  temps moyen / session ..... ${aiData.avgSessionTime} min`,
                    `  page n°1 .................. ${aiData.topPages[0]?.page_url ?? '—'} (${fmt(aiData.topPages[0]?.views ?? 0)} vues)`,
                    `  source n°1 ................ ${aiData.sources[0]?.source ?? 'direct'}`,
                    `  appareil dominant ......... ${aiData.devices[0]?.device ?? '—'}`,
                    '  [note] toutes les valeurs viennent de votre base, rien n\'est estimé.'
                ]
            })
        },
        {
            id: 'top_pages',
            terms: { page: 3, pages: 3, top: 2, performant: 2, populaire: 2, visite: 1, url: 2, contenu: 1, article: 1 },
            build: () => {
                if (!aiData.topPages.length) return { title: 'TOP PAGES', lines: ['  aucune donnée de page pour cette période.'] };
                const total = aiData.topPages.reduce((s, p) => s + Number(p.views), 0);
                return {
                    title: 'PAGES LES PLUS VISITÉES',
                    lines: aiData.topPages.slice(0, 5).map(p =>
                        `  ${fmt(p.views)} vues (${String(pct(p.views, total)).padStart(3)}%)  ${p.page_url}`
                    ).concat(['  [note] page à fort trafic + faible temps de session = promesse non tenue.'])
                };
            }
        },
        {
            id: 'sources',
            terms: { source: 3, provenance: 3, referral: 3, trafic: 1, visite: 1, google: 1, vient: 2, arrivent: 2, acquisition: 2, utm: 2, campagne: 1 },
            build: () => {
                if (!aiData.sources.length) return { title: 'SOURCES DE TRAFIC', lines: ['  aucune source enregistrée pour cette période.'] };
                const total = aiData.sources.reduce((s, x) => s + Number(x.count), 0);
                return {
                    title: 'D\'OÙ VIENT VOTRE TRAFIC',
                    lines: aiData.sources.slice(0, 5).map(x =>
                        `  ${fmt(x.count)} visites (${String(pct(x.count, total)).padStart(3)}%)  ${x.source || 'direct'}`
                    )
                };
            }
        },
        {
            id: 'tendance',
            terms: { tendance: 3, evolution: 3, croissance: 3, progres: 2, chute: 3, baisse: 3, jour: 1, semaine: 1, historique: 2, serie: 1 },
            build: () => {
                if (aiData.dailyStats.length < 2) return { title: 'TENDANCE', lines: ['  pas encore assez d\'historique. revenez dans quelques jours.'] };
                const g = Compute.growth();
                const proj = Compute.projection7d();
                const arrow = g === null ? '~' : g > 5 ? '++' : g < -5 ? '--' : '->';
                const verdict = g === null ? 'trafic récent, pas de comparaison possible.' :
                    g > 5 ? 'bonne croissance, continuez.' :
                    g < -5 ? 'baisse à investiguer (contenu, saisonnalité, source perdue).' :
                    'trafic stable.';
                return {
                    title: `TENDANCE SUR ${aiData.dailyStats.length} JOURS`,
                    lines: [sparklineAscii(aiData.dailyStats.map(x => Number(x.visits) || 0)), '',
                        `  évolution ........ [${arrow}] ${g === null ? '—' : (g > 0 ? '+' : '') + g + '%'}`,
                        `  total ............ ${fmt(aiData.dailyStats.reduce((s, x) => s + Number(x.visits), 0))} visites`,
                        proj !== null ? `  projection 7j ... ~${fmt(proj)} visites (linéaire)` : null,
                        `  [note] ${verdict} les projections sont des estimations linéaires de VOS données.`
                    ].filter(l => l !== null)
                };
            }
        },
        {
            id: 'pays',
            terms: { pays: 3, geographie: 3, localisation: 2, monde: 1, etranger: 2, visiteur: 1 },
            build: () => {
                if (!aiData.countries.length) return { title: 'RÉPARTITION GÉOGRAPHIQUE', lines: ['  aucune donnée géographique pour cette période.'] };
                const total = aiData.countries.reduce((s, x) => s + Number(x.visits), 0);
                return {
                    title: 'VOS VISITEURS PAR PAYS',
                    lines: aiData.countries.slice(0, 5).map(x =>
                        `  ${fmt(x.visits)} visites (${String(pct(x.visits, total)).padStart(3)}%)  ${x.country || 'Inconnu'}`
                    )
                };
            }
        },
        {
            id: 'appareils',
            terms: { appareil: 3, mobile: 3, telephone: 2, tablette: 2, desktop: 2, ordinateur: 2, navigateur: 3, browser: 3, chrome: 2, safari: 2 },
            build: () => {
                if (!aiData.devices.length) return { title: 'APPAREILS & NAVIGATEURS', lines: ['  aucune donnée d\'appareil pour cette période.'] };
                const totalD = aiData.devices.reduce((s, x) => s + Number(x.count), 0);
                const totalB = aiData.browsers.reduce((s, x) => s + Number(x.count), 0);
                const mobile = aiData.devices.find(x => x.device === 'Mobile');
                return {
                    title: 'APPAREILS & NAVIGATEURS',
                    lines: aiData.devices.slice(0, 3).map(x =>
                        `  appareil ... ${x.device} ${String(pct(x.count, totalD)).padStart(3)}%`
                    ).concat(aiData.browsers.slice(0, 3).map(x =>
                        `  navigateur . ${x.browser} ${String(pct(x.count, totalB)).padStart(3)}%`
                    )).concat(mobile && pct(mobile.count, totalD) > 60
                        ? ['  [note] majorité mobile : vérifiez que vos pages phares chargent vite en 4G.'] : [])
                };
            }
        },
        {
            id: 'sessions',
            terms: { session: 3, duree: 3, engagement: 3, rebond: 2, reste: 1, temps: 2 },
            build: () => {
                const pps = Compute.pagesParSession();
                return {
                    title: 'SESSIONS & ENGAGEMENT',
                    lines: [
                        `  temps moyen / session ... ${aiData.avgSessionTime} min`,
                        pps !== null ? `  pages par session ....... ${pps} (top sessions)` : null,
                        `  session la plus active .. ${fmt(aiData.sessionData[0]?.page_views ?? 0)} pages vues`
                    ].filter(l => l !== null)
                };
            }
        },
        {
            id: 'conseil',
            terms: { conseil: 3, recommandation: 3, ameliorer: 3, optimiser: 3, strategie: 2, astuce: 2, priorite: 2, faire: 1 },
            build: () => {
                const tips = [];
                if (aiData.topPages[0]) tips.push(`  1. renforcez ${aiData.topPages[0].page_url} : c'est votre vitrine la plus vue.`);
                if (aiData.sources[0]) tips.push(`  2. dédiiez du temps au canal ${aiData.sources[0].source || 'direct'}.`);
                if (aiData.devices[0]) tips.push(`  3. testez vos parcours sur ${aiData.devices[0].device} en priorité.`);
                const g = Compute.growth();
                if (g !== null && g < -5) tips.push('  4. investiguez la baisse de trafic : quelle source a chuté ?');
                if (!tips.length) tips.push('  collectez encore quelques jours de données pour des conseils personnalisés.');
                return {
                    title: 'PRIORITÉS CONCRÈTES (basées sur vos données)',
                    lines: tips.concat([`  doc complète : ${DOC_SITE}`])
                };
            }
        },
        // ---- la documentation comme source de réponses ----
        {
            id: 'doc',
            terms: { doc: 3, documentation: 3, installer: 3, installation: 3, integrer: 3, integration: 3, code: 1, suivi: 2, pixel: 2, script: 2, comment: 1, api: 2, webhook: 2, configurer: 3, regler: 2 },
            build: (question) => {
                const sec = DocSearch.search(question);
                if (!sec) {
                    return {
                        title: 'DOCUMENTATION',
                        lines: ['  passage introuvable dans la doc.', `  consultez-la directement : ${DOC_SITE}`]
                    };
                }
                return {
                    title: `DOC — ${sec.title}`,
                    lines: [
                        `  ${sec.excerpt}…`,
                        `  lire la section : ${DOC_SITE}#${encodeURIComponent(normalize(sec.title).replace(/\s+/g, '-'))}`
                    ]
                };
            }
        }
    ];

    function detectIntents(question) {
        const words = normalize(question).split(/[^a-z0-9]+/).filter(Boolean);
        const stems = words.map(w => w.slice(0, 6));
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

    const REFINE_TERMS = { pays: 'pays', mobile: 'appareils', appareil: 'appareils', navigateur: 'appareils', source: 'sources', provenance: 'sources', session: 'sessions', jour: 'tendance' };

    let lastIntent = null;

    function resolve(question) {
        const ranked = detectIntents(question);
        if (ranked.length && ranked[0].score >= 2) {
            lastIntent = ranked[0].intent;
            return { intent: ranked[0].intent, suggestions: [] };
        }
        const nq = normalize(question);
        for (const [term, intentId] of Object.entries(REFINE_TERMS)) {
            if (nq.includes(term)) {
                const it = INTENTS.find(i => i.id === intentId);
                if (it) { lastIntent = it; return { intent: it, suggestions: [] }; }
            }
        }
        const suggestions = ranked.length
            ? ranked.slice(0, 2).map(r => SUGGEST_LABELS[r.intent.id] || r.intent.id)
            : ['Résume-moi mes statistiques', 'Quelle est la tendance ?', 'Comment installer le code de suivi ?'];
        return { intent: null, suggestions };
    }

    const SUGGEST_LABELS = {
        resume: 'Résume-moi mes statistiques',
        top_pages: 'Top pages',
        sources: "D'où vient mon trafic ?",
        tendance: 'Quelle est la tendance ?',
        pays: 'Par pays',
        appareils: 'Appareils & navigateurs',
        sessions: 'Sessions & engagement',
        conseil: 'Donne-moi des conseils',
        doc: 'Documentation'
    };

    // ============================================================
    // 6. FRONT TERMINAL — affichage texte + prompt + historique
    // ============================================================
    const $panel = document.getElementById('aiPanel');
    const $conv  = document.getElementById('aiConversation');
    const $input = document.getElementById('aiInput');
    const $toggle = document.getElementById('aiToggle');
    const $badge = document.createElement('span');
    $badge.className = 'ai-badge';
    $badge.id = 'aiBadge';
    $badge.hidden = true;
    $badge.textContent = '!';
    $toggle.appendChild($badge);

    document.getElementById('aiPrompt').textContent = `${aiData.userName}@libreanalytics:~$`;

    function scrollBottom() { $conv.scrollTop = $conv.scrollHeight; }

    /** Écho d'une ligne texte (équivalent addUser/addBot, en mode terminal) */
    function echo(text, cls = 't-bot', { silent = false } = {}) {
        const div = document.createElement('div');
        div.className = cls;
        div.textContent = text;
        $conv.appendChild(div);
        scrollBottom();
        if (!silent && cls === 't-user') Memory.log('user', text);
        if (!silent && cls === 't-bot') Memory.log('bot', text);
    }

    function echoBlock(title, lines) {
        echo('┌─ ' + title, 't-title');
        lines.forEach(l => echo('│ ' + l, 't-bot'));
        echo('└' + '─'.repeat(Math.max(title.length + 3, 2)), 't-title');
    }

    /** Indicateur de frappe → curseur clignotant */
    function thinking(on) {
        if (on) {
            const div = document.createElement('div');
            div.id = 'aiThinking';
            div.className = 't-dim';
            div.textContent = '… ';
            div.innerHTML += '<span class="ai-cursor">&nbsp;</span>';
            $conv.appendChild(div);
        } else {
            document.getElementById('aiThinking')?.remove();
        }
        scrollBottom();
    }

    const HELP = [
        'COMMANDES',
        '  help ......... cette aide',
        '  clear ........ efface l\'écran ET la mémoire de conversation',
        '  exit ......... ferme le terminal',
        '  trend ........ raccourci tendance (graphique ASCII)',
        '',
        'SINON, pose ta question en langage naturel :',
        '  "résume-moi mes statistiques" · "top pages" · "d\'où vient mon trafic ?"',
        '  "tendance" · "par pays" · "appareils" · "sessions" · "conseils" · "doc / installer"'
    ];

    // Historique de commandes : ↑ / ↓
    const cmdHistory = [];
    let histPos = -1;

    function submitLine() {
        const q = $input.value.trim();
        if (!q) return;
        $input.value = '';
        cmdHistory.push(q);
        histPos = cmdHistory.length;
        echo(`${aiData.userName}@libreanalytics:~$ ${q}`, 't-user');

        const nq = normalize(q);

        if (nq === 'exit') { closePanel(); return; }
        if (nq === 'clear') { doClear(); return; }
        if (nq === 'help' || nq === '?' || nq === 'man') { echoBlock(HELP[0], HELP.slice(1)); return; }
        if (nq === 'trend') { runIntent(INTENTS.find(i => i.id === 'tendance'), q); return; }

        run(q);
    }

    async function run(question) {
        thinking(true);
        try {
            // La doc peut nécessiter un fetch : on "réfléchit" pendant ce temps
            if (!DocSearch.isReady()) await DocSearch.load();
            const { intent, suggestions } = resolve(question);
            if (intent) {
                runIntent(intent, question);
            } else {
                echo('commande inconnue. suggestions :', 't-warn');
                suggestions.forEach(s => echo(`  $ ${s}`, 't-dim'));
                echo(`(tape "help" pour la liste)`, 't-dim');
            }
        } finally {
            thinking(false);
        }
    }

    function runIntent(intent, question) {
        const out = intent.build(question);
        echoBlock(out.title, out.lines);
    }

    async function doClear() {
        $conv.innerHTML = '';
        await Memory.clear();
        banner();
        echo('mémoire de conversation effacée.', 't-dim');
    }

    function banner() {
        echo('LibreAssistant v2-CLI — assistant analytics 100% local', 't-title');
        echo(`session : ${aiData.userName} · site #${aiData.siteId} · ${aiData.period} derniers jours`, 't-dim');
        echo('tape "help" pour les commandes disponibles.', 't-dim');
        echo('');
    }

    function openPanel() {
        $panel.classList.add('active');
        Memory.loadHistory();
        // Alerte proactive : chute ou pic de trafic détecté à l'ouverture
        const a = Compute.anomaly();
        if (a) {
            $badge.hidden = false;
            $badge.classList.add('alert');
            const word = a.type === 'chute' ? 'CHUTE' : 'PIC';
            const cls = a.type === 'chute' ? 't-alert' : 't-warn';
            echo(`[ALERTE] ${word} DE TRAFIC DÉTECTÉ${a.type === 'chute' ? 'E' : ''} : ${a.pct}% vs période précédente. tape "tendance" pour le détail.`, cls);
        } else {
            $badge.hidden = true;
        }
        $input.focus();
    }

    function closePanel() { $panel.classList.remove('active'); }

    // ============================================================
    // 7. ÉVÉNEMENTS
    // ============================================================
    $toggle.addEventListener('click', () => $panel.classList.contains('active') ? closePanel() : openPanel());
    document.getElementById('aiClose').addEventListener('click', closePanel);
    document.getElementById('aiClear').addEventListener('click', doClear);

    $input.addEventListener('keydown', (ev) => {
        if (ev.key === 'Enter') { submitLine(); }
        else if (ev.key === 'ArrowUp') {
            ev.preventDefault();
            if (cmdHistory.length) { histPos = Math.max(0, histPos - 1); $input.value = cmdHistory[histPos] ?? ''; }
        }
        else if (ev.key === 'ArrowDown') {
            ev.preventDefault();
            histPos = Math.min(cmdHistory.length, histPos + 1);
            $input.value = cmdHistory[histPos] ?? '';
        }
        else if (ev.key === 'l' && ev.ctrlKey) { ev.preventDefault(); doClear(); }
    });

    $panel.addEventListener('click', () => $input.focus());

    // Préchargement de l'index de doc (silencieux)
    DocSearch.load();

    // Terminal ouvert par défaut ? Non : comme la V2, on clique pour ouvrir.
    </script>
</body>

</html>
