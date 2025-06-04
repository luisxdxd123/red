<?php
require_once '../includes/functions.php';
requireLogin();

$group_post_id = $_GET['group_post_id'] ?? null;

if (!$group_post_id || !is_numeric($group_post_id)) {
    echo '<p class="text-gray-500 text-sm">Error al cargar comentarios</p>';
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Verificar que el post existe y obtener el grupo_id
    $query = "SELECT group_id FROM group_posts WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$group_post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        echo '<p class="text-gray-500 text-sm">Post no encontrado</p>';
        exit();
    }
    
    // Verificar que el usuario es miembro del grupo
    if (!isGroupMember($_SESSION['user_id'], $post['group_id'])) {
        echo '<p class="text-gray-500 text-sm">No tienes acceso a este contenido</p>';
        exit();
    }
    
    // Obtener comentarios
    $query = "SELECT gpc.*, u.username, u.first_name, u.last_name 
              FROM group_post_comments gpc 
              JOIN users u ON gpc.user_id = u.id 
              WHERE gpc.group_post_id = ? 
              ORDER BY gpc.created_at ASC";
    $stmt = $db->prepare($query);
    $stmt->execute([$group_post_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($comments)) {
        echo '<p class="text-gray-500 text-sm text-center py-4">No hay comentarios aún</p>';
    } else {
        foreach ($comments as $comment) {
            ?>
            <div class="flex space-x-3 mb-3">
                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-green-400 to-blue-400 flex items-center justify-center text-white font-bold text-xs">
                    <?php echo strtoupper(substr($comment['first_name'], 0, 1) . substr($comment['last_name'], 0, 1)); ?>
                </div>
                <div class="flex-1">
                    <div class="bg-gray-100 rounded-lg p-3">
                        <div class="flex items-center justify-between mb-1">
                            <h5 class="font-medium text-gray-900 text-sm"><?php echo $comment['first_name'] . ' ' . $comment['last_name']; ?></h5>
                            <span class="text-xs text-gray-500"><?php echo timeAgo($comment['created_at']); ?></span>
                        </div>
                        <p class="text-gray-800 text-sm"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                    </div>
                </div>
            </div>
            <?php
        }
    }
    
} catch (PDOException $e) {
    echo '<p class="text-red-500 text-sm">Error al cargar comentarios</p>';
}
?> 