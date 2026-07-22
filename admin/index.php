<?php
$tituloPagina = 'Dashboard';
$paginaActual = 'dashboard';

require __DIR__ . '/_layout_top.php';

$db = getDB();
$totalPedidosHoy = $db->query("SELECT COUNT(*) c FROM pedidos WHERE DATE(creado_en) = CURDATE()")->fetch()['c'];
$ventasHoy = $db->query("SELECT COALESCE(SUM(total),0) t FROM pedidos WHERE DATE(creado_en) = CURDATE() AND estado != 'cancelado'")->fetch()['t'];
$pendientes = $db->query("SELECT COUNT(*) c FROM pedidos WHERE estado = 'pendiente'")->fetch()['c'];
$totalProductos = $db->query("SELECT COUNT(*) c FROM productos WHERE disponible = 1")->fetch()['c'];

$ultimosPedidos = $db->query("SELECT * FROM pedidos ORDER BY creado_en DESC LIMIT 8")->fetchAll();

// ------------------------------------------------------------------
// MODO DE PRUEBA: pon esto en true para ver las tarjetas con datos
// de ejemplo mientras no tengas pedidos reales. Vuelve a false cuando
// quieras usar los datos reales de la base de datos.
// ------------------------------------------------------------------
$modoPrueba = false;
if ($modoPrueba) {
    $totalPedidosHoy = 12;
    $ventasHoy = 456.50;
    $pendientes = 4;
    $totalProductos = 28;
}

$pedidosAyer = $db->query("SELECT COUNT(*) c FROM pedidos WHERE DATE(creado_en) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)")->fetch()['c'];
$ventasAyer  = $db->query("SELECT COALESCE(SUM(total),0) t FROM pedidos WHERE DATE(creado_en) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND estado != 'cancelado'")->fetch()['t'];

$totalPedidosGeneral   = $db->query("SELECT COUNT(*) c FROM pedidos")->fetch()['c'];
$totalProductosGeneral = $db->query("SELECT COUNT(*) c FROM productos")->fetch()['c'];

$maxPedidosSemana = $db->query("
    SELECT COALESCE(MAX(c), 1) m FROM (
        SELECT COUNT(*) c FROM pedidos
        WHERE creado_en >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(creado_en)
    ) x
")->fetch()['m'];

$maxVentasSemana = $db->query("
    SELECT COALESCE(MAX(t), 1) m FROM (
        SELECT COALESCE(SUM(total),0) t FROM pedidos
        WHERE creado_en >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND estado != 'cancelado'
        GROUP BY DATE(creado_en)
    ) x
")->fetch()['m'];
if ($maxPedidosSemana == 0) { $maxPedidosSemana = 1; }
if ($maxVentasSemana == 0) { $maxVentasSemana = 1; }

$pctLlenadoPedidos    = min($totalPedidosHoy / $maxPedidosSemana * 100, 100);
$pctVentas            = round(min($ventasHoy / $maxVentasSemana * 100, 100));
$pctLlenadoPendientes = $totalPedidosGeneral > 0 ? min($pendientes / $totalPedidosGeneral * 100, 100) : 0;
$pctLlenadoProductos  = $totalProductosGeneral > 0 ? min($totalProductos / $totalProductosGeneral * 100, 100) : 0;

if ($modoPrueba) {
    $pctLlenadoPedidos = 60;
    $pctVentas = 22;
    $pctLlenadoPendientes = 5;
    $pctLlenadoProductos = 88;
}

function pintarIcono(string $clase): void
{
    echo '<div style="width:38px;height:38px;border-radius:12px;background:rgba(255,255,255,.18);'
       . 'display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff;flex-shrink:0;'
       . 'margin:-4px 0 0 -4px;">'
       . '<i class="ti ' . $clase . '"></i></div>';
}

function pintarDona(string $texto, float $pctLlenado): void
{
    $r = 15;
    $circun = 2 * pi() * $r;
    $pctLlenado = max(min($pctLlenado, 100), 0);
    $largo = $circun * ($pctLlenado / 100);
    ?>
    <div style="width:40px;height:40px;position:relative;flex-shrink:0;">
        <svg viewBox="0 0 36 36" width="40" height="40" style="display:block;">
            <circle cx="18" cy="18" r="<?= $r ?>" fill="none" stroke="rgba(255,255,255,.28)" stroke-width="3.5"/>
            <circle cx="18" cy="18" r="<?= $r ?>" fill="none" stroke="#4ade80" stroke-width="3.5"
                stroke-linecap="round" stroke-dasharray="<?= round($largo, 2) ?> <?= round($circun, 2) ?>"
                transform="rotate(-90 18 18)"/>
        </svg>
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff;">
            <?= $texto ?>
        </div>
    </div>
    <?php
}

function pintarOlas(): void
{
    $pathOla = 'M0,30 C50,10 50,50 100,30 C150,10 150,50 200,30 '
             . 'C250,10 250,50 300,30 L300,80 L0,80 Z';
    ?>
    <div class="stat-waves">
        <div class="wave-layer wave-c"><svg viewBox="0 0 300 80" preserveAspectRatio="none"><path d="<?= $pathOla ?>" fill="#fff"/></svg></div>
        <div class="wave-layer wave-b"><svg viewBox="0 0 300 80" preserveAspectRatio="none"><path d="<?= $pathOla ?>" fill="#fff"/></svg></div>
        <div class="wave-layer wave-a"><svg viewBox="0 0 300 80" preserveAspectRatio="none"><path d="<?= $pathOla ?>" fill="#fff"/></svg></div>
    </div>
    <?php
}
?>

<div class="grid-stats">

    <div class="stat-frame">
        <div class="stat-box stat-pink">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;position:relative;z-index:2;margin-bottom:10px;">
                <?php pintarIcono('ti-shopping-cart'); ?>
                <?php pintarDona((string) $totalPedidosHoy, $pctLlenadoPedidos); ?>
            </div>
            <div class="stat-lbl">Pedidos hoy</div>
<div class="stat-num" data-target="<?= $totalPedidosHoy ?>" data-decimales="0">0</div>            <?php pintarOlas(); ?>
        </div>
    </div>

    <div class="stat-frame">
        <div class="stat-box stat-green">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;position:relative;z-index:2;margin-bottom:10px;">
                <?php pintarIcono('ti-cash-banknote'); ?>
                <?php pintarDona($pctVentas . '%', $pctVentas); ?>
            </div>
            <div class="stat-lbl">Ventas hoy</div>
<div class="stat-num" data-target="<?= $ventasHoy ?>" data-prefijo="S/ " data-decimales="2">S/ 0.00</div>            <?php pintarOlas(); ?>
        </div>
    </div>

    <div class="stat-frame">
        <div class="stat-box stat-purple">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;position:relative;z-index:2;margin-bottom:10px;">
                <?php pintarIcono('ti-clock-hour-4'); ?>
                <?php pintarDona((string) $pendientes, $pctLlenadoPendientes); ?>
            </div>
            <div class="stat-lbl">Pedidos pendientes</div>
<div class="stat-num" data-target="<?= $pendientes ?>" data-decimales="0">0</div>            <?php pintarOlas(); ?>
        </div>
    </div>

    <div class="stat-frame">
        <div class="stat-box stat-orange">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;position:relative;z-index:2;margin-bottom:10px;">
                <?php pintarIcono('ti-package'); ?>
                <?php pintarDona((string) $totalProductos, $pctLlenadoProductos); ?>
            </div>
            <div class="stat-lbl">Productos activos</div>
<div class="stat-num" data-target="<?= $totalProductos ?>" data-decimales="0">0</div>            <?php pintarOlas(); ?>
        </div>
    </div>

</div>

<div class="card">
    <h3>Últimos pedidos</h3>
    <div class="tabla-controles">
    <button type="button" class="btn-scroll-tabla btn-scroll-izq" aria-label="Desplazar tabla a la izquierda"><i class="ti ti-chevron-left"></i></button>
    <button type="button" class="btn-scroll-tabla btn-scroll-der" aria-label="Desplazar tabla a la derecha"><i class="ti ti-chevron-right"></i></button>
</div>
    <div class="tabla-scroll">
    <table>
<thead><tr>
    <th><i class="ti ti-hash"></i>Código</th>
    <th><i class="ti ti-user"></i>Cliente</th>
    <th><i class="ti ti-truck-delivery"></i>Entrega</th>
    <th><i class="ti ti-credit-card"></i>Pago</th>
    <th><i class="ti ti-currency-dollar"></i>Total</th>
    <th><i class="ti ti-flag"></i>Estado</th>
    <th><i class="ti ti-calendar"></i>Fecha</th>
</tr></thead>        <tbody>
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
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>