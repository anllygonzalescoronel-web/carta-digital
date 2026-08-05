<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requerirRol(['admin', 'mesero']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'mensaje' => 'Metodo no permitido'], 405);
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    jsonResponse(['ok' => false, 'mensaje' => 'JSON invalido'], 400);
}

$mesaOrigenId = (int)($body['mesa_origen_id'] ?? 0);
$mesaDestinoId = (int)($body['mesa_destino_id'] ?? 0);

if ($mesaOrigenId <= 0 || $mesaDestinoId <= 0) {
    jsonResponse(['ok' => false, 'mensaje' => 'Mesas inválidas para unir.'], 400);
}
if ($mesaOrigenId === $mesaDestinoId) {
    jsonResponse(['ok' => false, 'mensaje' => 'La mesa destino debe ser distinta a la mesa origen.'], 400);
}

$db = getDB();

try {
    $turno = $db->query("SELECT id FROM cajas_turnos WHERE estado = 'abierta' ORDER BY id DESC LIMIT 1")->fetch();
    if (!$turno) {
        throw new RuntimeException('Debes abrir una caja para operar el POS.');
    }

    $stmtMesa = $db->prepare(
        'SELECT m.id, m.nombre AS mesa_nombre, z.nombre AS zona_nombre
         FROM mesas m
         INNER JOIN zonas_mesas z ON z.id = m.zona_id
         WHERE m.id = :id AND m.activa = 1 AND z.activa = 1
         LIMIT 1'
    );

    $stmtMesa->execute(['id' => $mesaOrigenId]);
    $mesaOrigen = $stmtMesa->fetch();
    if (!$mesaOrigen) {
        throw new RuntimeException('La mesa origen no existe o está inactiva.');
    }

    $stmtMesa->execute(['id' => $mesaDestinoId]);
    $mesaDestino = $stmtMesa->fetch();
    if (!$mesaDestino) {
        throw new RuntimeException('La mesa destino no existe o está inactiva.');
    }

    // Verificar si la mesa origen ya está unida a otra
    $stmtCheck = $db->prepare('SELECT mesa_union_id FROM mesas WHERE id = :id LIMIT 1');
    $stmtCheck->execute(['id' => $mesaOrigenId]);
    $unionActual = $stmtCheck->fetchColumn();
    if ($unionActual !== false && $unionActual !== null) {
        throw new RuntimeException('La mesa origen ya está unida a otra mesa. Disuélvela primero.');
    }
    $stmtCheck->execute(['id' => $mesaDestinoId]);
    $unionDestino = $stmtCheck->fetchColumn();
    if ($unionDestino !== false && $unionDestino !== null) {
        throw new RuntimeException('La mesa destino ya está unida a otra mesa. Elige una mesa libre.');
    }

    $db->beginTransaction();

    // Marcar mesa origen como secundaria de la principal (destino)
    $db->prepare('UPDATE mesas SET mesa_union_id = :union_id WHERE id = :id')
       ->execute(['union_id' => $mesaDestinoId, 'id' => $mesaOrigenId]);

    // Mover consumos activos existentes de origen a destino (si los hay)
    $columnasPedido = [];
    $stmtCols = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos'");
    $stmtCols->execute();
    foreach ($stmtCols->fetchAll() as $col) {
        $columnasPedido[(string)$col['COLUMN_NAME']] = true;
    }

    $stmtPedidos = $db->prepare(
        "SELECT id FROM pedidos
         WHERE tipo_entrega = 'comer_aqui' AND mesa_id = :mesa_id
           AND estado NOT IN ('entregado', 'cancelado')"
    );
    $stmtPedidos->execute(['mesa_id' => $mesaOrigenId]);
    $pedidosOrigen = $stmtPedidos->fetchAll();
    $pedidosMovidos = 0;

    if (!empty($pedidosOrigen)) {
        $setPartes = ['mesa_id = :mesa_destino_id'];
        $paramsBase = ['mesa_destino_id' => $mesaDestinoId];
        if (isset($columnasPedido['mesa_nombre'])) {
            $setPartes[] = 'mesa_nombre = :mesa_nombre';
            $paramsBase['mesa_nombre'] = (string)$mesaDestino['mesa_nombre'];
        }
        if (isset($columnasPedido['zona_nombre'])) {
            $setPartes[] = 'zona_nombre = :zona_nombre';
            $paramsBase['zona_nombre'] = (string)$mesaDestino['zona_nombre'];
        }
        $sqlUpdate = 'UPDATE pedidos SET ' . implode(', ', $setPartes) . ' WHERE id = :pedido_id';
        $stmtUpdate = $db->prepare($sqlUpdate);
        foreach ($pedidosOrigen as $pedido) {
            $params = $paramsBase;
            $params['pedido_id'] = (int)$pedido['id'];
            $stmtUpdate->execute($params);
        }
        $pedidosMovidos = count($pedidosOrigen);
    }

    $db->commit();

    jsonResponse([
        'ok' => true,
        'mesa_origen' => ['id' => $mesaOrigenId, 'nombre' => (string)$mesaOrigen['mesa_nombre']],
        'mesa_destino' => ['id' => $mesaDestinoId, 'nombre' => (string)$mesaDestino['mesa_nombre']],
        'pedidos_movidos' => $pedidosMovidos,
        'mensaje' => 'Mesas unidas. Los nuevos pedidos de ' . $mesaOrigen['mesa_nombre'] . ' se asignarán a ' . $mesaDestino['mesa_nombre'] . '.',
    ]);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(['ok' => false, 'mensaje' => $e->getMessage()], 400);
}
