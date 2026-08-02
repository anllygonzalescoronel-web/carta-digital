<?php
ini_set('display_errors', '0');
ob_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requerirRol(['admin', 'cocinero']);

$db = getDB();
$estadosValidos = ['pendiente', 'pagado', 'en_preparacion', 'en_camino', 'entregado', 'cancelado'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        jsonResponse(['ok' => false, 'mensaje' => 'JSON invalido'], 400);
    }

    $pedidoId = (int) ($data['pedido_id'] ?? 0);
    $estado = (string) ($data['estado'] ?? '');

    if ($pedidoId <= 0 || !in_array($estado, $estadosValidos, true)) {
        jsonResponse(['ok' => false, 'mensaje' => 'Datos invalidos'], 400);
    }

    $stmt = $db->prepare('UPDATE pedidos SET estado = :estado WHERE id = :id');
    $stmt->execute(['estado' => $estado, 'id' => $pedidoId]);

    // ── Descontar ingredientes cuando pasa a "en_preparacion" ────────────────
    if ($estado === 'en_preparacion') {
        try {
            // Verificar que las tablas de ingredientes existen
            $tablaExiste = $db->query("SHOW TABLES LIKE 'ingredientes'")->fetch();
            if ($tablaExiste) {
                // Obtener detalle del pedido con sus ingredientes
                $stmtItems = $db->prepare(
                    "SELECT pd.producto_id, pd.cantidad AS cant_pedido
                     FROM pedido_detalle pd
                     WHERE pd.pedido_id = :pid AND pd.producto_id IS NOT NULL"
                );
                $stmtItems->execute(['pid' => $pedidoId]);
                $items = $stmtItems->fetchAll();

                $stmtGetIng = $db->prepare(
                    "SELECT pi.ingrediente_id, pi.cantidad AS cant_por_unidad,
                            i.stock_actual, i.unidad
                     FROM producto_ingredientes pi
                     INNER JOIN ingredientes i ON i.id = pi.ingrediente_id
                     WHERE pi.producto_id = :pid"
                );
                $stmtUpdateStock = $db->prepare(
                    "UPDATE ingredientes SET stock_actual = GREATEST(0, stock_actual - :descuento) WHERE id = :id"
                );
                $stmtMovimiento = $db->prepare(
                    "INSERT INTO ingrediente_movimientos (ingrediente_id, tipo, cantidad, stock_antes, stock_despues, motivo, pedido_id)
                     VALUES (:iid, 'salida', :cant, :sa, :sd, :motivo, :pid)"
                );

                foreach ($items as $item) {
                    $stmtGetIng->execute(['pid' => $item['producto_id']]);
                    $ingredientes = $stmtGetIng->fetchAll();

                    foreach ($ingredientes as $ing) {
                        $descuento = (float)$ing['cant_por_unidad'] * (float)$item['cant_pedido'];
                        $stockAntes = (float)$ing['stock_actual'];
                        $stockDespues = max(0, $stockAntes - $descuento);

                        $stmtUpdateStock->execute([
                            'descuento' => $descuento,
                            'id'        => $ing['ingrediente_id'],
                        ]);
                        $stmtMovimiento->execute([
                            'iid'    => $ing['ingrediente_id'],
                            'cant'   => $descuento,
                            'sa'     => $stockAntes,
                            'sd'     => $stockDespues,
                            'motivo' => 'Pedido #' . $pedidoId,
                            'pid'    => $pedidoId,
                        ]);
                    }
                }
            }
        } catch (Throwable $eIng) {
            // El descuento de stock falla silenciosamente para no bloquear la cocina
            error_log('Error descuento ingredientes: ' . $eIng->getMessage());
        }
    }

    jsonResponse(['ok' => true, 'mensaje' => 'Estado actualizado']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['ok' => false, 'mensaje' => 'Metodo no permitido'], 405);
}

try {

$filtro      = trim((string) ($_GET['estado']   ?? ''));
$limite      = max(1, min(200, (int) ($_GET['limite']  ?? 80)));
$periodo     = trim((string) ($_GET['periodo']  ?? 'hoy'));   // hoy | semana | mes | todo
$estacionId  = (int) ($_GET['estacion_id'] ?? 0);   // 0 = todas

// Calcular rango en hora Lima (UTC-5)
date_default_timezone_set('America/Lima');
$periodosValidos = ['hoy', 'semana', 'mes', 'todo'];
if (!in_array($periodo, $periodosValidos, true)) {
    $periodo = 'hoy';
}

$desde = null;
switch ($periodo) {
    case 'hoy':
        $desde = date('Y-m-d') . ' 00:00:00';
        break;
    case 'semana':
        $desde = date('Y-m-d', strtotime('monday this week')) . ' 00:00:00';
        break;
    case 'mes':
        $desde = date('Y-m-01') . ' 00:00:00';
        break;
    // 'todo': sin filtro de fecha
}

$sql = 'SELECT p.id, p.codigo, p.cliente_nombre, p.cliente_telefono, p.tipo_entrega, p.mesa_nombre, p.zona_nombre, p.metodo_pago, p.total, p.estado, p.creado_en,
        (SELECT COALESCE(SUM(pd.cantidad),0) FROM pedido_detalle pd WHERE pd.pedido_id = p.id) AS total_items,
        (SELECT GROUP_CONCAT(CONCAT(pd.cantidad, "x ", pd.nombre_producto) SEPARATOR "||") FROM pedido_detalle pd WHERE pd.pedido_id = p.id) AS resumen_items
        FROM pedidos p';
$params = [];
$condiciones = [];

if ($filtro !== '' && in_array($filtro, $estadosValidos, true)) {
    $condiciones[] = 'p.estado = :estado';
    $params['estado'] = $filtro;
}
if ($desde !== null) {
    $condiciones[] = 'p.creado_en >= :desde';
    $params['desde'] = $desde;
}

// ── Filtrar por estación de producción ────────────────────────────
// Si viene estacion_id se muestra solo los pedidos que tengan al menos 1 ítem
// cuyo producto pertenezca a las categorías asignadas a esa estación.
$estacionCatIds = [];
if ($estacionId > 0) {
    $stmtECats = $db->prepare(
        "SELECT ec.categoria_id
         FROM estacion_categorias ec
         INNER JOIN estaciones_produccion ep ON ep.id = ec.estacion_id
         WHERE ec.estacion_id = :eid AND ep.activa = 1"
    );
    $stmtECats->execute(['eid' => $estacionId]);
    $estacionCatIds = array_column($stmtECats->fetchAll(), 'categoria_id');

    if (!empty($estacionCatIds)) {
        $namedPlaceholders = [];
        foreach ($estacionCatIds as $i => $catId) {
            $key = 'cat' . $i;
            $namedPlaceholders[] = ':' . $key;
            $params[$key] = (int)$catId;
        }
        $condiciones[] = "p.id IN (
            SELECT pd.pedido_id
            FROM pedido_detalle pd
            INNER JOIN productos pr ON pr.id = pd.producto_id
            WHERE pr.categoria_id IN (" . implode(',', $namedPlaceholders) . ")
        )";
    } else {
        // Estación sin categorías → no mostrar nada
        jsonResponse([
            'ok' => true,
            'estados_validos' => $estadosValidos,
            'pedidos' => [],
            'periodo' => $periodo,
            'total' => 0,
            'estacion_id' => $estacionId,
            'estacion_cat_ids' => [],
        ]);
    }
}

if (!empty($condiciones)) {
    $sql .= ' WHERE ' . implode(' AND ', $condiciones);
}

$sql .= ' ORDER BY p.creado_en DESC LIMIT :limite';
$stmt = $db->prepare($sql);

// Bindear parámetros normales
foreach ($params as $k => $v) {
    $stmt->bindValue(':' . $k, $v);
}
$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->execute();
$pedidos = $stmt->fetchAll();

$out = [];
foreach ($pedidos as $p) {
    $items = [];
    if (!empty($p['resumen_items'])) {
        $items = explode('||', (string) $p['resumen_items']);
    }

    // Si hay filtro de estación, agregar solo los items que pertenecen a esa estación
    $itemsDetalle = [];
    $itemsDetalleEstacion = [];
    if ($estacionId > 0 && !empty($estacionCatIds)) {
        $stmtDet = $db->prepare(
            "SELECT pd.nombre_producto, pd.cantidad, pd.precio_unitario, pd.opciones_json,
                    COALESCE(pr.categoria_id, 0) AS categoria_id
             FROM pedido_detalle pd
             LEFT JOIN productos pr ON pr.id = pd.producto_id
             WHERE pd.pedido_id = :pid
             ORDER BY pd.id ASC"
        );
        $stmtDet->execute(['pid' => (int)$p['id']]);
        $itemsDetalle = $stmtDet->fetchAll();
        $itemsDetalleEstacion = array_values(array_filter($itemsDetalle, fn($d) => in_array((int)$d['categoria_id'], $estacionCatIds, true)));
    }

    $out[] = [
        'id'              => (int) $p['id'],
        'codigo'          => (string) $p['codigo'],
        'cliente_nombre'  => (string) $p['cliente_nombre'],
        'cliente_telefono'=> (string) $p['cliente_telefono'],
        'tipo_entrega'    => (string) $p['tipo_entrega'],
        'mesa_nombre'     => isset($p['mesa_nombre']) ? (string)$p['mesa_nombre'] : '',
        'zona_nombre'     => isset($p['zona_nombre']) ? (string)$p['zona_nombre'] : '',
        'metodo_pago'     => (string) $p['metodo_pago'],
        'total'           => (float) $p['total'],
        'estado'          => (string) $p['estado'],
        'creado_en'       => (string) $p['creado_en'],
        'total_items'     => (int) $p['total_items'],
        'items'           => $items,
        'items_detalle'   => array_map(fn($d) => [
            'nombre_producto' => $d['nombre_producto'],
            'cantidad'        => (int)$d['cantidad'],
            'precio_unitario' => (float)$d['precio_unitario'],
            'categoria_id'    => (int)$d['categoria_id'],
            'opciones_json'   => $d['opciones_json'] ?? null,
        ], $estacionId > 0 ? $itemsDetalleEstacion : $itemsDetalle),
        'total_items_estacion' => $estacionId > 0 ? count($itemsDetalleEstacion) : (int)$p['total_items'],
    ];
}

jsonResponse([
    'ok'              => true,
    'estados_validos' => $estadosValidos,
    'pedidos'         => $out,
    'periodo'         => $periodo,
    'total'           => count($out),
    'estacion_id'     => $estacionId,
    'estacion_cat_ids'=> $estacionCatIds,
]);

} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'mensaje' => 'Error interno: ' . $e->getMessage()], 500);
}
