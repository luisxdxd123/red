<?php
require_once '../includes/functions.php';
requireLogin();

// Obtener permisos del usuario
$user_permissions = getUserPermissions($_SESSION['user_id']);
$unread_messages = 0;

// Solo obtener mensajes no leídos si el usuario tiene acceso a mensajes
if ($user_permissions['can_access_messages']) {
    $unread_messages = getUnreadMessagesCount($_SESSION['user_id']);
}

$database = new Database();
$db = $database->getConnection();

// Obtener información del usuario
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener posts del usuario
$query = "SELECT p.*, u.username, u.first_name, u.last_name 
          FROM posts p 
          JOIN users u ON p.user_id = u.id 
          WHERE p.user_id = ? 
          ORDER BY p.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar estadísticas
$query = "SELECT COUNT(*) as posts_count FROM posts WHERE user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$posts_count = $stmt->fetchColumn();

$query = "SELECT COUNT(*) as followers_count FROM follows WHERE following_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$followers_count = $stmt->fetchColumn();

$query = "SELECT COUNT(*) as following_count FROM follows WHERE follower_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$following_count = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Red Social</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include '../includes/navbar.php'; ?>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Información del Perfil -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center space-x-6">
                <div class="w-20 h-20 rounded-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold text-2xl">
                    <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                </div>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900"><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h1>
                    <p class="text-gray-600">@<?php echo $user['username']; ?></p>
                    <p class="text-gray-700 mt-2"><?php echo $user['bio'] ?: 'Sin biografía'; ?></p>
                    <p class="text-sm text-gray-500 mt-1">Miembro desde <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                </div>
                <div class="text-right">
                    <button onclick="openEditModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        <i class="fas fa-edit mr-2"></i>Editar Perfil
                    </button>
                </div>
            </div>
            
            <!-- Estadísticas -->
            <div class="grid grid-cols-3 gap-4 mt-6 pt-6 border-t border-gray-200">
                <div class="text-center">
                    <div class="text-2xl font-bold text-indigo-600"><?php echo $posts_count; ?></div>
                    <div class="text-sm text-gray-600">Posts</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-indigo-600"><?php echo $followers_count; ?></div>
                    <div class="text-sm text-gray-600">Seguidores</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-indigo-600"><?php echo $following_count; ?></div>
                    <div class="text-sm text-gray-600">Siguiendo</div>
                </div>
            </div>
        </div>

        <!-- Posts del Usuario -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Mis Posts</h2>
            
            <?php if (empty($posts)): ?>
                <div class="text-center py-8">
                    <i class="fas fa-edit text-gray-400 text-4xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">No has publicado nada aún</h3>
                    <p class="text-gray-500">¡Comparte tu primer post desde la página de inicio!</p>
                    <a href="index.php" class="inline-block mt-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Crear Post
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($posts as $post): ?>
                        <div class="border-b border-gray-200 pb-6 last:border-b-0" data-post-id="<?php echo $post['id']; ?>">
                            <div class="flex items-center mb-3">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold text-xs">
                                    <?php echo strtoupper(substr($post['first_name'], 0, 1) . substr($post['last_name'], 0, 1)); ?>
                                </div>
                                <div class="ml-3 flex-1">
                                    <h4 class="font-semibold text-gray-900"><?php echo $post['first_name'] . ' ' . $post['last_name']; ?></h4>
                                    <p class="text-xs text-gray-500"><?php echo timeAgo($post['created_at']); ?></p>
                                </div>
                                <div class="flex space-x-2">
                                    <button onclick="deletePost(<?php echo $post['id']; ?>)" 
                                            class="text-gray-500 hover:text-red-500 transition duration-300" 
                                            title="Eliminar publicación">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <p class="text-gray-800 leading-relaxed mb-3"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            
                            <div class="flex items-center space-x-4 text-sm text-gray-500">
                                <span class="flex items-center">
                                    <i class="fas fa-heart text-red-500 mr-1"></i>
                                    <?php echo getPostLikesCount($post['id']); ?> likes
                                </span>
                                <span class="flex items-center">
                                    <i class="fas fa-comment text-blue-500 mr-1"></i>
                                    <?php echo getPostCommentsCount($post['id']); ?> comentarios
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para Editar Perfil -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Editar Perfil</h3>
                <form action="update_profile.php" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nombre</label>
                        <input type="text" name="first_name" value="<?php echo $user['first_name']; ?>" 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Apellido</label>
                        <input type="text" name="last_name" value="<?php echo $user['last_name']; ?>" 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Biografía</label>
                        <textarea name="bio" rows="3" 
                                  class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"><?php echo $user['bio']; ?></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeEditModal()" 
                                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded transition duration-300">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded transition duration-300">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openEditModal() {
            document.getElementById('editModal').classList.remove('hidden');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // Función para eliminar post
        function deletePost(postId) {
            if (confirm('¿Estás seguro de que quieres eliminar esta publicación? Esta acción no se puede deshacer.')) {
                // Mostrar indicador de carga
                const postElement = document.querySelector(`[data-post-id="${postId}"]`);
                const deleteBtn = postElement.querySelector('button[onclick*="deletePost"]');
                const originalIcon = deleteBtn.innerHTML;
                deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                deleteBtn.disabled = true;
                
                fetch('delete_post.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ post_id: postId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Animar la eliminación del post
                        postElement.style.transition = 'all 0.3s ease-out';
                        postElement.style.transform = 'translateX(-100%)';
                        postElement.style.opacity = '0';
                        
                        setTimeout(() => {
                            postElement.remove();
                            
                            // Mostrar mensaje de éxito
                            showNotification('Publicación eliminada exitosamente', 'success');
                            
                            // Verificar si no quedan posts
                            const remainingPosts = document.querySelectorAll('[data-post-id]');
                            if (remainingPosts.length === 0) {
                                location.reload(); // Recargar para mostrar el mensaje "No has publicado nada aún"
                            }
                        }, 300);
                    } else {
                        showNotification('Error al eliminar la publicación: ' + data.error, 'error');
                        // Restaurar botón
                        deleteBtn.innerHTML = originalIcon;
                        deleteBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error al eliminar la publicación', 'error');
                    // Restaurar botón
                    deleteBtn.innerHTML = originalIcon;
                    deleteBtn.disabled = false;
                });
            }
        }

        // Función para mostrar notificaciones
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg text-white max-w-sm ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 
                'bg-blue-500'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${
                        type === 'success' ? 'fa-check-circle' : 
                        type === 'error' ? 'fa-exclamation-circle' : 
                        'fa-info-circle'
                    } mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animar entrada
            notification.style.transform = 'translateX(100%)';
            notification.style.transition = 'transform 0.3s ease-out';
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 10);
            
            // Eliminar después de 4 segundos
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 4000);
        }
    </script>
</body>
</html> 