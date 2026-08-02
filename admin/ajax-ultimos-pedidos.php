<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requerirLogin();
requerirRol(['admin']);

header('Content-Type: application/json; charset=utf-8');

$db = getDB();
$pedidosPorPagina = 10;
$paginaActual2 = max(1, (int) ($_GET['pagina'] ?? 1));

$totalPedidosTabla = $db->query("SELECT COUNT(*) c FROM pedidos")->fetch()['c'];
$totalPaginasPedidos = max(1, (int) ceil($totalPedidosTabla / $pedidosPorPagina));
$paginaActual2 = min($paginaActual2, $totalPaginasPedidos);
$offsetPedidos = ($paginaActual2 - 1) * $pedidosPorPagina;

$stmt = $db->prepare("SELECT * FROM pedidos ORDER BY creado_en DESC LIMIT :limite OFFSET :offset");
$stmt->bindValue(':limite', $pedidosPorPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offsetPedidos, PDO::PARAM_INT);
$stmt->execute();
$pedidos = $stmt->fetchAll();

$filasHtml = '';
if (empty($pedidos)) {
    $filasHtml = '<tr><td colspan="7" style="text-align:center;color:#999;">Aún no hay pedidos.</td></tr>';
} else {
    foreach ($pedidos as $p) {
        if ($p['tipo_entrega'] === 'delivery') {
            $entrega = '<i class="ti ti-motorbike"></i> Delivery';
        } elseif ($p['tipo_entrega'] === 'comer_aqui') {
            $entrega = '<i class="ti ti-tools-kitchen-2"></i> Comer aqui';
        } else {
            $entrega = '<i class="ti ti-home"></i> Recojo';
        }
        $pago = [
            'efectivo'  => '<i class="ti ti-cash"></i> Efectivo',
            'yape_plin' => '<i class="ti ti-device-mobile"></i> Yape (Culqi)',
            'tarjeta'   => '<i class="ti ti-credit-card"></i> Tarjeta',
        ][$p['metodo_pago']] ?? '';

        $filasHtml .= '<tr>'
            . '<td><a href="pedidos.php?ver=' . $p['id'] . '">' . limpiar($p['codigo']) . '</a></td>'
            . '<td>' . limpiar($p['cliente_nombre']) . '</td>'
            . '<td>' . $entrega . '</td>'
            . '<td>' . $pago . '</td>'
            . '<td>' . formatoPrecio($p['total']) . '</td>'
            . '<td><span class="badge badge-' . $p['estado'] . '">' . $p['estado'] . '</span></td>'
            . '<td>' . date('d/m H:i', strtotime($p['creado_en'])) . '</td>'
            . '</tr>';
    }
}

echo json_encode([
    'filasHtml'    => $filasHtml,
    'pagina'       => $paginaActual2,
    'totalPaginas' => $totalPaginasPedidos,
]);