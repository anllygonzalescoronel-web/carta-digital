<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function estaLogueado(): bool {
    return !empty($_SESSION['admin_id']);
}

function requerirLogin(): void {
    if (!estaLogueado()) {
        header('Location: login.php');
        exit;
    }
}

function intentarLogin(string $usuario, string $password): bool {
    require_once __DIR__ . '/db.php';
    $stmt = getDB()->prepare('SELECT * FROM admin_usuarios WHERE usuario = :usuario LIMIT 1');
    $stmt->execute(['usuario' => $usuario]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_nombre'] = $admin['nombre'];
        return true;
    }
    return false;
}

function cerrarSesion(): void {
    $_SESSION = [];
    session_destroy();
}
