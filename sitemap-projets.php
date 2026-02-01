<?php
header('Content-Type: application/xml');

$projects = [
    'smart-pixel-analytics',
    'blockchain-explorer', 
    'sql-editor',
    '3d-animator',
    'advent-calendar-2025',
    'data-visualization-tool'
    
];

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://gael-berru.com/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    
    <url>
        <loc>https://gael-berru.com/skill/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    
    <?php foreach ($projects as $slug): ?>
    <url>
        <loc>https://gael-berru.com/advent-calendar/<?= $slug ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>
    
    <!-- Ajoute tes autres pages -->
    <url>
        <loc>https://gael-berru.com/smart_phpixel/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
</urlset>