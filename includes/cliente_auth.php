<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions.php';

function clienteEstaLogueado(): bool {
    return !empty($_SESSION['cliente_web_id']);
}

function clienteActualId(): ?int {
    return clienteEstaLogueado() ? (int)$_SESSION['cliente_web_id'] : null;
}

function obtenerClienteActual(): ?array {
    $clienteId = clienteActualId();
    if (!$clienteId) {
        return null;
    }
    return obtenerClienteWebPorId($clienteId);
}

function iniciarSesionCliente(array $cliente): void {
    session_regenerate_id(true);
    $_SESSION['cliente_web_id'] = (int)$cliente['id'];
    $_SESSION['cliente_web_nombre'] = (string)$cliente['nombre'];
    $_SESSION['cliente_web_email'] = (string)$cliente['email'];
    $_SESSION['cliente_web_proveedor'] = (string)($cliente['proveedor'] ?? 'local');
    actualizarUltimoLoginCliente((int)$cliente['id']);
}

function cerrarSesionCliente(): void {
    unset(
        $_SESSION['cliente_web_id'],
        $_SESSION['cliente_web_nombre'],
        $_SESSION['cliente_web_email'],
        $_SESSION['cliente_web_proveedor']
    );
}

function requerirClienteLogin(): void {
    if (!clienteEstaLogueado()) {
        header('Location: cliente-login.php');
        exit;
    }
}

function registrarClienteWeb(string $nombre, string $email, string $telefono, string $password): array {
    asegurarTablaClientesWeb();

    $nombre = trim($nombre);
    $email = normalizarEmailCliente($email);
    $telefono = trim($telefono);

    if ($nombre === '' || mb_strlen($nombre) < 2) {
        throw new RuntimeException('Ingresa un nombre valido.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Ingresa un correo valido.');
    }
    if (strlen($password) < 6) {
        throw new RuntimeException('La contrasena debe tener al menos 6 caracteres.');
    }

    $existente = obtenerClienteWebPorEmail($email);
    if ($existente) {
        throw new RuntimeException('Ese correo ya esta registrado.');
    }

    $stmt = getDB()->prepare(
        'INSERT INTO clientes_web (nombre, email, telefono, password_hash, proveedor, email_verificado, activo)
         VALUES (:nombre, :email, :telefono, :password_hash, :proveedor, :email_verificado, :activo)'
    );
    $stmt->execute([
        'nombre' => $nombre,
        'email' => $email,
        'telefono' => $telefono,
        'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        'proveedor' => 'local',
        'email_verificado' => 1,
        'activo' => 1,
    ]);

    $cliente = obtenerClienteWebPorId((int)getDB()->lastInsertId());
    if (!$cliente) {
        throw new RuntimeException('No se pudo crear la cuenta.');
    }

    vincularPedidosCliente((int)$cliente['id'], $cliente['email'], $cliente['telefono'] ?? '', 'cuenta');
    return $cliente;
}

function intentarLoginClienteLocal(string $email, string $password): ?array {
    asegurarTablaClientesWeb();

    $cliente = obtenerClienteWebPorEmail($email);
    if (!$cliente || (int)($cliente['activo'] ?? 1) !== 1) {
        return null;
    }

    $hash = (string)($cliente['password_hash'] ?? '');
    if ($hash === '' || !password_verify($password, $hash)) {
        return null;
    }

    vincularPedidosCliente((int)$cliente['id'], $cliente['email'], $cliente['telefono'] ?? '', $cliente['proveedor'] ?? 'cuenta');
    return $cliente;
}

function validarGoogleIdToken(string $idToken): array {
    $idToken = trim($idToken);
    if ($idToken === '') {
        throw new RuntimeException('Token de Google no recibido.');
    }

    $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode($idToken);
    $respuesta = @file_get_contents($url);
    if ($respuesta === false) {
        throw new RuntimeException('No se pudo validar la cuenta de Google.');
    }

    $payload = json_decode($respuesta, true);
    if (!is_array($payload) || !empty($payload['error_description'])) {
        throw new RuntimeException('Google devolvio un token invalido.');
    }

    $clientIdEsperado = trim((string)cfg('google_client_id', ''));
    if ($clientIdEsperado !== '' && (string)($payload['aud'] ?? '') !== $clientIdEsperado) {
        throw new RuntimeException('El token de Google no pertenece a esta aplicacion.');
    }

    if (empty($payload['sub']) || empty($payload['email'])) {
        throw new RuntimeException('La respuesta de Google no incluye los datos necesarios.');
    }

    return $payload;
}

function iniciarSesionClienteGoogle(string $idToken): array {
    asegurarTablaClientesWeb();
    $payload = validarGoogleIdToken($idToken);

    $googleId = trim((string)$payload['sub']);
    $email = normalizarEmailCliente((string)$payload['email']);
    $nombre = trim((string)($payload['name'] ?? $email));
    $avatar = trim((string)($payload['picture'] ?? ''));
    $verificado = (string)($payload['email_verified'] ?? '') === 'true' ? 1 : 0;

    $cliente = obtenerClienteWebPorGoogleId($googleId);
    if (!$cliente) {
        $cliente = obtenerClienteWebPorEmail($email);
    }

    $db = getDB();
    if ($cliente) {
        $stmt = $db->prepare(
            'UPDATE clientes_web
             SET nombre = :nombre, email = :email, google_id = :google_id, avatar_url = :avatar_url,
                 proveedor = :proveedor, email_verificado = :email_verificado, activo = 1
             WHERE id = :id'
        );
        $stmt->execute([
            'nombre' => $nombre,
            'email' => $email,
            'google_id' => $googleId,
            'avatar_url' => $avatar !== '' ? $avatar : null,
            'proveedor' => 'google',
            'email_verificado' => $verificado,
            'id' => (int)$cliente['id'],
        ]);
        $cliente = obtenerClienteWebPorId((int)$cliente['id']);
    } else {
        $stmt = $db->prepare(
            'INSERT INTO clientes_web (nombre, email, google_id, avatar_url, proveedor, email_verificado, activo)
             VALUES (:nombre, :email, :google_id, :avatar_url, :proveedor, :email_verificado, :activo)'
        );
        $stmt->execute([
            'nombre' => $nombre,
            'email' => $email,
            'google_id' => $googleId,
            'avatar_url' => $avatar !== '' ? $avatar : null,
            'proveedor' => 'google',
            'email_verificado' => $verificado,
            'activo' => 1,
        ]);
        $cliente = obtenerClienteWebPorId((int)$db->lastInsertId());
    }

    if (!$cliente) {
        throw new RuntimeException('No se pudo crear la sesion con Google.');
    }

    vincularPedidosCliente((int)$cliente['id'], $cliente['email'], $cliente['telefono'] ?? '', 'google');
    return $cliente;
}