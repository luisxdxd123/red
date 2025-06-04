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
    
    // Verificar que es miembro del grupo pero no admin
    $role = getUserGroupRole($user_id, $group_id);
    
    if (!$role) {
        echo json_encode(['success' => false, 'error' => 'No eres miembro de este grupo']);
        exit();
    }
    
    if ($role == 'admin') {
        echo json_encode(['success' => false, 'error' => 'Los administradores no pueden salir del grupo']);
        exit();
    }
    
    // Salir del grupo
    $query = "DELETE FROM group_members WHERE group_id = ? AND user_id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$group_id, $user_id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al salir del grupo']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?> 