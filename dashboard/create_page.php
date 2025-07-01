<?php
require_once '../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = cleanInput($_POST['name']);
    $description = cleanInput($_POST['description']);
    
    if (!empty($name) && !empty($description)) {
        $database = new Database();
        $db = $database->getConnection();
        
        try {
            $db->beginTransaction();
            
            // Procesar imagen de perfil
            $profile_image = 'default-page.jpg';
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['profile_image']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    $profile_image = uniqid() . '_' . $filename;
                    $upload_path = 'uploads/pages/profiles/';
                    
                    // Crear directorio si no existe
                    if (!file_exists($upload_path)) {
                        mkdir($upload_path, 0777, true);
                    }
                    
                    move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path . $profile_image);
                }
            }
            
            // Procesar imagen de portada
            $cover_image = 'default-page-cover.jpg';
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['cover_image']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    $cover_image = uniqid() . '_' . $filename;
                    $upload_path = 'uploads/pages/covers/';
                    
                    // Crear directorio si no existe
                    if (!file_exists($upload_path)) {
                        mkdir($upload_path, 0777, true);
                    }
                    
                    move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_path . $cover_image);
                }
            }
            
            // Crear la página
            $query = "INSERT INTO pages (name, description, profile_image, cover_image, creator_id) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$name, $description, $profile_image, $cover_image, $_SESSION['user_id']]);
            
            $page_id = $db->lastInsertId();
            
            $db->commit();
            $_SESSION['success'] = 'Página creada exitosamente';
            
            header('Location: page_detail.php?id=' . $page_id);
            exit();
            
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'Error al crear la página';
            header('Location: pages.php');
            exit();
        }
    } else {
        $_SESSION['error'] = 'Todos los campos son requeridos';
        header('Location: pages.php');
        exit();
    }
} else {
    header('Location: pages.php');
    exit();
} 