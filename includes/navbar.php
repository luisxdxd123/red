<?php
// Este archivo debe ser incluido después de las funciones de membresías
// Asegúrate de que $user_permissions y $unread_messages estén definidos antes de incluir este archivo

// Obtener la página actual para resaltar el enlace correspondiente
$current_page = basename($_SERVER['PHP_SELF']);

// Si los permisos no están definidos, obtenerlos
if (!isset($user_permissions)) {
    $query = "SELECT is_admin, can_create_pages, membership_type FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $user_permissions = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Si los mensajes no leídos no están definidos, obtenerlos
if (!isset($unread_messages)) {
    $unread_messages = getUnreadMessagesCount($_SESSION['user_id']);
}

// Si es admin o tiene membresía VIP, puede crear páginas automáticamente
if ($user_permissions['is_admin'] || $user_permissions['membership_type'] === 'vip') {
    $user_permissions['can_create_pages'] = true;
}

// Definir permisos basados en la membresía
if ($user_permissions['is_admin']) {
    // Administradores tienen acceso total
    $user_permissions['can_access_groups'] = true;
    $user_permissions['can_access_pages'] = true;
    $user_permissions['can_access_messages'] = true;
} else {
    // Usuarios normales - permisos basados en membresía
    $user_permissions['can_access_groups'] = ($user_permissions['membership_type'] === 'premium' || $user_permissions['membership_type'] === 'vip');
    $user_permissions['can_access_pages'] = ($user_permissions['membership_type'] === 'vip' || $user_permissions['can_create_pages']);
    $user_permissions['can_access_messages'] = ($user_permissions['membership_type'] === 'premium' || $user_permissions['membership_type'] === 'vip');
}
?>

<!-- Navbar moderno con Tailwind CSS -->
<nav class="bg-white border-b border-gray-100 shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <i class="fas fa-users text-blue-600 text-2xl mr-2 transform hover:scale-110 transition-transform duration-200"></i>
                    <span class="font-bold text-xl text-gray-800 hover:text-blue-600 transition-colors duration-200">Red Social</span>
                </div>

                <!-- Enlaces de navegación principales (Desktop) -->
                <div class="hidden md:ml-6 md:flex md:space-x-4">
                    <a href="index.php" 
                       class="<?php echo $current_page === 'index.php' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-all duration-200">
                        <i class="fas fa-home mr-2"></i>Inicio
                    </a>

                    <?php if ($user_permissions['can_access_groups']): ?>
                    <a href="groups.php" 
                       class="<?php echo $current_page === 'groups.php' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-all duration-200">
                        <i class="fas fa-users mr-2"></i>Grupos
                    </a>
                    <?php endif; ?>

                    <?php if ($user_permissions['can_access_pages']): ?>
                    <a href="pages.php" 
                       class="<?php echo $current_page === 'pages.php' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-all duration-200">
                        <i class="fas fa-flag mr-2"></i>Páginas
                    </a>
                    <?php endif; ?>

                    <?php if ($user_permissions['can_access_messages']): ?>
                    <a href="messages.php" 
                       class="<?php echo $current_page === 'messages.php' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-all duration-200 relative">
                        <i class="fas fa-envelope mr-2"></i>Mensajes
                        <?php if ($unread_messages > 0): ?>
                            <span class="absolute -top-1 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center animate-pulse">
                                <?php echo $unread_messages; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>

                    <?php if (!$user_permissions['is_admin']): ?>
                    <a href="memberships.php" 
                       class="<?php echo $current_page === 'memberships.php' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-all duration-200">
                        <i class="fas fa-crown mr-2"></i>Membresías
                    </a>
                    <?php endif; ?>

                    <a href="profile.php" 
                       class="<?php echo $current_page === 'profile.php' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-all duration-200">
                        <i class="fas fa-user mr-2"></i>Mi Perfil
                    </a>

                    <a href="users.php" 
                       class="<?php echo $current_page === 'users.php' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-all duration-200">
                        <i class="fas fa-user-friends mr-2"></i>Usuarios
                    </a>

                    <?php if ($user_permissions['is_admin']): ?>
                    <a href="admin_membership_requests.php" 
                       class="<?php echo $current_page === 'admin_membership_requests.php' ? 'border-purple-500 text-purple-600' : 'border-transparent text-purple-500 hover:text-purple-700 hover:border-purple-300'; ?> inline-flex items-center px-3 py-1 border-b-2 text-sm font-medium transition-all duration-200 bg-purple-50 rounded-t-lg">
                        <i class="fas fa-crown text-purple-500 mr-2"></i>Solicitudes
                        <?php
                        // Obtener cantidad de solicitudes pendientes
                        $query = "SELECT COUNT(*) as pending_count FROM membership_requests WHERE status = 'pending'";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        $pending_count = $stmt->fetch(PDO::FETCH_ASSOC)['pending_count'];
                        
                        if ($pending_count > 0): ?>
                            <span class="ml-2 bg-purple-100 text-purple-600 text-xs px-2 py-0.5 rounded-full">
                                <?php echo $pending_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Perfil y Logout (Desktop) -->
            <div class="hidden md:flex items-center space-x-4">
                <span class="text-sm font-medium text-gray-700">
                    ¡Hola, <span class="text-blue-600"><?php echo $_SESSION['first_name']; ?></span>!
                </span>
                <a href="../auth/logout.php" 
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                    <i class="fas fa-sign-out-alt mr-2"></i>Salir
                </a>
            </div>

            <!-- Botón de menú móvil -->
            <div class="flex items-center md:hidden">
                <button type="button" onclick="toggleMobileMenu()" 
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Menú móvil (pantalla completa) -->
        <div class="md:hidden hidden fixed inset-0 bg-white z-40" id="mobile-menu">
            <!-- Header del menú móvil -->
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
                <div class="flex items-center">
                    <div class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold">
                        <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1)); ?>
                    </div>
                    <div class="ml-3">
                        <div class="text-base font-medium text-gray-800">¡Hola, <?php echo $_SESSION['first_name']; ?>!</div>
                    </div>
                </div>
                <button onclick="toggleMobileMenu()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <!-- Enlaces de navegación móvil -->
            <div class="px-2 py-4 space-y-2 overflow-y-auto h-[calc(100vh-4rem)]">
                <a href="index.php" 
                   class="<?php echo $current_page === 'index.php' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50'; ?> flex items-center px-4 py-3 text-base font-medium rounded-lg transition-all duration-200">
                    <i class="fas fa-home mr-3 w-6 text-center"></i>
                    <span>Inicio</span>
                </a>

                <?php if ($user_permissions['can_access_groups']): ?>
                <a href="groups.php" 
                   class="<?php echo $current_page === 'groups.php' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50'; ?> flex items-center px-4 py-3 text-base font-medium rounded-lg transition-all duration-200">
                    <i class="fas fa-users mr-3 w-6 text-center"></i>
                    <span>Grupos</span>
                </a>
                <?php endif; ?>

                <?php if ($user_permissions['can_access_pages']): ?>
                <a href="pages.php" 
                   class="<?php echo $current_page === 'pages.php' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50'; ?> flex items-center px-4 py-3 text-base font-medium rounded-lg transition-all duration-200">
                    <i class="fas fa-flag mr-3 w-6 text-center"></i>
                    <span>Páginas</span>
                </a>
                <?php endif; ?>

                <?php if ($user_permissions['can_access_messages']): ?>
                <a href="messages.php" 
                   class="<?php echo $current_page === 'messages.php' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50'; ?> flex items-center px-4 py-3 text-base font-medium rounded-lg transition-all duration-200 relative">
                    <i class="fas fa-envelope mr-3 w-6 text-center"></i>
                    <span>Mensajes</span>
                    <?php if ($unread_messages > 0): ?>
                        <span class="ml-auto bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center animate-pulse">
                            <?php echo $unread_messages; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>

                <?php if (!$user_permissions['is_admin']): ?>
                <a href="memberships.php" 
                   class="<?php echo $current_page === 'memberships.php' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50'; ?> flex items-center px-4 py-3 text-base font-medium rounded-lg transition-all duration-200">
                    <i class="fas fa-crown mr-3 w-6 text-center"></i>
                    <span>Membresías</span>
                </a>
                <?php endif; ?>

                <a href="profile.php" 
                   class="<?php echo $current_page === 'profile.php' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50'; ?> flex items-center px-4 py-3 text-base font-medium rounded-lg transition-all duration-200">
                    <i class="fas fa-user mr-3 w-6 text-center"></i>
                    <span>Mi Perfil</span>
                </a>

                <a href="users.php" 
                   class="<?php echo $current_page === 'users.php' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50'; ?> flex items-center px-4 py-3 text-base font-medium rounded-lg transition-all duration-200">
                    <i class="fas fa-user-friends mr-3 w-6 text-center"></i>
                    <span>Usuarios</span>
                </a>

                <?php if ($user_permissions['is_admin']): ?>
                <a href="admin_membership_requests.php" 
                   class="<?php echo $current_page === 'admin_membership_requests.php' ? 'bg-purple-100 text-purple-600' : 'text-purple-600 hover:bg-purple-50'; ?> flex items-center px-4 py-3 text-base font-medium rounded-lg transition-all duration-200">
                    <i class="fas fa-crown text-purple-500 mr-3 w-6 text-center"></i>
                    <span>Solicitudes</span>
                    <?php if ($pending_count > 0): ?>
                        <span class="ml-auto bg-purple-200 text-purple-600 text-xs px-2 py-0.5 rounded-full">
                            <?php echo $pending_count; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>

                <!-- Separador -->
                <div class="border-t border-gray-200 my-4"></div>

                <!-- Botón de Cerrar Sesión -->
                <a href="../auth/logout.php" 
                   class="flex items-center px-4 py-3 text-base font-medium text-red-600 hover:bg-red-50 rounded-lg transition-all duration-200">
                    <i class="fas fa-sign-out-alt mr-3 w-6 text-center"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Modal de membresía bloqueada mejorado -->
<div id="membershipModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 backdrop-blur-sm z-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-xl shadow-2xl p-8 max-w-md w-full transform transition-all duration-300">
            <div class="text-center">
                <div class="bg-blue-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-lock text-blue-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Funcionalidad Bloqueada</h3>
                <p class="text-gray-600 mb-6">Esta función requiere una membresía Premium o VIP para acceder.</p>
                <div class="flex space-x-3">
                    <button onclick="closeMembershipModal()" 
                            class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all duration-200 font-medium">
                        Cerrar
                    </button>
                    <a href="memberships.php" 
                       class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 font-medium inline-flex items-center justify-center">
                        <i class="fas fa-crown mr-2"></i>Ver Membresías
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    const body = document.body;

    if (mobileMenu.classList.contains('hidden')) {
        // Abrir menú
        mobileMenu.classList.remove('hidden');
        mobileMenu.classList.add('animate-fade-in');
        body.style.overflow = 'hidden'; // Prevenir scroll
    } else {
        // Cerrar menú
        mobileMenu.classList.add('animate-fade-out');
        setTimeout(() => {
            mobileMenu.classList.add('hidden');
            mobileMenu.classList.remove('animate-fade-out');
            body.style.overflow = ''; // Restaurar scroll
        }, 200);
    }
}

// Cerrar menú móvil al hacer clic fuera
document.addEventListener('click', function(e) {
    const mobileMenu = document.getElementById('mobile-menu');
    const menuButton = document.querySelector('button[onclick="toggleMobileMenu()"]');
    
    if (!mobileMenu.contains(e.target) && !menuButton.contains(e.target) && !mobileMenu.classList.contains('hidden')) {
        toggleMobileMenu();
    }
});

// Cerrar menú móvil al cambiar el tamaño de la ventana
window.addEventListener('resize', function() {
    if (window.innerWidth >= 768) {
        const mobileMenu = document.getElementById('mobile-menu');
        const body = document.body;
        if (!mobileMenu.classList.contains('hidden')) {
            mobileMenu.classList.add('hidden');
            body.style.overflow = '';
        }
    }
});

// Funciones existentes del modal
function showMembershipModal() {
    const modal = document.getElementById('membershipModal');
    modal.classList.remove('hidden');
    modal.querySelector('.transform').classList.add('scale-100');
    modal.querySelector('.transform').classList.remove('scale-95');
}

function closeMembershipModal() {
    const modal = document.getElementById('membershipModal');
    modal.querySelector('.transform').classList.add('scale-95');
    modal.querySelector('.transform').classList.remove('scale-100');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

// Cerrar modal al hacer clic fuera
document.getElementById('membershipModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeMembershipModal();
    }
});
</script>

<style>
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes fadeOut {
    from {
        opacity: 1;
    }
    to {
        opacity: 0;
    }
}

.animate-fade-in {
    animation: fadeIn 0.2s ease-out forwards;
}

.animate-fade-out {
    animation: fadeOut 0.2s ease-out forwards;
}
</style> 