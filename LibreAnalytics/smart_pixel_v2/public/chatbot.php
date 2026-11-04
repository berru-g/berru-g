<?php
// ============================================================================
// berru·faq V1 — chatbot de renseignement pour gael-berru.com
// ----------------------------------------------------------------------------
// DA reprise à l'identique de LibreAssistant V2-CLI : front "terminal", on
// tape des lignes, la réponse sort en ASCII. Zéro dépendance.
// FAQ 2.0 : moteur d'intents par mots-clés scorés (même algo que Libre-
// Assistant) + recherche floue dans la base de projets (projects-database.js).
// Anonyme : aucune donnée stockée, aucun cookie — RGPD by design.
//
// Source de vérité : menu/projects-database.js. Si le menu du site est déjà
// chargé, le bot lit window.projectsDB ; sinon il fetch le dépôt GitHub en
// lecture seule. Les intents curatifs (services, prix, méthode, philosophie)
// restent en dur : ils ne figurent pas dans la base.
// ============================================================================

// ==================== CONFIG — tout se règle ici ===========================
$faqConfig = [
    'contactEmail'   => 'contact@gael-berru.com',
    'github'         => 'https://github.com/berru-g/',
    'restosUrl'      => 'https://dons.restosducoeur.org/faire-un-don/~mon-don/?utm_source=gael-berru.com',
    'libreAnalytics' => 'https://gael-berru.com/LibreAnalytics',
    'dbUrl'          => 'https://raw.githubusercontent.com/berru-g/berru-g/refs/heads/main/menu/projects-database.js',

    // Grille indicative sur-mesure (validée par Berru le 04/11/2026).
    // Modifie ces lignes si tes tarifs bougent — c'est la seule chose à éditer.
    'prices' => [
        ['label' => 'Site vitrine / CMS',            'price' => '~650 €', 'desc' => 'formulaires, galerie, responsive — sans template'],
        ['label' => 'Dashboard sur mesure',         'price' => '~990 €', 'desc' => 'login, analytics, API, dataviz, rapports CRON'],
        ['label' => 'Conception PCB / électronique','price' => '~990 €', 'desc' => 'schéma + routage EasyEDA, firmware Arduino + test prototype'],
        ['label' => 'Intégration 3D (WebGL)',       'price' => '~330 €', 'desc' => 'Three.js, animations synchronisées au scroll'],
    ],
];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* ============================================================
           FRONT TERMINAL — DA LibreAssistant V2-CLI, conservée telle quelle
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
        <button class="ai-toggle-btn" id="aiToggle">[ faq ]</button>

        <div class="ai-panel" id="aiPanel">
            <div class="ai-header">
                <span>berru·faq — ttyGB0 — 100% local · anonyme</span>
                <div class="ai-header-actions">
                    <button id="aiClear" title="Effacer l'écran (clear)">[clear]</button>
                    <button id="aiClose" title="Fermer (exit)">[exit]</button>
                </div>
            </div>

            <div class="ai-conversation" id="aiConversation"></div>

            <div class="ai-input-area">
                <span class="ai-prompt" id="aiPrompt">visiteur@gael-berru:~$</span>
                <input type="text" id="aiInput" autocomplete="off" spellcheck="false"
                    placeholder="pose ta question, ou 'help'…">
            </div>
        </div>
    </div>

    <script>
    'use strict';

    // ============================================================
    // 1. CONFIG (injectée depuis le PHP — éditable en haut du fichier)
    // ============================================================
    const CFG = <?= json_encode($faqConfig, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    const normalize = (q) => String(q).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

    // ============================================================
    // 2. BASE DE PROJETS — source de vérité unique.
    //    window.projectsDB si le menu du site est déjà chargé,
    //    sinon fetch lecture seule du dépôt GitHub (public).
    // ============================================================
    const DB = (function () {
        const HIDDEN = ['contact', 'Message de bienvenue'];
        let projects = null;
        let services = null;
        let state = 'void'; // void | ready | fail

        async function load() {
            if (state === 'ready') return true;
            try {
                if (window.projectsDB) {
                    projects = window.projectsDB;
                    services = window.servicesDB || [];
                } else {
                    const res = await fetch(CFG.dbUrl);
                    if (!res.ok) throw new Error(res.status);
                    (new Function(await res.text()))();
                    projects = window.projectsDB || null;
                    services = window.servicesDB || [];
                }
                state = projects ? 'ready' : 'fail';
            } catch (e) {
                state = 'fail';
            }
            return state === 'ready';
        }

        function portfolio() {
            return (projects || []).filter(p => !HIDDEN.includes(p.category));
        }

        /** Recherche scorée : title (3) > keywords/tags/features (2) > desc (1) */
        function search(question) {
            if (state !== 'ready') return [];
            const words = normalize(question).split(/[^a-z0-9]+/).filter(w => w.length >= 3);
            if (!words.length) return [];
            const scored = portfolio().map(p => {
                const t = normalize(p.title || '');
                const g = normalize([...(p.keywords || []), ...(p.tags || []), ...(p.features || []), p.category || ''].join(' '));
                const d = normalize((p.shortDesc || '') + ' ' + (p.longDesc || ''));
                let score = 0;
                for (const w of words) {
                    if (t.includes(w)) score += 3;
                    if (g.includes(w)) score += 2;
                    if (d.includes(w)) score += 1;
                }
                return { p, score };
            }).filter(x => x.score > 0).sort((a, b) => b.score - a.score);
            return scored.slice(0, 3).map(x => x.p);
        }

        function categories() {
            const m = new Map();
            for (const p of portfolio()) {
                const c = p.category || 'autre';
                m.set(c, (m.get(c) || 0) + 1);
            }
            return [...m.entries()].map(([name, count]) => ({ name, count })).sort((a, b) => b.count - a.count);
        }

        return {
            load, search, categories,
            isReady: () => state === 'ready',
            count: () => portfolio().length,
            services: () => services || []
        };
    })();

    // ============================================================
    // 3. MOTEUR D'INTENTS — mots-clés scorés, comme LibreAssistant.
    //    build() rend du TEXTE ASCII (DA terminal conservée).
    // ============================================================
    const INTENTS = [
        {
            id: 'services',
            terms: { service: 3, prestation: 3, propose: 3, offre: 2, expertise: 3, competence: 2, sais: 2, faire: 2, peux: 2, catalog: 2, aide: 1, quoi: 1 },
            build: () => ({
                title: 'MES SERVICES',
                lines: [
                    '  1. intégration 3D web ....... Three.js / WebGL, sites immersifs',
                    '  2. dashboards sur mesure .... analytics, dataviz, rapports automatisés',
                    '  3. outils éthiques & RGPD ... analytics sans cookies, données chez vous',
                    '  4. intégration API .......... Binance, CoinGecko, Blockstream, API privées',
                    '  5. électronique / PCB ...... conception, routage, firmware Arduino',
                    '  6. sites vitrine & CMS ..... from scratch, sans template',
                    '',
                    "  tape 'prix' (grille indicative), 'projets' (portfolio),",
                    "  'methode' (façon de bosser) ou 'contact' (devis gratuit)."
                ]
            })
        },
        {
            id: 'skills',
            terms: { competence: 3, techno: 3, technologie: 3, stack: 3, langage: 3, code: 1, maitrise: 3, php: 2, python: 2, javascript: 2, sql: 1, arduino: 1, html: 1, css: 1 },
            build: () => ({
                title: 'COMPÉTENCES',
                lines: [
                    '  frontend ....... HTML, CSS, JavaScript vanilla',
                    '  backend ........ PHP, Python',
                    '  données ........ SQL / MySQL, architectures sur mesure',
                    '  3D ............. Three.js, WebGL',
                    '  électronique ... Arduino, EasyEDA, PCB, NFC, radio',
                    '',
                    '  [note] ni frameworks ni templates : je code avec les briques fondamentales,',
                    '  pour que le code reste compréhensible et vous appartienne de bout en bout.'
                ]
            })
        },
        {
            id: 'methode',
            terms: { methode: 3, bosse: 3, travail: 2, travaille: 3, processus: 3, demarche: 3, souverain: 3, souverainete: 3, framework: 2, template: 2, dependance: 3, lovable: 3, noir: 1, boite: 1 },
            build: () => ({
                title: 'MA MÉTHODE — LA SOUVERAINETÉ TECHNIQUE',
                lines: [
                    '  1. ni frameworks ni templates → contrôle total sur chaque section',
                    '  2. pas de dépendances superflues → vos données ne transitent dans aucun système externe',
                    '  3. pas de boîte noire → aucun code copié-collé que je ne comprends pas',
                    '',
                    "  déroulé d'un projet :",
                    '  a. tu me décris ta problématique (mail ou formulaire)',
                    '  b. devis gratuit, sans engagement',
                    '  c. développement itératif, code documenté',
                    "  d. le code source t'appartient à 100 %, sans condition"
                ]
            })
        },
        {
            id: 'philosophie',
            terms: { philosophie: 3, open: 2, source: 2, gratuit: 3, libre: 3, don: 3, restos: 3, coeur: 3, vendre: 2, licence: 2, ethique: 2, pourquoi: 1 },
            build: () => ({
                title: 'OPEN SOURCE — RIEN À VENDRE',
                lines: [
                    '  tous mes projets sont open source, visibles ligne par ligne sur GitHub.',
                    "  pas de licence, pas de contrepartie : si un outil t'est utile, il est à toi.",
                    "  si tu tiens à donner, fais-le plutôt aux Restos du Cœur — c'est la seule",
                    '  monnaie que j\u2019accepte.',
                    '  → ' + CFG.restosUrl,
                    '',
                    "  [note] les Restos du Cœur m'ont aidé un jour, c'est ma façon de renvoyer",
                    "  l'ascenseur. le sur-mesure se chiffre sur devis ; l'existant est à toi."
                ]
            })
        },
        {
            id: 'prix',
            terms: { prix: 3, tarif: 3, coute: 3, combien: 3, budget: 3, devis: 2, cher: 2, euro: 2, gratuit: 1 },
            build: () => ({
                title: 'TARIFS SUR-MESURE — GRILLE INDICATIVE',
                lines: CFG.prices.map(p =>
                    '  ' + String(p.price).padStart(6) + '  ' + p.label + ' (' + p.desc + ')'
                ).concat([
                    '',
                    '  chaque projet est chiffré sur devis : gratuit, sans engagement.',
                    '  le plus simple : décris-moi ta problématique → ' + CFG.contactEmail + ' ou formulaire #contact',
                    '  [note] mes outils existants (LibreAnalytics, outils crypto, guides…) restent',
                    '  gratuits et open source.'
                ])
            })
        },
        {
            id: 'qui',
            terms: { berru: 3, gael: 3, autodidacte: 3, parcours: 3, experience: 2, presente: 2, histoire: 1, parcours: 3 },
            build: () => ({
                title: 'QUI SUIS-JE',
                lines: [
                    '  développeur autodidacte depuis 2020, artisan du code souverain et open source.',
                    '  web, données, électronique : je construis des outils qui restent la propriété',
                    '  de ceux qui les utilisent.',
                    '  tout mon travail est visible ligne par ligne depuis 2020 :',
                    '  → ' + CFG.github
                ]
            })
        },
        {
            id: 'libreanalytics',
            terms: { analytics: 3, libreanalytics: 3, tracking: 2, google: 2, matomo: 2, plausible: 2, umami: 1, stats: 2, statistique: 2, cookie: 2, privee: 2, gafam: 2, souverain: 2 },
            build: () => ({
                title: 'LIBREANALYTICS — ALTERNATIVE SOUVERAINE À GOOGLE ANALYTICS',
                lines: [
                    '  auto-hébergé, sans cookies, conforme RGPD, open source.',
                    "  tourne sur un mutualisé classique — pas besoin de Docker ni de VPS.",
                    "  dashboard complet, accès API, doc d'intégration complète.",
                    '  → ' + CFG.libreAnalytics
                ]
            })
        },
        {
            id: 'hardware',
            terms: { arduino: 3, pcb: 3, electronique: 3, badge: 2, nfc: 3, circuit: 2, midi: 3, daw: 3, radio: 2, rc: 2, moteur: 1, servo: 1, robotique: 2, lego: 2, easyeda: 2, shield: 2, firmware: 2, carte: 1 },
            build: (question) => {
                const lines = [
                    "  conception de circuits et PCB (EasyEDA), firmware Arduino, NFC, radio RC",
                    "  (jusqu'à 2 km), contrôleurs MIDI pour DAW.",
                    '',
                    '  réalisations :'
                ];
                const hits = DB.isReady() ? DB.search(question) : [];
                if (hits.length) {
                    hits.slice(0, 2).forEach(p => lines.push('  · ' + p.title + ' — ' + String(p.shortDesc || '').replace(/\s+/g, ' ').trim()));
                } else {
                    lines.push("  · NFC Badge, RC Motor, contrôleurs MIDI, shield Arduino-Lego", "    (tape 'projets nfc' ou 'projets arduino' pour le détail)");
                }
                lines.push('', '  indicatif : ~400 € le PCB — sur devis selon complexité.', '  code et détails : ' + CFG.github);
                return { title: 'ÉLECTRONIQUE & PCB', lines };
            }
        },
        {
            id: '3d',
            terms: { '3d': 3, threejs: 3, webgl: 3, immersion: 2, jeu: 2, scroll: 2, animation: 2, three: 3 },
            build: () => ({
                title: '3D & WEBGL',
                lines: [
                    '  intégration Three.js / WebGL : animations synchronisées au scroll,',
                    '  univers immersifs, dataviz 3D.',
                    "  réalisations : 3D Scroll Animator (générateur d'animations), jeu d'immersion",
                    '  dans le navigateur, heat map 3D crypto.',
                    '',
                    "  indicatif : ~700 € l'intégration — sur devis selon la scène."
                ]
            })
        },
        {
            id: 'dataviz',
            terms: { dashboard: 3, tableau: 2, bord: 1, dataviz: 3, visualisation: 3, donnee: 2, chart: 2, graphique: 2, sql: 3, csv: 2, excel: 2, json: 2, reporting: 2, cron: 2, admin: 2, reporting: 2 },
            build: () => ({
                title: 'DASHBOARDS & DATAVIZ',
                lines: [
                    '  tableaux de bord sur mesure : analytics multi-sites, wallets crypto',
                    '  (API CoinGecko), charts TradingView, agenda, météo, rapports clients',
                    '  automatisés par CRON.',
                    '  éditeur SQL-to-Map : import CSV / Excel / JSON, cartographie automatique.',
                    '  interfaces admin multi-bases, exports.',
                    '',
                    '  indicatif : ~900 € — sur devis selon les sources à brancher.'
                ]
            })
        },
        {
            id: 'site',
            terms: { site: 3, vitrine: 3, cms: 3, web: 1, ecommerce: 3, commerce: 3, boutique: 2, formulaire: 2, galerie: 2, responsive: 2, refonte: 2, marque: 1, vetement: 1 },
            build: () => ({
                title: 'SITES VITRINES & CMS',
                lines: [
                    '  sites vitrine et CMS développés from scratch, sans template : formulaires,',
                    '  galeries, responsive, analytics intégrée.',
                    '  réalisations : Golden Dessert (CMS pâtissier), marque + e-commerce',
                    '  Berru Clothing (2016–2018).',
                    '',
                    '  indicatif : ~600 € — sur devis.'
                ]
            })
        },
        {
            id: 'crypto',
            terms: { crypto: 3, bitcoin: 3, btc: 3, blockchain: 3, trading: 2, binance: 2, coingecko: 2, wallet: 2, investigation: 3, forensic: 2, scam: 2, solana: 2, transaction: 1 },
            build: () => ({
                title: 'CRYPTO & BLOCKCHAIN',
                lines: [
                    '  outils traders : API TradingView, CoinGecko, Binance (analyse de marché).',
                    '  investigation blockchain : traçage de transactions Bitcoin, identification',
                    '  des plateformes KYC, diagrammes automatiques.',
                    '  guide débutant : transactions sécurisées, wallet sans KYC, stacking Solana.',
                    '',
                    "  tout est gratuit et open source — tape 'projets crypto' pour la liste."
                ]
            })
        },
        {
            id: 'api',
            terms: { api: 3, integration: 2, websocket: 2, rest: 2, connecter: 2, brancher: 2 },
            build: () => ({
                title: 'INTÉGRATION API',
                lines: [
                    '  connexion aux APIs tierces : Binance, CoinGecko, Blockstream, TradingView,',
                    '  Google Calendar, météo… et aux APIs privées de vos propres outils.',
                    "  plein de mes projets reposent là-dessus — tape 'projets api'."
                ]
            })
        },
        {
            id: 'contact',
            terms: { contact: 3, mail: 2, email: 2, parler: 2, discuter: 2, message: 2, joindre: 3, telephone: 2, appelle: 1, reponse: 1, reunion: 1 },
            build: () => ({
                title: 'CONTACT',
                lines: [
                    '  mail ........ ' + CFG.contactEmail,
                    '  formulaire .. dans le menu du site (#contact)',
                    "  commence par me décrire ta problématique actuelle et en quoi je peux t'aider.",
                    '  devis gratuit, réponse humaine — pas de tunnel de vente.'
                ]
            })
        },
        // ---- le portfolio comme filet de sécurité : si aucune intention
        //      curative ne matche, on fouille la base de projets.
        {
            id: 'projets',
            terms: { projet: 3, portfolio: 3, realisation: 3, exemple: 2, demo: 2, montre: 2, voir: 2, travaux: 2, github: 2, ligne: 1 },
            build: (question) => {
                if (!DB.isReady()) {
                    return {
                        title: 'PORTFOLIO',
                        lines: [
                            "  la base de projets n'a pas pu être chargée pour le moment.",
                            '  → ' + CFG.github
                        ]
                    };
                }
                const hits = DB.search(question);
                if (hits.length) {
                    const lines = [];
                    hits.forEach((p, i) => {
                        const desc = String(p.longDesc || p.shortDesc || '').replace(/\s+/g, ' ').trim();
                        lines.push('  ' + (i + 1) + '. ' + p.title + ' — ' + String(p.shortDesc || '').replace(/\s+/g, ' ').trim());
                        if (desc) lines.push('     ' + desc.slice(0, 140) + (desc.length > 140 ? '…' : ''));
                        if (p.link && p.link !== '#') lines.push('     → ' + p.link);
                        if (p.tags && p.tags.length) lines.push('     tags : ' + p.tags.slice(0, 4).join(' · '));
                        lines.push('');
                    });
                    lines.push("  [note] ajoute un thème pour affiner : 'projets nfc', 'projets dashboard', 'projets crypto'…");
                    return { title: 'RÉALISATIONS CORRESPONDANTES', lines: lines.slice(0, -1) };
                }
                return {
                    title: 'PORTFOLIO — ' + DB.count() + ' PROJETS',
                    lines: DB.categories().map(c =>
                        '  ' + String(c.count).padStart(3) + '  ' + c.name
                    ).concat([
                        '',
                        '  ajoute un thème à ta question pour fouiller : nfc, pcb, dashboard,',
                        '  crypto, 3d, cms…',
                        '  tout le code : ' + CFG.github
                    ])
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

    // Raffinage : un mot-clé fort suffit à trancher même si la phrase est vague
    const REFINE_TERMS = {
        pcb: 'hardware', arduino: 'hardware', nfc: 'hardware', badge: 'hardware', midi: 'hardware',
        dashboard: 'dataviz', sql: 'dataviz', dataviz: 'dataviz',
        crypto: 'crypto', bitcoin: 'crypto', blockchain: 'crypto',
        vitrine: 'site', cms: 'site', ecommerce: 'site',
        analytics: 'libreanalytics', tracking: 'libreanalytics', cookies: 'libreanalytics',
        '3d': '3d', threejs: '3d', webgl: '3d',
        tarif: 'prix', devis: 'prix',
        framework: 'methode', souverain: 'methode',
        github: 'projets'
    };

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
            : ['Quels sont tes services ?', 'Tes tarifs ?', 'Comment travailles-tu ?', 'Montre-moi tes projets'];
        return { intent: null, suggestions };
    }

    const SUGGEST_LABELS = {
        services: "Quels sont tes services ?",
        skills: "Tes compétences / technologies ?",
        methode: "Comment travailles-tu ?",
        philosophie: "Pourquoi tout est gratuit ?",
        prix: "Tes tarifs ?",
        qui: "Qui es-tu ?",
        libreanalytics: "C'est quoi LibreAnalytics ?",
        hardware: "Tu fais de l'électronique / PCB ?",
        '3d': "De la 3D Web ?",
        dataviz: "Des dashboards sur mesure ?",
        site: "Des sites vitrines / CMS ?",
        crypto: "Des outils crypto ?",
        contact: "Comment te contacter ?",
        api: "Tu intègres des APIs ?",
        projets: "Montre-moi tes projets"
    };

    // ============================================================
    // 4. FRONT TERMINAL — affichage texte + prompt + historique
    //    (identique à LibreAssistant V2-CLI, sans module mémoire :
    //     visiteur anonyme, zéro stockage.)
    // ============================================================
    const $panel  = document.getElementById('aiPanel');
    const $conv   = document.getElementById('aiConversation');
    const $input  = document.getElementById('aiInput');
    const $toggle = document.getElementById('aiToggle');
    const $badge   = document.createElement('span');
    $badge.className = 'ai-badge alert';
    $badge.id = 'aiBadge';
    $badge.textContent = '!';
    $toggle.appendChild($badge);

    document.getElementById('aiPrompt').textContent = 'visiteur@gael-berru:~$';

    function scrollBottom() { $conv.scrollTop = $conv.scrollHeight; }

    /** Écho d'une ligne texte — textContent uniquement, aucune injection HTML */
    function echo(text, cls = 't-bot') {
        const div = document.createElement('div');
        div.className = cls;
        div.textContent = text;
        $conv.appendChild(div);
        scrollBottom();
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
        '  services ..... mes services',
        '  prix ......... la grille indicative',
        '  methode ...... ma façon de bosser',
        "  projets ...... mon portfolio ('projets nfc' pour filtrer par thème)",
        '  contact ...... me joindre',
        "  clear ........ efface l'écran",
        '  exit ......... ferme le terminal',
        '',
        'SINON, pose ta question en langage naturel :',
        '  "tu sais faire quoi ?" · "combien coûte un site ?" · "comment travailles-tu ?"',
        '  "c\'est quoi LibreAnalytics ?" · "montre-moi un projet NFC" · "comment te contacter ?"'
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
        echo('visiteur@gael-berru:~$ ' + q, 't-user');

        const nq = normalize(q);

        if (nq === 'exit') { closePanel(); return; }
        if (nq === 'clear') { doClear(); return; }
        if (nq === 'help' || nq === '?' || nq === 'man') { echoBlock(HELP[0], HELP.slice(1)); return; }

        // Raccourcis commandes → intents directs
        const shortcuts = { services: 'services', prix: 'prix', methode: 'methode', contact: 'contact', tarifs: 'prix' };
        if (shortcuts[nq]) { runIntent(INTENTS.find(i => i.id === shortcuts[nq]), q); return; }
        if (nq === 'projets' || nq.startsWith('projets ')) { runIntent(INTENTS.find(i => i.id === 'projets'), q); return; }

        run(q);
    }

    async function run(question) {
        thinking(true);
        try {
            // La base de projets peut nécessiter un fetch : on "réfléchit" pendant ce temps
            await DB.load();
            const { intent, suggestions } = resolve(question);
            if (intent) {
                runIntent(intent, question);
                return;
            }
            // Filet de sécurité FAQ 2.0 : on fouille la base de projets
            if (DB.isReady() && DB.search(question).length) {
                runIntent(INTENTS.find(i => i.id === 'projets'), question);
                return;
            }
            echo('je ne suis pas sûr de saisir. suggestions :', 't-warn');
            suggestions.forEach(s => echo('  $ ' + s, 't-dim'));
            echo('(tape "help" pour la liste des commandes)', 't-dim');
        } finally {
            thinking(false);
        }
    }

    function runIntent(intent, question) {
        const out = intent.build(question);
        echoBlock(out.title, out.lines);
    }

    function doClear() {
        $conv.innerHTML = '';
        banner();
    }

    function banner() {
        echo('berru·faq — assistant de renseignement · 100% local', 't-title');
        echo('mes services · mes réalisations · mes tarifs · ma méthode', 't-dim');
        echo('aucune donnée stockée, aucun cookie — RGPD by design.', 't-dim');
        echo('tape "help" pour les commandes, ou pose ta question en langage naturel.', 't-dim');
        echo('');
    }

    function openPanel() {
        $panel.classList.add('active');
        $badge.hidden = true;
        if (!$conv.children.length) banner();
        $input.focus();
    }

    function closePanel() { $panel.classList.remove('active'); }

    // ============================================================
    // 5. ÉVÉNEMENTS
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

    // Préchargement silencieux de la base de projets (depuis window.projectsDB
    // si le menu du site l'a déjà chargée, sinon fetch GitHub lecture seule).
    DB.load();
    </script>
</body>

</html>