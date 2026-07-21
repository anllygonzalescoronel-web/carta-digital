<?php
$tituloPagina = 'Dashboard';
$paginaActual = 'dashboard';
require __DIR__ . '/_layout_top.php';

$db = getDB();
$totalPedidosHoy = $db->query("SELECT COUNT(*) c FROM pedidos WHERE DATE(creado_en) = CURDATE()")->fetch()['c'];
$ventasHoy = $db->query("SELECT COALESCE(SUM(total),0) t FROM pedidos WHERE DATE(creado_en) = CURDATE() AND estado != 'cancelado'")->fetch()['t'];
$pendientes = $db->query("SELECT COUNT(*) c FROM pedidos WHERE estado = 'pendiente'")->fetch()['c'];
$totalProductos = $db->query("SELECT COUNT(*) c FROM productos")->fetch()['c'];

$ultimosPedidos = $db->query("SELECT * FROM pedidos ORDER BY creado_en DESC LIMIT 8")->fetchAll();
?>

<div class="grid-stats">
    <div class="stat-box"><div class="num"><?= $totalPedidosHoy ?></div><div class="lbl">Pedidos hoy</div></div>
    <div class="stat-box"><div class="num"><?= formatoPrecio($ventasHoy) ?></div><div class="lbl">Ventas hoy</div></div>
    <div class="stat-box"><div class="num"><?= $pendientes ?></div><div class="lbl">Pedidos pendientes</div></div>
    <div class="stat-box"><div class="num"><?= $totalProductos ?></div><div class="lbl">Productos activos</div></div>
</div>

<div class="card">
    <h3>Últimos pedidos</h3>
    <table>
        <thead><tr><th>Código</th><th>Cliente</th><th>Entrega</th><th>Pago</th><th>Total</th><th>Estado</th><th>Fecha</th></tr></thead>
        <tbody>
        <?php foreach ($ultimosPedidos as $p): ?>
            <tr>
                <td><a href="pedidos.php?ver=<?= $p['id'] ?>"><?= limpiar($p['codigo']) ?></a></td>
                <td><?= limpiar($p['cliente_nombre']) ?></td>
                <td><?= $p['tipo_entrega'] === 'delivery' ? '🛵 Delivery' : '🏠 Recojo' ?></td>
                <td><?= ['efectivo'=>'💵 Efectivo','yape_plin'=>'📲 Yape (Culqi)','tarjeta'=>'💳 Tarjeta'][$p['metodo_pago']] ?></td>
                <td><?= formatoPrecio($p['total']) ?></td>
                <td><span class="badge badge-<?= $p['estado'] ?>"><?= $p['estado'] ?></span></td>
                <td><?= date('d/m H:i', strtotime($p['creado_en'])) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($ultimosPedidos)): ?>
            <tr><td colspan="7" style="text-align:center;color:#999;">Aún no hay pedidos.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
