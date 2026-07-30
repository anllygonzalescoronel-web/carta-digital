<?php
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

$defaults = [
    'smtp_enabled' => '0',
    'smtp_host' => '',
    'smtp_port' => '587',
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_secure' => 'tls',
    'smtp_from_email' => '',
    'smtp_from_name' => 'Carta Digital',
    'smtp_timeout' => '15',
];

foreach ($defaults as $clave => $valor) {
    $stmt = $db->prepare(
        'INSERT INTO configuracion (clave, valor) VALUES (:clave, :valor)
         ON DUPLICATE KEY UPDATE valor = IF(valor IS NULL, VALUES(valor), valor)'
    );
    $stmt->execute([
        'clave' => $clave,
        'valor' => $valor,
    ]);
}

echo "Configuración SMTP lista.\n";
