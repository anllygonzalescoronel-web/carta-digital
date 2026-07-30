<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function normalizarRolUsuario(?string $rol): string {
    return $rol === 'cocinero' ? 'cocinero' : 'admin';
}

function rutaInicioPorRol(?string $rol = null): string {
    $rolFinal = normalizarRolUsuario($rol ?? ($_SESSION['admin_rol'] ?? 'admin'));
    return $rolFinal === 'cocinero' ? 'cocina.php' : 'index.php';
}

function obtenerRolActual(): string {
    return normalizarRolUsuario($_SESSION['admin_rol'] ?? 'admin');
}

function esAdmin(): bool {
    return estaLogueado() && obtenerRolActual() === 'admin';
}

function esCocinero(): bool {
    return estaLogueado() && obtenerRolActual() === 'cocinero';
}

function responderNoAutorizado(): void {
    $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
    $isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    $isApi = strpos((string)($_SERVER['REQUEST_URI'] ?? ''), '/api/') !== false;

    if ($isApi || $isAjax || strpos($accept, 'application/json') !== false) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'mensaje' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Location: ' . rutaInicioPorRol());
    exit;
}

function requerirRol(array $rolesPermitidos): void {
    requerirLogin();
    $rol = obtenerRolActual();
    if (!in_array($rol, $rolesPermitidos, true)) {
        responderNoAutorizado();
    }
}

function asegurarColumnasUsuariosAuth(): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    require_once __DIR__ . '/db.php';
    $db = getDB();

    $stmt = $db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_usuarios'");
    $columnas = [];
    foreach ($stmt->fetchAll() as $row) {
        $columnas[$row['COLUMN_NAME']] = true;
    }

    if (!isset($columnas['rol'])) {
        $db->exec("ALTER TABLE admin_usuarios ADD COLUMN rol ENUM('admin','cocinero') NOT NULL DEFAULT 'admin' AFTER nombre");
    }
    if (!isset($columnas['activo'])) {
        $db->exec("ALTER TABLE admin_usuarios ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER rol");
    }
    if (!isset($columnas['actualizado_en'])) {
        $db->exec("ALTER TABLE admin_usuarios ADD COLUMN actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER creado_en");
    }
}

function estaLogueado(): bool {
    return !empty($_SESSION['admin_id']);
}

function requerirLogin(): void {
    if (!estaLogueado()) {
        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        $isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
        $isApi = strpos((string)($_SERVER['REQUEST_URI'] ?? ''), '/api/') !== false;

        if ($isApi || $isAjax || strpos($accept, 'application/json') !== false) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'mensaje' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header('Location: login.php');
        exit;
    }
    asegurarColumnasUsuariosAuth();
}

function intentarLogin(string $usuario, string $password): bool {
    require_once __DIR__ . '/db.php';
    asegurarColumnasUsuariosAuth();

    $stmt = getDB()->prepare('SELECT * FROM admin_usuarios WHERE usuario = :usuario LIMIT 1');
    $stmt->execute(['usuario' => $usuario]);
    $admin = $stmt->fetch();

    $activo = !isset($admin['activo']) || (int)$admin['activo'] === 1;

    if ($admin && $activo && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_nombre'] = $admin['nombre'];
        $_SESSION['admin_rol'] = normalizarRolUsuario($admin['rol'] ?? 'admin');
        return true;
    }
    return false;
}

function cerrarSesion(): void {
    $_SESSION = [];
    session_destroy();
}
