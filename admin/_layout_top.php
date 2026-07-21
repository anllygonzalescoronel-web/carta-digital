<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requerirLogin();
$paginaActual = $paginaActual ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Panel Admin - <?= limpiar($tituloPagina ?? 'Dashboard') ?></title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
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
    <main class="admin-content">
        <header class="admin-topbar">
            <h1><?= limpiar($tituloPagina ?? '') ?></h1>
            <span>Hola, <?= limpiar($_SESSION['admin_nombre'] ?? '') ?></span>
        </header>
        <div class="admin-body">
