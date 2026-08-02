<?php
require_once __DIR__ . '/functions.php';

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

function ensureFacturacionSchema(PDO $db): void {
    // Tabla de correlativos para NubeFacT y flujo nativo
    $db->exec(
        "CREATE TABLE IF NOT EXISTS comprobante_correlativo (
            serie VARCHAR(10) PRIMARY KEY,
            tipo_comprobante TINYINT NOT NULL DEFAULT 2,
            ultimo_numero INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB"
    );

    $db->exec(
        "CREATE TABLE IF NOT EXISTS comprobantes_electronicos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pedido_id INT NOT NULL,
            tipo_comprobante ENUM('boleta','factura') NOT NULL,
            serie VARCHAR(10) NOT NULL,
            correlativo INT NOT NULL,
            numero_comprobante VARCHAR(30) NOT NULL,
            tipo_documento ENUM('dni','ruc') NOT NULL,
            numero_documento VARCHAR(20) NOT NULL,
            estado_sunat ENUM('pendiente_configuracion','pendiente_envio','aceptado','observado','rechazado','error') NOT NULL DEFAULT 'pendiente_configuracion',
            sunat_codigo VARCHAR(20) DEFAULT NULL,
            sunat_descripcion VARCHAR(500) DEFAULT NULL,
            sunat_ticket VARCHAR(100) DEFAULT NULL,
            xml_path VARCHAR(255) DEFAULT NULL,
            cdr_path VARCHAR(255) DEFAULT NULL,
            pdf_path VARCHAR(255) DEFAULT NULL,
            xml_hash VARCHAR(128) DEFAULT NULL,
            cdr_response_json LONGTEXT DEFAULT NULL,
            intentos_envio INT NOT NULL DEFAULT 0,
            enviado_en DATETIME DEFAULT NULL,
            respondido_en DATETIME DEFAULT NULL,
            payload_json LONGTEXT DEFAULT NULL,
            error_detalle TEXT DEFAULT NULL,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_comprobante_numero (numero_comprobante),
            KEY idx_comprobantes_pedido (pedido_id),
            KEY idx_comprobantes_estado (estado_sunat),
            CONSTRAINT fk_comprobantes_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB"
    );

    $columnasPedidos = [
        'tipo_comprobante' => "ALTER TABLE pedidos ADD COLUMN tipo_comprobante ENUM('boleta','factura') DEFAULT NULL AFTER cliente_telefono",
        'tipo_documento' => "ALTER TABLE pedidos ADD COLUMN tipo_documento ENUM('dni','ruc') DEFAULT NULL AFTER tipo_comprobante",
        'numero_documento' => "ALTER TABLE pedidos ADD COLUMN numero_documento VARCHAR(20) DEFAULT NULL AFTER tipo_documento",
        'comprobante_serie' => "ALTER TABLE pedidos ADD COLUMN comprobante_serie VARCHAR(10) DEFAULT NULL AFTER numero_documento",
        'comprobante_correlativo' => "ALTER TABLE pedidos ADD COLUMN comprobante_correlativo INT DEFAULT NULL AFTER comprobante_serie",
        'comprobante_numero' => "ALTER TABLE pedidos ADD COLUMN comprobante_numero VARCHAR(30) DEFAULT NULL AFTER comprobante_correlativo",
        'comprobante_id' => "ALTER TABLE pedidos ADD COLUMN comprobante_id INT DEFAULT NULL AFTER comprobante_numero",
        'sunat_estado' => "ALTER TABLE pedidos ADD COLUMN sunat_estado VARCHAR(40) DEFAULT NULL AFTER comprobante_id",
        'sunat_mensaje' => "ALTER TABLE pedidos ADD COLUMN sunat_mensaje VARCHAR(500) DEFAULT NULL AFTER sunat_estado"
    ];

    foreach ($columnasPedidos as $columna => $sql) {
        if (!facturacionColumnaExiste($db, 'pedidos', $columna)) {
            $db->exec($sql);
        }
    }

    if (!facturacionColumnaExiste($db, 'pedidos', 'comprobante_id')) {
        return;
    }

    if (!facturacionTieneIndice($db, 'pedidos', 'idx_pedidos_comprobante_id')) {
        $db->exec('ALTER TABLE pedidos ADD INDEX idx_pedidos_comprobante_id (comprobante_id)');
    }

    $columnasComprobantes = [
        'pdf_path' => "ALTER TABLE comprobantes_electronicos ADD COLUMN pdf_path VARCHAR(255) DEFAULT NULL AFTER cdr_path",
        'cdr_response_json' => "ALTER TABLE comprobantes_electronicos ADD COLUMN cdr_response_json LONGTEXT DEFAULT NULL AFTER xml_hash"
    ];

    foreach ($columnasComprobantes as $columna => $sql) {
        if (!facturacionColumnaExiste($db, 'comprobantes_electronicos', $columna)) {
            $db->exec($sql);
        }
    }

}

function facturacionColumnaExiste(PDO $db, string $tabla, string $columna): bool {
    $stmt = $db->prepare('SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :tabla AND COLUMN_NAME = :columna');
    $stmt->execute([
        'schema' => DB_NAME,
        'tabla' => $tabla,
        'columna' => $columna,
    ]);
    return (int)$stmt->fetch()['c'] > 0;
}

function facturacionTieneIndice(PDO $db, string $tabla, string $indice): bool {
    $stmt = $db->prepare('SELECT COUNT(*) c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :tabla AND INDEX_NAME = :indice');
    $stmt->execute([
        'schema' => DB_NAME,
        'tabla' => $tabla,
        'indice' => $indice,
    ]);
    return (int)$stmt->fetch()['c'] > 0;
}

function normalizarNumeroDocumento(string $numero): string {
    return preg_replace('/\D/', '', trim($numero));
}

function validarDocumentoCliente(string $tipoComprobante, string $tipoDocumento, string $numeroDocumento): ?string {
    $tipoComprobante = strtolower(trim($tipoComprobante));
    $tipoDocumento = strtolower(trim($tipoDocumento));
    $numeroDocumento = normalizarNumeroDocumento($numeroDocumento);

    if (!in_array($tipoComprobante, ['boleta', 'factura'], true)) {
        return 'Selecciona un tipo de comprobante válido.';
    }

    if ($tipoComprobante === 'factura') {
        if ($tipoDocumento !== 'ruc') {
            return 'La factura solo permite RUC.';
        }
        if (!preg_match('/^\d{11}$/', $numeroDocumento)) {
            return 'Para factura, el RUC debe tener 11 dígitos.';
        }
        return null;
    }

    if (!in_array($tipoDocumento, ['dni', 'ruc'], true)) {
        return 'Para boleta debes elegir DNI o RUC.';
    }

    if ($tipoDocumento === 'dni' && !preg_match('/^\d{8}$/', $numeroDocumento)) {
        return 'El DNI debe tener 8 dígitos.';
    }

    if ($tipoDocumento === 'ruc' && !preg_match('/^\d{11}$/', $numeroDocumento)) {
        return 'El RUC debe tener 11 dígitos.';
    }

    return null;
}

function facturacionConfigCompleta(): bool {
    if (strtolower(trim((string)cfg('facturacion_driver', 'native'))) !== 'native') {
        return false;
    }

    $requeridos = [
        'sunat_ruc_emisor',
        'sunat_usuario_sol',
        'sunat_clave_sol',
        'sunat_certificado_path',
        'sunat_serie_boleta',
        'sunat_serie_factura',
        'sunat_igv_porcentaje',
    ];

    foreach ($requeridos as $clave) {
        if (trim((string)cfg($clave, '')) === '') {
            return false;
        }
    }
    return true;
}

function facturacionObtenerSerie(string $tipoComprobante): string {
    if ($tipoComprobante === 'factura') {
        return strtoupper(trim((string)cfg('sunat_serie_factura', 'F001')));
    }
    return strtoupper(trim((string)cfg('sunat_serie_boleta', 'B001')));
}

function facturacionSiguienteCorrelativo(PDO $db, string $tipoComprobante): int {
    $clave = $tipoComprobante === 'factura' ? 'sunat_correlativo_factura' : 'sunat_correlativo_boleta';

    $stmt = $db->prepare('SELECT valor FROM configuracion WHERE clave = :clave FOR UPDATE');
    $stmt->execute(['clave' => $clave]);
    $fila = $stmt->fetch();

    if (!$fila) {
        $correlativo = 1;
        $ins = $db->prepare('INSERT INTO configuracion (clave, valor) VALUES (:clave, :valor)');
        $ins->execute(['clave' => $clave, 'valor' => '2']);
        return $correlativo;
    }

    $actual = max((int)$fila['valor'], 1);
    $nuevo = $actual + 1;
    $upd = $db->prepare('UPDATE configuracion SET valor = :valor WHERE clave = :clave');
    $upd->execute(['valor' => (string)$nuevo, 'clave' => $clave]);
    return $actual;
}

function facturacionNumeroComprobante(string $serie, int $correlativo): string {
    return strtoupper($serie) . '-' . str_pad((string)$correlativo, 8, '0', STR_PAD_LEFT);
}

function registrarComprobanteElectronicoDesdePedido(PDO $db, int $pedidoId): ?array {
    $stmtPedido = $db->prepare('SELECT * FROM pedidos WHERE id = :id LIMIT 1');
    $stmtPedido->execute(['id' => $pedidoId]);
    $pedido = $stmtPedido->fetch();

    if (!$pedido) {
        throw new RuntimeException('No se encontró el pedido para generar comprobante.');
    }

    $tipoComprobante = (string)($pedido['tipo_comprobante'] ?? '');
    if (!in_array($tipoComprobante, ['boleta', 'factura'], true)) {
        return null;
    }

    $tipoDocumento = strtolower((string)($pedido['tipo_documento'] ?? ''));
    $numeroDocumento = normalizarNumeroDocumento((string)($pedido['numero_documento'] ?? ''));

    $errorDoc = validarDocumentoCliente($tipoComprobante, $tipoDocumento, $numeroDocumento);
    if ($errorDoc !== null) {
        throw new RuntimeException($errorDoc);
    }

    $serie = facturacionObtenerSerie($tipoComprobante);
    $correlativo = facturacionSiguienteCorrelativo($db, $tipoComprobante);
    $numeroComprobante = facturacionNumeroComprobante($serie, $correlativo);

    $stmtDetalle = $db->prepare('SELECT producto_id, nombre_producto, precio_unitario, cantidad, subtotal FROM pedido_detalle WHERE pedido_id = :pedido_id');
    $stmtDetalle->execute(['pedido_id' => $pedidoId]);
    $detalle = $stmtDetalle->fetchAll();

    $payload = [
        'pedido_codigo' => $pedido['codigo'],
        'tipo_comprobante' => $tipoComprobante,
        'numero_comprobante' => $numeroComprobante,
        'emisor' => [
            'ruc' => trim((string)cfg('sunat_ruc_emisor', '')),
            'razon_social' => trim((string)cfg('sunat_razon_social', cfg('nombre_negocio', ''))),
            'nombre_comercial' => trim((string)cfg('sunat_nombre_comercial', cfg('nombre_negocio', ''))),
            'direccion' => trim((string)cfg('sunat_direccion', cfg('direccion_local', ''))),
            'ubigeo' => trim((string)cfg('sunat_ubigeo', '150101')),
            'distrito' => trim((string)cfg('sunat_distrito', 'LIMA')),
            'provincia' => trim((string)cfg('sunat_provincia', 'LIMA')),
            'departamento' => trim((string)cfg('sunat_departamento', 'LIMA')),
            'cod_pais' => 'PE',
        ],
        'cliente' => [
            'nombre' => $pedido['cliente_nombre'],
            'tipo_documento' => $tipoDocumento,
            'numero_documento' => $numeroDocumento,
        ],
        'totales' => [
            'subtotal' => (float)$pedido['subtotal'],
            'delivery' => (float)$pedido['costo_delivery'],
            'total' => (float)$pedido['total'],
        ],
        'items' => array_map(static function (array $item): array {
            return [
                'producto_id' => (int)$item['producto_id'],
                'nombre' => $item['nombre_producto'],
                'precio_unitario' => (float)$item['precio_unitario'],
                'cantidad' => (int)$item['cantidad'],
                'subtotal' => (float)$item['subtotal'],
            ];
        }, $detalle),
    ];

    $estadoSunat = facturacionConfigCompleta() ? 'pendiente_envio' : 'pendiente_configuracion';

    $stmtComp = $db->prepare(
        'INSERT INTO comprobantes_electronicos
        (pedido_id, tipo_comprobante, serie, correlativo, numero_comprobante, tipo_documento, numero_documento,
         estado_sunat, payload_json)
         VALUES
        (:pedido_id, :tipo_comprobante, :serie, :correlativo, :numero_comprobante, :tipo_documento, :numero_documento,
         :estado_sunat, :payload_json)'
    );
    $stmtComp->execute([
        'pedido_id' => $pedidoId,
        'tipo_comprobante' => $tipoComprobante,
        'serie' => $serie,
        'correlativo' => $correlativo,
        'numero_comprobante' => $numeroComprobante,
        'tipo_documento' => $tipoDocumento,
        'numero_documento' => $numeroDocumento,
        'estado_sunat' => $estadoSunat,
        'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);

    $comprobanteId = (int)$db->lastInsertId();

    $stmtPedidoUpd = $db->prepare(
        'UPDATE pedidos SET
            comprobante_id = :comprobante_id,
            comprobante_serie = :serie,
            comprobante_correlativo = :correlativo,
            comprobante_numero = :numero_comprobante,
            sunat_estado = :sunat_estado,
            sunat_mensaje = :sunat_mensaje
         WHERE id = :pedido_id'
    );
    $stmtPedidoUpd->execute([
        'comprobante_id' => $comprobanteId,
        'serie' => $serie,
        'correlativo' => $correlativo,
        'numero_comprobante' => $numeroComprobante,
        'sunat_estado' => $estadoSunat,
        'sunat_mensaje' => $estadoSunat === 'pendiente_configuracion'
            ? 'Completa la configuración SUNAT para enviar este comprobante.'
            : 'Pendiente de envío a SUNAT.',
        'pedido_id' => $pedidoId,
    ]);

    return [
        'id' => $comprobanteId,
        'numero_comprobante' => $numeroComprobante,
        'estado_sunat' => $estadoSunat,
    ];
}

function enviarComprobanteSunatNativo(PDO $db, int $comprobanteId): array {
    $stmt = $db->prepare('SELECT * FROM comprobantes_electronicos WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $comprobanteId]);
    $comp = $stmt->fetch();
    if (!$comp) {
        throw new RuntimeException('No existe el comprobante a enviar.');
    }

    if (!facturacionConfigCompleta()) {
        actualizarEstadoComprobante($db, $comprobanteId, 'pendiente_configuracion', null, 'Falta configurar datos SUNAT y certificado digital.');
        return [
            'ok' => false,
            'estado' => 'pendiente_configuracion',
            'mensaje' => 'Falta configurar datos SUNAT y certificado digital.',
        ];
    }

    $stmtPedido = $db->prepare('SELECT * FROM pedidos WHERE id = :id LIMIT 1');
    $stmtPedido->execute(['id' => (int)$comp['pedido_id']]);
    $pedido = $stmtPedido->fetch();
    if (!$pedido) {
        throw new RuntimeException('No existe el pedido del comprobante.');
    }

    try {
        $documento = facturacionConstruirDocumentoGreenter($pedido, $comp);
        $see = facturacionCrearSeeGreenter();

        $xmlFirmado = $see->getXmlSigned($documento);
        if (!$xmlFirmado) {
            throw new RuntimeException('No se pudo generar XML firmado del comprobante.');
        }

        $rutaXmlRel = facturacionGuardarArchivoSunat('xml', $comp['numero_comprobante'] . '.xml', $xmlFirmado);
        $rutaPdfRel = facturacionGenerarPdfComprobante($documento, $comp, $pedido);
        if ($rutaPdfRel === null) {
            $rutaPdfRel = facturacionGenerarPdfSimpleDesdePayload($comp, $pedido);
        }
        $xmlHash = facturacionExtraerResumenHashDesdeXml($xmlFirmado);
        if ($xmlHash === '') {
            $xmlHash = hash('sha256', $xmlFirmado);
        }

        $modo = strtolower(trim((string)cfg('sunat_modo', 'beta')));

        if ($modo === 'demo') {
            $mensajeDemo = 'XML y PDF generados en modo demo. Configura beta/producción para envío real.';
            actualizarEstadoComprobante(
                $db,
                $comprobanteId,
                'pendiente_envio',
                null,
                $mensajeDemo,
                null,
                $rutaXmlRel,
                null,
                $rutaPdfRel,
                $xmlHash
            );
            return [
                'ok' => true,
                'estado' => 'pendiente_envio',
                'mensaje' => $mensajeDemo,
            ];
        }

        $resultado = $see->send($documento);

        if (!$resultado || !$resultado->isSuccess()) {
            $error = $resultado ? $resultado->getError() : null;
            $codigoError = $error ? $error->getCode() : null;
            $mensajeError = $error ? $error->getMessage() : 'SUNAT no respondió correctamente.';

            actualizarEstadoComprobante(
                $db,
                $comprobanteId,
                'error',
                $codigoError,
                'Error SUNAT: ' . $mensajeError,
                null,
                $rutaXmlRel,
                null,
                $rutaPdfRel,
                $xmlHash
            );

            return [
                'ok' => false,
                'estado' => 'error',
                'mensaje' => $mensajeError,
            ];
        }

        $cdrZip = method_exists($resultado, 'getCdrZip') ? $resultado->getCdrZip() : null;
        $cdrResponse = method_exists($resultado, 'getCdrResponse') ? $resultado->getCdrResponse() : null;
        $codigoCdr = $cdrResponse ? $cdrResponse->getCode() : null;
        $descripcionCdr = $cdrResponse ? $cdrResponse->getDescription() : 'SUNAT procesó el comprobante.';
        $cdrJson = $cdrResponse ? json_encode([
            'id' => $cdrResponse->getId(),
            'code' => $cdrResponse->getCode(),
            'description' => $cdrResponse->getDescription(),
            'notes' => $cdrResponse->getNotes(),
            'reference' => $cdrResponse->getReference(),
        ], JSON_UNESCAPED_UNICODE) : null;

        $rutaCdrRel = null;
        if (!empty($cdrZip)) {
            $rutaCdrRel = facturacionGuardarArchivoSunat('cdr', 'R-' . $comp['numero_comprobante'] . '.zip', $cdrZip);
        }

        $estado = 'aceptado';
        if ($cdrResponse && method_exists($cdrResponse, 'isAccepted')) {
            if ($cdrResponse->isAccepted()) {
                $estado = ((string)$codigoCdr === '0') ? 'aceptado' : 'observado';
            } else {
                $estado = 'rechazado';
            }
        }

        actualizarEstadoComprobante(
            $db,
            $comprobanteId,
            $estado,
            $codigoCdr,
            (string)$descripcionCdr,
            $cdrJson,
            $rutaXmlRel,
            $rutaCdrRel,
            $rutaPdfRel,
            $xmlHash
        );

        return [
            'ok' => $estado === 'aceptado' || $estado === 'observado',
            'estado' => $estado,
            'mensaje' => (string)$descripcionCdr,
            'codigo' => $codigoCdr,
        ];
    } catch (Throwable $e) {
        actualizarEstadoComprobante($db, $comprobanteId, 'error', null, 'Error en integración SUNAT: ' . $e->getMessage());
        return [
            'ok' => false,
            'estado' => 'error',
            'mensaje' => $e->getMessage(),
        ];
    }
}

function facturacionCrearSeeGreenter(): Greenter\See {
    $see = new Greenter\See();

    $modo = strtolower(trim((string)cfg('sunat_modo', 'beta')));
    if (in_array($modo, ['produccion', 'prod'], true)) {
        $see->setService(Greenter\Ws\Services\SunatEndpoints::FE_PRODUCCION);
    } else {
        $see->setService(Greenter\Ws\Services\SunatEndpoints::FE_BETA);
    }

    $ruc = trim((string)cfg('sunat_ruc_emisor', ''));
    $user = trim((string)cfg('sunat_usuario_sol', ''));
    $pass = trim((string)cfg('sunat_clave_sol', ''));
    $see->setClaveSOL($ruc, $user, $pass);

    $cert = facturacionCargarCertificadoParaGreenter();
    $see->setCertificate($cert);

    return $see;
}

function facturacionProbarConexionSunatNativo(): array {
    if (strtolower(trim((string)cfg('facturacion_driver', 'native'))) !== 'native') {
        return [
            'ok' => false,
            'mensaje' => 'El motor de facturación activo no es SUNAT Nativo.',
        ];
    }

    if (!class_exists('Greenter\\See')) {
        return [
            'ok' => false,
            'mensaje' => 'Greenter no está disponible. Ejecuta composer install/require en este proyecto.',
        ];
    }

    if (!facturacionConfigCompleta()) {
        return [
            'ok' => false,
            'mensaje' => 'Configuración incompleta: revisa RUC, SOL, certificado, series e IGV.',
        ];
    }

    try {
        // 1) Valida que el certificado sea utilizable por Greenter.
        facturacionCargarCertificadoParaGreenter();

        // 2) Verifica conexión SOAP + credenciales con SUNAT.
        $see = facturacionCrearSeeGreenter();
        $status = $see->getStatus('1234567890');

        if ($status && $status->isSuccess()) {
            $code = $status->getCode();
            return [
                'ok' => true,
                'mensaje' => 'Conexión exitosa con SUNAT. Respuesta de prueba recibida' . ($code !== null ? ' (status: ' . $code . ').' : '.'),
            ];
        }

        $error = $status ? $status->getError() : null;
        $codigo = $error ? (string)$error->getCode() : '';
        $msg = $error ? (string)$error->getMessage() : 'No hubo respuesta válida del servicio SUNAT.';

        // Si SUNAT responde error de ticket, la conexión y credenciales generalmente sí funcionaron.
        if (stripos($msg, 'ticket') !== false) {
            return [
                'ok' => true,
                'mensaje' => 'Conexión y autenticación correctas. SUNAT respondió sobre ticket de prueba: ' . $msg,
            ];
        }

        return [
            'ok' => false,
            'mensaje' => 'SUNAT respondió error' . ($codigo !== '' ? ' [' . $codigo . ']' : '') . ': ' . $msg,
        ];
    } catch (Throwable $e) {
        return [
            'ok' => false,
            'mensaje' => 'Falló la prueba de conexión SUNAT: ' . $e->getMessage(),
        ];
    }
}

function facturacionCargarCertificadoParaGreenter(): string {
    $relPath = trim((string)cfg('sunat_certificado_path', ''));
    if ($relPath === '') {
        throw new RuntimeException('No hay certificado SUNAT configurado.');
    }

    $absPath = dirname(__DIR__) . '/' . ltrim(str_replace('\\', '/', $relPath), '/');
    if (!is_file($absPath)) {
        throw new RuntimeException('No se encuentra el archivo de certificado SUNAT.');
    }

    $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
    $contenido = (string)file_get_contents($absPath);

    if (in_array($ext, ['pfx', 'p12'], true)) {
        $pass = trim((string)cfg('sunat_certificado_clave', ''));
        $certs = [];
        if (!openssl_pkcs12_read($contenido, $certs, $pass)) {
            throw new RuntimeException('No se pudo leer el certificado PFX. Verifica la clave del certificado.');
        }

        $priv = trim((string)($certs['pkey'] ?? ''));
        $pub = trim((string)($certs['cert'] ?? ''));
        if ($priv === '' || $pub === '') {
            throw new RuntimeException('El certificado PFX no contiene clave privada o certificado público.');
        }

        return $priv . PHP_EOL . $pub;
    }

    if (strpos($contenido, 'BEGIN PRIVATE KEY') !== false || strpos($contenido, 'BEGIN RSA PRIVATE KEY') !== false) {
        return $contenido;
    }

    throw new RuntimeException('El certificado debe ser PFX o PEM que incluya clave privada.');
}

function facturacionConstruirDocumentoGreenter(array $pedido, array $comp): Greenter\Model\Sale\Invoice {
    $payload = json_decode((string)$comp['payload_json'], true);
    if (!is_array($payload)) {
        throw new RuntimeException('No se encontró payload del comprobante para generar el documento.');
    }

    $igvPct = max((float)cfg('sunat_igv_porcentaje', '18'), 0.0);
    $tipoComp = (string)$comp['tipo_comprobante'];
    $tipoDoc = $tipoComp === 'factura' ? '01' : '03';

    $company = (new Greenter\Model\Company\Company())
        ->setRuc(trim((string)cfg('sunat_ruc_emisor', '')))
        ->setRazonSocial(trim((string)cfg('sunat_razon_social', cfg('nombre_negocio', ''))))
        ->setNombreComercial(trim((string)cfg('sunat_nombre_comercial', cfg('nombre_negocio', ''))))
        ->setAddress(
            (new Greenter\Model\Company\Address())
                ->setUbigueo(trim((string)cfg('sunat_ubigeo', '150101')))
                ->setDepartamento(trim((string)cfg('sunat_departamento', 'LIMA')))
                ->setProvincia(trim((string)cfg('sunat_provincia', 'LIMA')))
                ->setDistrito(trim((string)cfg('sunat_distrito', 'LIMA')))
                ->setDireccion(trim((string)cfg('sunat_direccion', cfg('direccion_local', '-'))))
                ->setCodLocal('0000')
        );

    $tipoDocCliente = ((string)$comp['tipo_documento'] === 'ruc') ? '6' : '1';
    $client = (new Greenter\Model\Client\Client())
        ->setTipoDoc($tipoDocCliente)
        ->setNumDoc((string)$comp['numero_documento'])
        ->setRznSocial((string)$pedido['cliente_nombre']);

    $detalles = [];
    $totalValorVenta = 0.0;
    $totalIgv = 0.0;
    $factor = 1 + ($igvPct / 100);

    $items = $payload['items'] ?? [];
    foreach ($items as $item) {
        $cantidad = max((float)($item['cantidad'] ?? 1), 1.0);
        $mtoPrecioUnitario = round((float)($item['precio_unitario'] ?? 0), 2);
        $subtotalConIgv = round((float)($item['subtotal'] ?? ($mtoPrecioUnitario * $cantidad)), 2);
        $valorVenta = round($subtotalConIgv / $factor, 2);
        $igv = round($subtotalConIgv - $valorVenta, 2);
        $valorUnitario = round($valorVenta / $cantidad, 10);

        $totalValorVenta += $valorVenta;
        $totalIgv += $igv;

        $detalles[] = (new Greenter\Model\Sale\SaleDetail())
            ->setCodProducto((string)($item['producto_id'] ?? '0'))
            ->setUnidad('NIU')
            ->setCantidad($cantidad)
            ->setDescripcion((string)($item['nombre'] ?? 'Producto'))
            ->setMtoValorUnitario($valorUnitario)
            ->setMtoPrecioUnitario($mtoPrecioUnitario)
            ->setMtoBaseIgv($valorVenta)
            ->setPorcentajeIgv($igvPct)
            ->setIgv($igv)
            ->setTipAfeIgv('10')
            ->setTotalImpuestos($igv)
            ->setMtoValorVenta($valorVenta);
    }

    $delivery = round((float)($payload['totales']['delivery'] ?? 0), 2);
    if ($delivery > 0) {
        $valorVenta = round($delivery / $factor, 2);
        $igv = round($delivery - $valorVenta, 2);

        $totalValorVenta += $valorVenta;
        $totalIgv += $igv;

        $detalles[] = (new Greenter\Model\Sale\SaleDetail())
            ->setCodProducto('DELIVERY')
            ->setUnidad('ZZ')
            ->setCantidad(1)
            ->setDescripcion('Servicio de delivery')
            ->setMtoValorUnitario($valorVenta)
            ->setMtoPrecioUnitario($delivery)
            ->setMtoBaseIgv($valorVenta)
            ->setPorcentajeIgv($igvPct)
            ->setIgv($igv)
            ->setTipAfeIgv('10')
            ->setTotalImpuestos($igv)
            ->setMtoValorVenta($valorVenta);
    }

    $totalConIgv = round((float)$pedido['total'], 2);
    $totalValorVenta = round($totalValorVenta, 2);
    $totalIgv = round($totalIgv, 2);
    $subTotal = round($totalValorVenta + $totalIgv, 2);

    $leyenda = facturacionMontoLetras($totalConIgv);

    return (new Greenter\Model\Sale\Invoice())
        ->setUblVersion('2.1')
        ->setTipoOperacion('0101')
        ->setTipoDoc($tipoDoc)
        ->setSerie((string)$comp['serie'])
        ->setCorrelativo((string)$comp['correlativo'])
        ->setFechaEmision(new DateTime())
        ->setTipoMoneda('PEN')
        ->setCompany($company)
        ->setClient($client)
        ->setMtoOperGravadas($totalValorVenta)
        ->setMtoIGV($totalIgv)
        ->setTotalImpuestos($totalIgv)
        ->setValorVenta($totalValorVenta)
        ->setSubTotal($subTotal)
        ->setMtoImpVenta($totalConIgv)
        ->setDetails($detalles)
        ->setLegends([
            (new Greenter\Model\Sale\Legend())
                ->setCode('1000')
                ->setValue($leyenda)
        ]);
}

function facturacionMontoLetras(float $monto): string {
    $enteros = floor($monto);
    $centimos = (int)round(($monto - $enteros) * 100);

    if (class_exists('NumberFormatter')) {
        $fmt = new NumberFormatter('es_PE', NumberFormatter::SPELLOUT);
        $texto = strtoupper((string)$fmt->format($enteros));
        if ($texto !== '') {
            return trim($texto) . ' CON ' . str_pad((string)$centimos, 2, '0', STR_PAD_LEFT) . '/100 SOLES';
        }
    }

    return 'SON ' . number_format($monto, 2, '.', '') . ' SOLES';
}

function facturacionGuardarArchivoSunat(string $subCarpeta, string $nombreArchivo, string $contenido): string {
    $base = dirname(__DIR__) . '/uploads/sunat/' . trim($subCarpeta, '/');
    if (!is_dir($base) && !mkdir($base, 0775, true) && !is_dir($base)) {
        throw new RuntimeException('No se pudo crear la carpeta de archivos SUNAT.');
    }

    $nombreSeguro = preg_replace('/[^A-Za-z0-9._-]/', '_', $nombreArchivo);
    $rutaAbs = $base . '/' . $nombreSeguro;
    if (file_put_contents($rutaAbs, $contenido) === false) {
        throw new RuntimeException('No se pudo guardar archivo SUNAT: ' . $nombreSeguro);
    }

    return 'uploads/sunat/' . trim($subCarpeta, '/') . '/' . $nombreSeguro;
}

function facturacionGenerarPdfComprobante(Greenter\Model\Sale\Invoice $documento, array $comp, array $pedido): ?string {
    if (!class_exists('Dompdf\\Dompdf')) {
        return null;
    }

    try {
        $html = facturacionConstruirHtmlTicket80mm($documento, $comp, $pedido);

        $dompdf = new Dompdf\Dompdf(['isRemoteEnabled' => true]);
        $dompdf->loadHtml($html, 'UTF-8');

        $itemsCount = count($documento->getDetails() ?? []);
        $paperWidth = 226.77; // 80mm en puntos
        $paperHeight = max(900, 620 + ($itemsCount * 42));
        $dompdf->setPaper([0, 0, $paperWidth, $paperHeight]);
        $dompdf->render();

        $pdf = $dompdf->output();
        return facturacionGuardarArchivoSunat('pdf', ((string)($comp['numero_comprobante'] ?? 'comprobante')) . '.pdf', $pdf);
    } catch (Throwable $e) {
        return null;
    }
}

function facturacionConstruirHtmlTicket80mm(Greenter\Model\Sale\Invoice $documento, array $comp, array $pedido): string {
    $e = static function ($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    };

    $tipoLabel = ((string)($comp['tipo_comprobante'] ?? '') === 'factura') ? 'FACTURA DE VENTA ELECTRONICA' : 'BOLETA DE VENTA ELECTRONICA';
    $numeroComp = (string)($comp['numero_comprobante'] ?? ($documento->getSerie() . '-' . $documento->getCorrelativo()));
    $ruc = trim((string)cfg('sunat_ruc_emisor', ''));
    $razon = trim((string)cfg('sunat_razon_social', cfg('nombre_negocio', '')));
    $comercial = trim((string)cfg('sunat_nombre_comercial', ''));
    $direccion = trim((string)cfg('sunat_direccion', cfg('direccion_local', '-')));
    $web = trim((string)cfg('url_publica', ''));
    $resumenHash = trim((string)($comp['xml_hash'] ?? ''));

    $cliente = (string)($pedido['cliente_nombre'] ?? 'VARIOS');
    $tipoDocCli = strtoupper((string)($comp['tipo_documento'] ?? 'DNI'));
    $numDocCli = (string)($comp['numero_documento'] ?? 'VARIOS');
    $fecha = $documento->getFechaEmision();
    $fechaStr = $fecha instanceof DateTimeInterface ? $fecha->format('d-m-Y / H:i:s') : date('d-m-Y / H:i:s');

    $metodoPagoMap = [
        'efectivo' => 'Efectivo',
        'tarjeta' => 'Tarjeta',
        'yape_plin' => 'Yape / Plin',
    ];
    $metodoPago = $metodoPagoMap[(string)($pedido['metodo_pago'] ?? '')] ?? ucfirst((string)($pedido['metodo_pago'] ?? ''));

    $lineasItems = '';
    foreach (($documento->getDetails() ?? []) as $d) {
        $desc = $e((string)$d->getDescripcion());
        $cantidad = number_format((float)$d->getCantidad(), 2);
        $pu = number_format((float)$d->getMtoPrecioUnitario(), 2);
        $importe = number_format((float)$d->getMtoValorVenta() + (float)$d->getTotalImpuestos(), 2);
        $lineasItems .= "<tr>\n"
            . "<td class='qty'>{$cantidad}</td>\n"
            . "<td class='desc'>{$desc}</td>\n"
            . "<td class='num'>{$pu}</td>\n"
            . "<td class='num'>{$importe}</td>\n"
            . "</tr>";
    }

    $opGravada = number_format((float)$documento->getMtoOperGravadas(), 2);
    $igv = number_format((float)$documento->getMtoIGV(), 2);
    $total = number_format((float)$documento->getMtoImpVenta(), 2);
    $leyenda = '';
    foreach (($documento->getLegends() ?? []) as $leg) {
        if ((string)$leg->getCode() === '1000') {
            $leyenda = (string)$leg->getValue();
            break;
        }
    }

    $qrText = facturacionConstruirContenidoQrSunat($documento);
    $qrImg = facturacionGenerarQrSvgDataUri($qrText);

    $consultaUrl = $web !== '' ? rtrim($web, '/') . '/admin/comprobantes.php' : '';

    return "<!doctype html>\n<html><head><meta charset='utf-8'>\n<style>
body{font-family:DejaVu Sans,Arial,sans-serif;font-size:11px;color:#111;margin:0;padding:10px}
.center{text-align:center}
.sep{border-top:1px dashed #000;margin:7px 0}
h1,h2,p{margin:0}
h1{font-size:13px;font-weight:700}
h2{font-size:12px;font-weight:700}
.small{font-size:10px}
.tiny{font-size:9px;color:#444}
table{width:100%;border-collapse:collapse}
th,td{font-size:10px;padding:2px 0;vertical-align:top}
th{font-weight:700;text-transform:uppercase}
.qty{width:18%}
.desc{width:42%;word-break:break-word;padding-right:4px}
.num{width:20%;text-align:right}
.tot td{padding:1px 0}
.tot .label{text-align:left}
.tot .val{text-align:right}
.pay{font-size:12px;font-weight:700}
.qr{margin-top:8px}
.qr img{width:96px;height:96px}
</style></head><body>
<div class='center'>
  <h1>{$e($comercial !== '' ? $comercial : $razon)}</h1>
  <div>{$e($razon)}</div>
  <div><b>R.U.C. {$e($ruc)}</b></div>
  <div class='small'>{$e($direccion)}</div>
  " . ($web !== '' ? "<div class='small'>{$e($web)}</div>" : '') . "
</div>
<div class='sep'></div>
<div class='center'><h2>{$e($tipoLabel)}</h2><h2>{$e($numeroComp)}</h2></div>
<div class='sep'></div>
<div><b>Cliente:</b> {$e($cliente)}</div>
<div><b>Documento:</b> {$e($tipoDocCli)} - {$e($numDocCli)}</div>
<div><b>Direccion:</b> {$e((string)($pedido['direccion'] ?? '-'))}</div>
<div><b>Fecha de emision:</b> {$e($fechaStr)}</div>
<div><b>Moneda:</b> SOLES</div>
<div><b>Atencion:</b> {$e($cliente)}</div>
<div><b>Tipo de pago:</b> Contado</div>
<div><b>Nro referencia:</b> {$e((string)($pedido['referencia'] ?? ''))}</div>
<div><b>Observacion:</b> {$e((string)($pedido['notas'] ?? ''))}</div>
<div class='sep'></div>
<div><b>Metodos de pago:</b> {$e($metodoPago)}</div>
<div class='pay'>" . $e($total) . "</div>
<div class='sep'></div>
<table>
  <thead><tr><th class='qty'>Cant.</th><th class='desc'>Producto</th><th class='num'>P.U.</th><th class='num'>Importe</th></tr></thead>
  <tbody>{$lineasItems}</tbody>
</table>
<div class='sep'></div>
<table class='tot'>
  <tr><td class='label'>Descuento</td><td class='val'>: 0.00</td></tr>
  <tr><td class='label'>Op. Gravada</td><td class='val'>: {$e($opGravada)}</td></tr>
  <tr><td class='label'>Op. Exonerado</td><td class='val'>: 0.00</td></tr>
  <tr><td class='label'>Op. Inafecto</td><td class='val'>: 0.00</td></tr>
  <tr><td class='label'>ICBPER</td><td class='val'>: 0.00</td></tr>
  <tr><td class='label'>I.G.V.</td><td class='val'>: {$e($igv)}</td></tr>
  <tr><td class='label'>Imp. Pagado</td><td class='val'>: {$e($total)}</td></tr>
  <tr><td class='label'>Vuelto</td><td class='val'>: 0.00</td></tr>
  <tr><td class='label'><b>Importe a pagar</b></td><td class='val'><b>: {$e($total)}</b></td></tr>
</table>
<div class='sep'></div>
<div><b>Son:</b> {$e($leyenda)}</div>
" . ($resumenHash !== '' ? "<div class='tiny'><b>Resumen:</b> {$e($resumenHash)}</div>" : '') . "
" . ($qrImg !== '' ? "<div class='center qr'><img src='{$qrImg}' alt='QR'></div>" : '') . "
<div class='center tiny' style='margin-top:5px'>Representacion impresa de la {$e($tipoLabel)}.</div>
" . ($consultaUrl !== '' ? "<div class='center tiny'>Consulta en {$e($consultaUrl)}</div>" : '') . "
<div class='center' style='margin-top:8px'><b>::.GRACIAS POR SU COMPRA.::</b></div>
</body></html>";
}

function facturacionConstruirContenidoQrSunat(Greenter\Model\Sale\Invoice $sale): string {
    $client = $sale->getClient();
    $params = [
        (string)$sale->getCompany()->getRuc(),
        (string)$sale->getTipoDoc(),
        (string)$sale->getSerie(),
        (string)$sale->getCorrelativo(),
        number_format((float)$sale->getMtoIGV(), 2, '.', ''),
        number_format((float)$sale->getMtoImpVenta(), 2, '.', ''),
        $sale->getFechaEmision() instanceof DateTimeInterface ? $sale->getFechaEmision()->format('Y-m-d') : date('Y-m-d'),
        $client ? (string)$client->getTipoDoc() : '',
        $client ? (string)$client->getNumDoc() : '',
    ];

    // Mismo formato usado por Greenter Report: campos separados por | y pipe final.
    return implode('|', $params) . '|';
}

function facturacionExtraerResumenHashDesdeXml(string $xml): string {
    try {
        $doc = new DOMDocument();
        if (!@$doc->loadXML($xml)) {
            return '';
        }

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        $nodes = $xpath->query('//ds:Reference[@URI=""]/ds:DigestValue');
        if ($nodes && $nodes->length > 0) {
            return trim((string)$nodes->item(0)->textContent);
        }

        $alt = $xpath->query('//ds:DigestValue');
        if ($alt && $alt->length > 0) {
            return trim((string)$alt->item(0)->textContent);
        }
    } catch (Throwable $e) {
        return '';
    }

    return '';
}

function facturacionGenerarQrSvgDataUri(string $content): string {
    if (!class_exists('BaconQrCode\\Writer')) {
        return '';
    }

    try {
        $renderer = new BaconQrCode\Renderer\ImageRenderer(
            new BaconQrCode\Renderer\RendererStyle\RendererStyle(160, 0),
            new BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $writer = new BaconQrCode\Writer($renderer);
        $svg = $writer->writeString($content);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    } catch (Throwable $e) {
        return '';
    }
}

function facturacionGenerarPdfSimpleDesdePayload(array $comp, array $pedido): ?string {
    if (!class_exists('Dompdf\\Dompdf')) {
        return null;
    }

    $payload = json_decode((string)($comp['payload_json'] ?? ''), true);
    if (!is_array($payload)) {
        return null;
    }

    $itemsHtml = '';
    foreach (($payload['items'] ?? []) as $item) {
        $nombre = limpiar((string)($item['nombre'] ?? 'Producto'));
        $cantidad = (int)($item['cantidad'] ?? 0);
        $pu = number_format((float)($item['precio_unitario'] ?? 0), 2);
        $sub = number_format((float)($item['subtotal'] ?? 0), 2);
        $itemsHtml .= "<tr><td>{$nombre}</td><td style='text-align:center'>{$cantidad}</td><td style='text-align:right'>S/ {$pu}</td><td style='text-align:right'>S/ {$sub}</td></tr>";
    }

    $numero = limpiar((string)($comp['numero_comprobante'] ?? '-'));
    $tipo = strtoupper(limpiar((string)($comp['tipo_comprobante'] ?? '')));
    $cliente = limpiar((string)($pedido['cliente_nombre'] ?? '-'));
    $doc = strtoupper(limpiar((string)($comp['tipo_documento'] ?? ''))) . ' ' . limpiar((string)($comp['numero_documento'] ?? ''));
    $fecha = limpiar((string)($pedido['creado_en'] ?? date('Y-m-d H:i:s')));
    $subtotal = number_format((float)($pedido['subtotal'] ?? 0), 2);
    $igv = number_format((float)($pedido['total'] - $pedido['subtotal']), 2);
    $total = number_format((float)($pedido['total'] ?? 0), 2);

    $html = "<!doctype html><html><head><meta charset='utf-8'><style>
        body{font-family:DejaVu Sans,Arial,sans-serif;font-size:12px;color:#111}
        h1{font-size:18px;margin:0 0 10px}
        .box{border:1px solid #ddd;border-radius:8px;padding:10px;margin-bottom:10px}
        table{width:100%;border-collapse:collapse}
        th,td{border-bottom:1px solid #eee;padding:6px 4px}
        th{text-align:left;background:#fafafa}
    </style></head><body>
        <h1>{$tipo} {$numero}</h1>
        <div class='box'><b>Cliente:</b> {$cliente}<br><b>Documento:</b> {$doc}<br><b>Fecha:</b> {$fecha}</div>
        <table><thead><tr><th>Descripción</th><th style='text-align:center'>Cant.</th><th style='text-align:right'>P. Unit.</th><th style='text-align:right'>Subtotal</th></tr></thead><tbody>{$itemsHtml}</tbody></table>
        <div class='box' style='margin-top:10px'><b>Subtotal:</b> S/ {$subtotal}<br><b>IGV:</b> S/ {$igv}<br><b>Total:</b> S/ {$total}</div>
        <p style='font-size:11px;color:#555'>Representación impresa del comprobante electrónico.</p>
    </body></html>";

    try {
        $dompdf = new Dompdf\Dompdf(['isRemoteEnabled' => true]);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();
        return facturacionGuardarArchivoSunat('pdf', $numero . '.pdf', $dompdf->output());
    } catch (Throwable $e) {
        return null;
    }
}

function facturacionRegenerarPdfComprobante(PDO $db, int $comprobanteId): array {
    $stmt = $db->prepare('SELECT c.*, p.* FROM comprobantes_electronicos c INNER JOIN pedidos p ON p.id = c.pedido_id WHERE c.id = :id LIMIT 1');
    $stmt->execute(['id' => $comprobanteId]);
    $row = $stmt->fetch();
    if (!$row) {
        return ['ok' => false, 'mensaje' => 'No se encontró el comprobante.'];
    }

    $comp = $row;
    $pedido = $row;

    try {
        $documento = facturacionConstruirDocumentoGreenter($pedido, $comp);
        $pdfPath = facturacionGenerarPdfComprobante($documento, $comp, $pedido);
        if ($pdfPath === null) {
            $pdfPath = facturacionGenerarPdfSimpleDesdePayload($comp, $pedido);
        }

        if ($pdfPath === null) {
            return ['ok' => false, 'mensaje' => 'No se pudo generar PDF. Revisa configuración de Dompdf/Greenter.'];
        }

        $upd = $db->prepare('UPDATE comprobantes_electronicos SET pdf_path = :pdf WHERE id = :id');
        $upd->execute(['pdf' => $pdfPath, 'id' => $comprobanteId]);

        return ['ok' => true, 'mensaje' => 'PDF generado correctamente.', 'pdf_path' => $pdfPath];
    } catch (Throwable $e) {
        return ['ok' => false, 'mensaje' => 'Error al generar PDF: ' . $e->getMessage()];
    }
}

function facturacionObtenerBasePublica(): string {
    $cfgBase = trim((string)cfg('url_publica', ''));
    if ($cfgBase !== '') {
        return rtrim($cfgBase, '/');
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ((string)($_SERVER['SERVER_PORT'] ?? '') === '443');
    $scheme = $https ? 'https' : 'http';
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = rtrim(dirname($scriptName), '/.');

    if (substr($dir, -6) === '/admin') {
        $dir = substr($dir, 0, -6);
    }

    return rtrim($scheme . '://' . $host . ($dir ? '/' . ltrim($dir, '/') : ''), '/');
}

function facturacionObtenerUrlPublicaArchivo(string $rutaRelativa): string {
    $base = facturacionObtenerBasePublica();
    return $base . '/' . ltrim(str_replace('\\', '/', $rutaRelativa), '/');
}

function facturacionConstruirLinkWhatsappPdf(array $comp, string $numeroWhatsapp): ?string {
    $numero = preg_replace('/\D/', '', $numeroWhatsapp);
    if (strlen($numero) < 8) {
        return null;
    }

    $pdfPath = trim((string)($comp['pdf_path'] ?? ''));
    if ($pdfPath === '') {
        return null;
    }

    $urlPdf = facturacionObtenerUrlPublicaArchivo($pdfPath);
    $texto = "Hola, te compartimos tu comprobante electrónico "
        . ((string)($comp['numero_comprobante'] ?? ''))
        . ".\n\n"
        . "Descárgalo aquí: " . $urlPdf;

    return 'https://wa.me/' . $numero . '?text=' . rawurlencode($texto);
}

function actualizarEstadoComprobante(PDO $db, int $comprobanteId, string $estado, ?string $codigo, string $mensaje, ?string $cdrJson = null, ?string $xmlPath = null, ?string $cdrPath = null, ?string $pdfPath = null, ?string $xmlHash = null): void {
    $stmt = $db->prepare(
        'UPDATE comprobantes_electronicos
         SET estado_sunat = :estado,
             sunat_codigo = :codigo,
             sunat_descripcion = :mensaje,
             cdr_response_json = COALESCE(:cdr_response_json, cdr_response_json),
             xml_path = COALESCE(:xml_path, xml_path),
             cdr_path = COALESCE(:cdr_path, cdr_path),
             pdf_path = COALESCE(:pdf_path, pdf_path),
             xml_hash = COALESCE(:xml_hash, xml_hash),
             enviado_en = IF(enviado_en IS NULL, NOW(), enviado_en),
             respondido_en = NOW(),
             intentos_envio = intentos_envio + 1,
             error_detalle = :mensaje
         WHERE id = :id'
    );
    $stmt->execute([
        'estado' => $estado,
        'codigo' => $codigo,
        'mensaje' => $mensaje,
        'cdr_response_json' => $cdrJson,
        'xml_path' => $xmlPath,
        'cdr_path' => $cdrPath,
        'pdf_path' => $pdfPath,
        'xml_hash' => $xmlHash,
        'id' => $comprobanteId,
    ]);

    $stmt2 = $db->prepare(
        'UPDATE pedidos p
         INNER JOIN comprobantes_electronicos c ON c.id = p.comprobante_id
         SET p.sunat_estado = c.estado_sunat,
             p.sunat_mensaje = c.sunat_descripcion
         WHERE c.id = :id'
    );
    $stmt2->execute(['id' => $comprobanteId]);
}
