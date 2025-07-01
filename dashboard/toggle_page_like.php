<?php
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $page_post_id = $input['page_post_id'];
    $user_id = $_SESSION['user_id'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $db->beginTransaction();
        
        // Verificar si ya dio like
        $query = "SELECT id FROM page_post_likes WHERE page_post_id = ? AND user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$page_post_id, $user_id]);
        $like = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($like) {
            // Quitar like
            $query = "DELETE FROM page_post_likes WHERE page_post_id = ? AND user_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$page_post_id, $user_id]);
            $has_liked = false;
        } else {
            // Dar like
            $query = "INSERT INTO page_post_likes (page_post_id, user_id) VALUES (?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$page_post_id, $user_id]);
            $has_liked = true;
        }
        
        // Obtener número total de likes
        $likes_count = getPagePostLikesCount($page_post_id);
        
        $db->commit();
        echo json_encode([
            'success' => true,
            'has_liked' => $has_liked,
            'likes_count' => $likes_count
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'error' => 'Error al procesar el like']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
} 