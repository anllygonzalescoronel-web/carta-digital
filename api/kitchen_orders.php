<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!estaLogueado()) {
    jsonResponse(['ok' => false, 'mensaje' => 'No autorizado'], 401);
}

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

    jsonResponse(['ok' => true, 'mensaje' => 'Estado actualizado']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['ok' => false, 'mensaje' => 'Metodo no permitido'], 405);
}

$filtro = trim((string) ($_GET['estado'] ?? ''));
$limite = max(1, min(80, (int) ($_GET['limite'] ?? 40)));

$sql = 'SELECT p.id, p.codigo, p.cliente_nombre, p.cliente_telefono, p.tipo_entrega, p.metodo_pago, p.total, p.estado, p.creado_en,
        (SELECT COALESCE(SUM(pd.cantidad),0) FROM pedido_detalle pd WHERE pd.pedido_id = p.id) AS total_items,
        (SELECT GROUP_CONCAT(CONCAT(pd.cantidad, "x ", pd.nombre_producto) SEPARATOR "||") FROM pedido_detalle pd WHERE pd.pedido_id = p.id) AS resumen_items
        FROM pedidos p';
$params = [];

if ($filtro !== '' && in_array($filtro, $estadosValidos, true)) {
    $sql .= ' WHERE p.estado = :estado';
    $params['estado'] = $filtro;
}

$sql .= ' ORDER BY p.creado_en DESC LIMIT :limite';
$stmt = $db->prepare($sql);
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

    $out[] = [
        'id' => (int) $p['id'],
        'codigo' => (string) $p['codigo'],
        'cliente_nombre' => (string) $p['cliente_nombre'],
        'cliente_telefono' => (string) $p['cliente_telefono'],
        'tipo_entrega' => (string) $p['tipo_entrega'],
        'metodo_pago' => (string) $p['metodo_pago'],
        'total' => (float) $p['total'],
        'estado' => (string) $p['estado'],
        'creado_en' => (string) $p['creado_en'],
        'total_items' => (int) $p['total_items'],
        'items' => $items,
    ];
}

jsonResponse([
    'ok' => true,
    'estados_validos' => $estadosValidos,
    'pedidos' => $out,
]);
