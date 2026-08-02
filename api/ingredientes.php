<?php
ini_set('display_errors', '0');
ob_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requerirRol(['admin', 'cocinero']);

$db = getDB();

// ── Auto-migración ─────────────────────────────────────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS ingredientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    unidad ENUM('kg','g','l','ml','m','cm','unidad','porcion') NOT NULL DEFAULT 'unidad',
    stock_actual DECIMAL(10,3) NOT NULL DEFAULT 0,
    stock_minimo DECIMAL(10,3) NOT NULL DEFAULT 0,
    costo_unitario DECIMAL(10,4) NOT NULL DEFAULT 0,
    descripcion VARCHAR(255) DEFAULT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB");

$db->exec("CREATE TABLE IF NOT EXISTS producto_ingredientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    ingrediente_id INT NOT NULL,
    cantidad DECIMAL(10,3) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_prod_ing (producto_id, ingrediente_id)
) ENGINE=InnoDB");

$db->exec("CREATE TABLE IF NOT EXISTS ingrediente_movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingrediente_id INT NOT NULL,
    tipo ENUM('entrada','salida','ajuste') NOT NULL,
    cantidad DECIMAL(10,3) NOT NULL,
    stock_antes DECIMAL(10,3) NOT NULL,
    stock_despues DECIMAL(10,3) NOT NULL,
    motivo VARCHAR(255) DEFAULT NULL,
    pedido_id INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $accion = trim($_GET['accion'] ?? 'listar');

        if ($accion === 'listar') {
            $ingredientes = $db->query(
                "SELECT * FROM ingredientes ORDER BY nombre ASC"
            )->fetchAll();

            $out = array_map(fn($i) => [
                'id'            => (int)$i['id'],
                'nombre'        => $i['nombre'],
                'unidad'        => $i['unidad'],
                'stock_actual'  => (float)$i['stock_actual'],
                'stock_minimo'  => (float)$i['stock_minimo'],
                'costo_unitario'=> (float)$i['costo_unitario'],
                'descripcion'   => $i['descripcion'] ?? '',
                'activo'        => (int)$i['activo'],
                'bajo_stock'    => (float)$i['stock_actual'] <= (float)$i['stock_minimo'] && (float)$i['stock_minimo'] > 0,
            ], $ingredientes);

            jsonResponse(['ok' => true, 'ingredientes' => $out, 'total' => count($out)]);
        }

        if ($accion === 'producto') {
            // Ingredientes asignados a un producto
            $productoId = (int)($_GET['producto_id'] ?? 0);
            if ($productoId <= 0) jsonResponse(['ok' => false, 'mensaje' => 'producto_id inválido'], 400);

            $rows = $db->prepare(
                "SELECT pi.ingrediente_id, pi.cantidad, i.nombre, i.unidad, i.stock_actual
                 FROM producto_ingredientes pi
                 INNER JOIN ingredientes i ON i.id = pi.ingrediente_id
                 WHERE pi.producto_id = :pid
                 ORDER BY i.nombre ASC"
            );
            $rows->execute(['pid' => $productoId]);

            jsonResponse(['ok' => true, 'ingredientes' => $rows->fetchAll()]);
        }

        if ($accion === 'historial') {
            requerirRol(['admin']);
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) jsonResponse(['ok' => false, 'mensaje' => 'id inválido'], 400);

            $rows = $db->prepare(
                "SELECT * FROM ingrediente_movimientos WHERE ingrediente_id = :id ORDER BY creado_en DESC LIMIT 50"
            );
            $rows->execute(['id' => $id]);
            jsonResponse(['ok' => true, 'movimientos' => $rows->fetchAll()]);
        }

        jsonResponse(['ok' => false, 'mensaje' => 'Acción GET no reconocida'], 400);
    }

    // ── POST (solo admin) ──────────────────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['ok' => false, 'mensaje' => 'Método no permitido'], 405);
    }
    requerirRol(['admin']);

    $body = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body)) jsonResponse(['ok' => false, 'mensaje' => 'JSON inválido'], 400);

    $accion = (string)($body['accion'] ?? '');

    switch ($accion) {

        // ── Crear ──────────────────────────────────────────────────────────────
        case 'crear': {
            $nombre = trim((string)($body['nombre'] ?? ''));
            if ($nombre === '') jsonResponse(['ok' => false, 'mensaje' => 'El nombre es obligatorio.'], 400);

            $unidades_validas = ['kg','g','l','ml','m','cm','unidad','porcion'];
            $unidad = in_array($body['unidad'] ?? '', $unidades_validas) ? $body['unidad'] : 'unidad';
            $stock_actual  = max(0, (float)($body['stock_actual'] ?? 0));
            $stock_minimo  = max(0, (float)($body['stock_minimo'] ?? 0));
            $costo         = max(0, (float)($body['costo_unitario'] ?? 0));
            $descripcion   = trim((string)($body['descripcion'] ?? ''));

            $stmt = $db->prepare(
                "INSERT INTO ingredientes (nombre, unidad, stock_actual, stock_minimo, costo_unitario, descripcion)
                 VALUES (:n, :u, :sa, :sm, :c, :d)"
            );
            $stmt->execute([
                'n' => $nombre, 'u' => $unidad,
                'sa' => $stock_actual, 'sm' => $stock_minimo,
                'c' => $costo, 'd' => $descripcion ?: null,
            ]);
            $id = (int)$db->lastInsertId();

            // Registrar movimiento de entrada inicial si hay stock
            if ($stock_actual > 0) {
                $db->prepare(
                    "INSERT INTO ingrediente_movimientos (ingrediente_id, tipo, cantidad, stock_antes, stock_despues, motivo)
                     VALUES (:iid, 'entrada', :c, 0, :sd, 'Stock inicial')"
                )->execute(['iid' => $id, 'c' => $stock_actual, 'sd' => $stock_actual]);
            }

            jsonResponse(['ok' => true, 'id' => $id]);
        }

        // ── Actualizar ────────────────────────────────────────────────────────
        case 'actualizar': {
            $id = (int)($body['id'] ?? 0);
            if ($id <= 0) jsonResponse(['ok' => false, 'mensaje' => 'ID inválido'], 400);
            $nombre = trim((string)($body['nombre'] ?? ''));
            if ($nombre === '') jsonResponse(['ok' => false, 'mensaje' => 'El nombre es obligatorio.'], 400);

            $unidades_validas = ['kg','g','l','ml','m','cm','unidad','porcion'];
            $unidad       = in_array($body['unidad'] ?? '', $unidades_validas) ? $body['unidad'] : 'unidad';
            $stock_minimo = max(0, (float)($body['stock_minimo'] ?? 0));
            $costo        = max(0, (float)($body['costo_unitario'] ?? 0));
            $descripcion  = trim((string)($body['descripcion'] ?? ''));
            $activo       = ((int)($body['activo'] ?? 1)) ? 1 : 0;

            $db->prepare(
                "UPDATE ingredientes SET nombre=:n, unidad=:u, stock_minimo=:sm, costo_unitario=:c, descripcion=:d, activo=:a
                 WHERE id=:id"
            )->execute([
                'n' => $nombre, 'u' => $unidad,
                'sm' => $stock_minimo, 'c' => $costo,
                'd' => $descripcion ?: null, 'a' => $activo, 'id' => $id,
            ]);
            jsonResponse(['ok' => true]);
        }

        // ── Eliminar ──────────────────────────────────────────────────────────
        case 'eliminar': {
            $id = (int)($body['id'] ?? 0);
            if ($id <= 0) jsonResponse(['ok' => false, 'mensaje' => 'ID inválido'], 400);
            $db->prepare("DELETE FROM ingredientes WHERE id=:id")->execute(['id' => $id]);
            jsonResponse(['ok' => true]);
        }

        // ── Ajustar stock ─────────────────────────────────────────────────────
        case 'ajustar_stock': {
            $id     = (int)($body['id'] ?? 0);
            $tipo   = (string)($body['tipo'] ?? '');   // entrada | salida | ajuste
            $cant   = (float)($body['cantidad'] ?? 0);
            $motivo = trim((string)($body['motivo'] ?? ''));

            if ($id <= 0 || !in_array($tipo, ['entrada','salida','ajuste']) || $cant <= 0) {
                jsonResponse(['ok' => false, 'mensaje' => 'Datos inválidos'], 400);
            }

            $ing = $db->prepare("SELECT stock_actual FROM ingredientes WHERE id=:id")->execute(['id'=>$id]);
            $ing = $db->prepare("SELECT stock_actual FROM ingredientes WHERE id=:id");
            $ing->execute(['id' => $id]);
            $row = $ing->fetch();
            if (!$row) jsonResponse(['ok' => false, 'mensaje' => 'Ingrediente no encontrado'], 404);

            $antes = (float)$row['stock_actual'];
            if ($tipo === 'entrada') {
                $despues = $antes + $cant;
            } elseif ($tipo === 'salida') {
                $despues = max(0, $antes - $cant);
            } else {
                // ajuste directo al valor ingresado
                $despues = $cant;
            }

            $db->prepare("UPDATE ingredientes SET stock_actual=:s WHERE id=:id")
               ->execute(['s' => $despues, 'id' => $id]);

            $db->prepare(
                "INSERT INTO ingrediente_movimientos (ingrediente_id, tipo, cantidad, stock_antes, stock_despues, motivo)
                 VALUES (:iid, :tipo, :c, :sa, :sd, :m)"
            )->execute([
                'iid' => $id, 'tipo' => $tipo,
                'c' => $tipo === 'ajuste' ? abs($despues - $antes) : $cant,
                'sa' => $antes, 'sd' => $despues,
                'm' => $motivo ?: null,
            ]);

            jsonResponse(['ok' => true, 'stock_actual' => $despues]);
        }

        // ── Guardar ingredientes de un producto ───────────────────────────────
        case 'guardar_producto_ingredientes': {
            $productoId   = (int)($body['producto_id'] ?? 0);
            $ingredientes = (array)($body['ingredientes'] ?? []);
            if ($productoId <= 0) jsonResponse(['ok' => false, 'mensaje' => 'producto_id inválido'], 400);

            $db->prepare("DELETE FROM producto_ingredientes WHERE producto_id=:pid")
               ->execute(['pid' => $productoId]);

            $stmt = $db->prepare(
                "INSERT IGNORE INTO producto_ingredientes (producto_id, ingrediente_id, cantidad)
                 VALUES (:pid, :iid, :c)"
            );
            foreach ($ingredientes as $item) {
                $iid  = (int)($item['ingrediente_id'] ?? 0);
                $cant = (float)($item['cantidad'] ?? 0);
                if ($iid > 0 && $cant > 0) {
                    $stmt->execute(['pid' => $productoId, 'iid' => $iid, 'c' => $cant]);
                }
            }
            jsonResponse(['ok' => true]);
        }

        default:
            jsonResponse(['ok' => false, 'mensaje' => 'Acción no soportada'], 400);
    }

} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'mensaje' => 'Error interno: ' . $e->getMessage()], 500);
}
