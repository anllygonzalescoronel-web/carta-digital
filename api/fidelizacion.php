<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['ok' => false, 'mensaje' => 'Método no permitido'], 405);
}

$telefono = trim((string)($_GET['telefono'] ?? ''));
$resumen = obtenerResumenFidelizacionCliente($telefono);

jsonResponse([
    'ok' => true,
    'fidelizacion' => $resumen,
]);
