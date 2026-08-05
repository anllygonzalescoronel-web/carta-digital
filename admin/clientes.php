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
.cli-head { display:flex; justify-content:space-between; gap:18px; align-items:flex-start; flex-wrap:wrap; margin-bottom:16px; }
.cli-head h2 { margin:0; font-size:26px; color: var(--pos-texto, #0f172a); }
.cli-head p { margin:6px 0 0; color: var(--pos-muted, #64748b); font-size:13px; }

/* ── Stats hundidas con color ── */
.cli-stats {
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:14px;
    margin-bottom:18px;
}
.cli-stat-frame {
    position: relative;
    border-radius: 22px;
    padding: 10px;
    overflow: hidden;
    isolation: isolate;
}
.cli-stat-frame::after {
    content: '';
    position: absolute;
    inset: 4.5px;
    border-radius: 18px;
    background: var(--neu-base);
    box-shadow: inset 5px 5px 12px var(--neu-sombra-oscura), inset -5px -5px 12px var(--neu-sombra-clara);
    z-index: 1;
}
.cli-stat-frame .cli-stat {
    position: relative;
    z-index: 2;
}
.cli-stat {
    position: relative;
    overflow: hidden;
    border-radius: 16px;
    padding: 16px;
    min-height: 92px;
    color: #fff;
    box-shadow: 4px 4px 10px rgba(0,0,0,.18);
    transition: transform .2s ease, box-shadow .2s ease;
    isolation: isolate;
}
.cli-stat:hover { transform: translateY(-3px); }

.cli-stat .cli-stat-content {
    position: relative;
    z-index: 2;
}

.cli-blob {
    position: absolute;
    z-index: 1;
    width: 34px;
    height: 34px;
    border-radius: 4px;
    background-color: var(--blob-color, rgba(255,255,255,.6));
    opacity: .5;
    filter: blur(4px);
    mix-blend-mode: overlay;
    animation: cliBlobBounce 6s linear infinite, cliBlobRotar 6s linear infinite;
    pointer-events: none;
    transform: rotate(45deg);
}
@keyframes cliBlobBounce {
    
    0%   { top: -15%; left: -10%; }
    25%  { top: -15%; left: 92%; }
    50%  { top: 78%; left: 92%; }
    75%  { top: 78%; left: -10%; }
    100% { top: -15%; left: -10%; }
}

@keyframes cliBlobRotar {
    from { transform: rotate(45deg); }
    to   { transform: rotate(405deg); }
}

@media (prefers-reduced-motion: reduce) {
    .cli-blob { animation: none; }
}

/* Borde neón que rota alrededor de cada tarjeta, con el color propio */
.cli-stat-frame::before {
    content: '';
    position: absolute;
    inset: -60%;
    background: conic-gradient(from 0deg, transparent 0%, var(--neon-color, #fff) 12%, transparent 26%);
    animation: cliNeonRotar 4s linear infinite;
    pointer-events: none;
    z-index: 0;
}

@keyframes cliNeonRotar {
    to { transform: rotate(360deg); }
}

.cli-stat-frame.frame-purple::before { --neon-color: #a78bfa; }
.cli-stat-frame.frame-blue::before   { --neon-color: #7dd3fc; }
.cli-stat-frame.frame-amber::before  { --neon-color: #fdba74; }
.cli-stat-frame.frame-green::before  { --neon-color: #86efac; }

.cli-stat-frame.frame-purple .cli-blob { --blob-color: #7ea8ff; }
.cli-stat-frame.frame-blue .cli-blob   { --blob-color: #0ea5e9; }
.cli-stat-frame.frame-amber .cli-blob  { --blob-color: #ffb37a; }
.cli-stat-frame.frame-green .cli-blob  { --blob-color: #7fe0a8; }

@media (prefers-reduced-motion: reduce) {
    .cli-stat-frame::before { animation: none; }
}
.cli-stat.c-purple { background: linear-gradient(135deg, #2a1b6a 0%, #5b4bd6 45%, #7ea8ff 100%); }
.cli-stat.c-blue   { background: linear-gradient(135deg, #0c4a6e 0%, #0284c7 45%, #7dd3fc 100%); }
.cli-stat.c-amber  { background: linear-gradient(135deg, #7a2b0f 0%, #e8590c 45%, #ffb37a 100%); }
.cli-stat.c-green  { background: linear-gradient(135deg, #0f4c3a 0%, #1f9e6d 45%, #7fe0a8 100%); }
.cli-stat strong { display:block; font-size:24px; font-weight:800; line-height:1.2; color:#fff; }
.cli-stat span { font-size:11.5px; font-weight:600; opacity:.92; color:#fff; margin-top:2px; display:block; }

/* ── Layout ── */
.cli-layout { display:grid; grid-template-columns:minmax(0,1.15fr) minmax(320px,.85fr); gap:16px; align-items:start; }

.cli-table-wrap, .cli-detail {
    background: var(--neu-base);
    border: none;
    border-radius: 20px;
    box-shadow: 8px 8px 18px var(--neu-sombra-oscura), -8px -8px 18px var(--neu-sombra-clara);
    overflow: hidden;
}

.cli-table { width:100%; border-collapse:collapse; table-layout: fixed; }
.cli-table th:nth-child(1), .cli-table td:nth-child(1) { width: 24%; }
.cli-table th:nth-child(2), .cli-table td:nth-child(2) { width: 9%; }
.cli-table th:nth-child(3), .cli-table td:nth-child(3) { width: 8%; }
.cli-table th:nth-child(4), .cli-table td:nth-child(4) { width: 9%; }
.cli-table th:nth-child(5), .cli-table td:nth-child(5) { width: 15%; }
.cli-table th:nth-child(6), .cli-table td:nth-child(6) { width: 15%; }
.cli-table th:nth-child(7), .cli-table td:nth-child(7) { width: 14%; white-space: nowrap; padding-left: 9px; }
.cli-table th, .cli-table td {
    padding:12px 10px; text-align:left;
    border-bottom:1px solid rgba(0,0,0,.06);
    font-size:12.5px; color: var(--pos-texto, #334155);
    word-wrap: break-word;
    overflow-wrap: break-word;
}
.cli-table-wrap {
    overflow: hidden;
}
.cli-table th {
    font-size:10.5px; letter-spacing:.03em; text-transform:uppercase;
    color: var(--pos-muted, #64748b);
    background: transparent;
    white-space: nowrap;
}
.cli-table tbody tr:hover td { background: rgba(0,0,0,.02); }

.cli-name { font-weight:800; color: var(--pos-texto, #0f172a); }
.cli-sub { display:block; font-size:12px; color: var(--pos-muted, #64748b); margin-top:4px; }

.pill { display:inline-flex; padding:5px 10px; border-radius:999px; font-size:11px; font-weight:800; }
.pill-google { background:#dbeafe; color:#1d4ed8; }
.pill-local  { background:#dcfce7; color:#166534; }

.cli-link {
    display:inline-flex; align-items:center; gap:6px;
    padding:9px 14px; border-radius:12px;
    background: linear-gradient(135deg, #ff8a3d, #E8590C);
    color:#fff; text-decoration:none; font-size:12px; font-weight:800;
    box-shadow: 4px 4px 10px rgba(232,89,12,.35);
    transition: transform .15s ease;
}
.cli-link:hover { transform: translateY(-2px); }

.cli-detail { padding:20px; }
.cli-detail h3 { margin:0 0 6px; color: var(--pos-texto, #0f172a); }
.cli-detail p  { margin:0 0 14px; color: var(--pos-muted, #64748b); font-size:13px; }

.cli-detail-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; margin-bottom:14px; }
.cli-detail-box {
    border: none;
    border-radius: 16px;
    padding: 14px;
    background: var(--neu-base);
    box-shadow: inset 4px 4px 9px var(--neu-sombra-oscura), inset -4px -4px 9px var(--neu-sombra-clara);
}
.cli-detail-box span { display:block; font-size:11px; color: var(--pos-muted, #64748b); margin-bottom:4px; }
.cli-detail-box strong { display:block; font-size:15px; color: var(--pos-texto, #0f172a); }
.cli-detail-box.full { grid-column:1 / -1; }

.cli-orders { display:grid; gap:10px; }
.cli-order {
    border: none;
    border-radius: 14px;
    padding: 14px;
    background: var(--neu-base);
    box-shadow: inset 4px 4px 9px var(--neu-sombra-oscura), inset -4px -4px 9px var(--neu-sombra-clara);
}
.cli-order-top { display:flex; justify-content:space-between; gap:10px; align-items:center; margin-bottom:6px; }
.cli-order-meta { display:flex; gap:10px; flex-wrap:wrap; color: var(--pos-muted, #64748b); font-size:12px; }

.cli-empty { font-size:13px; color: var(--pos-muted, #64748b); }

.cli-status-chips { display:flex; gap:8px; flex-wrap:wrap; margin:12px 0 14px; }
.cli-status-chips span {
    display:inline-flex; padding:6px 10px; border-radius:999px;
    background: var(--neu-base);
    color: var(--pos-texto, #334155);
    font-size:11px; font-weight:800;
    box-shadow: 3px 3px 7px var(--neu-sombra-oscura), -3px -3px 7px var(--neu-sombra-clara);
}

.cli-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:12px; }

@media (max-width:980px) {
    .cli-stats { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .cli-layout { grid-template-columns:1fr; }
}

/* ── Modo oscuro ── */
body.modo-oscuro .cli-head h2 { color: #f1f5f9; }
body.modo-oscuro .cli-head p  { color: #94a3b8; }
body.modo-oscuro .cli-table th,
body.modo-oscuro .cli-table td { color: #cbd5e1; border-bottom-color: rgba(255,255,255,0.08); }
body.modo-oscuro .cli-table th { color: #94a3b8; }
body.modo-oscuro .cli-table tbody tr:hover td { background: rgba(255,255,255,0.03); }
body.modo-oscuro .cli-name { color: #f1f5f9; }
body.modo-oscuro .cli-sub  { color: #94a3b8; }
body.modo-oscuro .cli-detail h3 { color: #f1f5f9; }
body.modo-oscuro .cli-detail p  { color: #94a3b8; }
body.modo-oscuro .cli-detail-box span { color: #94a3b8; }
body.modo-oscuro .cli-detail-box strong { color: #f1f5f9; }
body.modo-oscuro .cli-order-meta { color: #94a3b8; }
body.modo-oscuro .cli-empty { color: #94a3b8; }
body.modo-oscuro .cli-status-chips span { color: #cbd5e1; }
body.modo-oscuro .pill-google { background: rgba(30,64,175,0.25); color: #93c5fd; }
body.modo-oscuro .pill-local  { background: rgba(22,101,52,0.25); color: #86efac; }
</style>

<div class="cli-head">
    <div>
        <h2>Clientes web</h2>
        <p>Controla quién se registró, cómo inició sesión y qué tanto compra desde la web.</p>
    </div>
</div>

<div class="cli-stats">
    <div class="cli-stat-frame frame-purple"><div class="cli-stat c-purple"><div class="cli-blob"></div><div class="cli-stat-content"><strong><?= $totales['clientes'] ?></strong><span>Clientes registrados</span></div></div></div>
    <div class="cli-stat-frame frame-blue"><div class="cli-stat c-blue"><div class="cli-blob"></div><div class="cli-stat-content"><strong><?= $totales['google'] ?></strong><span>Ingresaron con Google</span></div></div></div>
    <div class="cli-stat-frame frame-amber"><div class="cli-stat c-amber"><div class="cli-blob"></div><div class="cli-stat-content"><strong><?= $totales['pedidos'] ?></strong><span>Pedidos vinculados</span></div></div></div>
    <div class="cli-stat-frame frame-green"><div class="cli-stat c-green"><div class="cli-blob"></div><div class="cli-stat-content"><strong><?= formatoPrecio($totales['facturacion']) ?></strong><span>Facturación asociada</span></div></div></div>
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