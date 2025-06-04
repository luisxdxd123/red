<?php
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $post_id = $input['post_id'];
    $user_id = $_SESSION['user_id'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar si el usuario ya le dio like
    $query = "SELECT id FROM post_likes WHERE user_id = ? AND post_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, $post_id]);
    
    if ($stmt->rowCount() > 0) {
        // Quitar like
        $query = "DELETE FROM post_likes WHERE user_id = ? AND post_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id, $post_id]);
        $liked = false;
    } else {
        // Dar like
        $query = "INSERT INTO post_likes (user_id, post_id) VALUES (?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id, $post_id]);
        $liked = true;
    }
    
    // Obtener el nuevo conteo de likes
    $count = getPostLikesCount($post_id);
    
    echo json_encode([
        'success' => true,
        'liked' => $liked,
        'count' => $count
    ]);
} else {
    echo json_encode(['success' => false]);
}
?> 