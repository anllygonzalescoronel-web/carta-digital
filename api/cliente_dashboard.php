<?php
require_once __DIR__ . '/../includes/cliente_auth.php';

header('Content-Type: application/json; charset=utf-8');
requerirClienteLogin();

$clienteId = clienteActualId();
if (!$clienteId) {
    jsonResponse(['ok' => false, 'mensaje' => 'Sesion no valida'], 401);
}

try {
    $dashboard = obtenerResumenClienteDashboard($clienteId);
    jsonResponse([
        'ok' => true,
        'dashboard' => $dashboard,
    ]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'mensaje' => $e->getMessage()], 400);
}