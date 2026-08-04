-- Módulo Ofertas Web (solo carta digital - delivery y recojo)

CREATE TABLE IF NOT EXISTS `ofertas_web` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `titulo`           VARCHAR(120) NOT NULL,
  `color_fondo`      VARCHAR(30)  NOT NULL DEFAULT 'rgba(255,80,0,0.18)',
  `tipo_descuento`   ENUM('porcentaje','plano') NOT NULL DEFAULT 'porcentaje',
  `valor_descuento`  DECIMAL(8,2) NOT NULL DEFAULT 0,
  `activo`           TINYINT(1)   NOT NULL DEFAULT 1,
  `orden`            INT          NOT NULL DEFAULT 0,
  `created_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `oferta_web_productos` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `oferta_id`  INT UNSIGNED NOT NULL,
  `producto_id` INT UNSIGNED NOT NULL,
  UNIQUE KEY `uq_oferta_producto` (`oferta_id`, `producto_id`),
  KEY `idx_oferta` (`oferta_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
