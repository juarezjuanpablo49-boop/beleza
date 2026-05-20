<?php
require_once 'config.php';
startSession();

// Logout forzado: visita /?logout para limpiar sesión
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

$u = currentUser();
if ($u) {
    header('Location: ' . ($u['rol'] === 'admin' ? 'admin.php' : 'cliente.php'));
} else {
    header('Location: login.php');
}
exit;
