<?php
require_once '../includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Verificar si el usuario tiene permiso para crear páginas
$query = "SELECT can_create_pages FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user['can_create_pages']) {
    header('Location: pages.php');
    exit;
}

// Continuar con la creación de la página si tiene permiso
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $profile_image = 'default-page.jpg';
    $cover_image = 'default-page-cover.jpg';

    // Procesar imagen de perfil
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $file_info = pathinfo($_FILES['profile_image']['name']);
        $extension = strtolower($file_info['extension']);
        
        if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
            $profile_image = uniqid() . '_' . time() . '.' . $extension;
            move_uploaded_file(
                $_FILES['profile_image']['tmp_name'],
                'uploads/pages/profiles/' . $profile_image
            );
        }
    }

    // Procesar imagen de portada
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $file_info = pathinfo($_FILES['cover_image']['name']);
        $extension = strtolower($file_info['extension']);
        
        if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
            $cover_image = uniqid() . '_' . time() . '.' . $extension;
            move_uploaded_file(
                $_FILES['cover_image']['tmp_name'],
                'uploads/pages/covers/' . $cover_image
            );
        }
    }

    // Insertar la página en la base de datos
    $query = "INSERT INTO pages (name, description, creator_id, profile_image, cover_image) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$name, $description, $_SESSION['user_id'], $profile_image, $cover_image])) {
        $page_id = $db->lastInsertId();
        
        // Hacer que el creador siga automáticamente la página
        $query = "INSERT INTO page_followers (page_id, user_id) VALUES (?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$page_id, $_SESSION['user_id']]);
        
        header('Location: page_detail.php?id=' . $page_id);
        exit;
    }
}

header('Location: pages.php');
exit; 