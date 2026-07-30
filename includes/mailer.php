<?php
require_once __DIR__ . '/functions.php';

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Verifica si el SMTP está listo para enviar.
 */
function smtpEstaConfigurado(): bool {
    if (cfg('smtp_enabled', '0') !== '1') {
        return false;
    }

    $host = trim((string)cfg('smtp_host', ''));
    $port = (int)cfg('smtp_port', '0');
    $user = trim((string)cfg('smtp_username', ''));
    $pass = trim((string)cfg('smtp_password', ''));
    $from = trim((string)cfg('smtp_from_email', ''));

    if ($host === '' || $port <= 0 || $user === '' || $pass === '' || $from === '') {
        return false;
    }

    return true;
}

/**
 * Resuelve una ruta local desde valor guardado en BD o URL.
 */
function resolverAdjuntoLocal(?string $ruta): ?string {
    $valor = trim((string)$ruta);
    if ($valor === '') {
        return null;
    }

    if (preg_match('/^https?:\/\//i', $valor)) {
        return null;
    }

    $normal = str_replace('\\', '/', $valor);
    $base = dirname(__DIR__);

    // Si ya es absoluta y existe.
    if ((preg_match('/^[A-Za-z]:\//', $normal) || str_starts_with($normal, '/')) && is_file($normal)) {
        return $normal;
    }

    // Rutas relativas frecuentes.
    $candidatas = [
        $base . '/' . ltrim($normal, '/'),
        $base . '/uploads/' . ltrim($normal, '/'),
        $base . '/uploads/sunat/' . ltrim($normal, '/'),
    ];

    foreach ($candidatas as $file) {
        if (is_file($file)) {
            return $file;
        }
    }

    return null;
}

/**
 * Descarga adjunto remoto para anexarlo por contenido.
 */
function descargarAdjuntoRemoto(string $url, string $nombreSugerido, int $timeoutSegundos = 10): ?array {
    if (!preg_match('/^https?:\/\//i', $url)) {
        return null;
    }

    $ctx = stream_context_create([
        'http' => [
            'timeout' => max(3, min(20, $timeoutSegundos)),
            'follow_location' => 1,
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $content = @file_get_contents($url, false, $ctx);
    if ($content === false || $content === '') {
        return null;
    }

    // Limitar adjuntos remotos a 10MB para no saturar memoria.
    if (strlen($content) > 10 * 1024 * 1024) {
        return null;
    }

    return [
        'content' => $content,
        'name' => $nombreSugerido,
    ];
}

/**
 * Envía correo de agradecimiento con adjuntos de comprobante si existen.
 */
function enviarCorreoCompraCliente(array $ctx): array {
    if (!smtpEstaConfigurado()) {
        return ['ok' => false, 'mensaje' => 'SMTP no configurado'];
    }

    $to = trim((string)($ctx['cliente_email'] ?? ''));
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'mensaje' => 'Email de cliente no válido'];
    }

    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload)) {
        return ['ok' => false, 'mensaje' => 'Autoload de Composer no encontrado'];
    }
    require_once $autoload;

    if (!class_exists(PHPMailer::class)) {
        return ['ok' => false, 'mensaje' => 'PHPMailer no instalado'];
    }

    $host = trim((string)cfg('smtp_host', ''));
    $port = (int)cfg('smtp_port', '587');
    $user = trim((string)cfg('smtp_username', ''));
    $pass = trim((string)cfg('smtp_password', ''));
    $secure = strtolower(trim((string)cfg('smtp_secure', 'tls')));
    $fromEmail = trim((string)cfg('smtp_from_email', ''));
    $fromName = trim((string)cfg('smtp_from_name', cfg('nombre_negocio', 'Carta Digital')));
    $timeout = max(5, min(120, (int)cfg('smtp_timeout', '15')));

    $nombreCliente = trim((string)($ctx['cliente_nombre'] ?? 'Cliente'));
    $codigoPedido = trim((string)($ctx['codigo'] ?? ''));
    $numeroComprobante = trim((string)($ctx['numero_comprobante'] ?? ''));
    $total = (float)($ctx['total'] ?? 0);
    $negocio = trim((string)cfg('nombre_negocio', 'Carta Digital'));

    $asunto = 'Gracias por tu compra - Pedido ' . ($codigoPedido !== '' ? $codigoPedido : date('YmdHis'));
    $cuerpoHtml = '<div style="font-family:Arial,sans-serif;line-height:1.5;color:#1f2937">'
        . '<h2 style="margin:0 0 10px">Gracias por tu compra</h2>'
        . '<p>Hola <strong>' . htmlspecialchars($nombreCliente, ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
        . '<p>Gracias por tu pedido en <strong>' . htmlspecialchars($negocio, ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
        . ($codigoPedido !== '' ? '<p><strong>Código de pedido:</strong> ' . htmlspecialchars($codigoPedido, ENT_QUOTES, 'UTF-8') . '</p>' : '')
        . ($numeroComprobante !== '' ? '<p><strong>Comprobante:</strong> ' . htmlspecialchars($numeroComprobante, ENT_QUOTES, 'UTF-8') . '</p>' : '')
        . '<p><strong>Total:</strong> S/ ' . number_format($total, 2) . '</p>'
        . '<p>Adjuntamos tus archivos de comprobante disponibles (PDF, XML y CDR).</p>'
        . '<p>Gracias por preferirnos.</p>'
        . '</div>';

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = $port;
        $mail->SMTPAuth = true;
        $mail->Username = $user;
        $mail->Password = $pass;
        $mail->Timeout = $timeout;
        $mail->CharSet = 'UTF-8';

        if ($secure === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($secure === 'none') {
            $mail->SMTPSecure = false;
            $mail->SMTPAutoTLS = false;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->setFrom($fromEmail, $fromName !== '' ? $fromName : 'Carta Digital');
        $mail->addAddress($to, $nombreCliente !== '' ? $nombreCliente : null);

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $cuerpoHtml;
        $mail->AltBody = "Gracias por tu compra. Pedido: {$codigoPedido}. Total: S/ " . number_format($total, 2);

        $adjuntos = [
            'pdf' => $ctx['comprobante_pdf'] ?? null,
            'xml' => $ctx['comprobante_xml'] ?? null,
            'cdr' => $ctx['comprobante_cdr'] ?? null,
            'pdf_path' => $ctx['comprobante_pdf_path'] ?? null,
            'xml_path' => $ctx['comprobante_xml_path'] ?? null,
            'cdr_path' => $ctx['comprobante_cdr_path'] ?? null,
        ];

        $yaAdjuntos = [];
        foreach ($adjuntos as $key => $ruta) {
            $rutaStr = is_string($ruta) ? trim($ruta) : '';
            if ($rutaStr === '') {
                continue;
            }

            $archivo = resolverAdjuntoLocal($rutaStr);
            if ($archivo) {
                $real = realpath($archivo);
                if ($real === false || isset($yaAdjuntos[$real])) {
                    continue;
                }

                $mail->addAttachment($archivo);
                $yaAdjuntos[$real] = true;
                continue;
            }

            // Si viene como URL (NubeFacT), adjuntar por contenido.
            if (preg_match('/^https?:\/\//i', $rutaStr)) {
                $ext = pathinfo(parse_url($rutaStr, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION);
                $ext = $ext !== '' ? $ext : ($key === 'cdr' || str_contains($key, 'cdr') ? 'zip' : ($key === 'xml' || str_contains($key, 'xml') ? 'xml' : 'pdf'));
                $nombre = 'comprobante_' . preg_replace('/[^a-z0-9_]/i', '_', $key) . '.' . strtolower($ext);
                $descargado = descargarAdjuntoRemoto($rutaStr, $nombre, $timeout);
                if ($descargado && !empty($descargado['content']) && !empty($descargado['name'])) {
                    $firma = sha1($descargado['name'] . ':' . strlen($descargado['content']));
                    if (!isset($yaAdjuntos[$firma])) {
                        $mail->addStringAttachment($descargado['content'], $descargado['name']);
                        $yaAdjuntos[$firma] = true;
                    }
                }
            }
        }

        $mail->send();
        return ['ok' => true, 'mensaje' => 'Correo enviado'];
    } catch (PHPMailerException $e) {
        return ['ok' => false, 'mensaje' => 'Error SMTP: ' . $e->getMessage()];
    } catch (Throwable $e) {
        return ['ok' => false, 'mensaje' => 'Error enviando correo: ' . $e->getMessage()];
    }
}
