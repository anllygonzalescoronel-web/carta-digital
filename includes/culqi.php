<?php
/**
 * Integración con Culqi usando su API REST directamente (cURL).
 * Esto evita depender de Composer para tener algo funcional de inmediato.
 * Si prefieres usar el SDK oficial (culqi/culqi-php que ya tienes clonado),
 * revisa el bloque comentado al final de este archivo.
 */
require_once __DIR__ . '/functions.php';

class CulqiException extends RuntimeException {}

/**
 * Crea un cargo (cobro) en Culqi.
 *
 * @param string $token   Token generado por Culqi.js en el navegador (tkn_xxx)
 * @param float  $montoSoles Monto en SOLES (ej. 25.50)
 * @param string $email    Email del cliente
 * @param string $descripcion Descripción del cargo (ej. "Pedido PED-260721-ABCDE")
 * @return array Respuesta decodificada de Culqi (incluye "id" del cargo si fue exitoso)
 * @throws CulqiException si Culqi rechaza el cargo o hay un error de red
 */
function crearCargoCulqi(string $token, float $montoSoles, string $email, string $descripcion): array {
    $secretKey = cfg('culqi_secret_key');
    if (empty($secretKey) || str_contains($secretKey, 'XXXX')) {
        throw new CulqiException('Aún no configuraste tu llave secreta de Culqi en el panel de administración (Configuración).');
    }

    $montoCentimos = (int) round($montoSoles * 100);

    $payload = [
        'amount'        => $montoCentimos,
        'currency_code' => 'PEN',
        'email'         => $email,
        'source_id'     => $token,
        'description'   => $descripcion,
        'capture'       => true,
    ];

    $ch = curl_init('https://api.culqi.com/v2/charges');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $secretKey,
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    $respuestaCruda = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($respuestaCruda === false) {
        throw new CulqiException('No se pudo conectar con Culqi: ' . $curlError);
    }

    $respuesta = json_decode($respuestaCruda, true);
    if (!is_array($respuesta)) {
        throw new CulqiException('Respuesta inválida de Culqi.');
    }

    $outcome = is_array($respuesta['outcome'] ?? null) ? $respuesta['outcome'] : [];
    $mensajeUsuario = (string)($respuesta['user_message'] ?? $outcome['user_message'] ?? '');
    $mensajeComercio = (string)($respuesta['merchant_message'] ?? $outcome['merchant_message'] ?? '');

    if ($httpCode !== 200 && $httpCode !== 201) {
        $mensaje = $mensajeUsuario !== ''
            ? $mensajeUsuario
            : ($mensajeComercio !== '' ? $mensajeComercio : 'El banco u operador rechazó el pago.');
        throw new CulqiException($mensaje);
    }

    $responseCode = strtolower((string)($respuesta['response_code'] ?? $outcome['type'] ?? ''));
    $state = strtolower((string)($respuesta['state'] ?? ''));
    $status = strtolower((string)($respuesta['status'] ?? ''));
    $esExitosa = in_array($responseCode, ['venta_exitosa', 'approved', 'authorized', 'captured', 'paid', 'succeeded'], true)
        || in_array($state, ['exitosa', 'aprobada', 'capturada', 'pagada'], true)
        || in_array($status, ['captured', 'paid', 'succeeded', 'successful', 'approved', 'authorized'], true);

    if (!$esExitosa) {
        $mensaje = $mensajeUsuario
            ?: ($mensajeComercio
            ?: ((string)($respuesta['response_code'] ?? $outcome['type'] ?? 'El banco u operador rechazó el pago.')));
        throw new CulqiException($mensaje);
    }

    return $respuesta;
}

/*
 * ------------------------------------------------------------------
 * ALTERNATIVA: usar el SDK oficial culqi/culqi-php
 * ------------------------------------------------------------------
 * 1) Instala Composer si no lo tienes.
 * 2) En la raíz del proyecto ejecuta:
 *      composer require culqi/culqi-php
 *    (o clona tu repo dentro de una carpeta "libs/culqi-php" y
 *    referencia su autoload manualmente).
 * 3) Reemplaza la función crearCargoCulqi() de arriba por algo como:
 *
 *   require_once __DIR__ . '/../vendor/autoload.php';
 *   use Culqi\Culqi;
 *
 *   function crearCargoCulqi(string $token, float $montoSoles, string $email, string $descripcion): array {
 *       $culqi = new Culqi(['api_key' => cfg('culqi_secret_key')]);
 *       $cargo = $culqi->Charges->create([
 *           'amount'        => (int) round($montoSoles * 100),
 *           'currency_code' => 'PEN',
 *           'email'         => $email,
 *           'source_id'     => $token,
 *           'description'   => $descripcion,
 *       ]);
 *       return (array) $cargo;
 *   }
 * ------------------------------------------------------------------
 */
