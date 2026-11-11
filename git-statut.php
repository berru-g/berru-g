      <?php
      // ===== GITHUB STATS =====
      function gh_stats(string $user, int $ttl = 21600): ?array
      {
        $dir = __DIR__ . '/cache';
        $file = $dir . '/github_stats.json';

        // Cache frais → on ne touche pas au réseau
        if (is_file($file) && (time() - filemtime($file)) < $ttl) {
          $d = json_decode((string) file_get_contents($file), true);
          if (is_array($d) && !empty($d['repos']))
            return $d;
        }

        $ctx = stream_context_create([
          'http' => [
            'header' => "User-Agent: gael-berru.com\r\nAccept: application/vnd.github+json\r\n",
            'timeout' => 5,
          ]
        ]);

        $uRaw = @file_get_contents("https://api.github.com/users/" . urlencode($user), false, $ctx);
        $rRaw = @file_get_contents("https://api.github.com/search/repositories?q=user%3A" . urlencode($user) . "&sort=stars&per_page=100", false, $ctx);

        // Échec réseau : on retombe sur le cache périmé (mieux que rien), sinon rien
        if ($uRaw === false || $rRaw === false) {
          if (is_file($file)) {
            $d = json_decode((string) file_get_contents($file), true);
            if (is_array($d) && !empty($d['repos']))
              return $d;
          }
          return null;
        }

        $u = json_decode($uRaw, true);
        $r = json_decode($rRaw, true);
        if (!is_array($u) || !is_array($r) || empty($r['items']))
          return null;

        $stars = 0;
        $lastPush = 0;
        $langs = [];
        foreach ($r['items'] as $repo) {           // NB : compte exact si ≤ 100 repos
          $stars += (int) ($repo['stargazers_count'] ?? 0);
          $lastPush = max($lastPush, strtotime((string) ($repo['pushed_at'] ?? '')) ?: 0);
          $l = $repo['language'] ?? null;
          if ($l)
            $langs[$l] = ($langs[$l] ?? 0) + 1;
        }
        arsort($langs);

        $stats = [
          'repos' => (int) ($r['total_count'] ?: count($r['items'])),
          'stars' => $stars,
          'last_push' => $lastPush,
          'since' => strtotime((string) ($u['created_at'] ?? '2020-01-01')) ?: strtotime('2020-01-01'),
          'langs' => array_slice(array_keys($langs), 0, 3),
          'top' => array_map(function ($x) {
            return [
              'name' => (string) $x['name'],
              'stars' => (int) ($x['stargazers_count'] ?? 0),
              'desc' => (string) ($x['description'] ?? ''),
              'url' => (string) ($x['html_url'] ?? ''),
            ];
          }, array_slice($r['items'], 0, 3)),
          'fetched_at' => time(),
        ];

        if (!is_dir($dir))
          @mkdir($dir, 0775, true);
        @file_put_contents($file, json_encode($stats, JSON_UNESCAPED_UNICODE));
        return $stats;
      }

      /** "il y a 3 min" / "il y a 4 h" / "il y a 2 j" */
      function fr_ago(int $ts): string
      {
        $d = max(0, time() - $ts);
        if ($d < 3600)
          return 'il y a ' . max(1, intdiv($d, 60)) . ' min';
        if ($d < 86400)
          return 'il y a ' . intdiv($d, 3600) . ' h';
        if ($d < 2592000)
          return 'il y a ' . intdiv($d, 86400) . ' j';
        return 'le ' . date('d/m/Y', $ts);
      }

      $gh = gh_stats('berru-g');
      ?>

      <?php if ($gh): ?>
        <footer id="live-footer" data-aos="fade-up"
          style="margin-top:3rem; padding:1.5rem; background:var(--sidebar-bg); border:1px solid var(--border-color); border-radius:12px; box-shadow:var(--shadow);">

          <div
            style="display:flex; align-items:baseline; justify-content:space-between; gap:0.75rem; flex-wrap:wrap; margin-bottom:1.25rem;">
            <h3 style="margin:0; font-size:0.95rem; font-weight:600; color:var(--text-color);">
              <i class="fab fa-github" style="color:var(--primary-color); margin-right:0.4rem;"></i>Live depuis GitHub
            </h3>
            <span
              style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-secondary);">données
              publiques · cache 6 h</span>
          </div>

          <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:0.75rem;">
            <div
              style="padding:0.875rem 1rem; background:var(--search-bg); ">
              <div style="font-size:1.6rem; font-weight:700; line-height:1.15; color:var(--primary-color);">
                <?= (int) $gh['repos'] ?></div>
              <div style="margin-top:0.2rem; font-size:0.75rem; color:var(--text-secondary);">dépôts open source</div>
            </div>
            <div
              style="padding:0.875rem 1rem; background:var(--search-bg); ">
              <div style="font-size:1.6rem; font-weight:700; line-height:1.15; color:var(--primary-color);">★
                <?= (int) $gh['stars'] ?></div>
              <div style="margin-top:0.2rem; font-size:0.75rem; color:var(--text-secondary);">étoiles cumulées</div>
            </div>
            <div
              style="padding:0.875rem 1rem; background:var(--search-bg); ">
              <div style="font-size:1.25rem; font-weight:700; line-height:1.3; color:var(--primary-color);">
                <?= htmlspecialchars(fr_ago((int) $gh['last_push'])) ?></div>
              <div style="margin-top:0.2rem; font-size:0.75rem; color:var(--text-secondary);">dernier push</div>
            </div>
            <div
              style="padding:0.875rem 1rem; background:var(--search-bg); ">
              <div style="font-size:1.6rem; font-weight:700; line-height:1.15; color:var(--primary-color);">
                <?= (int) floor((time() - (int) $gh['since']) / 31557600) ?> ans</div>
              <div style="margin-top:0.2rem; font-size:0.75rem; color:var(--text-secondary);">de code public —
                <?= htmlspecialchars(implode(' · ', $gh['langs'])) ?></div>
            </div>
          </div>

          <div style="margin-top:0.75rem; display:grid; gap:0.5rem;">
            <?php foreach ($gh['top'] as $repo): ?>
              <a href="<?= htmlspecialchars($repo['url']) ?>" target="_blank" rel="noopener"
                style="display:flex; align-items:center; gap:0.75rem; padding:0.625rem 0.875rem; background:var(--search-bg); text-decoration:none; color:var(--text-color);">
                <i class="fab fa-github" style="color:var(--primary-color); flex-shrink:0;"></i>
                <span
                  style="font-weight:600; font-size:0.875rem; flex-shrink:0;"><?= htmlspecialchars($repo['name']) ?></span>
                <span
                  style="flex:1; min-width:0; font-size:0.75rem; color:var(--text-secondary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars(mb_strimwidth($repo['desc'], 0, 110, '…')) ?></span>
                <span
                  style="flex-shrink:0; background:var(--primary-light); color:var(--primary-color); font-size:0.72rem; font-weight:600; padding:2px 8px; border-radius:12px;">★
                  <?= (int) $repo['stars'] ?></span>
              </a>
            <?php endforeach; ?>
          </div>

        </footer>
      <?php endif; ?>