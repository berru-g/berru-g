<?php
// ============================================================================
// BLOCS DYNAMIQUES pour index.php — combo RGPD live + GitHub live
// ----------------------------------------------------------------------------
// BLOC A (PHP)  : GitHub live — API publique, 0 token, 0 dépendance,
//                 cache fichier 6 h (respecte le rate limit 60 req/h/IP).
// BLOC B (HTML/CSS/JS) : démo RGPD "terminal" — DA identique au chatbot
//                 berru·faq. 100% côté client : zéro requête, zéro stockage.
//
// Charte respectée : aucune donnée fabriquée, tout est mesuré pour de vrai.
// Si l'API GitHub échoue (rate limit, réseau), le bloc A s'affiche tout
// simplement pas — jamais de chiffre bidonné.
// ============================================================================

// ---------------------------------------------------------------------------
// BLOC A — GITHUB LIVE (à coller où tu veux dans index.php)
// ---------------------------------------------------------------------------
// Prérequis : un dossier ./cache/ accessible en écriture (créé automatiquement).

/**
 * Stats GitHub publiques pour un utilisateur, avec cache fichier.
 * 2 requêtes API max par refresh : /users/:u + /search/repositories (1 seul
 * appel pour TOUS les repos, leurs étoiles et leur date de dernier push).
 * TTL 6h → ~8 requêtes/jour, très loin du rate limit (60/h/IP sans token).
 */
function gh_stats(string $user, int $ttl = 21600): ?array
{
    $dir  = __DIR__ . '/cache';
    $file = $dir . '/github_stats.json';

    // Cache frais → on ne touche pas au réseau
    if (is_file($file) && (time() - filemtime($file)) < $ttl) {
        $d = json_decode((string)file_get_contents($file), true);
        if (is_array($d) && !empty($d['repos'])) return $d;
    }

    $ctx = stream_context_create(['http' => [
        'header'  => "User-Agent: gael-berru.com\r\nAccept: application/vnd.github+json\r\n",
        'timeout' => 5,
    ]]);

    $uRaw = @file_get_contents("https://api.github.com/users/" . urlencode($user), false, $ctx);
    $rRaw = @file_get_contents("https://api.github.com/search/repositories?q=user%3A" . urlencode($user) . "&sort=stars&per_page=100", false, $ctx);

    // Échec réseau : on retombe sur le cache périmé (mieux que rien), sinon rien
    if ($uRaw === false || $rRaw === false) {
        if (is_file($file)) {
            $d = json_decode((string)file_get_contents($file), true);
            if (is_array($d) && !empty($d['repos'])) return $d;
        }
        return null;
    }

    $u = json_decode($uRaw, true);
    $r = json_decode($rRaw, true);
    if (!is_array($u) || !is_array($r) || empty($r['items'])) return null;

    $stars = 0; $lastPush = 0; $langs = [];
    foreach ($r['items'] as $repo) {           // NB : compte exact si ≤ 100 repos
        $stars += (int)($repo['stargazers_count'] ?? 0);
        $lastPush = max($lastPush, strtotime((string)($repo['pushed_at'] ?? '')) ?: 0);
        $l = $repo['language'] ?? null;
        if ($l) $langs[$l] = ($langs[$l] ?? 0) + 1;
    }
    arsort($langs);

    $stats = [
        'repos'     => (int)($r['total_count'] ?: count($r['items'])),
        'stars'     => $stars,
        'last_push' => $lastPush,
        'since'     => strtotime((string)($u['created_at'] ?? '2020-01-01')) ?: strtotime('2020-01-01'),
        'langs'     => array_slice(array_keys($langs), 0, 3),
        'top'       => array_map(function ($x) {
            return [
                'name'  => (string)$x['name'],
                'stars' => (int)($x['stargazers_count'] ?? 0),
                'desc'  => (string)($x['description'] ?? ''),
                'url'   => (string)($x['html_url'] ?? ''),
            ];
        }, array_slice($r['items'], 0, 3)),
        'fetched_at'=> time(),
    ];

    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    @file_put_contents($file, json_encode($stats, JSON_UNESCAPED_UNICODE));
    return $stats;
}

/** "il y a 3 min" / "il y a 4 h" / "il y a 2 j" */
function fr_ago(int $ts): string
{
    $d = max(0, time() - $ts);
    if ($d < 3600)    return 'il y a ' . max(1, intdiv($d, 60)) . ' min';
    if ($d < 86400)   return 'il y a ' . intdiv($d, 3600) . ' h';
    if ($d < 2592000) return 'il y a ' . intdiv($d, 86400) . ' j';
    return 'le ' . date('d/m/Y', $ts);
}

$gh = gh_stats('berru-g');
?>

<?php if ($gh): ?>
<!-- ===================== BLOC A — AFFICHAGE ===================== -->
<!-- Design volontairement neutre : ajuste les variables pour coller à ta DA -->
<style>
    .gh-live {
        --gh-fg: #e8eaf0;
        --gh-dim: #8a93a5;
        --gh-accent: #4af626;
        --gh-card-bg: rgba(255, 255, 255, 0.04);
        --gh-border: rgba(255, 255, 255, 0.12);
    }
    .gh-live { color: var(--gh-fg); font-family: system-ui, sans-serif; }
    .gh-live-cards {
        display: flex; flex-wrap: wrap; gap: 14px; margin: 16px 0;
    }
    .gh-live-card {
        flex: 1 1 160px;
        background: var(--gh-card-bg);
        border: 1px solid var(--gh-border);
        border-radius: 10px;
        padding: 16px 18px;
    }
    .gh-live-card .big {
        font-size: 1.9rem; font-weight: 700; line-height: 1.1; color: var(--gh-accent);
    }
    .gh-live-card .label { margin-top: 4px; font-size: 0.85rem; color: var(--gh-dim); }
    .gh-live-top { margin: 0; padding: 0; list-style: none; }
    .gh-live-top li { padding: 7px 0; border-top: 1px solid var(--gh-border); }
    .gh-live-top li:first-child { border-top: none; }
    .gh-live-top a { color: var(--gh-fg); text-decoration: none; font-weight: 600; }
    .gh-live-top a:hover { color: var(--gh-accent); }
    .gh-live-top .stars { color: var(--gh-dim); font-size: 0.85rem; margin-left: 8px; }
    .gh-live-top p { margin: 2px 0 0 0; font-size: 0.85rem; color: var(--gh-dim); }
    .gh-live-note { margin-top: 12px; font-size: 0.75rem; color: var(--gh-dim); }
</style>

<div class="gh-live">
    <div class="gh-live-cards">
        <div class="gh-live-card">
            <div class="big"><?= (int)$gh['repos'] ?></div>
            <div class="label">dépôts publics open source</div>
        </div>
        <div class="gh-live-card">
            <div class="big">★ <?= (int)$gh['stars'] ?></div>
            <div class="label">étoiles cumulées</div>
        </div>
        <div class="gh-live-card">
            <div class="big"><?= htmlspecialchars(fr_ago((int)$gh['last_push'])) ?></div>
            <div class="label">dernier push</div>
        </div>
        <div class="gh-live-card">
            <div class="big"><?= (int)floor((time() - (int)$gh['since']) / 31557600) ?> ans</div>
            <div class="label">que je code en public — <?= htmlspecialchars(implode(' · ', $gh['langs'])) ?></div>
        </div>
    </div>

    <?php if (!empty($gh['top'])): ?>
    <ul class="gh-live-top">
        <?php foreach ($gh['top'] as $repo): ?>
        <li>
            <a href="<?= htmlspecialchars($repo['url']) ?>" target="_blank" rel="noopener">
                <?= htmlspecialchars($repo['name']) ?>
            </a>
            <span class="stars">★ <?= (int)$repo['stars'] ?></span>
            <?php if ($repo['desc']): ?><p><?= htmlspecialchars(mb_strimwidth($repo['desc'], 0, 110, '…')) ?></p><?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <p class="gh-live-note">
        Données publiques GitHub, rafraîchies toutes les 6 h côté serveur. Aucun token, aucune dépendance.
    </p>
</div>
<?php endif; ?>
<!-- ================== /BLOC A ================== -->


<!-- ===================== BLOC B — DÉMO RGPD TERMINAL ===================== -->
<!-- 100% côté client : zéro requête, zéro stockage, zéro cookie.
     Effet "il sait ce qu'il fait" + argumentaire LibreAnalytics sans en faire trop. -->
<style>
    .rgpd-term {
        --term-bg: #0b0d12;
        --term-fg: #c9f2d0;
        --term-green: #4af626;
        --term-accent: #ab9ff2;
        --term-pink: #ff86e9;
        --term-dim: #5c6a72;
        border: 1px solid var(--term-green);
        background: var(--term-bg);
        color: var(--term-fg);
        font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
        max-width: 720px;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.5);
    }
    .rgpd-term-header {
        background: #141821;
        border-bottom: 1px solid var(--term-green);
        padding: 4px 10px;
        display: flex; justify-content: space-between;
        font-size: 0.75rem; color: var(--term-dim); user-select: none;
    }
    .rgpd-term-body {
        padding: 12px 14px; min-height: 260px;
        font-size: 0.8rem; line-height: 1.55;
        white-space: pre-wrap; word-break: break-word;
    }
    .rgpd-cmd    { color: var(--term-accent); }
    .rgpd-res    { color: var(--term-fg); }
    .rgpd-final  { color: var(--term-pink); font-weight: bold; }
    .rgpd-dim    { color: var(--term-dim); }
    .rgpd-cursor {
        display: inline-block; width: 7px; background: var(--term-green);
        animation: rgpd-blink 1s steps(2) infinite;
    }
    @keyframes rgpd-blink { 50% { opacity: 0; } }
</style>

<section class="rgpd-term" id="rgpdTerm">
    <div class="rgpd-term-header">
        <span>live-privacy — ttyGB1 — 0 donnée stockée</span>
        <span>100% local</span>
    </div>
    <div class="rgpd-term-body" id="rgpdOut">
        <noscript><span class="rgpd-dim">cette démo a besoin de JavaScript — tout se calcule dans ton navigateur, rien n'est envoyé.</span></noscript>
    </div>
</section>

<script>
(function () {
    'use strict';

    const out = document.getElementById('rgpdOut');
    const LIBREANALYTICS_URL = 'https://gael-berru.com/LibreAnalytics';

    // --- Détection navigateur / OS (parsing sobre de l'UA, zéro dépendance) ---
    function detect() {
        const ua = navigator.userAgent;
        const b = /Edg\//.test(ua) ? 'Edge'
            : /OPR\//.test(ua) ? 'Opera'
            : /Firefox\//.test(ua) ? 'Firefox'
            : /Chrome\//.test(ua) ? 'Chrome'
            : /Safari\//.test(ua) ? 'Safari' : 'navigateur inconnu';
        const os = /Windows/.test(ua) ? 'Windows'
            : /Android/.test(ua) ? 'Android'
            : /iPhone|iPad/.test(ua) ? 'iOS'
            : /Mac OS X/.test(ua) ? 'macOS'
            : /Linux/.test(ua) ? 'Linux' : 'OS inconnu';
        const type = /Mobi|Android|iPhone/.test(ua) ? 'mobile' : 'desktop';
        return { b: b, os: os, type: type };
    }

    function buildScript() {
        const d = detect();
        const tz = Intl.DateTimeFormat().resolvedOptions().timeZone || 'inconnu';
        const now = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        const ref = document.referrer
            ? 'arrivé depuis ' + (function () { try { return new URL(document.referrer).hostname; } catch (e) { return 'un lien externe'; } })()
            : 'accès direct — aucun referrer transmis';
        const conn = (navigator.connection && navigator.connection.effectiveType)
            ? ' · réseau ~' + navigator.connection.effectiveType : '';
        const cores = navigator.hardwareConcurrency ? navigator.hardwareConcurrency + ' coeurs cpu' : 'cpu inconnu';

        return [
            ['whoami', 'visiteur anonyme — et il le restera'],
            ['navigateur', d.b.toLowerCase() + ' · ' + d.os.toLowerCase() + ' · ' + d.type],
            ['ecran', screen.width + '×' + screen.height + ' (dpr ' + (window.devicePixelRatio || 1) + ') · ' + cores + conn],
            ['locale', (navigator.language || '—') + ' · fuseau ' + tz + ' · heure locale ' + now],
            ['provenance', ref],
            ['data --stored', '0 octet. zéro requête serveur — tout a été calculé dans ton navigateur, rien n\u2019a été envoyé.']
        ];
    }

    // --- Machine à écrire : démarre quand le terminal entre dans le viewport ---
    let started = false;

    function line(text, cls) {
        const div = document.createElement('div');
        div.className = cls;
        div.textContent = text;
        out.appendChild(div);
    }

    function run() {
        if (started) return;
        started = true;
        const script = buildScript();
        let i = 0;

        function step() {
            if (i >= script.length) {
                line('ce genre de données, LibreAnalytics sait les mesurer sobrement — sans cookie, serveur en France :', 'rgpd-dim');
                line('→ ' + LIBREANALYTICS_URL, 'rgpd-res');
                return;
            }
            const cmd = script[i][0];
            const res = script[i][1];
            const isFinal = (i === script.length - 1);
            line('$ ' + cmd, 'rgpd-cmd');
            setTimeout(function () {
                line('  ' + res, isFinal ? 'rgpd-final' : 'rgpd-res');
                i++;
                setTimeout(step, isFinal ? 0 : 420);
            }, 260);
        }
        step();
    }

    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver(function (entries) {
            if (entries.some(function (e) { return e.isIntersecting; })) { io.disconnect(); run(); }
        }, { threshold: 0.4 });
        io.observe(document.getElementById('rgpdTerm'));
    } else {
        run(); // pas d'IO : on lance direct
    }
})();
</script>
<!-- ================== /BLOC B ================== -->