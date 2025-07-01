<?php
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['post_id']) || !is_numeric($_GET['post_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID de post inválido']);
    exit();
}

$post_id = $_GET['post_id'];

$database = new Database();
$db = $database->getConnection();

try {
    // Obtener los comentarios con información del usuario
    $query = "SELECT c.*, u.username, u.first_name, u.last_name 
              FROM page_post_comments c 
              JOIN users u ON c.user_id = u.id 
              WHERE c.page_post_id = ? 
              ORDER BY c.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$post_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'comments' => $comments
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error al cargar los comentarios']);
} 