<?php
require_once '../includes/functions.php';
redirectIfLoggedIn();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = cleanInput($_POST['username']);
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = cleanInput($_POST['first_name']);
    $last_name = cleanInput($_POST['last_name']);
    
    // Validaciones
    if (empty($username)) $errors[] = 'El nombre de usuario es requerido';
    if (empty($email)) $errors[] = 'El email es requerido';
    if (empty($password)) $errors[] = 'La contraseña es requerida';
    if (empty($first_name)) $errors[] = 'El nombre es requerido';
    if (empty($last_name)) $errors[] = 'El apellido es requerido';
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email no es válido';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'La contraseña debe tener al menos 6 caracteres';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Las contraseñas no coinciden';
    }
    
    if (empty($errors)) {
        $database = new Database();
        $db = $database->getConnection();
        
        // Verificar si el username o email ya existen
        $query = "SELECT id FROM users WHERE username = ? OR email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            $errors[] = 'El nombre de usuario o email ya están en uso';
        } else {
            // Crear el usuario
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO users (username, email, password, first_name, last_name) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$username, $email, $hashed_password, $first_name, $last_name])) {
                $success = true;
            } else {
                $errors[] = 'Error al crear la cuenta';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Red Social</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-purple-50 to-pink-100 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-purple-600">
                    <i class="fas fa-user-plus text-white text-xl"></i>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Crea tu cuenta
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    ¿Ya tienes cuenta?
                    <a href="login.php" class="font-medium text-purple-600 hover:text-purple-500">
                        Inicia sesión aquí
                    </a>
                </p>
            </div>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">¡Éxito!</strong>
                    <span class="block sm:inline">Tu cuenta ha sido creada. <a href="login.php" class="font-bold underline">Inicia sesión</a></span>
                </div>
            <?php else: ?>
                <form class="mt-8 space-y-6" method="POST">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700">Nombre</label>
                                <input id="first_name" name="first_name" type="text" required 
                                       value="<?php echo isset($_POST['first_name']) ? $_POST['first_name'] : ''; ?>"
                                       class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500 focus:z-10 sm:text-sm" 
                                       placeholder="Nombre">
                            </div>
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700">Apellido</label>
                                <input id="last_name" name="last_name" type="text" required 
                                       value="<?php echo isset($_POST['last_name']) ? $_POST['last_name'] : ''; ?>"
                                       class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500 focus:z-10 sm:text-sm" 
                                       placeholder="Apellido">
                            </div>
                        </div>
                        
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700">Usuario</label>
                            <input id="username" name="username" type="text" required 
                                   value="<?php echo isset($_POST['username']) ? $_POST['username'] : ''; ?>"
                                   class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500 focus:z-10 sm:text-sm" 
                                   placeholder="Nombre de usuario">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input id="email" name="email" type="email" required 
                                   value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>"
                                   class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500 focus:z-10 sm:text-sm" 
                                   placeholder="Email">
                        </div>
                        
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                            <input id="password" name="password" type="password" required 
                                   class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500 focus:z-10 sm:text-sm" 
                                   placeholder="Contraseña (mín. 6 caracteres)">
                        </div>
                        
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirmar Contraseña</label>
                            <input id="confirm_password" name="confirm_password" type="password" required 
                                   class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500 focus:z-10 sm:text-sm" 
                                   placeholder="Confirmar contraseña">
                        </div>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <ul class="list-disc list-inside">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div>
                        <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition duration-150 ease-in-out">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i class="fas fa-user-plus text-purple-500 group-hover:text-purple-400"></i>
                            </span>
                            Crear Cuenta
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 