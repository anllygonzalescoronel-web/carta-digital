<?php
/**
 * CARTA DIGITAL - INSTALADOR
 * API de Verificación (check.php)
 */

header('Content-Type: application/json; charset=utf-8');

// Detectar si es una solicitud de verificación inicial o prueba de DB
$input = @file_get_contents('php://input');
$data = @json_decode($input, true);

if (isset($data['action']) && $data['action'] === 'test_db') {
    testDatabaseConnection($data['config']);
} else {
    checkAllRequirements();
}

function checkAllRequirements() {
    $requirements = [];

    // ========== PHP VERSION ==========
    $phpVersion = phpversion();
    $requirements['php'] = [];

    $phpVersionNum = (float)substr($phpVersion, 0, 3);
    if ($phpVersionNum >= 8.0) {
        $requirements['php'][] = [
            'name' => 'Versión de PHP',
            'message' => "PHP {$phpVersion} (Requerido: 8.0+)",
            'status' => 'success'
        ];
    } else {
        $requirements['php'][] = [
            'name' => 'Versión de PHP',
            'message' => "PHP {$phpVersion} - ⚠️ Se requiere 8.0+",
            'status' => 'error'
        ];
    }

    // ========== EXTENSIONS ==========
    $requirements['extensions'] = [];
    
    $requiredExtensions = [
        'pdo' => 'PDO (Acceso a BD)',
        'pdo_mysql' => 'PDO MySQL',
        'curl' => 'cURL (Llamadas HTTP)',
        'openssl' => 'OpenSSL (Seguridad)',
        'gd' => 'GD (Procesamiento de imágenes)',
        'mbstring' => 'Multibyte String',
        'xml' => 'XML (Facturación)',
        'json' => 'JSON',
        'zip' => 'ZIP (Composer)'
    ];

    foreach ($requiredExtensions as $ext => $label) {
        $isLoaded = extension_loaded($ext);
        $requirements['extensions'][] = [
            'name' => $label,
            'message' => $isLoaded ? 'Instalada' : 'No encontrada',
            'status' => $isLoaded ? 'success' : 'error'
        ];
    }

    // ========== MYSQL ==========
    $requirements['mysql'] = [];

    // Verificar si MySQL está disponible
    $mysqlAvailable = extension_loaded('pdo_mysql');
    $requirements['mysql'][] = [
        'name' => 'Soporte MySQL',
        'message' => $mysqlAvailable ? 'PDO MySQL disponible' : 'PDO MySQL no encontrado',
        'status' => $mysqlAvailable ? 'success' : 'error'
    ];

    // ========== FILE PERMISSIONS ==========
    $requirements['permissions'] = [];

    $uploadDirs = [
        'uploads' => '../uploads',
        'uploads/productos' => '../uploads/productos',
        'uploads/banners' => '../uploads/banners',
        'uploads/categorias' => '../uploads/categorias',
        'uploads/sunat' => '../uploads/sunat'
    ];

    foreach ($uploadDirs as $name => $path) {
        $fullPath = __DIR__ . '/' . $path;
        $exists = is_dir($fullPath);
        $writable = is_writable($fullPath);
        
        if ($exists && $writable) {
            $requirements['permissions'][] = [
                'name' => "Directorio: {$name}",
                'message' => 'Directorio existe y es escribible',
                'status' => 'success'
            ];
        } else if ($exists) {
            $requirements['permissions'][] = [
                'name' => "Directorio: {$name}",
                'message' => 'Existe pero NO es escribible',
                'status' => 'warning'
            ];
        } else {
            $requirements['permissions'][] = [
                'name' => "Directorio: {$name}",
                'message' => 'No existe (se creará)',
                'status' => 'warning'
            ];
        }
    }

    // Verificar archivo de configuración
    $dbConfigPath = __DIR__ . '/../includes/db.php';
    if (file_exists($dbConfigPath)) {
        $requirements['permissions'][] = [
            'name' => 'Archivo de configuración DB',
            'message' => 'includes/db.php encontrado',
            'status' => 'success'
        ];
    }

    // ========== WEBSERVER ==========
    $requirements['webserver'] = [];

    // Verificar servidor web
    $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido';
    $requirements['webserver'][] = [
        'name' => 'Servidor Web',
        'message' => $serverSoftware,
        'status' => 'success'
    ];

    // Verificar HTTPS (recomendado para producción)
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $requirements['webserver'][] = [
        'name' => 'HTTPS/SSL',
        'message' => $isHttps ? 'Habilitado' : 'No habilitado (solo en desarrollo)',
        'status' => $isHttps ? 'success' : 'warning'
    ];

    // Verificar que estamos en el directorio correcto
    $isInCorrectDir = strpos(__DIR__, 'installer') !== false;
    $requirements['webserver'][] = [
        'name' => 'Ubicación del instalador',
        'message' => $isInCorrectDir ? 'Correcta' : 'Incorrecta',
        'status' => $isInCorrectDir ? 'success' : 'error'
    ];

    // Verificar memoria disponible
    $memoryLimit = ini_get('memory_limit');
    if ($memoryLimit === '-1') {
        $memoryLimitFormatted = 'Ilimitada';
        $status = 'success';
    } else {
        $memoryBytes = convertToBytes($memoryLimit);
        $memoryLimitFormatted = $memoryLimit;
        $status = $memoryBytes >= (128 * 1024 * 1024) ? 'success' : 'warning';
    }
    
    $requirements['webserver'][] = [
        'name' => 'Límite de memoria',
        'message' => "Configurado en: {$memoryLimitFormatted}",
        'status' => $status
    ];

    // Verificar timeout de ejecución
    $maxExecTime = ini_get('max_execution_time');
    $requirements['webserver'][] = [
        'name' => 'Timeout de ejecución',
        'message' => "Configurado en: {$maxExecTime}s",
        'status' => $maxExecTime >= 30 || $maxExecTime === '0' ? 'success' : 'warning'
    ];

    // Verificar tamaño máximo de upload
    $uploadMax = convertToBytes(ini_get('upload_max_filesize'));
    $postMax = convertToBytes(ini_get('post_max_size'));
    $maxUpload = min($uploadMax, $postMax);
    $maxUploadMB = $maxUpload / (1024 * 1024);
    
    $requirements['webserver'][] = [
        'name' => 'Tamaño máximo de upload',
        'message' => "Límite: {$maxUploadMB}MB",
        'status' => $maxUpload >= (50 * 1024 * 1024) ? 'success' : 'warning'
    ];

    echo json_encode($requirements);
}

function testDatabaseConnection($config) {
    $response = ['success' => false, 'error' => ''];

    try {
        $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ]);

        // Test connection
        $version = $pdo->query("SELECT VERSION()")->fetchColumn();
        
        // Try to connect to the specific database
        try {
            $pdo->exec("USE `{$config['db_name']}`");
            $dbExists = true;
        } catch (Exception $e) {
            $dbExists = false;
        }

        $response = [
            'success' => true,
            'database' => $config['db_name'],
            'mysql_version' => $version,
            'db_exists' => $dbExists,
            'message' => $dbExists ? 'BD existe' : 'BD no existe (se creará)'
        ];

    } catch (PDOException $e) {
        $response['error'] = htmlspecialchars($e->getMessage());
    }

    echo json_encode($response);
}

function convertToBytes($value) {
    $value = trim($value);
    $last = strtolower($value[strlen($value)-1]);
    $value = (int)$value;
    
    switch($last) {
        case 'g': $value *= 1024;
        case 'm': $value *= 1024;
        case 'k': $value *= 1024;
    }
    
    return $value;
}
?>
