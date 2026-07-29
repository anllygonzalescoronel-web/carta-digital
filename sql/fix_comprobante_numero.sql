-- ============================================
-- Ajuste de compatibilidad: comprobante_numero en pedidos
-- Mi migración de NubeFacT lo creó como INT, pero el flujo de
-- SUNAT Nativo (y el puente unificado) necesitan guardar texto
-- como "BBB1-000011", no solo el número.
-- ============================================
USE carta_digital;

ALTER TABLE pedidos MODIFY COLUMN comprobante_numero VARCHAR(30) NULL;