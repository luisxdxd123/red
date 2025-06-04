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

if (!$group_post_id || !is_numeric($group_post_id)) {
    echo json_encode(['success' => false, 'error' => 'ID de post inválido']);
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
    
    // Verificar si ya le dio like
    $query = "SELECT id FROM group_post_likes WHERE user_id = ? AND group_post_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id'], $group_post_id]);
    $like = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($like) {
        // Quitar like
        $query = "DELETE FROM group_post_likes WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$like['id']]);
        $liked = false;
    } else {
        // Dar like
        $query = "INSERT INTO group_post_likes (user_id, group_post_id) VALUES (?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['user_id'], $group_post_id]);
        $liked = true;
    }
    
    // Contar likes totales
    $count = getGroupPostLikesCount($group_post_id);
    
    echo json_encode([
        'success' => true,
        'liked' => $liked,
        'count' => $count
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error en la base de datos']);
}
?> 