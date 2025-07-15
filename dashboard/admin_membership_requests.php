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

// Configuración de paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Filtros
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$membership_filter = isset($_GET['membership_type']) ? $_GET['membership_type'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'], $_POST['request_id'])) {
        $request_id = $_POST['request_id'];
        $action = $_POST['action'];
        $admin_notes = $_POST['admin_notes'] ?? '';

        if (in_array($action, ['approve', 'reject', 'cancel'])) {
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
            $status = $action === 'approve' ? 'approved' : ($action === 'reject' ? 'rejected' : 'cancelled');
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

                $_SESSION['success'] = "Solicitud aprobada exitosamente.";
            } elseif ($action === 'reject') {
                $_SESSION['success'] = "Solicitud rechazada.";
            } else {
                $_SESSION['success'] = "Solicitud cancelada.";
            }
            
            header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
            exit;
        }
    }
}

// Construir la consulta con filtros
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "mr.status = ?";
    $params[] = $status_filter;
}

if ($membership_filter) {
    $where_conditions[] = "mr.membership_type = ?";
    $params[] = $membership_filter;
}

if ($date_filter) {
    switch ($date_filter) {
        case 'today':
            $where_conditions[] = "DATE(mr.request_date) = CURDATE()";
            break;
        case 'week':
            $where_conditions[] = "mr.request_date >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
            break;
        case 'month':
            $where_conditions[] = "mr.request_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
            break;
    }
}

if ($search) {
    $where_conditions[] = "(u.username LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

// Construir la consulta final
$query = "SELECT mr.*, u.username, u.first_name, u.last_name, u.email 
          FROM membership_requests mr 
          JOIN users u ON mr.user_id = u.id";

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

// Obtener total de registros para paginación
$count_query = str_replace("mr.*, u.username, u.first_name, u.last_name, u.email", "COUNT(*) as total", $query);
$stmt = $db->prepare($count_query);
$stmt->execute($params);
$total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $per_page);

// Agregar ordenamiento y límite
$query .= " ORDER BY mr.request_date DESC LIMIT $offset, $per_page";

// Ejecutar consulta final
$stmt = $db->prepare($query);
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas
$stats_query = "SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_requests,
                SUM(CASE WHEN status = 'user_cancelled' THEN 1 ELSE 0 END) as user_cancelled_requests
                FROM membership_requests";
$stmt = $db->prepare($stats_query);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener tipos de membresía únicos
$memberships_query = "SELECT DISTINCT membership_type FROM membership_requests";
$stmt = $db->prepare($memberships_query);
$stmt->execute();
$membership_types = $stmt->fetchAll(PDO::FETCH_COLUMN);

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

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Notificaciones -->
        <?php if (isset($_SESSION['success']) || isset($_SESSION['error'])): ?>
            <div id="notifications" class="mb-4">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Encabezado y Estadísticas -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">Panel de Solicitudes</h1>
            
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <!-- Total Solicitudes -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-blue-50">
                            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Total</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_requests']; ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Pendientes -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-yellow-50">
                            <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Pendientes</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['pending_requests']; ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Aprobadas -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-green-50">
                            <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Aprobadas</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['approved_requests']; ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Rechazadas -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-red-50">
                            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Rechazadas</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['rejected_requests']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Canceladas por Admin -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-gray-50">
                            <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Canceladas Admin</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['cancelled_requests']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Canceladas por Usuario -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-orange-50">
                            <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Canceladas Usuario</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['user_cancelled_requests']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Nombre, email..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pendientes</option>
                        <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Aprobadas</option>
                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rechazadas</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Canceladas por Admin</option>
                        <option value="user_cancelled" <?php echo $status_filter === 'user_cancelled' ? 'selected' : ''; ?>>Canceladas por Usuario</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Membresía</label>
                    <select name="membership_type" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todas</option>
                        <?php foreach ($membership_types as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo $membership_filter === $type ? 'selected' : ''; ?>>
                                <?php echo ucfirst($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha</label>
                    <select name="date" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todas</option>
                        <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Hoy</option>
                        <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>Última semana</option>
                        <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>Último mes</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <i class="fas fa-search mr-2"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabla de Solicitudes -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <?php if (empty($requests)): ?>
                <div class="text-center py-8">
                    <i class="fas fa-clipboard-check text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-600">No hay solicitudes que coincidan con los filtros</p>
                </div>
            <?php else: ?>
                <?php
                // Verificar si hay solicitudes pendientes
                $has_pending = false;
                foreach ($requests as $request) {
                    if ($request['status'] === 'pending') {
                        $has_pending = true;
                        break;
                    }
                }
                ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Detalles de Membresía</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comprobante</th>
                                <?php if ($has_pending): ?>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($requests as $request): ?>
                                <tr class="hover:bg-gray-50">
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
                                    <td class="px-6 py-4">
                                        <div class="text-sm">
                                            <div class="font-medium text-gray-900">
                                                <span class="capitalize"><?php echo $request['membership_type']; ?></span>
                                                <span class="text-gray-500 mx-2">•</span>
                                                <span>$<?php echo number_format($request['amount'], 2); ?> MXN</span>
                                            </div>
                                            <div class="text-gray-500 mt-1">
                                                <?php echo date('d M Y, H:i', strtotime($request['request_date'])); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $status_class = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'approved' => 'bg-green-100 text-green-800',
                                            'rejected' => 'bg-red-100 text-red-800',
                                            'cancelled' => 'bg-gray-100 text-gray-700',
                                            'user_cancelled' => 'bg-orange-100 text-orange-800'
                                        ][$request['status']];
                                        $status_text = [
                                            'pending' => 'Pendiente',
                                            'approved' => 'Aprobada',
                                            'rejected' => 'Rechazada',
                                            'cancelled' => 'Cancelada por Admin',
                                            'user_cancelled' => 'Cancelada por Usuario'
                                        ][$request['status']];
                                        ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <a href="uploads/membership_requests/<?php echo $request['payment_proof']; ?>" 
                                           target="_blank"
                                           class="inline-flex items-center text-sm text-blue-600 hover:text-blue-900">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            Ver
                                        </a>
                                    </td>
                                    <?php if ($has_pending && $request['status'] === 'pending'): ?>
                                        <td class="px-6 py-4">
                                            <div class="flex space-x-2">
                                                <button onclick="showConfirmationModal(<?php echo $request['id']; ?>, 'approve')" 
                                                        class="inline-flex items-center px-3 py-1 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                    Aprobar
                                                </button>
                                                <button onclick="showConfirmationModal(<?php echo $request['id']; ?>, 'reject')"
                                                        class="inline-flex items-center px-3 py-1 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">
                                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                    Rechazar
                                                </button>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Modal de Confirmación -->
        <div id="confirmationModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
            <div class="bg-white rounded-lg max-w-md w-full mx-4">
                <div class="p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-medium text-gray-900" id="modalTitle"></h3>
                        <p class="text-sm text-gray-500 mt-2" id="modalMessage"></p>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button onclick="hideConfirmationModal()" 
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors duration-200">
                            Cancelar
                        </button>
                        <form id="actionForm" method="POST" class="inline">
                            <input type="hidden" name="request_id" id="requestId">
                            <input type="hidden" name="action" id="actionType">
                            <button type="submit" id="confirmButton" 
                                    class="px-4 py-2 rounded-md text-white transition-colors duration-200">
                                Confirmar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        function showConfirmationModal(requestId, action) {
            const modal = document.getElementById('confirmationModal');
            const title = document.getElementById('modalTitle');
            const message = document.getElementById('modalMessage');
            const confirmButton = document.getElementById('confirmButton');
            const requestIdInput = document.getElementById('requestId');
            const actionTypeInput = document.getElementById('actionType');

            requestIdInput.value = requestId;
            actionTypeInput.value = action;

            if (action === 'approve') {
                title.textContent = 'Aprobar Solicitud';
                message.textContent = '¿Estás seguro de que deseas aprobar esta solicitud de membresía?';
                confirmButton.className = 'px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md transition-colors duration-200';
            } else {
                title.textContent = 'Rechazar Solicitud';
                message.textContent = '¿Estás seguro de que deseas rechazar esta solicitud de membresía?';
                confirmButton.className = 'px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors duration-200';
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function hideConfirmationModal() {
            const modal = document.getElementById('confirmationModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Auto-hide para notificaciones
        const notifications = document.getElementById('notifications');
        if (notifications) {
            setTimeout(function() {
                notifications.style.transition = 'opacity 0.5s ease-out';
                notifications.style.opacity = '0';
                setTimeout(() => notifications.remove(), 500);
            }, 5000);
        }
    </script>
</body>
</html> 