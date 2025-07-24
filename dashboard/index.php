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

// Obtener las 3 páginas más populares para sugerencias
$popular_pages_query = "SELECT p.id, p.name, p.description,
                              COUNT(pf.id) as followers_count,
                              CASE WHEN EXISTS (
                                  SELECT 1 FROM page_followers 
                                  WHERE page_id = p.id AND user_id = ?
                              ) THEN 1 ELSE 0 END as is_following
                       FROM pages p 
                       LEFT JOIN page_followers pf ON p.id = pf.page_id
                       WHERE p.is_active = 1
                       GROUP BY p.id
                       ORDER BY followers_count DESC, p.created_at DESC
                       LIMIT 3";
$stmt = $db->prepare($popular_pages_query);
$stmt->execute([$_SESSION['user_id']]);
$popular_pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                                     class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                            <?php else: ?>
                                <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold flex-shrink-0">
                                    <?php echo strtoupper(substr($current_user['first_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <textarea name="content" 
                                      placeholder="¿Qué estás pensando?" 
                                      class="w-full min-h-[60px] resize-none focus:outline-none text-gray-700 placeholder-gray-400 text-sm bg-transparent"
                                      rows="2"></textarea>
                        </div>

                        <!-- Área de archivos multimedia -->
                        <div class="border-2 border-dashed border-gray-200 rounded-lg p-4 transition-colors duration-200" 
                             id="drop-zone"
                             ondrop="handleDrop(event)"
                             ondragover="handleDragOver(event)"
                             ondragleave="handleDragLeave(event)">
                            <div class="text-center">
                                <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl mb-2"></i>
                                <p class="text-sm text-gray-600 mb-1">Arrastra archivos aquí o</p>
                                <button type="button" 
                                        onclick="document.getElementById('media-input').click()" 
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 mb-2">
                                    <i class="fas fa-plus mr-2"></i>
                                    Seleccionar archivos
                                </button>
                                <p class="text-xs text-gray-500">
                                    Imágenes (JPG, PNG, GIF) y Videos (MP4, WebM) hasta 1GB
                                </p>
                                <p id="file-count-info" class="text-xs text-blue-600 font-medium hidden">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    <span id="file-count">0</span> archivos seleccionados (máximo 10)
                                </p>
                            </div>
                            <input type="file" name="media[]" multiple 
                                   accept="image/jpeg,image/png,image/gif,video/mp4,video/webm"
                                   id="media-input" class="hidden" 
                                   onchange="handleFileSelect(this)">
                            
                            <!-- Barra de Progreso -->
                            <div id="upload-progress-container" class="mt-4 hidden">
                                <div class="flex items-center justify-between mb-1">
                                    <p class="text-sm text-gray-600" id="upload-status">Preparando archivos...</p>
                                    <p class="text-xs text-gray-500" id="upload-details"></p>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div id="upload-progress" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Vista previa de archivos -->
                        <div id="media-preview" class="grid grid-cols-2 sm:grid-cols-3 gap-2 hidden"></div>

                        <!-- Botón para agregar más archivos cuando ya hay archivos seleccionados -->
                        <div id="add-more-files" class="text-center py-3 hidden">
                            <button type="button" 
                                    onclick="addMoreFiles()" 
                                    class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200">
                                <i class="fas fa-plus mr-2"></i>
                                Agregar más archivos
                            </button>
                            <p class="text-xs text-gray-500 mt-2">
                                <span id="remaining-files">0</span> <span id="remaining-text">archivos restantes de 10</span>
                            </p>
                        </div>

                        <!-- Botones de acción -->
                        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                            <button type="button" onclick="clearFiles()" id="clear-btn" 
                                    class="text-red-500 hover:text-red-600 text-sm hidden">
                                <i class="fas fa-times mr-1"></i>Limpiar todo
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
                <!-- Sugerencias de Páginas -->
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800">
                            <i class="fas fa-star text-yellow-400 mr-2"></i>Páginas Populares
                        </h3>
                        <?php if ($user_permissions['can_access_pages']): ?>
                        <a href="pages.php" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                            Ver todas
                        </a>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($popular_pages)): ?>
                        <div class="space-y-3">
                            <?php foreach ($popular_pages as $index => $page): 
                                // Generar un color único para cada página basado en su índice
                                $colors = [
                                    'from-blue-500 to-cyan-400',
                                    'from-purple-500 to-pink-400',
                                    'from-orange-500 to-yellow-400'
                                ];
                                $colorClass = $colors[$index % count($colors)];
                            ?>
                                <div class="group">
                                    <div class="flex items-center p-3 rounded-lg bg-gradient-to-r <?php echo $colorClass; ?> bg-opacity-10 hover:bg-opacity-20 transition-all duration-200">
                                        <!-- Nombre y seguidores -->
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-900">
                                                <?php echo htmlspecialchars($page['name']); ?>
                                            </h4>
                                            <p class="text-xs text-gray-600">
                                                <?php echo number_format($page['followers_count']); ?> seguidores
                                            </p>
                                        </div>

                                        <!-- Botón de acción -->
                                        <?php if ($user_permissions['can_access_pages']): ?>
                                            <?php if (!$page['is_following']): ?>
                                                <button onclick="followPage(<?php echo $page['id']; ?>)" 
                                                        class="text-sm bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-800 px-3 py-1 rounded-full transition-all duration-200 shadow-sm">
                                                    Seguir
                                                </button>
                                            <?php else: ?>
                                                <span class="text-xs bg-white bg-opacity-90 px-3 py-1 rounded-full text-green-600">
                                                    <i class="fas fa-check"></i> Siguiendo
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <a href="memberships.php" 
                                               class="text-xs bg-white bg-opacity-90 hover:bg-opacity-100 px-3 py-1 rounded-full text-blue-600 font-medium shadow-sm">
                                                <i class="fas fa-crown"></i> VIP
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-500 text-center py-2">
                            <i class="fas fa-flag text-gray-400"></i>
                            No hay páginas disponibles
                        </p>
                    <?php endif; ?>
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

    <!-- Modal de Aviso de Archivo Grande -->
    <div id="largeFileModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 backdrop-blur-sm z-50 hidden">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-xl shadow-2xl p-6 max-w-md w-full transform transition-all duration-300">
                <div class="text-center mb-6">
                    <div class="bg-yellow-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2" id="modalTitle">Archivo Grande</h3>
                    <p class="text-gray-600" id="modalMessage"></p>
                </div>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeLargeFileModal(false)" 
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors duration-200 font-medium">
                        Cancelar
                    </button>
                    <button onclick="closeLargeFileModal(true)" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 font-medium">
                        Continuar
                    </button>
                </div>
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

        // Funciones para manejo de archivos
        function handleDragOver(e) {
            e.preventDefault();
            e.stopPropagation();
            document.getElementById('drop-zone').classList.add('border-blue-400', 'bg-blue-50');
        }

        function handleDragLeave(e) {
            e.preventDefault();
            e.stopPropagation();
            document.getElementById('drop-zone').classList.remove('border-blue-400', 'bg-blue-50');
        }

        function handleDrop(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const dropZone = document.getElementById('drop-zone');
            dropZone.classList.remove('border-blue-400', 'bg-blue-50');
            
            const dt = e.dataTransfer;
            const files = dt.files;
            
            handleFiles(files);
        }

        function handleFileSelect(input) {
            handleFiles(input.files);
        }

        // Variables para el manejo de modales y carga
        let modalResolve = null;
        let currentUpload = {
            totalFiles: 0,
            processedFiles: 0,
            totalSize: 0,
            uploadedSize: 0
        };
        
        // Array para mantener los archivos seleccionados
        let selectedFiles = [];

        // Función para mostrar el modal de archivo grande
        function showLargeFileModal(message) {
            return new Promise((resolve) => {
                const modal = document.getElementById('largeFileModal');
                document.getElementById('modalMessage').textContent = message;
                modal.classList.remove('hidden');
                modalResolve = resolve;
            });
        }

        // Función para cerrar el modal de archivo grande
        function closeLargeFileModal(result) {
            const modal = document.getElementById('largeFileModal');
            modal.classList.add('hidden');
            if (modalResolve) {
                modalResolve(result);
                modalResolve = null;
            }
        }

        // Función para mostrar el progreso de carga
        function showUploadProgress() {
            const progressContainer = document.getElementById('upload-progress-container');
            progressContainer.classList.remove('hidden');
        }

        // Función para ocultar el progreso de carga
        function hideUploadProgress() {
            const progressContainer = document.getElementById('upload-progress-container');
            progressContainer.classList.add('hidden');
        }

        // Función para actualizar el progreso
        function updateUploadProgress(loaded, total) {
            const progress = Math.round((loaded / total) * 100);
            const progressBar = document.getElementById('upload-progress');
            const statusText = document.getElementById('upload-status');
            const details = document.getElementById('upload-details');

            progressBar.style.width = `${progress}%`;
            
            const loadedSize = loaded > 1024 * 1024 * 1024 
                ? `${(loaded / (1024 * 1024 * 1024)).toFixed(2)} GB`
                : `${Math.round(loaded / (1024 * 1024))} MB`;
            const totalSize = total > 1024 * 1024 * 1024
                ? `${(total / (1024 * 1024 * 1024)).toFixed(2)} GB`
                : `${Math.round(total / (1024 * 1024))} MB`;

            statusText.textContent = `Subiendo archivos... ${progress}%`;
            details.textContent = `${loadedSize} de ${totalSize}`;

            // Si la carga está completa, ocultar la barra después de un momento
            if (progress === 100) {
                setTimeout(() => {
                    hideUploadProgress();
                }, 1000);
            }
        }

        // Función para validar archivo con modal
        async function validateFile(file) {
            const maxFileSize = 1024 * 1024 * 1024; // 1GB
            const allowedTypes = {
                'image/jpeg': 'imagen JPG',
                'image/png': 'imagen PNG',
                'image/gif': 'imagen GIF',
                'video/mp4': 'video MP4',
                'video/webm': 'video WebM'
            };

            if (!allowedTypes.hasOwnProperty(file.type)) {
                await showLargeFileModal('Solo se permiten imágenes (JPG, PNG, GIF) y videos (MP4, WebM).');
                return false;
            }

            if (file.size > maxFileSize) {
                const sizeInGB = (file.size / (1024 * 1024 * 1024)).toFixed(2);
                await showLargeFileModal(`El archivo "${file.name}" (${sizeInGB}GB) excede el límite permitido de 1GB.`);
                return false;
            }

            if (file.size > (100 * 1024 * 1024)) {
                const sizeInMB = Math.round(file.size / (1024 * 1024));
                const shouldContinue = await showLargeFileModal(
                    `El archivo "${file.name}" es grande (${sizeInMB}MB). La carga podría tardar varios minutos. ¿Deseas continuar?`
                );
                if (!shouldContinue) return false;
            }

            return true;
        }

        // Función para manejar la subida de archivos
        async function handleFiles(files) {
            // Agregar archivos a los ya seleccionados
            for (const file of Array.from(files)) {
                if (selectedFiles.length >= 10) {
                    await showLargeFileModal('Máximo 10 archivos permitidos');
                    break;
                }
                
                // Verificar si el archivo ya existe (por nombre y tamaño)
                const isDuplicate = selectedFiles.some(existingFile => 
                    existingFile.name === file.name && existingFile.size === file.size
                );
                
                if (isDuplicate) {
                    console.log(`Archivo duplicado ignorado: ${file.name}`);
                    continue;
                }
                
                if (await validateFile(file)) {
                    selectedFiles.push(file);
                }
            }
            
            // Verificar tamaño total
            if (selectedFiles.length > 0) {
                const totalSize = selectedFiles.reduce((acc, file) => acc + file.size, 0);
                if (totalSize > (200 * 1024 * 1024)) {
                    const totalSizeInMB = Math.round(totalSize / (1024 * 1024));
                    const shouldContinue = await showLargeFileModal(
                        `Has seleccionado ${selectedFiles.length} archivos con un tamaño total de ${totalSizeInMB}MB. La carga podría tardar varios minutos. ¿Deseas continuar?`
                    );
                    if (!shouldContinue) {
                        clearFiles();
                        return;
                    }
                }
            }
            
            // Actualizar la vista previa
            updatePreview();
            
            // Sincronizar con el input file
            syncInputFile();
        }
        
        // Función para actualizar la vista previa
        function updatePreview() {
            const preview = document.getElementById('media-preview');
            const clearBtn = document.getElementById('clear-btn');
            const dropZone = document.getElementById('drop-zone');
            const fileCountInfo = document.getElementById('file-count-info');
            const fileCount = document.getElementById('file-count');
            const addMoreFiles = document.getElementById('add-more-files');
            const remainingFiles = document.getElementById('remaining-files');
            const remainingText = document.getElementById('remaining-text');
            
            preview.innerHTML = '';
            
            // Actualizar contador de archivos
            fileCount.textContent = selectedFiles.length;
            const remaining = 10 - selectedFiles.length;
            remainingFiles.textContent = remaining;
            
            // Actualizar texto del contador de manera inteligente
            if (remaining === 1) {
                remainingText.textContent = 'archivo restante de 10';
            } else if (remaining === 0) {
                remainingText.textContent = '¡Límite alcanzado!';
            } else {
                remainingText.textContent = 'archivos restantes de 10';
            }
            
            if (selectedFiles.length > 0) {
                preview.classList.remove('hidden');
                clearBtn.classList.remove('hidden');
                dropZone.classList.add('hidden');
                fileCountInfo.classList.remove('hidden');
                
                // Mostrar botón de agregar más archivos si no se ha alcanzado el límite
                if (selectedFiles.length < 10) {
                    addMoreFiles.classList.remove('hidden');
                    const addMoreBtn = addMoreFiles.querySelector('button');
                    addMoreBtn.disabled = false;
                    addMoreBtn.className = 'inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200';
                    addMoreBtn.innerHTML = '<i class="fas fa-plus mr-2"></i>Agregar más archivos';
                } else {
                    addMoreFiles.classList.remove('hidden');
                    const addMoreBtn = addMoreFiles.querySelector('button');
                    addMoreBtn.disabled = true;
                    addMoreBtn.className = 'inline-flex items-center px-4 py-2 bg-gray-300 text-gray-500 text-sm font-medium rounded-lg cursor-not-allowed';
                    addMoreBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Límite alcanzado (10 archivos)';
                }

                // Configurar el progreso total
                const totalSize = selectedFiles.reduce((acc, file) => acc + file.size, 0);
                currentUpload = {
                    totalFiles: selectedFiles.length,
                    processedFiles: 0,
                    totalSize: totalSize,
                    uploadedSize: 0
                };

                showUploadProgress();

                selectedFiles.forEach((file, index) => {
                    const reader = new FileReader();
                    const fileDiv = createFilePreview(file, index);
                    preview.appendChild(fileDiv);

                    reader.onload = (e) => {
                        if (file.type.startsWith('image/')) {
                            const img = fileDiv.querySelector('img');
                            if (img) img.src = e.target.result;
                        } else if (file.type.startsWith('video/')) {
                            const video = fileDiv.querySelector('video');
                            if (video) video.src = e.target.result;
                        }
                        
                        // Actualizar progreso
                        currentUpload.processedFiles++;
                        currentUpload.uploadedSize += file.size;
                        updateUploadProgress(currentUpload.uploadedSize, currentUpload.totalSize);
                    };

                    reader.readAsDataURL(file);
                });
            } else {
                preview.classList.add('hidden');
                clearBtn.classList.add('hidden');
                dropZone.classList.remove('hidden');
                fileCountInfo.classList.add('hidden');
                addMoreFiles.classList.add('hidden');
                hideUploadProgress();
            }
        }
        
        // Función para sincronizar el input file con los archivos seleccionados
        function syncInputFile() {
            const input = document.getElementById('media-input');
            const dt = new DataTransfer();
            
            selectedFiles.forEach(file => {
                dt.items.add(file);
            });
            
            input.files = dt.files;
        }

        function createFilePreview(file, index) {
            const div = document.createElement('div');
            div.className = 'relative bg-gray-100 rounded-lg aspect-square overflow-hidden';
            div.setAttribute('data-file-index', index);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 z-10 shadow-sm';
            removeBtn.innerHTML = '×';
            removeBtn.onclick = () => removeFile(index);

            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.className = 'w-full h-full object-cover';
                div.appendChild(img);
            } else if (file.type.startsWith('video/')) {
                const video = document.createElement('video');
                video.className = 'w-full h-full object-cover';
                video.controls = false;
                
                const playIcon = document.createElement('div');
                playIcon.className = 'absolute inset-0 flex items-center justify-center bg-black bg-opacity-30';
                playIcon.innerHTML = '<i class="fas fa-play text-white text-xl"></i>';
                
                div.appendChild(video);
                div.appendChild(playIcon);
            }

            const fileName = document.createElement('div');
            fileName.className = 'absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-xs p-1 truncate';
            fileName.textContent = file.name;

            div.appendChild(removeBtn);
            div.appendChild(fileName);
            return div;
        }
        
        // Función para remover un archivo específico
        function removeFile(index) {
            selectedFiles.splice(index, 1);
            updatePreview();
            syncInputFile();
        }
        
        // Función para agregar más archivos
        function addMoreFiles() {
            if (selectedFiles.length < 10) {
                // Efecto visual en el botón
                const btn = document.querySelector('#add-more-files button');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Seleccionando...';
                
                // Abrir selector de archivos
                document.getElementById('media-input').click();
                
                // Restaurar texto original después de un momento
                setTimeout(() => {
                    if (btn.innerHTML.includes('Seleccionando')) {
                        btn.innerHTML = originalText;
                    }
                }, 2000);
            }
        }

        function clearFiles() {
            const input = document.getElementById('media-input');
            const preview = document.getElementById('media-preview');
            const clearBtn = document.getElementById('clear-btn');
            const dropZone = document.getElementById('drop-zone');
            const addMoreFiles = document.getElementById('add-more-files');
            const fileCountInfo = document.getElementById('file-count-info');
            
            // Limpiar array de archivos seleccionados
            selectedFiles = [];
            
            input.value = '';
            preview.innerHTML = '';
            preview.classList.add('hidden');
            clearBtn.classList.add('hidden');
            addMoreFiles.classList.add('hidden');
            fileCountInfo.classList.add('hidden');
            dropZone.classList.remove('hidden');
            hideUploadProgress();
        }
        
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
                    file_path: el.src || (el.querySelector('source') ? el.querySelector('source').src : el.src),
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
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la solicitud');
            });
        }
    </script>
</body>
</html> 