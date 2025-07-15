<?php
require_once '../includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Verificar permisos del usuario
$query = "SELECT is_admin, can_create_pages, membership_type FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$user_permissions = $stmt->fetch(PDO::FETCH_ASSOC);

// Si es admin o tiene membresía VIP, puede crear páginas automáticamente
if ($user_permissions['is_admin'] || $user_permissions['membership_type'] === 'vip') {
    $user_permissions['can_create_pages'] = true;
}

// Verificar si hay una solicitud pendiente (solo si no es admin, no es VIP y no tiene permisos)
$pending_request = null;
if (!$user_permissions['is_admin'] && $user_permissions['membership_type'] !== 'vip' && !$user_permissions['can_create_pages']) {
    $query = "SELECT status FROM page_requests WHERE user_id = ? AND status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $pending_request = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Obtener todas las páginas
$query = "SELECT p.*, u.first_name, u.last_name, u.username,
          (SELECT COUNT(*) FROM page_followers WHERE page_id = p.id) as followers_count,
          (SELECT COUNT(*) FROM page_posts WHERE page_id = p.id) as posts_count,
          CASE WHEN EXISTS (
              SELECT 1 FROM page_followers 
              WHERE page_id = p.id AND user_id = ?
          ) THEN 1 ELSE 0 END as is_following
          FROM pages p 
          JOIN users u ON p.creator_id = u.id
          WHERE p.is_active = 1
          ORDER BY p.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener número de mensajes no leídos
$unread_messages = getUnreadMessagesCount($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Páginas - Red Social</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include '../includes/navbar.php'; ?>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">Páginas</h1>
                    <p class="text-gray-600">Descubre y sigue páginas interesantes</p>
                </div>
                <?php if ($user_permissions['can_create_pages']): ?>
                    <button onclick="openCreatePageModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        <i class="fas fa-plus mr-2"></i>Crear Página
                    </button>
                <?php elseif ($pending_request): ?>
                    <div class="text-yellow-600">
                        <i class="fas fa-clock mr-2"></i>Solicitud en Revisión
                    </div>
                <?php else: ?>
                    <a href="request_page_access.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        <i class="fas fa-unlock mr-2"></i>Solicitar Acceso
                    </a>
                <?php endif; ?>
            </div>

            <?php if (isset($_GET['request_sent']) && $_GET['request_sent'] == 1): ?>
                <div class="mt-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4">
                    <p class="font-bold">¡Solicitud enviada con éxito!</p>
                    <p>Tu solicitud será revisada por nuestros administradores. Te notificaremos cuando sea aprobada.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Grid de Páginas -->
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($pages as $page): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <!-- Cover Image -->
                    <div class="h-32 bg-gradient-to-r from-blue-500 to-purple-600 relative">
                        <?php if ($page['cover_image'] != 'default-page-cover.jpg'): ?>
                            <img src="uploads/pages/covers/<?php echo $page['cover_image']; ?>" 
                                 class="w-full h-full object-cover" alt="Portada">
                        <?php endif; ?>
                        <div class="absolute inset-0 bg-black bg-opacity-20"></div>
                    </div>
                    
                    <div class="p-6">
                        <!-- Imagen de perfil -->
                        <div class="relative -mt-16 mb-4">
                            <div class="w-24 h-24 rounded-full border-4 border-white overflow-hidden bg-white">
                                <?php if ($page['profile_image'] != 'default-page.jpg'): ?>
                                    <img src="uploads/pages/profiles/<?php echo $page['profile_image']; ?>" 
                                         class="w-full h-full object-cover" alt="Perfil">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center text-white text-3xl font-bold">
                                        <?php echo strtoupper(substr($page['name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Información de la Página -->
                        <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($page['name']); ?></h3>
                        <p class="text-gray-600 text-sm mb-3 line-clamp-2"><?php echo htmlspecialchars($page['description']); ?></p>
                        
                        <!-- Creador -->
                        <div class="flex items-center mb-3">
                            <div class="w-6 h-6 rounded-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold text-xs mr-2">
                                <?php echo strtoupper(substr($page['first_name'], 0, 1) . substr($page['last_name'], 0, 1)); ?>
                            </div>
                            <span class="text-sm text-gray-600">
                                Creada por <?php echo $page['first_name'] . ' ' . $page['last_name']; ?>
                            </span>
                        </div>
                        
                        <!-- Estadísticas -->
                        <div class="flex justify-between text-sm text-gray-600 mb-4">
                            <div class="flex items-center">
                                <i class="fas fa-users mr-1"></i>
                                <?php echo $page['followers_count']; ?> seguidores
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-edit mr-1"></i>
                                <?php echo $page['posts_count']; ?> posts
                            </div>
                        </div>
                        
                        <!-- Acciones -->
                        <div class="space-y-2">
                            <?php if ($page['creator_id'] == $_SESSION['user_id']): ?>
                                <a href="page_detail.php?id=<?php echo $page['id']; ?>" 
                                   class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300 text-center block">
                                    <i class="fas fa-crown mr-2"></i>Administrar Página
                                </a>
                            <?php else: ?>
                                <?php if ($page['is_following']): ?>
                                    <button onclick="unfollowPage(<?php echo $page['id']; ?>)" 
                                            class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                                        <i class="fas fa-times mr-2"></i>Dejar de Seguir
                                    </button>
                                <?php else: ?>
                                    <button onclick="followPage(<?php echo $page['id']; ?>)" 
                                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                                        <i class="fas fa-plus mr-2"></i>Seguir Página
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a href="page_detail.php?id=<?php echo $page['id']; ?>" 
                               class="w-full bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded-lg transition duration-300 text-center block">
                                <i class="fas fa-eye mr-2"></i>Ver Página
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($pages)): ?>
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <i class="fas fa-flag text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">No hay páginas disponibles</h3>
                <?php if ($user_permissions['can_create_pages']): ?>
                    <p class="text-gray-500 mb-4">¡Sé el primero en crear una página!</p>
                    <button onclick="openCreatePageModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        <i class="fas fa-plus mr-2"></i>Crear Primera Página
                    </button>
                <?php else: ?>
                    <p class="text-gray-500 mb-4">Para crear páginas, necesitas solicitar acceso primero.</p>
                    <?php if (!$pending_request): ?>
                        <a href="request_page_access.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300 inline-block">
                            <i class="fas fa-unlock mr-2"></i>Solicitar Acceso
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($user_permissions['can_create_pages']): ?>
    <!-- Modal para Crear Página -->
    <div id="createPageModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Crear Nueva Página</h3>
                <form action="create_page.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nombre de la Página</label>
                        <input type="text" name="name" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="Ej: Mi Página de Tecnología">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Descripción</label>
                        <textarea name="description" rows="3" required
                                  class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                  placeholder="Describe de qué trata tu página..."></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Foto de Perfil</label>
                        <input type="file" name="profile_image" accept="image/*"
                               class="mt-1 block w-full text-sm text-gray-500
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-full file:border-0
                                      file:text-sm file:font-semibold
                                      file:bg-indigo-50 file:text-indigo-700
                                      hover:file:bg-indigo-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Foto de Portada</label>
                        <input type="file" name="cover_image" accept="image/*"
                               class="mt-1 block w-full text-sm text-gray-500
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-full file:border-0
                                      file:text-sm file:font-semibold
                                      file:bg-indigo-50 file:text-indigo-700
                                      hover:file:bg-indigo-100">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeCreatePageModal()" 
                                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded transition duration-300">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded transition duration-300">
                            Crear Página
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Modal functions
        function openCreatePageModal() {
            document.getElementById('createPageModal').classList.remove('hidden');
        }
        
        function closeCreatePageModal() {
            document.getElementById('createPageModal').classList.add('hidden');
        }

        // Page actions
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
    </script>
</body>
</html> 