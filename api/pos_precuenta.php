<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requerirRol(['admin', 'mesero']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['ok' => false, 'mensaje' => 'Metodo no permitido'], 405);
}

$mesaId = (int)($_GET['mesa_id'] ?? 0);
if ($mesaId <= 0) {
    jsonResponse(['ok' => false, 'mensaje' => 'Mesa inválida.'], 400);
}

$db = getDB();

try {
    $turno = $db->query("SELECT id FROM cajas_turnos WHERE estado = 'abierta' ORDER BY id DESC LIMIT 1")->fetch();
    if (!$turno) {
        jsonResponse(['ok' => false, 'mensaje' => 'No hay caja abierta.'], 409);
    }

    $stmtMesa = $db->prepare('SELECT id, nombre FROM mesas WHERE id = :id LIMIT 1');
    $stmtMesa->execute(['id' => $mesaId]);
    $mesa = $stmtMesa->fetch();
    if (!$mesa) {
        jsonResponse(['ok' => false, 'mensaje' => 'Mesa no encontrada.'], 404);
    }

    $stmtPedidos = $db->prepare(
        "SELECT id, codigo, cliente_nombre, estado, subtotal, costo_delivery, total, creado_en
         FROM pedidos
         WHERE tipo_entrega = 'comer_aqui'
           AND mesa_id = :mesa_id
           AND estado NOT IN ('entregado', 'cancelado')
         ORDER BY creado_en ASC"
    );
    $stmtPedidos->execute(['mesa_id' => $mesaId]);
    $pedidos = $stmtPedidos->fetchAll();

    $ids = [];
    $total = 0.0;
    foreach ($pedidos as $p) {
        $ids[] = (int)$p['id'];
        $total += (float)$p['total'];
    }

    $lineas = [];
    if (!empty($ids)) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmtDet = $db->prepare(
            "SELECT nombre_producto, precio_unitario, cantidad, subtotal, opciones_json
             FROM pedido_detalle
             WHERE pedido_id IN ($in)
             ORDER BY nombre_producto ASC, id ASC"
        );
        $stmtDet->execute($ids);
        $raw = $stmtDet->fetchAll();

        $agrupado = [];
        foreach ($raw as $row) {
            $opcionesJson = (string)($row['opciones_json'] ?? '');
            $key = (string)$row['nombre_producto'] . '|' . $opcionesJson . '|' . (string)$row['precio_unitario'];
            if (!isset($agrupado[$key])) {
                $opciones = [];
                if ($opcionesJson !== '') {
                    $dec = json_decode($opcionesJson, true);
                    if (is_array($dec)) {
                        $opciones = $dec;
                    }
                }
                $agrupado[$key] = [
                    'nombre_producto' => (string)$row['nombre_producto'],
                    'precio_unitario' => (float)$row['precio_unitario'],
                    'cantidad' => 0,
                    'subtotal' => 0.0,
                    'opciones' => $opciones,
                ];
            }

            $agrupado[$key]['cantidad'] += (int)$row['cantidad'];
            $agrupado[$key]['subtotal'] += (float)$row['subtotal'];
        }

        $lineas = array_values($agrupado);
    }

    jsonResponse([
        'ok' => true,
        'mesa' => [
            'id' => (int)$mesa['id'],
            'nombre' => (string)$mesa['nombre'],
        ],
        'pedidos_activos' => array_map(static function ($p) {
            return [
                'id' => (int)$p['id'],
                'codigo' => (string)$p['codigo'],
                'cliente_nombre' => (string)$p['cliente_nombre'],
                'estado' => (string)$p['estado'],
                'subtotal' => (float)$p['subtotal'],
                'costo_delivery' => (float)$p['costo_delivery'],
                'total' => (float)$p['total'],
                'creado_en' => (string)$p['creado_en'],
            ];
        }, $pedidos),
        'resumen_lineas' => array_map(static function ($l) {
            return [
                'nombre_producto' => (string)$l['nombre_producto'],
                'precio_unitario' => (float)($l['precio_unitario'] ?? 0),
                'cantidad' => (int)$l['cantidad'],
                'subtotal' => (float)$l['subtotal'],
                'opciones' => is_array($l['opciones'] ?? null) ? $l['opciones'] : [],
            ];
        }, $lineas),
        'total_precuenta' => round($total, 2),
    ]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'mensaje' => 'No se pudo generar la precuenta.'], 500);
}
