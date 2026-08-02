<?php
/**
 * CARTA DIGITAL - INSTALADOR
 * API de Instalación (install.php)
 */

header('Content-Type: application/json; charset=utf-8');

// Set error handling
set_error_handler(function($errno, $errstr) {
    throw new Exception("PHP Error: $errstr");
});

$input = @file_get_contents('php://input');
$data = @json_decode($input, true);

$config = $data['config'] ?? [];
$action = $data['action'] ?? '';

$response = ['success' => false, 'error' => '', 'message' => ''];

try {
    switch ($action) {
        case 'create_db':
            createDatabase($config);
            $response['success'] = true;
            $response['message'] = 'Base de datos creada correctamente';
            break;

        case 'import_schema':
            importSchema($config);
            $response['success'] = true;
            $response['message'] = 'Esquema importado correctamente';
            break;

        case 'install_composer':
            installComposerDependencies();
            $response['success'] = true;
            $response['message'] = 'Dependencias instaladas correctamente';
            break;

        case 'check_permissions':
            checkAndCreateDirectories();
            $response['success'] = true;
            $response['message'] = 'Directorios configurados correctamente';
            break;

        case 'create_config':
            createConfigFile($config);
            $response['success'] = true;
            $response['message'] = 'Configuración creada correctamente';
            break;

        default:
            throw new Exception("Acción no reconocida: $action");
    }

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);

// ========== INSTALLATION FUNCTIONS ==========

function createDatabase($config) {
    // Conectar al servidor MySQL sin especificar BD
    $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    ]);

    $dbName = $config['db_name'];
    
    // Crear base de datos si no existe
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` 
        DEFAULT CHARACTER SET utf8mb4 
        DEFAULT COLLATE utf8mb4_unicode_ci");

    // Seleccionar la BD
    $pdo->exec("USE `{$dbName}`");

    return true;
}

function importSchema($config) {
    // Conectar a la BD específica
    $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    ]);

    // Leer archivo schema.sql
    $schemaFile = __DIR__ . '/../../sql/schema.sql';
    
    if (!file_exists($schemaFile)) {
        throw new Exception("Archivo sql/schema.sql no encontrado en: $schemaFile");
    }

    $sql = file_get_contents($schemaFile);

    if (empty($sql)) {
        throw new Exception("El archivo schema.sql está vacío");
    }

    // Dividir por puntos y coma y ejecutar cada sentencia
    // Esto es necesario porque PDO no soporta ejecución múltiple por defecto
    $statements = preg_split('/;(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $sql);

    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        if (empty($statement)) {
            continue;
        }

        // Skip comments
        if (strpos($statement, '--') === 0) {
            continue;
        }

        // Skip MySQL specific comments
        if (strpos($statement, '/*') === 0) {
            continue;
        }

        try {
            $pdo->exec($statement . ';');
        } catch (PDOException $e) {
            if (stripos($e->getMessage(), 'already exists') !== false) {
                // Ignorar errores de "tabla ya existe"
                continue;
            }
            throw $e;
        }
    }

    return true;
}

function installComposerDependencies() {
    $composerPath = __DIR__ . '/../../composer.json';
    $vendorPath = __DIR__ . '/../../vendor';

    // Verificar si composer.json existe
    if (!file_exists($composerPath)) {
        // No hay dependencias de Composer configuradas
        return true;
    }

    // Verificar si ya está instalado
    if (file_exists($vendorPath . '/autoload.php')) {
        // Ya está instalado
        return true;
    }

    // Intentar instalar usando composer
    $composerCmd = 'composer install --working-dir=' . escapeshellarg(__DIR__ . '/../../');

    // Ejecutar comando
    $output = shell_exec($composerCmd . ' 2>&1');

    // Verificar que se instaló
    if (!file_exists($vendorPath . '/autoload.php')) {
        // Si no está disponible Composer en línea de comandos, es OK
        // El usuario puede ejecutarlo manualmente después
        if (extension_loaded('phar')) {
            throw new Exception('Composer no está disponible. Ejecute: composer install');
        }
    }

    return true;
}

function checkAndCreateDirectories() {
    $baseDir = __DIR__ . '/../../';
    
    $uploadDirs = [
        'uploads',
        'uploads/productos',
        'uploads/banners',
        'uploads/categorias',
        'uploads/sunat',
        'uploads/sunat/cdr',
        'uploads/sunat/certificados',
        'uploads/sunat/pdf',
        'uploads/sunat/xml',
        'uploads/iconos'
    ];

    foreach ($uploadDirs as $dir) {
        $fullPath = $baseDir . $dir;
        
        // Crear directorio si no existe
        if (!is_dir($fullPath)) {
            if (!mkdir($fullPath, 0755, true)) {
                throw new Exception("No se pudo crear el directorio: $dir");
            }
        }

        // Intentar dar permisos de escritura
        if (!is_writable($fullPath)) {
            @chmod($fullPath, 0755);
        }

        if (!is_writable($fullPath)) {
            // Advertencia pero no error
            error_log("Advertencia: El directorio $dir no es escribible");
        }
    }

    return true;
}

function createConfigFile($config) {
    $dbConfigFile = __DIR__ . '/../../includes/db.php';

    // Verificar que el archivo existe o crearlo
    if (!file_exists($dbConfigFile)) {
        throw new Exception("El archivo includes/db.php no existe");
    }

    // Leer el archivo original
    $content = file_get_contents($dbConfigFile);

    // Reemplazar valores de configuración
    $newContent = $content;

    // Reemplazar define statements de forma segura
    $replacements = [
        "define('DB_HOST', 'localhost')" => "define('DB_HOST', '" . addslashes($config['db_host']) . "')",
        "define('DB_NAME', 'carta_digital')" => "define('DB_NAME', '" . addslashes($config['db_name']) . "')",
        "define('DB_USER', 'root')" => "define('DB_USER', '" . addslashes($config['db_user']) . "')",
        "define('DB_PASS', '')" => "define('DB_PASS', '" . addslashes($config['db_pass']) . "')",
    ];

    foreach ($replacements as $search => $replace) {
        $newContent = str_replace($search, $replace, $newContent);
    }

    // Guardar la configuración
    if (!file_put_contents($dbConfigFile, $newContent)) {
        throw new Exception("No se pudo escribir el archivo de configuración");
    }

    // También crear un archivo .env si el sistema lo necesita
    createEnvFile($config);

    return true;
}

function createEnvFile($config) {
    $envFile = __DIR__ . '/../../.env';

    $envContent = "; Archivo de configuración de Carta Digital
; Generado automáticamente por el instalador

; Base de datos
DB_HOST={$config['db_host']}
DB_PORT={$config['db_port']}
DB_NAME={$config['db_name']}
DB_USER={$config['db_user']}
DB_PASS={$config['db_pass']}

; Aplicación
APP_NAME=\"Carta Digital\"
APP_DEBUG=false
APP_ENV=production

; Seguridad
SESSION_NAME=carta_digital_session

; Configuración de servidor
CHARSET=utf8mb4
TIMEZONE=America/Lima

; APIs externas (configurar después en admin)
CULQI_PUBLIC_KEY=
CULQI_SECRET_KEY=
APIPERU_TOKEN=
WHATSAPP_NUMBER=

; Correo
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=\"Carta Digital\"

; Facturación
NUBEFACT_RUC=
NUBEFACT_USERNAME=
NUBEFACT_PASSWORD=
NUBEFACT_CLIENT_SECRET=

; Greenter (Facturación electrónica)
SUNAT_SOL_USER=
SUNAT_SOL_PASS=
SUNAT_CERTIFICATE_PATH=

; Timestamp
GENERATED_AT=" . date('Y-m-d H:i:s') . "
";

    @file_put_contents($envFile, $envContent);

    return true;
}

?>
