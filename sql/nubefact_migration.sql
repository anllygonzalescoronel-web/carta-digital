-- ============================================
-- Carta Digital - Integración NubeFacT (SUNAT)
-- Ejecutar después de sql/schema.sql
-- ============================================
USE carta_digital;

-- Correlativo de boletas (evita choques si 2 pedidos pagan casi al mismo tiempo)
CREATE TABLE IF NOT EXISTS comprobante_correlativo (
    serie VARCHAR(4) PRIMARY KEY,
    tipo_comprobante TINYINT NOT NULL DEFAULT 2, -- 1 factura, 2 boleta
    ultimo_numero INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

INSERT IGNORE INTO comprobante_correlativo (serie, tipo_comprobante, ultimo_numero)
VALUES ('BBB1', 2, 0);

-- Datos que faltaban en pedidos para poder facturar
ALTER TABLE pedidos
    ADD COLUMN IF NOT EXISTS cliente_email VARCHAR(150) NULL AFTER cliente_telefono,
    ADD COLUMN IF NOT EXISTS cliente_dni VARCHAR(15) NULL AFTER cliente_email,
    ADD COLUMN IF NOT EXISTS comprobante_serie VARCHAR(4) NULL,
    ADD COLUMN IF NOT EXISTS comprobante_numero INT NULL,
    ADD COLUMN IF NOT EXISTS comprobante_pdf_url VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS comprobante_xml_url VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS comprobante_cdr_url VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS comprobante_estado VARCHAR(20) NULL, -- 'aceptado' | 'error'
    ADD COLUMN IF NOT EXISTS comprobante_error TEXT NULL;

-- Guarda tu RUTA y TOKEN igual que guardas las llaves de Culqi (Configuración -> configuracion)
INSERT INTO configuracion (clave, valor) VALUES
('nubefact_ruta', 'https://api.nubefact.com/api/v1/26193ce4-ef37-4e90-8b44-4e5d9ff3617f'),
('nubefact_token', 'd195e97c2206472497ba36d95140776df8ddf315f4c14bccb8a34092283108bc'),
('nubefact_serie_boleta', 'BBB1')
ON DUPLICATE KEY UPDATE clave = clave;