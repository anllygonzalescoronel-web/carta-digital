<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (estaLogueado()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    if (intentarLogin($usuario, $password)) {
        header('Location: index.php');
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
<link rel="stylesheet" href="assets/admin.css">
</head>
<body class="login-body">
    <form class="login-box" method="POST">
        <h2>🍗 Panel Administrador</h2>
        <?php if ($error): ?><div class="alerta-error"><?= limpiar($error) ?></div><?php endif; ?>
        <div class="form-group">
            <label>Usuario</label>
            <input type="text" name="usuario" required autofocus>
        </div>
        <div class="form-group">
            <label>Contraseña</label>
            <input type="password" name="password" required>
        </div>
        <button class="btn-principal" type="submit">Ingresar</button>
        <p class="ayuda-login">Usuario por defecto: <b>admin</b> / Clave: <b>admin123</b></p>
    </form>
</body>
</html>
