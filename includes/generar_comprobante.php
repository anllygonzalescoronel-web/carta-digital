<?php
/**
 * Emite la boleta electrónica de un pedido ya guardado y pagado, y guarda
 * los enlaces (PDF/XML/CDR) en la tabla pedidos.
 *
 * Se llama DESPUÉS de hacer commit() del pedido en api/pedido.php, para no
 * mezclar la transacción de venta con la de facturación: si NubeFacT falla
 * o SUNAT está caída, el pedido ya quedó guardado y pagado igual, y el
 * comprobante se puede reintentar después desde el admin.
 */
require_once __DIR__ . '/nubefact.php';

function generarComprobantePorPedido(int $pedidoId): array {
    $db = getDB();

    $stmt = $db->prepare('SELECT * FROM pedidos WHERE id = :id');
    $stmt->execute(['id' => $pedidoId]);
    $pedido = $stmt->fetch();

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

    $datosBase = [
        'cliente_nombre'  => $pedido['cliente_nombre'],
        'cliente_email'   => $pedido['cliente_email'] ?? '',
        'cliente_dni'     => $pedido['cliente_dni'] ?? '',
        'items'           => array_map(fn($d) => [
            'descripcion'     => $d['nombre_producto'],
            'cantidad'        => $d['cantidad'],
            'precio_unitario' => $d['precio_unitario'],
        ], $detalle),
    ];

    // Hasta 3 intentos: si NubeFacT dice "ya existe" (contador desincronizado,
    // p.ej. por pruebas manuales), avanzamos el correlativo y probamos otra vez.
    $ultimoError = null;
    for ($intento = 1; $intento <= 3; $intento++) {
        // Correlativo seguro con lock, igual de robusto que tu manejo de transacciones en pedido.php
        $db->beginTransaction();
        $stmtNum = $db->prepare('SELECT ultimo_numero FROM comprobante_correlativo WHERE serie = :s FOR UPDATE');
        $stmtNum->execute(['s' => $serie]);
        $numero = (int) $stmtNum->fetchColumn() + 1;
        $db->prepare('UPDATE comprobante_correlativo SET ultimo_numero = :n WHERE serie = :s')
            ->execute(['n' => $numero, 's' => $serie]);
        $db->commit();

        $datosBoleta = ['serie' => $serie, 'numero' => $numero] + $datosBase;

        try {
            $respuesta = emitirBoletaNubefact($datosBoleta);

            $db->prepare('UPDATE pedidos SET
                    comprobante_serie = :serie, comprobante_numero = :numero,
                    comprobante_pdf_url = :pdf, comprobante_xml_url = :xml,
                    comprobante_cdr_url = :cdr, comprobante_estado = :estado,
                    comprobante_error = NULL
                WHERE id = :id')
                ->execute([
                    'serie'   => $serie,
                    'numero'  => $numero,
                    'pdf'     => $respuesta['enlace_del_pdf'] ?? null,
                    'xml'     => $respuesta['enlace_del_xml'] ?? null,
                    'cdr'     => $respuesta['enlace_del_cdr'] ?? null,
                    'estado'  => 'aceptado',
                    'id'      => $pedidoId,
                ]);

            return [
                'ok'  => true,
                'pdf' => $respuesta['enlace_del_pdf'] ?? null,
                'xml' => $respuesta['enlace_del_xml'] ?? null,
                'cdr' => $respuesta['enlace_del_cdr'] ?? null,
            ];
        } catch (NubefactException $e) {
            $ultimoError = $e->getMessage();
            $mensajeNormalizado = mb_strtolower($ultimoError, 'UTF-8');

            // Solo reintentamos si es un problema de correlativo desincronizado.
            // Cualquier otro error (config, datos del cliente, etc.) se reporta directo.
            $esErrorDeNumero = str_contains($mensajeNormalizado, 'ya existe')
                || str_contains($mensajeNormalizado, 'correlativo');

            if (!$esErrorDeNumero) {
                break;
            }
            // si es error de número, el for vuelve a intentar con el siguiente correlativo
        }
    }

    // El pago YA está cobrado; no revertimos el pedido, solo dejamos rastro
    // del error para reintentar la emisión manualmente desde el admin.
    $db->prepare('UPDATE pedidos SET comprobante_estado = :estado, comprobante_error = :err WHERE id = :id')
        ->execute(['estado' => 'error', 'err' => $ultimoError, 'id' => $pedidoId]);

    return ['ok' => false, 'error' => $ultimoError];
}