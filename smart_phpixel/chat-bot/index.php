<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

header('Content-Type: application/json');

// Désactive les erreurs pour éviter de polluer le JSON
error_reporting(0);

// Liens vers vos fichiers GitHub (à adapter)
$sources = [
    'doc' => [
        'url' => 'https://raw.githubusercontent.com/berru-g/smart_pixel_v2/main/README.md',
        'type' => 'markdown'
    ],
    'install' => [
        'url' => 'https://raw.githubusercontent.com/berru-g/smart_pixel_v2/main/doc/index.html',
        'type' => 'html'
    ],
    'api' => [
        'url' => 'https://raw.githubusercontent.com/berru-g/smart_pixel_v2/main/public/README.md',
        'type' => 'markdown'
    ]
];


// Récupère un fichier depuis GitHub
function fetchContent($url) {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: PHP',
                'Accept: text/plain'  // Pour éviter les redirections ou blocages
            ],
            'ignore_errors' => true  // Pour récupérer les codes HTTP d'erreur
        ]
    ]);

    $content = @file_get_contents($url, false, $context);
    if ($content === false) {
        $error = error_get_last();
        throw new Exception("Erreur lors de la récupération de $url: " . ($error['message'] ?? 'Inconnu'));
    }

    // Vérifie si le contenu est vide ou une page d'erreur HTML (ex: 404 GitHub)
    if (empty($content) || strpos($content, '<!DOCTYPE html>') !== false) {
        throw new Exception("Le contenu de $url est vide ou invalide (404 ou blocage).");
    }

    return $content;
}



// Parse le contenu selon son type
function parseContent($content, $type) {
    if ($type === 'markdown') {
        return parseMarkdown($content);
    } else {
        return parseHTML($content);
    }
}

// Parse le Markdown (titres + contenu)
function parseMarkdown($content) {
    $sections = [];
    $lines = explode("\n", $content);
    $currentSection = null;

    foreach ($lines as $line) {
        // Détection des titres (ex: # Titre, ## Sous-titre)
        if (preg_match('/^(#+)\s+(.*?)$/', $line, $matches)) {
            if ($currentSection !== null) {
                $sections[] = $currentSection;
            }
            $currentSection = [
                'title' => trim($matches[2]),
                'level' => strlen($matches[1]),
                'content' => ''
            ];
        }
        // Ajoute le contenu à la section actuelle
        elseif ($currentSection !== null) {
            $currentSection['content'] .= $line . "\n";
        }
    }
    if ($currentSection !== null) {
        $sections[] = $currentSection;
    }
    return $sections;
}

// Parse le HTML (titres + paragraphs)
function parseHTML($content) {
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);  // Désactive les warnings HTML
    @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
    $sections = [];

    foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
        $headings = $dom->getElementsByTagName($tag);
        foreach ($headings as $heading) {
            $level = (int) substr($heading->nodeName, 1);
            $title = trim($heading->nodeValue);
            $content = '';
            $nextNode = $heading->nextSibling;
            while ($nextNode !== null && !preg_match('/^h[1-6]$/', $nextNode->nodeName)) {
                if ($nextNode->nodeType === XML_TEXT_NODE && trim($nextNode->nodeValue) !== '') {
                    $content .= trim($nextNode->nodeValue) . "\n";
                } elseif ($nextNode->nodeName === 'p') {
                    $content .= trim($nextNode->nodeValue) . "\n";
                }
                $nextNode = $nextNode->nextSibling;
            }
            $sections[] = [
                'title' => $title,
                'level' => $level,
                'content' => trim($content)
            ];
        }
    }
    return $sections;
}



// Cherche une réponse dans les sections
function searchInSections($query, $sections) {
    $query = strtolower($query);
    $results = [];

    foreach ($sections as $section) {
        if (strpos(strtolower($section['title']), $query) !== false ||
            strpos(strtolower($section['content']), $query) !== false) {
            $results[] = $section;
        }
    }
    return $results;
}

// Traite la requête
if (isset($_GET['query'])) {
    $query = strtolower($_GET['query']);
    $response = [];

    foreach ($sources as $name => $source) {
        $content = fetchContent($source['url']);
        if ($content) {
            $sections = parseContent($content, $source['type']);
            $results = searchInSections($query, $sections);
            if (!empty($results)) {
                $response[$name] = [
                    'title' => ucfirst($name),
                    'results' => array_map(function($section) {
                        return [
                            'title' => $section['title'],
                            'content' => !empty($section['content']) ? substr(trim($section['content']), 0, 200) . '...' : 'Aucun contenu supplémentaire.'
                        ];
                    }, $results)
                ];
            }
        }
    }

    if (empty($response)) {
        $response['error'] = "Aucune réponse trouvée. Essayez avec des mots-clés comme 'installation', 'API', etc.";
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['error' => 'Aucune requête spécifiée.']);
}
?>
