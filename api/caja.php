<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requerirRol(['admin']);

$db = getDB();

function cajaTurnoAbierto(PDO $db): ?array {
    $stmt = $db->query("SELECT * FROM cajas_turnos WHERE estado = 'abierta' ORDER BY id DESC LIMIT 1");
    $turno = $stmt->fetch();
    return $turno ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $turno = cajaTurnoAbierto($db);
        $resumen = ['ingresos' => 0.0, 'egresos' => 0.0, 'ventas' => 0.0, 'saldo' => 0.0];
        $movs = [];

        if ($turno) {
            $stmtMov = $db->prepare(
                'SELECT id, tipo, concepto, monto, referencia_tipo, referencia_id, creado_en
                 FROM caja_movimientos
                 WHERE turno_id = :turno
                 ORDER BY id DESC
                 LIMIT 200'
            );
            $stmtMov->execute(['turno' => $turno['id']]);
            $movs = $stmtMov->fetchAll();

            foreach ($movs as $m) {
                $monto = (float)$m['monto'];
                if ($m['tipo'] === 'ingreso') $resumen['ingresos'] += $monto;
                elseif ($m['tipo'] === 'egreso') $resumen['egresos'] += $monto;
                elseif ($m['tipo'] === 'venta') $resumen['ventas'] += $monto;
            }

            // Fallback: en algunos flujos la venta queda en pedidos.caja_turno_id
            // pero no se inserta movimiento tipo "venta".
            if ($resumen['ventas'] <= 0.0) {
                $stmtVentasTurno = $db->prepare(
                    "SELECT COALESCE(SUM(total), 0) AS total_ventas
                     FROM pedidos
                     WHERE caja_turno_id = :turno
                       AND estado != 'cancelado'"
                );
                $stmtVentasTurno->execute(['turno' => (int)$turno['id']]);
                $filaVentasTurno = $stmtVentasTurno->fetch();
                $resumen['ventas'] = (float)($filaVentasTurno['total_ventas'] ?? 0);
            }

            $resumen['saldo'] = (float)$turno['monto_apertura'] + $resumen['ingresos'] + $resumen['ventas'] - $resumen['egresos'];
        }

        // Historial de turnos cerrados con totales
        $stmtHist = $db->query(
            "SELECT t.*,
                    COALESCE(SUM(CASE WHEN m.tipo='venta'    THEN m.monto ELSE 0 END), 0) AS total_ventas,
                    COALESCE(SUM(CASE WHEN m.tipo='ingreso'  THEN m.monto ELSE 0 END), 0) AS total_ingresos,
                    COALESCE(SUM(CASE WHEN m.tipo='egreso'   THEN m.monto ELSE 0 END), 0) AS total_egresos
             FROM cajas_turnos t
             LEFT JOIN caja_movimientos m ON m.turno_id = t.id
             WHERE t.estado = 'cerrada'
             GROUP BY t.id
             ORDER BY t.id DESC
             LIMIT 30"
        );
        $historial = $stmtHist->fetchAll();

        jsonResponse([
            'ok' => true,
            'turno' => $turno,
            'resumen' => $resumen,
            'movimientos' => $movs,
            'historial' => $historial,
        ]);
    } catch (Throwable $e) {
        jsonResponse(['ok' => false, 'mensaje' => 'No se pudo cargar caja.'], 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'mensaje' => 'Metodo no permitido'], 405);
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    jsonResponse(['ok' => false, 'mensaje' => 'JSON invalido'], 400);
}

$accion = (string)($body['accion'] ?? '');

try {
    if ($accion === 'abrir') {
        $monto = round((float)($body['monto_apertura'] ?? 0), 2);
        $obs = trim((string)($body['observacion'] ?? ''));

        if ($monto < 0) {
            jsonResponse(['ok' => false, 'mensaje' => 'Monto de apertura invalido.'], 400);
        }

        $turnoAbierto = cajaTurnoAbierto($db);
        if ($turnoAbierto) {
            jsonResponse(['ok' => false, 'mensaje' => 'Ya existe una caja abierta.'], 400);
        }

        $stmt = $db->prepare(
            'INSERT INTO cajas_turnos (usuario_id, usuario_nombre, estado, monto_apertura, observacion_apertura, abierta_en)
             VALUES (:uid, :uname, :estado, :monto, :obs, NOW())'
        );
        $stmt->execute([
            'uid' => (int)($_SESSION['admin_id'] ?? 0),
            'uname' => (string)($_SESSION['admin_nombre'] ?? 'Admin'),
            'estado' => 'abierta',
            'monto' => $monto,
            'obs' => $obs !== '' ? $obs : null,
        ]);

        jsonResponse(['ok' => true, 'mensaje' => 'Caja abierta correctamente.']);
    }

    if ($accion === 'cerrar') {
        $montoCierre = round((float)($body['monto_cierre'] ?? 0), 2);
        $obs = trim((string)($body['observacion'] ?? ''));

        $turnoAbierto = cajaTurnoAbierto($db);
        if (!$turnoAbierto) {
            jsonResponse(['ok' => false, 'mensaje' => 'No hay caja abierta para cerrar.'], 400);
        }

        $stmt = $db->prepare(
            "UPDATE cajas_turnos
             SET estado = 'cerrada', monto_cierre = :monto, observacion_cierre = :obs, cerrada_en = NOW()
             WHERE id = :id"
        );
        $stmt->execute([
            'monto' => $montoCierre,
            'obs' => $obs !== '' ? $obs : null,
            'id' => (int)$turnoAbierto['id'],
        ]);

        jsonResponse(['ok' => true, 'mensaje' => 'Caja cerrada correctamente.']);
    }

    if ($accion === 'movimiento') {
        $tipo = (string)($body['tipo'] ?? '');
        $concepto = trim((string)($body['concepto'] ?? ''));
        $monto = round((float)($body['monto'] ?? 0), 2);

        if (!in_array($tipo, ['ingreso', 'egreso'], true)) {
            jsonResponse(['ok' => false, 'mensaje' => 'Tipo de movimiento invalido.'], 400);
        }
        if ($concepto === '') {
            jsonResponse(['ok' => false, 'mensaje' => 'Concepto requerido.'], 400);
        }
        if ($monto <= 0) {
            jsonResponse(['ok' => false, 'mensaje' => 'Monto invalido.'], 400);
        }

        $turnoAbierto = cajaTurnoAbierto($db);
        if (!$turnoAbierto) {
            jsonResponse(['ok' => false, 'mensaje' => 'Debes abrir caja primero.'], 400);
        }

        $stmt = $db->prepare(
            'INSERT INTO caja_movimientos (turno_id, tipo, concepto, monto, referencia_tipo, referencia_id)
             VALUES (:turno, :tipo, :concepto, :monto, NULL, NULL)'
        );
        $stmt->execute([
            'turno' => (int)$turnoAbierto['id'],
            'tipo' => $tipo,
            'concepto' => $concepto,
            'monto' => $monto,
        ]);

        jsonResponse(['ok' => true, 'mensaje' => 'Movimiento registrado.']);
    }

    jsonResponse(['ok' => false, 'mensaje' => 'Accion no valida.'], 400);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'mensaje' => 'Error en la operacion de caja.'], 500);
}
