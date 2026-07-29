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

    $serie = cfg('nubefact_serie_boleta', 'BBB1');

    // NubeFacT exige numero_de_documento no vacío incluso "sin documento",
    // así que usamos DNI si lo capturaste, o el placeholder estándar si no.
    $tieneDni = !empty($pedido['cliente_dni']);
    $tipoDocumento = 'dni'; // el ENUM de comprobantes_electronicos solo admite dni|ruc
    $numeroDocumento = $tieneDni ? $pedido['cliente_dni'] : '00000000';

    $datosBase = [
        'cliente_nombre' => $pedido['cliente_nombre'],
        'cliente_email'  => $pedido['cliente_email'] ?? '',
        'cliente_dni'    => $pedido['cliente_dni'] ?? '',
        'items'          => array_map(fn($d) => [
            'descripcion'     => $d['nombre_producto'],
            'cantidad'        => $d['cantidad'],
            'precio_unitario' => $d['precio_unitario'],
        ], $detalle),
    ];

    $ultimoError = null;
    $respuesta   = null;
    $numero      = null;

    // Hasta 3 intentos: si NubeFacT dice "ya existe" (contador desincronizado),
    // avanzamos el correlativo y probamos otra vez.
    for ($intento = 1; $intento <= 3; $intento++) {
        $db->beginTransaction();
        $stmtNum = $db->prepare('SELECT ultimo_numero FROM comprobante_correlativo WHERE serie = :s FOR UPDATE');
        $stmtNum->execute(['s' => $serie]);
        $numero = (int) $stmtNum->fetchColumn() + 1;
        $db->prepare('UPDATE comprobante_correlativo SET ultimo_numero = :n WHERE serie = :s')
            ->execute(['n' => $numero, 's' => $serie]);
        $db->commit();

        try {
            $respuesta = emitirBoletaNubefact(['serie' => $serie, 'numero' => $numero] + $datosBase);
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
        'tipo_comprobante'   => 'boleta',
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
            'mensaje'            => $respuesta ? 'Boleta generada vía NubeFacT.' : $ultimoError,
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