<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/culqi.php';
require_once __DIR__ . '/../includes/facturacion.php';
require_once __DIR__ . '/../includes/facturacion_nubefact_bridge.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/cliente_auth.php';

// Migrar clave legada 'facturacion_driver_activo' → 'facturacion_driver' si aún existe
try {
    $stmtLegado = getDB()->prepare("SELECT valor FROM configuracion WHERE clave = 'facturacion_driver_activo' LIMIT 1");
    $stmtLegado->execute();
    $valorLegado = $stmtLegado->fetchColumn();
    if ($valorLegado !== false && $valorLegado !== '') {
        $actual = getDB()->prepare("SELECT valor FROM configuracion WHERE clave = 'facturacion_driver' LIMIT 1");
        $actual->execute();
        $valorActual = $actual->fetchColumn();
        // Solo migrar si la clave principal está vacía o en default
        if ($valorActual === false || $valorActual === '' || $valorActual === 'native') {
            guardarConfig('facturacion_driver', $valorLegado);
        }
        // Limpiar clave legada para evitar confusión futura
        getDB()->exec("DELETE FROM configuracion WHERE clave = 'facturacion_driver_activo'");
    }
} catch (Throwable $e) { /* Silencioso: no debe interrumpir la compra */ }

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'mensaje' => 'Método no permitido'], 405);
}

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    jsonResponse(['ok' => false, 'mensaje' => 'Datos inválidos'], 400);
}

$items          = $body['items'] ?? [];
$clienteNombre  = trim($body['cliente_nombre'] ?? '');
$clienteTelefono = trim($body['cliente_telefono'] ?? '');
$tipoEntrega    = $body['tipo_entrega'] ?? '';
$direccion      = trim($body['direccion'] ?? '');
$referencia     = trim($body['referencia'] ?? '');
$mesaId         = (int)($body['mesa_id'] ?? 0);
$metodoPago     = $body['metodo_pago'] ?? '';
$notas          = trim($body['notas'] ?? '');
$culqiToken     = $body['culqi_token'] ?? null;
$clienteEmail   = trim($body['cliente_email'] ?? '');
$tipoComprobante = strtolower(trim((string)($body['tipo_comprobante'] ?? 'boleta')));
$tipoDocumento   = strtolower(trim((string)($body['tipo_documento'] ?? 'dni')));
$numeroDocumento = normalizarNumeroDocumento((string)($body['numero_documento'] ?? ''));
$origen          = strtolower(trim((string)($body['origen'] ?? 'web')));
$clienteSesionId = clienteActualId();
$clienteSesion = $clienteSesionId ? obtenerClienteWebPorId($clienteSesionId) : null;

if ($origen === 'pos') {
    requerirRol(['admin']);
}

// ---------- Validaciones básicas ----------
if (empty($items) || !is_array($items)) {
    jsonResponse(['ok' => false, 'mensaje' => 'Tu carrito está vacío.'], 400);
}
if ($clienteNombre === '' || mb_strlen($clienteNombre) < 2) {
    jsonResponse(['ok' => false, 'mensaje' => 'Ingresa tu nombre.'], 400);
}
if (!preg_match('/^[0-9+ ]{6,20}$/', $clienteTelefono)) {
    jsonResponse(['ok' => false, 'mensaje' => 'Ingresa un teléfono válido.'], 400);
}
if (!in_array($tipoEntrega, ['recojo', 'delivery', 'comer_aqui'], true)) {
    jsonResponse(['ok' => false, 'mensaje' => 'Selecciona recojo, delivery o comer aqui.'], 400);
}
if ($tipoEntrega === 'delivery' && mb_strlen($direccion) < 5) {
    jsonResponse(['ok' => false, 'mensaje' => 'Ingresa tu dirección de entrega.'], 400);
}
if ($tipoEntrega === 'comer_aqui' && $mesaId <= 0) {
    jsonResponse(['ok' => false, 'mensaje' => 'Selecciona una mesa para comer aqui.'], 400);
}
if (!in_array($metodoPago, ['efectivo', 'yape_plin', 'tarjeta'], true)) {
    jsonResponse(['ok' => false, 'mensaje' => 'Selecciona un método de pago.'], 400);
}
if ($origen !== 'pos' && in_array($metodoPago, ['tarjeta', 'yape_plin'], true) && empty($culqiToken)) {
    jsonResponse(['ok' => false, 'mensaje' => 'Falta el token de pago de Culqi.'], 400);
}

$errorDocumento = validarDocumentoCliente($tipoComprobante, $tipoDocumento, $numeroDocumento);
if ($errorDocumento !== null) {
    jsonResponse(['ok' => false, 'mensaje' => $errorDocumento], 400);
}

$db = getDB();

try {
    ensureFacturacionSchema($db);
    $db->beginTransaction();

    $cajaTurnoId = null;
    if ($origen === 'pos') {
        $stmtCaja = $db->query("SELECT id FROM cajas_turnos WHERE estado = 'abierta' ORDER BY id DESC LIMIT 1");
        $turno = $stmtCaja->fetch();
        if (!$turno) {
            throw new RuntimeException('Debes abrir una caja para registrar ventas POS.');
        }
        $cajaTurnoId = (int)$turno['id'];
    }

    $mesaNombrePedido = null;
    $zonaNombrePedido = null;

    if ($tipoEntrega === 'comer_aqui') {
        if (cfg('comer_aqui_activo', '1') !== '1') {
            throw new RuntimeException('La opción comer aqui no está disponible en este momento.');
        }

        $stmtMesa = $db->prepare(
            'SELECT m.id, m.nombre AS mesa_nombre, z.nombre AS zona_nombre
             FROM mesas m
             INNER JOIN zonas_mesas z ON z.id = m.zona_id
             WHERE m.id = :id AND m.activa = 1 AND z.activa = 1
             LIMIT 1'
        );
        $stmtMesa->execute(['id' => $mesaId]);
        $mesaData = $stmtMesa->fetch();
        if (!$mesaData) {
            throw new RuntimeException('La mesa seleccionada no está disponible.');
        }

        // En checkout web evitamos doble asignación de mesa; en POS sí permitimos seguir sumando consumos.
        if ($origen !== 'pos') {
            $stmtOcupada = $db->prepare(
                "SELECT COUNT(*)
                 FROM pedidos
                 WHERE tipo_entrega = 'comer_aqui'
                   AND mesa_id = :mesa_id
                   AND estado NOT IN ('entregado', 'cancelado')"
            );
            $stmtOcupada->execute(['mesa_id' => $mesaId]);
            if ((int)$stmtOcupada->fetchColumn() > 0) {
                throw new RuntimeException('Esa mesa ya está ocupada por otro pedido activo.');
            }
        }

        $mesaNombrePedido = (string)$mesaData['mesa_nombre'];
        $zonaNombrePedido = (string)$mesaData['zona_nombre'];
    }

    // ---------- Recalcular precios reales desde la BD (nunca confiar en el frontend) ----------
    $detalle = [];
    $subtotal = 0.0;

    $stmtProd = $db->prepare('SELECT id, nombre, precio, precio_oferta, disponible FROM productos WHERE id = :id LIMIT 1');
    $soportaOpciones = true;
    $stmtOp = null;
    $stmtGruposReq = null;
    try {
        $stmtOp = $db->prepare(
            'SELECT o.id, o.nombre, o.precio_extra, o.disponible, g.id AS grupo_id, g.nombre AS grupo_nombre, g.producto_id
             FROM producto_opciones o
             INNER JOIN producto_grupos g ON g.id = o.grupo_id
             WHERE o.id = :opcion_id AND g.id = :grupo_id
             LIMIT 1'
        );
        $stmtGruposReq = $db->prepare(
            'SELECT id, nombre, tipo, requerido, min_opciones, max_opciones
             FROM producto_grupos
             WHERE producto_id = :producto_id
             ORDER BY orden ASC, id ASC'
        );
    } catch (Throwable $e) {
        $soportaOpciones = false;
    }

    foreach ($items as $item) {
        $productoId = (int)($item['id'] ?? 0);
        $cantidad   = (int)($item['cantidad'] ?? 0);

        if ($productoId <= 0 || $cantidad <= 0 || $cantidad > 50) {
            throw new RuntimeException('Uno de los productos del carrito no es válido.');
        }

        $stmtProd->execute(['id' => $productoId]);
        $producto = $stmtProd->fetch();

        if (!$producto) {
            throw new RuntimeException('Un producto de tu carrito ya no existe.');
        }
        if ((int)$producto['disponible'] !== 1) {
            throw new RuntimeException('"' . $producto['nombre'] . '" ya no está disponible.');
        }

        $precioBase = $producto['precio_oferta'] !== null && $producto['precio_oferta'] > 0
            ? (float)$producto['precio_oferta']
            : (float)$producto['precio'];

        $opcionesItem = $item['opciones'] ?? [];
        $opcionesNormalizadas = [];
        $extraUnitario = 0.0;
        $conteoPorGrupo = [];

        if ($soportaOpciones && is_array($opcionesItem) && !empty($opcionesItem)) {
            foreach ($opcionesItem as $opRaw) {
                $grupoId = (int)($opRaw['grupo_id'] ?? 0);
                $opcionId = (int)($opRaw['opcion_id'] ?? 0);
                if ($grupoId <= 0 || $opcionId <= 0) {
                    throw new RuntimeException('Se detectó una opción inválida en uno de los productos.');
                }

                $stmtOp->execute(['opcion_id' => $opcionId, 'grupo_id' => $grupoId]);
                $opcionDb = $stmtOp->fetch();
                if (!$opcionDb || (int)$opcionDb['producto_id'] !== (int)$producto['id']) {
                    throw new RuntimeException('Una opción seleccionada no pertenece al producto "' . $producto['nombre'] . '".');
                }
                if ((int)$opcionDb['disponible'] !== 1) {
                    throw new RuntimeException('La opción "' . $opcionDb['nombre'] . '" no está disponible.');
                }

                $precioExtra = (float)$opcionDb['precio_extra'];
                $extraUnitario += $precioExtra;
                $conteoPorGrupo[$grupoId] = ($conteoPorGrupo[$grupoId] ?? 0) + 1;
                $opcionesNormalizadas[] = [
                    'grupo_id' => (int)$opcionDb['grupo_id'],
                    'grupo_nombre' => (string)$opcionDb['grupo_nombre'],
                    'opcion_id' => (int)$opcionDb['id'],
                    'opcion_nombre' => (string)$opcionDb['nombre'],
                    'precio_extra' => $precioExtra,
                ];
            }
        }

        // Validar reglas de grupos (requeridos, min y max)
        if ($soportaOpciones && $stmtGruposReq) {
            try {
                $stmtGruposReq->execute(['producto_id' => (int)$producto['id']]);
                $gruposProducto = $stmtGruposReq->fetchAll();
            } catch (Throwable $e) {
                $gruposProducto = [];
            }
        } else {
            $gruposProducto = [];
        }

        foreach ($gruposProducto as $g) {
            $gid = (int)$g['id'];
            $countSel = (int)($conteoPorGrupo[$gid] ?? 0);
            $esRequerido = (int)$g['requerido'] === 1;
            $minReq = max(0, (int)$g['min_opciones']);
            $maxReq = max(1, (int)$g['max_opciones']);
            $minFinal = $esRequerido ? max(1, $minReq) : $minReq;

            if ($countSel < $minFinal) {
                throw new RuntimeException('Faltan opciones en el grupo "' . $g['nombre'] . '" para "' . $producto['nombre'] . '".');
            }
            if ($countSel > $maxReq) {
                throw new RuntimeException('Seleccionaste demasiadas opciones en el grupo "' . $g['nombre'] . '" para "' . $producto['nombre'] . '".');
            }
            if (($g['tipo'] ?? '') === 'radio' && $countSel > 1) {
                throw new RuntimeException('Solo puedes elegir una opción en el grupo "' . $g['nombre'] . '".');
            }
        }

        $precioUnitario = round($precioBase + $extraUnitario, 2);

        $subtotalItem = round($precioUnitario * $cantidad, 2);
        $subtotal += $subtotalItem;

        $detalle[] = [
            'producto_id'      => $producto['id'],
            'nombre_producto'  => $producto['nombre'],
            'precio_unitario'  => $precioUnitario,
            'cantidad'         => $cantidad,
            'subtotal'         => $subtotalItem,
            'opciones_json'    => !empty($opcionesNormalizadas) ? json_encode($opcionesNormalizadas, JSON_UNESCAPED_UNICODE) : null,
            'opciones'         => $opcionesNormalizadas,
        ];
    }

    if (empty($detalle)) {
        throw new RuntimeException('Tu carrito está vacío.');
    }

    // ---------- Costo de delivery ----------
    $costoDelivery = 0.0;
    if ($tipoEntrega === 'delivery') {
        if (cfg('delivery_activo', '1') !== '1') {
            throw new RuntimeException('El delivery no está disponible en este momento.');
        }
        $costoDelivery = (float) cfg('costo_delivery', '0');
    } elseif ($tipoEntrega === 'recojo' && cfg('recojo_activo', '1') !== '1') {
        throw new RuntimeException('El recojo en local no está disponible en este momento.');
    } elseif ($tipoEntrega === 'comer_aqui' && cfg('comer_aqui_activo', '1') !== '1') {
        throw new RuntimeException('La opción comer aqui no está disponible en este momento.');
    }

    $total = round($subtotal + $costoDelivery, 2);
    $codigo = generarCodigoPedido();
    $estado = $origen === 'pos' ? 'pagado' : 'pendiente';
    $culqiChargeId = null;

    // ---------- Verificar método habilitado ----------
    $mapaActivo = [
        'efectivo'  => 'efectivo_activo',
        'yape_plin' => 'yape_plin_activo',
        'tarjeta'   => 'tarjeta_activo',
    ];
    if (cfg($mapaActivo[$metodoPago], '1') !== '1') {
        throw new RuntimeException('Ese método de pago no está disponible en este momento.');
    }

    // ---------- Procesar pago online con Culqi (tarjeta / yape) ----------
    if ($origen !== 'pos' && in_array($metodoPago, ['tarjeta', 'yape_plin'], true)) {
        $cargo = crearCargoCulqi(
            $culqiToken,
            $total,
            $clienteEmail,
            'Pedido ' . $codigo . ' - ' . cfg('nombre_negocio', 'Carta Digital'),
            $clienteNombre,
            $clienteTelefono
        );
        $culqiChargeId = $cargo['id'] ?? null;
        $estado = 'pagado';
    }

    // ---------- Guardar pedido ----------
    $columnasPedido = [];
    $stmtCols = $db->prepare(
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos'"
    );
    $stmtCols->execute();
    foreach ($stmtCols->fetchAll() as $col) {
        $columnasPedido[$col['COLUMN_NAME']] = true;
    }

    $camposInsert = [
        'codigo', 'cliente_nombre', 'cliente_telefono',
        'tipo_comprobante', 'tipo_documento', 'numero_documento', 'tipo_entrega', 'direccion', 'referencia',
        'metodo_pago', 'estado', 'subtotal', 'costo_delivery', 'total', 'notas', 'culqi_charge_id'
    ];
    $placeholders = [
        ':codigo', ':cliente_nombre', ':cliente_telefono',
        ':tipo_comprobante', ':tipo_documento', ':numero_documento', ':tipo_entrega', ':direccion', ':referencia',
        ':metodo_pago', ':estado', ':subtotal', ':costo_delivery', ':total', ':notas', ':culqi_charge_id'
    ];
    $paramsPedido = [
        'codigo' => $codigo,
        'cliente_nombre' => $clienteNombre,
        'cliente_telefono' => $clienteTelefono,
        'tipo_comprobante' => $tipoComprobante,
        'tipo_documento' => $tipoDocumento,
        'numero_documento' => $numeroDocumento,
        'tipo_entrega' => $tipoEntrega,
        'direccion' => $tipoEntrega === 'delivery' ? $direccion : null,
        'referencia' => $referencia ?: null,
        'metodo_pago' => $metodoPago,
        'estado' => $estado,
        'subtotal' => $subtotal,
        'costo_delivery' => $costoDelivery,
        'total' => $total,
        'notas' => $notas ?: null,
        'culqi_charge_id' => $culqiChargeId,
    ];

    if (isset($columnasPedido['cliente_email'])) {
        $camposInsert[] = 'cliente_email';
        $placeholders[] = ':cliente_email';
        $paramsPedido['cliente_email'] = $clienteSesion['email'] ?? $clienteEmail;
    }

    if (isset($columnasPedido['cliente_id'])) {
        $camposInsert[] = 'cliente_id';
        $placeholders[] = ':cliente_id';
        $paramsPedido['cliente_id'] = $clienteSesionId ?: null;
    }

    if (isset($columnasPedido['origen_cliente'])) {
        $camposInsert[] = 'origen_cliente';
        $placeholders[] = ':origen_cliente';
        $paramsPedido['origen_cliente'] = $clienteSesion
            ? (($clienteSesion['proveedor'] ?? 'local') === 'google' ? 'google' : 'cuenta')
            : 'anonimo';
    }

    if (isset($columnasPedido['cliente_dni'])) {
        $camposInsert[] = 'cliente_dni';
        $placeholders[] = ':cliente_dni';
        // cliente_dni se mantiene por compatibilidad con NubeFacT (generar_comprobante.php lo usa)
        $paramsPedido['cliente_dni'] = $tipoDocumento === 'dni' && $numeroDocumento !== '' ? $numeroDocumento : null;
    }

    if (isset($columnasPedido['mesa_id'])) {
        $camposInsert[] = 'mesa_id';
        $placeholders[] = ':mesa_id';
        $paramsPedido['mesa_id'] = $tipoEntrega === 'comer_aqui' ? $mesaId : null;
    }

    if (isset($columnasPedido['mesa_nombre'])) {
        $camposInsert[] = 'mesa_nombre';
        $placeholders[] = ':mesa_nombre';
        $paramsPedido['mesa_nombre'] = $tipoEntrega === 'comer_aqui' ? $mesaNombrePedido : null;
    }

    if (isset($columnasPedido['zona_nombre'])) {
        $camposInsert[] = 'zona_nombre';
        $placeholders[] = ':zona_nombre';
        $paramsPedido['zona_nombre'] = $tipoEntrega === 'comer_aqui' ? $zonaNombrePedido : null;
    }

    if (isset($columnasPedido['caja_turno_id'])) {
        $camposInsert[] = 'caja_turno_id';
        $placeholders[] = ':caja_turno_id';
        $paramsPedido['caja_turno_id'] = $cajaTurnoId;
    }

    $sqlPedido = 'INSERT INTO pedidos (' . implode(', ', $camposInsert) . ') VALUES (' . implode(', ', $placeholders) . ')';
    $stmtPedido = $db->prepare($sqlPedido);
    $stmtPedido->execute($paramsPedido);
    $pedidoId = $db->lastInsertId();

    if ($clienteSesionId) {
        vincularPedidosCliente((int)$clienteSesionId, $clienteSesion['email'] ?? $clienteEmail, $clienteSesion['telefono'] ?? $clienteTelefono, $clienteSesion['proveedor'] ?? 'cuenta');
    }

    $columnasDetalle = [];
    $stmtColsDetalle = $db->prepare(
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedido_detalle'"
    );
    $stmtColsDetalle->execute();
    foreach ($stmtColsDetalle->fetchAll() as $colDet) {
        $columnasDetalle[$colDet['COLUMN_NAME']] = true;
    }

    if (isset($columnasDetalle['opciones_json'])) {
        $stmtDetalle = $db->prepare(
            'INSERT INTO pedido_detalle (pedido_id, producto_id, nombre_producto, precio_unitario, cantidad, subtotal, opciones_json)
             VALUES (:pedido_id, :producto_id, :nombre_producto, :precio_unitario, :cantidad, :subtotal, :opciones_json)'
        );
    } else {
        $stmtDetalle = $db->prepare(
            'INSERT INTO pedido_detalle (pedido_id, producto_id, nombre_producto, precio_unitario, cantidad, subtotal)
             VALUES (:pedido_id, :producto_id, :nombre_producto, :precio_unitario, :cantidad, :subtotal)'
        );
    }
    foreach ($detalle as $d) {
        $paramsDetalle = [
            'pedido_id' => $pedidoId,
            'producto_id' => $d['producto_id'],
            'nombre_producto' => $d['nombre_producto'],
            'precio_unitario' => $d['precio_unitario'],
            'cantidad' => $d['cantidad'],
            'subtotal' => $d['subtotal'],
        ];
        if (isset($columnasDetalle['opciones_json'])) {
            $paramsDetalle['opciones_json'] = $d['opciones_json'];
        }
        $stmtDetalle->execute($paramsDetalle);
    }

    if ($cajaTurnoId !== null) {
        $stmtMovCaja = $db->prepare(
            'INSERT INTO caja_movimientos (turno_id, tipo, concepto, monto, referencia_tipo, referencia_id)
             VALUES (:turno_id, :tipo, :concepto, :monto, :ref_tipo, :ref_id)'
        );
        $stmtMovCaja->execute([
            'turno_id' => $cajaTurnoId,
            'tipo' => 'venta',
            'concepto' => 'Venta POS ' . $codigo,
            'monto' => $total,
            'ref_tipo' => 'pedido',
            'ref_id' => (int)$pedidoId,
        ]);
    }

    // ---------- Registrar el comprobante según el motor de facturación activo ----------
    $driverActivo = strtolower(trim((string) cfg('facturacion_driver', 'native')));
    $comprobante = null;

    if ($driverActivo !== 'nubefact') {
        // SUNAT Nativo: exactamente el mismo comportamiento de siempre.
        $comprobante = registrarComprobanteElectronicoDesdePedido($db, (int)$pedidoId);
    }

    $db->commit();

    if ($driverActivo === 'nubefact') {
        // NubeFacT: va DESPUÉS del commit a propósito. El pedido y el pago ya
        // quedaron guardados pase lo que pase con NubeFacT; si falla, no se
        // pierde el pedido y se puede reintentar luego.
        // Se emite para cualquier método de pago (efectivo incluido).
        $comprobante = emitirComprobanteNubefactUnificado($db, $pedidoId);
    } else {
        // SUNAT Nativo: si quedó listo para enviar, lo enviamos ya mismo.
        if ($comprobante && ($comprobante['estado_sunat'] ?? '') === 'pendiente_envio') {
            try {
                $db->beginTransaction();
                $resultadoSunat = enviarComprobanteSunatNativo($db, (int)$comprobante['id']);
                $db->commit();
                $comprobante['estado_sunat'] = $resultadoSunat['estado'] ?? $comprobante['estado_sunat'];
            } catch (Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
            }
        }
    }

    // ---------- Construir mensaje de WhatsApp ----------
    $lineas = [];
    $lineas[] = '🍗 *Nuevo pedido ' . $codigo . '*';
    $lineas[] = '';
    foreach ($detalle as $d) {
        $lineas[] = "• {$d['cantidad']}x {$d['nombre_producto']} - " . formatoPrecio($d['subtotal']);
        if (!empty($d['opciones'])) {
            foreach ($d['opciones'] as $op) {
                $extraTxt = ((float)$op['precio_extra'] > 0) ? (' (+' . formatoPrecio((float)$op['precio_extra']) . ')') : '';
                $lineas[] = '   - ' . $op['grupo_nombre'] . ': ' . $op['opcion_nombre'] . $extraTxt;
            }
        }
    }
    $lineas[] = '';
    $lineas[] = 'Subtotal: ' . formatoPrecio($subtotal);
    if ($costoDelivery > 0) {
        $lineas[] = 'Delivery: ' . formatoPrecio($costoDelivery);
    }
    $lineas[] = '*Total: ' . formatoPrecio($total) . '*';
    $lineas[] = '';
    $lineas[] = 'Cliente: ' . $clienteNombre;
    $lineas[] = 'Comprobante: ' . strtoupper($tipoComprobante) . ' - ' . strtoupper($tipoDocumento) . ' ' . $numeroDocumento;
    $lineas[] = 'Teléfono: ' . $clienteTelefono;
    if ($tipoEntrega === 'delivery') {
        $lineas[] = 'Entrega: Delivery 🛵';
    } elseif ($tipoEntrega === 'comer_aqui') {
        $lineas[] = 'Entrega: Comer aqui 🍽️';
        if ($mesaNombrePedido !== null) {
            $lineas[] = 'Mesa: ' . $mesaNombrePedido . ($zonaNombrePedido ? ' · Zona ' . $zonaNombrePedido : '');
        }
    } else {
        $lineas[] = 'Entrega: Recojo en local 🏠';
    }
    if ($tipoEntrega === 'delivery') {
        $lineas[] = 'Dirección: ' . $direccion;
        if ($referencia) $lineas[] = 'Referencia: ' . $referencia;
    }
    $textoPago = [
        'efectivo' => 'Efectivo al recibir 💵',
        'yape_plin' => 'Yape vía Culqi - PAGADO ONLINE ✅',
        'tarjeta' => 'Tarjeta - PAGADO ONLINE ✅',
    ];
    $lineas[] = 'Pago: ' . $textoPago[$metodoPago];
    if ($notas) $lineas[] = 'Notas: ' . $notas;

    $mensaje = implode("\n", $lineas);
    $numeroWhatsapp = preg_replace('/\D/', '', cfg('whatsapp_numero', ''));
    $whatsappUrl = 'https://wa.me/' . $numeroWhatsapp . '?text=' . rawurlencode($mensaje);

    // ---------- Enviar correo al cliente (no bloqueante) ----------
    $mailResultado = ['ok' => false, 'mensaje' => 'No enviado'];
    if (!empty($clienteEmail)) {
        $mailResultado = enviarCorreoCompraCliente([
            'cliente_email' => $clienteEmail,
            'cliente_nombre' => $clienteNombre,
            'codigo' => $codigo,
            'total' => $total,
            'numero_comprobante' => $comprobante['numero_comprobante']
                ?? $comprobante['numero']
                ?? $comprobante['serie_numero']
                ?? ($comprobante['serie'] ?? '') . ((isset($comprobante['correlativo']) && isset($comprobante['serie'])) ? ('-' . $comprobante['correlativo']) : ''),
            'comprobante_pdf' => $comprobante['pdf'] ?? null,
            'comprobante_xml' => $comprobante['xml'] ?? null,
            'comprobante_cdr' => $comprobante['cdr'] ?? null,
            'comprobante_pdf_path' => $comprobante['pdf_path'] ?? null,
            'comprobante_xml_path' => $comprobante['xml_path'] ?? null,
            'comprobante_cdr_path' => $comprobante['cdr_path'] ?? null,
        ]);
    }

    $resumenFidelizacion = obtenerResumenFidelizacionCliente($clienteTelefono);

    jsonResponse([
        'ok' => true,
        'codigo' => $codigo,
        'total' => $total,
        'estado' => $estado,
        'comprobante' => $comprobante,
        'whatsapp_url' => $whatsappUrl,
        'comprobante_pdf' => $comprobante['pdf'] ?? null,
        'comprobante_xml' => $comprobante['xml'] ?? null,
        'correo_enviado' => $mailResultado['ok'],
        'correo_mensaje' => $mailResultado['mensaje'] ?? '',
        'fidelizacion' => $resumenFidelizacion,
    ]);

} catch (CulqiException $e) {
    $db->rollBack();
    $mensajeCulqi = $e->getMessage();
    $mensajeNormalizado = mb_strtolower($mensajeCulqi, 'UTF-8');

    if (str_contains($mensajeNormalizado, 'necesita autenticarse') || str_contains($mensajeNormalizado, 'autenticaci')) {
        jsonResponse([
            'ok' => false,
            'mensaje' => 'Pago rechazado: la tarjeta requiere autenticación 3DS. Completa el flujo 3DS con Culqi o prueba con una tarjeta de pruebas sin 3DS (por ejemplo 4111 1111 1111 1111).',
        ], 402);
    }

    jsonResponse(['ok' => false, 'mensaje' => 'Pago rechazado: ' . $mensajeCulqi], 402);
} catch (RuntimeException $e) {
    $db->rollBack();
    jsonResponse(['ok' => false, 'mensaje' => $e->getMessage()], 400);
} catch (Throwable $e) {
    $db->rollBack();
    jsonResponse(['ok' => false, 'mensaje' => 'Error inesperado del servidor.'], 500);
}