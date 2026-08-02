<?php
ini_set('display_errors', '0');
ob_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requerirRol(['admin', 'cocinero']);

$db = getDB();

// ── Auto-migración ────────────────────────────────────────────────────────────
$db->exec("
    CREATE TABLE IF NOT EXISTS estaciones_produccion (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        descripcion VARCHAR(255) DEFAULT NULL,
        color VARCHAR(20) NOT NULL DEFAULT '#0f172a',
        icono VARCHAR(60) NOT NULL DEFAULT 'ti-chef-hat',
        orden INT NOT NULL DEFAULT 0,
        activa TINYINT(1) NOT NULL DEFAULT 1,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB
");
$db->exec("
    CREATE TABLE IF NOT EXISTS estacion_categorias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        estacion_id INT NOT NULL,
        categoria_id INT NOT NULL,
        UNIQUE KEY uq_estacion_categoria (estacion_id, categoria_id),
        CONSTRAINT fk_estacion_cat_estacion FOREIGN KEY (estacion_id)
            REFERENCES estaciones_produccion(id) ON DELETE CASCADE,
        CONSTRAINT fk_estacion_cat_categoria FOREIGN KEY (categoria_id)
            REFERENCES categorias(id) ON DELETE CASCADE
    ) ENGINE=InnoDB
");
$db->exec("
    CREATE TABLE IF NOT EXISTS estacion_usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        estacion_id INT NOT NULL,
        usuario_id INT NOT NULL,
        UNIQUE KEY uq_estacion_usuario (estacion_id, usuario_id),
        CONSTRAINT fk_estacion_usr_estacion FOREIGN KEY (estacion_id)
            REFERENCES estaciones_produccion(id) ON DELETE CASCADE,
        CONSTRAINT fk_estacion_usr_usuario FOREIGN KEY (usuario_id)
            REFERENCES admin_usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB
");

// ── GET: Listar estaciones con sus categorías y usuarios ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $esAdmin = obtenerRolActual() === 'admin';
        $usuarioId = (int)($_SESSION['admin_id'] ?? 0);

        // Todas las estaciones o solo las del usuario actual (cocinero)
        if ($esAdmin) {
            $estaciones = $db->query(
                "SELECT * FROM estaciones_produccion ORDER BY orden ASC, id ASC"
            )->fetchAll();
        } else {
            $stmt = $db->prepare(
                "SELECT ep.* FROM estaciones_produccion ep
                 INNER JOIN estacion_usuarios eu ON eu.estacion_id = ep.id
                 WHERE eu.usuario_id = :uid AND ep.activa = 1
                 ORDER BY ep.orden ASC, ep.id ASC"
            );
            $stmt->execute(['uid' => $usuarioId]);
            $estaciones = $stmt->fetchAll();
        }

        // Categorías por estación
        $catRows = $db->query(
            "SELECT ec.estacion_id, c.id AS cat_id, c.nombre AS cat_nombre
             FROM estacion_categorias ec
             INNER JOIN categorias c ON c.id = ec.categoria_id"
        )->fetchAll();
        $catPorEstacion = [];
        foreach ($catRows as $r) {
            $catPorEstacion[(int)$r['estacion_id']][] = [
                'id' => (int)$r['cat_id'],
                'nombre' => $r['cat_nombre'],
            ];
        }

        // Usuarios por estación (solo admin necesita esto)
        $usuariosPorEstacion = [];
        if ($esAdmin) {
            $usrRows = $db->query(
                "SELECT eu.estacion_id, u.id AS usr_id, u.nombre AS usr_nombre, u.usuario
                 FROM estacion_usuarios eu
                 INNER JOIN admin_usuarios u ON u.id = eu.usuario_id"
            )->fetchAll();
            foreach ($usrRows as $r) {
                $usuariosPorEstacion[(int)$r['estacion_id']][] = [
                    'id' => (int)$r['usr_id'],
                    'nombre' => $r['usr_nombre'],
                    'usuario' => $r['usuario'],
                ];
            }
        }

        $out = [];
        foreach ($estaciones as $e) {
            $eid = (int)$e['id'];
            $out[] = [
                'id' => $eid,
                'nombre' => $e['nombre'],
                'descripcion' => $e['descripcion'] ?? '',
                'color' => $e['color'],
                'icono' => $e['icono'],
                'orden' => (int)$e['orden'],
                'activa' => (int)$e['activa'],
                'categorias' => $catPorEstacion[$eid] ?? [],
                'usuarios' => $usuariosPorEstacion[$eid] ?? [],
            ];
        }

        // Datos adicionales para el panel de gestión
        $todasCategorias = $esAdmin
            ? $db->query("SELECT id, nombre FROM categorias WHERE activo = 1 ORDER BY orden ASC, nombre ASC")->fetchAll()
            : [];
        $todosUsuarios = $esAdmin
            ? $db->query("SELECT id, nombre, usuario, rol FROM admin_usuarios WHERE activo = 1 ORDER BY nombre ASC")->fetchAll()
            : [];

        jsonResponse([
            'ok' => true,
            'estaciones' => $out,
            'todas_categorias' => $todasCategorias,
            'todos_usuarios' => $todosUsuarios,
        ]);
    } catch (Throwable $e) {
        jsonResponse(['ok' => false, 'mensaje' => 'Error al cargar estaciones.'], 500);
    }
}

// ── POST: Acciones CRUD (solo admin) ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'mensaje' => 'Método no permitido'], 405);
}

requerirRol(['admin']);

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    jsonResponse(['ok' => false, 'mensaje' => 'JSON inválido'], 400);
}

$accion = (string)($body['accion'] ?? '');

try {
    switch ($accion) {
        // ── Crear estación ────────────────────────────────────────────────────
        case 'crear': {
            $nombre = trim((string)($body['nombre'] ?? ''));
            if ($nombre === '') {
                jsonResponse(['ok' => false, 'mensaje' => 'El nombre es obligatorio.'], 400);
            }
            $color = preg_match('/^#[0-9a-fA-F]{3,6}$/', (string)($body['color'] ?? ''))
                ? (string)$body['color']
                : '#0f172a';
            $icono = trim((string)($body['icono'] ?? 'ti-chef-hat'));
            $descripcion = trim((string)($body['descripcion'] ?? ''));
            $orden = (int)$db->query("SELECT COALESCE(MAX(orden),0)+1 FROM estaciones_produccion")->fetchColumn();

            $stmt = $db->prepare(
                "INSERT INTO estaciones_produccion (nombre, descripcion, color, icono, orden, activa)
                 VALUES (:nombre, :desc, :color, :icono, :orden, 1)"
            );
            $stmt->execute([
                'nombre' => $nombre,
                'desc' => $descripcion !== '' ? $descripcion : null,
                'color' => $color,
                'icono' => $icono,
                'orden' => $orden,
            ]);
            $estacionId = (int)$db->lastInsertId();

            _sincronizarCategorias($db, $estacionId, (array)($body['categorias'] ?? []));
            _sincronizarUsuarios($db, $estacionId, (array)($body['usuarios'] ?? []));

            jsonResponse(['ok' => true, 'id' => $estacionId]);
            break;
        }

        // ── Actualizar estación ───────────────────────────────────────────────
        case 'actualizar': {
            $id = (int)($body['id'] ?? 0);
            if ($id <= 0) {
                jsonResponse(['ok' => false, 'mensaje' => 'ID inválido.'], 400);
            }
            $nombre = trim((string)($body['nombre'] ?? ''));
            if ($nombre === '') {
                jsonResponse(['ok' => false, 'mensaje' => 'El nombre es obligatorio.'], 400);
            }
            $color = preg_match('/^#[0-9a-fA-F]{3,6}$/', (string)($body['color'] ?? ''))
                ? (string)$body['color']
                : '#0f172a';
            $icono = trim((string)($body['icono'] ?? 'ti-chef-hat'));
            $descripcion = trim((string)($body['descripcion'] ?? ''));
            $activa = ((int)($body['activa'] ?? 1)) === 1 ? 1 : 0;

            $stmt = $db->prepare(
                "UPDATE estaciones_produccion
                 SET nombre = :nombre, descripcion = :desc, color = :color, icono = :icono, activa = :activa
                 WHERE id = :id"
            );
            $stmt->execute([
                'nombre' => $nombre,
                'desc' => $descripcion !== '' ? $descripcion : null,
                'color' => $color,
                'icono' => $icono,
                'activa' => $activa,
                'id' => $id,
            ]);

            _sincronizarCategorias($db, $id, (array)($body['categorias'] ?? []));
            _sincronizarUsuarios($db, $id, (array)($body['usuarios'] ?? []));

            jsonResponse(['ok' => true]);
            break;
        }

        // ── Eliminar estación ─────────────────────────────────────────────────
        case 'eliminar': {
            $id = (int)($body['id'] ?? 0);
            if ($id <= 0) {
                jsonResponse(['ok' => false, 'mensaje' => 'ID inválido.'], 400);
            }
            $db->prepare("DELETE FROM estaciones_produccion WHERE id = :id")->execute(['id' => $id]);
            jsonResponse(['ok' => true]);
            break;
        }

        default:
            jsonResponse(['ok' => false, 'mensaje' => 'Acción no soportada.'], 400);
    }
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'mensaje' => 'Error en la operación.'], 500);
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function _sincronizarCategorias(PDO $db, int $estacionId, array $ids): void {
    $db->prepare("DELETE FROM estacion_categorias WHERE estacion_id = :id")->execute(['id' => $estacionId]);
    if (empty($ids)) {
        return;
    }
    $stmt = $db->prepare(
        "INSERT IGNORE INTO estacion_categorias (estacion_id, categoria_id) VALUES (:eid, :cid)"
    );
    foreach ($ids as $cid) {
        $cid = (int)$cid;
        if ($cid > 0) {
            $stmt->execute(['eid' => $estacionId, 'cid' => $cid]);
        }
    }
}

function _sincronizarUsuarios(PDO $db, int $estacionId, array $ids): void {
    $db->prepare("DELETE FROM estacion_usuarios WHERE estacion_id = :id")->execute(['id' => $estacionId]);
    if (empty($ids)) {
        return;
    }
    $stmt = $db->prepare(
        "INSERT IGNORE INTO estacion_usuarios (estacion_id, usuario_id) VALUES (:eid, :uid)"
    );
    foreach ($ids as $uid) {
        $uid = (int)$uid;
        if ($uid > 0) {
            $stmt->execute(['eid' => $estacionId, 'uid' => $uid]);
        }
    }
}
