<?php
/**
 * Script mejorado para ejecutar migración SQL
 */

require_once __DIR__ . '/includes/functions.php';

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║  EJECUTANDO MIGRACIÓN: FACTURACIÓN ELECTRÓNICA HÍBRIDA    ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

$db = getDB();

$sqlFile = __DIR__ . '/sql/hybrid_migration.sql';
if (!file_exists($sqlFile)) {
    echo "❌ ERROR: Archivo $sqlFile no encontrado\n";
    exit(1);
}

$sql = file_get_contents($sqlFile);

try {
    echo "📝 Leyendo archivo SQL...\n";
    
    // Dividir por ";" pero ignorar comentarios y strings
    $queries = [];
    $currentQuery = '';
    $delimiter = ';';
    $inString = false;
    $stringChar = null;
    $prevChar = '';
    
    $lines = explode("\n", $sql);
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        // Ignorar comentarios
        if (strpos($trimmed, '--') === 0) {
            continue;
        }
        if (strpos($trimmed, '/*') === 0 && strpos($trimmed, '*/') === false) {
            continue; // Comentario multi-línea iniciado
        }
        
        // Cambio de delimiter
        if (preg_match('/^DELIMITER\s+(.+)/i', $trimmed, $m)) {
            $delimiter = trim($m[1]);
            continue;
        }
        
        $currentQuery .= $line . "\n";
        
        // Buscar delimiter
        if (strpos($line, $delimiter) !== false) {
            $queries[] = str_replace($delimiter, ';', $currentQuery);
            $currentQuery = '';
        }
    }
    
    if (trim($currentQuery) !== '') {
        $queries[] = $currentQuery;
    }
    
    echo "   Encontradas " . count($queries) . " sentencias SQL\n";
    
    $contador = 0;
    $errores = 0;
    
    foreach ($queries as $q) {
        $q = trim($q);
        if (empty($q)) continue;
        
        try {
            $db->exec($q);
            $contador++;
            echo "   ✓ Ejecutada sentencia #$contador\n";
        } catch (Exception $e) {
            $errores++;
            $msg = $e->getMessage();
            // Si es una tabla existente, ignorar
            if (strpos($msg, 'already exists') === false && 
                strpos($msg, 'Duplicate') === false &&
                strpos($msg, 'Syntax error') === false) {
                echo "   ⚠  Sentencia #" . ($contador + $errores) . ": " . substr($msg, 0, 80) . "...\n";
            }
        }
    }
    
    echo "\n✅ Migración completada\n";
    echo "   Sentencias ejecutadas: $contador\n";
    echo "   Errores/Ignorados: $errores\n\n";

    // Verificaciones post-migración
    echo "📊 Verificando estructura creada...\n\n";
    
    $verificaciones = [
        ['tabla', 'facturacion_comprobantes', 'Tabla de comprobantes unificada'],
        ['tabla', 'facturacion_secuencias', 'Tabla de secuencias'],
        ['tabla', 'facturacion_config', 'Tabla de configuración'],
        ['tabla', 'facturacion_error_log', 'Tabla de registro de errores'],
        ['vista', 'v_comprobantes_pendientes', 'Vista de comprobantes pendientes'],
        ['vista', 'v_comprobantes_aceptados', 'Vista de comprobantes aceptados'],
        ['procedure', 'sp_obtener_siguiente_correlativo', 'Procedure para correlativo'],
    ];

    $allOk = true;
    foreach ($verificaciones as $item) {
        $tipo = $item[0];
        $nombre = $item[1];
        $descripcion = $item[2];
        $existe = false;
        
        if ($tipo === 'tabla') {
            $stmt = $db->prepare(
                "SELECT COUNT(*) as cnt FROM information_schema.TABLES 
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?"
            );
            $stmt->execute([DB_NAME, $nombre]);
            $existe = $stmt->fetch()['cnt'] > 0;
        } elseif ($tipo === 'vista') {
            $stmt = $db->prepare(
                "SELECT COUNT(*) as cnt FROM information_schema.VIEWS 
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?"
            );
            $stmt->execute([DB_NAME, $nombre]);
            $existe = $stmt->fetch()['cnt'] > 0;
        } elseif ($tipo === 'procedure') {
            $stmt = $db->prepare(
                "SELECT COUNT(*) as cnt FROM information_schema.ROUTINES 
                 WHERE ROUTINE_SCHEMA = ? AND ROUTINE_NAME = ? AND ROUTINE_TYPE = 'PROCEDURE'"
            );
            $stmt->execute([DB_NAME, $nombre]);
            $existe = $stmt->fetch()['cnt'] > 0;
        }
        
        if ($existe) {
            echo "✅ $descripcion ($nombre)\n";
        } else {
            echo "❌ $descripcion ($nombre)\n";
            $allOk = false;
        }
    }

    // Verificar datos iniciales
    echo "\n📦 Verificando datos iniciales...\n\n";
    
    try {
        $secuencias = $db->query("SELECT COUNT(*) as cnt FROM facturacion_secuencias")->fetch();
        echo "✅ Secuencias inicializadas: " . $secuencias['cnt'] . " registros\n";
        
        $configNative = $db->query("SELECT COUNT(*) as cnt FROM facturacion_config WHERE driver = 'native'")->fetch();
        echo "✅ Configuración SUNAT Nativo: " . $configNative['cnt'] . " parámetros\n";
        
        $configNubefact = $db->query("SELECT COUNT(*) as cnt FROM facturacion_config WHERE driver = 'nubefact'")->fetch();
        echo "✅ Configuración NubeFacT: " . $configNubefact['cnt'] . " parámetros\n";
    } catch (Exception $e) {
        echo "⚠️  No se pude verificar datos iniciales: " . $e->getMessage() . "\n";
    }

    echo "\n";
    if ($allOk) {
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║          ✅ MIGRACIÓN COMPLETADA EXITOSAMENTE           ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n\n";
    } else {
        echo "⚠️  Algunos componentes no se crearon. Verifica los errores arriba.\n\n";
    }
    
} catch (Throwable $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n\n";
    exit(1);
}
