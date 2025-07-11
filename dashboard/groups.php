<?php
require_once '../includes/functions.php';
requireLogin();

// Verificar que el usuario tenga acceso a grupos
$user_permissions = getUserPermissions($_SESSION['user_id']);
if (!$user_permissions['can_access_groups']) {
    $_SESSION['warning'] = 'Necesitas una membresía Premium o VIP para acceder a los grupos.';
    header('Location: memberships.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Obtener grupos públicos y privados donde el usuario es miembro
$query = "SELECT g.*, u.first_name, u.last_name, u.username,
          (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as members_count,
          (SELECT COUNT(*) FROM group_posts WHERE group_id = g.id) as posts_count,
          gm.role as user_role
          FROM groups g 
          JOIN users u ON g.creator_id = u.id
          LEFT JOIN group_members gm ON g.id = gm.group_id AND gm.user_id = ?
          WHERE g.is_active = 1 AND (g.privacy = 'public' OR gm.user_id IS NOT NULL)
          ORDER BY g.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener número de mensajes no leídos solo si tiene acceso
$unread_messages = 0;
if ($user_permissions['can_access_messages']) {
    $unread_messages = getUnreadMessagesCount($_SESSION['user_id']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grupos - Red Social</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include '../includes/navbar.php'; ?>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">Grupos</h1>
                    <p class="text-gray-600">Descubre y únete a comunidades de interés</p>
                </div>
                <button onclick="openCreateGroupModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                    <i class="fas fa-plus mr-2"></i>Crear Grupo
                </button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex space-x-4">
                <button onclick="filterGroups('all')" id="filter-all" class="filter-btn bg-indigo-600 text-white px-4 py-2 rounded-lg transition duration-300">
                    Todos
                </button>
                <button onclick="filterGroups('member')" id="filter-member" class="filter-btn bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition duration-300">
                    Mis Grupos
                </button>
                <button onclick="filterGroups('public')" id="filter-public" class="filter-btn bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition duration-300">
                    Públicos
                </button>
            </div>
        </div>

        <!-- Grid de Grupos -->
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($groups as $group): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden group-card" 
                     data-is-member="<?php echo $group['user_role'] ? 'true' : 'false'; ?>" 
                     data-privacy="<?php echo $group['privacy']; ?>">
                    
                    <!-- Cover Image -->
                    <div class="h-32 bg-gradient-to-r from-blue-500 to-purple-600 relative">
                        <div class="absolute inset-0 bg-black bg-opacity-20"></div>
                        <div class="absolute bottom-4 left-4">
                            <span class="bg-white bg-opacity-90 text-xs px-2 py-1 rounded-full font-medium">
                                <i class="fas fa-<?php echo $group['privacy'] == 'private' ? 'lock' : 'globe'; ?> mr-1"></i>
                                <?php echo ucfirst($group['privacy']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <!-- Información del Grupo -->
                        <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($group['name']); ?></h3>
                        <p class="text-gray-600 text-sm mb-3 line-clamp-2"><?php echo htmlspecialchars($group['description']); ?></p>
                        
                        <!-- Creador -->
                        <div class="flex items-center mb-3">
                            <div class="w-6 h-6 rounded-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold text-xs mr-2">
                                <?php echo strtoupper(substr($group['first_name'], 0, 1) . substr($group['last_name'], 0, 1)); ?>
                            </div>
                            <span class="text-sm text-gray-600">
                                Creado por <?php echo $group['first_name'] . ' ' . $group['last_name']; ?>
                            </span>
                        </div>
                        
                        <!-- Estadísticas -->
                        <div class="flex justify-between text-sm text-gray-600 mb-4">
                            <div class="flex items-center">
                                <i class="fas fa-users mr-1"></i>
                                <?php echo $group['members_count']; ?> miembros
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-edit mr-1"></i>
                                <?php echo $group['posts_count']; ?> posts
                            </div>
                        </div>
                        
                        <!-- Acciones -->
                        <div class="space-y-2">
                            <?php if ($group['user_role']): ?>
                                <!-- Ya es miembro -->
                                <a href="group_detail.php?id=<?php echo $group['id']; ?>" 
                                   class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300 text-center block">
                                    <i class="fas fa-check mr-2"></i>Ver Grupo
                                    <?php if ($group['user_role'] == 'admin'): ?>
                                        <span class="text-xs">(Admin)</span>
                                    <?php endif; ?>
                                </a>
                                <?php if ($group['user_role'] != 'admin'): ?>
                                    <button onclick="leaveGroup(<?php echo $group['id']; ?>)" 
                                            class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                                        <i class="fas fa-sign-out-alt mr-2"></i>Salir del Grupo
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- No es miembro -->
                                <?php if ($group['privacy'] == 'public'): ?>
                                    <button onclick="joinGroup(<?php echo $group['id']; ?>)" 
                                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                                        <i class="fas fa-plus mr-2"></i>Unirse al Grupo
                                    </button>
                                <?php else: ?>
                                    <button class="w-full bg-gray-400 text-white font-bold py-2 px-4 rounded-lg cursor-not-allowed" disabled>
                                        <i class="fas fa-lock mr-2"></i>Grupo Privado
                                    </button>
                                <?php endif; ?>
                                <a href="group_detail.php?id=<?php echo $group['id']; ?>" 
                                   class="w-full bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded-lg transition duration-300 text-center block">
                                    <i class="fas fa-eye mr-2"></i>Ver Detalles
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($groups)): ?>
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <i class="fas fa-users text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">No hay grupos disponibles</h3>
                <p class="text-gray-500 mb-4">¡Sé el primero en crear un grupo!</p>
                <button onclick="openCreateGroupModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                    <i class="fas fa-plus mr-2"></i>Crear Primer Grupo
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal para Crear Grupo -->
    <div id="createGroupModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Crear Nuevo Grupo</h3>
                <form action="create_group.php" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nombre del Grupo</label>
                        <input type="text" name="name" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="Ej: Desarrolladores PHP">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Descripción</label>
                        <textarea name="description" rows="3" required
                                  class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                  placeholder="Describe de qué trata tu grupo..."></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Privacidad</label>
                        <select name="privacy" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="public">Público - Cualquiera puede unirse</option>
                            <option value="private">Privado - Solo por invitación</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeCreateGroupModal()" 
                                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded transition duration-300">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded transition duration-300">
                            Crear Grupo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        function openCreateGroupModal() {
            document.getElementById('createGroupModal').classList.remove('hidden');
        }
        
        function closeCreateGroupModal() {
            document.getElementById('createGroupModal').classList.add('hidden');
        }

        // Filter functions
        function filterGroups(filter) {
            const cards = document.querySelectorAll('.group-card');
            const buttons = document.querySelectorAll('.filter-btn');
            
            // Reset button styles
            buttons.forEach(btn => {
                btn.className = 'filter-btn bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition duration-300';
            });
            
            // Highlight active button
            document.getElementById(`filter-${filter}`).className = 'filter-btn bg-indigo-600 text-white px-4 py-2 rounded-lg transition duration-300';
            
            // Show/hide cards
            cards.forEach(card => {
                const isMember = card.getAttribute('data-is-member') === 'true';
                const privacy = card.getAttribute('data-privacy');
                
                let show = false;
                
                switch(filter) {
                    case 'all':
                        show = true;
                        break;
                    case 'member':
                        show = isMember;
                        break;
                    case 'public':
                        show = privacy === 'public';
                        break;
                }
                
                card.style.display = show ? 'block' : 'none';
            });
        }

        // Group actions
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
                        location.reload();
                    } else {
                        alert('Error al salir del grupo: ' + data.error);
                    }
                });
            }
        }
    </script>
</body>
</html> 