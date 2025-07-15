<?php
require_once '../includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Obtener información del usuario actual incluyendo el avatar
$query = "SELECT u.*, COALESCE(u.avatar_url, '') as avatar_url FROM users u WHERE u.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener permisos del usuario y verificar membresía
$user_permissions = getUserPermissions($_SESSION['user_id']);
$unread_messages = 0;

// Solo obtener mensajes no leídos si el usuario tiene acceso a mensajes
if ($user_permissions['can_access_messages']) {
    $unread_messages = getUnreadMessagesCount($_SESSION['user_id']);
}

// Obtener posts con información del usuario
$query = "SELECT p.*, u.username, u.first_name, u.last_name, u.avatar_url,
          CASE WHEN EXISTS (
              SELECT 1 FROM post_media pm WHERE pm.post_id = p.id
          ) THEN 1 ELSE 0 END as has_media 
          FROM posts p 
          JOIN users u ON p.user_id = u.id 
          ORDER BY p.created_at DESC 
          LIMIT 20";
$stmt = $db->prepare($query);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener medios para cada post que los tenga
foreach ($posts as &$post) {
    $post['has_media'] = (bool)$post['has_media'];
    if ($post['has_media']) {
        $post['media'] = getPostMedia($post['id']);
    } else {
        $post['media'] = [];
    }
}

// Obtener las 5 publicaciones más populares para tendencias
$trending_query = "SELECT p.*, u.username, u.first_name, u.last_name, u.avatar_url,
                    COUNT(pl.id) as likes_count
                  FROM posts p 
                  JOIN users u ON p.user_id = u.id
                  LEFT JOIN post_likes pl ON p.id = pl.post_id
                  GROUP BY p.id, u.id
                  ORDER BY likes_count DESC, p.created_at DESC
                  LIMIT 5";
$stmt = $db->prepare($trending_query);
$stmt->execute();
$trending_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
<body class="bg-gray-50">
    <?php include '../includes/navbar.php'; ?>

    <div class="max-w-screen-xl mx-auto px-4 py-4 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Columna Izquierda - Perfil y Estadísticas -->
            <div class="lg:w-1/4 space-y-4">
                <!-- Tarjeta de Perfil -->
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <div class="flex items-center space-x-3">
                        <?php if ($current_user['avatar_url']): ?>
                            <img src="<?php echo htmlspecialchars($current_user['avatar_url']); ?>" 
                                 alt="Tu avatar" 
                                 class="w-12 h-12 rounded-full object-cover">
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold">
                                <?php echo strtoupper(substr($current_user['first_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h2 class="font-semibold text-gray-800"><?php echo $current_user['first_name'] . ' ' . $current_user['last_name']; ?></h2>
                            <p class="text-sm text-gray-500">@<?php echo $current_user['username']; ?></p>
                        </div>
                    </div>
                    <a href="profile.php" class="mt-3 block text-sm text-blue-600 hover:text-blue-700 font-medium">
                        <i class="fas fa-user-edit mr-1"></i>Editar perfil
                    </a>
                </div>

                <!-- Banner de Membresía -->
                <?php if ($user_permissions['membership_type'] === 'basico'): ?>
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl shadow-sm p-4 text-white">
                    <div class="flex items-center space-x-3 mb-3">
                        <i class="fas fa-crown text-yellow-300 text-2xl"></i>
                        <h3 class="font-semibold">Mejora tu experiencia</h3>
                    </div>
                    <p class="text-sm text-blue-100 mb-3">Desbloquea todas las funciones con una membresía premium</p>
                    <a href="memberships.php" class="inline-block w-full bg-white text-blue-600 text-center text-sm font-medium py-2 px-4 rounded-lg hover:bg-blue-50 transition-colors duration-200">
                        Ver planes
                    </a>
                </div>
                <?php endif; ?>

                <!-- Enlaces Rápidos -->
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <h3 class="font-semibold text-gray-800 mb-3">Enlaces Rápidos</h3>
                    <div class="space-y-2">
                        <?php if ($user_permissions['can_access_groups']): ?>
                        <a href="groups.php" class="flex items-center text-gray-700 hover:text-blue-600 transition-colors duration-200">
                            <i class="fas fa-users w-5"></i>
                            <span>Grupos</span>
                        </a>
                        <?php else: ?>
                        <a href="memberships.php" class="flex items-center text-gray-700 hover:text-blue-600 transition-colors duration-200">
                            <i class="fas fa-users w-5"></i>
                            <span>Grupos</span>
                            <span class="ml-auto text-xs text-blue-600">
                                <i class="fas fa-crown"></i> Premium
                            </span>
                        </a>
                        <?php endif; ?>

                        <?php if ($user_permissions['can_access_pages']): ?>
                        <a href="pages.php" class="flex items-center text-gray-700 hover:text-blue-600 transition-colors duration-200">
                            <i class="fas fa-flag w-5"></i>
                            <span>Páginas</span>
                        </a>
                        <?php else: ?>
                        <a href="memberships.php" class="flex items-center text-gray-700 hover:text-blue-600 transition-colors duration-200">
                            <i class="fas fa-flag w-5"></i>
                            <span>Páginas</span>
                            <span class="ml-auto text-xs text-blue-600">
                                <i class="fas fa-crown"></i> VIP
                            </span>
                        </a>
                        <?php endif; ?>

                        <?php if ($user_permissions['can_access_messages']): ?>
                        <a href="messages.php" class="flex items-center text-gray-700 hover:text-blue-600 transition-colors duration-200">
                            <i class="fas fa-envelope w-5"></i>
                            <span>Mensajes</span>
                            <?php if ($unread_messages > 0): ?>
                                <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                                    <?php echo $unread_messages; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <?php else: ?>
                        <a href="memberships.php" class="flex items-center text-gray-700 hover:text-blue-600 transition-colors duration-200">
                            <i class="fas fa-envelope w-5"></i>
                            <span>Mensajes</span>
                            <span class="ml-auto text-xs text-blue-600">
                                <i class="fas fa-crown"></i> Premium
                            </span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Columna Central - Feed Principal -->
            <div class="lg:flex-1">
                <!-- Notificaciones -->
                <?php if (isset($_SESSION['success']) || isset($_SESSION['warning']) || isset($_SESSION['error'])): ?>
                    <div class="space-y-2 mb-4">
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-check-circle text-green-500"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-green-700"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['warning'])): ?>
                            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-r-lg">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-700"><?php echo $_SESSION['warning']; unset($_SESSION['warning']); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-times-circle text-red-500"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-red-700"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Crear Post -->
                <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
                    <form action="create_post.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <div class="flex items-start space-x-3">
                            <?php if ($current_user['avatar_url']): ?>
                                <img src="<?php echo htmlspecialchars($current_user['avatar_url']); ?>" 
                                     alt="Tu avatar" 
                                     class="w-10 h-10 rounded-full object-cover">
                            <?php else: ?>
                                <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold">
                                    <?php echo strtoupper(substr($current_user['first_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <textarea name="content" 
                                      placeholder="¿Qué estás pensando?" 
                                      class="flex-1 resize-none border-0 bg-transparent focus:ring-0 text-gray-700 placeholder-gray-400 text-sm"
                                      rows="2"></textarea>
                        </div>

                        <!-- Área de archivos multimedia -->
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="text-center">
                                <i class="fas fa-cloud-upload-alt text-gray-400 text-xl mb-2"></i>
                                <p class="text-sm text-gray-600">Arrastra archivos aquí o haz clic para seleccionar</p>
                                <p class="text-xs text-gray-500 mt-1">Imágenes y videos hasta 10MB</p>
                            </div>
                            <input type="file" name="media[]" multiple accept="image/*,video/*" 
                                   id="media-input" class="hidden" onchange="previewFiles(this)">
                        </div>

                        <!-- Vista previa de archivos -->
                        <div id="media-preview" class="grid grid-cols-2 sm:grid-cols-3 gap-2 hidden"></div>

                        <!-- Botones de acción -->
                        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                            <button type="button" onclick="clearFiles()" id="clear-btn" 
                                    class="text-red-500 hover:text-red-600 text-sm hidden">
                                <i class="fas fa-times mr-1"></i>Limpiar
                            </button>
                            <button type="submit" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                                <i class="fas fa-paper-plane mr-2"></i>Publicar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Posts Timeline -->
                <div class="space-y-4">
                    <?php foreach ($posts as $post): ?>
                        <div class="bg-white rounded-xl shadow-sm" data-post-id="<?php echo $post['id']; ?>">
                            <!-- Header del Post -->
                            <div class="p-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <?php if ($post['avatar_url']): ?>
                                            <img src="<?php echo htmlspecialchars($post['avatar_url']); ?>" 
                                                 alt="Avatar de <?php echo htmlspecialchars($post['first_name']); ?>"
                                                 class="w-10 h-10 rounded-full object-cover">
                                        <?php else: ?>
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold text-sm">
                                                <?php echo strtoupper(substr($post['first_name'], 0, 1) . substr($post['last_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <h4 class="font-semibold text-gray-900"><?php echo $post['first_name'] . ' ' . $post['last_name']; ?></h4>
                                        <p class="text-sm text-gray-500">@<?php echo $post['username']; ?> • <?php echo timeAgo($post['created_at']); ?></p>
                                    </div>
                                    <div class="flex space-x-2">
                                        <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                                            <button onclick="deletePost(<?php echo $post['id']; ?>)" 
                                                    class="text-gray-400 hover:text-red-500 transition-colors duration-200" 
                                                    title="Eliminar publicación">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php elseif (isFollowing($_SESSION['user_id'], $post['user_id'])): ?>
                                            <a href="messages.php?user=<?php echo $post['user_id']; ?>" 
                                               class="text-gray-400 hover:text-blue-500 transition-colors duration-200" 
                                               title="Enviar mensaje">
                                                <i class="fas fa-envelope"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Contenido del Post -->
                                <?php if (!empty($post['content'])): ?>
                                    <p class="text-gray-800 mt-3"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                <?php endif; ?>
                            </div>

                            <!-- Medios del Post -->
                            <?php if (!empty($post['media'])): ?>
                                <div class="media-grid border-t border-gray-100">
                                    <?php 
                                    $media_count = count($post['media']);
                                    $grid_class = '';
                                    if ($media_count == 1) {
                                        $grid_class = 'grid-cols-1';
                                    } elseif ($media_count == 2) {
                                        $grid_class = 'grid-cols-2';
                                    } elseif ($media_count >= 3) {
                                        $grid_class = 'grid-cols-2';
                                    }
                                    ?>
                                    <div class="grid <?php echo $grid_class; ?> gap-px">
                                        <?php 
                                        $display_count = min($media_count, 4);
                                        for ($i = 0; $i < $display_count; $i++): 
                                            $media = $post['media'][$i];
                                            $remaining = $media_count - 4;
                                        ?>
                                            <div class="relative <?php echo ($media_count == 3 && $i == 0) ? 'row-span-2' : ''; ?> group cursor-pointer" 
                                                 onclick="openMediaModal(<?php echo $post['id']; ?>, <?php echo $i; ?>)">
                                                
                                                <?php if ($media['file_type'] == 'image'): ?>
                                                    <img src="<?php echo htmlspecialchars($media['file_path']); ?>" 
                                                         alt="Imagen de la publicación" 
                                                         class="w-full h-full object-cover <?php echo ($media_count == 1) ? 'max-h-96' : 'h-48'; ?>">
                                                <?php elseif ($media['file_type'] == 'video'): ?>
                                                    <div class="relative">
                                                        <video class="w-full h-full object-cover <?php echo ($media_count == 1) ? 'max-h-96' : 'h-48'; ?>" 
                                                               preload="metadata">
                                                            <source src="<?php echo htmlspecialchars($media['file_path']); ?>" 
                                                                    type="<?php echo htmlspecialchars($media['mime_type']); ?>">
                                                        </video>
                                                        <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-30">
                                                            <i class="fas fa-play text-white text-2xl"></i>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($i == 3 && $remaining > 0): ?>
                                                    <div class="absolute inset-0 bg-black bg-opacity-60 flex items-center justify-center">
                                                        <span class="text-white text-xl font-bold">+<?php echo $remaining; ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Acciones del Post -->
                            <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
                                <div class="flex space-x-6">
                                    <button onclick="toggleLike(<?php echo $post['id']; ?>)" 
                                            class="flex items-center space-x-2 text-gray-500 hover:text-red-500 transition-colors duration-200"
                                            id="like-btn-<?php echo $post['id']; ?>">
                                        <i class="<?php echo hasUserLikedPost($_SESSION['user_id'], $post['id']) ? 'fas text-red-500' : 'far'; ?> fa-heart"></i>
                                        <span id="like-count-<?php echo $post['id']; ?>" class="text-sm">
                                            <?php echo getPostLikesCount($post['id']); ?>
                                        </span>
                                    </button>
                                    
                                    <button onclick="toggleComments(<?php echo $post['id']; ?>)" 
                                            class="flex items-center space-x-2 text-gray-500 hover:text-blue-500 transition-colors duration-200">
                                        <i class="far fa-comment"></i>
                                        <span class="text-sm"><?php echo getPostCommentsCount($post['id']); ?></span>
                                    </button>
                                </div>
                            </div>

                            <!-- Sección de Comentarios -->
                            <div id="comments-<?php echo $post['id']; ?>" class="hidden border-t border-gray-100">
                                <div class="p-4">
                                    <form onsubmit="addComment(event, <?php echo $post['id']; ?>)" class="flex items-start space-x-2">
                                        <?php if ($current_user['avatar_url']): ?>
                                            <img src="<?php echo htmlspecialchars($current_user['avatar_url']); ?>" 
                                                 alt="Tu avatar" 
                                                 class="w-8 h-8 rounded-full object-cover">
                                        <?php else: ?>
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold text-sm">
                                                <?php echo strtoupper(substr($current_user['first_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex-1 min-w-0">
                                            <textarea placeholder="Escribe un comentario..." 
                                                      class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 resize-none text-sm"
                                                      rows="1" required></textarea>
                                        </div>
                                        <button type="submit" 
                                                class="bg-blue-600 hover:bg-blue-700 text-white p-2 rounded-lg transition-colors duration-200">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </form>
                                    
                                    <div id="comments-list-<?php echo $post['id']; ?>" class="mt-4 space-y-3">
                                        <!-- Los comentarios se cargarán dinámicamente -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($posts)): ?>
                        <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                            <i class="fas fa-comments text-gray-300 text-4xl mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay publicaciones aún</h3>
                            <p class="text-gray-500">¡Sé el primero en compartir algo!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Columna Derecha - Sugerencias y Tendencias -->
            <div class="lg:w-1/4 space-y-4">
                <!-- Sugerencias de Usuarios -->
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <h3 class="font-semibold text-gray-800 mb-4">Sugerencias para ti</h3>
                    <!-- Aquí puedes añadir una lista de usuarios sugeridos -->
                    <p class="text-sm text-gray-500 text-center">
                        <i class="fas fa-users text-gray-400"></i>
                        Próximamente...
                    </p>
                </div>

                <!-- Tendencias -->
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <h3 class="font-semibold text-gray-800 mb-4">
                        <i class="fas fa-fire text-orange-500 mr-2"></i>Tendencias
                    </h3>
                    <?php if (!empty($trending_posts)): ?>
                        <div class="space-y-4">
                            <?php foreach ($trending_posts as $post): ?>
                                <div class="group">
                                    <a href="#post-<?php echo $post['id']; ?>" class="block hover:bg-gray-50 rounded-lg p-2 transition-colors duration-200">
                                        <div class="flex items-center space-x-3">
                                            <!-- Avatar del autor -->
                                            <div class="flex-shrink-0">
                                                <?php if ($post['avatar_url']): ?>
                                                    <img src="<?php echo htmlspecialchars($post['avatar_url']); ?>" 
                                                         alt="Avatar de <?php echo htmlspecialchars($post['first_name']); ?>"
                                                         class="w-8 h-8 rounded-full object-cover">
                                                <?php else: ?>
                                                    <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold text-xs">
                                                        <?php echo strtoupper(substr($post['first_name'], 0, 1) . substr($post['last_name'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Contenido -->
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">
                                                    <?php echo $post['first_name'] . ' ' . $post['last_name']; ?>
                                                </p>
                                                <p class="text-xs text-gray-500 truncate">
                                                    <?php 
                                                    $content = strip_tags($post['content']);
                                                    echo strlen($content) > 50 ? substr($content, 0, 50) . '...' : $content;
                                                    ?>
                                                </p>
                                            </div>

                                            <!-- Contador de likes -->
                                            <div class="flex items-center text-sm text-gray-500">
                                                <i class="fas fa-heart text-red-500 mr-1"></i>
                                                <span><?php echo $post['likes_count']; ?></span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-500 text-center">
                            <i class="fas fa-chart-line text-gray-400"></i>
                            No hay publicaciones destacadas aún
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para visualizar medios -->
    <div id="media-modal" class="fixed inset-0 bg-black bg-opacity-90 hidden z-50 flex items-center justify-center">
        <div class="relative max-w-4xl max-h-full p-4 w-full">
            <!-- Botón cerrar -->
            <button onclick="closeMediaModal()" 
                    class="absolute top-4 right-4 text-white hover:text-gray-300 text-2xl z-10">
                <i class="fas fa-times"></i>
            </button>
            
            <!-- Navegación anterior -->
            <button id="prev-media" onclick="navigateMedia(-1)" 
                    class="absolute left-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 text-2xl z-10 hidden">
                <i class="fas fa-chevron-left"></i>
            </button>
            
            <!-- Navegación siguiente -->
            <button id="next-media" onclick="navigateMedia(1)" 
                    class="absolute right-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 text-2xl z-10 hidden">
                <i class="fas fa-chevron-right"></i>
            </button>
            
            <!-- Contenedor del medio -->
            <div id="modal-media-container" class="flex items-center justify-center h-full">
                <!-- El contenido se carga dinámicamente -->
            </div>
            
            <!-- Información del medio -->
            <div id="media-info" class="absolute bottom-4 left-4 right-4 text-white text-center">
                <p id="media-counter" class="text-sm opacity-75"></p>
            </div>
        </div>
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
                                location.reload(); // Recargar para mostrar el mensaje "No hay posts aún"
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

        // ===== FUNCIONES PARA MANEJO DE ARCHIVOS MULTIMEDIA =====
        
        // Función para previsualizar archivos seleccionados
        function previewFiles(input) {
            const files = input.files;
            const preview = document.getElementById('media-preview');
            const clearBtn = document.getElementById('clear-btn');
            
            if (files.length === 0) {
                preview.classList.add('hidden');
                clearBtn.classList.add('hidden');
                return;
            }
            
            preview.innerHTML = '';
            preview.classList.remove('hidden');
            clearBtn.classList.remove('hidden');
            
            // Validar número de archivos
            if (files.length > 10) {
                alert('Máximo 10 archivos permitidos');
                input.value = '';
                preview.classList.add('hidden');
                clearBtn.classList.add('hidden');
                return;
            }
            
            Array.from(files).forEach((file, index) => {
                const fileDiv = document.createElement('div');
                fileDiv.className = 'relative bg-gray-100 rounded-lg overflow-hidden';
                
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 z-10';
                removeBtn.innerHTML = '×';
                removeBtn.onclick = () => removeFile(index);
                
                if (file.type.startsWith('image/')) {
                    const img = document.createElement('img');
                    img.className = 'w-full h-24 object-cover';
                    img.src = URL.createObjectURL(file);
                    img.onload = () => URL.revokeObjectURL(img.src);
                    
                    fileDiv.appendChild(img);
                } else if (file.type.startsWith('video/')) {
                    const video = document.createElement('video');
                    video.className = 'w-full h-24 object-cover';
                    video.src = URL.createObjectURL(file);
                    video.controls = false;
                    video.muted = true;
                    
                    const playIcon = document.createElement('div');
                    playIcon.className = 'absolute inset-0 flex items-center justify-center bg-black bg-opacity-50';
                    playIcon.innerHTML = '<i class="fas fa-play text-white text-xl"></i>';
                    
                    fileDiv.appendChild(video);
                    fileDiv.appendChild(playIcon);
                }
                
                const fileName = document.createElement('div');
                fileName.className = 'absolute bottom-0 left-0 right-0 bg-black bg-opacity-75 text-white text-xs p-1 truncate';
                fileName.textContent = file.name;
                
                fileDiv.appendChild(removeBtn);
                fileDiv.appendChild(fileName);
                preview.appendChild(fileDiv);
            });
        }
        
        // Función para remover un archivo específico
        function removeFile(index) {
            const input = document.getElementById('media-input');
            const dt = new DataTransfer();
            
            Array.from(input.files).forEach((file, i) => {
                if (i !== index) {
                    dt.items.add(file);
                }
            });
            
            input.files = dt.files;
            previewFiles(input);
        }
        
        // Función para limpiar todos los archivos
        function clearFiles() {
            const input = document.getElementById('media-input');
            const preview = document.getElementById('media-preview');
            const clearBtn = document.getElementById('clear-btn');
            
            input.value = '';
            preview.innerHTML = '';
            preview.classList.add('hidden');
            clearBtn.classList.add('hidden');
        }
        
        // Configurar drag & drop
        document.addEventListener('DOMContentLoaded', function() {
            const dropZone = document.querySelector('.border-dashed');
            const fileInput = document.getElementById('media-input');
            
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight(e) {
                dropZone.classList.add('border-indigo-500', 'bg-indigo-50');
            }
            
            function unhighlight(e) {
                dropZone.classList.remove('border-indigo-500', 'bg-indigo-50');
            }
            
            dropZone.addEventListener('drop', handleDrop, false);
            
            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                
                                 fileInput.files = files;
                 previewFiles(fileInput);
             }
         });

        // ===== FUNCIONES PARA MODAL DE MEDIOS =====
        
        let currentPostMedia = [];
        let currentMediaIndex = 0;
        
        // Función para abrir modal de medios
        function openMediaModal(postId, mediaIndex) {
            // Encontrar el post y sus medios
            const postElement = document.querySelector(`[data-post-id="${postId}"]`);
            if (!postElement) {
                // Si no encontramos el elemento, obtener los medios via AJAX
                fetch(`get_post_media.php?post_id=${postId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentPostMedia = data.media;
                        currentMediaIndex = mediaIndex;
                        showMediaInModal();
                    }
                });
            } else {
                // Obtener los medios del post actual
                const mediaElements = postElement.querySelectorAll('.media-grid img, .media-grid video');
                currentPostMedia = Array.from(mediaElements).map(el => ({
                    file_path: el.src || el.querySelector('source').src,
                    file_type: el.tagName.toLowerCase() === 'img' ? 'image' : 'video',
                    mime_type: el.type || 'video/mp4'
                }));
                currentMediaIndex = mediaIndex;
                showMediaInModal();
            }
        }
        
        // Función para mostrar medio en modal
        function showMediaInModal() {
            const modal = document.getElementById('media-modal');
            const container = document.getElementById('modal-media-container');
            const counter = document.getElementById('media-counter');
            const prevBtn = document.getElementById('prev-media');
            const nextBtn = document.getElementById('next-media');
            
            modal.classList.remove('hidden');
            
            // Limpiar contenedor
            container.innerHTML = '';
            
            const media = currentPostMedia[currentMediaIndex];
            
            if (media.file_type === 'image') {
                const img = document.createElement('img');
                img.src = media.file_path;
                img.className = 'max-w-full max-h-full object-contain';
                container.appendChild(img);
            } else if (media.file_type === 'video') {
                const video = document.createElement('video');
                video.src = media.file_path;
                video.className = 'max-w-full max-h-full object-contain';
                video.controls = true;
                video.autoplay = true;
                container.appendChild(video);
            }
            
            // Actualizar contador
            counter.textContent = `${currentMediaIndex + 1} de ${currentPostMedia.length}`;
            
            // Mostrar/ocultar botones de navegación
            if (currentPostMedia.length > 1) {
                prevBtn.classList.remove('hidden');
                nextBtn.classList.remove('hidden');
                
                // Habilitar/deshabilitar botones según la posición
                prevBtn.style.opacity = currentMediaIndex > 0 ? '1' : '0.5';
                nextBtn.style.opacity = currentMediaIndex < currentPostMedia.length - 1 ? '1' : '0.5';
            } else {
                prevBtn.classList.add('hidden');
                nextBtn.classList.add('hidden');
            }
        }
        
        // Función para navegar entre medios
        function navigateMedia(direction) {
            const newIndex = currentMediaIndex + direction;
            
            if (newIndex >= 0 && newIndex < currentPostMedia.length) {
                currentMediaIndex = newIndex;
                showMediaInModal();
            }
        }
        
        // Función para cerrar modal
        function closeMediaModal() {
            const modal = document.getElementById('media-modal');
            const container = document.getElementById('modal-media-container');
            
            modal.classList.add('hidden');
            
            // Detener videos si hay alguno reproduciéndose
            const videos = container.querySelectorAll('video');
            videos.forEach(video => {
                video.pause();
                video.currentTime = 0;
            });
            
            // Limpiar variables
            currentPostMedia = [];
            currentMediaIndex = 0;
        }
        
        // Cerrar modal con tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMediaModal();
            } else if (e.key === 'ArrowLeft') {
                navigateMedia(-1);
            } else if (e.key === 'ArrowRight') {
                navigateMedia(1);
            }
        });
        
        // Cerrar modal al hacer click fuera del contenido
        document.getElementById('media-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeMediaModal();
            }
        });
    </script>
</body>
</html> 