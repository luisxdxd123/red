<?php
require_once '../includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Obtener conversaciones del usuario
$conversations = getUserConversations($_SESSION['user_id']);

// Si hay un parámetro 'user', verificar que puede enviar mensaje
$selected_conversation = null;
$selected_user = null;
$error_message = null;

if (isset($_GET['user']) && is_numeric($_GET['user'])) {
    $other_user_id = $_GET['user'];
    
    // Obtener información del otro usuario
    $query = "SELECT * FROM users WHERE id = ? AND id != ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$other_user_id, $_SESSION['user_id']]);
    $selected_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selected_user) {
        // Verificar si puede enviar mensaje (debe seguir al usuario)
        if (canSendMessageToUser($_SESSION['user_id'], $other_user_id)) {
            $conversation_id = getOrCreateConversation($_SESSION['user_id'], $other_user_id);
            $selected_conversation = $conversation_id;
        } else {
            $error_message = "Solo puedes enviar mensajes a usuarios que sigues. <a href='users.php' class='text-indigo-600 underline'>Descubre usuarios para seguir</a>";
        }
    }
} elseif (isset($_GET['conversation']) && is_numeric($_GET['conversation'])) {
    $selected_conversation = $_GET['conversation'];
}

// Obtener usuarios seguidos para nueva conversación
$followed_users = getFollowedUsersForMessaging($_SESSION['user_id']);

// Obtener número de mensajes no leídos
$unread_messages = getUnreadMessagesCount($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensajes - Red Social</title>
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
                        <a href="groups.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300">
                            <i class="fas fa-users mr-1"></i>Grupos
                        </a>
                        <a href="pages.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300">
                            <i class="fas fa-flag mr-1"></i>Páginas
                        </a>
                        <a href="messages.php" class="py-4 px-2 text-indigo-500 border-b-4 border-indigo-500 font-semibold">
                            <i class="fas fa-envelope mr-1"></i>Mensajes
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

    <div class="max-w-6xl mx-auto px-4 py-8">
        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
                <strong class="font-bold">No puedes enviar este mensaje:</strong>
                <span class="block sm:inline"><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md h-96 flex">
            <!-- Lista de Conversaciones -->
            <div class="w-1/3 border-r border-gray-200">
                <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900">Mensajes</h2>
                    <button onclick="openNewMessageModal()" class="text-indigo-600 hover:text-indigo-700" title="Nueva conversación">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                
                <div class="overflow-y-auto h-80">
                    <?php if (empty($conversations)): ?>
                        <div class="p-4 text-center text-gray-500">
                            <i class="fas fa-comments text-3xl mb-2"></i>
                            <p>No tienes conversaciones aún</p>
                            <p class="text-sm mb-3">Envía mensajes a usuarios que sigues</p>
                            <button onclick="openNewMessageModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-3 py-1 rounded transition duration-300">
                                Nueva conversación
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conversation): ?>
                            <div class="p-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer conversation-item <?php echo $selected_conversation == $conversation['id'] ? 'bg-indigo-50' : ''; ?>"
                                 onclick="selectConversation(<?php echo $conversation['id']; ?>)">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold text-sm mr-3">
                                        <?php echo strtoupper(substr($conversation['other_user_first_name'], 0, 1) . substr($conversation['other_user_last_name'], 0, 1)); ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-center">
                                            <h4 class="font-medium text-gray-900 truncate">
                                                <?php echo $conversation['other_user_first_name'] . ' ' . $conversation['other_user_last_name']; ?>
                                            </h4>
                                            <?php if ($conversation['unread_count'] > 0): ?>
                                                <span class="bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                                    <?php echo $conversation['unread_count'] > 9 ? '9+' : $conversation['unread_count']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-sm text-gray-600 truncate">@<?php echo $conversation['other_user_username']; ?></p>
                                        <?php if ($conversation['last_message']): ?>
                                            <p class="text-xs text-gray-500 truncate mt-1"><?php echo htmlspecialchars($conversation['last_message']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Área de Chat -->
            <div class="flex-1">
                <?php if ($selected_conversation): ?>
                    <div id="chat-area" class="flex flex-col h-full">
                        <!-- Header del Chat -->
                        <div class="p-4 border-b border-gray-200 bg-gray-50">
                            <?php if ($selected_user): ?>
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold text-sm mr-3">
                                        <?php echo strtoupper(substr($selected_user['first_name'], 0, 1) . substr($selected_user['last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-gray-900"><?php echo $selected_user['first_name'] . ' ' . $selected_user['last_name']; ?></h3>
                                        <p class="text-sm text-gray-600">@<?php echo $selected_user['username']; ?></p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- Obtener info del chat seleccionado -->
                                <?php 
                                foreach ($conversations as $conv) {
                                    if ($conv['id'] == $selected_conversation) {
                                        echo '<div class="flex items-center">
                                                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold text-sm mr-3">
                                                    ' . strtoupper(substr($conv['other_user_first_name'], 0, 1) . substr($conv['other_user_last_name'], 0, 1)) . '
                                                </div>
                                                <div>
                                                    <h3 class="font-medium text-gray-900">' . $conv['other_user_first_name'] . ' ' . $conv['other_user_last_name'] . '</h3>
                                                    <p class="text-sm text-gray-600">@' . $conv['other_user_username'] . '</p>
                                                </div>
                                              </div>';
                                        break;
                                    }
                                }
                                ?>
                            <?php endif; ?>
                        </div>

                        <!-- Mensajes -->
                        <div id="messages-container" class="flex-1 overflow-y-auto p-4 space-y-4">
                            <!-- Los mensajes se cargarán dinámicamente -->
                        </div>

                        <!-- Formulario de Mensaje -->
                        <div class="p-4 border-t border-gray-200">
                            <form onsubmit="sendMessage(event)" class="flex space-x-2">
                                <input type="hidden" id="conversation-id" value="<?php echo $selected_conversation; ?>">
                                <textarea id="message-input" placeholder="Escribe tu mensaje..." 
                                          class="flex-1 p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none" 
                                          rows="1" required></textarea>
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition duration-300">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="flex items-center justify-center h-full text-gray-500">
                        <div class="text-center">
                            <i class="fas fa-comments text-4xl mb-4"></i>
                            <h3 class="text-lg font-medium mb-2">Selecciona una conversación</h3>
                            <p class="mb-4">Elige una conversación o inicia una nueva</p>
                            <button onclick="openNewMessageModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                                <i class="fas fa-plus mr-2"></i>Nueva conversación
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para Nueva Conversación -->
    <div id="newMessageModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Nueva Conversación</h3>
                
                <?php if (empty($followed_users)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-user-friends text-gray-400 text-3xl mb-2"></i>
                        <p class="text-gray-600 mb-3">No sigues a ningún usuario aún</p>
                        <a href="users.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                            Descubrir Usuarios
                        </a>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-gray-600 mb-4">Selecciona un usuario que sigues para iniciar una conversación:</p>
                    <div class="max-h-64 overflow-y-auto">
                        <?php foreach ($followed_users as $user): ?>
                            <div class="flex items-center justify-between p-3 border-b border-gray-100 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-400 to-purple-400 flex items-center justify-center text-white font-bold text-xs mr-3">
                                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-900"><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h4>
                                        <p class="text-sm text-gray-600">@<?php echo $user['username']; ?></p>
                                    </div>
                                </div>
                                <?php if ($user['has_conversation'] > 0): ?>
                                    <span class="text-xs text-gray-500">Ya tienes conversación</span>
                                <?php else: ?>
                                    <button onclick="startConversation(<?php echo $user['id']; ?>)" 
                                            class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-3 py-1 rounded transition duration-300">
                                        Enviar mensaje
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="flex justify-end mt-4">
                    <button onclick="closeNewMessageModal()" 
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded transition duration-300">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentConversationId = <?php echo $selected_conversation ? $selected_conversation : 'null'; ?>;
        
        // Modal functions
        function openNewMessageModal() {
            document.getElementById('newMessageModal').classList.remove('hidden');
        }
        
        function closeNewMessageModal() {
            document.getElementById('newMessageModal').classList.add('hidden');
        }
        
        function startConversation(userId) {
            window.location.href = `messages.php?user=${userId}`;
        }
        
        function selectConversation(conversationId) {
            window.location.href = `messages.php?conversation=${conversationId}`;
        }

        function loadMessages() {
            if (!currentConversationId) return;
            
            fetch(`get_messages.php?conversation_id=${currentConversationId}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('messages-container').innerHTML = data;
                scrollToBottom();
            });
        }

        function sendMessage(event) {
            event.preventDefault();
            
            const messageInput = document.getElementById('message-input');
            const content = messageInput.value.trim();
            
            if (!content || !currentConversationId) return;
            
            fetch('send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    conversation_id: currentConversationId, 
                    content: content 
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                    loadMessages();
                }
            });
        }

        function scrollToBottom() {
            const container = document.getElementById('messages-container');
            container.scrollTop = container.scrollHeight;
        }

        // Cargar mensajes si hay una conversación seleccionada
        if (currentConversationId) {
            loadMessages();
            
            // Actualizar mensajes cada 3 segundos
            setInterval(loadMessages, 3000);
        }

        // Auto-resize textarea
        document.getElementById('message-input')?.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    </script>
</body>
</html> 