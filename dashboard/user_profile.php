<?php
require_once '../includes/functions.php';
requireLogin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: users.php');
    exit();
}

$user_id = $_GET['id'];

// No permitir ver tu propio perfil aquí
if ($user_id == $_SESSION['user_id']) {
    header('Location: profile.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Obtener información del usuario
$query = "SELECT * FROM users WHERE id = ? AND is_active = 1";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: users.php');
    exit();
}

// Obtener posts del usuario
$query = "SELECT p.*, u.username, u.first_name, u.last_name 
          FROM posts p 
          JOIN users u ON p.user_id = u.id 
          WHERE p.user_id = ? 
          ORDER BY p.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar estadísticas
$query = "SELECT COUNT(*) FROM posts WHERE user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$posts_count = $stmt->fetchColumn();

$query = "SELECT COUNT(*) FROM follows WHERE following_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$followers_count = $stmt->fetchColumn();

$query = "SELECT COUNT(*) FROM follows WHERE follower_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$following_count = $stmt->fetchColumn();

$is_following = isFollowing($_SESSION['user_id'], $user_id);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $user['first_name'] . ' ' . $user['last_name']; ?> - Red Social</title>
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
                        <a href="index.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300">Inicio</a>
                        <a href="profile.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300">Mi Perfil</a>
                        <a href="users.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300">Usuarios</a>
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
                    <button onclick="toggleFollow(<?php echo $user['id']; ?>)" 
                            id="follow-btn-<?php echo $user['id']; ?>"
                            class="<?php echo $is_following ? 'bg-gray-200 hover:bg-gray-300 text-gray-800' : 'bg-indigo-600 hover:bg-indigo-700 text-white'; ?> font-bold py-2 px-4 rounded-lg transition duration-300">
                        <i class="<?php echo $is_following ? 'fas fa-user-check' : 'fas fa-user-plus'; ?> mr-2"></i>
                        <?php echo $is_following ? 'Siguiendo' : 'Seguir'; ?>
                    </button>
                    
                    <?php if ($is_following): ?>
                        <a href="messages.php?user=<?php echo $user['id']; ?>" 
                           class="ml-2 bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                            <i class="fas fa-envelope mr-2"></i>Mensaje
                        </a>
                    <?php else: ?>
                        <div class="ml-2 inline-block bg-gray-300 text-gray-500 font-bold py-2 px-4 rounded-lg cursor-not-allowed" title="Debes seguir al usuario para enviar mensajes">
                            <i class="fas fa-envelope mr-2"></i>Mensaje
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Estadísticas -->
            <div class="grid grid-cols-3 gap-4 mt-6 pt-6 border-t border-gray-200">
                <div class="text-center">
                    <div class="text-2xl font-bold text-indigo-600"><?php echo $posts_count; ?></div>
                    <div class="text-sm text-gray-600">Posts</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-indigo-600" id="followers-count"><?php echo $followers_count; ?></div>
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
            <h2 class="text-xl font-bold text-gray-900 mb-6">Posts de <?php echo $user['first_name']; ?></h2>
            
            <?php if (empty($posts)): ?>
                <div class="text-center py-8">
                    <i class="fas fa-edit text-gray-400 text-4xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2"><?php echo $user['first_name']; ?> no ha publicado nada aún</h3>
                    <p class="text-gray-500">Cuando publique algo, aparecerá aquí.</p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($posts as $post): ?>
                        <div class="border-b border-gray-200 pb-6 last:border-b-0">
                            <div class="flex items-center mb-3">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold text-xs">
                                    <?php echo strtoupper(substr($post['first_name'], 0, 1) . substr($post['last_name'], 0, 1)); ?>
                                </div>
                                <div class="ml-3">
                                    <h4 class="font-semibold text-gray-900"><?php echo $post['first_name'] . ' ' . $post['last_name']; ?></h4>
                                    <p class="text-xs text-gray-500"><?php echo timeAgo($post['created_at']); ?></p>
                                </div>
                            </div>
                            
                            <p class="text-gray-800 leading-relaxed mb-3"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            
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

                            <!-- Sección de Comentarios -->
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
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Función para seguir/dejar de seguir
        function toggleFollow(userId) {
            fetch('toggle_follow.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ user_id: userId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const followBtn = document.getElementById(`follow-btn-${userId}`);
                    const followersCount = document.getElementById('followers-count');
                    
                    if (data.following) {
                        followBtn.className = 'bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg transition duration-300';
                        followBtn.innerHTML = '<i class="fas fa-user-check mr-2"></i>Siguiendo';
                        followersCount.textContent = parseInt(followersCount.textContent) + 1;
                    } else {
                        followBtn.className = 'bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300';
                        followBtn.innerHTML = '<i class="fas fa-user-plus mr-2"></i>Seguir';
                        followersCount.textContent = parseInt(followersCount.textContent) - 1;
                    }
                }
            });
        }

        // Las demás funciones de likes y comentarios (iguales que en index.php)
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

        function toggleComments(postId) {
            const commentsDiv = document.getElementById(`comments-${postId}`);
            
            if (commentsDiv.classList.contains('hidden')) {
                commentsDiv.classList.remove('hidden');
                loadComments(postId);
            } else {
                commentsDiv.classList.add('hidden');
            }
        }

        function loadComments(postId) {
            fetch(`get_comments.php?post_id=${postId}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById(`comments-list-${postId}`).innerHTML = data;
            });
        }

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