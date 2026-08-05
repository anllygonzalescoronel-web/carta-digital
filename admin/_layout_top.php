<?php
date_default_timezone_set('America/Lima');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requerirLogin();
$paginaActual = $paginaActual ?? '';
$rolActual = obtenerRolActual();

if ($rolActual === 'cocinero' && $paginaActual !== 'cocina') {
    header('Location: cocina.php');
    exit;
}

if ($rolActual === 'mesero') {
    $paginasPermitidasMesero = ['dashboard', 'pos', 'cajas'];
    if (!in_array($paginaActual, $paginasPermitidasMesero, true)) {
        header('Location: index.php');
        exit;
    }
}

$hora = (int) date('H');
if ($hora < 12) {
    $saludo = 'Buenos días';
} elseif ($hora < 19) {
    $saludo = 'Buenas tardes';
} else {
    $saludo = 'Buenas noches';
}
$nombreAdmin = trim((string)($_SESSION['admin_nombre'] ?? 'Admin'));
$inicialAdmin = strtoupper(substr($nombreAdmin, 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.0.1/dist/chartjs-plugin-zoom.min.js"></script>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Panel Admin - <?= limpiar($tituloPagina ?? 'Dashboard') ?></title>
<link rel="stylesheet" href="assets/admin.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="assets/dashboard-graficos.css">
<script>
    if (localStorage.getItem('tema-admin') === 'oscuro') {
        document.documentElement.classList.add('precarga-oscura');
    }
</script>
<style>
    .precarga-oscura { background: #262a35; }

    .admin-sidebar {
        background: #ffffff !important;
        border-right: 1px solid #e2e8f0;
        box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.02), 8px 0 24px rgba(15, 23, 42, 0.04);
        padding: 16px 12px;
    }

    .admin-sidebar-brand {
        margin-bottom: 16px;
        padding: 10px;
        border-radius: 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .admin-logo {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #0f172a;
        font-size: 17px;
        font-weight: 800;
        margin: 0;
    }

    .admin-logo i {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        background: #0f172a;
        color: #fff;
        box-shadow: none;
    }

    .admin-brand-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 8px;
        font-size: 11px;
        font-weight: 700;
        color: #475569;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        padding: 4px 8px;
    }

    .admin-sidebar nav {
        display: grid;
        gap: 8px;
    }

    .admin-sidebar-section {
        display: grid;
        gap: 4px;
        padding: 6px 0;
    }

    .admin-sidebar-section-title {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #94a3b8;
        padding: 0 8px 4px;
    }

    .admin-sidebar nav a {
        color: #475569;
        font-weight: 700;
        border-radius: 12px;
        padding: 10px 12px;
        border-left: 3px solid transparent;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
    }

    .admin-sidebar nav a:hover,
    .admin-sidebar nav a.activo {
        background: #f8fafc;
        color: #0f172a;
        border-left-color: #0f172a;
        box-shadow: none;
    }

    .admin-sidebar nav a.salir {
        margin-top: 6px;
        background: #fff5f5;
        color: #b91c1c;
    }

    .admin-topbar {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
        padding: 16px 18px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
    }

    .admin-topbar-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .admin-topbar-left .topbar-kicker {
        display: block;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 2px;
    }

    .admin-topbar h1 {
        margin: 0;
        font-size: 20px;
        color: #0f172a;
        font-weight: 800;
    }

    .admin-topbar-right {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .admin-user-pill {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 10px;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        box-shadow: none;
    }

    .admin-user-pill .avatar-pill {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: #0f172a;
        color: #fff;
        font-weight: 800;
        font-size: 15px;
        flex-shrink: 0;
    }

    .admin-user-pill strong {
        display: block;
        font-size: 13px;
        color: #0f172a;
    }

    .admin-user-pill span {
        display: block;
        font-size: 11px;
        color: #64748b;
    }

    .theme-toggle {
        width: 42px;
        height: 42px;
        border-radius: 999px;
        background: #f8fafc;
        color: #0f172a;
        border: 1px solid #e2e8f0;
        box-shadow: none;
    }

    body.modo-oscuro .admin-sidebar {
        background: #1f2430 !important;
        border-color: rgba(255, 255, 255, 0.08);
        box-shadow: 0 0 0 1px rgba(255,255,255,0.03), 8px 0 24px rgba(0,0,0,0.18);
    }

    body.modo-oscuro .admin-logo {
        color: #f8fafc;
    }

    body.modo-oscuro .admin-brand-chip {
        background: rgba(15, 23, 42, 0.55);
        color: #bfdbfe;
        border-color: rgba(191, 219, 254, 0.12);
    }

    body.modo-oscuro .admin-sidebar nav a {
        color: #cbd5e1;
    }

    body.modo-oscuro .admin-topbar {
        background: #1f2430;
        border-color: rgba(255,255,255,0.07);
        box-shadow: 0 4px 16px rgba(0,0,0,0.2);
    }

    body.modo-oscuro .admin-topbar h1 {
        color: #f8fafc;
    }

    body.modo-oscuro .admin-user-pill {
        background: rgba(15, 23, 42, 0.6);
        border-color: rgba(255,255,255,0.08);
    }

    body.modo-oscuro .admin-user-pill strong {
        color: #f8fafc;
    }

    body.modo-oscuro .admin-user-pill span {
        color: #94a3b8;
    }

    .admin-quick-links {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-left: 10px;
    }

    .admin-quick-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 10px;
        border-radius: 999px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
        text-decoration: none;
        font-size: 12px;
        font-weight: 700;
    }

    .admin-quick-link:hover,
    .admin-quick-link.activo {
        background: #0f172a;
        color: #fff;
        border-color: #0f172a;
    }

    body.modo-oscuro .admin-quick-link {
        background: rgba(255,255,255,0.05);
        border-color: rgba(255,255,255,0.08);
        color: #cbd5e1;
    }

    body.modo-oscuro .admin-quick-link:hover,
    body.modo-oscuro .admin-quick-link.activo {
        background: #fff;
        color: #0f172a;
        border-color: #fff;
    }

    @media (max-width: 780px) {
        .admin-topbar {
            flex-direction: column;
            align-items: flex-start;
        }
        .admin-topbar-right {
            width: 100%;
            justify-content: space-between;
        }
        .admin-quick-links {
            display: none;
        }
    }
</style>
</head>
<body class="<?= (isset($_COOKIE['tema-admin']) && $_COOKIE['tema-admin'] === 'oscuro') ? '' : '' ?>">
<div class="admin-wrap">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-brand">
            <h2 class="admin-logo"><i class="ti ti-chef-hat"></i> Panel Admin</h2>
            <div class="admin-brand-chip"><i class="ti ti-circle-filled"></i> Online</div>
        </div>
        <nav>
            <div class="admin-sidebar-section">
                <div class="admin-sidebar-section-title">Inicio</div>
                <?php if ($rolActual === 'admin' || $rolActual === 'mesero'): ?>
                <a href="index.php" class="<?= $paginaActual === 'dashboard' ? 'activo' : '' ?>"><i class="ti ti-layout-dashboard"></i> Dashboard</a>
                <a href="pos.php" class="<?= $paginaActual === 'pos' ? 'activo' : '' ?>"><i class="ti ti-device-desktop"></i> POS Ventas</a>
                <a href="cajas.php" class="<?= $paginaActual === 'cajas' ? 'activo' : '' ?>"><i class="ti ti-cash-banknote"></i> Cajas</a>
                <?php endif; ?>
                <?php if ($rolActual === 'admin'): ?>
                <a href="pedidos.php" class="<?= $paginaActual === 'pedidos' ? 'activo' : '' ?>"><i class="ti ti-receipt"></i> Pedidos</a>
                <?php endif; ?>
                <?php if ($rolActual !== 'mesero'): ?>
                <a href="cocina.php" class="<?= $paginaActual === 'cocina' ? 'activo' : '' ?>"><i class="ti ti-chef-hat"></i> Cocina en Vivo</a>
                <a href="estaciones.php" class="<?= $paginaActual === 'estaciones' ? 'activo' : '' ?>"><i class="ti ti-layout-board"></i> Estaciones</a>
                <?php endif; ?>
            </div>

            <?php if ($rolActual === 'admin'): ?>
            <div class="admin-sidebar-section">
                <div class="admin-sidebar-section-title">Gestión</div>
                <a href="usuarios.php" class="<?= $paginaActual === 'usuarios' ? 'activo' : '' ?>"><i class="ti ti-users"></i> Usuarios</a>
                <a href="clientes.php" class="<?= $paginaActual === 'clientes' ? 'activo' : '' ?>"><i class="ti ti-user-circle"></i> Clientes Web</a>
                <a href="categorias.php" class="<?= $paginaActual === 'categorias' ? 'activo' : '' ?>"><i class="ti ti-category"></i> Categorías</a>
                <a href="productos.php" class="<?= $paginaActual === 'productos' ? 'activo' : '' ?>"><i class="ti ti-tools-kitchen-2"></i> Productos</a>
                <a href="ingredientes.php" class="<?= $paginaActual === 'ingredientes' ? 'activo' : '' ?>"><i class="ti ti-packages"></i> Ingredientes / Stock</a>
                <a href="opciones_producto.php" class="<?= $paginaActual === 'opciones' ? 'activo' : '' ?>"><i class="ti ti-adjustments-horizontal"></i> Toppings / Extras</a>
                <a href="mesas.php" class="<?= $paginaActual === 'mesas' ? 'activo' : '' ?>"><i class="ti ti-armchair"></i> Mesas y Zonas</a>
            </div>

            <div class="admin-sidebar-section">
                <div class="admin-sidebar-section-title">Contenido</div>
                <a href="banners.php" class="<?= $paginaActual === 'banners' ? 'activo' : '' ?>"><i class="ti ti-photo"></i> Banners</a>
                <a href="ofertas_web.php" class="<?= $paginaActual === 'ofertas_web' ? 'activo' : '' ?>"><i class="ti ti-tag"></i> Ofertas Web</a>
                <a href="popups.php" class="<?= $paginaActual === 'popups' ? 'activo' : '' ?>"><i class="ti ti-message-2"></i> Popups</a>
                <a href="configuracion.php" class="<?= $paginaActual === 'configuracion' ? 'activo' : '' ?>"><i class="ti ti-settings"></i> Configuración</a>
                <a href="comprobantes.php" class="<?= $paginaActual === 'comprobantes' ? 'activo' : '' ?>"><i class="ti ti-file-invoice"></i> Comprobantes</a>
            </div>
            <?php endif; ?>

            <div class="admin-sidebar-section">
                <div class="admin-sidebar-section-title">Varios</div>
                <a href="../index.php" target="_blank"><i class="ti ti-world"></i> Ver carta pública</a>
                <a href="https://wa.me/51956761889?text=Hola%2C%20quiero%20reportar%20un%20bug%20en%20Carta%20Digital" target="_blank" rel="noopener" class="reporte-bug"><i class="ti ti-bug"></i> Reportar bug</a>
            </div>
        </nav>
</aside>
    <div class="overlay-menu" id="overlay-menu"></div>
    <main class="admin-content">
        <header class="admin-topbar">
            <div class="admin-topbar-left">
                <button type="button" id="btn-menu-movil" class="btn-menu-movil" aria-label="Abrir menú"><i class="ti ti-menu-2"></i></button>
                <div>
                    <span class="topbar-kicker">Panel de gestión</span>
                    <h1><?= limpiar($tituloPagina ?? '') ?></h1>
                </div>
                <div class="admin-quick-links">
                    <?php if ($rolActual !== 'mesero'): ?>
                    <a href="cocina.php" class="admin-quick-link<?= $paginaActual === 'cocina' ? ' activo' : '' ?>"><i class="ti ti-chef-hat"></i> Cocina</a>
                    <?php endif; ?>
                    <?php if ($rolActual === 'mesero'): ?>
                    <a href="index.php" class="admin-quick-link<?= $paginaActual === 'dashboard' ? ' activo' : '' ?>"><i class="ti ti-layout-dashboard"></i> Dashboard</a>
                    <a href="pos.php" class="admin-quick-link<?= $paginaActual === 'pos' ? ' activo' : '' ?>"><i class="ti ti-device-desktop"></i> POS</a>
                    <a href="cajas.php" class="admin-quick-link<?= $paginaActual === 'cajas' ? ' activo' : '' ?>"><i class="ti ti-cash-banknote"></i> Cajas</a>
                    <?php endif; ?>
                    <?php if ($rolActual === 'admin'): ?>
                    <a href="pedidos.php" class="admin-quick-link<?= $paginaActual === 'pedidos' ? ' activo' : '' ?>"><i class="ti ti-receipt"></i> Pedidos</a>
                    <a href="pos.php" class="admin-quick-link<?= $paginaActual === 'pos' ? ' activo' : '' ?>"><i class="ti ti-device-desktop"></i> POS</a>
                    <a href="cajas.php" class="admin-quick-link<?= $paginaActual === 'cajas' ? ' activo' : '' ?>"><i class="ti ti-cash-banknote"></i> Cajas</a>
                    <a href="productos.php" class="admin-quick-link<?= $paginaActual === 'productos' ? ' activo' : '' ?>"><i class="ti ti-tools-kitchen-2"></i> Productos</a>
                    <a href="mesas.php" class="admin-quick-link<?= $paginaActual === 'mesas' ? ' activo' : '' ?>"><i class="ti ti-armchair"></i> Mesas</a>
                    <a href="usuarios.php" class="admin-quick-link<?= $paginaActual === 'usuarios' ? ' activo' : '' ?>"><i class="ti ti-users"></i> Usuarios</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="admin-topbar-right">
                <div class="admin-user-pill">
                    <div class="avatar-pill"><?= limpiar($inicialAdmin) ?></div>
                    <div>
                        <strong><?= limpiar($nombreAdmin) ?></strong>
                        <span><?= $saludo ?> · <?= limpiar(ucfirst($rolActual)) ?></span>
                    </div>
                </div>
                <a href="logout.php" class="btn-logout-header" title="Cerrar sesión"><i class="ti ti-logout"></i> Salir</a>
                <button type="button" id="theme-toggle" class="theme-toggle" title="Cambiar tema" aria-label="Cambiar entre modo claro y oscuro"><i class="ti ti-moon"></i></button>
            </div>
        </header>
        <div class="admin-body">