<?php
require_once '../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $content = cleanInput($_POST['content']);
    
    if (!empty($content)) {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "INSERT INTO posts (user_id, content) VALUES (?, ?)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$_SESSION['user_id'], $content])) {
            $_SESSION['success'] = 'Post publicado exitosamente';
        } else {
            $_SESSION['error'] = 'Error al publicar el post';
        }
    } else {
        $_SESSION['error'] = 'El contenido no puede estar vacío';
    }
}

header('Location: index.php');
exit();
?> 