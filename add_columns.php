<?php
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

echo "\n📝 Agregando columnas faltantes a tabla pedidos...\n\n";

try {
    $col = $db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='categorias' AND COLUMN_NAME='imagen'")->fetchColumn();
    if (!$col) {
        $db->exec("ALTER TABLE categorias ADD COLUMN imagen VARCHAR(255) DEFAULT NULL AFTER nombre");
        echo "✅ Columna imagen agregada a categorias\n";
    } else {
        echo "⚠️  Ya existe: imagen en categorias\n";
    }
} catch (Exception $e) {
    echo "⚠️ No se pudo verificar imagen en categorias: " . $e->getMessage() . "\n";
}

$columnas = [
    'cliente_dni' => 'VARCHAR(20) DEFAULT NULL COMMENT "DNI/RUC del cliente"',
    'facturacion_driver' => 'VARCHAR(20) DEFAULT "native" COMMENT "native|nubefact"',
    'facturacion_estado' => 'VARCHAR(30) DEFAULT "pendiente" COMMENT "estado facturación"',
    'facturacion_error' => 'TEXT DEFAULT NULL',
    'facturacion_fecha' => 'DATETIME DEFAULT NULL',
    'facturacion_intento' => 'INT DEFAULT 0',
    'cliente_email' => 'VARCHAR(255) DEFAULT NULL',
];

// Primero verificar qué columnas ya existen
$stmt = $db->prepare(
    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'pedidos'"
);
$stmt->execute([DB_NAME]);
$columnasExistentes = [];
foreach ($stmt->fetchAll() as $row) {
    $columnasExistentes[$row['COLUMN_NAME']] = true;
}

foreach ($columnas as $columna => $def) {
    if (isset($columnasExistentes[$columna])) {
        echo "⚠️  Ya existe: $columna\n";
        continue;
    }
    
    try {
        $sql = "ALTER TABLE pedidos ADD COLUMN $columna $def";
        $db->exec($sql);
        echo "✅ Agregada columna: $columna\n";
    } catch (Exception $e) {
        echo "❌ Error en $columna: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Proceso completado\n";

// ---------- Tablas de toppings / variaciones ----------
echo "\n📝 Verificando tablas de opciones de productos...\n\n";

try {
    $db->exec('CREATE TABLE IF NOT EXISTS producto_grupos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        producto_id INT NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        tipo ENUM(\'radio\',\'checkbox\') DEFAULT \'radio\',
        requerido TINYINT(1) DEFAULT 0,
        min_opciones INT DEFAULT 0,
        max_opciones INT DEFAULT 1,
        orden INT DEFAULT 0,
        FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB');
    echo "✅ Tabla producto_grupos verificada\n";
} catch (Exception $e) {
    echo "❌ Error en producto_grupos: " . $e->getMessage() . "\n";
}

try {
    $db->exec('CREATE TABLE IF NOT EXISTS producto_opciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        grupo_id INT NOT NULL,
        nombre VARCHAR(150) NOT NULL,
        precio_extra DECIMAL(10,2) DEFAULT 0.00,
        disponible TINYINT(1) DEFAULT 1,
        orden INT DEFAULT 0,
        FOREIGN KEY (grupo_id) REFERENCES producto_grupos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB');
    echo "✅ Tabla producto_opciones verificada\n";
} catch (Exception $e) {
    echo "❌ Error en producto_opciones: " . $e->getMessage() . "\n";
}

try {
    $col = $db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pedido_detalle' AND COLUMN_NAME='opciones_json'")->fetchColumn();
    if (!$col) {
        $db->exec("ALTER TABLE pedido_detalle ADD COLUMN opciones_json TEXT DEFAULT NULL");
        echo "✅ Columna opciones_json agregada a pedido_detalle\n";
    } else {
        echo "⚠️  Ya existe: opciones_json en pedido_detalle\n";
    }
} catch (Exception $e) {
    echo "❌ Error en opciones_json: " . $e->getMessage() . "\n";
}

echo "\n✅ Migración de opciones completada\n";
