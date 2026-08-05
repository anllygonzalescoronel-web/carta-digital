<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requerirRol(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'mensaje' => 'Metodo no permitido'], 405);
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    jsonResponse(['ok' => false, 'mensaje' => 'JSON invalido'], 400);
}

$mesaId = (int)($body['mesa_id'] ?? 0);
$motivo = trim((string)($body['motivo'] ?? 'Liberacion manual POS'));

if ($mesaId <= 0) {
    jsonResponse(['ok' => false, 'mensaje' => 'Mesa invalida.'], 400);
}

$db = getDB();

try {
    $turno = $db->query("SELECT id FROM cajas_turnos WHERE estado = 'abierta' ORDER BY id DESC LIMIT 1")->fetch();
    if (!$turno) {
        throw new RuntimeException('Debes abrir una caja para operar el POS.');
    }

    $stmtMesa = $db->prepare(
        'SELECT m.id, m.nombre AS mesa_nombre
         FROM mesas m
         INNER JOIN zonas_mesas z ON z.id = m.zona_id
         WHERE m.id = :id AND m.activa = 1 AND z.activa = 1
         LIMIT 1'
    );
    $stmtMesa->execute(['id' => $mesaId]);
    $mesa = $stmtMesa->fetch();
    if (!$mesa) {
        throw new RuntimeException('La mesa seleccionada no existe o está inactiva.');
    }

    $stmtPedidos = $db->prepare(
        "SELECT id, notas
         FROM pedidos
         WHERE tipo_entrega = 'comer_aqui'
           AND mesa_id = :mesa_id
           AND estado NOT IN ('entregado', 'cancelado')"
    );
    $stmtPedidos->execute(['mesa_id' => $mesaId]);
    $pedidos = $stmtPedidos->fetchAll();

    if (empty($pedidos)) {
        jsonResponse([
            'ok' => true,
            'mesa' => [
                'id' => $mesaId,
                'nombre' => (string)$mesa['mesa_nombre'],
            ],
            'pedidos_cancelados' => 0,
            'mensaje' => 'La mesa ya estaba libre.',
        ]);
    }

    $db->beginTransaction();

    $stmtCancelar = $db->prepare(
        "UPDATE pedidos
         SET estado = 'cancelado',
             notas = CONCAT(IFNULL(notas, ''), :nota_extra)
         WHERE id = :id"
    );

    $notaExtra = ' | ' . ($motivo !== '' ? $motivo : 'Liberacion manual POS');
    foreach ($pedidos as $pedido) {
        $stmtCancelar->execute([
            'nota_extra' => $notaExtra,
            'id' => (int)$pedido['id'],
        ]);
    }

    // Disolver unión: quitar mesa_union_id de todas las mesas secundarias y también si la mesa era secundaria
    $db->prepare('UPDATE mesas SET mesa_union_id = NULL WHERE mesa_union_id = :mesa_id OR id = :mesa_id2')
       ->execute(['mesa_id' => $mesaId, 'mesa_id2' => $mesaId]);

    $db->commit();

    jsonResponse([
        'ok' => true,
        'mesa' => [
            'id' => $mesaId,
            'nombre' => (string)$mesa['mesa_nombre'],
        ],
        'pedidos_cancelados' => count($pedidos),
        'mensaje' => 'Mesa liberada correctamente. Se cancelaron los consumos activos.',
    ]);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(['ok' => false, 'mensaje' => $e->getMessage()], 400);
}
