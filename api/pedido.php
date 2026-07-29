<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/culqi.php';
require_once __DIR__ . '/../includes/facturacion.php';

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
$metodoPago     = $body['metodo_pago'] ?? '';
$notas          = trim($body['notas'] ?? '');
$culqiToken     = $body['culqi_token'] ?? null;
$clienteEmail   = trim($body['cliente_email'] ?? 'cliente@example.com');
$tipoComprobante = strtolower(trim((string)($body['tipo_comprobante'] ?? 'boleta')));
$tipoDocumento   = strtolower(trim((string)($body['tipo_documento'] ?? 'dni')));
$numeroDocumento = normalizarNumeroDocumento((string)($body['numero_documento'] ?? ''));

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
if (!in_array($tipoEntrega, ['recojo', 'delivery'], true)) {
    jsonResponse(['ok' => false, 'mensaje' => 'Selecciona recojo o delivery.'], 400);
}
if ($tipoEntrega === 'delivery' && mb_strlen($direccion) < 5) {
    jsonResponse(['ok' => false, 'mensaje' => 'Ingresa tu dirección de entrega.'], 400);
}
if (!in_array($metodoPago, ['efectivo', 'yape_plin', 'tarjeta'], true)) {
    jsonResponse(['ok' => false, 'mensaje' => 'Selecciona un método de pago.'], 400);
}
if (in_array($metodoPago, ['tarjeta', 'yape_plin'], true) && empty($culqiToken)) {
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

    // ---------- Recalcular precios reales desde la BD (nunca confiar en el frontend) ----------
    $detalle = [];
    $subtotal = 0.0;

    $stmtProd = $db->prepare('SELECT id, nombre, precio, precio_oferta, disponible FROM productos WHERE id = :id LIMIT 1');

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

        $precioUnitario = $producto['precio_oferta'] !== null && $producto['precio_oferta'] > 0
            ? (float)$producto['precio_oferta']
            : (float)$producto['precio'];

        $subtotalItem = round($precioUnitario * $cantidad, 2);
        $subtotal += $subtotalItem;

        $detalle[] = [
            'producto_id'      => $producto['id'],
            'nombre_producto'  => $producto['nombre'],
            'precio_unitario'  => $precioUnitario,
            'cantidad'         => $cantidad,
            'subtotal'         => $subtotalItem,
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
    } elseif (cfg('recojo_activo', '1') !== '1') {
        throw new RuntimeException('El recojo en local no está disponible en este momento.');
    }

    $total = round($subtotal + $costoDelivery, 2);
    $codigo = generarCodigoPedido();
    $estado = 'pendiente';
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
    if (in_array($metodoPago, ['tarjeta', 'yape_plin'], true)) {
        $cargo = crearCargoCulqi(
            $culqiToken,
            $total,
            $clienteEmail,
            'Pedido ' . $codigo . ' - ' . cfg('nombre_negocio', 'Carta Digital')
        );
        $culqiChargeId = $cargo['id'] ?? null;
        $estado = 'pagado';
    }

    // ---------- Guardar pedido ----------
    $stmtPedido = $db->prepare(
        'INSERT INTO pedidos
            (codigo, cliente_nombre, cliente_telefono, tipo_comprobante, tipo_documento, numero_documento, tipo_entrega, direccion, referencia,
             metodo_pago, estado, subtotal, costo_delivery, total, notas, culqi_charge_id)
         VALUES
            (:codigo, :cliente_nombre, :cliente_telefono, :tipo_comprobante, :tipo_documento, :numero_documento, :tipo_entrega, :direccion, :referencia,
             :metodo_pago, :estado, :subtotal, :costo_delivery, :total, :notas, :culqi_charge_id)'
    );
    $stmtPedido->execute([
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
    ]);
    $pedidoId = $db->lastInsertId();

    $stmtDetalle = $db->prepare(
        'INSERT INTO pedido_detalle (pedido_id, producto_id, nombre_producto, precio_unitario, cantidad, subtotal)
         VALUES (:pedido_id, :producto_id, :nombre_producto, :precio_unitario, :cantidad, :subtotal)'
    );
    foreach ($detalle as $d) {
        $stmtDetalle->execute([
            'pedido_id' => $pedidoId,
            'producto_id' => $d['producto_id'],
            'nombre_producto' => $d['nombre_producto'],
            'precio_unitario' => $d['precio_unitario'],
            'cantidad' => $d['cantidad'],
            'subtotal' => $d['subtotal'],
        ]);
    }

    $comprobante = registrarComprobanteElectronicoDesdePedido($db, (int)$pedidoId);

    $db->commit();

    if ($comprobante && $comprobante['estado_sunat'] === 'pendiente_envio') {
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

    // ---------- Construir mensaje de WhatsApp ----------
    $lineas = [];
    $lineas[] = '🍗 *Nuevo pedido ' . $codigo . '*';
    $lineas[] = '';
    foreach ($detalle as $d) {
        $lineas[] = "• {$d['cantidad']}x {$d['nombre_producto']} - " . formatoPrecio($d['subtotal']);
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
    $lineas[] = 'Entrega: ' . ($tipoEntrega === 'delivery' ? 'Delivery 🛵' : 'Recojo en local 🏠');
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

    jsonResponse([
        'ok' => true,
        'codigo' => $codigo,
        'total' => $total,
        'estado' => $estado,
        'comprobante' => $comprobante ?? null,
        'whatsapp_url' => $whatsappUrl,
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
