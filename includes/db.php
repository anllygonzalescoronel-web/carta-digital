<?php
/**
 * Conexión a la base de datos (PDO + MySQL)
 * Ajusta estos datos a tu XAMPP (por defecto: usuario root, sin clave).
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'carta_digital');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            die('Error de conexión a la base de datos. Verifica includes/db.php. (' . $e->getMessage() . ')');
        }
    }
    return $pdo;
}
