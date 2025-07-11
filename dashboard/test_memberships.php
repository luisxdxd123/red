<?php
require_once '../includes/functions.php';
requireLogin();

// Obtener información del usuario actual
$user_permissions = getUserPermissions($_SESSION['user_id']);
$current_membership = getUserMembership($_SESSION['user_id']);
$membership_types = getMembershipTypes();

// Procesar cambio de membresía para pruebas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_membership'])) {
    $membership_type = $_POST['membership_type'];
    $result = processMembershipPurchase($_SESSION['user_id'], $membership_type);
    
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
        header('Location: test_memberships.php');
        exit();
    } else {
        $_SESSION['error'] = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Membresías - Red Social</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 py-8">
        
        <!-- Notificaciones -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-4">
                <i class="fas fa-flask text-blue-500 mr-2"></i>
                Prueba del Sistema de Membresías
            </h1>
            <p class="text-gray-600">Esta página te permite probar el sistema de membresías y ver cómo cambian los permisos.</p>
        </div>

        <!-- Información del Usuario Actual -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Usuario Actual: <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></h2>
            
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Membresía Actual -->
                <div>
                    <h3 class="text-lg font-semibold mb-3">Membresía Actual</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p><strong>Tipo:</strong> <span class="capitalize"><?php echo $current_membership['membership_type']; ?></span></p>
                        <p><strong>Expira:</strong> 
                            <?php if ($current_membership['membership_expires_at']): ?>
                                <?php echo date('d/m/Y H:i', strtotime($current_membership['membership_expires_at'])); ?>
                            <?php else: ?>
                                <span class="text-green-600">Permanente</span>
                            <?php endif; ?>
                        </p>
                        <p><strong>Creada:</strong> 
                            <?php if ($current_membership['membership_created_at']): ?>
                                <?php echo date('d/m/Y H:i', strtotime($current_membership['membership_created_at'])); ?>
                            <?php else: ?>
                                <span class="text-gray-500">No definida</span>
                            <?php endif; ?>
                        </p>
                        <p><strong>Es Admin:</strong> <span class="<?php echo $current_membership['is_admin'] ? 'text-green-600' : 'text-red-600'; ?>"><?php echo $current_membership['is_admin'] ? 'Sí' : 'No'; ?></span></p>
                        <p><strong>Puede crear páginas:</strong> <span class="<?php echo $current_membership['can_create_pages'] ? 'text-green-600' : 'text-red-600'; ?>"><?php echo $current_membership['can_create_pages'] ? 'Sí' : 'No'; ?></span></p>
                    </div>
                </div>

                <!-- Permisos Actuales -->
                <div>
                    <h3 class="text-lg font-semibold mb-3">Permisos Actuales</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <i class="fas fa-<?php echo $user_permissions['can_access_timeline'] ? 'check text-green-500' : 'times text-red-500'; ?> mr-2"></i>
                                Timeline
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-<?php echo $user_permissions['can_access_profile'] ? 'check text-green-500' : 'times text-red-500'; ?> mr-2"></i>
                                Perfil
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-<?php echo $user_permissions['can_access_users'] ? 'check text-green-500' : 'times text-red-500'; ?> mr-2"></i>
                                Usuarios
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-<?php echo $user_permissions['can_access_groups'] ? 'check text-green-500' : 'times text-red-500'; ?> mr-2"></i>
                                Grupos
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-<?php echo $user_permissions['can_access_messages'] ? 'check text-green-500' : 'times text-red-500'; ?> mr-2"></i>
                                Mensajes
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-<?php echo $user_permissions['can_access_pages'] ? 'check text-green-500' : 'times text-red-500'; ?> mr-2"></i>
                                Páginas
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-<?php echo $user_permissions['can_create_pages'] ? 'check text-green-500' : 'times text-red-500'; ?> mr-2"></i>
                                Crear Páginas
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-<?php echo $user_permissions['is_admin'] ? 'check text-green-500' : 'times text-red-500'; ?> mr-2"></i>
                                Admin
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Probar Diferentes Membresías -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Probar Diferentes Membresías</h2>
            <p class="text-gray-600 mb-4">Haz clic en los botones para cambiar tu membresía y ver cómo cambian los permisos:</p>
            
            <div class="grid md:grid-cols-3 gap-4">
                <?php foreach ($membership_types as $membership): ?>
                <div class="border <?php echo $current_membership['membership_type'] === $membership['name'] ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'; ?> rounded-lg p-4">
                    <h3 class="font-semibold capitalize mb-2"><?php echo $membership['name']; ?></h3>
                    <p class="text-sm text-gray-600 mb-3"><?php echo $membership['description']; ?></p>
                    <p class="text-lg font-bold mb-3">$<?php echo number_format($membership['price'], 0); ?> MXN</p>
                    
                    <?php if ($current_membership['membership_type'] === $membership['name']): ?>
                        <button class="w-full bg-gray-300 text-gray-600 py-2 px-4 rounded-lg cursor-not-allowed">
                            Membresía Actual
                        </button>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="membership_type" value="<?php echo $membership['name']; ?>">
                            <button type="submit" name="test_membership" 
                                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg transition duration-300">
                                Cambiar a <?php echo ucfirst($membership['name']); ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Enlaces de Prueba -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Probar Acceso a Páginas</h2>
            <p class="text-gray-600 mb-4">Intenta acceder a diferentes páginas para ver las restricciones:</p>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="index.php" class="bg-green-100 hover:bg-green-200 text-green-800 py-3 px-4 rounded-lg text-center transition duration-300">
                    <i class="fas fa-home block text-2xl mb-2"></i>
                    Inicio
                    <small class="block">Siempre disponible</small>
                </a>
                
                <a href="groups.php" class="<?php echo $user_permissions['can_access_groups'] ? 'bg-green-100 hover:bg-green-200 text-green-800' : 'bg-red-100 hover:bg-red-200 text-red-800'; ?> py-3 px-4 rounded-lg text-center transition duration-300">
                    <i class="fas fa-users block text-2xl mb-2"></i>
                    Grupos
                    <small class="block"><?php echo $user_permissions['can_access_groups'] ? 'Disponible' : 'Requiere Premium'; ?></small>
                </a>
                
                <a href="messages.php" class="<?php echo $user_permissions['can_access_messages'] ? 'bg-green-100 hover:bg-green-200 text-green-800' : 'bg-red-100 hover:bg-red-200 text-red-800'; ?> py-3 px-4 rounded-lg text-center transition duration-300">
                    <i class="fas fa-envelope block text-2xl mb-2"></i>
                    Mensajes
                    <small class="block"><?php echo $user_permissions['can_access_messages'] ? 'Disponible' : 'Requiere Premium'; ?></small>
                </a>
                
                <a href="pages.php" class="<?php echo $user_permissions['can_access_pages'] ? 'bg-green-100 hover:bg-green-200 text-green-800' : 'bg-red-100 hover:bg-red-200 text-red-800'; ?> py-3 px-4 rounded-lg text-center transition duration-300">
                    <i class="fas fa-flag block text-2xl mb-2"></i>
                    Páginas
                    <small class="block"><?php echo $user_permissions['can_access_pages'] ? 'Disponible' : 'Requiere VIP'; ?></small>
                </a>
            </div>
            
            <div class="mt-6 text-center">
                <a href="memberships.php" class="bg-indigo-600 hover:bg-indigo-700 text-white py-3 px-6 rounded-lg transition duration-300">
                    <i class="fas fa-crown mr-2"></i>
                    Ir a Página de Membresías
                </a>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide notifications after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.bg-green-100, .bg-red-100');
            alerts.forEach(alert => {
                if (alert.getAttribute('role') === 'alert') {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);
    </script>
</body>
</html> 