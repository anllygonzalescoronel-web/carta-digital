<?php
/**
 * VERIFICADOR DE INSTALACIÓN
 * Detecta si Carta Digital ya está instalada
 */

$isInstalled = false;
$installationInfo = [];

try {
    // Verificar archivo de configuración
    if (file_exists(__DIR__ . '/../includes/db.php')) {
        require_once __DIR__ . '/../includes/db.php';
        
        // Intentar conectar
        if (function_exists('getDB')) {
            try {
                $db = getDB();
                
                // Verificar tablas principales
                $result = $db->query("SELECT COUNT(*) FROM information_schema.tables 
                    WHERE table_schema = '" . DB_NAME . "' AND table_name = 'usuarios'");
                
                if ($result->fetchColumn() > 0) {
                    // BD existe y tiene la tabla usuarios
                    $result = $db->query("SELECT COUNT(*) FROM usuarios WHERE is_admin = 1");
                    if ($result->fetchColumn() > 0) {
                        $isInstalled = true;
                        
                        // Obtener información
                        $config = $db->query("SELECT clave, valor FROM configuracion WHERE clave IN ('nombre_negocio', 'sitio_titulo')")
                            ->fetchAll(PDO::FETCH_KEY_PAIR);
                        
                        $installationInfo = [
                            'status' => 'Instalado correctamente',
                            'business_name' => $config['nombre_negocio'] ?? 'Sin configurar',
                            'database' => DB_NAME,
                            'host' => DB_HOST,
                            'installed_at' => date('Y-m-d H:i:s', filemtime(__DIR__ . '/../includes/db.php'))
                        ];
                    }
                }
            } catch (Exception $e) {
                // BD no está completamente configurada
                $isInstalled = false;
            }
        }
    }
} catch (Exception $e) {
    $isInstalled = false;
}

// Si ya está instalado, redirigir
if ($isInstalled) {
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carta Digital - Ya Instalado</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .alert-container {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 40px;
            max-width: 600px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            text-align: center;
        }
        
        .alert-icon {
            font-size: 4em;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #e8ff47;
            font-size: 1.8em;
            margin-bottom: 10px;
        }
        
        .info-box {
            background: rgba(232, 255, 71, 0.1);
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        
        .info-row {
            padding: 8px 0;
            border-bottom: 1px solid #334155;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #94a3b8;
            font-size: 0.9em;
        }
        
        .info-value {
            color: #e8ff47;
            font-weight: 600;
            margin-top: 4px;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #e8ff47, #d1e535);
            color: #0f172a;
        }
        
        .btn-secondary {
            background: #334155;
            color: #e2e8f0;
            border: 1px solid #334155;
        }
        
        .btn-secondary:hover {
            background: #475569;
        }
        
        .warning {
            color: #f59e0b;
            font-size: 0.9em;
            margin-top: 20px;
            padding: 15px;
            background: rgba(245, 158, 11, 0.1);
            border-radius: 6px;
            border-left: 4px solid #f59e0b;
        }
    </style>
</head>
<body>
    <div class="alert-container">
        <div class="alert-icon">✅</div>
        <h1>¡Sistema Ya Instalado!</h1>
        <p style="color: #94a3b8; margin-bottom: 20px;">
            Carta Digital ya está completamente instalado y funcionando.
        </p>
        
        <div class="info-box">
            <div class="info-row">
                <div class="info-label">Estado:</div>
                <div class="info-value"><?php echo htmlspecialchars($installationInfo['status'] ?? 'Desconocido'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Base de Datos:</div>
                <div class="info-value"><?php echo htmlspecialchars($installationInfo['database'] ?? ''); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Negocio:</div>
                <div class="info-value"><?php echo htmlspecialchars($installationInfo['business_name'] ?? 'Sin configurar'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Host MySQL:</div>
                <div class="info-value"><?php echo htmlspecialchars($installationInfo['host'] ?? ''); ?></div>
            </div>
        </div>
        
        <div class="actions">
            <a href="../" class="btn btn-primary">👀 Ver Carta Digital</a>
            <a href="../admin/" class="btn btn-primary">⚙️ Panel Admin</a>
            <a href="diagnostico.php" class="btn btn-secondary">🔧 Diagnóstico</a>
        </div>
        
        <div class="warning">
            💡 Si necesitas reinstalar, elimina manualmente las tablas de la base de datos o contacta a soporte.
        </div>
    </div>
</body>
</html>
    <?php
    exit;
}

// Si no está instalado, mostrar el instalador
?>
