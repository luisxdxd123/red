<?php
require_once '../includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Obtener información del usuario actual
$user_permissions = getUserPermissions($_SESSION['user_id']);
$current_membership = getUserMembership($_SESSION['user_id']);
$membership_types = getMembershipTypes();

// Verificar si hay una solicitud pendiente
$query = "SELECT * FROM membership_requests WHERE user_id = ? AND status = 'pending'";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$pending_request = $stmt->fetch(PDO::FETCH_ASSOC);

// Procesar solicitud de membresía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['payment_proof'], $_POST['membership_type'])) {
        $membership_type = $_POST['membership_type'];
        
        // Obtener precio de la membresía
        $query = "SELECT price FROM membership_types WHERE name = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$membership_type]);
        $price = $stmt->fetchColumn();

        // Procesar el archivo
        if ($_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            $file_info = pathinfo($_FILES['payment_proof']['name']);
            $extension = strtolower($file_info['extension']);
            
            // Validar extensión
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
            if (in_array($extension, $allowed_extensions)) {
                $new_filename = uniqid() . '_' . time() . '.' . $extension;
                $upload_path = 'uploads/membership_requests/';
                
                // Crear directorio si no existe
                if (!file_exists($upload_path)) {
                    mkdir($upload_path, 0777, true);
                }
                
                // Mover archivo
                if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $upload_path . $new_filename)) {
                    // Guardar solicitud en la base de datos
                    $query = "INSERT INTO membership_requests (user_id, membership_type, payment_proof, amount) VALUES (?, ?, ?, ?)";
                    $stmt = $db->prepare($query);
                    if ($stmt->execute([$_SESSION['user_id'], $membership_type, $new_filename, $price])) {
                        $_SESSION['success'] = 'Solicitud enviada exitosamente. Un administrador revisará tu pago.';
                        header('Location: memberships.php');
                        exit;
                    }
                }
            }
        }
        $_SESSION['error'] = 'Error al procesar la solicitud. Por favor, intenta de nuevo.';
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
    <?php include '../includes/navbar.php'; ?>

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
                <div class="flex items-center space-x-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $membershipColors[$current_membership['membership_type']]; ?>">
                        <?php echo ucfirst($current_membership['membership_type']); ?>
                    </span>
                    <?php if ($current_membership['membership_type'] !== 'basico'): ?>
                        <button onclick="showCancelModal()" 
                                class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-red-600 hover:text-red-700 hover:bg-red-50 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                            </svg>
                            Cancelar Membresía
                        </button>
                    <?php endif; ?>
                </div>
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
                        <p class="text-gray-600">por año</p>
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
                    <?php elseif ($pending_request && $pending_request['membership_type'] === $membership['name']): ?>
                        <button class="w-full bg-yellow-500 text-white font-bold py-3 px-4 rounded-lg cursor-not-allowed">
                            <i class="fas fa-clock mr-2"></i>Solicitud en Revisión
                        </button>
                    <?php else: ?>
                        <button onclick="openRequestModal('<?php echo $membership['name']; ?>', <?php echo $membership['price']; ?>)" 
                                class="w-full <?php echo $membership['name'] === 'vip' ? 'bg-purple-600 hover:bg-purple-700' : 'bg-indigo-600 hover:bg-indigo-700'; ?> text-white font-bold py-3 px-4 rounded-lg transition duration-300">
                            <?php if ($membership['name'] === 'premium'): ?>
                                <i class="fas fa-upload mr-2"></i>Enviar Comprobante Premium
                            <?php else: ?>
                                <i class="fas fa-upload mr-2"></i>Enviar Comprobante VIP
                            <?php endif; ?>
                        </button>
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
                        <li>• Membresía Premium: 1 año por $499 MXN</li>
                        <li>• Membresía VIP: 1 año por $1,200 MXN</li>
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

    <!-- Modal para Solicitud de Membresía -->
    <div id="requestModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Solicitar Membresía <span id="membershipType" class="capitalize"></span></h3>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="membership_type" id="membershipTypeInput">
                    
                    <div class="bg-gray-50 p-4 rounded-lg mb-4">
                        <p class="text-gray-700 font-medium">Monto a pagar:</p>
                        <p class="text-2xl font-bold text-indigo-600">$<span id="membershipPrice"></span> MXN</p>
                        <p class="text-sm text-gray-500">por año</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Comprobante de Pago
                        </label>
                        <input type="file" name="payment_proof" required accept=".jpg,.jpeg,.png,.pdf"
                               class="mt-1 block w-full text-sm text-gray-500
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-full file:border-0
                                      file:text-sm file:font-semibold
                                      file:bg-indigo-50 file:text-indigo-700
                                      hover:file:bg-indigo-100">
                        <p class="mt-1 text-sm text-gray-500">
                            Formatos aceptados: JPG, JPEG, PNG, PDF. Tamaño máximo: 5MB
                        </p>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeRequestModal()" 
                                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded transition duration-300">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded transition duration-300">
                            Enviar Solicitud
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación de Cancelación -->
    <div id="cancelModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg max-w-md w-full mx-4">
            <div class="p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Cancelar Membresía</h3>
                    <p class="text-sm text-gray-500 mt-2">
                        ¿Estás seguro de que deseas cancelar tu membresía? Esta acción no se puede deshacer y perderás acceso a todas las funciones premium inmediatamente.
                    </p>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button onclick="hideCancelModal()" 
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors duration-200">
                        Volver
                    </button>
                    <form action="cancel_membership.php" method="POST" class="inline">
                        <button type="submit" 
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors duration-200">
                            Confirmar Cancelación
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openRequestModal(membershipType, price) {
            document.getElementById('membershipType').textContent = membershipType;
            document.getElementById('membershipTypeInput').value = membershipType;
            document.getElementById('membershipPrice').textContent = price.toLocaleString();
            document.getElementById('requestModal').classList.remove('hidden');
        }
        
        function closeRequestModal() {
            document.getElementById('requestModal').classList.add('hidden');
        }

        function showCancelModal() {
            const modal = document.getElementById('cancelModal');
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function hideCancelModal() {
            const modal = document.getElementById('cancelModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

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