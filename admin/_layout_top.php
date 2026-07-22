
<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requerirLogin();
$paginaActual = $paginaActual ?? '';

$hora = (int) date('H');
if ($hora < 12) {
    $saludo = 'Buenos días';
} elseif ($hora < 19) {
    $saludo = 'Buenas tardes';
} else {
    $saludo = 'Buenas noches';
}
?>
<?php
date_default_timezone_set('America/Lima');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requerirLogin();
$paginaActual = $paginaActual ?? '';

$hora = (int) date('H');
if ($hora < 12) {
    $saludo = 'Buenos días';
} elseif ($hora < 19) {
    $saludo = 'Buenas tardes';
} else {
    $saludo = 'Buenas noches';
}
?>
<!DOCTYPE html>
<html lang="es">
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
</style>
</head>
<body class="<?= (isset($_COOKIE['tema-admin']) && $_COOKIE['tema-admin'] === 'oscuro') ? '' : '' ?>">
<div class="admin-wrap">
    <aside class="admin-sidebar">
        <h2 class="admin-logo">🍗 Panel Admin</h2>
        <nav>
            <a href="index.php" class="<?= $paginaActual === 'dashboard' ? 'activo' : '' ?>">📊 Dashboard</a>
            <a href="pedidos.php" class="<?= $paginaActual === 'pedidos' ? 'activo' : '' ?>">🧾 Pedidos</a>
            <a href="categorias.php" class="<?= $paginaActual === 'categorias' ? 'activo' : '' ?>">🗂️ Categorías</a>
            <a href="productos.php" class="<?= $paginaActual === 'productos' ? 'activo' : '' ?>">🍽️ Productos</a>
            <a href="banners.php" class="<?= $paginaActual === 'banners' ? 'activo' : '' ?>">🖼️ Banners</a>
            <a href="configuracion.php" class="<?= $paginaActual === 'configuracion' ? 'activo' : '' ?>">⚙️ Configuración</a>
            <a href="../index.php" target="_blank">🌐 Ver carta pública</a>
            <a href="logout.php" class="salir">🚪 Cerrar sesión</a>
        </nav>
</aside>
    <div class="overlay-menu" id="overlay-menu"></div>
    <main class="admin-content">
        <header class="admin-topbar">
<div style="display:flex;align-items:center;">
    <button type="button" id="btn-menu-movil" class="btn-menu-movil" aria-label="Abrir menú">☰</button>
    <h1><?= limpiar($tituloPagina ?? '') ?></h1>
</div>            <div>
                <span><?= $saludo ?>, <?= limpiar($_SESSION['admin_nombre'] ?? '') ?></span>
                <button type="button" id="theme-toggle" class="theme-toggle" title="Cambiar tema" aria-label="Cambiar entre modo claro y oscuro">🌙</button>
            </div>
        </header>
        <div class="admin-body">