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

// Obtener información del usuario incluyendo el avatar
$query = "SELECT u.*, COALESCE(u.avatar_url, '') as avatar_url FROM users u WHERE u.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Procesar cancelación de membresía
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_membership') {
    // Verificar que no sea membresía básica
    if ($user['membership_type'] !== 'basico') {
        // Actualizar a membresía básica
        $query = "UPDATE users SET 
                  membership_type = 'basico',
                  membership_expires_at = NULL,
                  membership_created_at = CURRENT_TIMESTAMP
                  WHERE id = ?";
        $stmt = $db->prepare($query);
        if ($stmt->execute([$_SESSION['user_id']])) {
            $_SESSION['success'] = 'Tu membresía ha sido cancelada. Ahora tienes una cuenta básica.';
            header('Location: profile.php');
            exit;
        } else {
            $_SESSION['error'] = 'Error al cancelar la membresía. Por favor, intenta de nuevo.';
        }
    }
}

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

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Información del Perfil -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <!-- Sección Superior: Información Básica -->
            <div class="p-4 sm:p-6 pb-0">
                <div class="flex flex-col sm:flex-row items-center sm:items-start space-y-4 sm:space-y-0 sm:space-x-6">
                    <!-- Foto de Perfil con opción de cambio -->
                    <div class="flex-shrink-0 relative group">
                        <?php if ($user['avatar_url']): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" 
                                 alt="Avatar de <?php echo htmlspecialchars($user['first_name']); ?>"
                                 class="w-24 h-24 sm:w-28 sm:h-28 rounded-full object-cover shadow-lg">
                        <?php else: ?>
                            <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold text-3xl sm:text-4xl shadow-lg">
                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Botón para cambiar avatar -->
                        <button onclick="openAvatarModal()" 
                                class="absolute bottom-0 right-0 bg-blue-600 hover:bg-blue-700 text-white rounded-full p-2 shadow-lg transform transition-transform duration-200 hover:scale-110">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>

                    <!-- Información Principal -->
                    <div class="flex-1 min-w-0 text-center sm:text-left">
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 truncate"><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h1>
                        <p class="text-gray-600">@<?php echo $user['username']; ?></p>
                        <p class="text-gray-700 mt-3"><?php echo $user['bio'] ?: 'Sin biografía'; ?></p>
                        <p class="text-sm text-gray-500 mt-2 flex items-center justify-center sm:justify-start">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            Miembro desde <?php echo date('F Y', strtotime($user['created_at'])); ?>
                        </p>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="flex sm:flex-col space-x-2 sm:space-x-0 sm:space-y-2 w-full sm:w-40">
                        <button onclick="openEditModal()" 
                                class="flex-1 sm:flex-none bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center justify-center">
                            <i class="fas fa-edit mr-2"></i>
                            <span class="hidden sm:inline">Editar Perfil</span>
                            <span class="sm:hidden">Editar</span>
                        </button>
                        <button onclick="openPasswordModal()" 
                                class="flex-1 sm:flex-none bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center justify-center">
                            <i class="fas fa-key mr-2"></i>
                            <span class="hidden sm:inline">Cambiar Contraseña</span>
                            <span class="sm:hidden">Contraseña</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sección de Membresía -->
            <div class="p-4 sm:p-6 mt-6 bg-gray-50 mx-4 sm:mx-6 rounded-lg">
                <div class="flex flex-col sm:flex-row items-center justify-between mb-4 space-y-2 sm:space-y-0">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-crown text-blue-600 mr-2"></i>
                        Información de Membresía
                    </h2>
                    <?php if ($user['membership_expires_at']): ?>
                        <span class="text-sm text-gray-600 flex items-center">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            Expira: <?php echo date('d/m/Y', strtotime($user['membership_expires_at'])); ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="flex flex-col sm:flex-row items-center space-y-4 sm:space-y-0 sm:items-start sm:justify-between">
                    <?php 
                    // Definición de los badges de membresía
                    $membershipBadges = [
                        'basico' => [
                            'bg' => 'bg-gray-100',
                            'text' => 'text-gray-800',
                            'icon' => 'fas fa-user',
                            'description' => 'Acceso básico a la plataforma'
                        ],
                        'premium' => [
                            'bg' => 'bg-yellow-100',
                            'text' => 'text-yellow-800',
                            'icon' => 'fas fa-star',
                            'description' => 'Acceso a grupos y mensajes privados'
                        ],
                        'vip' => [
                            'bg' => 'bg-purple-100',
                            'text' => 'text-purple-800',
                            'icon' => 'fas fa-crown',
                            'description' => 'Acceso completo a todas las funciones'
                        ]
                    ];
                    
                    // Asegurarse de que el tipo de membresía existe en el array
                    $membership_type = $user['membership_type'] ?? 'basico';
                    $currentMembership = $membershipBadges[$membership_type] ?? $membershipBadges['basico'];
                    ?>
                    
                    <!-- Badge de Membresía -->
                    <div class="w-full sm:flex-1">
                        <div class="flex items-center px-4 py-3 rounded-lg <?php echo $currentMembership['bg'] . ' ' . $currentMembership['text']; ?>">
                            <i class="<?php echo $currentMembership['icon']; ?> text-2xl mr-3"></i>
                            <div>
                                <div class="font-bold">Membresía <?php echo ucfirst($membership_type); ?></div>
                                <div class="text-sm mt-1"><?php echo $currentMembership['description']; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Botón de Acción de Membresía -->
                    <div class="w-full sm:w-auto sm:ml-4">
                        <?php if ($user['membership_type'] !== 'basico'): ?>
                            <button onclick="openCancelMembershipModal()" 
                                    class="w-full sm:w-auto bg-white border-2 border-red-300 text-red-600 hover:bg-red-50 px-4 py-3 rounded-lg transition duration-300 flex items-center justify-center sm:justify-start">
                                <i class="fas fa-times-circle text-xl mr-2"></i>
                                <div>
                                    <div class="font-medium">Cancelar Membresía</div>
                                    <div class="text-xs text-red-500">Volver a cuenta básica</div>
                                </div>
                            </button>
                        <?php elseif (!isset($pending_membership_request)): ?>
                            <a href="memberships.php" 
                               class="w-full sm:w-auto bg-white border-2 border-blue-300 text-blue-600 hover:bg-blue-50 px-4 py-3 rounded-lg transition duration-300 flex items-center justify-center sm:justify-start">
                                <i class="fas fa-arrow-circle-up text-xl mr-2"></i>
                                <div>
                                    <div class="font-medium">Mejorar Membresía</div>
                                    <div class="text-xs text-blue-500">Ver planes disponibles</div>
                                </div>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 p-4 sm:p-6">
                <div class="bg-white border border-gray-100 rounded-lg p-4 text-center shadow-sm hover:shadow-md transition-shadow duration-300">
                    <div class="text-2xl font-bold text-blue-600 mb-1"><?php echo $posts_count; ?></div>
                    <div class="text-sm text-gray-600 flex items-center justify-center">
                        <i class="fas fa-edit mr-2"></i>Posts
                    </div>
                </div>
                <div class="bg-white border border-gray-100 rounded-lg p-4 text-center shadow-sm hover:shadow-md transition-shadow duration-300">
                    <div class="text-2xl font-bold text-blue-600 mb-1"><?php echo $followers_count; ?></div>
                    <div class="text-sm text-gray-600 flex items-center justify-center">
                        <i class="fas fa-users mr-2"></i>Seguidores
                    </div>
                </div>
                <div class="bg-white border border-gray-100 rounded-lg p-4 text-center shadow-sm hover:shadow-md transition-shadow duration-300">
                    <div class="text-2xl font-bold text-blue-600 mb-1"><?php echo $following_count; ?></div>
                    <div class="text-sm text-gray-600 flex items-center justify-center">
                        <i class="fas fa-user-friends mr-2"></i>Siguiendo
                    </div>
                </div>
            </div>

            <!-- Mensajes del Sistema -->
            <?php if (isset($_SESSION['success']) || isset($_SESSION['error'])): ?>
                <div class="px-4 sm:px-6 pb-6">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-green-800">¡Éxito!</h3>
                                    <p class="mt-1 text-sm text-green-700"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">Error</h3>
                                    <p class="mt-1 text-sm text-red-700"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Posts del Usuario -->
        <div class="bg-white rounded-lg shadow-md p-4 sm:p-6 mt-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Mis Posts</h2>
            
            <?php if (empty($posts)): ?>
                <div class="text-center py-8">
                    <i class="fas fa-edit text-gray-400 text-4xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">No has publicado nada aún</h3>
                    <p class="text-gray-500">¡Comparte tu primer post desde la página de inicio!</p>
                    <a href="index.php" class="inline-block mt-4 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
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
                                <div class="ml-3 flex-1 min-w-0">
                                    <h4 class="font-semibold text-gray-900 truncate"><?php echo $post['first_name'] . ' ' . $post['last_name']; ?></h4>
                                    <p class="text-xs text-gray-500"><?php echo timeAgo($post['created_at']); ?></p>
                                </div>
                                <div class="flex space-x-2">
                                    <button onclick="deletePost(<?php echo $post['id']; ?>)" 
                                            class="text-gray-500 hover:text-red-500 transition duration-300 p-2" 
                                            title="Eliminar publicación">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <p class="text-gray-800 leading-relaxed mb-3 break-words"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            
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

    <!-- Modales -->
    <!-- Modal para Editar Perfil -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
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

    <!-- Modal para Cancelar Membresía -->
    <div id="cancelMembershipModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-6 border w-full max-w-lg shadow-xl rounded-xl bg-white m-4">
            <div class="absolute top-4 right-4">
                <button onclick="closeCancelMembershipModal()" 
                        class="text-gray-400 hover:text-gray-500 transition duration-150">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="mb-6">
                <h3 class="text-xl font-bold text-gray-900 flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>
                    Cancelar Membresía
                </h3>
                <p class="mt-2 text-sm text-gray-600">
                    Estás a punto de cancelar tu membresía <?php echo ucfirst($user['membership_type']); ?>
                </p>
            </div>

            <div class="bg-yellow-50 rounded-xl p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h4 class="font-medium text-yellow-800">
                            Consecuencias de la Cancelación
                        </h4>
                        <ul class="mt-3 space-y-3">
                            <li class="flex items-center text-yellow-700">
                                <i class="fas fa-times-circle text-red-400 mr-2"></i>
                                <span>Perderás acceso a todas las funciones premium</span>
                            </li>
                            <li class="flex items-center text-yellow-700">
                                <i class="fas fa-arrow-circle-down text-yellow-500 mr-2"></i>
                                <span>Tu cuenta volverá a ser básica inmediatamente</span>
                            </li>
                            <li class="flex items-center text-yellow-700">
                                <i class="fas fa-coins text-yellow-600 mr-2"></i>
                                <span>No hay reembolsos por el tiempo restante</span>
                            </li>
                            <li class="flex items-center text-yellow-700">
                                <i class="fas fa-undo-alt text-red-500 mr-2"></i>
                                <span>Esta acción no se puede deshacer</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 rounded-xl p-4 mb-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                    </div>
                    <p class="ml-4 text-sm text-gray-600">
                        Si decides cancelar tu membresía, podrás volver a adquirirla en cualquier momento 
                        desde la sección de membresías, al mismo precio y con los mismos beneficios.
                    </p>
                </div>
            </div>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="cancel_membership">
                
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeCancelMembershipModal()" 
                            class="px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 hover:bg-gray-50 font-medium rounded-lg transition duration-300 flex items-center">
                        <i class="fas fa-times mr-2"></i>
                        Mantener Membresía
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition duration-300 flex items-center">
                        <i class="fas fa-check mr-2"></i>
                        Confirmar Cancelación
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Cambiar Avatar -->
    <div id="avatarModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Cambiar Foto de Perfil</h3>
                    <button onclick="closeAvatarModal()" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="avatarForm" action="update_avatar.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <!-- Vista previa del avatar -->
                    <div class="flex justify-center mb-4">
                        <div class="relative">
                            <div id="avatarPreview" class="w-32 h-32 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                                <?php if ($user['avatar_url']): ?>
                                    <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" 
                                         alt="Vista previa" 
                                         class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold text-4xl">
                                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Input para seleccionar archivo -->
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Seleccionar nueva imagen
                        </label>
                        <input type="file" 
                               id="avatarInput" 
                               name="avatar" 
                               accept="image/*"
                               class="hidden" 
                               onchange="previewAvatar(this)">
                        <label for="avatarInput" 
                               class="cursor-pointer bg-white border-2 border-gray-300 rounded-md py-2 px-3 hover:border-blue-500 focus:outline-none w-full inline-flex items-center justify-center text-sm text-gray-700 hover:text-blue-500 transition-colors duration-200">
                            <i class="fas fa-upload mr-2"></i>
                            Seleccionar imagen
                        </label>
                        <p class="mt-1 text-xs text-gray-500">
                            Formatos permitidos: JPG, PNG, GIF. Máximo 2MB.
                        </p>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex justify-end space-x-3 mt-6">
                        <?php if ($user['avatar_url']): ?>
                        <button type="button" 
                                onclick="removeAvatar()"
                                class="px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg transition-colors duration-200">
                            <i class="fas fa-trash-alt mr-2"></i>
                            Eliminar foto
                        </button>
                        <?php endif; ?>
                        <button type="button" 
                                onclick="closeAvatarModal()"
                                class="px-4 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg transition-colors duration-200">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white hover:bg-blue-700 rounded-lg transition-colors duration-200">
                            <i class="fas fa-save mr-2"></i>
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

        function openCancelMembershipModal() {
            document.getElementById('cancelMembershipModal').classList.remove('hidden');
        }
        
        function closeCancelMembershipModal() {
            document.getElementById('cancelMembershipModal').classList.add('hidden');
        }

        // Funciones para el manejo del avatar
        function openAvatarModal() {
            document.getElementById('avatarModal').classList.remove('hidden');
        }

        function closeAvatarModal() {
            document.getElementById('avatarModal').classList.add('hidden');
            // Resetear el formulario
            document.getElementById('avatarForm').reset();
            resetAvatarPreview();
        }

        function previewAvatar(input) {
            const preview = document.getElementById('avatarPreview');
            const file = input.files[0];

            if (file) {
                // Validar tamaño (2MB máximo)
                if (file.size > 2 * 1024 * 1024) {
                    alert('La imagen no debe superar los 2MB');
                    input.value = '';
                    return;
                }

                // Validar tipo
                if (!file.type.startsWith('image/')) {
                    alert('Por favor, selecciona una imagen válida');
                    input.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Vista previa" class="w-full h-full object-cover">`;
                }
                reader.readAsDataURL(file);
            }
        }

        function resetAvatarPreview() {
            const preview = document.getElementById('avatarPreview');
            <?php if ($user['avatar_url']): ?>
                preview.innerHTML = `<img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="Avatar actual" class="w-full h-full object-cover">`;
            <?php else: ?>
                preview.innerHTML = `
                    <div class="w-full h-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold text-4xl">
                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                    </div>`;
            <?php endif; ?>
        }

        function removeAvatar() {
            if (confirm('¿Estás seguro de que quieres eliminar tu foto de perfil?')) {
                fetch('remove_avatar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ action: 'remove_avatar' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error al eliminar la foto de perfil: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al procesar la solicitud');
                });
            }
        }
    </script>
</body>
</html> 