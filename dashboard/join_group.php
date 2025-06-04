<?php
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $group_id = $input['group_id'];
    $user_id = $_SESSION['user_id'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar que el grupo existe y es público
    $query = "SELECT * FROM groups WHERE id = ? AND privacy = 'public' AND is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$group_id]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$group) {
        echo json_encode(['success' => false, 'error' => 'Grupo no encontrado o es privado']);
        exit();
    }
    
    // Verificar si ya es miembro
    if (isGroupMember($user_id, $group_id)) {
        echo json_encode(['success' => false, 'error' => 'Ya eres miembro de este grupo']);
        exit();
    }
    
    // Unirse al grupo
    $query = "INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'member')";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$group_id, $user_id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al unirse al grupo']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?> 