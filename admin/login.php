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

if (estaLogueado()) {
    header('Location: ' . rutaInicioPorRol());
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    if (intentarLogin($usuario, $password)) {
        header('Location: ' . rutaInicioPorRol());
        exit;
    }
    $error = 'Usuario o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ingresar - Panel Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="assets/admin.css">
<link rel="stylesheet" href="assets/dashboard-graficos.css">
</head>
<body class="login-body">
    <div class="login-wrap">

        <!-- Lado izquierdo: ilustración animada -->
        <div class="login-visual">
            <div class="login-visual-overlay"></div>

            <div class="login-iconos-flotantes">
                <i class="ti ti-pizza icono-flotante fi-1"></i>
                <i class="ti ti-burger icono-flotante fi-2"></i>
                <i class="ti ti-coffee icono-flotante fi-3"></i>
                <i class="ti ti-tools-kitchen-2 icono-flotante fi-4"></i>
                <i class="ti ti-glass-full icono-flotante fi-5"></i>
                <i class="ti ti-ice-cream icono-flotante fi-6"></i>
            </div>

            <div class="login-visual-texto">
                <h1><i class="ti ti-chef-hat"></i> Panel Administrador</h1>
                <p>Gestiona tu carta digital, pedidos y productos desde un solo lugar.</p>
            </div>
        </div>

        <!-- Lado derecho: formulario -->
        <div class="login-form-side">
            <form class="login-box" method="POST">
                <h2><i class="ti ti-lock"></i> Ingresar</h2>
                <?php if ($error): ?><div class="alerta-error"><i class="ti ti-alert-circle"></i> <?= limpiar($error) ?></div><?php endif; ?>
                <div class="form-group">
                    <label>Usuario</label>
                    <input type="text" name="usuario" required autofocus>
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" required>
                </div>
                <button class="btn-principal btn-ripple" type="submit">Ingresar</button>
                <p class="ayuda-login">Usuario por defecto: <b>admin</b> / Clave: <b>admin123</b></p>
            </form>
        </div>

    </div>

    <script>
        // Efecto ripple en el botón de ingresar
        document.querySelectorAll('.btn-ripple').forEach(function (boton) {
            boton.addEventListener('click', function (e) {
                const circulo = document.createElement('span');
                const radio = Math.max(boton.clientWidth, boton.clientHeight);
                const rect = boton.getBoundingClientRect();
                circulo.style.width = circulo.style.height = radio + 'px';
                circulo.style.left = (e.clientX - rect.left - radio / 2) + 'px';
                circulo.style.top = (e.clientY - rect.top - radio / 2) + 'px';
                circulo.classList.add('ripple-circulo');
                boton.appendChild(circulo);
                setTimeout(function () { circulo.remove(); }, 600);
            });
        });
    </script>
</body>
</html>