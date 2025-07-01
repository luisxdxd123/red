<?php
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$page_post_id = $input['page_post_id'] ?? null;

if (!$page_post_id || !is_numeric($page_post_id)) {
    echo json_encode(['success' => false, 'error' => 'ID de post inválido']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    // Obtener información del post y la página
    $query = "SELECT p.*, pp.media_url, pp.media_type FROM page_posts pp
              JOIN pages p ON pp.page_id = p.id 
              WHERE pp.id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$page_post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        echo json_encode(['success' => false, 'error' => 'Post no encontrado']);
        exit();
    }
    
    // Verificar que el usuario es el creador de la página
    if (!isPageCreator($_SESSION['user_id'], $post['id'])) {
        echo json_encode(['success' => false, 'error' => 'No tienes permiso para eliminar este post']);
        exit();
    }
    
    // Eliminar el archivo multimedia si existe
    if ($post['media_url']) {
        $file_path = 'uploads/pages/posts/' . $post['media_url'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    // Eliminar el post (los likes y comentarios se eliminarán automáticamente por las restricciones FK)
    $query = "DELETE FROM page_posts WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$page_post_id]);
    
    $db->commit();
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => 'Error al eliminar el post']);
} 