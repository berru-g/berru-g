<?php
header('Content-Type: application/xml; charset=utf-8');

$projects = [
    'smart-pixel-analytics' => '2024-12-30',
    'blockchain-explorer' => '2024-12-30',
    'sql-editor' => '2024-12-30',
    '3d-animator' => '2024-12-30',
    'advent-calendar-2025' => '2024-12-30',
    'data-visualization-tool' => '2024-12-30'
];

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
    
    <!-- Page d'accueil -->
    <url>
        <loc>https://gael-berru.com/</loc>
        <lastmod>2024-12-30</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    
    <!-- Page compétences -->
    <url>
        <loc>https://gael-berru.com/skill/</loc>
        <lastmod>2024-12-30</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    
    <!-- Projets (URLs pour les bots) -->
    <?php foreach ($projects as $slug => $lastmod): ?>
    <url>
        <loc>https://gael-berru.com/projet/<?= htmlspecialchars($slug) ?></loc>
        <lastmod><?= $lastmod ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>
    
    <!-- Smart Pixel -->
    <url>
        <loc>https://gael-berru.com/smart_phpixel/</loc>
        <lastmod>2024-12-30</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    
</urlset>