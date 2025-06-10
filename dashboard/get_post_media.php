<?php
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['post_id']) || !is_numeric($_GET['post_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID de post inválido']);
    exit();
}

$post_id = (int)$_GET['post_id'];

// Verificar que el post existe
$database = new Database();
$db = $database->getConnection();

$query = "SELECT id FROM posts WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$post_id]);

if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'error' => 'Post no encontrado']);
    exit();
}

// Obtener los medios del post
$media = getPostMedia($post_id);

echo json_encode([
    'success' => true,
    'media' => $media
]);
?> 