<?php
require_once '../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $content = cleanInput($_POST['content']);
    $has_media = false;
    $media_errors = [];
    
    if (!empty($content) || (!empty($_FILES['media']['name'][0]))) {
        $database = new Database();
        $db = $database->getConnection();
        
        try {
            // Iniciar transacción
            $db->beginTransaction();
            
            // Insertar el post
            $query = "INSERT INTO posts (user_id, content, has_media) VALUES (?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$_SESSION['user_id'], $content, false]); // Inicialmente sin medios
            $post_id = $db->lastInsertId();
            
            // Procesar archivos multimedia si existen
            if (!empty($_FILES['media']['name'][0])) {
                $uploaded_files = 0;
                $total_files = count($_FILES['media']['name']);
                
                // Límite de archivos por post
                if ($total_files > 10) {
                    throw new Exception('Máximo 10 archivos por publicación');
                }
                
                for ($i = 0; $i < $total_files; $i++) {
                    if ($_FILES['media']['error'][$i] == UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['media']['name'][$i],
                            'type' => $_FILES['media']['type'][$i],
                            'tmp_name' => $_FILES['media']['tmp_name'][$i],
                            'size' => $_FILES['media']['size'][$i]
                        ];
                        
                        $upload_result = uploadMediaFile($file, $post_id);
                        
                        if ($upload_result['success']) {
                            $uploaded_files++;
                            $has_media = true;
                        } else {
                            $media_errors[] = "Error en archivo " . ($i + 1) . ": " . $upload_result['error'];
                        }
                    }
                }
                
                // Actualizar el post para indicar que tiene medios
                if ($has_media) {
                    $query = "UPDATE posts SET has_media = TRUE WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$post_id]);
                }
            }
            
            // Confirmar transacción
            $db->commit();
            
            if (!empty($media_errors)) {
                $_SESSION['warning'] = 'Post publicado pero algunos archivos no se pudieron subir: ' . implode(', ', $media_errors);
            } else {
                $_SESSION['success'] = 'Post publicado exitosamente' . ($has_media ? ' con medios' : '');
            }
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $db->rollBack();
            $_SESSION['error'] = 'Error al publicar el post: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = 'Debes escribir algo o subir al menos un archivo';
    }
}

header('Location: index.php');
exit();
?> 