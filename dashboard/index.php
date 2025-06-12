<?php
require_once '../includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Obtener posts con información del usuario
$query = "SELECT p.*, u.username, u.first_name, u.last_name, u.profile_picture 
          FROM posts p 
          JOIN users u ON p.user_id = u.id 
          ORDER BY p.created_at DESC 
          LIMIT 20";
$stmt = $db->prepare($query);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener medios para cada post que los tenga
foreach ($posts as &$post) {
    if ($post['has_media']) {
        $post['media'] = getPostMedia($post['id']);
    } else {
        $post['media'] = [];
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
    <title>Inicio - Red Social</title>
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
                        <a href="index.php" class="py-4 px-2 text-indigo-500 border-b-4 border-indigo-500 font-semibold">
                            <i class="fas fa-home mr-1"></i>Inicio
                        </a>
                        <a href="groups.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300">
                            <i class="fas fa-users mr-1"></i>Grupos
                        </a>
                        <a href="messages.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-indigo-500 transition duration-300 relative">
                            <i class="fas fa-envelope mr-1"></i>Mensajes
                            <?php if ($unread_messages > 0): ?>
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                    <?php echo $unread_messages > 9 ? '9+' : $unread_messages; ?>
                                </span>
                            <?php endif; ?>
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

    <?php if (isset($_SESSION['warning'])): ?>
        <div class="max-w-4xl mx-auto px-4 pt-4">
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['warning']; unset($_SESSION['warning']); ?></span>
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

    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Crear Post -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">¿Qué estás pensando?</h3>
            <form action="create_post.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <textarea name="content" placeholder="Comparte algo interesante..." 
                          class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none" 
                          rows="3"></textarea>
                
                <!-- Sección de archivos multimedia -->
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4">
                    <div class="text-center">
                        <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-2"></i>
                        <p class="text-gray-600 mb-2">Arrastra archivos aquí o haz clic para seleccionar</p>
                        <p class="text-sm text-gray-500">Máximo 10 archivos. Imágenes (JPG, PNG, GIF, WebP) hasta 10MB. Videos (MP4, WebM, MOV, AVI) hasta 100MB.</p>
                    </div>
                    <input type="file" name="media[]" multiple accept="image/*,video/*" 
                           id="media-input" class="hidden" onchange="previewFiles(this)">
                    <button type="button" onclick="document.getElementById('media-input').click()" 
                            class="w-full mt-2 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-4 rounded-lg transition duration-300">
                        <i class="fas fa-plus mr-2"></i>Seleccionar archivos
                    </button>
                </div>
                
                <!-- Vista previa de archivos -->
                <div id="media-preview" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 hidden"></div>
                
                <!-- Botones de acción -->
                <div class="flex justify-between items-center">
                    <button type="button" onclick="clearFiles()" id="clear-btn" 
                            class="text-red-600 hover:text-red-800 transition duration-300 hidden">
                        <i class="fas fa-trash mr-1"></i>Limpiar archivos
                    </button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        <i class="fas fa-paper-plane mr-2"></i>Publicar
                    </button>
                </div>
            </form>
        </div>

        <!-- Posts Timeline -->
        <div class="space-y-6">
            <?php foreach ($posts as $post): ?>
                <div class="bg-white rounded-lg shadow-md p-6" data-post-id="<?php echo $post['id']; ?>">
                    <!-- Header del Post -->
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold text-sm">
                            <?php echo strtoupper(substr($post['first_name'], 0, 1) . substr($post['last_name'], 0, 1)); ?>
                        </div>
                        <div class="ml-3 flex-1">
                            <h4 class="font-semibold text-gray-900"><?php echo $post['first_name'] . ' ' . $post['last_name']; ?></h4>
                            <p class="text-sm text-gray-500">@<?php echo $post['username']; ?> • <?php echo timeAgo($post['created_at']); ?></p>
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                                <!-- Botón eliminar para el propietario del post -->
                                <button onclick="deletePost(<?php echo $post['id']; ?>)" 
                                        class="text-gray-500 hover:text-red-500 transition duration-300" 
                                        title="Eliminar publicación">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php elseif (isFollowing($_SESSION['user_id'], $post['user_id'])): ?>
                                <a href="messages.php?user=<?php echo $post['user_id']; ?>" 
                                   class="text-gray-500 hover:text-indigo-500 transition duration-300" 
                                   title="Enviar mensaje">
                                    <i class="fas fa-envelope"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Contenido del Post -->
                    <div class="mb-4">
                        <?php if (!empty($post['content'])): ?>
                            <p class="text-gray-800 leading-relaxed mb-4"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                        <?php endif; ?>
                        
                        <!-- Medios del Post -->
                        <?php if (!empty($post['media'])): ?>
                            <div class="media-grid">
                                <?php 
                                $media_count = count($post['media']);
                                $grid_class = '';
                                if ($media_count == 1) {
                                    $grid_class = 'grid-cols-1';
                                } elseif ($media_count == 2) {
                                    $grid_class = 'grid-cols-2';
                                } elseif ($media_count == 3) {
                                    $grid_class = 'grid-cols-2';
                                } elseif ($media_count >= 4) {
                                    $grid_class = 'grid-cols-2';
                                }
                                ?>
                                <div class="grid <?php echo $grid_class; ?> gap-2 rounded-lg overflow-hidden">
                                    <?php 
                                    $display_count = min($media_count, 4);
                                    for ($i = 0; $i < $display_count; $i++): 
                                        $media = $post['media'][$i];
                                        $remaining = $media_count - 4;
                                    ?>
                                        <div class="relative <?php echo ($media_count == 3 && $i == 0) ? 'row-span-2' : ''; ?> group cursor-pointer" 
                                             onclick="openMediaModal(<?php echo $post['id']; ?>, <?php echo $i; ?>)">
                                            
                                            <?php if ($media['file_type'] == 'image'): ?>
                                                <img src="<?php echo htmlspecialchars($media['file_path']); ?>" 
                                                     alt="Imagen de la publicación" 
                                                     class="w-full h-full object-cover <?php echo ($media_count == 1) ? 'max-h-96' : 'h-32 md:h-40'; ?> transition-transform group-hover:scale-105">
                                            <?php elseif ($media['file_type'] == 'video'): ?>
                                                <div class="relative">
                                                    <video class="w-full h-full object-cover <?php echo ($media_count == 1) ? 'max-h-96' : 'h-32 md:h-40'; ?>" 
                                                           preload="metadata">
                                                        <source src="<?php echo htmlspecialchars($media['file_path']); ?>" 
                                                                type="<?php echo htmlspecialchars($media['mime_type']); ?>">
                                                    </video>
                                                    <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-30 transition-opacity group-hover:bg-opacity-50">
                                                        <i class="fas fa-play text-white text-2xl"></i>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Mostrar contador si hay más de 4 medios -->
                                            <?php if ($i == 3 && $remaining > 0): ?>
                                                <div class="absolute inset-0 bg-black bg-opacity-60 flex items-center justify-center">
                                                    <span class="text-white text-xl font-bold">+<?php echo $remaining; ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Acciones del Post -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                        <div class="flex space-x-6">
                            <button onclick="toggleLike(<?php echo $post['id']; ?>)" 
                                    class="flex items-center space-x-2 text-gray-500 hover:text-red-500 transition duration-300"
                                    id="like-btn-<?php echo $post['id']; ?>">
                                <i class="<?php echo hasUserLikedPost($_SESSION['user_id'], $post['id']) ? 'fas text-red-500' : 'far'; ?> fa-heart"></i>
                                <span id="like-count-<?php echo $post['id']; ?>"><?php echo getPostLikesCount($post['id']); ?></span>
                            </button>
                            
                            <button onclick="toggleComments(<?php echo $post['id']; ?>)" 
                                    class="flex items-center space-x-2 text-gray-500 hover:text-blue-500 transition duration-300">
                                <i class="far fa-comment"></i>
                                <span><?php echo getPostCommentsCount($post['id']); ?></span>
                            </button>
                        </div>
                    </div>

                    <!-- Sección de Comentarios (inicialmente oculta) -->
                    <div id="comments-<?php echo $post['id']; ?>" class="hidden mt-4 border-t border-gray-200 pt-4">
                        <!-- Formulario para nuevo comentario -->
                        <form onsubmit="addComment(event, <?php echo $post['id']; ?>)" class="mb-4">
                            <div class="flex space-x-2">
                                <textarea placeholder="Escribe un comentario..." 
                                          class="flex-1 p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none" 
                                          rows="2" required></textarea>
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition duration-300">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                        
                        <!-- Lista de comentarios -->
                        <div id="comments-list-<?php echo $post['id']; ?>">
                            <!-- Los comentarios se cargarán dinámicamente -->
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($posts)): ?>
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <i class="fas fa-comments text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">No hay posts aún</h3>
                <p class="text-gray-500">¡Sé el primero en compartir algo!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal para visualizar medios -->
    <div id="media-modal" class="fixed inset-0 bg-black bg-opacity-90 hidden z-50 flex items-center justify-center">
        <div class="relative max-w-4xl max-h-full p-4 w-full">
            <!-- Botón cerrar -->
            <button onclick="closeMediaModal()" 
                    class="absolute top-4 right-4 text-white hover:text-gray-300 text-2xl z-10">
                <i class="fas fa-times"></i>
            </button>
            
            <!-- Navegación anterior -->
            <button id="prev-media" onclick="navigateMedia(-1)" 
                    class="absolute left-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 text-2xl z-10 hidden">
                <i class="fas fa-chevron-left"></i>
            </button>
            
            <!-- Navegación siguiente -->
            <button id="next-media" onclick="navigateMedia(1)" 
                    class="absolute right-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 text-2xl z-10 hidden">
                <i class="fas fa-chevron-right"></i>
            </button>
            
            <!-- Contenedor del medio -->
            <div id="modal-media-container" class="flex items-center justify-center h-full">
                <!-- El contenido se carga dinámicamente -->
            </div>
            
            <!-- Información del medio -->
            <div id="media-info" class="absolute bottom-4 left-4 right-4 text-white text-center">
                <p id="media-counter" class="text-sm opacity-75"></p>
            </div>
        </div>
    </div>

    <script>
        // Función para dar/quitar like
        function toggleLike(postId) {
            fetch('toggle_like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ post_id: postId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const likeBtn = document.getElementById(`like-btn-${postId}`);
                    const likeCount = document.getElementById(`like-count-${postId}`);
                    const icon = likeBtn.querySelector('i');
                    
                    if (data.liked) {
                        icon.className = 'fas fa-heart text-red-500';
                    } else {
                        icon.className = 'far fa-heart';
                    }
                    
                    likeCount.textContent = data.count;
                }
            });
        }

        // Función para mostrar/ocultar comentarios
        function toggleComments(postId) {
            const commentsDiv = document.getElementById(`comments-${postId}`);
            
            if (commentsDiv.classList.contains('hidden')) {
                commentsDiv.classList.remove('hidden');
                loadComments(postId);
            } else {
                commentsDiv.classList.add('hidden');
            }
        }

        // Función para cargar comentarios
        function loadComments(postId) {
            fetch(`get_comments.php?post_id=${postId}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById(`comments-list-${postId}`).innerHTML = data;
            });
        }

        // Función para agregar comentario
        function addComment(event, postId) {
            event.preventDefault();
            
            const form = event.target;
            const textarea = form.querySelector('textarea');
            const content = textarea.value.trim();
            
            if (!content) return;
            
            fetch('add_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    post_id: postId, 
                    content: content 
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    textarea.value = '';
                    loadComments(postId);
                }
            });
        }

        // Función para eliminar post
        function deletePost(postId) {
            if (confirm('¿Estás seguro de que quieres eliminar esta publicación? Esta acción no se puede deshacer.')) {
                // Mostrar indicador de carga
                const postElement = document.querySelector(`[data-post-id="${postId}"]`);
                const deleteBtn = postElement.querySelector('button[onclick*="deletePost"]');
                const originalIcon = deleteBtn.innerHTML;
                deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                deleteBtn.disabled = true;
                
                fetch('delete_post.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ post_id: postId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Animar la eliminación del post
                        postElement.style.transition = 'all 0.3s ease-out';
                        postElement.style.transform = 'translateX(-100%)';
                        postElement.style.opacity = '0';
                        
                        setTimeout(() => {
                            postElement.remove();
                            
                            // Mostrar mensaje de éxito
                            showNotification('Publicación eliminada exitosamente', 'success');
                            
                            // Verificar si no quedan posts
                            const remainingPosts = document.querySelectorAll('[data-post-id]');
                            if (remainingPosts.length === 0) {
                                location.reload(); // Recargar para mostrar el mensaje "No hay posts aún"
                            }
                        }, 300);
                    } else {
                        showNotification('Error al eliminar la publicación: ' + data.error, 'error');
                        // Restaurar botón
                        deleteBtn.innerHTML = originalIcon;
                        deleteBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error al eliminar la publicación', 'error');
                    // Restaurar botón
                    deleteBtn.innerHTML = originalIcon;
                    deleteBtn.disabled = false;
                });
            }
        }

        // Función para mostrar notificaciones
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg text-white max-w-sm ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 
                'bg-blue-500'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${
                        type === 'success' ? 'fa-check-circle' : 
                        type === 'error' ? 'fa-exclamation-circle' : 
                        'fa-info-circle'
                    } mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animar entrada
            notification.style.transform = 'translateX(100%)';
            notification.style.transition = 'transform 0.3s ease-out';
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 10);
            
            // Eliminar después de 4 segundos
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 4000);
        }

        // ===== FUNCIONES PARA MANEJO DE ARCHIVOS MULTIMEDIA =====
        
        // Función para previsualizar archivos seleccionados
        function previewFiles(input) {
            const files = input.files;
            const preview = document.getElementById('media-preview');
            const clearBtn = document.getElementById('clear-btn');
            
            if (files.length === 0) {
                preview.classList.add('hidden');
                clearBtn.classList.add('hidden');
                return;
            }
            
            preview.innerHTML = '';
            preview.classList.remove('hidden');
            clearBtn.classList.remove('hidden');
            
            // Validar número de archivos
            if (files.length > 10) {
                alert('Máximo 10 archivos permitidos');
                input.value = '';
                preview.classList.add('hidden');
                clearBtn.classList.add('hidden');
                return;
            }
            
            Array.from(files).forEach((file, index) => {
                const fileDiv = document.createElement('div');
                fileDiv.className = 'relative bg-gray-100 rounded-lg overflow-hidden';
                
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 z-10';
                removeBtn.innerHTML = '×';
                removeBtn.onclick = () => removeFile(index);
                
                if (file.type.startsWith('image/')) {
                    const img = document.createElement('img');
                    img.className = 'w-full h-24 object-cover';
                    img.src = URL.createObjectURL(file);
                    img.onload = () => URL.revokeObjectURL(img.src);
                    
                    fileDiv.appendChild(img);
                } else if (file.type.startsWith('video/')) {
                    const video = document.createElement('video');
                    video.className = 'w-full h-24 object-cover';
                    video.src = URL.createObjectURL(file);
                    video.controls = false;
                    video.muted = true;
                    
                    const playIcon = document.createElement('div');
                    playIcon.className = 'absolute inset-0 flex items-center justify-center bg-black bg-opacity-50';
                    playIcon.innerHTML = '<i class="fas fa-play text-white text-xl"></i>';
                    
                    fileDiv.appendChild(video);
                    fileDiv.appendChild(playIcon);
                }
                
                const fileName = document.createElement('div');
                fileName.className = 'absolute bottom-0 left-0 right-0 bg-black bg-opacity-75 text-white text-xs p-1 truncate';
                fileName.textContent = file.name;
                
                fileDiv.appendChild(removeBtn);
                fileDiv.appendChild(fileName);
                preview.appendChild(fileDiv);
            });
        }
        
        // Función para remover un archivo específico
        function removeFile(index) {
            const input = document.getElementById('media-input');
            const dt = new DataTransfer();
            
            Array.from(input.files).forEach((file, i) => {
                if (i !== index) {
                    dt.items.add(file);
                }
            });
            
            input.files = dt.files;
            previewFiles(input);
        }
        
        // Función para limpiar todos los archivos
        function clearFiles() {
            const input = document.getElementById('media-input');
            const preview = document.getElementById('media-preview');
            const clearBtn = document.getElementById('clear-btn');
            
            input.value = '';
            preview.innerHTML = '';
            preview.classList.add('hidden');
            clearBtn.classList.add('hidden');
        }
        
        // Configurar drag & drop
        document.addEventListener('DOMContentLoaded', function() {
            const dropZone = document.querySelector('.border-dashed');
            const fileInput = document.getElementById('media-input');
            
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight(e) {
                dropZone.classList.add('border-indigo-500', 'bg-indigo-50');
            }
            
            function unhighlight(e) {
                dropZone.classList.remove('border-indigo-500', 'bg-indigo-50');
            }
            
            dropZone.addEventListener('drop', handleDrop, false);
            
            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                
                                 fileInput.files = files;
                 previewFiles(fileInput);
             }
         });

        // ===== FUNCIONES PARA MODAL DE MEDIOS =====
        
        let currentPostMedia = [];
        let currentMediaIndex = 0;
        
        // Función para abrir modal de medios
        function openMediaModal(postId, mediaIndex) {
            // Encontrar el post y sus medios
            const postElement = document.querySelector(`[data-post-id="${postId}"]`);
            if (!postElement) {
                // Si no encontramos el elemento, obtener los medios via AJAX
                fetch(`get_post_media.php?post_id=${postId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentPostMedia = data.media;
                        currentMediaIndex = mediaIndex;
                        showMediaInModal();
                    }
                });
            } else {
                // Obtener los medios del post actual
                const mediaElements = postElement.querySelectorAll('.media-grid img, .media-grid video');
                currentPostMedia = Array.from(mediaElements).map(el => ({
                    file_path: el.src || el.querySelector('source').src,
                    file_type: el.tagName.toLowerCase() === 'img' ? 'image' : 'video',
                    mime_type: el.type || 'video/mp4'
                }));
                currentMediaIndex = mediaIndex;
                showMediaInModal();
            }
        }
        
        // Función para mostrar medio en modal
        function showMediaInModal() {
            const modal = document.getElementById('media-modal');
            const container = document.getElementById('modal-media-container');
            const counter = document.getElementById('media-counter');
            const prevBtn = document.getElementById('prev-media');
            const nextBtn = document.getElementById('next-media');
            
            modal.classList.remove('hidden');
            
            // Limpiar contenedor
            container.innerHTML = '';
            
            const media = currentPostMedia[currentMediaIndex];
            
            if (media.file_type === 'image') {
                const img = document.createElement('img');
                img.src = media.file_path;
                img.className = 'max-w-full max-h-full object-contain';
                container.appendChild(img);
            } else if (media.file_type === 'video') {
                const video = document.createElement('video');
                video.src = media.file_path;
                video.className = 'max-w-full max-h-full object-contain';
                video.controls = true;
                video.autoplay = true;
                container.appendChild(video);
            }
            
            // Actualizar contador
            counter.textContent = `${currentMediaIndex + 1} de ${currentPostMedia.length}`;
            
            // Mostrar/ocultar botones de navegación
            if (currentPostMedia.length > 1) {
                prevBtn.classList.remove('hidden');
                nextBtn.classList.remove('hidden');
                
                // Habilitar/deshabilitar botones según la posición
                prevBtn.style.opacity = currentMediaIndex > 0 ? '1' : '0.5';
                nextBtn.style.opacity = currentMediaIndex < currentPostMedia.length - 1 ? '1' : '0.5';
            } else {
                prevBtn.classList.add('hidden');
                nextBtn.classList.add('hidden');
            }
        }
        
        // Función para navegar entre medios
        function navigateMedia(direction) {
            const newIndex = currentMediaIndex + direction;
            
            if (newIndex >= 0 && newIndex < currentPostMedia.length) {
                currentMediaIndex = newIndex;
                showMediaInModal();
            }
        }
        
        // Función para cerrar modal
        function closeMediaModal() {
            const modal = document.getElementById('media-modal');
            const container = document.getElementById('modal-media-container');
            
            modal.classList.add('hidden');
            
            // Detener videos si hay alguno reproduciéndose
            const videos = container.querySelectorAll('video');
            videos.forEach(video => {
                video.pause();
                video.currentTime = 0;
            });
            
            // Limpiar variables
            currentPostMedia = [];
            currentMediaIndex = 0;
        }
        
        // Cerrar modal con tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMediaModal();
            } else if (e.key === 'ArrowLeft') {
                navigateMedia(-1);
            } else if (e.key === 'ArrowRight') {
                navigateMedia(1);
            }
        });
        
        // Cerrar modal al hacer click fuera del contenido
        document.getElementById('media-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeMediaModal();
            }
        });
    </script>
</body>
</html> 