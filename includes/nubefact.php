<?php
/**
 * Integración con NubeFacT (API REST - JSON) para emitir Boletas y Facturas.
 * Sigue el mismo patrón que includes/culqi.php: usa cfg() para las credenciales
 * y lanza una excepción propia si algo falla, sin detener el checkout.
 */
require_once __DIR__ . '/functions.php';

class NubefactException extends RuntimeException {}

/**
 * Emite una Boleta de Venta electrónica (mantiene compatibilidad con el
 * formato usado hasta ahora: cliente_dni suelto, sin documento = 0).
 *
 * @param array $pedido [
 *     'serie'  => 'BBB1',
 *     'numero' => 24,
 *     'cliente_nombre' => 'Juan Perez',
 *     'cliente_email'  => 'juan@correo.com' (opcional),
 *     'cliente_dni'    => '12345678' (opcional),
 *     'items' => [ ['descripcion' => 'Lomo Saltado', 'cantidad' => 1, 'precio_unitario' => 60.00], ... ]
 * ]
 */
function emitirBoletaNubefact(array $pedido): array {
    $tieneDni = !empty($pedido['cliente_dni']);
    return emitirComprobanteNubefact([
        'serie'                   => $pedido['serie'],
        'numero'                  => $pedido['numero'],
        'cliente_nombre'          => $pedido['cliente_nombre'],
        'cliente_email'           => $pedido['cliente_email'] ?? '',
        'cliente_tipo_documento'  => $tieneDni ? 'dni' : '',
        'cliente_numero_documento' => $tieneDni ? $pedido['cliente_dni'] : '',
        'items'                   => $pedido['items'],
    ], 2);
}

/**
 * Emite una Factura electrónica. Requiere RUC de 11 dígitos.
 *
 * @param array $pedido [
 *     'serie'  => 'FFF1',
 *     'numero' => 5,
 *     'cliente_nombre' => 'Empresa SAC',
 *     'cliente_email'  => 'compras@empresa.com' (opcional),
 *     'cliente_ruc'    => '20123456789',
 *     'items' => [ ... ]
 * ]
 */
function emitirFacturaNubefact(array $pedido): array {
    $ruc = trim((string)($pedido['cliente_ruc'] ?? ''));
    if (!preg_match('/^\d{11}$/', $ruc)) {
        throw new NubefactException('Para emitir factura por NubeFacT necesitas un RUC válido de 11 dígitos.');
    }

    return emitirComprobanteNubefact([
        'serie'                    => $pedido['serie'],
        'numero'                   => $pedido['numero'],
        'cliente_nombre'           => $pedido['cliente_nombre'],
        'cliente_email'            => $pedido['cliente_email'] ?? '',
        'cliente_tipo_documento'   => 'ruc',
        'cliente_numero_documento' => $ruc,
        'items'                    => $pedido['items'],
    ], 1);
}

/**
 * Núcleo compartido: arma el JSON y llama a la API de NubeFacT.
 *
 * @param array $pedido [
 *     'serie', 'numero', 'cliente_nombre', 'cliente_email',
 *     'cliente_tipo_documento' => 'dni'|'ruc'|'' (vacío = sin documento),
 *     'cliente_numero_documento', 'items'
 * ]
 * @param int $tipoComprobante 1 = factura, 2 = boleta
 * @return array Respuesta de NubeFacT (incluye enlace_del_pdf, enlace_del_xml, enlace_del_cdr)
 * @throws NubefactException si NubeFacT rechaza el comprobante o hay error de red/config
 */
function emitirComprobanteNubefact(array $pedido, int $tipoComprobante): array {
    // NubeFacT exige que la fecha de emisión sea "hoy" en Perú; si el servidor
    // está en otra zona horaria (común en hosting), date() puede devolver el
    // día equivocado y NubeFacT rechaza el comprobante.
    date_default_timezone_set('America/Lima');

    $ruta  = cfg('nubefact_ruta');
    $token = cfg('nubefact_token');

    if (empty($ruta) || str_contains($ruta, 'TU-RUTA-AQUI') || empty($token) || str_contains($token, 'TU-TOKEN-AQUI')) {
        throw new NubefactException('Aún no configuraste tu RUTA y TOKEN de NubeFacT en el panel de administración (Configuración).');
    }

    $igvPorcentaje = 18.00;
    $totalGravada = 0;
    $totalIgv     = 0;
    $items        = [];

    foreach ($pedido['items'] as $item) {
        $precioUnitario = (float) $item['precio_unitario']; // con IGV incluido
        $cantidad       = (float) $item['cantidad'];
        $totalItem      = round($precioUnitario * $cantidad, 2);
        $valorUnitario  = round($precioUnitario / (1 + $igvPorcentaje / 100), 2);
        $subtotalItem   = round($valorUnitario * $cantidad, 2);
        $igvItem        = round($totalItem - $subtotalItem, 2);

        $totalGravada += $subtotalItem;
        $totalIgv     += $igvItem;

        $items[] = [
            'unidad_de_medida' => 'NIU',
            'codigo'           => $item['codigo'] ?? '',
            'descripcion'      => $item['descripcion'],
            'cantidad'         => $cantidad,
            'valor_unitario'   => $valorUnitario,
            'precio_unitario'  => $precioUnitario,
            'descuento'        => '',
            'subtotal'         => $subtotalItem,
            'tipo_de_igv'      => 1, // Gravado - Operación Onerosa
            'igv'              => $igvItem,
            'total'            => $totalItem,
            'anticipo_regularizacion' => false,
        ];
    }

    $total = round($totalGravada + $totalIgv, 2);

    $tipoDoc = strtolower(trim((string)($pedido['cliente_tipo_documento'] ?? '')));
    $numeroDoc = trim((string)($pedido['cliente_numero_documento'] ?? ''));

    if ($tipoComprobante === 1) {
        // Factura: SIEMPRE RUC.
        $clienteTipoDocumento = 6;
        if (!preg_match('/^\d{11}$/', $numeroDoc)) {
            throw new NubefactException('Para factura se requiere un RUC válido de 11 dígitos.');
        }
        $clienteNumeroDocumento = $numeroDoc;
    } elseif ($tipoDoc === 'dni' && preg_match('/^\d{8}$/', $numeroDoc)) {
        $clienteTipoDocumento = 1; // DNI
        $clienteNumeroDocumento = $numeroDoc;
    } elseif ($tipoDoc === 'ruc' && preg_match('/^\d{11}$/', $numeroDoc)) {
        $clienteTipoDocumento = 6; // RUC (boleta a nombre de empresa, poco común pero válido)
        $clienteNumeroDocumento = $numeroDoc;
    } else {
        $clienteTipoDocumento = 0; // Sin documento (válido para boletas < S/700)
        $clienteNumeroDocumento = '00000000'; // NubeFacT exige que no vaya vacío
    }

    $body = [
        'operacion'                        => 'generar_comprobante',
        'tipo_de_comprobante'               => $tipoComprobante, // 1 factura, 2 boleta
        'serie'                             => $pedido['serie'],
        'numero'                            => $pedido['numero'],
        'sunat_transaction'                 => 1,
        'cliente_tipo_de_documento'         => $clienteTipoDocumento,
        'cliente_numero_de_documento'       => $clienteNumeroDocumento,
        'cliente_denominacion'              => $pedido['cliente_nombre'],
        'cliente_email'                     => $pedido['cliente_email'] ?? '',
        'fecha_de_emision'                  => date('d-m-Y'),
        'moneda'                            => 1, // Soles
        'porcentaje_de_igv'                 => $igvPorcentaje,
        'total_gravada'                     => round($totalGravada, 2),
        'total_igv'                         => round($totalIgv, 2),
        'total'                             => $total,
        'enviar_automaticamente_a_la_sunat' => true,
        'enviar_automaticamente_al_cliente' => !empty($pedido['cliente_email']),
        'items'                             => $items,
    ];

    $ch = curl_init($ruta);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $respuestaCruda = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($respuestaCruda === false) {
        throw new NubefactException('No se pudo conectar con NubeFacT: ' . $curlError);
    }

    $respuesta = json_decode($respuestaCruda, true);
    if (!is_array($respuesta)) {
        throw new NubefactException('Respuesta inválida de NubeFacT.');
    }

    if ($httpCode !== 200 || isset($respuesta['errors'])) {
        $mensaje = is_array($respuesta['errors'] ?? null)
            ? implode(' | ', $respuesta['errors'])
            : (string)($respuesta['errors'] ?? 'NubeFacT rechazó el comprobante.');
        throw new NubefactException($mensaje);
    }

    return $respuesta;
}