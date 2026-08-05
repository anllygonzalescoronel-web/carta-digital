<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requerirRol(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'mensaje' => 'Metodo no permitido'], 405);
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    jsonResponse(['ok' => false, 'mensaje' => 'JSON invalido'], 400);
}

$items = $body['items'] ?? [];
$mesaId = (int)($body['mesa_id'] ?? 0);
$notas = trim((string)($body['notas'] ?? ''));

if (!is_array($items) || empty($items)) {
    jsonResponse(['ok' => false, 'mensaje' => 'No hay productos para enviar a cocina.'], 400);
}
if ($mesaId <= 0) {
    jsonResponse(['ok' => false, 'mensaje' => 'Mesa invalida.'], 400);
}

$db = getDB();

try {
    $turno = $db->query("SELECT id FROM cajas_turnos WHERE estado = 'abierta' ORDER BY id DESC LIMIT 1")->fetch();
    if (!$turno) {
        throw new RuntimeException('Debes abrir una caja para operar el POS.');
    }

    $stmtMesa = $db->prepare(
        'SELECT m.id, m.nombre AS mesa_nombre, z.nombre AS zona_nombre, m.mesa_union_id
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

    // Si la mesa está unida a otra (es secundaria), redirigir pedidos a la mesa principal
    if (!empty($mesa['mesa_union_id'])) {
        $stmtMesa->execute(['id' => (int)$mesa['mesa_union_id']]);
        $mesaPrincipal = $stmtMesa->fetch();
        if ($mesaPrincipal) {
            $mesaId = (int)$mesaPrincipal['id'];
            $mesa = $mesaPrincipal;
        }
    }

    $stmtProd = $db->prepare(
        'SELECT id, nombre, precio, precio_oferta, disponible
         FROM productos
         WHERE id = :id
         LIMIT 1'
    );

    $stmtOp = null;
    $stmtGruposReq = null;
    $soportaOpciones = true;
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

    $detalle = [];
    $subtotal = 0.0;

    foreach ($items as $item) {
        $productoId = (int)($item['id'] ?? 0);
        $cantidad = max(1, (int)($item['cantidad'] ?? 1));
        if ($productoId <= 0) {
            throw new RuntimeException('Hay un producto invalido en la comanda.');
        }

        $stmtProd->execute(['id' => $productoId]);
        $producto = $stmtProd->fetch();
        if (!$producto) {
            throw new RuntimeException('Un producto de la comanda ya no existe.');
        }
        if ((int)$producto['disponible'] !== 1) {
            throw new RuntimeException('"' . $producto['nombre'] . '" ya no está disponible.');
        }

        $precioBase = $producto['precio_oferta'] !== null && (float)$producto['precio_oferta'] > 0
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

        $gruposProducto = [];
        if ($soportaOpciones && $stmtGruposReq) {
            try {
                $stmtGruposReq->execute(['producto_id' => (int)$producto['id']]);
                $gruposProducto = $stmtGruposReq->fetchAll();
            } catch (Throwable $e) {
                $gruposProducto = [];
            }
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
            'producto_id' => (int)$producto['id'],
            'nombre_producto' => (string)$producto['nombre'],
            'precio_unitario' => $precioUnitario,
            'cantidad' => $cantidad,
            'subtotal' => $subtotalItem,
            'opciones_json' => !empty($opcionesNormalizadas) ? json_encode($opcionesNormalizadas, JSON_UNESCAPED_UNICODE) : null,
        ];
    }

    if (empty($detalle)) {
        throw new RuntimeException('La comanda no tiene detalle.');
    }

    $db->beginTransaction();

    $codigo = generarCodigoPedido();
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
        'cliente_nombre' => 'Mesa ' . $mesa['mesa_nombre'],
        'cliente_telefono' => '999999999',
        'tipo_comprobante' => null,
        'tipo_documento' => null,
        'numero_documento' => null,
        'tipo_entrega' => 'comer_aqui',
        'direccion' => null,
        'referencia' => null,
        'metodo_pago' => 'efectivo',
        'estado' => 'pendiente',
        'subtotal' => round($subtotal, 2),
        'costo_delivery' => 0,
        'total' => round($subtotal, 2),
        'notas' => $notas !== '' ? $notas : 'Comanda POS',
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
        $placeholders[] = ':mesa_id';
        $paramsPedido['mesa_id'] = $mesaId;
    }
    if (isset($columnasPedido['mesa_nombre'])) {
        $camposInsert[] = 'mesa_nombre';
        $placeholders[] = ':mesa_nombre';
        $paramsPedido['mesa_nombre'] = $mesa['mesa_nombre'];
    }
    if (isset($columnasPedido['zona_nombre'])) {
        $camposInsert[] = 'zona_nombre';
        $placeholders[] = ':zona_nombre';
        $paramsPedido['zona_nombre'] = $mesa['zona_nombre'];
    }
    if (isset($columnasPedido['caja_turno_id'])) {
        $camposInsert[] = 'caja_turno_id';
        $placeholders[] = ':caja_turno_id';
        $paramsPedido['caja_turno_id'] = (int)$turno['id'];
    }

    $stmtPedido = $db->prepare('INSERT INTO pedidos (' . implode(', ', $camposInsert) . ') VALUES (' . implode(', ', $placeholders) . ')');
    $stmtPedido->execute($paramsPedido);
    $pedidoId = (int)$db->lastInsertId();

    $columnasDetalle = [];
    $stmtColsDetalle = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedido_detalle'");
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

    $db->commit();

    jsonResponse([
        'ok' => true,
        'pedido_id' => $pedidoId,
        'codigo' => $codigo,
        'mesa' => [
            'id' => $mesaId,
            'nombre' => (string)$mesa['mesa_nombre'],
            'zona' => (string)$mesa['zona_nombre'],
        ],
        'total' => round($subtotal, 2),
        'mensaje' => 'Comanda enviada a cocina correctamente.',
    ]);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(['ok' => false, 'mensaje' => $e->getMessage()], 400);
}