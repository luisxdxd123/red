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
$content = $input['content'] ?? '';

if (!$page_post_id || !is_numeric($page_post_id) || empty(trim($content))) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Verificar que el post existe y obtener el page_id
    $query = "SELECT page_id FROM page_posts WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$page_post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        echo json_encode(['success' => false, 'error' => 'Post no encontrado']);
        exit();
    }
    
    // Verificar que el usuario es seguidor de la página o el creador
    if (!isPageFollower($_SESSION['user_id'], $post['page_id']) && !isPageCreator($_SESSION['user_id'], $post['page_id'])) {
        echo json_encode(['success' => false, 'error' => 'No tienes permiso para comentar']);
        exit();
    }
    
    // Agregar el comentario
    $query = "INSERT INTO page_post_comments (page_post_id, user_id, content) VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$page_post_id, $_SESSION['user_id'], trim($content)]);
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error en la base de datos']);
} 