<?php
require_once '../includes/functions.php';
requireLogin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: groups.php');
    exit();
}

$group_id = $_GET['id'];

$database = new Database();
$db = $database->getConnection();

// Obtener información del grupo
$query = "SELECT g.*, u.first_name, u.last_name, u.username,
          (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as members_count,
          (SELECT COUNT(*) FROM group_posts WHERE group_id = g.id) as posts_count
          FROM groups g 
          JOIN users u ON g.creator_id = u.id
          WHERE g.id = ? AND g.is_active = 1";
$stmt = $db->prepare($query);
$stmt->execute([$group_id]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {
    header('Location: groups.php');
    exit();
}

// Verificar si es miembro y obtener rol
$user_role = getUserGroupRole($_SESSION['user_id'], $group_id);
$is_member = $user_role !== null;

// Si es grupo privado y no es miembro, no puede ver
if ($group['privacy'] == 'private' && !$is_member) {
    header('Location: groups.php');
    exit();
}

// Obtener posts del grupo
$query = "SELECT gp.*, u.username, u.first_name, u.last_name 
          FROM group_posts gp 
          JOIN users u ON gp.user_id = u.id 
          WHERE gp.group_id = ? 
          ORDER BY gp.created_at DESC 
          LIMIT 20";
$stmt = $db->prepare($query);
$stmt->execute([$group_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener miembros del grupo
$query = "SELECT gm.*, u.username, u.first_name, u.last_name, u.profile_picture 
          FROM group_members gm 
          JOIN users u ON gm.user_id = u.id 
          WHERE gm.group_id = ? 
          ORDER BY 
          CASE gm.role 
            WHEN 'admin' THEN 1 
            WHEN 'moderator' THEN 2 
            ELSE 3 
          END, gm.joined_at ASC";
$stmt = $db->prepare($query);
$stmt->execute([$group_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener número de mensajes no leídos
$unread_messages = getUnreadMessagesCount($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($group['name']); ?> - Red Social</title>
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
                        <a href="index.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300">
                            <i class="fas fa-home mr-1"></i>Inicio
                        </a>
                        <a href="groups.php" class="py-4 px-2 text-indigo-500 border-b-4 border-indigo-500 font-semibold">
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

    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Header del Grupo -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <!-- Cover Image -->
            <div class="h-48 bg-gradient-to-r from-blue-500 to-purple-600 relative">
                <div class="absolute inset-0 bg-black bg-opacity-30"></div>
                <div class="absolute bottom-4 left-4">
                    <span class="bg-white bg-opacity-90 text-sm px-3 py-1 rounded-full font-medium">
                        <i class="fas fa-<?php echo $group['privacy'] == 'private' ? 'lock' : 'globe'; ?> mr-1"></i>
                        Grupo <?php echo ucfirst($group['privacy']); ?>
                    </span>
                </div>
                <div class="absolute top-4 right-4">
                    <a href="groups.php" class="bg-white bg-opacity-90 hover:bg-white text-gray-800 px-3 py-1 rounded-full text-sm font-medium transition duration-300">
                        <i class="fas fa-arrow-left mr-1"></i>Volver a Grupos
                    </a>
                </div>
            </div>
            
            <div class="p-6">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <h1 class="text-2xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($group['name']); ?></h1>
                        <p class="text-gray-700 mb-3"><?php echo htmlspecialchars($group['description']); ?></p>
                        
                        <!-- Información del creador -->
                        <div class="flex items-center mb-4">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold text-sm mr-3">
                                <?php echo strtoupper(substr($group['first_name'], 0, 1) . substr($group['last_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">
                                    Creado por <span class="font-medium"><?php echo $group['first_name'] . ' ' . $group['last_name']; ?></span>
                                </p>
                                <p class="text-xs text-gray-500">@<?php echo $group['username']; ?> • <?php echo date('d M Y', strtotime($group['created_at'])); ?></p>
                            </div>
                        </div>
                        
                        <!-- Estadísticas -->
                        <div class="flex space-x-6 text-sm text-gray-600">
                            <div class="flex items-center">
                                <i class="fas fa-users mr-1"></i>
                                <span class="font-medium"><?php echo $group['members_count']; ?></span> miembros
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-edit mr-1"></i>
                                <span class="font-medium"><?php echo $group['posts_count']; ?></span> posts
                            </div>
                        </div>
                    </div>
                    
                    <div class="ml-4">
                        <?php if (!$is_member && $group['privacy'] == 'public'): ?>
                            <button onclick="joinGroup(<?php echo $group['id']; ?>)" 
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                                <i class="fas fa-plus mr-2"></i>Unirse al Grupo
                            </button>
                        <?php elseif ($is_member && $user_role != 'admin'): ?>
                            <button onclick="leaveGroup(<?php echo $group['id']; ?>)" 
                                    class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                                <i class="fas fa-sign-out-alt mr-2"></i>Salir del Grupo
                            </button>
                        <?php elseif ($user_role == 'admin'): ?>
                            <span class="bg-green-600 text-white font-bold py-2 px-4 rounded-lg">
                                <i class="fas fa-crown mr-2"></i>Administrador
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Columna Principal - Posts -->
            <div class="lg:col-span-2 space-y-6">
                <?php if ($is_member): ?>
                    <!-- Crear Post en Grupo -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-4">Compartir en el grupo</h3>
                        <form action="create_group_post.php" method="POST" class="space-y-4">
                            <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                            <textarea name="content" placeholder="Comparte algo con el grupo..." 
                                      class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none" 
                                      rows="3" required></textarea>
                            <div class="flex justify-end">
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                                    <i class="fas fa-paper-plane mr-2"></i>Publicar
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- Posts del Grupo -->
                <?php if (empty($posts)): ?>
                    <div class="bg-white rounded-lg shadow-md p-8 text-center">
                        <i class="fas fa-comments text-gray-400 text-4xl mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-600 mb-2">No hay posts aún</h3>
                        <?php if ($is_member): ?>
                            <p class="text-gray-500">¡Sé el primero en compartir algo!</p>
                        <?php else: ?>
                            <p class="text-gray-500">Únete al grupo para ver y crear contenido</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <!-- Header del Post -->
                            <div class="flex items-center mb-4">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold text-sm">
                                    <?php echo strtoupper(substr($post['first_name'], 0, 1) . substr($post['last_name'], 0, 1)); ?>
                                </div>
                                <div class="ml-3">
                                    <h4 class="font-semibold text-gray-900"><?php echo $post['first_name'] . ' ' . $post['last_name']; ?></h4>
                                    <p class="text-sm text-gray-500">@<?php echo $post['username']; ?> • <?php echo timeAgo($post['created_at']); ?></p>
                                </div>
                            </div>

                            <!-- Contenido del Post -->
                            <div class="mb-4">
                                <p class="text-gray-800 leading-relaxed"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            </div>

                            <!-- Acciones del Post -->
                            <?php if ($is_member): ?>
                                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                                    <div class="flex space-x-6">
                                        <button onclick="toggleGroupLike(<?php echo $post['id']; ?>)" 
                                                class="flex items-center space-x-2 text-gray-500 hover:text-red-500 transition duration-300"
                                                id="group-like-btn-<?php echo $post['id']; ?>">
                                            <i class="<?php echo hasUserLikedGroupPost($_SESSION['user_id'], $post['id']) ? 'fas text-red-500' : 'far'; ?> fa-heart"></i>
                                            <span id="group-like-count-<?php echo $post['id']; ?>"><?php echo getGroupPostLikesCount($post['id']); ?></span>
                                        </button>
                                        
                                        <button onclick="toggleGroupComments(<?php echo $post['id']; ?>)" 
                                                class="flex items-center space-x-2 text-gray-500 hover:text-blue-500 transition duration-300">
                                            <i class="far fa-comment"></i>
                                            <span><?php echo getGroupPostCommentsCount($post['id']); ?></span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Sección de Comentarios -->
                                <div id="group-comments-<?php echo $post['id']; ?>" class="hidden mt-4 border-t border-gray-200 pt-4">
                                    <!-- Formulario para nuevo comentario -->
                                    <form onsubmit="addGroupComment(event, <?php echo $post['id']; ?>)" class="mb-4">
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
                                    <div id="group-comments-list-<?php echo $post['id']; ?>">
                                        <!-- Los comentarios se cargarán dinámicamente -->
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="pt-4 border-t border-gray-200 text-center text-gray-500">
                                    <p class="text-sm">Únete al grupo para interactuar con los posts</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Sidebar - Miembros -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        Miembros (<?php echo count($members); ?>)
                    </h3>
                    
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        <?php foreach ($members as $member): ?>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-400 to-purple-400 flex items-center justify-center text-white font-bold text-xs mr-3">
                                        <?php echo strtoupper(substr($member['first_name'], 0, 1) . substr($member['last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-900 text-sm"><?php echo $member['first_name'] . ' ' . $member['last_name']; ?></h4>
                                        <p class="text-xs text-gray-600">@<?php echo $member['username']; ?></p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    <?php if ($member['role'] == 'admin'): ?>
                                        <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full font-medium">
                                            <i class="fas fa-crown mr-1"></i>Admin
                                        </span>
                                    <?php elseif ($member['role'] == 'moderator'): ?>
                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full font-medium">
                                            <i class="fas fa-shield-alt mr-1"></i>Mod
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($member['user_id'] != $_SESSION['user_id']): ?>
                                        <a href="user_profile.php?id=<?php echo $member['user_id']; ?>" 
                                           class="text-gray-500 hover:text-indigo-500 transition duration-300">
                                            <i class="fas fa-eye text-xs"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Funciones para grupos
        function joinGroup(groupId) {
            fetch('join_group.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ group_id: groupId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error al unirse al grupo: ' + data.error);
                }
            });
        }

        function leaveGroup(groupId) {
            if (confirm('¿Estás seguro de que quieres salir de este grupo?')) {
                fetch('leave_group.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ group_id: groupId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'groups.php';
                    } else {
                        alert('Error al salir del grupo: ' + data.error);
                    }
                });
            }
        }

        // Funciones para posts de grupos
        function toggleGroupLike(postId) {
            fetch('toggle_group_like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ group_post_id: postId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const likeBtn = document.getElementById(`group-like-btn-${postId}`);
                    const likeCount = document.getElementById(`group-like-count-${postId}`);
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

        function toggleGroupComments(postId) {
            const commentsDiv = document.getElementById(`group-comments-${postId}`);
            
            if (commentsDiv.classList.contains('hidden')) {
                commentsDiv.classList.remove('hidden');
                loadGroupComments(postId);
            } else {
                commentsDiv.classList.add('hidden');
            }
        }

        function loadGroupComments(postId) {
            fetch(`get_group_comments.php?group_post_id=${postId}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById(`group-comments-list-${postId}`).innerHTML = data;
            });
        }

        function addGroupComment(event, postId) {
            event.preventDefault();
            
            const form = event.target;
            const textarea = form.querySelector('textarea');
            const content = textarea.value.trim();
            
            if (!content) return;
            
            fetch('add_group_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    group_post_id: postId, 
                    content: content 
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    textarea.value = '';
                    loadGroupComments(postId);
                }
            });
        }
    </script>
</body>
</html> 