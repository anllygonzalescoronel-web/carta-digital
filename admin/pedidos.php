<?php
$tituloPagina = 'Pedidos';
$paginaActual = 'pedidos';
require __DIR__ . '/_layout_top.php';

$db = getDB();
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cambiar_estado') {
    $id = (int)$_POST['id'];
    $estado = $_POST['estado'];
    $estadosValidos = ['pendiente','pagado','en_preparacion','en_camino','entregado','cancelado'];
    if (in_array($estado, $estadosValidos, true)) {
        $db->prepare('UPDATE pedidos SET estado = :e WHERE id = :id')->execute(['e'=>$estado,'id'=>$id]);
        $mensaje = 'Estado actualizado.';
    }
}

$filtroEstado = $_GET['estado'] ?? '';
$sql = 'SELECT * FROM pedidos';
$params = [];
if ($filtroEstado) {
    $sql .= ' WHERE estado = :estado';
    $params['estado'] = $filtroEstado;
}
$sql .= ' ORDER BY creado_en DESC LIMIT 200';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$pedidos = $stmt->fetchAll();

$pedidoDetalle = null;
$detalleItems = [];
if (!empty($_GET['ver'])) {
    $stmt = $db->prepare('SELECT * FROM pedidos WHERE id = :id');
    $stmt->execute(['id' => (int)$_GET['ver']]);
    $pedidoDetalle = $stmt->fetch();
    if ($pedidoDetalle) {
        $stmt2 = $db->prepare('SELECT * FROM pedido_detalle WHERE pedido_id = :id');
        $stmt2->execute(['id' => $pedidoDetalle['id']]);
        $detalleItems = $stmt2->fetchAll();
    }
}
?>

<?php if ($mensaje): ?><div class="alerta-ok"><?= limpiar($mensaje) ?></div><?php endif; ?>

<?php if ($pedidoDetalle): ?>
<div class="card">
    <h3>Detalle del pedido <?= limpiar($pedidoDetalle['codigo']) ?></h3>
    <p><b>Cliente:</b> <?= limpiar($pedidoDetalle['cliente_nombre']) ?> — <?= limpiar($pedidoDetalle['cliente_telefono']) ?></p>
    <p><b>Entrega:</b> <?= $pedidoDetalle['tipo_entrega'] === 'delivery' ? '🛵 Delivery a ' . limpiar($pedidoDetalle['direccion']) : '🏠 Recojo en local' ?></p>
    <?php if ($pedidoDetalle['referencia']): ?><p><b>Referencia:</b> <?= limpiar($pedidoDetalle['referencia']) ?></p><?php endif; ?>
    <p><b>Pago:</b> <?= ['efectivo'=>'💵 Efectivo','yape_plin'=>'📲 Yape (Culqi)','tarjeta'=>'💳 Tarjeta (Culqi)'][$pedidoDetalle['metodo_pago']] ?>
        <?php if ($pedidoDetalle['culqi_charge_id']): ?> — ID cargo: <code><?= limpiar($pedidoDetalle['culqi_charge_id']) ?></code><?php endif; ?>
    </p>
    <?php if ($pedidoDetalle['notas']): ?><p><b>Notas:</b> <?= limpiar($pedidoDetalle['notas']) ?></p><?php endif; ?>

    <table style="margin-top:14px;">
        <thead><tr><th>Producto</th><th>Cant.</th><th>P. Unit.</th><th>Subtotal</th></tr></thead>
        <tbody>
        <?php foreach ($detalleItems as $d): ?>
            <tr>
                <td><?= limpiar($d['nombre_producto']) ?></td>
                <td><?= $d['cantidad'] ?></td>
                <td><?= formatoPrecio($d['precio_unitario']) ?></td>
                <td><?= formatoPrecio($d['subtotal']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p style="margin-top:10px;">Subtotal: <?= formatoPrecio($pedidoDetalle['subtotal']) ?></p>
    <?php if ($pedidoDetalle['costo_delivery'] > 0): ?><p>Delivery: <?= formatoPrecio($pedidoDetalle['costo_delivery']) ?></p><?php endif; ?>
    <p style="font-size:17px;font-weight:800;color:#E8590C;">Total: <?= formatoPrecio($pedidoDetalle['total']) ?></p>

    <form method="POST" style="margin-top:14px;display:flex;gap:10px;align-items:center;">
        <input type="hidden" name="accion" value="cambiar_estado">
        <input type="hidden" name="id" value="<?= $pedidoDetalle['id'] ?>">
        <select name="estado" style="padding:8px;border-radius:8px;border:1px solid #ddd;">
            <?php foreach (['pendiente','pagado','en_preparacion','en_camino','entregado','cancelado'] as $e): ?>
                <option value="<?= $e ?>" <?= $pedidoDetalle['estado'] === $e ? 'selected' : '' ?>><?= $e ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primario" type="submit">Actualizar estado</button>
        <a href="pedidos.php" class="btn btn-secundario">← Volver al listado</a>
    </form>
</div>
<?php else: ?>

<div class="card" style="padding-bottom:6px;">
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="pedidos.php" class="btn <?= $filtroEstado==='' ? 'btn-primario' : 'btn-secundario' ?> btn-sm">Todos</a>
        <?php foreach (['pendiente','pagado','en_preparacion','en_camino','entregado','cancelado'] as $e): ?>
            <a href="?estado=<?= $e ?>" class="btn <?= $filtroEstado===$e ? 'btn-primario' : 'btn-secundario' ?> btn-sm"><?= $e ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <table>
        <thead><tr><th>Código</th><th>Cliente</th><th>Entrega</th><th>Pago</th><th>Total</th><th>Estado</th><th>Fecha</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($pedidos as $p): ?>
            <tr>
                <td><?= limpiar($p['codigo']) ?></td>
                <td><?= limpiar($p['cliente_nombre']) ?></td>
                <td><?= $p['tipo_entrega'] === 'delivery' ? '🛵 Delivery' : '🏠 Recojo' ?></td>
                <td><?= ['efectivo'=>'💵','yape_plin'=>'📲','tarjeta'=>'💳'][$p['metodo_pago']] ?></td>
                <td><?= formatoPrecio($p['total']) ?></td>
                <td><span class="badge badge-<?= $p['estado'] ?>"><?= $p['estado'] ?></span></td>
                <td><?= date('d/m H:i', strtotime($p['creado_en'])) ?></td>
                <td><a href="?ver=<?= $p['id'] ?>" class="btn btn-secundario btn-sm">Ver</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($pedidos)): ?><tr><td colspan="8" style="text-align:center;color:#999;">No hay pedidos.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
