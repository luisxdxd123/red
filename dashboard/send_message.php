<?php
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $conversation_id = $input['conversation_id'];
    $content = cleanInput($input['content']);
    $sender_id = $_SESSION['user_id'];
    
    if (!empty($content) && !empty($conversation_id)) {
        $database = new Database();
        $db = $database->getConnection();
        
        // Verificar que el usuario tiene acceso a esta conversación
        $query = "SELECT * FROM conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$conversation_id, $sender_id, $sender_id]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$conversation) {
            echo json_encode(['success' => false, 'error' => 'Conversación no encontrada']);
            exit();
        }
        
        // Determinar el receptor
        $receiver_id = ($conversation['user1_id'] == $sender_id) ? $conversation['user2_id'] : $conversation['user1_id'];
        
        try {
            $db->beginTransaction();
            
            // Insertar el mensaje
            $query = "INSERT INTO messages (conversation_id, sender_id, receiver_id, content) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$conversation_id, $sender_id, $receiver_id, $content]);
            
            // Actualizar timestamp de la conversación
            $query = "UPDATE conversations SET last_message_at = NOW() WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$conversation_id]);
            
            $db->commit();
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'error' => 'Error al enviar mensaje']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?> 