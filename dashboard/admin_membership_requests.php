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
            // Obtener información de la solicitud
            $query = "SELECT * FROM membership_requests WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$request_id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            // Actualizar estado de la solicitud
            $query = "UPDATE membership_requests 
                     SET status = ?, admin_notes = ?, response_date = CURRENT_TIMESTAMP 
                     WHERE id = ?";
            $stmt = $db->prepare($query);
            $status = $action === 'approve' ? 'approved' : 'rejected';
            $stmt->execute([$status, $admin_notes, $request_id]);

            // Si se aprueba, actualizar membresía del usuario
            if ($action === 'approve') {
                $expiry_date = date('Y-m-d H:i:s', strtotime('+1 year'));
                $query = "UPDATE users 
                         SET membership_type = ?, 
                             membership_expires_at = ?,
                             membership_created_at = CURRENT_TIMESTAMP
                         WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$request['membership_type'], $expiry_date, $request['user_id']]);
            }
        }
    }
}

// Obtener todas las solicitudes
$query = "SELECT mr.*, u.username, u.first_name, u.last_name, u.email 
          FROM membership_requests mr 
          JOIN users u ON mr.user_id = u.id 
          ORDER BY mr.request_date DESC";
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
    <title>Administrar Solicitudes de Membresía - Red Social</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include '../includes/navbar.php'; ?>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Administrar Solicitudes de Membresía</h1>

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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Membresía</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
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
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="capitalize"><?php echo $request['membership_type']; ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        $<?php echo number_format($request['amount'], 2); ?> MXN
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
                                        <a href="uploads/membership_requests/<?php echo $request['payment_proof']; ?>" 
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