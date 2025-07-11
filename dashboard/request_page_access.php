<?php
require_once '../includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Verificar si el usuario ya tiene una solicitud pendiente
$query = "SELECT * FROM page_requests WHERE user_id = ? AND status = 'pending'";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$pending_request = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar si el usuario ya tiene acceso a páginas
$query = "SELECT can_create_pages FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$pending_request && !$user['can_create_pages']) {
    // Procesar el archivo
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $file_info = pathinfo($_FILES['payment_proof']['name']);
        $extension = strtolower($file_info['extension']);
        
        // Validar extensión
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
        if (in_array($extension, $allowed_extensions)) {
            // Generar nombre único
            $new_filename = uniqid() . '_' . time() . '.' . $extension;
            $upload_path = 'uploads/page_requests/';
            
            // Crear directorio si no existe
            if (!file_exists($upload_path)) {
                mkdir($upload_path, 0777, true);
            }
            
            // Mover archivo
            if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $upload_path . $new_filename)) {
                // Guardar solicitud en la base de datos
                $query = "INSERT INTO page_requests (user_id, payment_proof) VALUES (?, ?)";
                $stmt = $db->prepare($query);
                if ($stmt->execute([$_SESSION['user_id'], $new_filename])) {
                    header('Location: pages.php?request_sent=1');
                    exit;
                }
            }
        }
    }
    header('Location: request_page_access.php?error=1');
    exit;
}

// Obtener número de mensajes no leídos
$unread_messages = getUnreadMessagesCount($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Acceso a Páginas - Red Social</title>
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
                    <!-- Menú de navegación -->
                    <div class="hidden md:flex items-center space-x-1">
                        <a href="index.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300">
                            <i class="fas fa-home mr-1"></i>Inicio
                        </a>
                        <a href="groups.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300">
                            <i class="fas fa-users mr-1"></i>Grupos
                        </a>
                        <a href="pages.php" class="py-4 px-2 text-indigo-500 border-b-4 border-indigo-500 font-semibold">
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

    <div class="max-w-2xl mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Solicitar Acceso a Páginas</h1>

            <?php if ($user['can_create_pages']): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                    <p class="font-bold">¡Ya tienes acceso a la creación de páginas!</p>
                    <p>Puedes volver a la sección de páginas y comenzar a crear tu página.</p>
                    <a href="pages.php" class="inline-block mt-4 bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded transition duration-300">
                        <i class="fas fa-arrow-left mr-2"></i>Volver a Páginas
                    </a>
                </div>
            <?php elseif ($pending_request): ?>
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6">
                    <p class="font-bold">Ya tienes una solicitud pendiente</p>
                    <p>Tu solicitud está siendo revisada por nuestros administradores. Te notificaremos cuando sea aprobada.</p>
                    <p class="mt-2">Fecha de solicitud: <?php echo date('d/m/Y H:i', strtotime($pending_request['request_date'])); ?></p>
                    <a href="pages.php" class="inline-block mt-4 bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded transition duration-300">
                        <i class="fas fa-arrow-left mr-2"></i>Volver a Páginas
                    </a>
                </div>
            <?php else: ?>
                <?php if (isset($_GET['error'])): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                        <p class="font-bold">Error al procesar la solicitud</p>
                        <p>Por favor, verifica que el archivo sea válido y vuelve a intentarlo.</p>
                    </div>
                <?php endif; ?>

                <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6">
                    <p class="font-bold">Información Importante</p>
                    <p>Para crear páginas en nuestra plataforma, necesitas enviar un comprobante de pago. Una vez aprobado, podrás crear y administrar tus propias páginas.</p>
                </div>

                <form action="request_page_access.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Comprobante de Pago
                        </label>
                        <input type="file" name="payment_proof" required accept=".jpg,.jpeg,.png,.pdf"
                               class="block w-full text-sm text-gray-500
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
                        <a href="pages.php" 
                           class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded transition duration-300">
                            Cancelar
                        </a>
                        <button type="submit" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded transition duration-300">
                            Enviar Solicitud
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 