<?php
// Este archivo debe ser incluido después de las funciones de membresías
// Asegúrate de que $user_permissions y $unread_messages estén definidos antes de incluir este archivo

$current_page = basename($_SERVER['PHP_SELF']);
?>

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
                    <!-- Inicio - Siempre disponible -->
                    <a href="index.php" class="py-4 px-2 <?php echo $current_page === 'index.php' ? 'text-indigo-500 border-b-4 border-indigo-500' : 'text-gray-500 hover:text-indigo-500'; ?> font-semibold transition duration-300">
                        <i class="fas fa-home mr-1"></i>Inicio
                    </a>
                    
                    <!-- Grupos - Solo para Premium y VIP -->
                    <?php if ($user_permissions['can_access_groups']): ?>
                    <a href="groups.php" class="py-4 px-2 <?php echo $current_page === 'groups.php' || $current_page === 'group_detail.php' ? 'text-indigo-500 border-b-4 border-indigo-500' : 'text-gray-500 hover:text-indigo-500'; ?> font-semibold transition duration-300">
                        <i class="fas fa-users mr-1"></i>Grupos
                    </a>
                    <?php endif; ?>
                    
                    <!-- Páginas - Solo para VIP -->
                    <?php if ($user_permissions['can_access_pages']): ?>
                    <a href="pages.php" class="py-4 px-2 <?php echo $current_page === 'pages.php' || $current_page === 'page_detail.php' ? 'text-indigo-500 border-b-4 border-indigo-500' : 'text-gray-500 hover:text-indigo-500'; ?> font-semibold transition duration-300">
                        <i class="fas fa-flag mr-1"></i>Páginas
                    </a>
                    <?php endif; ?>
                    
                    <!-- Mensajes - Solo para Premium y VIP -->
                    <?php if ($user_permissions['can_access_messages']): ?>
                    <a href="messages.php" class="py-4 px-2 <?php echo $current_page === 'messages.php' ? 'text-indigo-500 border-b-4 border-indigo-500' : 'text-gray-500 hover:text-indigo-500'; ?> font-semibold transition duration-300 relative">
                        <i class="fas fa-envelope mr-1"></i>Mensajes
                        <?php if ($unread_messages > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                <?php echo $unread_messages > 9 ? '9+' : $unread_messages; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>
                    
                    <!-- Membresías - Siempre disponible -->
                    <a href="memberships.php" class="py-4 px-2 <?php echo $current_page === 'memberships.php' ? 'text-indigo-500 border-b-4 border-indigo-500' : 'text-gray-500 hover:text-indigo-500'; ?> font-semibold transition duration-300">
                        <i class="fas fa-crown mr-1"></i>Membresías
                        <?php if ($user_permissions['membership_type'] === 'basico'): ?>
                            <span class="ml-1 bg-yellow-500 text-white text-xs px-2 py-1 rounded-full">¡Mejora!</span>
                        <?php endif; ?>
                    </a>
                    
                    <!-- Mi Perfil - Siempre disponible -->
                    <a href="profile.php" class="py-4 px-2 <?php echo $current_page === 'profile.php' ? 'text-indigo-500 border-b-4 border-indigo-500' : 'text-gray-500 hover:text-indigo-500'; ?> font-semibold transition duration-300">
                        <i class="fas fa-user mr-1"></i>Mi Perfil
                    </a>
                    
                    <!-- Usuarios - Siempre disponible -->
                    <a href="users.php" class="py-4 px-2 <?php echo $current_page === 'users.php' || $current_page === 'user_profile.php' ? 'text-indigo-500 border-b-4 border-indigo-500' : 'text-gray-500 hover:text-indigo-500'; ?> font-semibold transition duration-300">
                        <i class="fas fa-user-friends mr-1"></i>Usuarios
                    </a>
                    
                    <!-- Solicitudes - Solo para admins y VIP -->
                    <?php if ($user_permissions['can_create_pages'] && isset($user_permissions['is_admin']) && $user_permissions['is_admin']): ?>
                    <a href="admin_page_requests.php" class="py-4 px-2 <?php echo $current_page === 'admin_page_requests.php' ? 'text-indigo-500 border-b-4 border-indigo-500' : 'text-gray-500 hover:text-indigo-500'; ?> font-semibold transition duration-300">
                        <i class="fas fa-tasks mr-1"></i>Solicitudes
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <!-- Indicador de membresía -->
                <div class="flex items-center">
                    <?php 
                    $membershipBadges = [
                        'basico' => 'bg-gray-100 text-gray-800',
                        'premium' => 'bg-yellow-100 text-yellow-800',
                        'vip' => 'bg-purple-100 text-purple-800'
                    ];
                    $membershipIcons = [
                        'basico' => 'fas fa-user',
                        'premium' => 'fas fa-star',
                        'vip' => 'fas fa-crown'
                    ];
                    ?>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo $membershipBadges[$user_permissions['membership_type']]; ?>">
                        <i class="<?php echo $membershipIcons[$user_permissions['membership_type']]; ?> mr-1"></i>
                        <?php echo ucfirst($user_permissions['membership_type']); ?>
                    </span>
                </div>
                
                <span class="text-gray-700">Hola, <?php echo $_SESSION['first_name']; ?>!</span>
                <a href="../auth/logout.php" class="py-2 px-2 font-medium text-gray-500 rounded hover:bg-red-500 hover:text-white transition duration-300">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Modal de membresía bloqueada -->
<div id="membershipModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <div class="text-center">
                <i class="fas fa-lock text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Funcionalidad Bloqueada</h3>
                <p class="text-gray-600 mb-4">Esta función requiere una membresía Premium o VIP.</p>
                <div class="flex space-x-3">
                    <button onclick="closeMembershipModal()" class="flex-1 bg-gray-200 text-gray-800 py-2 px-4 rounded-lg hover:bg-gray-300 transition duration-300">
                        Cerrar
                    </button>
                    <a href="memberships.php" class="flex-1 bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 transition duration-300 text-center">
                        Ver Membresías
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showMembershipModal() {
    document.getElementById('membershipModal').classList.remove('hidden');
}

function closeMembershipModal() {
    document.getElementById('membershipModal').classList.add('hidden');
}

// Cerrar modal al hacer clic fuera de él
document.getElementById('membershipModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeMembershipModal();
    }
});
</script> 