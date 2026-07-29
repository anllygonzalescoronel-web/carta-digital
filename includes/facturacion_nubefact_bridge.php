<?php
/**
 * Puente entre el checkout y el panel "Comprobantes" (admin/comprobantes.php)
 * cuando el motor de facturación activo es NubeFacT
 * (configuracion.facturacion_driver = 'nubefact').
 *
 * Escribe en la MISMA tabla `comprobantes_electronicos` que usa el flujo de
 * SUNAT Nativo, para que boletas de ambos proveedores aparezcan juntas en
 * el panel de administración.
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/nubefact.php';
require_once __DIR__ . '/facturacion.php'; // ensureFacturacionSchema()

/**
 * Genera la boleta vía NubeFacT y la registra en comprobantes_electronicos.
 *
 * @return array ['ok' => bool, 'pdf' => ?string, 'xml' => ?string, 'cdr' => ?string, 'error' => ?string]
 */
function emitirComprobanteNubefactUnificado(PDO $db, int $pedidoId): array {
    ensureFacturacionSchema($db);

    $stmtPedido = $db->prepare('SELECT * FROM pedidos WHERE id = :id LIMIT 1');
    $stmtPedido->execute(['id' => $pedidoId]);
    $pedido = $stmtPedido->fetch();
    if (!$pedido) {
        return ['ok' => false, 'error' => 'Pedido no encontrado'];
    }

    $stmtDet = $db->prepare('SELECT * FROM pedido_detalle WHERE pedido_id = :id');
    $stmtDet->execute(['id' => $pedidoId]);
    $detalle = $stmtDet->fetchAll();
    if (empty($detalle)) {
        return ['ok' => false, 'error' => 'El pedido no tiene detalle'];
    }

    // Lo que el cliente eligió en el checkout (columnas que agregó tu compañero)
    $esFactura = strtolower(trim((string)($pedido['tipo_comprobante'] ?? 'boleta'))) === 'factura';
    $tipoDocumentoPedido = strtolower(trim((string)($pedido['tipo_documento'] ?? '')));
    $numeroDocumentoPedido = trim((string)($pedido['numero_documento'] ?? ''));

    if ($esFactura) {
        $serie = cfg('nubefact_serie_factura', 'FFF1');
        $tipoDocumento = 'ruc';
        $numeroDocumento = $numeroDocumentoPedido;
        if (!preg_match('/^\d{11}$/', $numeroDocumento)) {
            return ['ok' => false, 'error' => 'Para factura por NubeFacT se requiere un RUC válido de 11 dígitos.'];
        }
    } else {
        $serie = cfg('nubefact_serie_boleta', 'BBB1');
        if ($tipoDocumentoPedido === 'ruc' && preg_match('/^\d{11}$/', $numeroDocumentoPedido)) {
            $tipoDocumento = 'ruc';
            $numeroDocumento = $numeroDocumentoPedido;
        } elseif ($tipoDocumentoPedido === 'dni' && preg_match('/^\d{8}$/', $numeroDocumentoPedido)) {
            $tipoDocumento = 'dni';
            $numeroDocumento = $numeroDocumentoPedido;
        } elseif (!empty($pedido['cliente_dni'])) {
            // Compatibilidad con el campo viejo cliente_dni
            $tipoDocumento = 'dni';
            $numeroDocumento = $pedido['cliente_dni'];
        } else {
            // El ENUM de comprobantes_electronicos solo admite dni|ruc, así que
            // para "sin documento" lo registramos como dni con el placeholder.
            $tipoDocumento = 'dni';
            $numeroDocumento = '00000000';
        }
    }

    $datosBase = [
        'cliente_nombre' => $pedido['cliente_nombre'],
        'cliente_email'  => $pedido['cliente_email'] ?? '',
        'items'          => array_map(fn($d) => [
            'descripcion'     => $d['nombre_producto'],
            'cantidad'        => $d['cantidad'],
            'precio_unitario' => $d['precio_unitario'],
        ], $detalle),
    ];

    if ($esFactura) {
        $datosBase['cliente_ruc'] = $numeroDocumento;
    } else {
        $datosBase['cliente_dni'] = $tipoDocumento === 'dni' && $numeroDocumento !== '00000000' ? $numeroDocumento : '';
    }

    $ultimoError = null;
    $respuesta   = null;
    $numero      = null;

    // Hasta 3 intentos: si NubeFacT dice "ya existe" (contador desincronizado),
    // avanzamos el correlativo y probamos otra vez.
    for ($intento = 1; $intento <= 3; $intento++) {
        $db->beginTransaction();
        $db->exec("INSERT IGNORE INTO comprobante_correlativo (serie, tipo_comprobante, ultimo_numero) VALUES (" . $db->quote($serie) . ", " . ($esFactura ? 1 : 2) . ", 0)");
        $stmtNum = $db->prepare('SELECT ultimo_numero FROM comprobante_correlativo WHERE serie = :s FOR UPDATE');
        $stmtNum->execute(['s' => $serie]);
        $numero = (int) $stmtNum->fetchColumn() + 1;
        $db->prepare('UPDATE comprobante_correlativo SET ultimo_numero = :n WHERE serie = :s')
            ->execute(['n' => $numero, 's' => $serie]);
        $db->commit();

        try {
            $datosEnvio = ['serie' => $serie, 'numero' => $numero] + $datosBase;
            $respuesta = $esFactura
                ? emitirFacturaNubefact($datosEnvio)
                : emitirBoletaNubefact($datosEnvio);
            break; // éxito, salimos del for
        } catch (NubefactException $e) {
            $ultimoError = $e->getMessage();
            $normalizado = mb_strtolower($ultimoError, 'UTF-8');
            $esErrorDeNumero = str_contains($normalizado, 'ya existe') || str_contains($normalizado, 'correlativo');
            $respuesta = null;
            if (!$esErrorDeNumero) {
                break; // otro tipo de error, no tiene caso reintentar
            }
        }
    }

    $numeroComprobante = $serie . '-' . str_pad((string) $numero, 6, '0', STR_PAD_LEFT);
    $estadoSunat = $respuesta ? 'aceptado' : 'error';

    // ---------- Registrar en la tabla compartida con SUNAT Nativo ----------
    $stmtComp = $db->prepare(
        'INSERT INTO comprobantes_electronicos
            (pedido_id, tipo_comprobante, serie, correlativo, numero_comprobante, tipo_documento, numero_documento,
             estado_sunat, sunat_descripcion, xml_path, cdr_path, pdf_path, cdr_response_json, payload_json,
             error_detalle, enviado_en, respondido_en, intentos_envio)
         VALUES
            (:pedido_id, :tipo_comprobante, :serie, :correlativo, :numero_comprobante, :tipo_documento, :numero_documento,
             :estado_sunat, :sunat_descripcion, :xml_path, :cdr_path, :pdf_path, :cdr_response_json, :payload_json,
             :error_detalle, NOW(), NOW(), 1)'
    );
    $stmtComp->execute([
        'pedido_id'          => $pedidoId,
        'tipo_comprobante'   => $esFactura ? 'factura' : 'boleta',
        'serie'              => $serie,
        'correlativo'        => $numero,
        'numero_comprobante' => $numeroComprobante,
        'tipo_documento'     => $tipoDocumento,
        'numero_documento'   => $numeroDocumento,
        'estado_sunat'       => $estadoSunat,
        'sunat_descripcion'  => $respuesta ? 'Generado vía NubeFacT.' : $ultimoError,
        'xml_path'           => $respuesta['enlace_del_xml'] ?? null,
        'cdr_path'           => $respuesta['enlace_del_cdr'] ?? null,
        'pdf_path'           => $respuesta['enlace_del_pdf'] ?? null,
        'cdr_response_json'  => $respuesta ? json_encode($respuesta, JSON_UNESCAPED_UNICODE) : null,
        'payload_json'       => json_encode($datosBase, JSON_UNESCAPED_UNICODE),
        'error_detalle'      => $ultimoError,
    ]);
    $comprobanteId = (int) $db->lastInsertId();

    $db->prepare('UPDATE pedidos SET
            comprobante_id = :comprobante_id,
            comprobante_serie = :serie,
            comprobante_correlativo = :correlativo,
            comprobante_numero = :numero_comprobante,
            sunat_estado = :estado,
            sunat_mensaje = :mensaje
        WHERE id = :pedido_id')
        ->execute([
            'comprobante_id'     => $comprobanteId,
            'serie'              => $serie,
            'correlativo'        => $numero,
            'numero_comprobante' => $numeroComprobante,
            'estado'             => $estadoSunat,
            'mensaje'            => $respuesta ? 'Comprobante generado vía NubeFacT.' : $ultimoError,
            'pedido_id'          => $pedidoId,
        ]);

    if (!$respuesta) {
        return ['ok' => false, 'error' => $ultimoError];
    }

    return [
        'ok'  => true,
        'pdf' => $respuesta['enlace_del_pdf'] ?? null,
        'xml' => $respuesta['enlace_del_xml'] ?? null,
        'cdr' => $respuesta['enlace_del_cdr'] ?? null,
    ];
}

/**
 * Punto único de entrada desde el checkout: decide si usar NubeFacT o
 * dejarlo para el flujo nativo de tu compañero, según la configuración.
 */
function emitirComprobanteSegunDriver(PDO $db, int $pedidoId): array {
    $driver = strtolower(trim((string) cfg('facturacion_driver', 'native')));

    if ($driver === 'nubefact') {
        return emitirComprobanteNubefactUnificado($db, $pedidoId);
    }

    // Driver 'native' (SUNAT Nativo de tu compañero): lo dejamos tal cual él
    // lo tenga resuelto (registrarComprobanteElectronicoDesdePedido + envío),
    // esto no lo tocamos para no interferir con su trabajo.
    return ['ok' => false, 'error' => 'Driver "native" no gestionado desde este puente.'];
}