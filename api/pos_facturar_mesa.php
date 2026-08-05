<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/facturacion.php';
require_once __DIR__ . '/../includes/facturacion_nubefact_bridge.php';

requerirRol(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'mensaje' => 'Metodo no permitido'], 405);
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    jsonResponse(['ok' => false, 'mensaje' => 'JSON invalido'], 400);
}

$mesaId = (int)($body['mesa_id'] ?? 0);
$tipoEmision = strtolower(trim((string)($body['tipo_emision'] ?? 'ticket')));
$clienteNombre = trim((string)($body['cliente_nombre'] ?? 'Cliente POS'));
$clienteTelefono = trim((string)($body['cliente_telefono'] ?? '999999999'));
$tipoDocumento = strtolower(trim((string)($body['tipo_documento'] ?? 'dni')));
$numeroDocumento = normalizarNumeroDocumento((string)($body['numero_documento'] ?? ''));
$metodoPago = strtolower(trim((string)($body['metodo_pago'] ?? 'efectivo')));
$notas = trim((string)($body['notas'] ?? ''));

if ($mesaId <= 0) {
    jsonResponse(['ok' => false, 'mensaje' => 'Mesa invalida.'], 400);
}
if (!in_array($tipoEmision, ['ticket', 'boleta', 'factura'], true)) {
    jsonResponse(['ok' => false, 'mensaje' => 'Tipo de emisión invalido.'], 400);
}
if (!in_array($metodoPago, ['efectivo', 'tarjeta', 'yape_plin'], true)) {
    jsonResponse(['ok' => false, 'mensaje' => 'Método de pago invalido.'], 400);
}

$db = getDB();

function posUrlArchivoComprobante(?string $ruta): ?string {
    $ruta = trim((string)$ruta);
    if ($ruta === '') {
        return null;
    }
    if (preg_match('#^https?://#i', $ruta)) {
        return $ruta;
    }
    return '../' . ltrim($ruta, '/');
}

try {
    ensureFacturacionSchema($db);
    $driverActivo = strtolower(trim((string)cfg('facturacion_driver', 'native')));

    $turno = $db->query("SELECT id FROM cajas_turnos WHERE estado = 'abierta' ORDER BY id DESC LIMIT 1")->fetch();
    if (!$turno) {
        throw new RuntimeException('Debes abrir una caja para operar el POS.');
    }

    $stmtMesa = $db->prepare(
        'SELECT m.id, m.nombre AS mesa_nombre, z.nombre AS zona_nombre
         FROM mesas m
         INNER JOIN zonas_mesas z ON z.id = m.zona_id
         WHERE m.id = :id AND m.activa = 1 AND z.activa = 1
         LIMIT 1'
    );
    $stmtMesa->execute(['id' => $mesaId]);
    $mesa = $stmtMesa->fetch();
    if (!$mesa) {
        throw new RuntimeException('La mesa seleccionada no existe o está inactiva.');
    }

    $stmtPedidos = $db->prepare(
        "SELECT *
         FROM pedidos
         WHERE tipo_entrega = 'comer_aqui'
           AND mesa_id = :mesa_id
           AND estado NOT IN ('entregado', 'cancelado')
         ORDER BY creado_en ASC"
    );
    $stmtPedidos->execute(['mesa_id' => $mesaId]);
    $pedidosActivos = $stmtPedidos->fetchAll();
    if (empty($pedidosActivos)) {
        throw new RuntimeException('No hay consumos activos para facturar en esta mesa.');
    }

    $pedidoIds = array_map(static fn(array $p): int => (int)$p['id'], $pedidosActivos);
    $placeholders = implode(',', array_fill(0, count($pedidoIds), '?'));
    $stmtDetalle = $db->prepare(
        "SELECT producto_id, nombre_producto, precio_unitario, cantidad, subtotal, opciones_json
         FROM pedido_detalle
         WHERE pedido_id IN ($placeholders)
         ORDER BY id ASC"
    );
    $stmtDetalle->execute($pedidoIds);
    $detalle = $stmtDetalle->fetchAll();
    if (empty($detalle)) {
        throw new RuntimeException('No hay detalle para facturar en esta mesa.');
    }

    $subtotal = 0.0;
    foreach ($detalle as $row) {
        $subtotal += (float)$row['subtotal'];
    }

    $tipoComprobanteFinal = null;
    $estadoSunatPreview = null;
    $mensajeSunatPreview = null;
    if (in_array($tipoEmision, ['boleta', 'factura'], true) && ($driverActivo === 'nubefact' || facturacionConfigCompleta())) {
        $tipoComprobanteFinal = $tipoEmision;
        $errorDoc = validarDocumentoCliente($tipoComprobanteFinal, $tipoDocumento, $numeroDocumento);
        if ($errorDoc !== null) {
            throw new RuntimeException($errorDoc);
        }
    } elseif (in_array($tipoEmision, ['boleta', 'factura'], true)) {
        $mensajeSunatPreview = 'SUNAT no está disponible o no está configurado. Se emitió ticket de venta local.';
    }

    $db->beginTransaction();

    $codigo = generarCodigoPedido();
    $camposInsert = [
        'codigo', 'cliente_nombre', 'cliente_telefono',
        'tipo_comprobante', 'tipo_documento', 'numero_documento', 'tipo_entrega', 'direccion', 'referencia',
        'metodo_pago', 'estado', 'subtotal', 'costo_delivery', 'total', 'notas', 'culqi_charge_id'
    ];
    $placeholdersInsert = [
        ':codigo', ':cliente_nombre', ':cliente_telefono',
        ':tipo_comprobante', ':tipo_documento', ':numero_documento', ':tipo_entrega', ':direccion', ':referencia',
        ':metodo_pago', ':estado', ':subtotal', ':costo_delivery', ':total', ':notas', ':culqi_charge_id'
    ];
    $paramsPedido = [
        'codigo' => $codigo,
        'cliente_nombre' => $clienteNombre !== '' ? $clienteNombre : 'Cliente POS',
        'cliente_telefono' => $clienteTelefono !== '' ? $clienteTelefono : '999999999',
        'tipo_comprobante' => $tipoComprobanteFinal,
        'tipo_documento' => $tipoComprobanteFinal ? $tipoDocumento : null,
        'numero_documento' => $tipoComprobanteFinal ? $numeroDocumento : null,
        'tipo_entrega' => 'comer_aqui',
        'direccion' => null,
        'referencia' => null,
        'metodo_pago' => $metodoPago,
        'estado' => 'entregado',
        'subtotal' => round($subtotal, 2),
        'costo_delivery' => 0,
        'total' => round($subtotal, 2),
        'notas' => $notas !== '' ? $notas : 'Liquidación POS de mesa',
        'culqi_charge_id' => null,
    ];

    $columnasPedido = [];
    $stmtColsPedido = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos'");
    $stmtColsPedido->execute();
    foreach ($stmtColsPedido->fetchAll() as $col) {
        $columnasPedido[$col['COLUMN_NAME']] = true;
    }

    if (isset($columnasPedido['mesa_id'])) {
        $camposInsert[] = 'mesa_id';
        $placeholdersInsert[] = ':mesa_id';
        $paramsPedido['mesa_id'] = $mesaId;
    }
    if (isset($columnasPedido['mesa_nombre'])) {
        $camposInsert[] = 'mesa_nombre';
        $placeholdersInsert[] = ':mesa_nombre';
        $paramsPedido['mesa_nombre'] = $mesa['mesa_nombre'];
    }
    if (isset($columnasPedido['zona_nombre'])) {
        $camposInsert[] = 'zona_nombre';
        $placeholdersInsert[] = ':zona_nombre';
        $paramsPedido['zona_nombre'] = $mesa['zona_nombre'];
    }
    if (isset($columnasPedido['caja_turno_id'])) {
        $camposInsert[] = 'caja_turno_id';
        $placeholdersInsert[] = ':caja_turno_id';
        $paramsPedido['caja_turno_id'] = (int)$turno['id'];
    }
    if (isset($columnasPedido['cliente_email'])) {
        $camposInsert[] = 'cliente_email';
        $placeholdersInsert[] = ':cliente_email';
        $paramsPedido['cliente_email'] = '';
    }
    if (isset($columnasPedido['cliente_dni'])) {
        $camposInsert[] = 'cliente_dni';
        $placeholdersInsert[] = ':cliente_dni';
        $paramsPedido['cliente_dni'] = $tipoDocumento === 'dni' ? $numeroDocumento : null;
    }

    $stmtPedido = $db->prepare('INSERT INTO pedidos (' . implode(', ', $camposInsert) . ') VALUES (' . implode(', ', $placeholdersInsert) . ')');
    $stmtPedido->execute($paramsPedido);
    $pedidoLiquidacionId = (int)$db->lastInsertId();

    $columnasDetalle = [];
    $stmtColsDetalle = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedido_detalle'");
    $stmtColsDetalle->execute();
    foreach ($stmtColsDetalle->fetchAll() as $colDet) {
        $columnasDetalle[$colDet['COLUMN_NAME']] = true;
    }

    if (isset($columnasDetalle['opciones_json'])) {
        $stmtInsDetalle = $db->prepare(
            'INSERT INTO pedido_detalle (pedido_id, producto_id, nombre_producto, precio_unitario, cantidad, subtotal, opciones_json)
             VALUES (:pedido_id, :producto_id, :nombre_producto, :precio_unitario, :cantidad, :subtotal, :opciones_json)'
        );
    } else {
        $stmtInsDetalle = $db->prepare(
            'INSERT INTO pedido_detalle (pedido_id, producto_id, nombre_producto, precio_unitario, cantidad, subtotal)
             VALUES (:pedido_id, :producto_id, :nombre_producto, :precio_unitario, :cantidad, :subtotal)'
        );
    }

    foreach ($detalle as $row) {
        $paramsDet = [
            'pedido_id' => $pedidoLiquidacionId,
            'producto_id' => (int)$row['producto_id'],
            'nombre_producto' => (string)$row['nombre_producto'],
            'precio_unitario' => (float)$row['precio_unitario'],
            'cantidad' => (int)$row['cantidad'],
            'subtotal' => (float)$row['subtotal'],
        ];
        if (isset($columnasDetalle['opciones_json'])) {
            $paramsDet['opciones_json'] = $row['opciones_json'] ?: null;
        }
        $stmtInsDetalle->execute($paramsDet);
    }

    $stmtMovCaja = $db->prepare(
        'INSERT INTO caja_movimientos (turno_id, tipo, concepto, monto, referencia_tipo, referencia_id)
         VALUES (:turno_id, :tipo, :concepto, :monto, :ref_tipo, :ref_id)'
    );
    $stmtMovCaja->execute([
        'turno_id' => (int)$turno['id'],
        'tipo' => 'venta',
        'concepto' => 'Liquidación mesa ' . $mesa['mesa_nombre'] . ' ' . $codigo,
        'monto' => round($subtotal, 2),
        'ref_tipo' => 'pedido',
        'ref_id' => $pedidoLiquidacionId,
    ]);

    $comprobante = null;
    $tipoEmitido = $tipoComprobanteFinal ? $tipoComprobanteFinal : 'ticket';
    if ($tipoComprobanteFinal !== null && $driverActivo !== 'nubefact') {
        $comprobante = registrarComprobanteElectronicoDesdePedido($db, $pedidoLiquidacionId);
        $estadoSunatPreview = $comprobante['estado_sunat'] ?? null;
        if ($estadoSunatPreview === 'pendiente_configuracion') {
            $tipoEmitido = 'ticket';
            $mensajeSunatPreview = 'SUNAT no está listo. Se generó ticket de venta local.';
        }
    }

    $stmtCerrar = $db->prepare(
        "UPDATE pedidos
         SET estado = 'cancelado', notas = CONCAT(IFNULL(notas, ''), ' | Facturado en ', :codigo)
         WHERE id = :id"
    );
    foreach ($pedidoIds as $pedidoIdOriginal) {
        $stmtCerrar->execute([
            'codigo' => $codigo,
            'id' => (int)$pedidoIdOriginal,
        ]);
    }

    // Disolver unión: quitar mesa_union_id de todas las mesas secundarias que apuntan a esta
    $db->prepare('UPDATE mesas SET mesa_union_id = NULL WHERE mesa_union_id = :mesa_id')
       ->execute(['mesa_id' => $mesaId]);

    $db->commit();

    $pdfUrl = null;
    if ($tipoComprobanteFinal !== null) {
        if ($driverActivo === 'nubefact') {
            $comprobante = emitirComprobanteNubefactUnificado($db, $pedidoLiquidacionId);
            if (!($comprobante['ok'] ?? false)) {
                $tipoEmitido = 'ticket';
                $nubefactError = $comprobante['error'] ?? 'No se pudo generar el comprobante en NubeFacT.';
                $mensajeSunatPreview = 'ERROR NUBEFACT: ' . $nubefactError;
            } else {
                $pdfUrl = !empty($comprobante['pdf']) ? (string)$comprobante['pdf'] : null;
            }

            $stmtCompFinal = $db->prepare('SELECT id, numero_comprobante, estado_sunat, sunat_descripcion, pdf_path FROM comprobantes_electronicos WHERE pedido_id = :pedido_id ORDER BY id DESC LIMIT 1');
            $stmtCompFinal->execute(['pedido_id' => $pedidoLiquidacionId]);
            $compRow = $stmtCompFinal->fetch();
            if ($compRow) {
                $comprobante = [
                    'id' => (int)$compRow['id'],
                    'numero_comprobante' => (string)$compRow['numero_comprobante'],
                    'estado_sunat' => (string)$compRow['estado_sunat'],
                ];
                $estadoSunatPreview = (string)$compRow['estado_sunat'];
                if ($pdfUrl === null) {
                    $pdfUrl = posUrlArchivoComprobante($compRow['pdf_path'] ?? null);
                }
            }
        } elseif ($comprobante && !empty($comprobante['id'])) {
            if (($comprobante['estado_sunat'] ?? '') === 'pendiente_envio') {
                try {
                    $db->beginTransaction();
                    $resultadoSunat = enviarComprobanteSunatNativo($db, (int)$comprobante['id']);
                    $db->commit();
                    $estadoSunatPreview = $resultadoSunat['estado'] ?? $estadoSunatPreview;
                } catch (Throwable $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                }
            }

            $pdfGen = facturacionRegenerarPdfComprobante($db, (int)$comprobante['id']);
            if (($pdfGen['ok'] ?? false) && !empty($pdfGen['pdf_path'])) {
                $pdfUrl = posUrlArchivoComprobante((string)$pdfGen['pdf_path']);
            }

            $stmtCompFinal = $db->prepare('SELECT id, numero_comprobante, estado_sunat, sunat_descripcion, pdf_path FROM comprobantes_electronicos WHERE id = :id LIMIT 1');
            $stmtCompFinal->execute(['id' => (int)$comprobante['id']]);
            $compRow = $stmtCompFinal->fetch();
            if ($compRow) {
                $comprobante = [
                    'id' => (int)$compRow['id'],
                    'numero_comprobante' => (string)$compRow['numero_comprobante'],
                    'estado_sunat' => (string)$compRow['estado_sunat'],
                ];
                $estadoSunatPreview = (string)$compRow['estado_sunat'];
                if ($pdfUrl === null) {
                    $pdfUrl = posUrlArchivoComprobante($compRow['pdf_path'] ?? null);
                }
            }
        }

        if ($tipoEmision !== 'ticket' && $tipoEmitido !== 'ticket' && $comprobante && !empty($comprobante['numero_comprobante'])) {
            $tipoEmitido = $tipoComprobanteFinal;
        }
    }

    $itemsPrint = [];
    foreach ($detalle as $row) {
        $opciones = [];
        if (!empty($row['opciones_json'])) {
            $decoded = json_decode((string)$row['opciones_json'], true);
            if (is_array($decoded)) {
                $opciones = $decoded;
            }
        }
        $itemsPrint[] = [
            'nombre_producto' => (string)$row['nombre_producto'],
            'cantidad' => (int)$row['cantidad'],
            'precio_unitario' => (float)$row['precio_unitario'],
            'subtotal' => (float)$row['subtotal'],
            'opciones' => $opciones,
        ];
    }

    jsonResponse([
        'ok' => true,
        'mensaje' => $mensajeSunatPreview ?: 'Mesa facturada correctamente.',
        'nubefact_error' => $nubefactError ?? null,
        'tipo_emitido' => $tipoEmitido,
        'pedido_id' => $pedidoLiquidacionId,
        'codigo' => $codigo,
        'comprobante' => $comprobante,
        'pdf_url' => $pdfUrl,
        'print' => [
            'mesa' => [
                'nombre' => (string)$mesa['mesa_nombre'],
                'zona' => (string)$mesa['zona_nombre'],
            ],
            'cliente_nombre' => $clienteNombre,
            'cliente_telefono' => $clienteTelefono,
            'tipo_documento' => $tipoDocumento,
            'numero_documento' => $numeroDocumento,
            'metodo_pago' => $metodoPago,
            'tipo_emitido' => $tipoEmitido,
            'numero_comprobante' => $comprobante['numero_comprobante'] ?? null,
            'estado_sunat' => $estadoSunatPreview,
            'items' => $itemsPrint,
            'total' => round($subtotal, 2),
            'fecha' => date('Y-m-d H:i:s'),
        ],
    ]);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(['ok' => false, 'mensaje' => $e->getMessage()], 400);
}