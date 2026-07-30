<?php
/**
 * Endpoint AJAX para consultar RENIEC/RUC
 * Uso: GET /api/consultar_documento.php?tipo=dni&numero=12345678
 * 
 * Respuesta:
 * {
 *   "ok": true,
 *   "datos": {
 *     "nombreCompleto": "JUAN PEREZ GARCIA",
 *     "estado": "ACTIVO"
 *   }
 * }
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/apiperu.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Método no permitido'
    ]);
    exit;
}

$tipo = strtolower(trim($_GET['tipo'] ?? ''));
$numero = trim($_GET['numero'] ?? '');

if (!in_array($tipo, ['dni', 'ruc'])) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Tipo debe ser dni o ruc'
    ]);
    exit;
}

if (empty($numero)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Número requerido'
    ]);
    exit;
}

try {
    $resultado = null;
    if ($tipo === 'dni') {
        $resultado = consultarDNIReniec($numero);
    } else {
        $resultado = consultarRUCSunat($numero);
    }

    echo json_encode([
        'ok' => true,
        'datos' => $resultado
    ]);

} catch (APIPeruException $e) {
    $mensaje = $e->getMessage();
    $msgNorm = strtolower($mensaje);

    // Guardamos detalle técnico en log del servidor para diagnóstico,
    // pero al cliente le devolvemos un texto corto y claro.
    error_log('[APIPERU] ' . $mensaje);

    if (
        str_contains($msgNorm, 'consulta reniec fallida') ||
        str_contains($msgNorm, 'consulta ruc fallida') ||
        str_contains($msgNorm, 'error conectando con apiperu') ||
        str_contains($msgNorm, 'timeout') ||
        str_contains($msgNorm, 'timed out') ||
        str_contains($msgNorm, 'failed to connect') ||
        str_contains($msgNorm, 'host desconocido') ||
        str_contains($msgNorm, 'no ha podido responder')
    ) {
        $mensaje = 'No se pudo consultar RENIEC/SUNAT en este momento. Intenta nuevamente o continúa manualmente.';
    }

    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'mensaje' => $mensaje
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error del servidor'
    ]);
}
