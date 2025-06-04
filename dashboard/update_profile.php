<?php
require_once '../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = cleanInput($_POST['first_name']);
    $last_name = cleanInput($_POST['last_name']);
    $bio = cleanInput($_POST['bio']);
    
    if (!empty($first_name) && !empty($last_name)) {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "UPDATE users SET first_name = ?, last_name = ?, bio = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$first_name, $last_name, $bio, $_SESSION['user_id']])) {
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['success'] = 'Perfil actualizado exitosamente';
        } else {
            $_SESSION['error'] = 'Error al actualizar el perfil';
        }
    } else {
        $_SESSION['error'] = 'El nombre y apellido son requeridos';
    }
}

header('Location: profile.php');
exit();
?> 