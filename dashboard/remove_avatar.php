<?php
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');
$response = ['success' => false, 'error' => ''];

try {
    $database = new Database();
    $db = $database->getConnection();

    // Obtener avatar actual
    $query = "SELECT avatar_url FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $avatar_url = $stmt->fetchColumn();

    // Eliminar archivo si existe
    if ($avatar_url && file_exists($avatar_url)) {
        unlink($avatar_url);
    }

    // Actualizar base de datos
    $query = "UPDATE users SET avatar_url = NULL WHERE id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$_SESSION['user_id']])) {
        $response['success'] = true;
    } else {
        throw new Exception('Error al actualizar la base de datos');
    }

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?> 