<?php
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $post_id = $input['post_id'];
    $content = cleanInput($input['content']);
    $user_id = $_SESSION['user_id'];
    
    if (!empty($content) && !empty($post_id)) {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "INSERT INTO comments (user_id, post_id, content) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$user_id, $post_id, $content])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al guardar comentario']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?> 