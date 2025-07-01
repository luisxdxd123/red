<?php
require_once '../includes/functions.php';
requireLogin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: pages.php');
    exit();
}

$page_id = $_GET['id'];

$database = new Database();
$db = $database->getConnection();

// Obtener información de la página
$query = "SELECT p.*, u.first_name, u.last_name, u.username,
          (SELECT COUNT(*) FROM page_followers WHERE page_id = p.id) as followers_count,
          (SELECT COUNT(*) FROM page_posts WHERE page_id = p.id) as posts_count,
          CASE WHEN EXISTS (
              SELECT 1 FROM page_followers 
              WHERE page_id = p.id AND user_id = ?
          ) THEN 1 ELSE 0 END as is_following
          FROM pages p 
          JOIN users u ON p.creator_id = u.id
          WHERE p.id = ? AND p.is_active = 1";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id'], $page_id]);
$page = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$page) {
    header('Location: pages.php');
    exit();
}

// Verificar si es el creador
$is_creator = isPageCreator($_SESSION['user_id'], $page_id);

// Obtener posts de la página
$query = "SELECT pp.*, u.username, u.first_name, u.last_name,
          (SELECT COUNT(*) FROM page_post_likes WHERE page_post_id = pp.id) as likes_count,
          (SELECT COUNT(*) FROM page_post_comments WHERE page_post_id = pp.id) as comments_count,
          CASE WHEN EXISTS (
              SELECT 1 FROM page_post_likes 
              WHERE page_post_id = pp.id AND user_id = ?
          ) THEN 1 ELSE 0 END as has_liked
          FROM page_posts pp 
          JOIN users u ON pp.user_id = u.id 
          WHERE pp.page_id = ? 
          ORDER BY pp.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id'], $page_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener número de mensajes no leídos
$unread_messages = getUnreadMessagesCount($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page['name']); ?> - Red Social</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex space-x-7">
                    <div>
                        <a href="index.php" class="flex items-center py-4 px-2">
                            <span class="font-semibold text-gray-500 text-lg">Red Social</span>
                        </a>
                    </div>
                    <div class="hidden md:flex items-center space-x-1">
                        <a href="index.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300">
                            <i class="fas fa-home mr-1"></i>Inicio
                        </a>
                        <a href="groups.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300">
                            <i class="fas fa-users mr-1"></i>Grupos
                        </a>
                        <a href="pages.php" class="py-4 px-2 text-indigo-500 border-b-4 border-indigo-500 font-semibold">
                            <i class="fas fa-flag mr-1"></i>Páginas
                        </a>
                        <a href="messages.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300 relative">
                            <i class="fas fa-envelope mr-1"></i>Mensajes
                            <?php if ($unread_messages > 0): ?>
                                <span class="absolute top-3 right-0 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                    <?php echo $unread_messages; ?>
                                </span>
                            <?php endif; ?>
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

    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Header de la Página -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <!-- Cover Image -->
            <div class="h-64 bg-gradient-to-r from-blue-500 to-purple-600 relative">
                <?php if ($page['cover_image'] != 'default-page-cover.jpg'): ?>
                    <img src="uploads/pages/covers/<?php echo $page['cover_image']; ?>" 
                         class="w-full h-full object-cover" alt="Portada">
                <?php endif; ?>
                <div class="absolute inset-0 bg-black bg-opacity-30"></div>
                <div class="absolute top-4 right-4">
                    <a href="pages.php" class="bg-white bg-opacity-90 hover:bg-white text-gray-800 px-3 py-1 rounded-full text-sm font-medium transition duration-300">
                        <i class="fas fa-arrow-left mr-1"></i>Volver a Páginas
                    </a>
                </div>
            </div>
            
            <div class="p-6">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <!-- Imagen de perfil -->
                        <div class="flex items-center mb-4">
                            <div class="w-24 h-24 rounded-full border-4 border-white overflow-hidden bg-white -mt-16 mr-4">
                                <?php if ($page['profile_image'] != 'default-page.jpg'): ?>
                                    <img src="uploads/pages/profiles/<?php echo $page['profile_image']; ?>" 
                                         class="w-full h-full object-cover" alt="Perfil">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center text-white text-3xl font-bold">
                                        <?php echo strtoupper(substr($page['name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($page['name']); ?></h1>
                                <p class="text-gray-700 mb-3"><?php echo htmlspecialchars($page['description']); ?></p>
                            </div>
                        </div>
                        
                        <!-- Información del creador -->
                        <div class="flex items-center mb-4">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold text-sm mr-3">
                                <?php echo strtoupper(substr($page['first_name'], 0, 1) . substr($page['last_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">
                                    Creada por <span class="font-medium"><?php echo $page['first_name'] . ' ' . $page['last_name']; ?></span>
                                </p>
                                <p class="text-xs text-gray-500">@<?php echo $page['username']; ?> • <?php echo date('d M Y', strtotime($page['created_at'])); ?></p>
                            </div>
                        </div>
                        
                        <!-- Estadísticas -->
                        <div class="flex space-x-6 text-sm text-gray-600">
                            <div class="flex items-center">
                                <i class="fas fa-users mr-1"></i>
                                <span class="font-medium"><?php echo $page['followers_count']; ?></span> seguidores
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-edit mr-1"></i>
                                <span class="font-medium"><?php echo $page['posts_count']; ?></span> posts
                            </div>
                        </div>
                    </div>
                    
                    <div class="ml-4">
                        <?php if ($is_creator): ?>
                            <span class="bg-green-600 text-white font-bold py-2 px-4 rounded-lg">
                                <i class="fas fa-crown mr-2"></i>Administrador
                            </span>
                        <?php else: ?>
                            <?php if ($page['is_following']): ?>
                                <button onclick="unfollowPage(<?php echo $page['id']; ?>)" 
                                        class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                                    <i class="fas fa-times mr-2"></i>Dejar de Seguir
                                </button>
                            <?php else: ?>
                                <button onclick="followPage(<?php echo $page['id']; ?>)" 
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                                    <i class="fas fa-plus mr-2"></i>Seguir Página
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Columna Principal - Posts -->
            <div class="lg:col-span-2 space-y-6">
                <?php if ($is_creator): ?>
                    <!-- Crear Post (Solo para el creador) -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-4">Publicar en la página</h3>
                        <form action="create_page_post.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                            <input type="hidden" name="page_id" value="<?php echo $page_id; ?>">
                            <textarea name="content" placeholder="Comparte algo con tus seguidores..." 
                                      class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none" 
                                      rows="3" required></textarea>
                            
                            <div class="flex items-center space-x-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Agregar multimedia (opcional)</label>
                                    <input type="file" name="media" accept="image/*,video/*"
                                           class="block w-full text-sm text-gray-500
                                                  file:mr-4 file:py-2 file:px-4
                                                  file:rounded-full file:border-0
                                                  file:text-sm file:font-semibold
                                                  file:bg-indigo-50 file:text-indigo-700
                                                  hover:file:bg-indigo-100">
                                </div>
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                                    <i class="fas fa-paper-plane mr-2"></i>Publicar
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- Lista de Posts -->
                <?php foreach ($posts as $post): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold text-sm mr-3">
                                <?php echo strtoupper(substr($post['first_name'], 0, 1) . substr($post['last_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900"><?php echo $post['first_name'] . ' ' . $post['last_name']; ?></p>
                                <p class="text-sm text-gray-500"><?php echo date('d M Y H:i', strtotime($post['created_at'])); ?></p>
                            </div>
                        </div>

                        <p class="text-gray-800 mb-4"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>

                        <?php if ($post['media_url']): ?>
                            <div class="mb-4 rounded-lg overflow-hidden">
                                <?php if ($post['media_type'] == 'image'): ?>
                                    <img src="uploads/pages/posts/<?php echo $post['media_url']; ?>" 
                                         class="w-full h-auto" alt="Post media">
                                <?php else: ?>
                                    <video src="uploads/pages/posts/<?php echo $post['media_url']; ?>" 
                                           class="w-full h-auto" controls></video>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="flex items-center justify-between text-sm text-gray-500">
                            <button onclick="togglePagePostLike(<?php echo $post['id']; ?>)" 
                                    class="flex items-center space-x-2 <?php echo $post['has_liked'] ? 'text-red-500' : ''; ?>">
                                <i class="fas fa-heart"></i>
                                <span id="likes-count-<?php echo $post['id']; ?>"><?php echo $post['likes_count']; ?></span>
                            </button>
                            <button onclick="togglePagePostComments(<?php echo $post['id']; ?>)" class="flex items-center space-x-2">
                                <i class="fas fa-comment"></i>
                                <span id="comments-count-<?php echo $post['id']; ?>"><?php echo $post['comments_count']; ?></span>
                            </button>
                            <?php if ($is_creator): ?>
                                <button onclick="deletePagePost(<?php echo $post['id']; ?>)" class="text-red-500">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- Sección de Comentarios -->
                        <div id="comments-section-<?php echo $post['id']; ?>" class="hidden mt-4 pt-4 border-t">
                            <div id="comments-list-<?php echo $post['id']; ?>" class="space-y-4 mb-4">
                                <!-- Los comentarios se cargarán aquí -->
                            </div>
                            
                            <?php if ($page['is_following'] || $is_creator): ?>
                                <form onsubmit="addPagePostComment(event, <?php echo $post['id']; ?>)" class="flex items-center space-x-2">
                                    <textarea placeholder="Escribe un comentario..." 
                                              class="flex-1 p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"
                                              rows="1"></textarea>
                                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition duration-300">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($posts)): ?>
                    <div class="bg-white rounded-lg shadow-md p-8 text-center">
                        <i class="fas fa-edit text-gray-400 text-4xl mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-600 mb-2">No hay publicaciones</h3>
                        <?php if ($is_creator): ?>
                            <p class="text-gray-500">¡Sé el primero en publicar algo en tu página!</p>
                        <?php else: ?>
                            <p class="text-gray-500">Esta página aún no tiene publicaciones.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Barra Lateral -->
            <div class="space-y-6">
                <?php if ($is_creator): ?>
                    <!-- Panel de Administración -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-4">Panel de Administración</h3>
                        <div class="space-y-3">
                            <button onclick="openEditPageModal()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                                <i class="fas fa-edit mr-2"></i>Editar Página
                            </button>
                            <button onclick="if(confirm('¿Estás seguro de que quieres eliminar esta página?')) deletePage(<?php echo $page_id; ?>)" 
                                    class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                                <i class="fas fa-trash mr-2"></i>Eliminar Página
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Información Adicional -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold mb-4">Acerca de la Página</h3>
                    <div class="space-y-3 text-sm text-gray-600">
                        <p><i class="fas fa-calendar mr-2"></i>Creada el <?php echo date('d/m/Y', strtotime($page['created_at'])); ?></p>
                        <p><i class="fas fa-user mr-2"></i>Administrada por <?php echo $page['first_name'] . ' ' . $page['last_name']; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Funciones para páginas
        function followPage(pageId) {
            fetch('follow_page.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ page_id: pageId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error al seguir la página: ' + data.error);
                }
            });
        }

        function unfollowPage(pageId) {
            if (confirm('¿Estás seguro de que quieres dejar de seguir esta página?')) {
                fetch('unfollow_page.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ page_id: pageId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error al dejar de seguir la página: ' + data.error);
                    }
                });
            }
        }

        function deletePage(pageId) {
            fetch('delete_page.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ page_id: pageId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'pages.php';
                } else {
                    alert('Error al eliminar la página: ' + data.error);
                }
            });
        }

        // Funciones para posts
        function togglePagePostLike(postId) {
            fetch('toggle_page_like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ page_post_id: postId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const likesCount = document.getElementById(`likes-count-${postId}`);
                    likesCount.textContent = data.likes_count;
                    const likeButton = likesCount.parentElement;
                    if (data.has_liked) {
                        likeButton.classList.add('text-red-500');
                    } else {
                        likeButton.classList.remove('text-red-500');
                    }
                }
            });
        }

        function togglePagePostComments(postId) {
            const commentsSection = document.getElementById(`comments-section-${postId}`);
            if (commentsSection.classList.contains('hidden')) {
                commentsSection.classList.remove('hidden');
                loadPagePostComments(postId);
            } else {
                commentsSection.classList.add('hidden');
            }
        }

        function loadPagePostComments(postId) {
            fetch(`get_page_comments.php?post_id=${postId}`)
            .then(response => response.json())
            .then(data => {
                const commentsList = document.getElementById(`comments-list-${postId}`);
                commentsList.innerHTML = data.comments.map(comment => `
                    <div class="flex items-start space-x-3">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold text-xs">
                            ${comment.first_name.charAt(0)}${comment.last_name.charAt(0)}
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-900">${comment.first_name} ${comment.last_name}</p>
                            <p class="text-gray-700">${comment.content}</p>
                            <p class="text-xs text-gray-500">${new Date(comment.created_at).toLocaleString()}</p>
                        </div>
                    </div>
                `).join('');
            });
        }

        function addPagePostComment(event, postId) {
            event.preventDefault();
            
            const form = event.target;
            const textarea = form.querySelector('textarea');
            const content = textarea.value.trim();
            
            if (!content) return;
            
            fetch('add_page_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    page_post_id: postId, 
                    content: content 
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    textarea.value = '';
                    loadPagePostComments(postId);
                    // Actualizar contador de comentarios
                    const commentsCount = document.getElementById(`comments-count-${postId}`);
                    commentsCount.textContent = parseInt(commentsCount.textContent) + 1;
                }
            });
        }

        function deletePagePost(postId) {
            if (confirm('¿Estás seguro de que quieres eliminar esta publicación?')) {
                fetch('delete_page_post.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ page_post_id: postId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error al eliminar la publicación: ' + data.error);
                    }
                });
            }
        }
    </script>
</body>
</html> 