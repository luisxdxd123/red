<?php
require_once '../includes/functions.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener la membresía actual del usuario
    $query = "SELECT membership_type FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $current_membership = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($current_membership && $current_membership['membership_type'] !== 'basico') {
        // Buscar la última solicitud aprobada del usuario
        $query = "SELECT id, amount FROM membership_requests 
                 WHERE user_id = ? AND status = 'approved' 
                 ORDER BY request_date DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['user_id']]);
        $last_request = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($last_request) {
            // Actualizar el estado de la solicitud a cancelada por usuario
            $query = "UPDATE membership_requests 
                     SET status = 'user_cancelled', 
                         response_date = CURRENT_TIMESTAMP 
                     WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$last_request['id']]);

            // Revertir la membresía del usuario a básica
            $query = "UPDATE users 
                     SET membership_type = 'basico', 
                         membership_expires_at = NULL 
                     WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$_SESSION['user_id']]);

            $_SESSION['success'] = "Tu membresía ha sido cancelada exitosamente.";
        }
    }

    // Redirigir de vuelta a la página de membresías
    header('Location: memberships.php');
    exit;
}
?> 