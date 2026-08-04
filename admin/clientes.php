<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requerirRol(['admin']);
asegurarTablaClientesWeb();

$tituloPagina = 'Clientes Web';
$paginaActual = 'clientes';
$clientes = obtenerResumenAdminClientes();
$clienteDetalleId = (int)($_GET['cliente_id'] ?? 0);
$detalleCliente = null;

if ($clienteDetalleId > 0) {
    try {
        $detalleCliente = obtenerDetalleAdminCliente($clienteDetalleId);
    } catch (Throwable $e) {
        $detalleCliente = null;
    }
}

$totales = [
    'clientes' => count($clientes),
    'google' => 0,
    'local' => 0,
    'pedidos' => 0,
    'facturacion' => 0.0,
];
foreach ($clientes as $cliente) {
    if (($cliente['proveedor'] ?? 'local') === 'google') {
        $totales['google']++;
    } else {
        $totales['local']++;
    }
    $totales['pedidos'] += (int)$cliente['pedidos_totales'];
    $totales['facturacion'] += (float)$cliente['total_gastado'];
}

require __DIR__ . '/_layout_top.php';
?>
<style>
.cli-head{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;flex-wrap:wrap;margin-bottom:16px}.cli-head h2{margin:0;font-size:28px}.cli-head p{margin:6px 0 0;color:#64748b}.cli-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:16px}.cli-stat{background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:16px}.cli-stat strong{display:block;font-size:24px;margin-bottom:4px}.cli-stat span{font-size:12px;color:#64748b}.cli-layout{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(320px,.85fr);gap:16px;align-items:start}.cli-table-wrap,.cli-detail{background:#fff;border:1px solid #e5e7eb;border-radius:18px;overflow:auto}.cli-table{width:100%;border-collapse:collapse}.cli-table th,.cli-table td{padding:14px 16px;text-align:left;border-bottom:1px solid #eef2f7;font-size:13px}.cli-table th{font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#64748b;background:#f8fafc}.cli-name{font-weight:800;color:#0f172a}.cli-sub{display:block;font-size:12px;color:#64748b;margin-top:4px}.pill{display:inline-flex;padding:5px 10px;border-radius:999px;font-size:11px;font-weight:800}.pill-google{background:#dbeafe;color:#1d4ed8}.pill-local{background:#eaf7ee;color:#166534}.cli-link{display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border-radius:999px;background:#0f172a;color:#fff;text-decoration:none;font-size:12px;font-weight:800}.cli-detail{padding:18px}.cli-detail h3{margin:0 0 6px}.cli-detail p{margin:0 0 14px;color:#64748b;font-size:13px}.cli-detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-bottom:14px}.cli-detail-box{border:1px solid #eef2f7;border-radius:16px;padding:12px}.cli-detail-box span{display:block;font-size:11px;color:#64748b;margin-bottom:4px}.cli-detail-box strong{display:block;font-size:15px;color:#0f172a}.cli-detail-box.full{grid-column:1 / -1}.cli-orders{display:grid;gap:10px}.cli-order{border:1px solid #eef2f7;border-radius:14px;padding:12px}.cli-order-top{display:flex;justify-content:space-between;gap:10px;align-items:center;margin-bottom:6px}.cli-order-meta{display:flex;gap:10px;flex-wrap:wrap;color:#64748b;font-size:12px}.cli-empty{font-size:13px;color:#64748b}.cli-status-chips{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0 14px}.cli-status-chips span{display:inline-flex;padding:6px 10px;border-radius:999px;background:#f8fafc;color:#334155;font-size:11px;font-weight:800}.cli-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}@media (max-width:980px){.cli-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.cli-layout{grid-template-columns:1fr}}
</style>

<div class="cli-head">
    <div>
        <h2>Clientes web</h2>
        <p>Controla quién se registró, cómo inició sesión y qué tanto compra desde la web.</p>
    </div>
</div>

<div class="cli-stats">
    <div class="cli-stat"><strong><?= $totales['clientes'] ?></strong><span>Clientes registrados</span></div>
    <div class="cli-stat"><strong><?= $totales['google'] ?></strong><span>Ingresaron con Google</span></div>
    <div class="cli-stat"><strong><?= $totales['pedidos'] ?></strong><span>Pedidos vinculados</span></div>
    <div class="cli-stat"><strong><?= formatoPrecio($totales['facturacion']) ?></strong><span>Facturación asociada</span></div>
</div>

<div class="cli-layout">
    <div class="cli-table-wrap">
        <table class="cli-table">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Acceso</th>
                    <th>Pedidos</th>
                    <th>Gastado</th>
                    <th>Última compra</th>
                    <th>Último login</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$clientes): ?>
                <tr><td colspan="7">Aún no hay clientes registrados.</td></tr>
                <?php endif; ?>
                <?php foreach ($clientes as $cliente): ?>
                <tr>
                    <td>
                        <span class="cli-name"><?= limpiar($cliente['nombre']) ?></span>
                        <span class="cli-sub"><?= limpiar($cliente['email']) ?></span>
                        <span class="cli-sub"><?= limpiar($cliente['telefono'] ?: 'Sin teléfono') ?></span>
                    </td>
                    <td>
                        <span class="pill <?= ($cliente['proveedor'] ?? 'local') === 'google' ? 'pill-google' : 'pill-local' ?>">
                            <?= ($cliente['proveedor'] ?? 'local') === 'google' ? 'Google' : 'Cuenta web' ?>
                        </span>
                    </td>
                    <td><?= (int)$cliente['pedidos_totales'] ?></td>
                    <td><?= formatoPrecio($cliente['total_gastado']) ?></td>
                    <td><?= limpiar($cliente['ultima_compra'] ?: '-') ?></td>
                    <td><?= limpiar($cliente['ultimo_login_at'] ?: '-') ?></td>
                    <td><a class="cli-link" href="clientes.php?cliente_id=<?= (int)$cliente['id'] ?>">Ver detalle</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <aside class="cli-detail">
        <?php if (!$detalleCliente): ?>
            <h3>Detalle del cliente</h3>
            <p>Selecciona un cliente para ver su fidelización, estados de pedidos y actividad reciente.</p>
            <div class="cli-empty">Todavía no hay un cliente seleccionado.</div>
        <?php else: ?>
            <h3><?= limpiar($detalleCliente['cliente']['nombre']) ?></h3>
            <p><?= limpiar($detalleCliente['cliente']['email']) ?> · <?= limpiar($detalleCliente['cliente']['telefono'] ?: 'Sin teléfono') ?></p>

            <div class="cli-detail-grid">
                <div class="cli-detail-box"><span>Nivel</span><strong><?= limpiar($detalleCliente['fidelizacion']['nivel'] ?? 'Nuevo') ?></strong></div>
                <div class="cli-detail-box"><span>Ticket promedio</span><strong><?= formatoPrecio($detalleCliente['metricas']['ticket_promedio'] ?? 0) ?></strong></div>
                <div class="cli-detail-box"><span>Total gastado</span><strong><?= formatoPrecio($detalleCliente['metricas']['total_gastado'] ?? 0) ?></strong></div>
                <div class="cli-detail-box"><span>Producto favorito</span><strong><?= limpiar($detalleCliente['metricas']['producto_favorito'] ?: 'Sin dato') ?></strong></div>
                <div class="cli-detail-box full"><span>Mensaje actual de fidelización</span><strong><?= limpiar($detalleCliente['fidelizacion']['mensaje_principal'] ?? 'Sin actividad') ?></strong></div>
            </div>

            <div class="cli-status-chips">
                <?php foreach (($detalleCliente['estados'] ?? []) as $estado => $cantidad): ?>
                    <span><?= limpiar($estado) ?>: <?= (int)$cantidad ?></span>
                <?php endforeach; ?>
                <?php if (empty($detalleCliente['estados'])): ?>
                    <span>Sin pedidos asociados</span>
                <?php endif; ?>
            </div>

            <div class="cli-actions">
                <a class="cli-link" href="../estado-pedido.php?telefono=<?= urlencode((string)($detalleCliente['cliente']['telefono'] ?? '')) ?>" target="_blank" rel="noopener noreferrer">Abrir estado cliente</a>
            </div>

            <div class="cli-orders" style="margin-top:14px;">
                <?php if (empty($detalleCliente['pedidos'])): ?>
                    <div class="cli-empty">Este cliente todavía no tiene pedidos vinculados.</div>
                <?php else: ?>
                    <?php foreach ($detalleCliente['pedidos'] as $pedido): ?>
                    <article class="cli-order">
                        <div class="cli-order-top">
                            <strong><?= limpiar($pedido['codigo']) ?></strong>
                            <span class="pill pill-local"><?= limpiar($pedido['estado']) ?></span>
                        </div>
                        <div class="cli-order-meta">
                            <span><?= formatoPrecio($pedido['total']) ?></span>
                            <span><?= limpiar($pedido['tipo_entrega']) ?></span>
                            <span><?= limpiar($pedido['metodo_pago']) ?></span>
                            <span><?= limpiar($pedido['creado_en']) ?></span>
                        </div>
                    </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </aside>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>