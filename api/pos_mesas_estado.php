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
        'SELECT id, zona_id, nombre, capacidad, sillas, pos_x, pos_y, forma, orden
         FROM mesas
         WHERE activa = 1
         ORDER BY zona_id ASC, orden ASC, id ASC'
    )->fetchAll();

    $activos = [];
    $stmtActivos = $db->query(
        "SELECT mesa_id, COUNT(*) AS pedidos_activos, SUM(total) AS total_activo, MAX(creado_en) AS ultima_actividad
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
        ];
    }

    $mesasPorZona = [];
    foreach ($mesas as $m) {
        $idMesa = (int)$m['id'];
        $infoActiva = $activos[$idMesa] ?? null;
        $ocupada = $infoActiva !== null;

        $item = [
            'id' => $idMesa,
            'zona_id' => (int)$m['zona_id'],
            'nombre' => (string)$m['nombre'],
            'capacidad' => (int)$m['capacidad'],
            'sillas' => (int)$m['sillas'],
            'pos_x' => (int)$m['pos_x'],
            'pos_y' => (int)$m['pos_y'],
            'forma' => (string)$m['forma'],
            'ocupada' => $ocupada,
            'estado' => $ocupada ? 'ocupada' : 'libre',
            'pedidos_activos' => $infoActiva['pedidos_activos'] ?? 0,
            'total_activo' => $infoActiva['total_activo'] ?? 0.0,
            'ultima_actividad' => $infoActiva['ultima_actividad'] ?? null,
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
