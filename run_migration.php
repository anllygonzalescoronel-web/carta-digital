<?php
/**
 * Script para ejecutar la migración SQL de facturación híbrida
 * Uso: php run_migration.php
 */

require_once __DIR__ . '/includes/functions.php';

echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║  EJECUTANDO MIGRACIÓN: FACTURACIÓN ELECTRÓNICA HÍBRIDA    ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

$db = getDB();
$migracionSql = file_get_contents(__DIR__ . '/sql/hybrid_migration.sql');

if (!$migracionSql) {
    echo "❌ ERROR: No se pudo leer sql/hybrid_migration.sql\n";
    exit(1);
}

try {
    echo "📝 Ejecutando SQL migration...\n";
    
    // Ejecutar cada sentencia SQL
    $sentencias = explode(';', $migracionSql);
    $contador = 0;
    
    foreach ($sentencias as $sql) {
        $sql = trim($sql);
        if (empty($sql) || strpos($sql, '--') === 0) {
            continue;
        }
        
        try {
            $db->exec($sql);
            $contador++;
        } catch (Exception $e) {
            // Si ya existe, continuar
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "⚠️  (Ignorado - ya existe)\n";
            } else {
                echo "❌ Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✅ Migración completada exitosamente\n";
    echo "   Sentencias ejecutadas: $contador\n\n";

    // Verificaciones post-migración
    echo "📊 Verificando estructura creada...\n\n";
    
    $verificaciones = [
        'facturacion_comprobantes' => 'Tabla de comprobantes unificada',
        'facturacion_secuencias' => 'Tabla de secuencias de correlativoss',
        'facturacion_config' => 'Tabla de configuración por driver',
        'facturacion_error_log' => 'Tabla de registro de errores',
        'v_comprobantes_pendientes' => 'Vista de comprobantes pendientes',
        'v_comprobantes_aceptados' => 'Vista de comprobantes aceptados',
        'sp_obtener_siguiente_correlativo' => 'Procedure para correlativo',
    ];

    foreach ($verificaciones as $objeto => $descripcion) {
        if (strpos($objeto, 'v_') === 0) {
            // Es una vista
            $stmt = $db->prepare(
                "SELECT COUNT(*) as cnt FROM information_schema.VIEWS 
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?"
            );
            $stmt->execute([DB_NAME, $objeto]);
        } elseif (strpos($objeto, 'sp_') === 0) {
            // Es un procedure
            $stmt = $db->prepare(
                "SELECT COUNT(*) as cnt FROM information_schema.ROUTINES 
                 WHERE ROUTINE_SCHEMA = ? AND ROUTINE_NAME = ? AND ROUTINE_TYPE = 'PROCEDURE'"
            );
            $stmt->execute([DB_NAME, $objeto]);
        } else {
            // Es una tabla
            $stmt = $db->prepare(
                "SELECT COUNT(*) as cnt FROM information_schema.TABLES 
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?"
            );
            $stmt->execute([DB_NAME, $objeto]);
        }
        
        $existe = $stmt->fetch()['cnt'] > 0;
        $icono = $existe ? '✅' : '❌';
        echo "$icono $descripcion ($objeto)\n";
    }

    // Verificar datos iniciales
    echo "\n📦 Verificando datos iniciales...\n\n";
    
    $secuencias = $db->query("SELECT COUNT(*) as cnt FROM facturacion_secuencias")->fetch();
    echo "✅ Secuencias inicializadas: " . $secuencias['cnt'] . " registros\n";
    
    $configNative = $db->query("SELECT COUNT(*) as cnt FROM facturacion_config WHERE driver = 'native'")->fetch();
    echo "✅ Configuración SUNAT Nativo: " . $configNative['cnt'] . " parámetros\n";
    
    $configNubefact = $db->query("SELECT COUNT(*) as cnt FROM facturacion_config WHERE driver = 'nubefact'")->fetch();
    echo "✅ Configuración NubeFacT: " . $configNubefact['cnt'] . " parámetros\n";
    
    echo "\n╔═══════════════════════════════════════════════════════════╗\n";
    echo "║          ✅ MIGRACIÓN COMPLETADA EXITOSAMENTE           ║\n";
    echo "╚═══════════════════════════════════════════════════════════╝\n\n";
    
} catch (Throwable $e) {
    echo "\n❌ ERROR FATAL: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n\n";
    exit(1);
}
