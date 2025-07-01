<?php
require_once '../includes/functions.php';
requireLogin();

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
                        <a href="groups.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300">Grupos</a>
                        <a href="pages.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300">Páginas</a>
                        <a href="users.php" class="py-4 px-2 text-indigo-500 border-b-4 border-indigo-500 font-semibold">Usuarios</a>
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