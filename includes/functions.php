<?php
require_once __DIR__ . '/db.php';

/**
 * Devuelve toda la configuración como arreglo clave => valor
 */
function getConfig(): array {
    static $config = null;
    if ($config === null) {
        $stmt = getDB()->query('SELECT clave, valor FROM configuracion');
        $config = [];
        foreach ($stmt->fetchAll() as $row) {
            $config[$row['clave']] = $row['valor'];
        }
    }
    return $config;
}

function cfg(string $clave, $default = '') {
    $config = getConfig();
    return $config[$clave] ?? $default;
}

function guardarConfig(string $clave, string $valor): void {
    $stmt = getDB()->prepare(
        'INSERT INTO configuracion (clave, valor) VALUES (:clave, :valor)
         ON DUPLICATE KEY UPDATE valor = :valor2'
    );
    $stmt->execute(['clave' => $clave, 'valor' => $valor, 'valor2' => $valor]);
}

function formatoPrecio($numero): string {
    return 'S/ ' . number_format((float)$numero, 2);
}

function generarCodigoPedido(): string {
    return 'PED-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -5));
}

/**
 * Sube una imagen a la carpeta indicada y devuelve el nombre de archivo generado.
 * Devuelve null si no se subió nada.
 */
function subirImagen(string $inputName, string $carpetaDestino): ?string {
    if (empty($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $archivo = $_FILES[$inputName];
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Error al subir el archivo (código ' . $archivo['error'] . ')');
    }
    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $extensionesPermitidas, true)) {
        throw new RuntimeException('Formato de imagen no permitido. Usa jpg, png o webp.');
    }
    if ($archivo['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('La imagen supera los 5MB permitidos.');
    }
    $nombreNuevo = uniqid('img_', true) . '.' . $ext;
    $rutaDestino = rtrim($carpetaDestino, '/') . '/' . $nombreNuevo;
    if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        throw new RuntimeException('No se pudo guardar la imagen en el servidor.');
    }
    return $nombreNuevo;
}

/**
 * Sube un archivo con extensiones permitidas y devuelve el nombre generado.
 */
function subirArchivoSeguro(string $inputName, string $carpetaDestino, array $extensionesPermitidas, int $tamanoMaximoBytes = 5242880): ?string {
    if (empty($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $archivo = $_FILES[$inputName];
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Error al subir el archivo (código ' . $archivo['error'] . ')');
    }

    $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $permitidas = array_map('strtolower', $extensionesPermitidas);
    if (!in_array($ext, $permitidas, true)) {
        throw new RuntimeException('Formato no permitido. Permitidos: ' . implode(', ', $permitidas));
    }

    if ($archivo['size'] > $tamanoMaximoBytes) {
        throw new RuntimeException('El archivo supera el tamaño máximo permitido.');
    }

    $nombreNuevo = uniqid('file_', true) . '.' . $ext;
    $rutaDestino = rtrim($carpetaDestino, '/') . '/' . $nombreNuevo;

    if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        throw new RuntimeException('No se pudo guardar el archivo en el servidor.');
    }

    return $nombreNuevo;
}

function jsonResponse($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function limpiar(string $texto): string {
    return htmlspecialchars(trim($texto), ENT_QUOTES, 'UTF-8');
}
