<?php
require_once '../includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Verificar si el usuario es administrador
$query = "SELECT is_admin FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user['is_admin']) {
    header('Location: index.php');
    exit;
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'], $_POST['request_id'])) {
        $request_id = $_POST['request_id'];
        $action = $_POST['action'];
        $admin_notes = $_POST['admin_notes'] ?? '';

        if ($action === 'approve' || $action === 'reject') {
            // Actualizar estado de la solicitud
            $query = "UPDATE page_requests 
                     SET status = ?, admin_notes = ?, response_date = CURRENT_TIMESTAMP 
                     WHERE id = ?";
            $stmt = $db->prepare($query);
            $status = $action === 'approve' ? 'approved' : 'rejected';
            $stmt->execute([$status, $admin_notes, $request_id]);

            // Si se aprueba, dar acceso al usuario
            if ($action === 'approve') {
                $query = "UPDATE users u 
                         JOIN page_requests pr ON pr.user_id = u.id 
                         SET u.can_create_pages = TRUE 
                         WHERE pr.id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$request_id]);
            }
        }
    }
}

// Obtener todas las solicitudes
$query = "SELECT pr.*, u.username, u.first_name, u.last_name, u.email 
          FROM page_requests pr 
          JOIN users u ON pr.user_id = u.id 
          ORDER BY pr.request_date DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener número de mensajes no leídos
$unread_messages = getUnreadMessagesCount($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Solicitudes de Páginas - Red Social</title>
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
                        <a href="pages.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300">
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
                        <a href="admin_page_requests.php" class="py-4 px-2 text-indigo-500 border-b-4 border-indigo-500 font-semibold">
                            <i class="fas fa-tasks mr-1"></i>Solicitudes
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
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Administrar Solicitudes de Páginas</h1>

            <?php if (empty($requests)): ?>
                <div class="text-center py-8">
                    <i class="fas fa-clipboard-check text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-600">No hay solicitudes pendientes de revisión</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comprobante</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($request['email']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('d/m/Y H:i', strtotime($request['request_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $status_class = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'approved' => 'bg-green-100 text-green-800',
                                            'rejected' => 'bg-red-100 text-red-800'
                                        ][$request['status']];
                                        $status_text = [
                                            'pending' => 'Pendiente',
                                            'approved' => 'Aprobada',
                                            'rejected' => 'Rechazada'
                                        ][$request['status']];
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <a href="uploads/page_requests/<?php echo $request['payment_proof']; ?>" 
                                           target="_blank"
                                           class="text-indigo-600 hover:text-indigo-900">
                                            Ver Comprobante
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <button onclick="openActionModal(<?php echo $request['id']; ?>, 'approve')" 
                                                    class="text-green-600 hover:text-green-900 mr-3">
                                                <i class="fas fa-check mr-1"></i>Aprobar
                                            </button>
                                            <button onclick="openActionModal(<?php echo $request['id']; ?>, 'reject')" 
                                                    class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-times mr-1"></i>Rechazar
                                            </button>
                                        <?php else: ?>
                                            <span class="text-gray-500">
                                                <?php echo $request['admin_notes'] ? htmlspecialchars($request['admin_notes']) : 'Sin notas'; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para Acciones -->
    <div id="actionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-bold text-gray-900 mb-4" id="modalTitle">Procesar Solicitud</h3>
                <form id="actionForm" method="POST" class="space-y-4">
                    <input type="hidden" name="request_id" id="requestId">
                    <input type="hidden" name="action" id="actionType">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Notas Administrativas
                        </label>
                        <textarea name="admin_notes" rows="3"
                                  class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                  placeholder="Agregar notas sobre la decisión..."></textarea>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeActionModal()" 
                                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded transition duration-300">
                            Cancelar
                        </button>
                        <button type="submit" id="confirmButton"
                                class="text-white font-bold py-2 px-4 rounded transition duration-300">
                            Confirmar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openActionModal(requestId, action) {
            document.getElementById('requestId').value = requestId;
            document.getElementById('actionType').value = action;
            
            const modal = document.getElementById('actionModal');
            const title = document.getElementById('modalTitle');
            const confirmButton = document.getElementById('confirmButton');
            
            if (action === 'approve') {
                title.textContent = 'Aprobar Solicitud';
                confirmButton.className = 'bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition duration-300';
                confirmButton.textContent = 'Aprobar';
            } else {
                title.textContent = 'Rechazar Solicitud';
                confirmButton.className = 'bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition duration-300';
                confirmButton.textContent = 'Rechazar';
            }
            
            modal.classList.remove('hidden');
        }
        
        function closeActionModal() {
            document.getElementById('actionModal').classList.add('hidden');
        }
    </script>
</body>
</html> 