<?php
require_once '../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pages.php');
    exit();
}

$page_id = $_POST['page_id'] ?? null;
$content = $_POST['content'] ?? '';

if (!$page_id || !is_numeric($page_id) || empty(trim($content))) {
    header('Location: pages.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Verificar que el usuario es el creador de la página
if (!isPageCreator($_SESSION['user_id'], $page_id)) {
    header('Location: page_detail.php?id=' . $page_id . '&error=unauthorized');
    exit();
}

try {
    $db->beginTransaction();
    
    $media_type = null;
    $media_url = null;
    
    // Procesar archivo multimedia si existe
    if (isset($_FILES['media']) && $_FILES['media']['error'] == 0) {
        $allowed_image = ['jpg', 'jpeg', 'png', 'gif'];
        $allowed_video = ['mp4', 'webm', 'ogg'];
        
        $filename = $_FILES['media']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Determinar el tipo de archivo
        if (in_array($ext, $allowed_image)) {
            $media_type = 'image';
        } elseif (in_array($ext, $allowed_video)) {
            $media_type = 'video';
        }
        
        if ($media_type) {
            $media_url = uniqid() . '_' . $filename;
            $upload_path = 'uploads/pages/posts/';
            
            // Crear directorio si no existe
            if (!file_exists($upload_path)) {
                mkdir($upload_path, 0777, true);
            }
            
            move_uploaded_file($_FILES['media']['tmp_name'], $upload_path . $media_url);
        }
    }
    
    // Crear el post
    $query = "INSERT INTO page_posts (page_id, user_id, content, media_type, media_url) VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$page_id, $_SESSION['user_id'], trim($content), $media_type, $media_url]);
    
    $db->commit();
    $_SESSION['success'] = 'Publicación creada exitosamente';
    
} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['error'] = 'Error al crear la publicación';
    
    // Si hubo error y se subió un archivo, eliminarlo
    if ($media_url && file_exists('uploads/pages/posts/' . $media_url)) {
        unlink('uploads/pages/posts/' . $media_url);
    }
}

header('Location: page_detail.php?id=' . $page_id);
exit(); 