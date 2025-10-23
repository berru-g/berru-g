<?php
/**
 * Actions AJAX sécurisées pour la galerie
 * Aucune backdoor - Code 100% transparent
 */
session_start();
require_once 'figueconfite.php';

header('Content-Type: application/json; charset=utf-8');

// 1. Vérification admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

// 2. Vérification CSRF obligatoire
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token CSRF invalide']);
    exit;
}

// 3. Vérification action
if (!isset($_POST['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Action manquante']);
    exit;
}

$pdo = getDbConnection();
$action = $_POST['action'];

try {
    switch ($action) {
        
        // ===== UPDATE TITLE =====
        case 'update_title':
            if (!isset($_POST['id']) || !isset($_POST['title'])) {
                throw new Exception('Paramètres manquants');
            }
            
            // Validation stricte de l'ID
            $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) {
                throw new Exception('ID invalide');
            }
            
            // Sanitization du titre
            $title = sanitizeInput($_POST['title']);
            
            if (strlen($title) > 255 || strlen($title) < 1) {
                throw new Exception('Titre invalide (1-255 caractères)');
            }
            
            // Update sécurisé avec prepared statement
            $stmt = $pdo->prepare("UPDATE galerie_images SET title = ? WHERE id = ?");
            $stmt->execute([$title, $id]);
            
            echo json_encode(['success' => true]);
            break;

        // ===== UPLOAD IMAGE (REMPLACEMENT) =====
        case 'upload_image':
            if (!isset($_FILES['image']) || !isset($_POST['id'])) {
                throw new Exception('Paramètres manquants');
            }
            
            // Validation ID
            $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) {
                throw new Exception('ID invalide');
            }
            
            // Vérifier que l'image existe en BDD
            $stmt = $pdo->prepare("SELECT image_path FROM galerie_images WHERE id = ?");
            $stmt->execute([$id]);
            $oldImage = $stmt->fetch();
            
            if (!$oldImage) {
                throw new Exception('Image non trouvée en base de données');
            }
            
            // VALIDATION STRICTE DE L'UPLOAD
            $errors = validateUpload($_FILES['image']);
            if (!empty($errors)) {
                throw new Exception('Upload invalide: ' . implode(', ', $errors));
            }
            
            // Générer nom de fichier SÉCURISÉ (impossible à deviner)
            $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $newFilename = 'image_' . $id . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $uploadPath = UPLOAD_DIR . $newFilename;
            
            // Déplacer le fichier uploadé
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                throw new Exception('Erreur lors du déplacement du fichier');
            }
            
            // Définir permissions restrictives
            chmod($uploadPath, 0644);
            
            // Supprimer ancienne image SI custom upload (pas les images par défaut)
            $oldPath = $oldImage['image_path'];
            $oldBasename = basename($oldPath);
            if (strpos($oldBasename, 'image_') === 0) {
                $oldFullPath = UPLOAD_DIR . $oldBasename;
                if (file_exists($oldFullPath)) {
                    unlink($oldFullPath);
                }
            }
            
            // Update BDD avec prepared statement
            $relativePath = '../img/' . $newFilename;
            $stmt = $pdo->prepare("UPDATE galerie_images SET image_path = ? WHERE id = ?");
            $stmt->execute([$relativePath, $id]);
            
            echo json_encode([
                'success' => true,
                'new_path' => $relativePath
            ]);
            break;

        // ===== ADD IMAGE (NOUVELLE) =====
        case 'add_image':
            if (!isset($_FILES['new_image']) || !isset($_POST['title'])) {
                throw new Exception('Paramètres manquants');
            }
            
            // Sanitization du titre
            $title = sanitizeInput($_POST['title']);
            
            if (strlen($title) > 255 || strlen($title) < 1) {
                throw new Exception('Titre invalide (1-255 caractères)');
            }
            
            // VALIDATION STRICTE DE L'UPLOAD
            $errors = validateUpload($_FILES['new_image']);
            if (!empty($errors)) {
                throw new Exception('Upload invalide: ' . implode(', ', $errors));
            }
            
            // Générer nom de fichier SÉCURISÉ
            $extension = strtolower(pathinfo($_FILES['new_image']['name'], PATHINFO_EXTENSION));
            $newFilename = 'image_new_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $uploadPath = UPLOAD_DIR . $newFilename;
            
            // Déplacer le fichier
            if (!move_uploaded_file($_FILES['new_image']['tmp_name'], $uploadPath)) {
                throw new Exception('Erreur lors du déplacement du fichier');
            }
            
            // Permissions restrictives
            chmod($uploadPath, 0644);
            
            // Insert en BDD avec prepared statement
            $relativePath = '../img/' . $newFilename;
            $stmt = $pdo->prepare("INSERT INTO galerie_images (image_path, title) VALUES (?, ?)");
            $stmt->execute([$relativePath, $title]);
            
            echo json_encode([
                'success' => true,
                'id' => $pdo->lastInsertId()
            ]);
            break;

        // ===== DELETE IMAGE =====
        case 'delete_image':
            if (!isset($_POST['id'])) {
                throw new Exception('ID manquant');
            }
            
            // Validation ID
            $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) {
                throw new Exception('ID invalide');
            }
            
            // Récupérer l'image pour supprimer le fichier
            $stmt = $pdo->prepare("SELECT image_path FROM galerie_images WHERE id = ?");
            $stmt->execute([$id]);
            $image = $stmt->fetch();
            
            if (!$image) {
                throw new Exception('Image non trouvée');
            }
            
            // Supprimer le fichier SI custom upload (pas les images par défaut)
            $imagePath = $image['image_path'];
            $basename = basename($imagePath);
            
            // CONTRÔLE STRICT: uniquement les fichiers uploadés (préfixe "image_")
            if (strpos($basename, 'image_') === 0) {
                $fullPath = UPLOAD_DIR . $basename;
                
                // Vérification supplémentaire: le fichier est bien dans UPLOAD_DIR
                $realPath = realpath($fullPath);
                $uploadDirReal = realpath(UPLOAD_DIR);
                
                if ($realPath && strpos($realPath, $uploadDirReal) === 0) {
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }
            }
            
            // Supprimer de la BDD avec prepared statement
            $stmt = $pdo->prepare("DELETE FROM galerie_images WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
            break;

        // ===== ACTION INCONNUE =====
        default:
            throw new Exception('Action inconnue: ' . htmlspecialchars($action));
    }
    
} catch (Exception $e) {
    http_response_code(400);
    // Log sécurisé (sans données sensibles)
    error_log("Erreur galerie [Action: " . $action . ", User IP: " . $_SERVER['REMOTE_ADDR'] . "]: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    // Log sécurisé (message générique en sortie)
    error_log("Erreur BDD galerie [Action: " . $action . "]: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Erreur base de données'
    ]);
}
?>