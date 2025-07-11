<?php
require_once '../includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Obtener información del usuario actual
$user_permissions = getUserPermissions($_SESSION['user_id']);
$current_membership = getUserMembership($_SESSION['user_id']);
$membership_types = getMembershipTypes();

// Procesar compra de membresía
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase_membership'])) {
    $membership_type = $_POST['membership_type'];
    $result = processMembershipPurchase($_SESSION['user_id'], $membership_type);
    
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
        header('Location: memberships.php');
        exit();
    } else {
        $_SESSION['error'] = $result['message'];
    }
}

// Obtener número de mensajes no leídos
$unread_messages = getUnreadMessagesCount($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membresías - Red Social</title>
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
                        <a href="index.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300">
                            <i class="fas fa-home mr-1"></i>Inicio
                        </a>
                        <?php if ($user_permissions['can_access_groups']): ?>
                        <a href="groups.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300">
                            <i class="fas fa-users mr-1"></i>Grupos
                        </a>
                        <?php endif; ?>
                        <?php if ($user_permissions['can_access_pages']): ?>
                        <a href="pages.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300">
                            <i class="fas fa-flag mr-1"></i>Páginas
                        </a>
                        <?php endif; ?>
                        <?php if ($user_permissions['can_access_messages']): ?>
                        <a href="messages.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300 relative">
                            <i class="fas fa-envelope mr-1"></i>Mensajes
                            <?php if ($unread_messages > 0): ?>
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                    <?php echo $unread_messages > 9 ? '9+' : $unread_messages; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <?php endif; ?>
                        <a href="memberships.php" class="py-4 px-2 text-indigo-500 border-b-4 border-indigo-500 font-semibold">
                            <i class="fas fa-crown mr-1"></i>Membresías
                        </a>
                        <a href="profile.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300">
                            <i class="fas fa-user mr-1"></i>Mi Perfil
                        </a>
                        <a href="users.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300">
                            <i class="fas fa-user-friends mr-1"></i>Usuarios
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

    <!-- Notificaciones -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="max-w-4xl mx-auto px-4 pt-4">
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="max-w-4xl mx-auto px-4 pt-4">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                <i class="fas fa-crown text-yellow-500 mr-3"></i>
                Membresías Premium
            </h1>
            <p class="text-xl text-gray-600">Desbloquea todas las funciones de nuestra red social</p>
        </div>

        <!-- Membresía Actual -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Tu Membresía Actual</h2>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <?php 
                    $membershipIcons = [
                        'basico' => 'fas fa-user text-gray-500',
                        'premium' => 'fas fa-star text-yellow-500',
                        'vip' => 'fas fa-crown text-purple-500'
                    ];
                    $membershipColors = [
                        'basico' => 'bg-gray-100 text-gray-800',
                        'premium' => 'bg-yellow-100 text-yellow-800',
                        'vip' => 'bg-purple-100 text-purple-800'
                    ];
                    ?>
                    <i class="<?php echo $membershipIcons[$current_membership['membership_type']]; ?> text-3xl mr-4"></i>
                    <div>
                        <h3 class="text-xl font-semibold capitalize"><?php echo $current_membership['membership_type']; ?></h3>
                        <?php if ($current_membership['membership_expires_at']): ?>
                            <p class="text-gray-600">Expira: <?php echo date('d/m/Y', strtotime($current_membership['membership_expires_at'])); ?></p>
                        <?php else: ?>
                            <p class="text-gray-600">Membresía permanente</p>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $membershipColors[$current_membership['membership_type']]; ?>">
                    <?php echo ucfirst($current_membership['membership_type']); ?>
                </span>
            </div>
        </div>

        <!-- Planes de Membresía -->
        <div class="grid md:grid-cols-3 gap-8">
            <?php foreach ($membership_types as $membership): ?>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden <?php echo $membership['name'] === 'vip' ? 'border-4 border-purple-500 transform scale-105' : ''; ?>">
                <?php if ($membership['name'] === 'vip'): ?>
                <div class="bg-purple-500 text-white text-center py-2">
                    <span class="font-bold">¡MÁS POPULAR!</span>
                </div>
                <?php endif; ?>
                
                <div class="p-6">
                    <div class="text-center mb-6">
                        <?php if ($membership['name'] === 'basico'): ?>
                            <i class="fas fa-user text-gray-500 text-4xl mb-4"></i>
                        <?php elseif ($membership['name'] === 'premium'): ?>
                            <i class="fas fa-star text-yellow-500 text-4xl mb-4"></i>
                        <?php else: ?>
                            <i class="fas fa-crown text-purple-500 text-4xl mb-4"></i>
                        <?php endif; ?>
                        
                        <h3 class="text-2xl font-bold capitalize mb-2"><?php echo $membership['name']; ?></h3>
                        <div class="text-3xl font-bold mb-2">
                            $<?php echo number_format($membership['price'], 0); ?>
                            <span class="text-lg text-gray-500 font-normal">MXN</span>
                        </div>
                        <?php if ($membership['name'] !== 'basico'): ?>
                        <p class="text-gray-600">por <?php echo $membership['duration_months']; ?> mes(es)</p>
                        <?php endif; ?>
                    </div>

                    <div class="mb-6">
                        <h4 class="font-semibold mb-3">Características incluidas:</h4>
                        <ul class="space-y-2">
                            <?php 
                            $features = json_decode($membership['features'], true);
                            foreach ($features as $feature): 
                            ?>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span class="text-sm"><?php echo $feature; ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <?php if ($current_membership['membership_type'] === $membership['name']): ?>
                        <button class="w-full bg-gray-300 text-gray-600 font-bold py-3 px-4 rounded-lg cursor-not-allowed">
                            Membresía Actual
                        </button>
                    <?php elseif ($membership['name'] === 'basico'): ?>
                        <button class="w-full bg-gray-300 text-gray-600 font-bold py-3 px-4 rounded-lg cursor-not-allowed">
                            Membresía Gratuita
                        </button>
                    <?php else: ?>
                        <form method="POST" class="w-full">
                            <input type="hidden" name="membership_type" value="<?php echo $membership['name']; ?>">
                            <button type="submit" name="purchase_membership" 
                                    class="w-full <?php echo $membership['name'] === 'vip' ? 'bg-purple-600 hover:bg-purple-700' : 'bg-indigo-600 hover:bg-indigo-700'; ?> text-white font-bold py-3 px-4 rounded-lg transition duration-300"
                                    onclick="return confirm('¿Estás seguro de que quieres comprar esta membresía por $<?php echo number_format($membership['price'], 0); ?> MXN?')">
                                <?php if ($membership['name'] === 'premium'): ?>
                                    <i class="fas fa-shopping-cart mr-2"></i>Obtener Premium
                                <?php else: ?>
                                    <i class="fas fa-crown mr-2"></i>Obtener VIP
                                <?php endif; ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Información adicional -->
        <div class="mt-12 bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">
                <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                Información Importante
            </h2>
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-semibold mb-2">
                        <i class="fas fa-clock text-green-500 mr-2"></i>
                        Duración de Membresías
                    </h3>
                    <ul class="text-gray-600 space-y-1">
                        <li>• Membresía Básica: Permanente y gratuita</li>
                        <li>• Membresía Premium: 1 mes por $2,000 MXN</li>
                        <li>• Membresía VIP: 1 mes por $5,000 MXN</li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-semibold mb-2">
                        <i class="fas fa-shield-alt text-blue-500 mr-2"></i>
                        Políticas
                    </h3>
                    <ul class="text-gray-600 space-y-1">
                        <li>• Las membresías se activan inmediatamente</li>
                        <li>• Puedes cambiar de plan en cualquier momento</li>
                        <li>• El acceso a funciones premium se mantiene hasta la expiración</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide notifications after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.bg-green-100, .bg-red-100');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html> 