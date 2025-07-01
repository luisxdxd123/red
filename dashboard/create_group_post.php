<?php
require_once '../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: groups.php');
    exit();
}

$group_id = $_POST['group_id'] ?? null;
$content = $_POST['content'] ?? '';

if (!$group_id || !is_numeric($group_id) || empty(trim($content))) {
    header('Location: groups.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Verificar que el usuario es el creador del grupo
$query = "SELECT creator_id FROM groups WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$group_id]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group || $group['creator_id'] != $_SESSION['user_id']) {
    header('Location: group_detail.php?id=' . $group_id . '&error=unauthorized');
    exit();
}

try {
    // Crear el post en el grupo
    $query = "INSERT INTO group_posts (group_id, user_id, content) VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$group_id, $_SESSION['user_id'], trim($content)]);
    
    // Redireccionar de vuelta al grupo
    header('Location: group_detail.php?id=' . $group_id);
    exit();
    
} catch (PDOException $e) {
    header('Location: group_detail.php?id=' . $group_id . '&error=1');
    exit();
}
?> 