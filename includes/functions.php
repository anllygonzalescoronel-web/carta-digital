<?php
require_once __DIR__ . '/db.php';

/**
 * Devuelve toda la configuración como arreglo clave => valor
 */
function getConfig(): array {
    static $config = null;
    if ($config === null) {
        $stmt = getDB()->query('SELECT clave, valor FROM configuracion');
        $config = [];
        foreach ($stmt->fetchAll() as $row) {
            $config[$row['clave']] = $row['valor'];
        }
    }
    return $config;
}

function cfg(string $clave, $default = '') {
    $config = getConfig();
    return $config[$clave] ?? $default;
}

function guardarConfig(string $clave, string $valor): void {
    $stmt = getDB()->prepare(
        'INSERT INTO configuracion (clave, valor) VALUES (:clave, :valor)
         ON DUPLICATE KEY UPDATE valor = :valor2'
    );
    $stmt->execute(['clave' => $clave, 'valor' => $valor, 'valor2' => $valor]);
}

function asegurarTablaPopupsFrontend(): void {
    static $asegurada = false;
    if ($asegurada) {
        return;
    }

    getDB()->exec(
        "CREATE TABLE IF NOT EXISTS frontend_popups (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre VARCHAR(150) NOT NULL,
            titulo VARCHAR(190) DEFAULT NULL,
            tipo_contenido ENUM('texto','html') NOT NULL DEFAULT 'texto',
            contenido LONGTEXT DEFAULT NULL,
            css_custom LONGTEXT DEFAULT NULL,
            js_custom LONGTEXT DEFAULT NULL,
            mostrar_una_vez TINYINT(1) NOT NULL DEFAULT 0,
            orden INT NOT NULL DEFAULT 0,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $asegurada = true;
}

function obtenerPopupsFrontendActivos(): array {
    asegurarTablaPopupsFrontend();
    return getDB()->query('SELECT * FROM frontend_popups WHERE activo = 1 ORDER BY orden ASC, id DESC')->fetchAll();
}

function normalizarTelefonoCliente(string $telefono): string {
    $soloDigitos = preg_replace('/\D+/', '', $telefono);
    if ($soloDigitos === null) {
        return '';
    }

    if (strlen($soloDigitos) >= 9) {
        return substr($soloDigitos, -9);
    }

    return $soloDigitos;
}

function obtenerResumenFidelizacionCliente(?string $telefono): array {
    $telefonoNormalizado = normalizarTelefonoCliente((string)$telefono);
    $resumenBase = [
        'valido' => false,
        'telefono' => $telefonoNormalizado,
        'pedidos_totales' => 0,
        'total_gastado' => 0.0,
        'ticket_promedio' => 0.0,
        'nivel' => 'Nuevo',
        'progreso_actual' => 0,
        'objetivo_premio' => 3,
        'faltan_para_premio' => 3,
        'producto_favorito' => '',
        'ultima_compra' => null,
        'mensaje_principal' => 'Haz tu primer pedido y empieza a construir tu historial.',
        'mensaje_secundario' => 'Cuando vuelvas desde la web, te mostraremos tu progreso y tus favoritos.',
    ];

    if ($telefonoNormalizado === '' || $telefonoNormalizado === '999999999') {
        return $resumenBase;
    }

    $db = getDB();
    $telefonoExpr = "RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(cliente_telefono, '+', ''), ' ', ''), '-', ''), '(', ''), ')', ''), 9)";
    $estadosValidos = "('pagado','en_preparacion','en_camino','entregado')";

    $stmtResumen = $db->prepare(
        "SELECT COUNT(*) AS pedidos_totales,
                COALESCE(SUM(total), 0) AS total_gastado,
                COALESCE(AVG(total), 0) AS ticket_promedio,
                MAX(creado_en) AS ultima_compra
         FROM pedidos
         WHERE {$telefonoExpr} = :telefono
           AND estado IN {$estadosValidos}"
    );
    $stmtResumen->execute(['telefono' => $telefonoNormalizado]);
    $resumen = $stmtResumen->fetch() ?: [];

    $pedidosTotales = (int)($resumen['pedidos_totales'] ?? 0);
    $totalGastado = (float)($resumen['total_gastado'] ?? 0);
    $ticketPromedio = (float)($resumen['ticket_promedio'] ?? 0);
    $ultimaCompra = $resumen['ultima_compra'] ?? null;

    $stmtFavorito = $db->prepare(
        "SELECT pd.nombre_producto, SUM(pd.cantidad) AS cantidad_total, SUM(pd.subtotal) AS subtotal_total
         FROM pedidos p
         INNER JOIN pedido_detalle pd ON pd.pedido_id = p.id
         WHERE RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(p.cliente_telefono, '+', ''), ' ', ''), '-', ''), '(', ''), ')', ''), 9) = :telefono
           AND p.estado IN {$estadosValidos}
         GROUP BY pd.nombre_producto
         ORDER BY cantidad_total DESC, subtotal_total DESC, pd.nombre_producto ASC
         LIMIT 1"
    );
    $stmtFavorito->execute(['telefono' => $telefonoNormalizado]);
    $favorito = $stmtFavorito->fetch();
    $productoFavorito = (string)($favorito['nombre_producto'] ?? '');

    $objetivoPremio = 3;
    $progresoActual = 0;
    $faltanParaPremio = $objetivoPremio;
    if ($pedidosTotales > 0) {
        $progresoActual = $pedidosTotales % $objetivoPremio;
        if ($progresoActual === 0) {
            $progresoActual = $objetivoPremio;
            $faltanParaPremio = 0;
        } else {
            $faltanParaPremio = $objetivoPremio - $progresoActual;
        }
    }

    if ($pedidosTotales >= 6) {
        $nivel = 'VIP';
    } elseif ($pedidosTotales >= 3) {
        $nivel = 'Fan';
    } elseif ($pedidosTotales >= 1) {
        $nivel = 'Explorador';
    } else {
        $nivel = 'Nuevo';
    }

    if ($pedidosTotales === 0) {
        $mensajePrincipal = 'Haz tu primer pedido y activa tu Club Sabor.';
        $mensajeSecundario = 'Al volver desde la web verás tu progreso, ticket promedio y tu producto favorito.';
    } elseif ($faltanParaPremio === 0) {
        $mensajePrincipal = 'Cerraste un ciclo perfecto de recompra.';
        $mensajeSecundario = $productoFavorito !== ''
            ? 'Tu favorito sigue siendo ' . $productoFavorito . '. Es el mejor momento para repetirlo.'
            : 'Ya estás listo para seguir sumando otro ciclo de pedidos.';
    } else {
        $mensajePrincipal = 'Te falta ' . $faltanParaPremio . ' pedido' . ($faltanParaPremio === 1 ? '' : 's') . ' para completar tu siguiente ciclo.';
        $mensajeSecundario = $productoFavorito !== ''
            ? 'Tu producto favorito es ' . $productoFavorito . '. Vuelve por el y sigue subiendo.'
            : 'Cada compra desde la web alimenta tu historial y personaliza mejor la experiencia.';
    }

    return [
        'valido' => true,
        'telefono' => $telefonoNormalizado,
        'pedidos_totales' => $pedidosTotales,
        'total_gastado' => round($totalGastado, 2),
        'ticket_promedio' => round($ticketPromedio, 2),
        'nivel' => $nivel,
        'progreso_actual' => $progresoActual,
        'objetivo_premio' => $objetivoPremio,
        'faltan_para_premio' => $faltanParaPremio,
        'producto_favorito' => $productoFavorito,
        'ultima_compra' => $ultimaCompra,
        'mensaje_principal' => $mensajePrincipal,
        'mensaje_secundario' => $mensajeSecundario,
    ];
}

function obtenerColumnasTabla(string $tabla): array {
    $stmt = getDB()->prepare(
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabla"
    );
    $stmt->execute(['tabla' => $tabla]);

    $columnas = [];
    foreach ($stmt->fetchAll() as $row) {
        $columnas[$row['COLUMN_NAME']] = true;
    }

    return $columnas;
}

function asegurarTablaClientesWeb(): void {
    static $asegurada = false;
    if ($asegurada) {
        return;
    }

    $db = getDB();
    $db->exec(
        "CREATE TABLE IF NOT EXISTS clientes_web (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre VARCHAR(150) NOT NULL,
            email VARCHAR(190) NOT NULL,
            telefono VARCHAR(30) DEFAULT NULL,
            password_hash VARCHAR(255) DEFAULT NULL,
            google_id VARCHAR(190) DEFAULT NULL,
            avatar_url VARCHAR(255) DEFAULT NULL,
            proveedor ENUM('local','google') NOT NULL DEFAULT 'local',
            email_verificado TINYINT(1) NOT NULL DEFAULT 0,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            ultimo_login_at DATETIME DEFAULT NULL,
            creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_clientes_web_email (email),
            UNIQUE KEY uq_clientes_web_google_id (google_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $columnasPedidos = obtenerColumnasTabla('pedidos');
    if (!isset($columnasPedidos['cliente_id'])) {
        $db->exec("ALTER TABLE pedidos ADD COLUMN cliente_id INT UNSIGNED NULL AFTER id");
    }
    if (!isset($columnasPedidos['origen_cliente'])) {
        $db->exec("ALTER TABLE pedidos ADD COLUMN origen_cliente ENUM('anonimo','cuenta','google') NOT NULL DEFAULT 'anonimo' AFTER cliente_id");
    }

    $columnasClientes = obtenerColumnasTabla('clientes_web');
    if (!isset($columnasClientes['google_id'])) {
        $db->exec("ALTER TABLE clientes_web ADD COLUMN google_id VARCHAR(190) DEFAULT NULL AFTER password_hash");
    }
    if (!isset($columnasClientes['avatar_url'])) {
        $db->exec("ALTER TABLE clientes_web ADD COLUMN avatar_url VARCHAR(255) DEFAULT NULL AFTER google_id");
    }
    if (!isset($columnasClientes['proveedor'])) {
        $db->exec("ALTER TABLE clientes_web ADD COLUMN proveedor ENUM('local','google') NOT NULL DEFAULT 'local' AFTER avatar_url");
    }
    if (!isset($columnasClientes['email_verificado'])) {
        $db->exec("ALTER TABLE clientes_web ADD COLUMN email_verificado TINYINT(1) NOT NULL DEFAULT 0 AFTER proveedor");
    }
    if (!isset($columnasClientes['activo'])) {
        $db->exec("ALTER TABLE clientes_web ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER email_verificado");
    }
    if (!isset($columnasClientes['ultimo_login_at'])) {
        $db->exec("ALTER TABLE clientes_web ADD COLUMN ultimo_login_at DATETIME DEFAULT NULL AFTER activo");
    }

    $asegurada = true;
}

function normalizarEmailCliente(string $email): string {
    return mb_strtolower(trim($email), 'UTF-8');
}

function obtenerClienteWebPorId(int $clienteId): ?array {
    asegurarTablaClientesWeb();
    $stmt = getDB()->prepare('SELECT * FROM clientes_web WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $clienteId]);
    $cliente = $stmt->fetch();
    return $cliente ?: null;
}

function obtenerClienteWebPorEmail(string $email): ?array {
    asegurarTablaClientesWeb();
    $stmt = getDB()->prepare('SELECT * FROM clientes_web WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => normalizarEmailCliente($email)]);
    $cliente = $stmt->fetch();
    return $cliente ?: null;
}

function obtenerClienteWebPorGoogleId(string $googleId): ?array {
    asegurarTablaClientesWeb();
    $stmt = getDB()->prepare('SELECT * FROM clientes_web WHERE google_id = :google_id LIMIT 1');
    $stmt->execute(['google_id' => trim($googleId)]);
    $cliente = $stmt->fetch();
    return $cliente ?: null;
}

function vincularPedidosCliente(int $clienteId, ?string $email, ?string $telefono, ?string $proveedor = 'cuenta'): void {
    asegurarTablaClientesWeb();
    $db = getDB();

    $emailNormalizado = normalizarEmailCliente((string)$email);
    $telefonoNormalizado = normalizarTelefonoCliente((string)$telefono);
    $condiciones = [];
    $params = [
        'cliente_id' => $clienteId,
        'origen_cliente' => $proveedor === 'google' ? 'google' : 'cuenta',
    ];

    if ($emailNormalizado !== '') {
        $condiciones[] = "LOWER(COALESCE(cliente_email, '')) = :email";
        $params['email'] = $emailNormalizado;
    }
    if ($telefonoNormalizado !== '') {
        $condiciones[] = "RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(cliente_telefono, ''), '+', ''), ' ', ''), '-', ''), '(', ''), ')', ''), 9) = :telefono";
        $params['telefono'] = $telefonoNormalizado;
    }

    if (!$condiciones) {
        return;
    }

    $sql = 'UPDATE pedidos SET cliente_id = :cliente_id, origen_cliente = :origen_cliente WHERE cliente_id IS NULL AND (' . implode(' OR ', $condiciones) . ')';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
}

function actualizarUltimoLoginCliente(int $clienteId): void {
    asegurarTablaClientesWeb();
    $stmt = getDB()->prepare('UPDATE clientes_web SET ultimo_login_at = NOW() WHERE id = :id');
    $stmt->execute(['id' => $clienteId]);
}

function actualizarPerfilClienteWeb(int $clienteId, string $nombre, string $telefono, ?string $nuevoPassword = null): array {
    asegurarTablaClientesWeb();

    $nombre = trim($nombre);
    $telefono = trim($telefono);

    if ($nombre === '' || mb_strlen($nombre) < 2) {
        throw new RuntimeException('Ingresa un nombre valido.');
    }

    $soloDigitos = preg_replace('/\D+/', '', $telefono);
    if ($telefono !== '' && ($soloDigitos === null || strlen($soloDigitos) < 6 || strlen($soloDigitos) > 20)) {
        throw new RuntimeException('Ingresa un telefono valido.');
    }

    $cliente = obtenerClienteWebPorId($clienteId);
    if (!$cliente) {
        throw new RuntimeException('Cliente no encontrado.');
    }

    $db = getDB();
    if ($nuevoPassword !== null && $nuevoPassword !== '') {
        if (strlen($nuevoPassword) < 6) {
            throw new RuntimeException('La nueva contrasena debe tener al menos 6 caracteres.');
        }

        $stmt = $db->prepare(
            'UPDATE clientes_web
             SET nombre = :nombre, telefono = :telefono, password_hash = :password_hash
             WHERE id = :id'
        );
        $stmt->execute([
            'nombre' => $nombre,
            'telefono' => $telefono !== '' ? $telefono : null,
            'password_hash' => password_hash($nuevoPassword, PASSWORD_BCRYPT),
            'id' => $clienteId,
        ]);
    } else {
        $stmt = $db->prepare(
            'UPDATE clientes_web
             SET nombre = :nombre, telefono = :telefono
             WHERE id = :id'
        );
        $stmt->execute([
            'nombre' => $nombre,
            'telefono' => $telefono !== '' ? $telefono : null,
            'id' => $clienteId,
        ]);
    }

    $actualizado = obtenerClienteWebPorId($clienteId);
    if (!$actualizado) {
        throw new RuntimeException('No se pudo actualizar el perfil.');
    }

    vincularPedidosCliente($clienteId, $actualizado['email'], $actualizado['telefono'] ?? '', $actualizado['proveedor'] ?? 'cuenta');
    return $actualizado;
}

function obtenerResumenClienteDashboard(int $clienteId): array {
    asegurarTablaClientesWeb();
    $db = getDB();

    $stmtCliente = $db->prepare('SELECT * FROM clientes_web WHERE id = :id LIMIT 1');
    $stmtCliente->execute(['id' => $clienteId]);
    $cliente = $stmtCliente->fetch();
    if (!$cliente) {
        throw new RuntimeException('Cliente no encontrado.');
    }

    $stmtMetricas = $db->prepare(
        "SELECT COUNT(*) AS pedidos_totales,
                COALESCE(SUM(total), 0) AS total_gastado,
                COALESCE(AVG(total), 0) AS ticket_promedio,
                MAX(creado_en) AS ultima_compra,
                SUM(CASE WHEN estado = 'entregado' THEN 1 ELSE 0 END) AS pedidos_entregados
         FROM pedidos
         WHERE cliente_id = :cliente_id"
    );
    $stmtMetricas->execute(['cliente_id' => $clienteId]);
    $metricas = $stmtMetricas->fetch() ?: [];

    $columnasPedidos = obtenerColumnasTabla('pedidos');
    $camposPedidos = ['id', 'codigo', 'cliente_nombre', 'cliente_telefono', 'tipo_entrega', 'metodo_pago', 'total', 'estado', 'creado_en'];
    if (isset($columnasPedidos['comprobante_numero'])) {
        $camposPedidos[] = 'comprobante_numero';
    }
    if (isset($columnasPedidos['comprobante_pdf_url'])) {
        $camposPedidos[] = 'comprobante_pdf_url';
    }
    if (isset($columnasPedidos['comprobante_xml_url'])) {
        $camposPedidos[] = 'comprobante_xml_url';
    }
    if (isset($columnasPedidos['comprobante_cdr_url'])) {
        $camposPedidos[] = 'comprobante_cdr_url';
    }
    if (isset($columnasPedidos['comprobante_id'])) {
        $camposPedidos[] = 'comprobante_id';
    }

    $stmtPedidos = $db->prepare(
        'SELECT ' . implode(', ', $camposPedidos) . '
         FROM pedidos
         WHERE cliente_id = :cliente_id
         ORDER BY creado_en DESC
         LIMIT 10'
    );
    $stmtPedidos->execute(['cliente_id' => $clienteId]);
    $pedidos = $stmtPedidos->fetchAll();

    $comprobantesPorId = [];
    if (isset($columnasPedidos['comprobante_id'])) {
        $idsComprobantes = [];
        foreach ($pedidos as $pedidoTmp) {
            $idComp = (int)($pedidoTmp['comprobante_id'] ?? 0);
            if ($idComp > 0) {
                $idsComprobantes[$idComp] = $idComp;
            }
        }

        if ($idsComprobantes) {
            try {
                $placeholders = implode(',', array_fill(0, count($idsComprobantes), '?'));
                $stmtComp = $db->prepare(
                    "SELECT id, numero_comprobante, pdf_path, xml_path, cdr_path
                     FROM comprobantes_electronicos
                     WHERE id IN ({$placeholders})"
                );
                $stmtComp->execute(array_values($idsComprobantes));
                foreach ($stmtComp->fetchAll() as $comp) {
                    $comprobantesPorId[(int)$comp['id']] = $comp;
                }
            } catch (Throwable $e) {
                $comprobantesPorId = [];
            }
        }
    }

    $stmtFavorito = $db->prepare(
        'SELECT pd.nombre_producto, SUM(pd.cantidad) AS cantidad_total
         FROM pedidos p
         INNER JOIN pedido_detalle pd ON pd.pedido_id = p.id
         WHERE p.cliente_id = :cliente_id
         GROUP BY pd.nombre_producto
         ORDER BY cantidad_total DESC, pd.nombre_producto ASC
         LIMIT 1'
    );
    $stmtFavorito->execute(['cliente_id' => $clienteId]);
    $favorito = $stmtFavorito->fetch();

    $fidelizacion = obtenerResumenFidelizacionCliente((string)($cliente['telefono'] ?? ''));

    return [
        'cliente' => [
            'id' => (int)$cliente['id'],
            'nombre' => (string)$cliente['nombre'],
            'email' => (string)$cliente['email'],
            'telefono' => (string)($cliente['telefono'] ?? ''),
            'proveedor' => (string)($cliente['proveedor'] ?? 'local'),
            'avatar_url' => (string)($cliente['avatar_url'] ?? ''),
            'ultimo_login_at' => $cliente['ultimo_login_at'] ?? null,
            'creado_en' => $cliente['creado_en'] ?? null,
        ],
        'metricas' => [
            'pedidos_totales' => (int)($metricas['pedidos_totales'] ?? 0),
            'pedidos_entregados' => (int)($metricas['pedidos_entregados'] ?? 0),
            'total_gastado' => round((float)($metricas['total_gastado'] ?? 0), 2),
            'ticket_promedio' => round((float)($metricas['ticket_promedio'] ?? 0), 2),
            'ultima_compra' => $metricas['ultima_compra'] ?? null,
            'producto_favorito' => (string)($favorito['nombre_producto'] ?? ''),
        ],
        'fidelizacion' => $fidelizacion,
        'pedidos' => array_map(static function (array $pedido) use ($comprobantesPorId): array {
            $comprobanteId = (int)($pedido['comprobante_id'] ?? 0);
            $comprobanteExt = $comprobanteId > 0 ? ($comprobantesPorId[$comprobanteId] ?? null) : null;
            $numeroComprobante = (string)($pedido['comprobante_numero'] ?? ($comprobanteExt['numero_comprobante'] ?? ''));
            $pdfComprobante = (string)($pedido['comprobante_pdf_url'] ?? ($comprobanteExt['pdf_path'] ?? ''));
            $xmlComprobante = (string)($pedido['comprobante_xml_url'] ?? ($comprobanteExt['xml_path'] ?? ''));
            $cdrComprobante = (string)($pedido['comprobante_cdr_url'] ?? ($comprobanteExt['cdr_path'] ?? ''));

            return [
                'id' => (int)$pedido['id'],
                'codigo' => (string)$pedido['codigo'],
                'cliente_nombre' => (string)$pedido['cliente_nombre'],
                'cliente_telefono' => (string)$pedido['cliente_telefono'],
                'tipo_entrega' => (string)$pedido['tipo_entrega'],
                'metodo_pago' => (string)$pedido['metodo_pago'],
                'total' => (float)$pedido['total'],
                'estado' => (string)$pedido['estado'],
                'creado_en' => (string)$pedido['creado_en'],
                'comprobante_numero' => $numeroComprobante,
                'comprobante_pdf_url' => $pdfComprobante,
                'comprobante_xml_url' => $xmlComprobante,
                'comprobante_cdr_url' => $cdrComprobante,
            ];
        }, $pedidos),
    ];
}

function obtenerResumenAdminClientes(): array {
    asegurarTablaClientesWeb();
    $db = getDB();
    $clientes = $db->query('SELECT * FROM clientes_web ORDER BY creado_en DESC, id DESC')->fetchAll();

    $stmtMetricas = $db->query(
        'SELECT cliente_id, COUNT(*) AS pedidos_totales, COALESCE(SUM(total), 0) AS total_gastado, MAX(creado_en) AS ultima_compra
         FROM pedidos
         WHERE cliente_id IS NOT NULL
         GROUP BY cliente_id'
    );
    $metricasPorCliente = [];
    foreach ($stmtMetricas->fetchAll() as $row) {
        $metricasPorCliente[(int)$row['cliente_id']] = $row;
    }

    $salida = [];
    foreach ($clientes as $cliente) {
        $clienteId = (int)$cliente['id'];
        $metricas = $metricasPorCliente[$clienteId] ?? null;
        $salida[] = [
            'id' => $clienteId,
            'nombre' => (string)$cliente['nombre'],
            'email' => (string)$cliente['email'],
            'telefono' => (string)($cliente['telefono'] ?? ''),
            'proveedor' => (string)($cliente['proveedor'] ?? 'local'),
            'activo' => (int)($cliente['activo'] ?? 1),
            'ultimo_login_at' => $cliente['ultimo_login_at'] ?? null,
            'creado_en' => $cliente['creado_en'] ?? null,
            'pedidos_totales' => (int)($metricas['pedidos_totales'] ?? 0),
            'total_gastado' => round((float)($metricas['total_gastado'] ?? 0), 2),
            'ultima_compra' => $metricas['ultima_compra'] ?? null,
        ];
    }

    return $salida;
}

function obtenerDetalleAdminCliente(int $clienteId): array {
    asegurarTablaClientesWeb();

    $cliente = obtenerClienteWebPorId($clienteId);
    if (!$cliente) {
        throw new RuntimeException('Cliente no encontrado.');
    }

    $dashboard = obtenerResumenClienteDashboard($clienteId);
    $db = getDB();

    $stmtPedidos = $db->prepare(
        'SELECT id, codigo, tipo_entrega, metodo_pago, total, estado, creado_en
         FROM pedidos
         WHERE cliente_id = :cliente_id
         ORDER BY creado_en DESC
         LIMIT 20'
    );
    $stmtPedidos->execute(['cliente_id' => $clienteId]);
    $pedidos = $stmtPedidos->fetchAll();

    $stmtEstado = $db->prepare(
        'SELECT estado, COUNT(*) AS cantidad
         FROM pedidos
         WHERE cliente_id = :cliente_id
         GROUP BY estado'
    );
    $stmtEstado->execute(['cliente_id' => $clienteId]);

    $estados = [];
    foreach ($stmtEstado->fetchAll() as $row) {
        $estados[(string)$row['estado']] = (int)$row['cantidad'];
    }

    return [
        'cliente' => $dashboard['cliente'],
        'metricas' => $dashboard['metricas'],
        'fidelizacion' => $dashboard['fidelizacion'],
        'pedidos' => array_map(static function (array $pedido): array {
            return [
                'id' => (int)$pedido['id'],
                'codigo' => (string)$pedido['codigo'],
                'tipo_entrega' => (string)$pedido['tipo_entrega'],
                'metodo_pago' => (string)$pedido['metodo_pago'],
                'total' => round((float)$pedido['total'], 2),
                'estado' => (string)$pedido['estado'],
                'creado_en' => (string)$pedido['creado_en'],
            ];
        }, $pedidos),
        'estados' => $estados,
    ];
}

function formatoPrecio($numero): string {
    return 'S/ ' . number_format((float)$numero, 2);
}

function generarCodigoPedido(): string {
    return 'PED-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -5));
}

/**
 * Sube una imagen a la carpeta indicada y devuelve el nombre de archivo generado.
 * Devuelve null si no se subió nada.
 */
function subirImagen(string $inputName, string $carpetaDestino): ?string {
    if (empty($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $archivo = $_FILES[$inputName];
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Error al subir el archivo (código ' . $archivo['error'] . ')');
    }
    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $extensionesPermitidas, true)) {
        throw new RuntimeException('Formato de imagen no permitido. Usa jpg, png o webp.');
    }
    if ($archivo['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('La imagen supera los 5MB permitidos.');
    }
    $nombreNuevo = uniqid('img_', true) . '.' . $ext;
    $rutaDestino = rtrim($carpetaDestino, '/') . '/' . $nombreNuevo;
    if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        throw new RuntimeException('No se pudo guardar la imagen en el servidor.');
    }
    return $nombreNuevo;
}

/**
 * Sube un archivo con extensiones permitidas y devuelve el nombre generado.
 */
function subirArchivoSeguro(string $inputName, string $carpetaDestino, array $extensionesPermitidas, int $tamanoMaximoBytes = 5242880): ?string {
    if (empty($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $archivo = $_FILES[$inputName];
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Error al subir el archivo (código ' . $archivo['error'] . ')');
    }

    $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $permitidas = array_map('strtolower', $extensionesPermitidas);
    if (!in_array($ext, $permitidas, true)) {
        throw new RuntimeException('Formato no permitido. Permitidos: ' . implode(', ', $permitidas));
    }

    if ($archivo['size'] > $tamanoMaximoBytes) {
        throw new RuntimeException('El archivo supera el tamaño máximo permitido.');
    }

    $nombreNuevo = uniqid('file_', true) . '.' . $ext;
    $rutaDestino = rtrim($carpetaDestino, '/') . '/' . $nombreNuevo;

    if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        throw new RuntimeException('No se pudo guardar el archivo en el servidor.');
    }

    return $nombreNuevo;
}

function jsonResponse($data, int $status = 200): void {
    if (ob_get_level()) ob_clean();
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function limpiar(string $texto): string {
    return htmlspecialchars(trim($texto), ENT_QUOTES, 'UTF-8');
}

/**
 * Convierte cantidad entre unidades de medida
 * @param float $cantidad Valor a convertir
 * @param string $desde Unidad origen (kg, g, l, ml, m, cm, unidad, porcion)
 * @param string $hacia Unidad destino
 * @return float Cantidad convertida
 */
function convertirUnidad(float $cantidad, string $desde, string $hacia): float {
    if ($desde === $hacia || $cantidad <= 0) return $cantidad;

    // Tabla de conversiones (todo a la unidad "base")
    // kg, g: base = g (gramos)
    // l, ml: base = ml (mililitros)
    // m, cm: base = cm (centímetros)
    // unidad, porcion: no tienen conversión

    $grupos = [
        'peso'    => ['kg' => 1000, 'g' => 1],        // 1 kg = 1000 g
        'volumen' => ['l' => 1000, 'ml' => 1],         // 1 l = 1000 ml
        'longitud'=> ['m' => 100, 'cm' => 1],          // 1 m = 100 cm
    ];

    $grupo_desde = null;
    $grupo_hacia = null;
    foreach ($grupos as $g => $unidades) {
        if (isset($unidades[$desde])) $grupo_desde = $g;
        if (isset($unidades[$hacia])) $grupo_hacia = $g;
    }

    // Si no están en el mismo grupo o alguno no existe, devolveremos sin convertir
    if ($grupo_desde !== $grupo_hacia) return $cantidad;

    // Normalizar a la unidad base del grupo
    $factor_desde = $grupos[$grupo_desde][$desde];
    $factor_hacia = $grupos[$grupo_desde][$hacia];

    // cantidad en desde → cantidad base → cantidad en hacia
    $en_base = $cantidad * $factor_desde;
    $resultado = $en_base / $factor_hacia;

    return round($resultado, 4);  // Redondear a 4 decimales
}
