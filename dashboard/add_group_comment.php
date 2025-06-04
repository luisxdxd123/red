<?php
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$group_post_id = $input['group_post_id'] ?? null;
$content = $input['content'] ?? '';

if (!$group_post_id || !is_numeric($group_post_id) || empty(trim($content))) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Verificar que el post existe y obtener el grupo_id
    $query = "SELECT group_id FROM group_posts WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$group_post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        echo json_encode(['success' => false, 'error' => 'Post no encontrado']);
        exit();
    }
    
    // Verificar que el usuario es miembro del grupo
    if (!isGroupMember($_SESSION['user_id'], $post['group_id'])) {
        echo json_encode(['success' => false, 'error' => 'No eres miembro del grupo']);
        exit();
    }
    
    // Agregar el comentario
    $query = "INSERT INTO group_post_comments (group_post_id, user_id, content) VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$group_post_id, $_SESSION['user_id'], trim($content)]);
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error en la base de datos']);
}
?> 