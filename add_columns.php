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
    'mesa_id' => 'INT DEFAULT NULL',
    'mesa_nombre' => 'VARCHAR(80) DEFAULT NULL',
    'zona_nombre' => 'VARCHAR(80) DEFAULT NULL',
    'caja_turno_id' => 'INT DEFAULT NULL',
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

try {
    $colTipoEntrega = $db->query("SHOW COLUMNS FROM pedidos LIKE 'tipo_entrega'")->fetch();
    $tipoActual = strtolower((string)($colTipoEntrega['Type'] ?? ''));
    if (strpos($tipoActual, 'comer_aqui') === false) {
        $db->exec("ALTER TABLE pedidos MODIFY tipo_entrega ENUM('recojo','delivery','comer_aqui') NOT NULL");
        echo "✅ ENUM tipo_entrega actualizado con comer_aqui\n";
    } else {
        echo "⚠️  ENUM tipo_entrega ya incluye comer_aqui\n";
    }
} catch (Exception $e) {
    echo "❌ Error actualizando tipo_entrega: " . $e->getMessage() . "\n";
}

try {
    $stmtCfg = $db->prepare("SELECT COUNT(*) FROM configuracion WHERE clave = 'comer_aqui_activo'");
    $stmtCfg->execute();
    if ((int)$stmtCfg->fetchColumn() === 0) {
        $db->prepare("INSERT INTO configuracion (clave, valor) VALUES ('comer_aqui_activo', '1')")->execute();
        echo "✅ Config comer_aqui_activo creada\n";
    } else {
        echo "⚠️  Ya existe config comer_aqui_activo\n";
    }
} catch (Exception $e) {
    echo "❌ Error en config comer_aqui_activo: " . $e->getMessage() . "\n";
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

// ---------- Tablas de zonas y mesas ----------
echo "\n📝 Verificando módulo de mesas y zonas...\n\n";

try {
    $db->exec('CREATE TABLE IF NOT EXISTS zonas_mesas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(80) NOT NULL,
        ancho INT NOT NULL DEFAULT 1200,
        alto INT NOT NULL DEFAULT 700,
        orden INT NOT NULL DEFAULT 0,
        activa TINYINT(1) NOT NULL DEFAULT 1,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB');
    echo "✅ Tabla zonas_mesas verificada\n";
} catch (Exception $e) {
    echo "❌ Error en zonas_mesas: " . $e->getMessage() . "\n";
}

try {
    $db->exec('CREATE TABLE IF NOT EXISTS mesas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        zona_id INT NOT NULL,
        nombre VARCHAR(80) NOT NULL,
        capacidad INT NOT NULL DEFAULT 4,
        sillas INT NOT NULL DEFAULT 4,
        pos_x INT NOT NULL DEFAULT 80,
        pos_y INT NOT NULL DEFAULT 80,
        forma ENUM(\'rectangular\',\'redonda\') NOT NULL DEFAULT \'rectangular\',
        activa TINYINT(1) NOT NULL DEFAULT 1,
        orden INT NOT NULL DEFAULT 0,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_mesas_zona (zona_id),
        CONSTRAINT fk_mesas_zona FOREIGN KEY (zona_id) REFERENCES zonas_mesas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB');
    echo "✅ Tabla mesas verificada\n";
} catch (Exception $e) {
    echo "❌ Error en mesas: " . $e->getMessage() . "\n";
}

try {
    $countZonas = (int)$db->query('SELECT COUNT(*) FROM zonas_mesas')->fetchColumn();
    if ($countZonas === 0) {
        $db->exec("INSERT INTO zonas_mesas (nombre, ancho, alto, orden, activa) VALUES
            ('Salon principal', 1200, 720, 1, 1),
            ('Terraza', 1200, 720, 2, 1)");
        echo "✅ Zonas iniciales creadas\n";
    } else {
        echo "⚠️  Ya existen zonas\n";
    }
} catch (Exception $e) {
    echo "❌ Error creando zonas iniciales: " . $e->getMessage() . "\n";
}

try {
    $countMesas = (int)$db->query('SELECT COUNT(*) FROM mesas')->fetchColumn();
    if ($countMesas === 0) {
        $zonas = $db->query('SELECT id, nombre FROM zonas_mesas')->fetchAll();
        $map = [];
        foreach ($zonas as $z) {
            $map[$z['nombre']] = (int)$z['id'];
        }

        $stmtMesa = $db->prepare('INSERT INTO mesas (zona_id, nombre, capacidad, sillas, pos_x, pos_y, forma, activa, orden)
            VALUES (:zona_id, :nombre, :capacidad, :sillas, :pos_x, :pos_y, :forma, 1, :orden)');

        $semilla = [
            ['zona' => 'Salon principal', 'nombre' => 'Mesa 1', 'capacidad' => 4, 'sillas' => 4, 'x' => 120, 'y' => 120, 'forma' => 'rectangular', 'orden' => 1],
            ['zona' => 'Salon principal', 'nombre' => 'Mesa 2', 'capacidad' => 4, 'sillas' => 4, 'x' => 340, 'y' => 120, 'forma' => 'rectangular', 'orden' => 2],
            ['zona' => 'Salon principal', 'nombre' => 'Mesa 3', 'capacidad' => 6, 'sillas' => 6, 'x' => 200, 'y' => 320, 'forma' => 'redonda', 'orden' => 3],
            ['zona' => 'Terraza', 'nombre' => 'Mesa T1', 'capacidad' => 4, 'sillas' => 4, 'x' => 180, 'y' => 180, 'forma' => 'rectangular', 'orden' => 1],
            ['zona' => 'Terraza', 'nombre' => 'Mesa T2', 'capacidad' => 2, 'sillas' => 2, 'x' => 420, 'y' => 240, 'forma' => 'redonda', 'orden' => 2],
        ];

        foreach ($semilla as $m) {
            if (!isset($map[$m['zona']])) {
                continue;
            }
            $stmtMesa->execute([
                'zona_id' => $map[$m['zona']],
                'nombre' => $m['nombre'],
                'capacidad' => $m['capacidad'],
                'sillas' => $m['sillas'],
                'pos_x' => $m['x'],
                'pos_y' => $m['y'],
                'forma' => $m['forma'],
                'orden' => $m['orden'],
            ]);
        }

        echo "✅ Mesas iniciales creadas\n";
    } else {
        echo "⚠️  Ya existen mesas\n";
    }
} catch (Exception $e) {
    echo "❌ Error creando mesas iniciales: " . $e->getMessage() . "\n";
}

echo "\n✅ Migración de mesas completada\n";

// ---------- Tablas de caja ----------
echo "\n📝 Verificando módulo de caja...\n\n";

try {
    $db->exec('CREATE TABLE IF NOT EXISTS cajas_turnos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        usuario_nombre VARCHAR(120) NOT NULL,
        estado ENUM(\'abierta\',\'cerrada\') NOT NULL DEFAULT \'abierta\',
        monto_apertura DECIMAL(10,2) NOT NULL DEFAULT 0,
        monto_cierre DECIMAL(10,2) DEFAULT NULL,
        observacion_apertura VARCHAR(255) DEFAULT NULL,
        observacion_cierre VARCHAR(255) DEFAULT NULL,
        abierta_en DATETIME NOT NULL,
        cerrada_en DATETIME DEFAULT NULL,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_caja_turnos_estado (estado),
        KEY idx_caja_turnos_usuario (usuario_id)
    ) ENGINE=InnoDB');
    echo "✅ Tabla cajas_turnos verificada\n";
} catch (Exception $e) {
    echo "❌ Error en cajas_turnos: " . $e->getMessage() . "\n";
}

try {
    $db->exec('CREATE TABLE IF NOT EXISTS caja_movimientos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        turno_id INT NOT NULL,
        tipo ENUM(\'ingreso\',\'egreso\',\'venta\') NOT NULL,
        concepto VARCHAR(255) NOT NULL,
        monto DECIMAL(10,2) NOT NULL,
        referencia_tipo VARCHAR(40) DEFAULT NULL,
        referencia_id INT DEFAULT NULL,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_caja_mov_turno (turno_id),
        CONSTRAINT fk_caja_mov_turno FOREIGN KEY (turno_id) REFERENCES cajas_turnos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB');
    echo "✅ Tabla caja_movimientos verificada\n";
} catch (Exception $e) {
    echo "❌ Error en caja_movimientos: " . $e->getMessage() . "\n";
}

echo "\n✅ Migración de caja completada\n";
