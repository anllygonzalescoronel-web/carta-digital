<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['ok' => false, 'mensaje' => 'Metodo no permitido'], 405);
}

$db = getDB();

try {
    $stmtMesas = $db->query(
        "SELECT m.id, m.zona_id, m.nombre, m.capacidad, m.sillas, m.forma,
                z.nombre AS zona_nombre
         FROM mesas m
         INNER JOIN zonas_mesas z ON z.id = m.zona_id
         WHERE m.activa = 1 AND z.activa = 1
         ORDER BY z.orden ASC, z.id ASC, m.orden ASC, m.id ASC"
    );
    $mesas = $stmtMesas->fetchAll();

    $ocupadas = [];
    $stmtOcupadas = $db->query(
        "SELECT mesa_id
         FROM pedidos
         WHERE tipo_entrega = 'comer_aqui'
           AND mesa_id IS NOT NULL
           AND estado NOT IN ('entregado', 'cancelado')"
    );
    foreach ($stmtOcupadas->fetchAll() as $row) {
        $ocupadas[(int)$row['mesa_id']] = true;
    }

    $zonas = [];
    foreach ($mesas as $m) {
        $zid = (int)$m['zona_id'];
        if (!isset($zonas[$zid])) {
            $zonas[$zid] = [
                'id' => $zid,
                'nombre' => (string)$m['zona_nombre'],
                'mesas' => [],
            ];
        }

        $zonas[$zid]['mesas'][] = [
            'id' => (int)$m['id'],
            'nombre' => (string)$m['nombre'],
            'capacidad' => (int)$m['capacidad'],
            'sillas' => (int)$m['sillas'],
            'forma' => (string)$m['forma'],
            'ocupada' => isset($ocupadas[(int)$m['id']]),
        ];
    }

    jsonResponse([
        'ok' => true,
        'zonas' => array_values($zonas),
    ]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'mensaje' => 'No se pudo cargar mesas.'], 500);
}
