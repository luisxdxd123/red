<?php
require_once '../includes/functions.php';
requireLogin();

if (isset($_GET['post_id'])) {
    $post_id = $_GET['post_id'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT c.*, u.username, u.first_name, u.last_name 
              FROM comments c 
              JOIN users u ON c.user_id = u.id 
              WHERE c.post_id = ? 
              ORDER BY c.created_at ASC";
    $stmt = $db->prepare($query);
    $stmt->execute([$post_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($comments as $comment):
?>
        <div class="flex items-start space-x-2 mb-3 p-3 bg-gray-50 rounded-lg">
            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-400 to-purple-400 flex items-center justify-center text-white font-bold text-xs">
                <?php echo strtoupper(substr($comment['first_name'], 0, 1) . substr($comment['last_name'], 0, 1)); ?>
            </div>
            <div class="flex-1">
                <div class="flex items-center space-x-2 mb-1">
                    <span class="font-semibold text-sm text-gray-900"><?php echo $comment['first_name'] . ' ' . $comment['last_name']; ?></span>
                    <span class="text-xs text-gray-500">@<?php echo $comment['username']; ?></span>
                    <span class="text-xs text-gray-400">•</span>
                    <span class="text-xs text-gray-500"><?php echo timeAgo($comment['created_at']); ?></span>
                </div>
                <p class="text-sm text-gray-700"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
            </div>
        </div>
<?php 
    endforeach;
    
    if (empty($comments)) {
        echo '<p class="text-gray-500 text-sm text-center py-4">No hay comentarios aún. ¡Sé el primero en comentar!</p>';
    }
}
?> 