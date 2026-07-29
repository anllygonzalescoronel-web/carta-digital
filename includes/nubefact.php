<?php
/**
 * Integración con NubeFacT (API REST - JSON) para emitir Boletas electrónicas.
 * Sigue el mismo patrón que includes/culqi.php: usa cfg() para las credenciales
 * y lanza una excepción propia si algo falla, sin detener el checkout.
 */
require_once __DIR__ . '/functions.php';

class NubefactException extends RuntimeException {}

/**
 * Emite una Boleta de Venta electrónica.
 *
 * @param array $pedido [
 *     'serie'  => 'BBB1',
 *     'numero' => 24,
 *     'cliente_nombre' => 'Juan Perez',
 *     'cliente_email'  => 'juan@correo.com' (opcional),
 *     'cliente_dni'    => '12345678' (opcional),
 *     'items' => [ ['descripcion' => 'Lomo Saltado', 'cantidad' => 1, 'precio_unitario' => 60.00], ... ]
 * ]
 * @return array Respuesta de NubeFacT (incluye enlace_del_pdf, enlace_del_xml, enlace_del_cdr)
 * @throws NubefactException si NubeFacT rechaza el comprobante o hay error de red/config
 */
function emitirBoletaNubefact(array $pedido): array {
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
    $tieneDni = !empty($pedido['cliente_dni']);

    $body = [
        'operacion'                        => 'generar_comprobante',
        'tipo_de_comprobante'               => 2, // Boleta de venta
        'serie'                             => $pedido['serie'],
        'numero'                            => $pedido['numero'],
        'sunat_transaction'                 => 1,
        'cliente_tipo_de_documento'         => $tieneDni ? 1 : 0, // 1 DNI, 0 sin documento (venta < S/700)
        'cliente_numero_de_documento'       => $tieneDni ? $pedido['cliente_dni'] : '00000000',
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