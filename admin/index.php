<?php
// Evita que /admin (sin slash) rompa rutas relativas de assets.
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
if (preg_match('#/admin$#i', $requestPath)) {
    $query = $_SERVER['QUERY_STRING'] ?? '';
    $destino = $requestPath . '/' . ($query !== '' ? ('?' . $query) : '');
    header('Location: ' . $destino, true, 302);
    exit;
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requerirRol(['admin', 'mesero']);

$tituloPagina = 'Dashboard';
$paginaActual = 'dashboard';

require __DIR__ . '/_layout_top.php';

$db = getDB();
$totalPedidosHoy = $db->query("SELECT COUNT(*) c FROM pedidos WHERE DATE(creado_en) = CURDATE()")->fetch()['c'];
$ventasHoy = $db->query("SELECT COALESCE(SUM(total),0) t FROM pedidos WHERE DATE(creado_en) = CURDATE() AND estado != 'cancelado'")->fetch()['t'];
$pendientes = $db->query("SELECT COUNT(*) c FROM pedidos WHERE estado = 'pendiente'")->fetch()['c'];
$totalProductos = $db->query("SELECT COUNT(*) c FROM productos WHERE disponible = 1")->fetch()['c'];

$columnaAperturaCaja = 'creado_en';
try {
    $stmtColsCaja = $db->query("SHOW COLUMNS FROM cajas_turnos");
    $columnasCaja = $stmtColsCaja ? $stmtColsCaja->fetchAll(PDO::FETCH_COLUMN, 0) : [];
    if (in_array('abierta_en', $columnasCaja, true)) {
        $columnaAperturaCaja = 'abierta_en';
    } elseif (in_array('creado_en', $columnasCaja, true)) {
        $columnaAperturaCaja = 'creado_en';
    }
} catch (Throwable $e) {
    $columnaAperturaCaja = 'creado_en';
}

$turnoCajaActual = $db->query("SELECT id, estado, {$columnaAperturaCaja} AS fecha_apertura FROM cajas_turnos ORDER BY id DESC LIMIT 1")->fetch();
$cajaAbierta = $turnoCajaActual && ($turnoCajaActual['estado'] ?? '') === 'abierta';
$textoEstadoCaja = $cajaAbierta ? 'Caja abierta' : 'Caja cerrada';
$detalleEstadoCaja = '';
if ($cajaAbierta && !empty($turnoCajaActual['fecha_apertura'])) {
    $detalleEstadoCaja = 'Desde ' . date('d/m H:i', strtotime($turnoCajaActual['fecha_apertura']));
}

// ----- Paginación de "Últimos pedidos" -----
$pedidosPorPagina = 10;
$paginaActual2 = max(1, (int) ($_GET['pagina'] ?? 1));
$offsetPedidos = ($paginaActual2 - 1) * $pedidosPorPagina;

$totalPedidosTabla = $db->query("SELECT COUNT(*) c FROM pedidos")->fetch()['c'];
$totalPaginasPedidos = max(1, (int) ceil($totalPedidosTabla / $pedidosPorPagina));
$paginaActual2 = min($paginaActual2, $totalPaginasPedidos);
$offsetPedidos = ($paginaActual2 - 1) * $pedidosPorPagina;

$stmtPedidos = $db->prepare("SELECT * FROM pedidos ORDER BY creado_en DESC LIMIT :limite OFFSET :offset");
$stmtPedidos->bindValue(':limite', $pedidosPorPagina, PDO::PARAM_INT);
$stmtPedidos->bindValue(':offset', $offsetPedidos, PDO::PARAM_INT);
$stmtPedidos->execute();
$ultimosPedidos = $stmtPedidos->fetchAll();

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

// ==================================================================
// DATOS PARA LOS 4 GRÁFICOS (debajo de las tarjetas)
// ==================================================================

// ----- 1. Pedidos hoy: tendencia de los últimos 7 días -----
$tendenciaPedidosFilas = $db->query("
    SELECT DATE(creado_en) fecha, COUNT(*) c
    FROM pedidos
    WHERE creado_en >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(creado_en)
")->fetchAll();

$mapaTendencia = [];
foreach ($tendenciaPedidosFilas as $fila) {
    $mapaTendencia[$fila['fecha']] = (int) $fila['c'];
}
$etiquetasTendencia = [];
$valoresTendencia = [];
for ($i = 6; $i >= 0; $i--) {
    $fechaClave = date('Y-m-d', strtotime("-$i day"));
    $etiquetasTendencia[] = date('d/m', strtotime($fechaClave));
    $valoresTendencia[] = $mapaTendencia[$fechaClave] ?? 0;
}

// ----- 2. Ventas hoy: vs ayer -----
// (ya tenemos $ventasHoy y $ventasAyer calculados arriba)

// ----- 3. Pedidos pendientes: vs total y desglose por estado -----
$desglosoEstadosFilas = $db->query("SELECT estado, COUNT(*) c FROM pedidos GROUP BY estado")->fetchAll();
$mapaEstados = [
    'pendiente'       => 0,
    'pagado'          => 0,
    'en_preparacion'  => 0,
    'en_camino'       => 0,
    'entregado'       => 0,
    'cancelado'       => 0,
];
foreach ($desglosoEstadosFilas as $fila) {
    if (array_key_exists($fila['estado'], $mapaEstados)) {
        $mapaEstados[$fila['estado']] = (int) $fila['c'];
    }
}
$etiquetasEstados = [];
$valoresEstados = [];
foreach ($mapaEstados as $clave => $valor) {
    $etiquetasEstados[] = ucfirst(str_replace('_', ' ', $clave));
    $valoresEstados[] = $valor;
}

// ----- 4. Productos activos: vs inactivos y por categoría -----
// ----- 3.5 Comprobantes: Boleta vs Factura, año calendario actual (Ene-Dic) -----
$nombresMeses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$anioActual = (int) date('Y');

$clavesMeses = [];
$etiquetasMeses = [];
for ($m = 1; $m <= 12; $m++) {
    $clavesMeses[] = sprintf('%04d-%02d', $anioActual, $m);
    $etiquetasMeses[] = $nombresMeses[$m - 1];
}

$stmtComprobantesMes = $db->prepare("
    SELECT DATE_FORMAT(creado_en, '%Y-%m') AS mes, tipo_comprobante, COUNT(*) c
    FROM comprobantes_electronicos
    WHERE YEAR(creado_en) = :anio
    GROUP BY mes, tipo_comprobante
");
$stmtComprobantesMes->bindValue(':anio', $anioActual, PDO::PARAM_INT);
$stmtComprobantesMes->execute();
$filasComprobantesMes = $stmtComprobantesMes->fetchAll();

$mapaMesBoleta = array_fill_keys($clavesMeses, 0);
$mapaMesFactura = array_fill_keys($clavesMeses, 0);
foreach ($filasComprobantesMes as $fila) {
    if (!array_key_exists($fila['mes'], $mapaMesBoleta)) continue;
    if ($fila['tipo_comprobante'] === 'boleta') {
        $mapaMesBoleta[$fila['mes']] = (int) $fila['c'];
    } elseif ($fila['tipo_comprobante'] === 'factura') {
        $mapaMesFactura[$fila['mes']] = (int) $fila['c'];
    }
}

$valoresMesBoleta = array_values($mapaMesBoleta);
$valoresMesFactura = array_values($mapaMesFactura);



$productosInactivos = $db->query("SELECT COUNT(*) c FROM productos WHERE disponible = 0")->fetch()['c'];

// Si tu tabla de categorías o la columna de relación se llaman distinto,
// ajusta el JOIN de abajo (categorias.nombre / productos.categoria_id).
$etiquetasCategoria = [];
$valoresCategoria = [];
try {
    $productosPorCategoriaFilas = $db->query("
        SELECT c.nombre nombre, COUNT(p.id) c
        FROM categorias c
        LEFT JOIN productos p ON p.categoria_id = c.id AND p.disponible = 1
        GROUP BY c.id, c.nombre
        ORDER BY c.nombre
    ")->fetchAll();
    foreach ($productosPorCategoriaFilas as $fila) {
        $etiquetasCategoria[] = $fila['nombre'];
        $valoresCategoria[] = (int) $fila['c'];
    }
} catch (Exception $e) {
    // Si la tabla/columnas no existen con esos nombres, esta vista queda vacía
    // sin romper el resto del dashboard.
    $etiquetasCategoria = [];
    $valoresCategoria = [];
}

$datosGraficosDashboard = [
    'tendencia' => [
        'labels'  => $etiquetasTendencia,
        'valores' => $valoresTendencia,
    ],
    'ventas' => [
        'hoyVsAyer' => [
            'labels'  => ['Hoy', 'Ayer'],
            'valores' => [round((float) $ventasHoy, 2), round((float) $ventasAyer, 2)],
        ],
    ],
    'pendientes' => [
        'vsTotal' => [
            'labels'  => ['Pendientes', 'Otros'],
            'valores' => [(int) $pendientes, max((int) $totalPedidosGeneral - (int) $pendientes, 0)],
        ],
        'porEstado' => [
            'labels'  => $etiquetasEstados,
            'valores' => $valoresEstados,
        ],
    ],

'comprobantes' => [
        'porMes' => [
            'labels'  => $etiquetasMeses,
            'boleta'  => $valoresMesBoleta,
            'factura' => $valoresMesFactura,
        ],
    ],

    'productos' => [
        'vsInactivos' => [
            'labels'  => ['Activos', 'Inactivos'],
            'valores' => [(int) $totalProductos, (int) $productosInactivos],
        ],
        'porCategoria' => [
            'labels'  => $etiquetasCategoria,
            'valores' => $valoresCategoria,
        ],
    ],
];

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

<div class="dashboard-welcome card">
    <div class="dashboard-welcome-floaters" aria-hidden="true">
        <i class="ti ti-chef-hat f1"></i>
        <i class="ti ti-tools-kitchen-2 f2"></i>
        <i class="ti ti-pizza f3"></i>
        <i class="ti ti-cup f4"></i>
        <i class="ti ti-ice-cream f5"></i>
        <i class="ti ti-forklift f6"></i>
        <i class="ti ti-bowl f7"></i>
        <i class="ti ti-meat f8"></i>
        <i class="ti ti-fish f9"></i>
        <i class="ti ti-bread f10"></i>
        <i class="ti ti-salad f11"></i>
        <i class="ti ti-cookie f12"></i>
    </div>
    <div class="dashboard-welcome-icon"><i class="ti ti-sparkles"></i></div>
    <div class="dashboard-welcome-text">
        <h2>Bienvenido, <?= limpiar($nombreAdmin ?? 'Admin') ?></h2>
        <p>Hoy es un buen día para vender más. Revisa tus métricas, pedidos y productos destacados.</p>
        <div class="dashboard-caja-estado <?= $cajaAbierta ? 'is-open' : 'is-closed' ?>">
            <span class="dashboard-caja-dot"></span>
            <strong><?= limpiar($textoEstadoCaja) ?></strong>
            <?php if ($detalleEstadoCaja !== ''): ?>
                <small><?= limpiar($detalleEstadoCaja) ?></small>
            <?php endif; ?>
        </div>
    </div>
</div>

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

<script>
    window.datosGraficosDashboard = <?= json_encode($datosGraficosDashboard, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
</script>

<div class="grid-graficos">

    <div class="grafico-frame">
        <div class="grafico-box">
            <div class="grafico-header">
                <h4><i class="ti ti-chart-area"></i> Pedidos · últimos 7 días</h4>
            </div>
            <div class="grafico-canvas-wrap">
                <canvas id="graficoPedidosTendencia"></canvas>
            </div>
        </div>
    </div>

    <div class="grafico-frame">
        <div class="grafico-box">
            <div class="grafico-header">
                <h4><i class="ti ti-chart-donut"></i> Ventas hoy vs Ayer</h4>
            </div>
<div id="anilloVentas" class="anillo-progreso-wrap"></div>
            <div class="grafico-leyenda"></div>
        </div>
    </div>

<div class="grafico-frame">
        <div class="grafico-box">
            <div class="grafico-header">
                <h4><i class="ti ti-chart-donut"></i> Pedidos pendientes por estado</h4>
            </div>
            <div class="grafico-canvas-wrap">
                <div id="anilloPendientes" class="anillo-progreso-wrap"></div>
            </div>
            <div class="grafico-leyenda"></div>
        </div>
    </div>


    <div class="grafico-frame">
        <div class="grafico-box">
            <div class="grafico-header">
                <h4><i class="ti ti-chart-donut"></i> Productos activos</h4>
                <div class="grafico-tabs" data-grafico="productos">
                    <button type="button" class="tab-activa" data-vista="0">Activos vs Inactivos</button>
                    <button type="button" data-vista="1">Por categoría</button>
                </div>
            </div>
            <div class="grafico-canvas-wrap">
                <div id="anilloProductos" class="anillo-progreso-wrap"></div>
            </div>
            <div class="grafico-leyenda"></div>
        </div>
    </div>

<div class="grafico-frame-full">
    <div class="grafico-box">
        <div class="grafico-header">
            <h4><i class="ti ti-file-invoice"></i> Boletas vs Facturas · <?= date('Y') ?></h4>
        </div>
        <div class="grafico-canvas-wrap grafico-canvas-wrap-full">
            <div id="graficoComprobantesMensual" class="barras-comprobantes-wrap"></div>
        </div>
        <div class="grafico-leyenda"></div>
    </div>
</div>

</div>

<div class="top-rankings-grid">

    <!-- ── Top Productos más vendidos ── -->
    <div class="card top-ranking-card">
        <div class="top-ranking-head">
            <div class="top-ranking-icon"><i class="ti ti-trophy"></i></div>
            <div class="top-ranking-head-text">
                <h3>Top productos vendidos</h3>
                <p>Los 12 productos con más unidades despachadas</p>
            </div>
        </div>
        <div class="top-ranking-periodos" data-tipo="productos">
            <button type="button" class="top-periodo-btn" data-periodo="hoy">Hoy</button>
            <button type="button" class="top-periodo-btn" data-periodo="semana">Semana</button>
            <button type="button" class="top-periodo-btn top-periodo-btn-activo" data-periodo="mes">Mes</button>
            <button type="button" class="top-periodo-btn" data-periodo="anio">Año</button>
        </div>
        <div class="top-ranking-scroll" id="top-productos-lista">
            <div class="top-ranking-loading"><i class="ti ti-loader-2 top-spin"></i> Cargando…</div>
        </div>
    </div>

    <!-- ── Top Categorías más vendidas ── -->
    <div class="card top-ranking-card">
        <div class="top-ranking-head">
            <div class="top-ranking-icon top-ranking-icon-cat"><i class="ti ti-category"></i></div>
            <div class="top-ranking-head-text">
                <h3>Top categorías vendidas</h3>
                <p>Categorías con mayor volumen de ventas</p>
            </div>
        </div>
        <div class="top-ranking-periodos" data-tipo="categorias">
            <button type="button" class="top-periodo-btn" data-periodo="hoy">Hoy</button>
            <button type="button" class="top-periodo-btn" data-periodo="semana">Semana</button>
            <button type="button" class="top-periodo-btn top-periodo-btn-activo" data-periodo="mes">Mes</button>
            <button type="button" class="top-periodo-btn" data-periodo="anio">Año</button>
        </div>
        <div class="top-ranking-scroll" id="top-categorias-lista">
            <div class="top-ranking-loading"><i class="ti ti-loader-2 top-spin"></i> Cargando…</div>
        </div>
    </div>

</div>

<script>
(function () {
    const FALLBACK_PROD = '<i class="ti ti-tools-kitchen-2"></i>';
    const FALLBACK_CAT  = '<i class="ti ti-category"></i>';

    function rankClass(r) {
        return r === 1 ? 'gold' : r === 2 ? 'silver' : r === 3 ? 'bronze' : '';
    }

    function renderLista(contenedor, items, esCat) {
        if (!items || items.length === 0) {
            contenedor.innerHTML = '<div class="top-ranking-empty"><i class="ti ti-chart-bar-off"></i><span>Sin ventas en este período</span></div>';
            return;
        }
        const fallback = esCat ? FALLBACK_CAT : FALLBACK_PROD;
        const barClass = esCat ? 'top-ranking-bar top-ranking-bar-cat' : 'top-ranking-bar';
        const html = items.map(item => {
            // Imagen: mostramos ambos (img + fallback oculto) y con CSS/JS simple los alternamos
            const imgHtml = item.imagen
                ? `<img src="${item.imagen}" alt="" class="top-ranking-img" loading="lazy"
                     onload="this.nextElementSibling.style.display='none'"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                   <div class="top-ranking-img top-ranking-img-fallback" style="display:none">${fallback}</div>`
                : `<div class="top-ranking-img top-ranking-img-fallback">${fallback}</div>`;
            return '<div class="top-ranking-item">'
                + '<div class="top-rank-num ' + rankClass(item.rank) + '">' + item.rank + '</div>'
                + imgHtml
                + '<div class="top-ranking-info">'
                + '<span class="top-ranking-name">' + item.nombre + '</span>'
                + '<span class="top-ranking-qty"><i class="ti ti-package"></i> ' + item.total_vendido.toLocaleString('es-PE') + ' uds.</span>'
                + '<div class="top-ranking-bar-wrap"><div class="' + barClass + '" style="width:' + item.pct + '%"></div></div>'
                + '</div></div>';
        }).join('');
        contenedor.innerHTML = html;
    }

    function cargarTop(tipo, periodo) {
        const contenedor = document.getElementById('top-' + tipo + '-lista');
        contenedor.innerHTML = '<div class="top-ranking-loading"><i class="ti ti-loader-2 top-spin"></i> Cargando…</div>';
        fetch('ajax-top-rankings.php?tipo=' + tipo + '&periodo=' + periodo)
            .then(r => r.json())
            .then(data => renderLista(contenedor, data.items, tipo === 'categorias'))
            .catch(() => {
                contenedor.innerHTML = '<div class="top-ranking-empty"><i class="ti ti-wifi-off"></i><span>Error al cargar datos</span></div>';
            });
    }

    document.querySelectorAll('.top-ranking-periodos').forEach(function (barra) {
        const tipo = barra.dataset.tipo;
        barra.querySelectorAll('.top-periodo-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                barra.querySelectorAll('.top-periodo-btn').forEach(b => b.classList.remove('top-periodo-btn-activo'));
                btn.classList.add('top-periodo-btn-activo');
                cargarTop(tipo, btn.dataset.periodo);
            });
        });
        // Carga inicial con el período activo (mes)
        cargarTop(tipo, 'mes');
    });
})();
</script>

<div class="card">
    <h3>Últimos pedidos</h3>
    <div class="tabla-controles">
    <button type="button" class="btn-scroll-tabla btn-scroll-izq" aria-label="Desplazar tabla a la izquierda"><i class="ti ti-chevron-left"></i></button>
    <button type="button" class="btn-scroll-tabla btn-scroll-der" aria-label="Desplazar tabla a la derecha"><i class="ti ti-chevron-right"></i></button>
</div>
    <div class="tabla-scroll">
    <table id="tabla-ultimos-pedidos">
<thead><tr>
    <th><i class="ti ti-hash"></i>Código</th>
    <th><i class="ti ti-user"></i>Cliente</th>
    <th><i class="ti ti-truck-delivery"></i>Entrega</th>
    <th><i class="ti ti-credit-card"></i>Pago</th>
    <th><i class="ti ti-currency-dollar"></i>Total</th>
    <th><i class="ti ti-flag"></i>Estado</th>
    <th><i class="ti ti-calendar"></i>Fecha</th>
</tr></thead>        <tbody id="tbody-ultimos-pedidos">
        <?php foreach ($ultimosPedidos as $p): ?>
            <tr>
                <td><a href="pedidos.php?ver=<?= $p['id'] ?>"><?= limpiar($p['codigo']) ?></a></td>
                <td><?= limpiar($p['cliente_nombre']) ?></td>
<td><?php
    if ($p['tipo_entrega'] === 'delivery') {
        echo '<i class="ti ti-motorbike"></i> Delivery';
    } elseif ($p['tipo_entrega'] === 'comer_aqui') {
        echo '<i class="ti ti-tools-kitchen-2"></i> Comer aqui';
    } else {
        echo '<i class="ti ti-home"></i> Recojo';
    }
?></td>
<td><?= ['efectivo'=>'<i class="ti ti-cash"></i> Efectivo','yape_plin'=>'<i class="ti ti-device-mobile"></i> Yape (Culqi)','tarjeta'=>'<i class="ti ti-credit-card"></i> Tarjeta'][$p['metodo_pago']] ?></td>
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

    <?php if ($totalPaginasPedidos > 1): ?>
    <div class="paginacion-pedidos" id="paginacion-pedidos"
         data-pagina-actual="<?= $paginaActual2 ?>" data-total-paginas="<?= $totalPaginasPedidos ?>">
        <button type="button" class="btn-scroll-tabla" id="btn-pag-prev" <?= $paginaActual2 <= 1 ? 'disabled' : '' ?>
            aria-label="Ver pedidos más recientes">
            <i class="ti ti-chevron-left"></i>
        </button>
        <span class="paginacion-texto" id="txt-pagina-actual">Página <?= $paginaActual2 ?> de <?= $totalPaginasPedidos ?></span>
        <button type="button" class="btn-scroll-tabla" id="btn-pag-next" <?= $paginaActual2 >= $totalPaginasPedidos ? 'disabled' : '' ?>
            aria-label="Ver pedidos anteriores">
            <i class="ti ti-chevron-right"></i>
        </button>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>