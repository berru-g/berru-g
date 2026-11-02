<?php

// LibreAssistant CLI — assistant analytics 100% local, EN STANDALONE.
// requêtes SQL que dashboard, et le site à analyser se choisit
// DANS LE TERMINAL : `sites` puis `use <id|nom|SP_code>`.
// CHANGER EN PROD :
// sans login, l'outil liste tous les sites du serveur local. 
// Probleme acce absolue aux nom de site et key public si non connecté! pour l'instan balek ya degun
// Si connecté, affiche les data du user_id


require_once __DIR__ . '/../includes/config.php';
if (is_file(__DIR__ . '/../includes/auth.php')) {
    require_once __DIR__ . '/../includes/auth.php';
}

$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);

// ============================================================
// MINI-API — mêmes requêtes que dashboard.php, rien d'inventé
// ============================================================
if (isset($_GET['api'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_GET['api'];
    $userId = $_SESSION['user_id'] ?? null;
    $isLoggedIn = class_exists('Auth') && Auth::isLoggedIn();

    try {
        // ---- Liste des sites -----------------------------------
        if ($action === 'sites') {
            if ($isLoggedIn) {
                $stmt = $pdo->prepare("SELECT id, site_name, domain, tracking_code FROM user_sites WHERE user_id = ? ORDER BY id DESC");
                $stmt->execute([$userId]);
            } else {
                $stmt = $pdo->query("SELECT id, site_name, domain, tracking_code FROM user_sites ORDER BY id DESC");
            }
            echo json_encode(['success' => true, 'sites' => $stmt->fetchAll()]);
            exit;
        }

        // ---- Stats d'un site ----------------------------------
        if ($action === 'stats') {
            $siteQuery = trim($_GET['site'] ?? '');
            if ($siteQuery === '') {
                http_response_code(400);
                echo json_encode(['error' => "Aucun site sélectionné. Tapez 'sites' puis 'use <id|nom|SP_code>'."]);
                exit;
            }
            $period = max(1, (int)($_GET['period'] ?? 30));

            // Résolution — accepte aussi la syntaxe affichée dans l'aide :
            // `use <6|smart-pixel|SP_79747769>` : on garde le 1er identifiant qui matche.
            $candidates = [];
            $cleaned = trim($siteQuery, "<> \t");
            foreach (explode('|', $cleaned) as $part) {
                $part = trim($part);
                if ($part !== '') $candidates[] = $part;
            }
            if (!$candidates) $candidates = [$siteQuery];

            $site = false;
            foreach ($candidates as $candidate) {
                if (ctype_digit($candidate)) {
                    $stmt = $pdo->prepare("SELECT id, site_name, domain, tracking_code, user_id FROM user_sites WHERE id = ? LIMIT 1");
                    $stmt->execute([$candidate]);
                } elseif (preg_match('/^SP_[0-9a-f]+$/i', $candidate)) {
                    $stmt = $pdo->prepare("SELECT id, site_name, domain, tracking_code, user_id FROM user_sites WHERE tracking_code = ? LIMIT 1");
                    $stmt->execute([$candidate]);
                } else {
                    $stmt = $pdo->prepare("SELECT id, site_name, domain, tracking_code, user_id FROM user_sites WHERE site_name = ? LIMIT 1");
                    $stmt->execute([$candidate]);
                }
                $site = $stmt->fetch();
                if ($site) break;
                // Fallback nom insensible à la casse / tirets vs espaces
                if (!ctype_digit($candidate) && !preg_match('/^SP_/i', $candidate)) {
                    $stmt = $pdo->prepare("SELECT id, site_name, domain, tracking_code, user_id FROM user_sites WHERE LOWER(site_name) = LOWER(?) OR REPLACE(LOWER(site_name), ' ', '-') = LOWER(?) LIMIT 1");
                    $stmt->execute([$candidate, $candidate]);
                    $site = $stmt->fetch();
                    if ($site) break;
                }
            }
            if (!$site) {
                http_response_code(404);
                echo json_encode(['error' => "Site introuvable : " . $siteQuery . ". Tapez 'sites' pour la liste."]);
                exit;
            }
            if ($isLoggedIn && (int)$site['user_id'] !== (int)$userId) {
                http_response_code(403);
                echo json_encode(['error' => 'Accès interdit à ce site.']);
                exit;
            }
            $selectedSiteId = (int)$site['id'];
            $dateFilter = date('Y-m-d H:i:s', strtotime("-$period days"));

            // Visiteurs uniques sur la période (identique dashboard)
            $stmt = $pdo->prepare("SELECT COUNT(DISTINCT ip_address) FROM smart_pixel_tracking WHERE site_id = ? AND timestamp >= ?");
            $stmt->execute([$selectedSiteId, $dateFilter]);
            $uniqueVisitorsPeriod = (int)$stmt->fetchColumn();

            // SOURCES
            $stmt = $pdo->prepare("SELECT source, COUNT(*) as count FROM smart_pixel_tracking WHERE site_id = ? AND timestamp >= ? GROUP BY source ORDER BY count DESC");
            $stmt->execute([$selectedSiteId, $dateFilter]);
            $sources = $stmt->fetchAll();

            // TOP PAGES
            $stmt = $pdo->prepare("SELECT page_url, COUNT(*) as views FROM smart_pixel_tracking WHERE site_id = ? AND page_url != 'direct' AND timestamp >= ? GROUP BY page_url ORDER BY views DESC LIMIT 10");
            $stmt->execute([$selectedSiteId, $dateFilter]);
            $topPages = $stmt->fetchAll();

            // PAYS
            $stmt = $pdo->prepare("SELECT country, COUNT(*) as visits FROM smart_pixel_tracking WHERE site_id = ? AND timestamp >= ? GROUP BY country ORDER BY visits DESC LIMIT 10");
            $stmt->execute([$selectedSiteId, $dateFilter]);
            $countries = $stmt->fetchAll();

            // APPAREILS
            $stmt = $pdo->prepare("
                SELECT
                    CASE
                        WHEN user_agent LIKE '%Mobile%' THEN 'Mobile'
                        WHEN user_agent LIKE '%Tablet%' THEN 'Tablet'
                        ELSE 'Desktop'
                    END as device,
                    COUNT(*) as count
                FROM smart_pixel_tracking
                WHERE site_id = ? AND timestamp >= ?
                GROUP BY device
                ORDER BY count DESC
            ");
            $stmt->execute([$selectedSiteId, $dateFilter]);
            $devices = $stmt->fetchAll();

            // NAVIGATEURS
            $stmt = $pdo->prepare("
                SELECT
                    CASE
                        WHEN user_agent LIKE '%Chrome%' THEN 'Chrome'
                        WHEN user_agent LIKE '%Firefox%' THEN 'Firefox'
                        WHEN user_agent LIKE '%Safari%' THEN 'Safari'
                        WHEN user_agent LIKE '%Edge%' THEN 'Edge'
                        ELSE 'Other'
                    END as browser,
                    COUNT(*) as count
                FROM smart_pixel_tracking
                WHERE site_id = ? AND timestamp >= ?
                GROUP BY browser
                ORDER BY count DESC
            ");
            $stmt->execute([$selectedSiteId, $dateFilter]);
            $browsers = $stmt->fetchAll();

            // STATS JOURNALIÈRES (7 derniers jours, comme le dashboard)
            $stmt = $pdo->prepare("
                SELECT DATE(timestamp) as date, COUNT(*) as visits, COUNT(DISTINCT ip_address) as unique_visitors
                FROM smart_pixel_tracking
                WHERE site_id = ? AND timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(timestamp)
                ORDER BY date ASC
            ");
            $stmt->execute([$selectedSiteId]);
            $dailyStats = $stmt->fetchAll();

            // SESSIONS
            $stmt = $pdo->prepare("
                SELECT session_id, COUNT(*) as page_views, MIN(timestamp) as first_visit, MAX(timestamp) as last_visit
                FROM smart_pixel_tracking
                WHERE site_id = ? AND session_id != '' AND timestamp >= ?
                GROUP BY session_id
                ORDER BY page_views DESC
                LIMIT 10
            ");
            $stmt->execute([$selectedSiteId, $dateFilter]);
            $sessionData = $stmt->fetchAll();

            // Temps moyen de session (identique dashboard)
            $avgSessionTime = 0;
            if (count($sessionData) > 0) {
                $totalSessionTime = 0;
                foreach ($sessionData as $session) {
                    $totalSessionTime += strtotime($session['last_visit']) - strtotime($session['first_visit']);
                }
                $avgSessionTime = round($totalSessionTime / count($sessionData) / 60, 1);
            }

            echo json_encode([
                'success' => true,
                'site'    => ['id' => $selectedSiteId, 'site_name' => $site['site_name'], 'domain' => $site['domain'], 'tracking_code' => $site['tracking_code']],
                'period'  => $period,
                'topPages'             => $topPages,
                'countries'            => $countries,
                'devices'              => $devices,
                'browsers'             => $browsers,
                'sources'               => $sources,
                'dailyStats'           => $dailyStats,
                'sessionData'          => $sessionData,
                'uniqueVisitorsPeriod' => $uniqueVisitorsPeriod,
                'avgSessionTime'       => $avgSessionTime,
            ]);
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Action inconnue.']);
        exit;

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur base de données : ' . $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LibreAssistant CLI</title>
    <style>
        /* Front : un vrai terminal pleine page, rien d'autre */
        :root {
            --term-bg: #0b0d12;
            --term-fg: #c9f2d0;
            --term-green: #4af626;
            --term-accent: #ab9ff2;
            --term-pink: #ff86e9;
            --term-dim: #5c6a72;
            --term-amber: #ffd75f;
        }

        html, body {
            margin: 0;
            height: 100%;
            background: var(--term-bg);
            color: var(--term-fg);
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 14px;
        }

        .terminal {
            display: flex;
            flex-direction: column;
            height: 100vh;
            padding: 12px 16px;
            box-sizing: border-box;
        }

        /* Sortie : scroll auto, préformaté */
        .term-out {
            flex: 1;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-word;
            line-height: 1.55;
        }

        .term-out::-webkit-scrollbar { width: 8px; }
        .term-out::-webkit-scrollbar-thumb { background: var(--term-dim); }

        .t-user   { color: var(--term-accent); }
        .t-bot    { color: var(--term-fg); }
        .t-title  { color: var(--term-green); }
        .t-dim    { color: var(--term-dim); }
        .t-alert  { color: var(--term-pink); }
        .t-warn   { color: var(--term-amber); }

        .ai-cursor {
            display: inline-block;
            width: 8px;
            background: var(--term-green);
            animation: blink 1s steps(2) infinite;
        }

        @keyframes blink { 50% { opacity: 0; } }

        /* Ligne de saisie : EN HAUT, prompt + champ invisible */
        .term-input-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #1a2030;
        }

        .term-prompt { color: var(--term-green); white-space: nowrap; }

        #cmdInput {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: var(--term-fg);
            font-family: inherit;
            font-size: inherit;
            caret-color: var(--term-green);
        }

        /* Barre d'état : site courant + période */
        .term-status {
            color: var(--term-dim);
            font-size: 12px;
            padding-top: 6px;
            border-top: 1px solid #1a2030;
            margin-top: 6px;
            user-select: none;
        }
    </style>
</head>

<body>
    <div class="terminal">
        <div class="term-input-row">
            <span class="term-prompt" id="prompt">libreanalytics@cli:~$</span>
            <input type="text" id="cmdInput" autocomplete="off" spellcheck="false">
        </div>
        <div class="term-out" id="termOut"></div>
        <div class="term-status" id="status">aucun site chargé — tapez 'sites' puis 'use &lt;id|nom|SP_code&gt;'</div>
    </div>

    <script>
    'use strict';

    // ============================================================
    // 1. ÉTAT — données du site courant (même forme que la V2)
    // ============================================================
    let aiData = null;           // rempli après `use <site>`
    let currentSite = null;      // { id, site_name, domain, tracking_code }
    let currentPeriod = 30;

    const API = location.pathname; // ce fichier sert aussi de mini-API (?api=...)
    const LOG_URL = 'assistant_log.php';
    const DOC_SITE = 'https://gael-berru.com/LibreAnalytics/doc/';

    const pct = (part, total) => total > 0 ? Math.round(part / total * 100) : 0;
    const fmt = (n) => new Intl.NumberFormat('fr-FR').format(Number(n) || 0);
    const normalize = (q) => q.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

    // ============================================================
    // 2. CALCULS RÉELS (INCHANGÉS)
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

    // ============================================================
    // 3. DOC GITHUB (cache localStorage 7j) (INCHANGÉ)
    // ============================================================
    const DocSearch = (function () {
        const CACHE_KEY = 'libreassistant_doc_v1';
        const CACHE_TTL = 7 * 24 * 3600 * 1000;
        let sections = [];

        async function load() {
            try {
                const cached = JSON.parse(localStorage.getItem(CACHE_KEY) || 'null');
                if (cached && Date.now() - cached.at < CACHE_TTL) { sections = cached.sections; return sections; }
            } catch (e) {}
            try {
                const res = await fetch('https://api.github.com/repos/berru-g/LibreAnalytics/readme', {
                    headers: { 'Accept': 'application/vnd.github.v3+json' }
                });
                if (!res.ok) throw new Error(res.status);
                const data = await res.json();
                const bytes = Uint8Array.from(atob(data.content), c => c.charCodeAt(0));
                const md = new TextDecoder('utf-8').decode(bytes);
                sections = parseMarkdown(md);
                try { localStorage.setItem(CACHE_KEY, JSON.stringify({ at: Date.now(), sections })); } catch (e) {}
            } catch (e) { sections = []; }
            return sections;
        }

        function parseMarkdown(md) {
            const lines = md.split('\n');
            const out = [];
            let current = null;
            for (const line of lines) {
                const m = line.match(/^(#{2,3})\s+(.*)$/);
                if (m) { if (current) out.push(current); current = { title: m[2].trim(), body: [] }; }
                else if (current && line.trim() !== '---') { current.body.push(line); }
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
    // 4. MÉMOIRE DE CONVERSATION (assistant_log.php) (INCHANGÉE)
    // ============================================================
    const Memory = (function () {
        async function loadHistory() {
            if (!currentSite) return;
            try {
                const res = await fetch(`${LOG_URL}?action=history&site_id=${currentSite.id}`, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                if (!data.messages || !data.messages.length) return;
                for (const m of data.messages) {
                    m.role === 'user' ? echo(m.content, 't-user', { silent: true })
                                      : echo(m.content, 't-bot', { silent: true });
                }
            } catch (e) {}
        }

        function log(role, content) {
            if (!currentSite) return;
            try {
                fetch(`${LOG_URL}?action=log`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ site_id: currentSite.id, role, content: content.replace(/\s+/g, ' ').slice(0, 2000) })
                }).catch(() => {});
            } catch (e) {}
        }

        async function clear() {
            if (!currentSite) return;
            try { await fetch(`${LOG_URL}?action=clear&site_id=${currentSite.id}`); } catch (e) {}
        }

        return { loadHistory, log, clear };
    })();

    // ============================================================
    // 5. MOTEUR D'INTENTS — mêmes termes, mêmes poids, sortie texte
    // ============================================================
    const INTENTS = [
        {
            id: 'resume',
            terms: { resume: 3, global: 2, vue: 2, ensemble: 2, synthese: 3, overview: 3, situation: 2, stats: 1, statistique: 1 },
            build: () => ({
                title: 'VUE D\'ENSEMBLE',
                lines: [
                    `  visiteurs uniques ......... ${fmt(aiData.uniqueVisitorsPeriod)} (${aiData.period} jours)`,
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
                    title: `TENDANCE SUR ${aiData.dailyStats.length} JOURS (7 derniers jours)`,
                    lines: [sparklineAscii(aiData.dailyStats), '',
                        `  évolution ........ [${arrow}] ${g === null ? '—' : (g > 0 ? '+' : '') + g + '%'}`,
                        `  total 7j ......... ${fmt(aiData.dailyStats.reduce((s, x) => s + Number(x.visits), 0))} visites`,
                        proj !== null ? `  projection 7j ... ~${fmt(proj)} visites (linéaire)` : null,
                        `  [note] ${verdict}`
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
        {
            id: 'doc',
            terms: { doc: 3, documentation: 3, installer: 3, installation: 3, integrer: 3, integration: 3, code: 1, suivi: 2, pixel: 2, script: 2, comment: 1, api: 2, webhook: 2, configurer: 3, regler: 2 },
            build: (question) => {
                const sec = DocSearch.search(question);
                if (!sec) {
                    return { title: 'DOCUMENTATION', lines: ['  passage introuvable dans la doc.', `  consultez-la directement : ${DOC_SITE}`] };
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
    // 6. SORTIE TERMINAL — la commande s'affiche AU-DESSUS du résultat
    // ============================================================
    const $out = document.getElementById('termOut');
    const $input = document.getElementById('cmdInput');
    const $status = document.getElementById('status');
    const $prompt = document.getElementById('prompt');

    function scrollBottom() { $out.scrollTop = $out.scrollHeight; }

    function echo(text, cls = 't-bot', { silent = false } = {}) {
        const div = document.createElement('div');
        div.className = cls;
        div.textContent = text;
        $out.appendChild(div);
        scrollBottom();
        if (!silent) Memory.log(cls === 't-user' ? 'user' : 'bot', text);
    }

    function echoBlock(title, lines) {
        echo('┌─ ' + title, 't-title');
        lines.forEach(l => echo('│ ' + l, 't-bot'));
        echo('└' + '─'.repeat(Math.max(title.length + 3, 2)), 't-title');
    }

    function thinking(on) {
        if (on) {
            const div = document.createElement('div');
            div.id = 'thinking';
            div.className = 't-dim';
            div.textContent = '… ';
            div.innerHTML += '<span class="ai-cursor">&nbsp;</span>';
            $out.appendChild(div);
        } else {
            document.getElementById('thinking')?.remove();
        }
        scrollBottom();
    }

    function sparklineAscii(rows, width = 40) {
        if (!rows || rows.length < 2) return '';
        const values = rows.map(x => Number(x.visits) || 0);
        const max = Math.max(...values);
        return rows.map((x, i) => {
            const n = max > 0 ? Math.round((values[i] / max) * (width - 14)) : 0;
            const bar = '█'.repeat(Math.max(n, values[i] > 0 ? 1 : 0));
            return `  ${String(x.date).padEnd(10)} ${bar.padEnd(width - 14)} ${fmt(values[i])}`;
        }).join('\n');
    }

    function setStatus() {
        $status.textContent = currentSite
            ? `site : ${currentSite.site_name} (#${currentSite.id}, ${currentSite.domain || '—'}) · période : ${currentPeriod} j · ${fmt(aiData?.uniqueVisitorsPeriod ?? 0)} visiteurs uniques`
            : "aucun site chargé — tapez 'sites' puis 'use <id|nom|SP_code>'";
        $prompt.textContent = currentSite
            ? `libreanalytics@cli:${currentSite.site_name}$`
            : 'libreanalytics@cli:~$';
    }

    // ============================================================
    // 7. COMMANDES — sites, use, period, puis langage naturel
    // ============================================================
    async function fetchJson(url) {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.error || 'HTTP ' + res.status);
        return data;
    }

    async function cmdSites() {
        const data = await fetchJson(`${API}?api=sites`);
        if (!data.sites.length) { echo('aucun site enregistré sur ce serveur.', 't-warn'); return; }
        echoBlock('SITES DISPONIBLES', data.sites.map(s =>
            `  #${s.id}  ${s.site_name}  (${s.domain || '—'})  [${s.tracking_code}]`
        ).concat(["  charger un site : use <id|nom|SP_code>"]));
    }

    async function cmdUse(query) {
        thinking(true);
        try {
            const data = await fetchJson(`${API}?api=stats&site=${encodeURIComponent(query)}&period=${currentPeriod}`);
            currentSite = data.site;
            aiData = {
                topPages: data.topPages, countries: data.countries, devices: data.devices,
                browsers: data.browsers, sources: data.sources, dailyStats: data.dailyStats,
                sessionData: data.sessionData, totalVisits: data.uniqueVisitorsPeriod,
                uniqueVisitorsPeriod: data.uniqueVisitorsPeriod, avgSessionTime: data.avgSessionTime,
                period: data.period
            };
            localStorage.setItem('libreassistant_cli_site', query);
            setStatus();
            echoBlock(`SITE CHARGÉ — ${currentSite.site_name}`, [
                `  id .............. #${currentSite.id}`,
                `  domaine .......... ${currentSite.domain || '—'}`,
                `  tracking code .... ${currentSite.tracking_code}`,
                `  période .......... ${data.period} jours`,
                `  visiteurs uniques . ${fmt(data.uniqueVisitorsPeriod)}`,
                '  posez vos questions en langage naturel, ou tapez help.'
            ]);
            // Alerte proactive : chute ou pic détecté au chargement
            const a = Compute.anomaly();
            if (a) {
                const word = a.type === 'chute' ? 'CHUTE' : 'PIC';
                echo(`[ALERTE] ${word} DE TRAFIC DÉTECTÉ${a.type === 'chute' ? 'E' : ''} : ${a.pct}% vs période précédente. tapez "tendance" pour le détail.`,
                     a.type === 'chute' ? 't-alert' : 't-warn');
            }
            Memory.loadHistory();
        } catch (e) {
            echo('erreur : ' + e.message, 't-alert');
        } finally {
            thinking(false);
        }
    }

    async function cmdPeriod(days) {
        if (!currentSite) { echo("aucun site chargé. tapez 'sites' puis 'use <...>'.", 't-warn'); return; }
        const n = parseInt(days, 10);
        if (!n || n < 1 || n > 3650) { echo('usage : period <nombre de jours>', 't-warn'); return; }
        currentPeriod = n;
        await cmdUse(currentSite.site_name);
    }

    const HELP = [
        'COMMANDES',
        '  sites ................ liste les sites analyser',
        '  use <id|nom|SP_code> . charge un site (obligatoire avant les questions)',
        '  period <jours> ........ change la période d\'analyse (ex: period 7)',
        '  trend ................ raccourci tendance (graphe ASCII)',
        '  clear ................ efface l\'écran ET la mémoire de conversation',
        '  help ................. cette aide',
        '',
        'SINON, posez votre question en langage naturel :',
        '  "résume-moi mes statistiques" · "top pages" · "d\'où vient mon trafic ?"',
        '  "tendance" · "par pays" · "appareils" · "sessions" · "conseils" · "doc / installer"'
    ];

    const cmdHistory = [];
    let histPos = -1;

    async function submitLine() {
        const q = $input.value.trim();
        if (!q) return;
        $input.value = '';
        cmdHistory.push(q);
        histPos = cmdHistory.length;
        echo(`${$prompt.textContent} ${q}`, 't-user', { silent: true }); // commande au-dessus du résultat
        Memory.log('user', q);

        const nq = normalize(q);

        if (nq === 'clear') { $out.innerHTML = ''; await Memory.clear(); banner(); echo('mémoire de conversation effacée.', 't-dim'); return; }
        if (nq === 'help' || nq === '?' || nq === 'man') { echoBlock(HELP[0], HELP.slice(1)); return; }
        if (nq === 'sites' || nq === 'site list' || nq === 'ls') { await cmdSites(); return; }
        if (nq.startsWith('use ') || nq.startsWith('site ')) { await cmdUse(q.replace(/^(use|site)\s+/i, '')); return; }
        if (nq.startsWith('period ') || nq.startsWith('période ')) { await cmdPeriod(q.split(/\s+/)[1]); return; }
        if (nq === 'trend' || nq === 'tendance') { runIntent(INTENTS.find(i => i.id === 'tendance'), q); return; }

        // Langage naturel → moteur d'intents (nécessite un site chargé)
        if (!currentSite) {
            echo("aucun site chargé. tapez 'sites' puis 'use <id|nom|SP_code>'.", 't-warn');
            return;
        }
        thinking(true);
        try {
            if (!DocSearch.isReady()) await DocSearch.load();
            const { intent, suggestions } = resolve(q);
            if (intent) runIntent(intent, q);
            else {
                echo('commande inconnue. suggestions :', 't-warn');
                suggestions.forEach(s => echo(`  $ ${s}`, 't-dim'));
                echo('(tapez "help" pour la liste)', 't-dim');
            }
        } finally {
            thinking(false);
        }
    }

    function runIntent(intent, question) {
        const out = intent.build(question);
        echoBlock(out.title, out.lines);
    }

    function banner() {
        echo('LibreAnalytics CLI v1 ', 't-title');
        //echo('<a href="index.php" target="_blank">Connexion</a>', 't-dim');
        echo("tapez 'sites' pour lister, puis 'use <id|nom|SP_code>' pour charger.", 't-dim');
        echo("tapez 'help' pour toutes les commandes.", 't-dim');
        echo('');
    }

    // ============================================================
    // 8. ÉVÉNEMENTS + BOOT
    // ============================================================
    $input.addEventListener('keydown', (ev) => {
        if (ev.key === 'Enter') submitLine();
        else if (ev.key === 'ArrowUp') {
            ev.preventDefault();
            if (cmdHistory.length) { histPos = Math.max(0, histPos - 1); $input.value = cmdHistory[histPos] ?? ''; }
        }
        else if (ev.key === 'ArrowDown') {
            ev.preventDefault();
            histPos = Math.min(cmdHistory.length, histPos + 1);
            $input.value = cmdHistory[histPos] ?? '';
        }
        else if (ev.key === 'l' && ev.ctrlKey) { ev.preventDefault(); $out.innerHTML = ''; banner(); }
    });

    document.body.addEventListener('click', () => $input.focus());

    banner();
    setStatus();
    DocSearch.load();

    // Recharge automatiquement le dernier site utilisé (local)
    const lastSite = localStorage.getItem('libreassistant_cli_site');
    if (lastSite) cmdUse(lastSite);

    $input.focus();
    </script>
</body>

</html>