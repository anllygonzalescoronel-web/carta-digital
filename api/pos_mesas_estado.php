<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requerirRol(['admin']);

$db = getDB();

try {
    $turno = $db->query("SELECT id, usuario_id, usuario_nombre, abierta_en FROM cajas_turnos WHERE estado = 'abierta' ORDER BY id DESC LIMIT 1")->fetch();
    if (!$turno) {
        jsonResponse(['ok' => false, 'mensaje' => 'No hay caja abierta.'], 409);
    }

    $zonas = $db->query(
        'SELECT id, nombre, ancho, alto, orden
         FROM zonas_mesas
         WHERE activa = 1
         ORDER BY orden ASC, id ASC'
    )->fetchAll();

    $mesas = $db->query(
        'SELECT id, zona_id, nombre, capacidad, sillas, pos_x, pos_y, ancho, alto, forma, orden, decoraciones_json, mesa_union_id
         FROM mesas
         WHERE activa = 1
         ORDER BY zona_id ASC, orden ASC, id ASC'
    )->fetchAll();

    // Mapa id->nombre para mostrar nombre de la mesa principal
    $nombresMesas = [];
    foreach ($mesas as $m) {
        $nombresMesas[(int)$m['id']] = (string)$m['nombre'];
    }

    $activos = [];
        $stmtActivos = $db->query(
                "SELECT mesa_id,
                                COUNT(*) AS pedidos_activos,
                                SUM(total) AS total_activo,
                                MAX(creado_en) AS ultima_actividad,
                                SUM(CASE WHEN estado IN ('pendiente', 'en_preparacion', 'en_camino') THEN 1 ELSE 0 END) AS pedidos_cocina,
                                SUM(CASE WHEN estado = 'pagado' THEN 1 ELSE 0 END) AS pedidos_pagados
         FROM pedidos
         WHERE tipo_entrega = 'comer_aqui'
           AND mesa_id IS NOT NULL
           AND estado NOT IN ('entregado', 'cancelado')
         GROUP BY mesa_id"
    );
    foreach ($stmtActivos->fetchAll() as $a) {
        $activos[(int)$a['mesa_id']] = [
            'pedidos_activos' => (int)$a['pedidos_activos'],
            'total_activo' => (float)$a['total_activo'],
            'ultima_actividad' => (string)$a['ultima_actividad'],
            'pedidos_cocina' => (int)($a['pedidos_cocina'] ?? 0),
            'pedidos_pagados' => (int)($a['pedidos_pagados'] ?? 0),
        ];
    }

    $mesasPorZona = [];
    foreach ($mesas as $m) {
        $idMesa = (int)$m['id'];
        $infoActiva = $activos[$idMesa] ?? null;
        $pedidosActivos = (int)($infoActiva['pedidos_activos'] ?? 0);
        $pedidosCocina = (int)($infoActiva['pedidos_cocina'] ?? 0);
        $pedidosPagados = (int)($infoActiva['pedidos_pagados'] ?? 0);

        $estadoMesa = 'libre';
        if ($pedidosActivos > 0) {
            if ($pedidosCocina > 0) {
                $estadoMesa = 'proceso_pago';
            } elseif ($pedidosPagados > 0) {
                $estadoMesa = 'ocupada';
            } else {
                $estadoMesa = 'ocupada';
            }
        }

        $ocupada = $estadoMesa !== 'libre';

        $unionId = isset($m['mesa_union_id']) && $m['mesa_union_id'] !== null ? (int)$m['mesa_union_id'] : null;
        $unionNombre = $unionId ? ($nombresMesas[$unionId] ?? null) : null;

        $item = [
            'id' => $idMesa,
            'zona_id' => (int)$m['zona_id'],
            'nombre' => (string)$m['nombre'],
            'capacidad' => (int)$m['capacidad'],
            'sillas' => (int)$m['sillas'],
            'pos_x' => (int)$m['pos_x'],
            'pos_y' => (int)$m['pos_y'],
            'ancho' => max(80, (int)($m['ancho'] ?? 120)),
            'alto' => max(60, (int)($m['alto'] ?? 74)),
            'forma' => (string)$m['forma'],
            'decoraciones' => json_decode((string)($m['decoraciones_json'] ?? '[]'), true) ?: [],
            'ocupada' => $ocupada,
            'estado' => $estadoMesa,
            'pedidos_activos' => $pedidosActivos,
            'pedidos_cocina' => $pedidosCocina,
            'pedidos_pagados' => $pedidosPagados,
            'total_activo' => $infoActiva['total_activo'] ?? 0.0,
            'ultima_actividad' => $infoActiva['ultima_actividad'] ?? null,
            'mesa_union_id' => $unionId,
            'union_nombre' => $unionNombre,
        ];

        $zid = (int)$m['zona_id'];
        if (!isset($mesasPorZona[$zid])) {
            $mesasPorZona[$zid] = [];
        }
        $mesasPorZona[$zid][] = $item;
    }

    $out = [];
    foreach ($zonas as $z) {
        $idZona = (int)$z['id'];
        $out[] = [
            'id' => $idZona,
            'nombre' => (string)$z['nombre'],
            'ancho' => (int)$z['ancho'],
            'alto' => (int)$z['alto'],
            'orden' => (int)$z['orden'],
            'mesas' => $mesasPorZona[$idZona] ?? [],
        ];
    }

    jsonResponse([
        'ok' => true,
        'caja' => [
            'turno_id' => (int)$turno['id'],
            'usuario_id' => (int)$turno['usuario_id'],
            'usuario_nombre' => (string)$turno['usuario_nombre'],
            'abierta_en' => (string)$turno['abierta_en'],
        ],
        'zonas' => $out,
    ]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'mensaje' => 'No se pudo cargar el estado de mesas POS.'], 500);
}
