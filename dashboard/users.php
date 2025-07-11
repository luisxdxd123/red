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

// Obtener todos los usuarios excepto el actual
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as posts_count,
          (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as followers_count
          FROM users u 
          WHERE u.id != ? 
          ORDER BY u.first_name, u.last_name";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - Red Social</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include '../includes/navbar.php'; ?>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Descubre Usuarios</h1>
            <p class="text-gray-600">Conecta con otros miembros de la comunidad</p>
        </div>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($users as $user): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="text-center">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold text-xl mx-auto mb-4">
                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                        </div>
                        
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h3>
                        <p class="text-gray-600 text-sm">@<?php echo $user['username']; ?></p>
                        
                        <?php if ($user['bio']): ?>
                            <p class="text-gray-700 text-sm mt-2 line-clamp-3"><?php echo htmlspecialchars($user['bio']); ?></p>
                        <?php endif; ?>
                        
                        <!-- Estadísticas -->
                        <div class="flex justify-center space-x-4 mt-4 text-sm text-gray-600">
                            <div class="text-center">
                                <div class="font-bold text-indigo-600"><?php echo $user['posts_count']; ?></div>
                                <div>Posts</div>
                            </div>
                            <div class="text-center">
                                <div class="font-bold text-indigo-600"><?php echo $user['followers_count']; ?></div>
                                <div>Seguidores</div>
                            </div>
                        </div>
                        
                        <!-- Botón de seguir/no seguir -->
                        <div class="mt-4">
                            <?php if (isFollowing($_SESSION['user_id'], $user['id'])): ?>
                                <button onclick="toggleFollow(<?php echo $user['id']; ?>)" 
                                        id="follow-btn-<?php echo $user['id']; ?>"
                                        class="w-full bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg transition duration-300">
                                    <i class="fas fa-user-check mr-2"></i>Siguiendo
                                </button>
                            <?php else: ?>
                                <button onclick="toggleFollow(<?php echo $user['id']; ?>)" 
                                        id="follow-btn-<?php echo $user['id']; ?>"
                                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                                    <i class="fas fa-user-plus mr-2"></i>Seguir
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-2 space-y-2">
                            <a href="user_profile.php?id=<?php echo $user['id']; ?>" 
                               class="w-full inline-block bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded-lg transition duration-300 text-center">
                                <i class="fas fa-eye mr-2"></i>Ver Perfil
                            </a>
                            
                            <?php if ($user_permissions['can_access_messages']): ?>
                                <?php if (isFollowing($_SESSION['user_id'], $user['id'])): ?>
                                    <a href="messages.php?user=<?php echo $user['id']; ?>" 
                                       class="w-full inline-block bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300 text-center">
                                        <i class="fas fa-envelope mr-2"></i>Enviar Mensaje
                                    </a>
                                <?php else: ?>
                                    <div class="w-full bg-gray-300 text-gray-500 font-bold py-2 px-4 rounded-lg text-center cursor-not-allowed">
                                        <i class="fas fa-envelope mr-2"></i>Enviar Mensaje
                                        <div class="text-xs text-gray-400 mt-1">Debes seguir al usuario</div>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <button onclick="showMembershipModal()" 
                                        class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                                    <i class="fas fa-crown mr-2"></i>Mensajes Premium
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($users)): ?>
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <i class="fas fa-users text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">No hay otros usuarios aún</h3>
                <p class="text-gray-500">¡Invita a tus amigos a unirse a la red social!</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
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
                    
                    if (data.following) {
                        followBtn.className = 'w-full bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg transition duration-300';
                        followBtn.innerHTML = '<i class="fas fa-user-check mr-2"></i>Siguiendo';
                    } else {
                        followBtn.className = 'w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300';
                        followBtn.innerHTML = '<i class="fas fa-user-plus mr-2"></i>Seguir';
                    }
                }
            });
        }
    </script>
</body>
</html> 