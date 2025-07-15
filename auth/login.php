<?php
require_once '../includes/functions.php';
redirectIfLoggedIn();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor, completa todos los campos';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id, username, password, first_name, last_name FROM users WHERE username = ? OR email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$username, $username]);
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                
                header('Location: ../dashboard/index.php');
                exit();
            } else {
                $error = 'Credenciales incorrectas';
            }
        } else {
            $error = 'Usuario no encontrado';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Red Social</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 via-sky-50 to-cyan-50 min-h-screen">
    <div class="min-h-screen flex flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden">
            <!-- Header con gradiente -->
            <div class="bg-gradient-to-r from-blue-600 via-blue-500 to-cyan-500 px-6 py-8 text-center">
                <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-white bg-opacity-25 backdrop-blur-sm mb-4 transform hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-users text-white text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-white mb-2">
                    ¡Bienvenido de nuevo!
                </h2>
                <p class="text-blue-100 text-sm">
                    Inicia sesión para conectar con tu comunidad
                </p>
            </div>

            <!-- Formulario -->
            <div class="px-6 py-8">
                <form method="POST" class="space-y-6">
                    <!-- Campo de Usuario -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                            Usuario o Email
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-blue-400"></i>
                            </div>
                            <input id="username" name="username" type="text" required 
                                   class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out sm:text-sm" 
                                   placeholder="Ingresa tu usuario o email">
                        </div>
                    </div>

                    <!-- Campo de Contraseña -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            Contraseña
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-blue-400"></i>
                            </div>
                            <input id="password" name="password" type="password" required 
                                   class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out sm:text-sm" 
                                   placeholder="Ingresa tu contraseña">
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-lg" role="alert">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-blue-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700"><?php echo $error; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Botón de Inicio de Sesión -->
                    <button type="submit" 
                            class="w-full flex justify-center items-center px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-blue-600 via-blue-500 to-cyan-500 hover:from-blue-700 hover:via-blue-600 hover:to-cyan-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transform hover:scale-[1.02] transition-all duration-150 shadow-lg">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Iniciar Sesión
                    </button>

                    <!-- Enlace de Registro -->
                    <div class="text-center mt-6">
                        <p class="text-sm text-gray-600">
                            ¿No tienes una cuenta?
                            <a href="register.php" class="font-medium text-blue-600 hover:text-blue-500 hover:underline transition duration-150 ease-in-out ml-1">
                                Regístrate aquí
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Animación suave al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelector('.max-w-md').classList.add('animate-fade-in-up');
    });
    </script>

    <style>
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in-up {
        animation: fadeInUp 0.6s ease-out;
    }
    </style>
</body>
</html> 