<?php
require_once '../includes/functions.php';
requireLogin();

$response = ['success' => false, 'error' => ''];

try {
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se ha subido ninguna imagen');
    }

    $file = $_FILES['avatar'];
    
    // Validar tipo de archivo
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('Tipo de archivo no permitido. Solo se permiten JPG, PNG y GIF.');
    }

    // Validar tamaño (2MB máximo)
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new Exception('La imagen no debe superar los 2MB');
    }

    // Crear directorio si no existe
    $upload_dir = "uploads/avatars/" . date('Y/m');
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generar nombre único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . '/' . $filename;

    // Mover archivo
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Error al guardar la imagen');
    }

    // Actualizar base de datos
    $database = new Database();
    $db = $database->getConnection();

    // Obtener avatar anterior
    $query = "SELECT avatar_url FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $old_avatar = $stmt->fetchColumn();

    // Actualizar avatar en la base de datos
    $query = "UPDATE users SET avatar_url = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$filepath, $_SESSION['user_id']])) {
        // Eliminar avatar anterior si existe
        if ($old_avatar && file_exists($old_avatar)) {
            unlink($old_avatar);
        }
        
        $response['success'] = true;
        $_SESSION['success'] = 'Foto de perfil actualizada correctamente';
    } else {
        throw new Exception('Error al actualizar la base de datos');
    }

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    $_SESSION['error'] = $e->getMessage();
}

// Redireccionar de vuelta al perfil
header('Location: profile.php');
exit;
?> 