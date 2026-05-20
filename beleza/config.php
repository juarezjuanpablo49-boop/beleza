<?php
// ============================================================
// config.php — Configuración central Beleza
// ============================================================

define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');          // Cambia si tienes contraseña en phpMyAdmin
define('DB_NAME',    'beleza_db');
define('DB_CHARSET', 'utf8mb4');

define('SITE_URL',         'http://localhost/beleza');
define('SESSION_NAME',     'beleza_sess');
define('SESSION_LIFETIME', 3600 * 8);

date_default_timezone_set('America/Mexico_City');

// ---- Conexión PDO (singleton) ----
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn  = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
    }
    return $pdo;
}

// ---- Sesión segura ----
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => false,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

// ---- Helpers ----
function currentUser(): ?array {
    startSession();
    return $_SESSION['user'] ?? null;
}

function requireLogin(string $rol = ''): array {
    $user = currentUser();
    if (!$user) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
    if ($rol && $user['rol'] !== $rol) {
        http_response_code(403);
        die('<!DOCTYPE html><html><body style="font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0"><div style="text-align:center"><h2 style="color:#B5294E">Acceso denegado</h2><p>No tienes permiso para esta sección.</p><a href="login.php" style="color:#B5294E">Volver al inicio</a></div></body></html>');
    }
    return $user;
}

function jsonResponse(array $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}
