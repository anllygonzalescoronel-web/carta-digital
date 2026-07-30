-- ============================================
-- Carta Digital - Facturación Electrónica Híbrida
-- Soporta: SUNAT Nativo + NubeFacT
-- ============================================
USE carta_digital;

-- ============================================
-- TABLA UNIFICADA DE COMPROBANTES (AMBOS DRIVERS)
-- ============================================

CREATE TABLE IF NOT EXISTS facturacion_comprobantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    
    -- Información del comprobante
    tipo_comprobante ENUM('boleta','factura') NOT NULL,
    driver VARCHAR(20) NOT NULL COMMENT 'native|nubefact',
    serie VARCHAR(10) NOT NULL,
    correlativo INT NOT NULL,
    numero_comprobante VARCHAR(30) NOT NULL UNIQUE,
    
    -- Información del cliente
    tipo_documento ENUM('dni','ruc','sin_documento') NOT NULL,
    numero_documento VARCHAR(20) NOT NULL,
    
    -- Estado según driver
    estado ENUM('pendiente','procesando','aceptado','observado','rechazado','error') NOT NULL DEFAULT 'pendiente',
    
    -- Para SUNAT Nativo
    sunat_codigo VARCHAR(20) DEFAULT NULL,
    sunat_descripcion VARCHAR(500) DEFAULT NULL,
    sunat_ticket VARCHAR(100) DEFAULT NULL,
    
    -- Para NubeFacT
    nubefact_respuesta LONGTEXT DEFAULT NULL COMMENT 'JSON Response de NubeFacT',
    
    -- Archivos generados
    xml_path VARCHAR(255) DEFAULT NULL,
    cdr_path VARCHAR(255) DEFAULT NULL,
    pdf_path VARCHAR(255) DEFAULT NULL,
    xml_hash VARCHAR(128) DEFAULT NULL,
    
    -- Payload y respuesta
    payload_json LONGTEXT DEFAULT NULL COMMENT 'JSON enviado a API/SUNAT',
    response_json LONGTEXT DEFAULT NULL COMMENT 'Respuesta completa de API',
    
    -- Control de reintentos
    intentos_envio INT DEFAULT 0,
    ultimo_intento DATETIME DEFAULT NULL,
    enviado_en DATETIME DEFAULT NULL,
    respondido_en DATETIME DEFAULT NULL,
    
    -- Error y debugging
    error_detalle TEXT DEFAULT NULL,
    debug_log LONGTEXT DEFAULT NULL,
    
    -- Timestamps
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    UNIQUE KEY uq_numero_comprobante (numero_comprobante),
    KEY idx_pedido (pedido_id),
    KEY idx_estado (estado),
    KEY idx_driver (driver),
    KEY idx_numero_documento (numero_documento),
    KEY idx_serie_correlativo (serie, correlativo),
    
    CONSTRAINT fk_comprobante_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE SECUENCIAS DE COMPROBANTES (AMBOS DRIVERS)
-- ============================================

CREATE TABLE IF NOT EXISTS facturacion_secuencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver VARCHAR(20) NOT NULL COMMENT 'native|nubefact',
    serie VARCHAR(10) NOT NULL,
    tipo_comprobante TINYINT NOT NULL COMMENT '1=factura|2=boleta',
    ultimo_numero INT NOT NULL DEFAULT 0,
    
    UNIQUE KEY uq_driver_serie (driver, serie),
    KEY idx_driver (driver),
    
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Inicializar secuencias para ambos drivers
INSERT IGNORE INTO facturacion_secuencias (driver, serie, tipo_comprobante, ultimo_numero) VALUES
('native', 'B001', 2, 0),
('native', 'F001', 1, 0),
('nubefact', 'BBB1', 2, 0),
('nubefact', 'FFF1', 1, 0);

-- ============================================
-- TABLA DE CONFIGURACIÓN ESPECÍFICA POR DRIVER
-- ============================================

CREATE TABLE IF NOT EXISTS facturacion_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver VARCHAR(20) NOT NULL,
    clave VARCHAR(100) NOT NULL,
    valor TEXT,
    
    UNIQUE KEY uq_driver_clave (driver, clave),
    KEY idx_driver (driver),
    
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Configuración inicial para SUNAT Nativo
INSERT IGNORE INTO facturacion_config (driver, clave, valor) VALUES
('native', 'habilitado', '1'),
('native', 'modo', 'demo'),
('native', 'ruc_emisor', ''),
('native', 'razon_social', ''),
('native', 'nombre_comercial', ''),
('native', 'usuario_sol', ''),
('native', 'clave_sol', ''),
('native', 'certificado_path', ''),
('native', 'certificado_clave', ''),
('native', 'direccion', ''),
('native', 'ubigeo', '150131'),
('native', 'distrito', 'LIMA'),
('native', 'provincia', 'LIMA'),
('native', 'departamento', 'LIMA'),
('native', 'serie_boleta', 'B001'),
('native', 'serie_factura', 'F001');

-- Configuración inicial para NubeFacT
INSERT IGNORE INTO facturacion_config (driver, clave, valor) VALUES
('nubefact', 'habilitado', '0'),
('nubefact', 'ruta_api', ''),
('nubefact', 'token', ''),
('nubefact', 'serie_boleta', 'BBB1'),
('nubefact', 'serie_factura', 'FFF1');

-- ============================================
-- TABLA DE REGISTRO DE ERRORES DE FACTURACIÓN
-- ============================================

CREATE TABLE IF NOT EXISTS facturacion_error_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT DEFAULT NULL,
    driver VARCHAR(20) NOT NULL,
    tipo_error VARCHAR(50) NOT NULL,
    mensaje TEXT NOT NULL,
    detalles LONGTEXT DEFAULT NULL,
    resuelto TINYINT DEFAULT 0,
    fecha_resolucion DATETIME DEFAULT NULL,
    
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_pedido (pedido_id),
    KEY idx_driver (driver),
    KEY idx_resuelto (resuelto),
    
    CONSTRAINT fk_error_log_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================
-- AGREGAR COLUMNAS A TABLA PEDIDOS
-- ============================================

ALTER TABLE pedidos
    ADD COLUMN IF NOT EXISTS cliente_dni VARCHAR(20) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS cliente_email VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS facturacion_driver VARCHAR(20) DEFAULT 'native' COMMENT 'native|nubefact',
    ADD COLUMN IF NOT EXISTS facturacion_estado VARCHAR(30) DEFAULT 'pendiente' COMMENT 'pendiente|procesando|aceptado|rechazado|error',
    ADD COLUMN IF NOT EXISTS facturacion_error TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS facturacion_fecha DATETIME DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS facturacion_intento INT DEFAULT 0;

-- ============================================
-- AGREGAR NUEVAS CONFIGURACIONES GENERALES
-- ============================================

INSERT IGNORE INTO configuracion (clave, valor) VALUES
('facturacion_driver_activo', 'native'),
('facturacion_modo_hibrido', '0'),
('facturacion_reintentos_max', '5'),
('facturacion_timeout_segundos', '30');

-- ============================================
-- VISTA PARA CONSULTAS UNIFICADAS
-- ============================================

DROP VIEW IF EXISTS v_comprobantes_pendientes;
CREATE VIEW v_comprobantes_pendientes AS
SELECT 
    fc.id,
    fc.pedido_id,
    fc.driver,
    fc.numero_comprobante,
    fc.estado,
    fc.intentos_envio,
    fc.ultimo_intento,
    p.codigo as pedido_codigo,
    p.cliente_nombre
FROM facturacion_comprobantes fc
JOIN pedidos p ON fc.pedido_id = p.id
WHERE fc.estado IN ('pendiente', 'procesando', 'error')
AND fc.intentos_envio < 5
ORDER BY fc.ultimo_intento IS NULL DESC, fc.ultimo_intento ASC;

DROP VIEW IF EXISTS v_comprobantes_aceptados;
CREATE VIEW v_comprobantes_aceptados AS
SELECT 
    fc.id,
    fc.pedido_id,
    fc.driver,
    fc.numero_comprobante,
    fc.respondido_en,
    p.codigo as pedido_codigo,
    p.cliente_nombre,
    fc.pdf_path
FROM facturacion_comprobantes fc
JOIN pedidos p ON fc.pedido_id = p.id
WHERE fc.estado = 'aceptado'
ORDER BY fc.respondido_en DESC;

-- ============================================
-- PROCEDIMIENTO PARA OBTENER SIGUIENTE CORRELATIVO
-- ============================================

DELIMITER ;;
DROP PROCEDURE IF EXISTS sp_obtener_siguiente_correlativo;;
CREATE PROCEDURE sp_obtener_siguiente_correlativo(
    IN p_driver VARCHAR(20),
    IN p_serie VARCHAR(10),
    IN p_tipo_comprobante TINYINT,
    OUT p_numero INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_numero = NULL;
    END;

    START TRANSACTION;
    
    -- Obtener con lock
    SELECT ultimo_numero INTO p_numero 
    FROM facturacion_secuencias 
    WHERE driver = p_driver AND serie = p_serie
    FOR UPDATE;
    
    IF p_numero IS NULL THEN
        -- Crear si no existe
        INSERT INTO facturacion_secuencias (driver, serie, tipo_comprobante, ultimo_numero)
        VALUES (p_driver, p_serie, p_tipo_comprobante, 1);
        SET p_numero = 1;
    ELSE
        -- Incrementar
        SET p_numero = p_numero + 1;
        UPDATE facturacion_secuencias 
        SET ultimo_numero = p_numero
        WHERE driver = p_driver AND serie = p_serie;
    END IF;
    
    COMMIT;
END;;
DELIMITER ;

-- ============================================
-- FIN DE MIGRACIÓN
-- ============================================
COMMIT;