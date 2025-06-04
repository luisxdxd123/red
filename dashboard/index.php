<?php
require_once '../includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Obtener posts con información del usuario
$query = "SELECT p.*, u.username, u.first_name, u.last_name, u.profile_picture 
          FROM posts p 
          JOIN users u ON p.user_id = u.id 
          ORDER BY p.created_at DESC 
          LIMIT 20";
$stmt = $db->prepare($query);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener número de mensajes no leídos
$unread_messages = getUnreadMessagesCount($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - Red Social</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between">
                <div class="flex space-x-7">
                    <div class="flex items-center py-4">
                        <i class="fas fa-users text-indigo-600 text-2xl mr-2"></i>
                        <span class="font-semibold text-gray-500 text-lg">Red Social</span>
                    </div>
                    <div class="hidden md:flex items-center space-x-1">
                        <a href="index.php" class="py-4 px-2 text-indigo-500 border-b-4 border-indigo-500 font-semibold">
                            <i class="fas fa-home mr-1"></i>Inicio
                        </a>
                        <a href="groups.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300">
                            <i class="fas fa-users mr-1"></i>Grupos
                        </a>
                        <a href="messages.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300 relative">
                            <i class="fas fa-envelope mr-1"></i>Mensajes
                            <?php if ($unread_messages > 0): ?>
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                    <?php echo $unread_messages > 9 ? '9+' : $unread_messages; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <a href="profile.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300">
                            <i class="fas fa-user mr-1"></i>Mi Perfil
                        </a>
                        <a href="users.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300">
                            <i class="fas fa-user-friends mr-1"></i>Usuarios
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="text-gray-700">Hola, <?php echo $_SESSION['first_name']; ?>!</span>
                    <a href="../auth/logout.php" class="py-2 px-2 font-medium text-gray-500 rounded hover:bg-red-500 hover:text-white transition duration-300">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Crear Post -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">¿Qué estás pensando?</h3>
            <form action="create_post.php" method="POST" class="space-y-4">
                <textarea name="content" placeholder="Comparte algo interesante..." 
                          class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none" 
                          rows="3" required></textarea>
                <div class="flex justify-end">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        <i class="fas fa-paper-plane mr-2"></i>Publicar
                    </button>
                </div>
            </form>
        </div>

        <!-- Posts Timeline -->
        <div class="space-y-6">
            <?php foreach ($posts as $post): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <!-- Header del Post -->
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold text-sm">
                            <?php echo strtoupper(substr($post['first_name'], 0, 1) . substr($post['last_name'], 0, 1)); ?>
                        </div>
                        <div class="ml-3 flex-1">
                            <h4 class="font-semibold text-gray-900"><?php echo $post['first_name'] . ' ' . $post['last_name']; ?></h4>
                            <p class="text-sm text-gray-500">@<?php echo $post['username']; ?> • <?php echo timeAgo($post['created_at']); ?></p>
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($post['user_id'] != $_SESSION['user_id'] && isFollowing($_SESSION['user_id'], $post['user_id'])): ?>
                                <a href="messages.php?user=<?php echo $post['user_id']; ?>" 
                                   class="text-gray-500 hover:text-indigo-500 transition duration-300" 
                                   title="Enviar mensaje">
                                    <i class="fas fa-envelope"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Contenido del Post -->
                    <div class="mb-4">
                        <p class="text-gray-800 leading-relaxed"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                    </div>

                    <!-- Acciones del Post -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                        <div class="flex space-x-6">
                            <button onclick="toggleLike(<?php echo $post['id']; ?>)" 
                                    class="flex items-center space-x-2 text-gray-500 hover:text-red-500 transition duration-300"
                                    id="like-btn-<?php echo $post['id']; ?>">
                                <i class="<?php echo hasUserLikedPost($_SESSION['user_id'], $post['id']) ? 'fas text-red-500' : 'far'; ?> fa-heart"></i>
                                <span id="like-count-<?php echo $post['id']; ?>"><?php echo getPostLikesCount($post['id']); ?></span>
                            </button>
                            
                            <button onclick="toggleComments(<?php echo $post['id']; ?>)" 
                                    class="flex items-center space-x-2 text-gray-500 hover:text-blue-500 transition duration-300">
                                <i class="far fa-comment"></i>
                                <span><?php echo getPostCommentsCount($post['id']); ?></span>
                            </button>
                        </div>
                    </div>

                    <!-- Sección de Comentarios (inicialmente oculta) -->
                    <div id="comments-<?php echo $post['id']; ?>" class="hidden mt-4 border-t border-gray-200 pt-4">
                        <!-- Formulario para nuevo comentario -->
                        <form onsubmit="addComment(event, <?php echo $post['id']; ?>)" class="mb-4">
                            <div class="flex space-x-2">
                                <textarea placeholder="Escribe un comentario..." 
                                          class="flex-1 p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none" 
                                          rows="2" required></textarea>
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition duration-300">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                        
                        <!-- Lista de comentarios -->
                        <div id="comments-list-<?php echo $post['id']; ?>">
                            <!-- Los comentarios se cargarán dinámicamente -->
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($posts)): ?>
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <i class="fas fa-comments text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">No hay posts aún</h3>
                <p class="text-gray-500">¡Sé el primero en compartir algo!</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Función para dar/quitar like
        function toggleLike(postId) {
            fetch('toggle_like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ post_id: postId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const likeBtn = document.getElementById(`like-btn-${postId}`);
                    const likeCount = document.getElementById(`like-count-${postId}`);
                    const icon = likeBtn.querySelector('i');
                    
                    if (data.liked) {
                        icon.className = 'fas fa-heart text-red-500';
                    } else {
                        icon.className = 'far fa-heart';
                    }
                    
                    likeCount.textContent = data.count;
                }
            });
        }

        // Función para mostrar/ocultar comentarios
        function toggleComments(postId) {
            const commentsDiv = document.getElementById(`comments-${postId}`);
            
            if (commentsDiv.classList.contains('hidden')) {
                commentsDiv.classList.remove('hidden');
                loadComments(postId);
            } else {
                commentsDiv.classList.add('hidden');
            }
        }

        // Función para cargar comentarios
        function loadComments(postId) {
            fetch(`get_comments.php?post_id=${postId}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById(`comments-list-${postId}`).innerHTML = data;
            });
        }

        // Función para agregar comentario
        function addComment(event, postId) {
            event.preventDefault();
            
            const form = event.target;
            const textarea = form.querySelector('textarea');
            const content = textarea.value.trim();
            
            if (!content) return;
            
            fetch('add_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    post_id: postId, 
                    content: content 
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    textarea.value = '';
                    loadComments(postId);
                }
            });
        }
    </script>
</body>
</html> 