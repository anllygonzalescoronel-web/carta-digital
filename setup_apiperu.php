<?php
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

echo "\n📝 Agregando configuración de APIPERU...\n\n";

$configs = [
    ['clave' => 'apiperu_token', 'valor' => ''],
    ['clave' => 'apiperu_habilitado', 'valor' => '1'],
];

foreach ($configs as $config) {
    try {
        $db->prepare('INSERT IGNORE INTO configuracion (clave, valor) VALUES (:clave, :valor)')
            ->execute($config);
        echo "✅ Agregada configuración: {$config['clave']}\n";
    } catch (Exception $e) {
        echo "⚠️  Ya existe: {$config['clave']}\n";
    }
}

echo "\n✅ Configuración APIPERU agregada\n";
echo "\n📍 Siguiente paso:\n";
echo "   1. Obtén un token gratuito en: https://apiperu.dev/\n";
echo "   2. Ingresa el token en: admin/configuracion.php → APIPERU Token\n";
echo "   3. El checkout mostrará validación automática de DNI/RUC\n\n";
