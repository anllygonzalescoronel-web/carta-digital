<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requerirRol(['admin']);

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $zonas = $db->query(
            'SELECT id, nombre, ancho, alto, orden, activa
             FROM zonas_mesas
             ORDER BY orden ASC, id ASC'
        )->fetchAll();

        $mesas = $db->query(
            'SELECT id, zona_id, nombre, capacidad, sillas, pos_x, pos_y, forma, activa, orden
             FROM mesas
             ORDER BY zona_id ASC, orden ASC, id ASC'
        )->fetchAll();

        $mesasPorZona = [];
        foreach ($mesas as $m) {
            $zid = (int)$m['zona_id'];
            if (!isset($mesasPorZona[$zid])) {
                $mesasPorZona[$zid] = [];
            }
            $mesasPorZona[$zid][] = [
                'id' => (int)$m['id'],
                'zona_id' => $zid,
                'nombre' => (string)$m['nombre'],
                'capacidad' => (int)$m['capacidad'],
                'sillas' => (int)$m['sillas'],
                'pos_x' => (int)$m['pos_x'],
                'pos_y' => (int)$m['pos_y'],
                'forma' => (string)$m['forma'],
                'activa' => (int)$m['activa'],
                'orden' => (int)$m['orden'],
            ];
        }

        $out = [];
        foreach ($zonas as $z) {
            $idZona = (int)$z['id'];
            $out[] = [
                'id' => $idZona,
                'nombre' => (string)$z['nombre'],
                'ancho' => (int)$z['ancho'],
                'alto' => (int)$z['alto'],
                'orden' => (int)$z['orden'],
                'activa' => (int)$z['activa'],
                'mesas' => $mesasPorZona[$idZona] ?? [],
            ];
        }

        jsonResponse(['ok' => true, 'zonas' => $out]);
    } catch (Throwable $e) {
        jsonResponse(['ok' => false, 'mensaje' => 'No se pudo cargar el plano.'], 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'mensaje' => 'Metodo no permitido'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    jsonResponse(['ok' => false, 'mensaje' => 'JSON invalido'], 400);
}

$accion = (string)($data['accion'] ?? '');

try {
    switch ($accion) {
        case 'zona_crear': {
            $nombre = trim((string)($data['nombre'] ?? ''));
            $ancho = max(800, min(2400, (int)($data['ancho'] ?? 1200)));
            $alto = max(500, min(1600, (int)($data['alto'] ?? 700)));
            if ($nombre === '') {
                jsonResponse(['ok' => false, 'mensaje' => 'Nombre de zona requerido.'], 400);
            }

            $orden = (int)$db->query('SELECT COALESCE(MAX(orden),0)+1 FROM zonas_mesas')->fetchColumn();
            $stmt = $db->prepare('INSERT INTO zonas_mesas (nombre, ancho, alto, orden, activa) VALUES (:n, :w, :h, :o, 1)');
            $stmt->execute(['n' => $nombre, 'w' => $ancho, 'h' => $alto, 'o' => $orden]);
            jsonResponse(['ok' => true]);
            break;
        }

        case 'zona_actualizar': {
            $id = (int)($data['id'] ?? 0);
            $nombre = trim((string)($data['nombre'] ?? ''));
            $ancho = max(800, min(2400, (int)($data['ancho'] ?? 1200)));
            $alto = max(500, min(1600, (int)($data['alto'] ?? 700)));
            $activa = ((int)($data['activa'] ?? 1)) === 1 ? 1 : 0;
            if ($id <= 0 || $nombre === '') {
                jsonResponse(['ok' => false, 'mensaje' => 'Datos de zona invalidos.'], 400);
            }
            $stmt = $db->prepare('UPDATE zonas_mesas SET nombre = :n, ancho = :w, alto = :h, activa = :a WHERE id = :id');
            $stmt->execute(['n' => $nombre, 'w' => $ancho, 'h' => $alto, 'a' => $activa, 'id' => $id]);
            jsonResponse(['ok' => true]);
            break;
        }

        case 'zona_eliminar': {
            $id = (int)($data['id'] ?? 0);
            if ($id <= 0) {
                jsonResponse(['ok' => false, 'mensaje' => 'Zona invalida.'], 400);
            }
            $stmt = $db->prepare('DELETE FROM zonas_mesas WHERE id = :id');
            $stmt->execute(['id' => $id]);
            jsonResponse(['ok' => true]);
            break;
        }

        case 'mesa_crear': {
            $zonaId = (int)($data['zona_id'] ?? 0);
            $nombre = trim((string)($data['nombre'] ?? ''));
            $capacidad = max(1, min(20, (int)($data['capacidad'] ?? 4)));
            $sillas = max(1, min(20, (int)($data['sillas'] ?? $capacidad)));
            $forma = (string)($data['forma'] ?? 'rectangular');
            $forma = in_array($forma, ['rectangular', 'redonda'], true) ? $forma : 'rectangular';
            $posX = max(0, (int)($data['pos_x'] ?? 80));
            $posY = max(0, (int)($data['pos_y'] ?? 80));

            if ($zonaId <= 0 || $nombre === '') {
                jsonResponse(['ok' => false, 'mensaje' => 'Datos de mesa invalidos.'], 400);
            }

            $ordenStmt = $db->prepare('SELECT COALESCE(MAX(orden),0)+1 FROM mesas WHERE zona_id = :zona');
            $ordenStmt->execute(['zona' => $zonaId]);
            $orden = (int)$ordenStmt->fetchColumn();

            $stmt = $db->prepare(
                'INSERT INTO mesas (zona_id, nombre, capacidad, sillas, pos_x, pos_y, forma, activa, orden)
                 VALUES (:zona, :nombre, :capacidad, :sillas, :x, :y, :forma, 1, :orden)'
            );
            $stmt->execute([
                'zona' => $zonaId,
                'nombre' => $nombre,
                'capacidad' => $capacidad,
                'sillas' => $sillas,
                'x' => $posX,
                'y' => $posY,
                'forma' => $forma,
                'orden' => $orden,
            ]);
            jsonResponse(['ok' => true]);
            break;
        }

        case 'mesa_crear_lote': {
            $zonaId = (int)($data['zona_id'] ?? 0);
            $cantidad = max(1, min(100, (int)($data['cantidad'] ?? 1)));
            $prefijo = trim((string)($data['prefijo'] ?? 'Mesa'));
            $inicio = max(1, (int)($data['inicio'] ?? 1));
            $capacidad = max(1, min(20, (int)($data['capacidad'] ?? 4)));
            $sillas = max(1, min(20, (int)($data['sillas'] ?? $capacidad)));
            $forma = (string)($data['forma'] ?? 'rectangular');
            $forma = in_array($forma, ['rectangular', 'redonda'], true) ? $forma : 'rectangular';

            if ($zonaId <= 0 || $prefijo === '') {
                jsonResponse(['ok' => false, 'mensaje' => 'Datos inválidos para crear mesas en lote.'], 400);
            }

            $zonaStmt = $db->prepare('SELECT ancho, alto FROM zonas_mesas WHERE id = :id LIMIT 1');
            $zonaStmt->execute(['id' => $zonaId]);
            $zona = $zonaStmt->fetch();
            if (!$zona) {
                jsonResponse(['ok' => false, 'mensaje' => 'La zona seleccionada no existe.'], 400);
            }

            $ordenStmt = $db->prepare('SELECT COALESCE(MAX(orden),0) FROM mesas WHERE zona_id = :zona');
            $ordenStmt->execute(['zona' => $zonaId]);
            $ordenBase = (int)$ordenStmt->fetchColumn();

            $anchoZona = max(800, (int)$zona['ancho']);
            $altoZona = max(500, (int)$zona['alto']);
            $separacionX = 150;
            $separacionY = 100;
            $inicioX = 40;
            $inicioY = 40;
            $columnas = max(1, (int)floor(max(1, $anchoZona - $inicioX - 40) / $separacionX));

            $stmt = $db->prepare(
                'INSERT INTO mesas (zona_id, nombre, capacidad, sillas, pos_x, pos_y, forma, activa, orden)
                 VALUES (:zona, :nombre, :capacidad, :sillas, :x, :y, :forma, 1, :orden)'
            );

            $creadas = 0;
            for ($i = 0; $i < $cantidad; $i++) {
                $numero = $inicio + $i;
                $columna = $i % $columnas;
                $fila = (int)floor($i / $columnas);
                $posX = $inicioX + ($columna * $separacionX);
                $posY = $inicioY + ($fila * $separacionY);

                if ($posY > ($altoZona - 90)) {
                    $posY = 40 + (($i % max(1, (int)floor(max(1, $altoZona - 80) / 90))) * 90);
                }

                $stmt->execute([
                    'zona' => $zonaId,
                    'nombre' => $prefijo . ' ' . $numero,
                    'capacidad' => $capacidad,
                    'sillas' => $sillas,
                    'x' => $posX,
                    'y' => $posY,
                    'forma' => $forma,
                    'orden' => $ordenBase + $i + 1,
                ]);
                $creadas++;
            }

            jsonResponse(['ok' => true, 'creadas' => $creadas]);
            break;
        }

        case 'mesa_actualizar': {
            $id = (int)($data['id'] ?? 0);
            $nombre = trim((string)($data['nombre'] ?? ''));
            $capacidad = max(1, min(20, (int)($data['capacidad'] ?? 4)));
            $sillas = max(1, min(20, (int)($data['sillas'] ?? $capacidad)));
            $forma = (string)($data['forma'] ?? 'rectangular');
            $forma = in_array($forma, ['rectangular', 'redonda'], true) ? $forma : 'rectangular';
            $activa = ((int)($data['activa'] ?? 1)) === 1 ? 1 : 0;

            if ($id <= 0 || $nombre === '') {
                jsonResponse(['ok' => false, 'mensaje' => 'Datos de mesa invalidos.'], 400);
            }

            $stmt = $db->prepare(
                'UPDATE mesas
                 SET nombre = :nombre, capacidad = :capacidad, sillas = :sillas, forma = :forma, activa = :activa
                 WHERE id = :id'
            );
            $stmt->execute([
                'nombre' => $nombre,
                'capacidad' => $capacidad,
                'sillas' => $sillas,
                'forma' => $forma,
                'activa' => $activa,
                'id' => $id,
            ]);
            jsonResponse(['ok' => true]);
            break;
        }

        case 'mesa_eliminar': {
            $id = (int)($data['id'] ?? 0);
            if ($id <= 0) {
                jsonResponse(['ok' => false, 'mensaje' => 'Mesa invalida.'], 400);
            }

            $stmtOcupada = $db->prepare(
                "SELECT COUNT(*)
                 FROM pedidos
                 WHERE tipo_entrega = 'comer_aqui'
                   AND mesa_id = :mesa_id
                   AND estado NOT IN ('entregado', 'cancelado')"
            );
            $stmtOcupada->execute(['mesa_id' => $id]);
            if ((int)$stmtOcupada->fetchColumn() > 0) {
                jsonResponse(['ok' => false, 'mensaje' => 'No puedes eliminar una mesa con pedidos activos.'], 400);
            }

            $stmt = $db->prepare('DELETE FROM mesas WHERE id = :id');
            $stmt->execute(['id' => $id]);
            jsonResponse(['ok' => true]);
            break;
        }

        case 'layout_guardar': {
            $zonaId = (int)($data['zona_id'] ?? 0);
            $mesas = $data['mesas'] ?? [];

            if ($zonaId <= 0 || !is_array($mesas)) {
                jsonResponse(['ok' => false, 'mensaje' => 'Datos de layout invalidos.'], 400);
            }

            $stmt = $db->prepare('UPDATE mesas SET pos_x = :x, pos_y = :y, orden = :orden WHERE id = :id AND zona_id = :zona_id');
            foreach ($mesas as $idx => $m) {
                $idMesa = (int)($m['id'] ?? 0);
                if ($idMesa <= 0) {
                    continue;
                }
                $posX = max(0, (int)($m['pos_x'] ?? 0));
                $posY = max(0, (int)($m['pos_y'] ?? 0));
                $orden = max(0, (int)($m['orden'] ?? $idx));
                $stmt->execute([
                    'x' => $posX,
                    'y' => $posY,
                    'orden' => $orden,
                    'id' => $idMesa,
                    'zona_id' => $zonaId,
                ]);
            }

            jsonResponse(['ok' => true]);
            break;
        }

        default:
            jsonResponse(['ok' => false, 'mensaje' => 'Accion no soportada.'], 400);
    }
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'mensaje' => 'Error en la operacion solicitada.'], 500);
}
