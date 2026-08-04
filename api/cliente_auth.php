<?php
require_once __DIR__ . '/../includes/cliente_auth.php';

header('Content-Type: application/json; charset=utf-8');

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($metodo === 'GET') {
    $cliente = obtenerClienteActual();
    jsonResponse([
        'ok' => true,
        'autenticado' => $cliente !== null,
        'cliente' => $cliente ? [
            'id' => (int)$cliente['id'],
            'nombre' => (string)$cliente['nombre'],
            'email' => (string)$cliente['email'],
            'telefono' => (string)($cliente['telefono'] ?? ''),
            'proveedor' => (string)($cliente['proveedor'] ?? 'local'),
            'avatar_url' => (string)($cliente['avatar_url'] ?? ''),
        ] : null,
    ]);
}

if ($metodo !== 'POST') {
    jsonResponse(['ok' => false, 'mensaje' => 'Metodo no permitido'], 405);
}

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$accion = trim((string)($body['accion'] ?? ''));

try {
    if ($accion === 'registro') {
        $cliente = registrarClienteWeb(
            (string)($body['nombre'] ?? ''),
            (string)($body['email'] ?? ''),
            (string)($body['telefono'] ?? ''),
            (string)($body['password'] ?? '')
        );
        iniciarSesionCliente($cliente);
        jsonResponse(['ok' => true, 'cliente' => $cliente]);
    }

    if ($accion === 'login') {
        $cliente = intentarLoginClienteLocal((string)($body['email'] ?? ''), (string)($body['password'] ?? ''));
        if (!$cliente) {
            jsonResponse(['ok' => false, 'mensaje' => 'Correo o contrasena incorrectos.'], 401);
        }
        iniciarSesionCliente($cliente);
        jsonResponse(['ok' => true, 'cliente' => $cliente]);
    }

    if ($accion === 'google') {
        $cliente = iniciarSesionClienteGoogle((string)($body['credential'] ?? ''));
        iniciarSesionCliente($cliente);
        jsonResponse(['ok' => true, 'cliente' => $cliente]);
    }

    if ($accion === 'actualizar_perfil') {
        $clienteId = clienteActualId();
        if (!$clienteId) {
            jsonResponse(['ok' => false, 'mensaje' => 'Debes iniciar sesion para actualizar tu perfil.'], 401);
        }

        $cliente = actualizarPerfilClienteWeb(
            $clienteId,
            (string)($body['nombre'] ?? ''),
            (string)($body['telefono'] ?? ''),
            isset($body['password']) ? (string)$body['password'] : null
        );
        iniciarSesionCliente($cliente);
        jsonResponse(['ok' => true, 'cliente' => $cliente, 'mensaje' => 'Perfil actualizado correctamente.']);
    }

    if ($accion === 'logout') {
        cerrarSesionCliente();
        jsonResponse(['ok' => true]);
    }

    jsonResponse(['ok' => false, 'mensaje' => 'Accion no valida'], 400);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'mensaje' => $e->getMessage()], 400);
}