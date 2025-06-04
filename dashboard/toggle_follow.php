<?php
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $following_id = $input['user_id'];
    $follower_id = $_SESSION['user_id'];
    
    if ($following_id == $follower_id) {
        echo json_encode(['success' => false, 'error' => 'No puedes seguirte a ti mismo']);
        exit();
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar si ya sigue al usuario
    $query = "SELECT id FROM follows WHERE follower_id = ? AND following_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$follower_id, $following_id]);
    
    if ($stmt->rowCount() > 0) {
        // Dejar de seguir
        $query = "DELETE FROM follows WHERE follower_id = ? AND following_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$follower_id, $following_id]);
        $following = false;
    } else {
        // Seguir
        $query = "INSERT INTO follows (follower_id, following_id) VALUES (?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$follower_id, $following_id]);
        $following = true;
    }
    
    echo json_encode([
        'success' => true,
        'following' => $following
    ]);
} else {
    echo json_encode(['success' => false]);
}
?> 