<?php
require_once '../includes/functions.php';
requireLogin();

if (isset($_GET['conversation_id'])) {
    $conversation_id = $_GET['conversation_id'];
    $user_id = $_SESSION['user_id'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar que el usuario tiene acceso a esta conversación
    $query = "SELECT * FROM conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$conversation_id, $user_id, $user_id]);
    
    if ($stmt->rowCount() == 0) {
        echo '<p class="text-red-500 text-center">Acceso denegado</p>';
        exit();
    }
    
    // Marcar mensajes como leídos
    markMessagesAsRead($conversation_id, $user_id);
    
    // Obtener mensajes
    $query = "SELECT m.*, u.first_name, u.last_name, u.username 
              FROM messages m 
              JOIN users u ON m.sender_id = u.id 
              WHERE m.conversation_id = ? 
              ORDER BY m.created_at ASC";
    $stmt = $db->prepare($query);
    $stmt->execute([$conversation_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($messages)) {
        echo '<div class="text-center text-gray-500 py-8">
                <i class="fas fa-comment-dots text-3xl mb-2"></i>
                <p>No hay mensajes aún</p>
                <p class="text-sm">¡Envía el primer mensaje!</p>
              </div>';
    } else {
        foreach ($messages as $message):
            $is_own_message = $message['sender_id'] == $user_id;
?>
            <div class="flex <?php echo $is_own_message ? 'justify-end' : 'justify-start'; ?> mb-4">
                <div class="max-w-xs lg:max-w-md">
                    <?php if (!$is_own_message): ?>
                        <div class="flex items-center mb-1">
                            <div class="w-6 h-6 rounded-full bg-gradient-to-r from-blue-400 to-purple-400 flex items-center justify-center text-white font-bold text-xs mr-2">
                                <?php echo strtoupper(substr($message['first_name'], 0, 1) . substr($message['last_name'], 0, 1)); ?>
                            </div>
                            <span class="text-xs text-gray-600"><?php echo $message['first_name']; ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="<?php echo $is_own_message ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-900'; ?> rounded-lg px-4 py-2">
                        <p class="text-sm"><?php echo nl2br(htmlspecialchars($message['content'])); ?></p>
                    </div>
                    
                    <div class="<?php echo $is_own_message ? 'text-right' : 'text-left'; ?> mt-1">
                        <span class="text-xs text-gray-500"><?php echo timeAgo($message['created_at']); ?></span>
                        <?php if ($is_own_message): ?>
                            <i class="fas fa-<?php echo $message['is_read'] ? 'check-double text-blue-500' : 'check text-gray-400'; ?> ml-1"></i>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
<?php 
        endforeach;
    }
}
?> 