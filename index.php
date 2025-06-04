<?php
session_start();

// Si el usuario está logueado, redireccionar al dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard/index.php');
    exit();
} else {
    // Si no está logueado, redireccionar al login
    header('Location: auth/login.php');
    exit();
}
?> 