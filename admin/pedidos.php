<?php
// Detectamos si esta petición viene de nuestro JS (fetch) para no volver
// a imprimir el HTML de todo el layout (sidebar, topbar, etc.), solo la
// tabla + filtros + paginación.
$esAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($esAjax) {
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/functions.php';
    requerirLogin();
} else {
    $tituloPagina = 'Pedidos';
    $paginaActual = 'pedidos';
    require __DIR__ . '/_layout_top.php';
}

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

// ----- Paginación: 12 pedidos por página -----
$porPagina = 12;
$paginaPedidos = max(1, (int) ($_GET['pagina'] ?? 1));

$sqlConteo = 'SELECT COUNT(*) c FROM pedidos';
if ($filtroEstado) {
    $sqlConteo .= ' WHERE estado = :estado';
}
$stmtConteo = $db->prepare($sqlConteo);
if ($filtroEstado) {
    $stmtConteo->bindValue(':estado', $filtroEstado);
}
$stmtConteo->execute();
$totalPedidosFiltro = (int) $stmtConteo->fetch()['c'];
$totalPaginasPedidos = max(1, (int) ceil($totalPedidosFiltro / $porPagina));
$paginaPedidos = min($paginaPedidos, $totalPaginasPedidos);
$offsetPedidos = ($paginaPedidos - 1) * $porPagina;

$sql = 'SELECT * FROM pedidos';
if ($filtroEstado) {
    $sql .= ' WHERE estado = :estado';
}
$sql .= ' ORDER BY creado_en DESC LIMIT :limite OFFSET :offset';
$stmt = $db->prepare($sql);
if ($filtroEstado) {
    $stmt->bindValue(':estado', $filtroEstado);
}
$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offsetPedidos, PDO::PARAM_INT);
$stmt->execute();
$pedidos = $stmt->fetchAll();

// Para que los links de paginación conserven el filtro de estado actual
$queryFiltro = $filtroEstado ? '&estado=' . urlencode($filtroEstado) : '';

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

// Iconos por tipo de entrega y método de pago (mismos que usamos en el dashboard)
function iconoEntrega(string $tipo): string
{
    return $tipo === 'delivery'
        ? '<i class="ti ti-motorbike"></i> Delivery'
        : '<i class="ti ti-home"></i> Recojo';
}

function iconoPago(string $metodo, bool $conTexto = false): string
{
    $mapa = [
        'efectivo'  => ['ti-cash', 'Efectivo'],
        'yape_plin' => ['ti-device-mobile', 'Yape (Culqi)'],
        'tarjeta'   => ['ti-credit-card', 'Tarjeta (Culqi)'],
    ];
    if (!isset($mapa[$metodo])) return '';
    [$clase, $texto] = $mapa[$metodo];
    return '<i class="ti ' . $clase . '"></i>' . ($conTexto ? ' ' . $texto : '');
}
?>

<?php if ($mensaje && !$esAjax): ?><div class="alerta-ok"><i class="ti ti-circle-check"></i> <?= limpiar($mensaje) ?></div><?php endif; ?>

<?php if ($pedidoDetalle): ?>
<div class="card">
    <h3><i class="ti ti-receipt"></i> Detalle del pedido <?= limpiar($pedidoDetalle['codigo']) ?></h3>
    <div class="detalle-info">
        <p><i class="ti ti-user"></i> <b>Cliente:</b> <?= limpiar($pedidoDetalle['cliente_nombre']) ?> — <?= limpiar($pedidoDetalle['cliente_telefono']) ?></p>
        <p><i class="ti ti-truck-delivery"></i> <b>Entrega:</b> <?= $pedidoDetalle['tipo_entrega'] === 'delivery' ? '<i class="ti ti-motorbike"></i> Delivery a ' . limpiar($pedidoDetalle['direccion']) : '<i class="ti ti-home"></i> Recojo en local' ?></p>
        <?php if ($pedidoDetalle['referencia']): ?><p><i class="ti ti-map-pin"></i> <b>Referencia:</b> <?= limpiar($pedidoDetalle['referencia']) ?></p><?php endif; ?>
        <p><i class="ti ti-credit-card"></i> <b>Pago:</b> <?= iconoPago($pedidoDetalle['metodo_pago'], true) ?>
            <?php if ($pedidoDetalle['culqi_charge_id']): ?> — ID cargo: <code><?= limpiar($pedidoDetalle['culqi_charge_id']) ?></code><?php endif; ?>
        </p>
        <?php if ($pedidoDetalle['notas']): ?><p><i class="ti ti-notes"></i> <b>Notas:</b> <?= limpiar($pedidoDetalle['notas']) ?></p><?php endif; ?>
    </div>

    <table style="margin-top:14px;">
        <thead><tr>
            <th><i class="ti ti-tools-kitchen-2"></i> Producto</th>
            <th><i class="ti ti-hash"></i> Cant.</th>
            <th><i class="ti ti-currency-dollar"></i> P. Unit.</th>
            <th><i class="ti ti-sum"></i> Subtotal</th>
        </tr></thead>
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
    <div class="detalle-totales">
        <p>Subtotal: <?= formatoPrecio($pedidoDetalle['subtotal']) ?></p>
        <?php if ($pedidoDetalle['costo_delivery'] > 0): ?><p>Delivery: <?= formatoPrecio($pedidoDetalle['costo_delivery']) ?></p><?php endif; ?>
        <p class="detalle-total-final">Total: <?= formatoPrecio($pedidoDetalle['total']) ?></p>
    </div>

    <form method="POST" class="form-detalle-estado">
        <input type="hidden" name="accion" value="cambiar_estado">
        <input type="hidden" name="id" value="<?= $pedidoDetalle['id'] ?>">

        <div class="dropdown-neu" id="dropdown-estado">
            <button type="button" class="dropdown-neu-btn" id="dropdown-estado-btn">
                <span class="badge badge-<?= $pedidoDetalle['estado'] ?>" id="dropdown-estado-badge"><?= $pedidoDetalle['estado'] ?></span>
                <i class="ti ti-chevron-down"></i>
            </button>
            <input type="hidden" name="estado" id="dropdown-estado-input" value="<?= $pedidoDetalle['estado'] ?>">
            <div class="dropdown-neu-lista" id="dropdown-estado-lista">
                <?php foreach (['pendiente','pagado','en_preparacion','en_camino','entregado','cancelado'] as $e): ?>
                    <div class="dropdown-neu-opcion <?= $pedidoDetalle['estado']===$e ? 'activa' : '' ?>" data-valor="<?= $e ?>">
                        <span class="badge badge-<?= $e ?>"><?= $e ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <button class="btn btn-primario" type="submit"><i class="ti ti-refresh"></i> Actualizar estado</button>
        <a href="pedidos.php" class="btn btn-secundario"><i class="ti ti-arrow-left"></i> Volver al listado</a>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropdown = document.getElementById('dropdown-estado');
    if (!dropdown) return;
    const btn = document.getElementById('dropdown-estado-btn');
    const badge = document.getElementById('dropdown-estado-badge');
    const input = document.getElementById('dropdown-estado-input');
    const lista = document.getElementById('dropdown-estado-lista');

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('abierto');
    });

    lista.querySelectorAll('.dropdown-neu-opcion').forEach(function (opcion) {
        opcion.addEventListener('click', function () {
            const valor = opcion.getAttribute('data-valor');
            input.value = valor;
            badge.textContent = valor;
            badge.className = 'badge badge-' + valor;
            lista.querySelectorAll('.dropdown-neu-opcion').forEach(function (o) { o.classList.remove('activa'); });
            opcion.classList.add('activa');
            dropdown.classList.remove('abierto');
        });
    });

    document.addEventListener('click', function () {
        dropdown.classList.remove('abierto');
    });
});
</script>
<?php else: ?>

<div id="listado-pedidos-ajax">

<div class="card" style="padding-bottom:6px;">
    <div class="filtros-ajax" style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="pedidos.php" class="btn <?= $filtroEstado==='' ? 'btn-primario' : 'btn-secundario' ?> btn-sm"><i class="ti ti-list"></i> Todos</a>
        <?php foreach (['pendiente','pagado','en_preparacion','en_camino','entregado','cancelado'] as $e): ?>
            <a href="?estado=<?= $e ?>" class="badge badge-<?= $e ?> filtro-badge <?= $filtroEstado===$e ? 'filtro-activo' : '' ?>"><?= $e ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="tabla-controles">
        <button type="button" class="btn-scroll-tabla btn-scroll-izq" aria-label="Desplazar tabla a la izquierda"><i class="ti ti-chevron-left"></i></button>
        <button type="button" class="btn-scroll-tabla btn-scroll-der" aria-label="Desplazar tabla a la derecha"><i class="ti ti-chevron-right"></i></button>
    </div>
    <div class="tabla-scroll">
    <table id="tabla-pedidos">
        <thead><tr>
            <th><i class="ti ti-hash"></i>Código</th>
            <th><i class="ti ti-user"></i>Cliente</th>
            <th><i class="ti ti-truck-delivery"></i>Entrega</th>
            <th><i class="ti ti-credit-card"></i>Pago</th>
            <th><i class="ti ti-currency-dollar"></i>Total</th>
            <th><i class="ti ti-flag"></i>Estado</th>
            <th><i class="ti ti-calendar"></i>Fecha</th>
            <th><i class="ti ti-settings"></i>Acciones</th>
        </tr></thead>
        <tbody>
        <?php foreach ($pedidos as $p): ?>
            <tr>
                <td><?= limpiar($p['codigo']) ?></td>
                <td><?= limpiar($p['cliente_nombre']) ?></td>
                <td><?= iconoEntrega($p['tipo_entrega']) ?></td>
                <td><?= iconoPago($p['metodo_pago']) ?></td>
                <td><?= formatoPrecio($p['total']) ?></td>
                <td><span class="badge badge-<?= $p['estado'] ?>"><?= $p['estado'] ?></span></td>
                <td><?= date('d/m H:i', strtotime($p['creado_en'])) ?></td>
                <td><a href="?ver=<?= $p['id'] ?><?= $queryFiltro ?>" class="btn btn-secundario btn-sm"><i class="ti ti-eye"></i> Ver</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($pedidos)): ?><tr><td colspan="8" style="text-align:center;color:#999;">No hay pedidos.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>

    <?php if ($totalPaginasPedidos > 1): ?>
    <div class="paginacion-pedidos">
        <a href="?pagina=<?= max($paginaPedidos - 1, 1) . $queryFiltro ?>"
           class="btn-scroll-tabla <?= $paginaPedidos <= 1 ? 'deshabilitado' : '' ?>"
           aria-label="Página anterior">
            <i class="ti ti-chevron-left"></i>
        </a>
        <span class="paginacion-texto">Página <?= $paginaPedidos ?> de <?= $totalPaginasPedidos ?></span>
        <a href="?pagina=<?= min($paginaPedidos + 1, $totalPaginasPedidos) . $queryFiltro ?>"
           class="btn-scroll-tabla <?= $paginaPedidos >= $totalPaginasPedidos ? 'deshabilitado' : '' ?>"
           aria-label="Página siguiente">
            <i class="ti ti-chevron-right"></i>
        </a>
    </div>
    <?php endif; ?>
</div>

</div><!-- /listado-pedidos-ajax -->

<?php endif; ?>

<?php if (!$esAjax): ?>
<?php require __DIR__ . '/_layout_bottom.php'; ?>
<?php endif; ?>