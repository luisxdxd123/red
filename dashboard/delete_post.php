<?php
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$post_id = $input['post_id'] ?? null;

if (!$post_id || !is_numeric($post_id)) {
    echo json_encode(['success' => false, 'error' => 'ID de post inválido']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Verificar que el post existe y pertenece al usuario
    $query = "SELECT user_id, has_media FROM posts WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        echo json_encode(['success' => false, 'error' => 'Post no encontrado']);
        exit();
    }
    
    if ($post['user_id'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'error' => 'No tienes permisos para eliminar este post']);
        exit();
    }
    
    // Iniciar transacción
    $db->beginTransaction();
    
    // Si el post tiene medios, eliminar los archivos físicos y registros de la BD
    if ($post['has_media']) {
        // Obtener todos los medios del post
        $query = "SELECT id, file_path FROM post_media WHERE post_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$post_id]);
        $media_files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Eliminar archivos físicos
        foreach ($media_files as $media) {
            if (file_exists($media['file_path'])) {
                unlink($media['file_path']);
            }
        }
        
        // Eliminar registros de medios de la BD
        $query = "DELETE FROM post_media WHERE post_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$post_id]);
    }
    
    // Eliminar comentarios del post
    $query = "DELETE FROM comments WHERE post_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$post_id]);
    
    // Eliminar likes del post
    $query = "DELETE FROM post_likes WHERE post_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$post_id]);
    
    // Finalmente, eliminar el post
    $query = "DELETE FROM posts WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$post_id]);
    
    // Confirmar transacción
    $db->commit();
    
    echo json_encode(['success' => true, 'message' => 'Post eliminado exitosamente']);
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => 'Error al eliminar el post: ' . $e->getMessage()]);
}
?> 