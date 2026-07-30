<?php
/**
 * Servicio de Consulta a APIPERU
 * Integración con RENIEC (DNI) y RUC
 * 
 * API: https://docs.apiperu.dev/
 * 
 * Requiere token en config:
 *   apiperu_token (se obtiene gratuitamente en apiperu.dev)
 */

require_once __DIR__ . '/functions.php';

class APIPeruException extends RuntimeException {}

/**
 * Ejecuta una solicitud HTTP a APIPERU usando cURL (preferido) y fallback con streams.
 *
 * @param string[] $urls
 * @param string $token
 * @param array $payload
 * @return array
 * @throws APIPeruException
 */
function apiperuRequest(array $urls, string $token, array $payload): array {
    $json = json_encode($payload);
    if ($json === false) {
        throw new APIPeruException('No se pudo serializar la solicitud APIPERU.');
    }

    $urls = array_values(array_filter(array_map('trim', $urls)));
    if (empty($urls)) {
        throw new APIPeruException('No hay endpoints APIPERU configurados.');
    }

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
        'User-Agent: CartaDigital/1.0',
    ];

    $errores = [];

    // cURL suele ser más robusto para HTTPS en Windows.
    if (function_exists('curl_init')) {
        foreach ($urls as $url) {
            // Intento 1: resolución por defecto del sistema (IPv4/IPv6)
            // Intento 2: forzar IPv4 solo si el primero falla por red
            $modos = [
                ['nombre' => 'auto', 'ipresolve' => null],
                ['nombre' => 'ipv4', 'ipresolve' => CURL_IPRESOLVE_V4],
            ];

            foreach ($modos as $modo) {
                $ch = curl_init();
                $opts = [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $json,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_TIMEOUT => 8,
                    CURLOPT_CONNECTTIMEOUT => 4,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                ];
                if ($modo['ipresolve'] !== null) {
                    $opts[CURLOPT_IPRESOLVE] = $modo['ipresolve'];
                }
                curl_setopt_array($ch, $opts);

                $response = curl_exec($ch);
                $curlErrNo = curl_errno($ch);
                $curlErr = curl_error($ch);
                $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
                curl_close($ch);

                if ($response === false) {
                    $errores[] = "{$url} ({$modo['nombre']}): " . ($curlErr ?: 'sin detalle.');
                    // Solo continuamos al siguiente modo si fue un error de red
                    if (in_array($curlErrNo, [CURLE_OPERATION_TIMEDOUT, CURLE_COULDNT_CONNECT, CURLE_COULDNT_RESOLVE_HOST], true)) {
                        continue;
                    }
                    break;
                }

                $data = json_decode($response, true);
                if (!is_array($data)) {
                    $errores[] = "{$url}: Respuesta inválida";
                    break;
                }

                if ($httpCode >= 400) {
                    $msg = $data['message'] ?? ('HTTP ' . $httpCode . ' desde APIPERU');
                    $errores[] = "{$url}: {$msg}";
                    break;
                }

                return $data;
            }
        }

        // Si cURL existe y no logró responder, devolvemos aquí para evitar
        // espera adicional con file_get_contents (se siente como "clavado").
        throw new APIPeruException('Error conectando con APIPERU: ' . implode(' | ', $errores));
    }

    // Fallback con stream_context si cURL no está disponible o falló en todos los endpoints.
    foreach ($urls as $url) {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $json,
                'timeout' => 8,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            $error = error_get_last();
            $errores[] = "{$url}: " . ($error['message'] ?? 'sin detalle.');
            continue;
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            $errores[] = "{$url}: Respuesta inválida";
            continue;
        }

        return $data;
    }

    throw new APIPeruException('Error conectando con APIPERU: ' . implode(' | ', $errores));
}

/**
 * Consulta datos de un DNI en RENIEC
 * 
 * @param string $dni Número de DNI (8 dígitos)
 * @return array {
 *     'ok' => bool,
 *     'numeroDocumento' => string,
 *     'nombres' => string,
 *     'apellidoPaterno' => string,
 *     'apellidoMaterno' => string,
 *     'nombreCompleto' => string,
 *     'sexo' => string,
 *     'fechaNacimiento' => string,
 *     'estado' => string (ACTIVO|FALLECIDO|ANULADO)
 * }
 * @throws APIPeruException
 */
function consultarDNIReniec(string $dni): array {
    $dni = preg_replace('/\D/', '', trim($dni));
    
    if (!preg_match('/^\d{8}$/', $dni)) {
        throw new APIPeruException('DNI debe tener 8 dígitos.');
    }

    $token = cfg('apiperu_token', '');
    if (empty($token)) {
        throw new APIPeruException('Token de APIPERU no configurado. Obtén uno gratuitamente en https://apiperu.dev/');
    }

    try {
        // Endpoint principal + respaldo.
        $datos = apiperuRequest([
            'https://apiperu.dev/api/dni',
            'https://www.apiperu.dev/api/dni',
        ], $token, ['dni' => $dni]);

        if (!is_array($datos)) {
            throw new APIPeruException('Respuesta inválida de APIPERU');
        }

        // Validar respuesta exitosa
        if (isset($datos['success']) && !$datos['success']) {
            $mensaje = $datos['message'] ?? 'DNI no encontrado o sin registros públicos';
            throw new APIPeruException($mensaje);
        }

        if (!isset($datos['data'])) {
            throw new APIPeruException('Formato de respuesta inesperado');
        }

        $data = $datos['data'];

        // Construir nombre completo
        $nombres = trim((string)($data['nombres'] ?? ''));
        $apellidoPaterno = trim((string)($data['apellido_paterno'] ?? ''));
        $apellidoMaterno = trim((string)($data['apellido_materno'] ?? ''));
        $nombreCompleto = "$apellidoPaterno $apellidoMaterno $nombres";
        $nombreCompleto = preg_replace('/\s+/', ' ', trim($nombreCompleto));
        if ($nombreCompleto === '') {
            $nombreCompleto = trim((string)($data['nombre_completo'] ?? ''));
        }

        return [
            'ok' => true,
            'numeroDocumento' => $dni,
            'nombres' => $nombres,
            'apellidoPaterno' => $apellidoPaterno,
            'apellidoMaterno' => $apellidoMaterno,
            'nombreCompleto' => $nombreCompleto,
            'sexo' => isset($data['sexo']) ? strtoupper($data['sexo']) : '',
            'fechaNacimiento' => $data['fecha_nacimiento'] ?? '',
            'estado' => isset($data['estado']) ? strtoupper($data['estado']) : 'DESCONOCIDO',
        ];

    } catch (Exception $e) {
        throw new APIPeruException('Consulta RENIEC fallida: ' . $e->getMessage());
    }
}

/**
 * Consulta datos de un RUC en SUNAT
 * 
 * @param string $ruc Número de RUC (11 dígitos)
 * @return array {
 *     'ok' => bool,
 *     'ruc' => string,
 *     'razonSocial' => string,
 *     'nombreComercial' => string,
 *     'tipo' => string,
 *     'estado' => string,
 *     'direccion' => string,
 *     'telefonos' => array,
 *     'correos' => array
 * }
 * @throws APIPeruException
 */
function consultarRUCSunat(string $ruc): array {
    $ruc = preg_replace('/\D/', '', trim($ruc));
    
    if (!preg_match('/^\d{11}$/', $ruc)) {
        throw new APIPeruException('RUC debe tener 11 dígitos.');
    }

    $token = cfg('apiperu_token', '');
    if (empty($token)) {
        throw new APIPeruException('Token de APIPERU no configurado. Obtén uno gratuitamente en https://apiperu.dev/');
    }

    try {
        // Endpoint principal + respaldo.
        $datos = apiperuRequest([
            'https://apiperu.dev/api/ruc',
            'https://www.apiperu.dev/api/ruc',
        ], $token, ['ruc' => $ruc]);

        if (!is_array($datos)) {
            throw new APIPeruException('Respuesta inválida de APIPERU');
        }

        // Validar respuesta exitosa
        if (isset($datos['success']) && !$datos['success']) {
            $mensaje = $datos['message'] ?? 'RUC no encontrado';
            throw new APIPeruException($mensaje);
        }

        if (!isset($datos['data'])) {
            throw new APIPeruException('Formato de respuesta inesperado');
        }

        $data = $datos['data'];

        return [
            'ok' => true,
            'ruc' => $ruc,
            'razonSocial' => trim((string)($data['razon_social'] ?? $data['nombre_o_razon_social'] ?? '')),
            'nombreComercial' => trim((string)($data['nombre_comercial'] ?? '')),
            'tipo' => isset($data['tipo']) ? strtoupper($data['tipo']) : '',
            'estado' => isset($data['estado']) ? strtoupper($data['estado']) : 'DESCONOCIDO',
            'direccion' => trim((string)($data['direccion_completa'] ?? $data['direccion'] ?? '')),
            'telefonos' => $data['telefonos'] ?? [],
            'correos' => $data['correos'] ?? [],
        ];

    } catch (Exception $e) {
        throw new APIPeruException('Consulta RUC fallida: ' . $e->getMessage());
    }
}

