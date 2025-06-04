<?php
session_start();
require_once '../config/database.php';

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Función para requerir login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../auth/login.php');
        exit();
    }
}

// Función para redireccionar usuarios logueados
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header('Location: ../dashboard/index.php');
        exit();
    }
}

// Función para limpiar datos de entrada
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para mostrar tiempo relativo
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'hace ' . $time . ' segundos';
    if ($time < 3600) return 'hace ' . round($time/60) . ' minutos';
    if ($time < 86400) return 'hace ' . round($time/3600) . ' horas';
    if ($time < 2592000) return 'hace ' . round($time/86400) . ' días';
    if ($time < 31536000) return 'hace ' . round($time/2592000) . ' meses';
    return 'hace ' . round($time/31536000) . ' años';
}

// Función para verificar si un usuario le dio like a un post
function hasUserLikedPost($user_id, $post_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id FROM post_likes WHERE user_id = ? AND post_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, $post_id]);
    
    return $stmt->rowCount() > 0;
}

// Función para contar likes de un post
function getPostLikesCount($post_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT COUNT(*) FROM post_likes WHERE post_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$post_id]);
    
    return $stmt->fetchColumn();
}

// Función para contar comentarios de un post
function getPostCommentsCount($post_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT COUNT(*) FROM comments WHERE post_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$post_id]);
    
    return $stmt->fetchColumn();
}

// Función para verificar si un usuario sigue a otro
function isFollowing($follower_id, $following_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id FROM follows WHERE follower_id = ? AND following_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$follower_id, $following_id]);
    
    return $stmt->rowCount() > 0;
}

// ===== FUNCIONES PARA GRUPOS =====

// Función para verificar si un usuario es miembro de un grupo
function isGroupMember($user_id, $group_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id FROM group_members WHERE user_id = ? AND group_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, $group_id]);
    
    return $stmt->rowCount() > 0;
}

// Función para obtener el rol del usuario en un grupo
function getUserGroupRole($user_id, $group_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT role FROM group_members WHERE user_id = ? AND group_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, $group_id]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['role'] : null;
}

// Función para verificar si un usuario le dio like a un post de grupo
function hasUserLikedGroupPost($user_id, $group_post_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id FROM group_post_likes WHERE user_id = ? AND group_post_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, $group_post_id]);
    
    return $stmt->rowCount() > 0;
}

// Función para contar likes de un post de grupo
function getGroupPostLikesCount($group_post_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT COUNT(*) FROM group_post_likes WHERE group_post_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$group_post_id]);
    
    return $stmt->fetchColumn();
}

// Función para contar comentarios de un post de grupo
function getGroupPostCommentsCount($group_post_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT COUNT(*) FROM group_post_comments WHERE group_post_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$group_post_id]);
    
    return $stmt->fetchColumn();
}

// Función para contar miembros de un grupo
function getGroupMembersCount($group_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT COUNT(*) FROM group_members WHERE group_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$group_id]);
    
    return $stmt->fetchColumn();
}

// ===== FUNCIONES PARA MENSAJERÍA =====

// Función para obtener o crear una conversación entre dos usuarios (solo si se siguen)
function getOrCreateConversation($user1_id, $user2_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar que el usuario sigue al destinatario
    if (!isFollowing($user1_id, $user2_id)) {
        return false; // No puede crear conversación si no sigue al usuario
    }
    
    // Asegurar que user1_id sea el menor para evitar duplicados
    if ($user1_id > $user2_id) {
        $temp = $user1_id;
        $user1_id = $user2_id;
        $user2_id = $temp;
    }
    
    // Buscar conversación existente
    $query = "SELECT id FROM conversations WHERE user1_id = ? AND user2_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user1_id, $user2_id]);
    
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($conversation) {
        return $conversation['id'];
    } else {
        // Crear nueva conversación
        $query = "INSERT INTO conversations (user1_id, user2_id) VALUES (?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$user1_id, $user2_id]);
        return $db->lastInsertId();
    }
}

// Función para contar mensajes no leídos de un usuario
function getUnreadMessagesCount($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    
    return $stmt->fetchColumn();
}

// Función para marcar mensajes como leídos
function markMessagesAsRead($conversation_id, $user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND receiver_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$conversation_id, $user_id]);
}

// Función para obtener conversaciones de un usuario (solo con usuarios que sigue o lo siguen)
function getUserConversations($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT c.*, 
              CASE 
                  WHEN c.user1_id = ? THEN u2.first_name
                  ELSE u1.first_name
              END as other_user_first_name,
              CASE 
                  WHEN c.user1_id = ? THEN u2.last_name
                  ELSE u1.last_name
              END as other_user_last_name,
              CASE 
                  WHEN c.user1_id = ? THEN u2.username
                  ELSE u1.username
              END as other_user_username,
              CASE 
                  WHEN c.user1_id = ? THEN u2.id
                  ELSE u1.id
              END as other_user_id,
              (SELECT content FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
              (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND receiver_id = ? AND is_read = 0) as unread_count
              FROM conversations c
              JOIN users u1 ON c.user1_id = u1.id
              JOIN users u2 ON c.user2_id = u2.id
              WHERE (c.user1_id = ? OR c.user2_id = ?)
              AND EXISTS (
                  SELECT 1 FROM follows 
                  WHERE (follower_id = ? AND following_id = CASE WHEN c.user1_id = ? THEN c.user2_id ELSE c.user1_id END)
                  OR (follower_id = CASE WHEN c.user1_id = ? THEN c.user2_id ELSE c.user1_id END AND following_id = ?)
              )
              ORDER BY c.last_message_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener usuarios seguidos para iniciar conversaciones
function getFollowedUsersForMessaging($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT u.id, u.username, u.first_name, u.last_name,
              (SELECT COUNT(*) FROM conversations c 
               WHERE (c.user1_id = ? AND c.user2_id = u.id) 
               OR (c.user1_id = u.id AND c.user2_id = ?)) as has_conversation
              FROM users u
              JOIN follows f ON u.id = f.following_id
              WHERE f.follower_id = ? AND u.is_active = 1
              ORDER BY u.first_name, u.last_name";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, $user_id, $user_id]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para verificar si puede enviar mensaje a un usuario
function canSendMessageToUser($sender_id, $receiver_id) {
    return isFollowing($sender_id, $receiver_id);
}
?> 