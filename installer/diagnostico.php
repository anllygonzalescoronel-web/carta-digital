<?php
/**
 * CARTA DIGITAL - TROUBLESHOOTING
 * Herramienta de diagnóstico para solucionar problemas
 */

session_start();

$diagnostics = [];
$errors = [];
$warnings = [];

// ========== PHP CHECKS ==========
$diagnostics['php_version'] = phpversion();
$diagnostics['php_sapi'] = php_sapi_name();
$diagnostics['os'] = php_uname();

// ========== EXTENSIONS ==========
$requiredExtensions = ['pdo', 'pdo_mysql', 'curl', 'openssl', 'gd', 'mbstring', 'xml', 'json'];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $errors[] = "Extensión requerida no encontrada: $ext";
    }
}

// ========== FILE SYSTEM ==========
$uploadsPath = __DIR__ . '/../uploads';
if (!is_dir($uploadsPath)) {
    $warnings[] = "Directorio 'uploads' no existe";
} elseif (!is_writable($uploadsPath)) {
    $errors[] = "Directorio 'uploads' no tiene permisos de escritura";
}

// ========== DATABASE ==========
try {
    $dbConfig = include_once __DIR__ . '/../includes/db.php';
    $db = getDB();
    $diagnostics['database'] = 'Conectada';
    $diagnostics['database_version'] = $db->query("SELECT VERSION()")->fetchColumn();
} catch (Exception $e) {
    $errors[] = "No se puede conectar a la base de datos: " . $e->getMessage();
}

// ========== FILE PERMISSIONS ==========
$criticalFiles = [
    'includes/db.php' => 'Archivo de configuración DB',
    'index.php' => 'Archivo principal'
];

foreach ($criticalFiles as $file => $label) {
    $path = __DIR__ . '/../' . $file;
    if (!file_exists($path)) {
        $errors[] = "$label no encontrado: $file";
    } elseif (!is_readable($path)) {
        $errors[] = "$label no es legible: $file";
    }
}

// ========== GENERATE HTML REPORT ==========
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico - Carta Digital</title>
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
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px 0;
            border-bottom: 1px solid #334155;
        }

        .header h1 {
            font-size: 2.5em;
            color: #e8ff47;
            margin-bottom: 10px;
        }

        .header p {
            color: #94a3b8;
        }

        .section {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            margin-bottom: 30px;
            padding: 0;
            overflow: hidden;
        }

        .section-header {
            background: linear-gradient(135deg, rgba(232, 255, 71, 0.1) 0%, rgba(30, 41, 59, 0.5) 100%);
            padding: 15px 20px;
            border-bottom: 1px solid #334155;
            font-weight: 600;
            color: #e8ff47;
        }

        .section-content {
            padding: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #334155;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #cbd5e1;
        }

        .info-value {
            color: #e2e8f0;
            font-family: 'Courier New', monospace;
        }

        .alert {
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            border-left: 4px solid;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border-left-color: #ef4444;
            color: #fca5a5;
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border-left-color: #f59e0b;
            color: #fcd34d;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-left-color: #10b981;
            color: #a7f3d0;
        }

        .alert strong {
            display: block;
            margin-bottom: 5px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .status-ok {
            background: rgba(16, 185, 129, 0.2);
            color: #a7f3d0;
        }

        .status-error {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        .status-warning {
            background: rgba(245, 158, 11, 0.2);
            color: #fcd34d;
        }

        .actions {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #334155;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 0 10px;
            background: linear-gradient(135deg, #e8ff47, #d1e535);
            color: #0f172a;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.2s;
            cursor: pointer;
            border: none;
            font-size: 1em;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .code-block {
            background: #0f172a;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            margin: 10px 0;
            border: 1px solid #334155;
        }

        .footer {
            text-align: center;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #334155;
            color: #94a3b8;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🔧 Diagnóstico del Sistema</h1>
            <p>Herramienta de verificación y troubleshooting</p>
        </div>

        <!-- Status Summary -->
        <?php if (!empty($errors) || !empty($warnings)): ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <strong>❌ Se encontraron problemas críticos:</strong>
                    <?php foreach ($errors as $error): ?>
                        <div>• <?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($warnings)): ?>
                <div class="alert alert-warning">
                    <strong>⚠️ Advertencias:</strong>
                    <?php foreach ($warnings as $warning): ?>
                        <div>• <?php echo htmlspecialchars($warning); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-success">
                <strong>✅ Sistema listo:</strong>
                Todos los requisitos se cumplen correctamente.
            </div>
        <?php endif; ?>

        <!-- Diagnostics Sections -->
        
        <!-- PHP Information -->
        <div class="section">
            <div class="section-header">PHP</div>
            <div class="section-content">
                <div class="info-row">
                    <span class="info-label">Versión:</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($diagnostics['php_version']); ?>
                        <?php if (version_compare($diagnostics['php_version'], '8.0', '>=')): ?>
                            <span class="status-badge status-ok">✓ OK</span>
                        <?php else: ?>
                            <span class="status-badge status-error">✗ Se requiere 8.0+</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">SAPI:</span>
                    <span class="info-value"><?php echo htmlspecialchars($diagnostics['php_sapi']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Sistema Operativo:</span>
                    <span class="info-value"><?php echo htmlspecialchars($diagnostics['os']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Memoria Máxima:</span>
                    <span class="info-value"><?php echo ini_get('memory_limit'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Timeout de Ejecución:</span>
                    <span class="info-value"><?php echo ini_get('max_execution_time'); ?>s</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tamaño Máximo de Upload:</span>
                    <span class="info-value"><?php echo ini_get('upload_max_filesize'); ?></span>
                </div>
            </div>
        </div>

        <!-- Extensions -->
        <div class="section">
            <div class="section-header">Extensiones PHP</div>
            <div class="section-content">
                <?php foreach ($requiredExtensions as $ext): ?>
                    <div class="info-row">
                        <span class="info-label"><?php echo htmlspecialchars($ext); ?>:</span>
                        <span class="info-value">
                            <?php if (extension_loaded($ext)): ?>
                                <span class="status-badge status-ok">✓ Instalada</span>
                            <?php else: ?>
                                <span class="status-badge status-error">✗ No encontrada</span>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Database -->
        <?php if (isset($diagnostics['database'])): ?>
            <div class="section">
                <div class="section-header">Base de Datos</div>
                <div class="section-content">
                    <div class="info-row">
                        <span class="info-label">Conexión:</span>
                        <span class="info-value">
                            <span class="status-badge status-ok">✓ Conectada</span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Versión MySQL:</span>
                        <span class="info-value"><?php echo htmlspecialchars($diagnostics['database_version']); ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- File System -->
        <div class="section">
            <div class="section-header">Sistema de Archivos</div>
            <div class="section-content">
                <div class="info-row">
                    <span class="info-label">Directorio uploads:</span>
                    <span class="info-value">
                        <?php if (is_dir($uploadsPath)): ?>
                            <?php if (is_writable($uploadsPath)): ?>
                                <span class="status-badge status-ok">✓ OK</span>
                            <?php else: ?>
                                <span class="status-badge status-error">✗ No escribible</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="status-badge status-warning">⚠ No existe</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Ruta:</span>
                    <span class="info-value" style="font-size: 0.85em;"><?php echo htmlspecialchars($uploadsPath); ?></span>
                </div>
            </div>
        </div>

        <!-- Solutions -->
        <div class="section">
            <div class="section-header">📚 Soluciones Rápidas</div>
            <div class="section-content">
                <h3 style="margin-bottom: 15px;">Extensiones Faltantes</h3>
                <p style="margin-bottom: 10px;">Si falta alguna extensión, instálala según tu servidor:</p>
                
                <div class="code-block"><strong># En Ubuntu/Debian:</strong>
sudo apt-get install php8.1-mysql php8.1-curl php8.1-gd php8.1-mbstring php8.1-xml</div>

                <div class="code-block"><strong># En CentOS/Red Hat:</strong>
sudo yum install php-pdo php-mysql php-curl php-gd php-mbstring php-xml</div>

                <div class="code-block"><strong># En XAMPP (Windows):</strong>
Descomenta las extensiones en php.ini y reinicia Apache</div>

                <hr style="border: 1px solid #334155; margin: 20px 0;">

                <h3 style="margin-bottom: 15px;">Permisos de Directorios</h3>

                <div class="code-block"><strong># Linux/Mac:</strong>
chmod -R 755 uploads/
chmod -R 755 uploads/productos
chmod -R 755 uploads/banners</div>

                <div class="code-block"><strong># PowerShell (Windows Admin):</strong>
# Solo si es necesario cambiar permisos
icacls "C:\path\to\uploads" /grant Users:F /T</div>
            </div>
        </div>

        <!-- Actions -->
        <div class="actions">
            <button class="btn" onclick="location.href='index.php'">
                ← Volver al Instalador
            </button>
            <button class="btn" onclick="location.reload()">
                🔄 Actualizar Diagnóstico
            </button>
            <button class="btn" onclick="downloadReport()">
                📥 Descargar Reporte
            </button>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Carta Digital v2.0 | Herramienta de diagnóstico</p>
        <p>Generado: <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>

    <script>
        function downloadReport() {
            const report = document.body.innerText;
            const blob = new Blob([report], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'diagnostico_' + new Date().getTime() + '.txt';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        }
    </script>
</body>
</html>
