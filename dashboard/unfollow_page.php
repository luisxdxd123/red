<?php
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $page_id = $input['page_id'];
    $user_id = $_SESSION['user_id'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar que la página existe
    $query = "SELECT * FROM pages WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$page_id]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$page) {
        echo json_encode(['success' => false, 'error' => 'Página no encontrada']);
        exit();
    }
    
    // Verificar que el usuario sigue la página
    if (!isPageFollower($user_id, $page_id)) {
        echo json_encode(['success' => false, 'error' => 'No sigues esta página']);
        exit();
    }
    
    // Dejar de seguir la página
    $query = "DELETE FROM page_followers WHERE page_id = ? AND user_id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$page_id, $user_id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al dejar de seguir la página']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
} 