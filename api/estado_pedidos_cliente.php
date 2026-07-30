<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['ok' => false, 'mensaje' => 'Metodo no permitido'], 405);
}

$db = getDB();
$nombre = trim((string) ($_GET['nombre'] ?? ''));
$telefono = trim((string) ($_GET['telefono'] ?? ''));
$codigo = trim((string) ($_GET['codigo'] ?? ''));
$limite = max(1, min(20, (int) ($_GET['limite'] ?? 8)));

if ($codigo === '' && ($nombre === '' || $telefono === '')) {
    jsonResponse(['ok' => false, 'mensaje' => 'Debes enviar codigo o nombre+telefono'], 400);
}

$sql = 'SELECT id, codigo, cliente_nombre, cliente_telefono, tipo_entrega, metodo_pago, total, estado, creado_en
        FROM pedidos
        WHERE 1=1';
$params = [];

if ($codigo !== '') {
    $sql .= ' AND codigo = :codigo';
    $params['codigo'] = $codigo;
} else {
    $sql .= ' AND cliente_nombre LIKE :nombre AND cliente_telefono LIKE :telefono';
    $params['nombre'] = '%' . $nombre . '%';
    $params['telefono'] = '%' . $telefono . '%';
}

$sql .= ' ORDER BY creado_en DESC LIMIT :limite';
$stmt = $db->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue(':' . $k, $v);
}
$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->execute();
$pedidos = $stmt->fetchAll();

$out = [];
foreach ($pedidos as $p) {
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
    ];
}

jsonResponse([
    'ok' => true,
    'pedidos' => $out,
    'estados' => [
        'pendiente' => ['etiqueta' => 'Pendiente', 'paso' => 1],
        'pagado' => ['etiqueta' => 'Pagado', 'paso' => 2],
        'en_preparacion' => ['etiqueta' => 'En preparacion', 'paso' => 3],
        'en_camino' => ['etiqueta' => 'En camino', 'paso' => 4],
        'entregado' => ['etiqueta' => 'Entregado', 'paso' => 5],
        'cancelado' => ['etiqueta' => 'Cancelado', 'paso' => 0],
    ],
]);
