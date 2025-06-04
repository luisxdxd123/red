<?php
require_once '../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = cleanInput($_POST['name']);
    $description = cleanInput($_POST['description']);
    $privacy = cleanInput($_POST['privacy']);
    
    if (!empty($name) && !empty($description) && in_array($privacy, ['public', 'private'])) {
        $database = new Database();
        $db = $database->getConnection();
        
        try {
            $db->beginTransaction();
            
            // Crear el grupo
            $query = "INSERT INTO groups (name, description, privacy, creator_id) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$name, $description, $privacy, $_SESSION['user_id']]);
            
            $group_id = $db->lastInsertId();
            
            // Agregar al creador como admin del grupo
            $query = "INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'admin')";
            $stmt = $db->prepare($query);
            $stmt->execute([$group_id, $_SESSION['user_id']]);
            
            $db->commit();
            $_SESSION['success'] = 'Grupo creado exitosamente';
            
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'Error al crear el grupo';
        }
    } else {
        $_SESSION['error'] = 'Todos los campos son requeridos';
    }
}

header('Location: groups.php');
exit();
?> 