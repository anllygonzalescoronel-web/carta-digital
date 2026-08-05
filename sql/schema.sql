-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         8.0.30 - MySQL Community Server - GPL
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para carta_digital
CREATE DATABASE IF NOT EXISTS `carta_digital` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `carta_digital`;

-- Volcando estructura para tabla carta_digital.admin_usuarios
CREATE TABLE IF NOT EXISTS `admin_usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
	`rol` enum('admin','cocinero','mesero') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.admin_usuarios: ~3 rows (aproximadamente)
INSERT INTO `admin_usuarios` (`id`, `usuario`, `password_hash`, `nombre`, `rol`, `activo`, `creado_en`, `actualizado_en`) VALUES
	(1, 'admin', '$2y$10$vO8.XETGJIEPqhwBxaPle.KKIRFDJm.L7xomfpbRz05ccTRHiXd2S', 'Administrador', 'admin', 1, '2026-07-21 06:08:45', '2026-07-30 18:07:40'),
	(2, 'cocina', '$2y$10$9eMUpHAGqzweTbroLRPSzOwzWK018wOcYGqXgVlkgs5d69NBiCjq6', 'chef porkata', 'cocinero', 1, '2026-07-30 18:13:16', '2026-07-30 18:13:16'),
	(4, 'pollero', '$2y$10$Hu09JQ84GUKIHv3bnFWVWOhZhvxzeQf.rxNccIJdACkZ9ZPpk7h8a', 'POLLERO', 'cocinero', 1, '2026-08-02 06:08:11', '2026-08-02 06:08:11');

-- Volcando estructura para tabla carta_digital.banners
CREATE TABLE IF NOT EXISTS `banners` (
  `id` int NOT NULL AUTO_INCREMENT,
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtitulo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `orden` int DEFAULT '0',
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.banners: ~5 rows (aproximadamente)
INSERT INTO `banners` (`id`, `imagen`, `titulo`, `subtitulo`, `orden`, `activo`) VALUES
	(3, 'img_6a6aa3e71a0f94.00170763.jpg', '', '', 1, 1),
	(4, 'img_6a6aa3eea73e88.34282688.jpg', '', '', 2, 1),
	(5, 'img_6a6aa3f6d13ff4.88276886.jpg', '', '', 3, 1),
	(6, 'img_6a6aa3fe0d2b10.83296849.jpg', '', '', 4, 1),
	(7, 'img_6a6aa405b5e3e8.66557399.jpg', '', '', 5, 1);

-- Volcando estructura para tabla carta_digital.cajas_turnos
CREATE TABLE IF NOT EXISTS `cajas_turnos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `usuario_nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('abierta','cerrada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'abierta',
  `monto_apertura` decimal(10,2) NOT NULL DEFAULT '0.00',
  `monto_cierre` decimal(10,2) DEFAULT NULL,
  `observacion_apertura` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observacion_cierre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `abierta_en` datetime NOT NULL,
  `cerrada_en` datetime DEFAULT NULL,
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_caja_turnos_estado` (`estado`),
  KEY `idx_caja_turnos_usuario` (`usuario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.cajas_turnos: ~3 rows (aproximadamente)
INSERT INTO `cajas_turnos` (`id`, `usuario_id`, `usuario_nombre`, `estado`, `monto_apertura`, `monto_cierre`, `observacion_apertura`, `observacion_cierre`, `abierta_en`, `cerrada_en`, `creado_en`) VALUES
	(1, 1, 'Administrador', 'cerrada', 0.00, 0.00, 'DIA', NULL, '2026-08-01 23:36:02', '2026-08-02 00:25:56', '2026-08-02 04:36:02'),
	(2, 1, 'Administrador', 'cerrada', 0.00, 36.80, NULL, 'todo bempapa', '2026-08-02 00:33:17', '2026-08-02 00:40:52', '2026-08-02 05:33:17'),
	(3, 1, 'Administrador', 'abierta', 0.00, NULL, 'ABIERTO', NULL, '2026-08-02 00:51:49', NULL, '2026-08-02 05:51:49');

-- Volcando estructura para tabla carta_digital.caja_movimientos
CREATE TABLE IF NOT EXISTS `caja_movimientos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `turno_id` int NOT NULL,
  `tipo` enum('ingreso','egreso','venta') COLLATE utf8mb4_unicode_ci NOT NULL,
  `concepto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `referencia_tipo` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referencia_id` int DEFAULT NULL,
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_caja_mov_turno` (`turno_id`),
  CONSTRAINT `fk_caja_mov_turno` FOREIGN KEY (`turno_id`) REFERENCES `cajas_turnos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.caja_movimientos: ~6 rows (aproximadamente)
INSERT INTO `caja_movimientos` (`id`, `turno_id`, `tipo`, `concepto`, `monto`, `referencia_tipo`, `referencia_id`, `creado_en`) VALUES
	(1, 1, 'venta', 'Liquidación mesa Mesa 3 PED-260802-B8F78', 98.50, 'pedido', 36, '2026-08-02 05:04:49'),
	(2, 1, 'venta', 'Liquidación mesa Mesa 1 PED-260802-22083', 52.00, 'pedido', 37, '2026-08-02 05:09:28'),
	(3, 1, 'venta', 'Liquidación mesa mesa test PED-260802-D07E4', 14.90, 'pedido', 38, '2026-08-02 05:12:24'),
	(4, 1, 'venta', 'Liquidación mesa mesa test PED-260802-D3F76', 56.70, 'pedido', 40, '2026-08-02 05:19:47'),
	(5, 1, 'venta', 'Liquidación mesa Mesa 2 PED-260802-BB20D', 36.80, 'pedido', 42, '2026-08-02 05:22:49'),
	(6, 2, 'venta', 'Liquidación mesa Mesa 1 PED-260802-8FACC', 36.80, 'pedido', 44, '2026-08-02 05:33:57');

-- Volcando estructura para tabla carta_digital.categorias
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `orden` int DEFAULT '0',
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.categorias: ~8 rows (aproximadamente)
INSERT INTO `categorias` (`id`, `nombre`, `imagen`, `orden`, `activo`) VALUES
	(7, 'Combos', 'uploads/categorias/cat_1785374426_7.png', 1, 1),
	(8, 'Promociones', 'uploads/categorias/cat_1785374884_8.png', 2, 1),
	(9, 'Hamburguesas', 'uploads/categorias/cat_1785374926_9.png', 3, 1),
	(10, 'Broasters', 'uploads/categorias/cat_1785375005_10.png', 4, 1),
	(11, 'Salchipapas', 'uploads/categorias/cat_1785375049_11.png', 5, 1),
	(12, 'Acompañamientos', 'uploads/categorias/cat_1785375117_12.png', 6, 1),
	(14, 'Bebidas', 'uploads/categorias/cat_1785375167_14.png', 8, 1),
	(15, 'Postres', 'uploads/categorias/cat_1785375202_15.png', 9, 1);

-- Volcando estructura para tabla carta_digital.comprobantes_electronicos
CREATE TABLE IF NOT EXISTS `comprobantes_electronicos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pedido_id` int NOT NULL,
  `tipo_comprobante` enum('boleta','factura') COLLATE utf8mb4_unicode_ci NOT NULL,
  `serie` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `correlativo` int NOT NULL,
  `numero_comprobante` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_documento` enum('dni','ruc') COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_documento` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado_sunat` enum('pendiente_configuracion','pendiente_envio','aceptado','observado','rechazado','error') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente_configuracion',
  `sunat_codigo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sunat_descripcion` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sunat_ticket` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `xml_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cdr_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pdf_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `xml_hash` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cdr_response_json` longtext COLLATE utf8mb4_unicode_ci,
  `intentos_envio` int NOT NULL DEFAULT '0',
  `enviado_en` datetime DEFAULT NULL,
  `respondido_en` datetime DEFAULT NULL,
  `payload_json` longtext COLLATE utf8mb4_unicode_ci,
  `error_detalle` text COLLATE utf8mb4_unicode_ci,
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_comprobante_numero` (`numero_comprobante`),
  KEY `idx_comprobantes_pedido` (`pedido_id`),
  KEY `idx_comprobantes_estado` (`estado_sunat`),
  CONSTRAINT `fk_comprobantes_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.comprobantes_electronicos: ~22 rows (aproximadamente)
INSERT INTO `comprobantes_electronicos` (`id`, `pedido_id`, `tipo_comprobante`, `serie`, `correlativo`, `numero_comprobante`, `tipo_documento`, `numero_documento`, `estado_sunat`, `sunat_codigo`, `sunat_descripcion`, `sunat_ticket`, `xml_path`, `cdr_path`, `pdf_path`, `xml_hash`, `cdr_response_json`, `intentos_envio`, `enviado_en`, `respondido_en`, `payload_json`, `error_detalle`, `creado_en`, `actualizado_en`) VALUES
	(1, 15, 'boleta', 'B001', 1, 'B001-00000001', 'dni', '72115227', 'aceptado', '0', 'La Boleta numero B001-1, ha sido aceptada', NULL, 'uploads/sunat/xml/B001-00000001.xml', 'uploads/sunat/cdr/R-B001-00000001.zip', 'uploads/sunat/pdf/B001-00000001.pdf', '972c9aed640f6232b1b478a48472d03805da7b171597661926dba2d71c602371', '{"id":"B001-1","code":"0","description":"La Boleta numero B001-1, ha sido aceptada","notes":[],"reference":null}', 1, '2026-07-28 20:34:07', '2026-07-28 20:34:07', '{"pedido_codigo":"PED-260729-D7830","tipo_comprobante":"boleta","numero_comprobante":"B001-00000001","emisor":{"ruc":"20123456789","razon_social":"POLLERIA OGETES CALIENTES","nombre_comercial":"OGETESSSS","direccion":"CALLE AVENIDA TEST","ubigeo":"150101","distrito":"LIMA","provincia":"LIMA","departamento":"LIMA","cod_pais":"PE"},"cliente":{"nombre":"cristhian coronado","tipo_documento":"dni","numero_documento":"72115227"},"totales":{"subtotal":18,"delivery":0,"total":18},"items":[{"producto_id":1,"nombre":"PIZCITAAA","precio_unitario":18,"cantidad":1,"subtotal":18}]}', 'La Boleta numero B001-1, ha sido aceptada', '2026-07-29 01:34:06', '2026-07-29 01:45:35'),
	(2, 16, 'boleta', 'B001', 2, 'B001-00000002', 'dni', '12534875', 'aceptado', '0', 'La Boleta numero B001-2, ha sido aceptada', NULL, 'uploads/sunat/xml/B001-00000002.xml', 'uploads/sunat/cdr/R-B001-00000002.zip', 'uploads/sunat/pdf/B001-00000002.pdf', '5aabbeec269d1d59c89333516eee715a32c73d56322603d23bf0f70414b42a83', '{"id":"B001-2","code":"0","description":"La Boleta numero B001-2, ha sido aceptada","notes":[],"reference":null}', 1, '2026-07-28 20:47:05', '2026-07-28 20:47:05', '{"pedido_codigo":"PED-260729-D87D6","tipo_comprobante":"boleta","numero_comprobante":"B001-00000002","emisor":{"ruc":"20123456789","razon_social":"POLLERIA OGETES CALIENTES","nombre_comercial":"OGETESSSS","direccion":"CALLE AVENIDA TEST","ubigeo":"150101","distrito":"LIMA","provincia":"LIMA","departamento":"LIMA","cod_pais":"PE"},"cliente":{"nombre":"crisssthiannnnnnnnnnnnnnnnnnnnnnnnnn","tipo_documento":"dni","numero_documento":"12534875"},"totales":{"subtotal":18,"delivery":0,"total":18},"items":[{"producto_id":1,"nombre":"PIZCITAAA","precio_unitario":18,"cantidad":1,"subtotal":18}]}', 'La Boleta numero B001-2, ha sido aceptada', '2026-07-29 01:47:04', '2026-07-29 01:47:05'),
	(3, 17, 'boleta', 'B001', 3, 'B001-00000003', 'dni', '72115227', 'aceptado', '0', 'La Boleta numero B001-3, ha sido aceptada', NULL, 'uploads/sunat/xml/B001-00000003.xml', 'uploads/sunat/cdr/R-B001-00000003.zip', 'uploads/sunat/pdf/B001-00000003.pdf', '2TzGt72jGNhq9pXJW59xZl0Eunw=', '{"id":"B001-3","code":"0","description":"La Boleta numero B001-3, ha sido aceptada","notes":[],"reference":null}', 1, '2026-07-28 21:09:22', '2026-07-28 21:09:22', '{"pedido_codigo":"PED-260729-38538","tipo_comprobante":"boleta","numero_comprobante":"B001-00000003","emisor":{"ruc":"20123456789","razon_social":"POLLERIA OGETES CALIENTES","nombre_comercial":"OGETESSSS","direccion":"CALLE AVENIDA TEST","ubigeo":"150101","distrito":"LIMA","provincia":"LIMA","departamento":"LIMA","cod_pais":"PE"},"cliente":{"nombre":"cristhiancitoi boleteado","tipo_documento":"dni","numero_documento":"72115227"},"totales":{"subtotal":18,"delivery":0,"total":18},"items":[{"producto_id":1,"nombre":"PIZCITAAA","precio_unitario":18,"cantidad":1,"subtotal":18}]}', 'La Boleta numero B001-3, ha sido aceptada', '2026-07-29 02:09:22', '2026-07-29 02:09:22'),
	(5, 19, 'boleta', 'B001', 7, 'B001-00000007', 'dni', '72115227', 'aceptado', '0', 'La Boleta numero B001-7, ha sido aceptada', NULL, 'uploads/sunat/xml/B001-00000007.xml', 'uploads/sunat/cdr/R-B001-00000007.zip', 'uploads/sunat/pdf/B001-00000007.pdf', 'GaU/vOJjZ9C5A8IrTrPOc8q7P/M=', '{"id":"B001-7","code":"0","description":"La Boleta numero B001-7, ha sido aceptada","notes":[],"reference":null}', 1, '2026-07-29 19:58:13', '2026-07-29 19:58:13', '{"pedido_codigo":"PED-260730-02F60","tipo_comprobante":"boleta","numero_comprobante":"B001-00000007","emisor":{"ruc":"20123456789","razon_social":"POLLERIA OGETES CALIENTES","nombre_comercial":"OGETESSSS","direccion":"CALLE AVENIDA TEST","ubigeo":"150101","distrito":"LIMA","provincia":"LIMA","departamento":"LIMA","cod_pais":"PE"},"cliente":{"nombre":"CORONADO DE LA CRUZ CRISTHIAN ADRIAN","tipo_documento":"dni","numero_documento":"72115227"},"totales":{"subtotal":36,"delivery":0,"total":36},"items":[{"producto_id":1,"nombre":"PIZCITAAA","precio_unitario":18,"cantidad":2,"subtotal":36}]}', 'La Boleta numero B001-7, ha sido aceptada', '2026-07-30 00:58:12', '2026-07-30 00:58:13'),
	(6, 20, 'boleta', 'B001', 8, 'B001-00000008', 'dni', '72115227', 'aceptado', '0', 'La Boleta numero B001-8, ha sido aceptada', NULL, 'uploads/sunat/xml/B001-00000008.xml', 'uploads/sunat/cdr/R-B001-00000008.zip', 'uploads/sunat/pdf/B001-00000008.pdf', 'w9grgcCewO9MggfDt0rCG0ArIQw=', '{"id":"B001-8","code":"0","description":"La Boleta numero B001-8, ha sido aceptada","notes":[],"reference":null}', 1, '2026-07-29 20:02:02', '2026-07-29 20:02:02', '{"pedido_codigo":"PED-260730-3ABCE","tipo_comprobante":"boleta","numero_comprobante":"B001-00000008","emisor":{"ruc":"20123456789","razon_social":"POLLERIA OGETES CALIENTES","nombre_comercial":"OGETESSSS","direccion":"CALLE AVENIDA TEST","ubigeo":"150101","distrito":"LIMA","provincia":"LIMA","departamento":"LIMA","cod_pais":"PE"},"cliente":{"nombre":"CORONADO DE LA CRUZ CRISTHIAN ADRIAN","tipo_documento":"dni","numero_documento":"72115227"},"totales":{"subtotal":72,"delivery":0,"total":72},"items":[{"producto_id":1,"nombre":"PIZCITAAA","precio_unitario":18,"cantidad":4,"subtotal":72}]}', 'La Boleta numero B001-8, ha sido aceptada', '2026-07-30 01:02:01', '2026-07-30 01:02:02'),
	(7, 21, 'boleta', 'B001', 9, 'B001-00000009', 'dni', '70606878', 'aceptado', '0', 'La Boleta numero B001-9, ha sido aceptada', NULL, 'uploads/sunat/xml/B001-00000009.xml', 'uploads/sunat/cdr/R-B001-00000009.zip', 'uploads/sunat/pdf/B001-00000009.pdf', '/iU8LNinPqsPiS7kZw0zDjc7DLw=', '{"id":"B001-9","code":"0","description":"La Boleta numero B001-9, ha sido aceptada","notes":[],"reference":null}', 1, '2026-07-29 23:43:16', '2026-07-29 23:43:16', '{"pedido_codigo":"PED-260730-851A2","tipo_comprobante":"boleta","numero_comprobante":"B001-00000009","emisor":{"ruc":"20123456789","razon_social":"POLLERIA OGETES CALIENTES","nombre_comercial":"OGETESSSS","direccion":"CALLE AVENIDA TEST","ubigeo":"150101","distrito":"LIMA","provincia":"LIMA","departamento":"LIMA","cod_pais":"PE"},"cliente":{"nombre":"JHANETH CORONADO","tipo_documento":"dni","numero_documento":"70606878"},"totales":{"subtotal":14.9,"delivery":0,"total":14.9},"items":[{"producto_id":6,"nombre":"La Clásica Burger","precio_unitario":14.9,"cantidad":1,"subtotal":14.9}]}', 'La Boleta numero B001-9, ha sido aceptada', '2026-07-30 04:43:10', '2026-07-30 04:43:16'),
	(8, 22, 'boleta', 'B001', 10, 'B001-00000010', 'dni', '70606878', 'aceptado', '0', 'La Boleta numero B001-10, ha sido aceptada', NULL, 'uploads/sunat/xml/B001-00000010.xml', 'uploads/sunat/cdr/R-B001-00000010.zip', 'uploads/sunat/pdf/B001-00000010.pdf', 'C4QcuNueWfiy4gTb0nziOr+hkOo=', '{"id":"B001-10","code":"0","description":"La Boleta numero B001-10, ha sido aceptada","notes":[],"reference":null}', 1, '2026-07-29 23:49:10', '2026-07-29 23:49:10', '{"pedido_codigo":"PED-260730-4E404","tipo_comprobante":"boleta","numero_comprobante":"B001-00000010","emisor":{"ruc":"20123456789","razon_social":"POLLERIA OGETES CALIENTES","nombre_comercial":"OGETESSSS","direccion":"CALLE AVENIDA TEST","ubigeo":"150101","distrito":"LIMA","provincia":"LIMA","departamento":"LIMA","cod_pais":"PE"},"cliente":{"nombre":"JHANETH CORONADO","tipo_documento":"dni","numero_documento":"70606878"},"totales":{"subtotal":40.8,"delivery":0,"total":40.8},"items":[{"producto_id":7,"nombre":"Crispy Chicken Melt","precio_unitario":16.9,"cantidad":1,"subtotal":16.9},{"producto_id":9,"nombre":"La Extrema Monster","precio_unitario":23.9,"cantidad":1,"subtotal":23.9}]}', 'La Boleta numero B001-10, ha sido aceptada', '2026-07-30 04:49:10', '2026-07-30 04:49:10'),
	(9, 23, 'boleta', 'B001', 11, 'B001-00000011', 'dni', '70606878', 'aceptado', '0', 'La Boleta numero B001-11, ha sido aceptada', NULL, 'uploads/sunat/xml/B001-00000011.xml', 'uploads/sunat/cdr/R-B001-00000011.zip', 'uploads/sunat/pdf/B001-00000011.pdf', 'fUqWfitYfYZGJiuaCK2K9jmskts=', '{"id":"B001-11","code":"0","description":"La Boleta numero B001-11, ha sido aceptada","notes":[],"reference":null}', 1, '2026-07-30 14:58:37', '2026-07-30 14:58:37', '{"pedido_codigo":"PED-260730-54A0E","tipo_comprobante":"boleta","numero_comprobante":"B001-00000011","emisor":{"ruc":"20123456789","razon_social":"POLLERIA OGETES CALIENTES","nombre_comercial":"OGETESSSS","direccion":"CALLE AVENIDA TEST","ubigeo":"150101","distrito":"LIMA","provincia":"LIMA","departamento":"LIMA","cod_pais":"PE"},"cliente":{"nombre":"CORONADO DE LA CRUZ JHANETH","tipo_documento":"dni","numero_documento":"70606878"},"totales":{"subtotal":14.9,"delivery":0,"total":14.9},"items":[{"producto_id":6,"nombre":"La Clásica Burger","precio_unitario":14.9,"cantidad":1,"subtotal":14.9}]}', 'La Boleta numero B001-11, ha sido aceptada', '2026-07-30 19:58:29', '2026-07-30 19:58:37'),
	(10, 24, 'boleta', 'B001', 12, 'B001-00000012', 'dni', '10131343', 'aceptado', '0', 'La Boleta numero B001-12, ha sido aceptada', NULL, 'uploads/sunat/xml/B001-00000012.xml', 'uploads/sunat/cdr/R-B001-00000012.zip', 'uploads/sunat/pdf/B001-00000012.pdf', 'AeJyR6YFBOMygGOncc1w/nM8pkI=', '{"id":"B001-12","code":"0","description":"La Boleta numero B001-12, ha sido aceptada","notes":[],"reference":null}', 1, '2026-07-30 15:10:24', '2026-07-30 15:10:24', '{"pedido_codigo":"PED-260730-68406","tipo_comprobante":"boleta","numero_comprobante":"B001-00000012","emisor":{"ruc":"20123456789","razon_social":"POLLERIA OGETES CALIENTES","nombre_comercial":"OGETESSSS","direccion":"CALLE AVENIDA TEST","ubigeo":"150101","distrito":"LIMA","provincia":"LIMA","departamento":"LIMA","cod_pais":"PE"},"cliente":{"nombre":"DE LA CRUZ GUILLEN HILDA","tipo_documento":"dni","numero_documento":"10131343"},"totales":{"subtotal":19.9,"delivery":5,"total":24.9},"items":[{"producto_id":8,"nombre":"Bacon Cheddar Smash (Doble)","precio_unitario":19.9,"cantidad":1,"subtotal":19.9}]}', 'La Boleta numero B001-12, ha sido aceptada', '2026-07-30 20:10:23', '2026-07-30 20:10:24'),
	(11, 25, 'boleta', 'B001', 13, 'B001-00000013', 'dni', '72115227', 'aceptado', '0', 'La Boleta numero B001-13, ha sido aceptada', NULL, 'uploads/sunat/xml/B001-00000013.xml', 'uploads/sunat/cdr/R-B001-00000013.zip', 'uploads/sunat/pdf/B001-00000013.pdf', 'Plf3GYp0mQIXfLqq831dbWcg3AY=', '{"id":"B001-13","code":"0","description":"La Boleta numero B001-13, ha sido aceptada","notes":[],"reference":null}', 1, '2026-07-30 15:40:20', '2026-07-30 15:40:20', '{"pedido_codigo":"PED-260730-B26E4","tipo_comprobante":"boleta","numero_comprobante":"B001-00000013","emisor":{"ruc":"20123456789","razon_social":"POLLERIA OGETES CALIENTES","nombre_comercial":"OGETESSSS","direccion":"CALLE AVENIDA TEST","ubigeo":"150101","distrito":"LIMA","provincia":"LIMA","departamento":"LIMA","cod_pais":"PE"},"cliente":{"nombre":"CORONADO DE LA CRUZ CRISTHIAN ADRIAN","tipo_documento":"dni","numero_documento":"72115227"},"totales":{"subtotal":14.9,"delivery":0,"total":14.9},"items":[{"producto_id":6,"nombre":"La Clásica Burger","precio_unitario":14.9,"cantidad":1,"subtotal":14.9}]}', 'La Boleta numero B001-13, ha sido aceptada', '2026-07-30 20:40:19', '2026-07-30 20:40:20'),
	(12, 27, 'boleta', 'BBB1', 1, 'BBB1-000001', 'dni', '10131343', 'aceptado', NULL, 'Generado vía NubeFacT.', NULL, 'https://www.nubefact.com/cpe/0f81375a-a6e4-4510-8af9-49588155b3e7.xml', NULL, 'https://www.nubefact.com/cpe/0f81375a-a6e4-4510-8af9-49588155b3e7.pdf', NULL, '{"tipo_de_comprobante":2,"serie":"BBB1","numero":1,"enlace":"https:\\/\\/www.nubefact.com\\/cpe\\/0f81375a-a6e4-4510-8af9-49588155b3e7","aceptada_por_sunat":false,"sunat_description":null,"sunat_note":null,"sunat_responsecode":null,"sunat_soap_error":"","anulado":false,"pdf_zip_base64":null,"xml_zip_base64":null,"cdr_zip_base64":null,"cadena_para_codigo_qr":"10721152273 | 03 | BBB1 | 000001 | 6.69 | 43.80 | 30\\/07\\/2026 | 1 | 10131343 | KwWTJ0s\\/BwFHxbuazTPBeUreKJndaBQJH88gbajtB74= |","codigo_hash":"KwWTJ0s\\/BwFHxbuazTPBeUreKJndaBQJH88gbajtB74=","codigo_de_barras":"10721152273 | 03 | BBB1 | 000001 | 6.69 | 43.80 | 30\\/07\\/2026 | 1 | 10131343 | KwWTJ0s\\/BwFHxbuazTPBeUreKJndaBQJH88gbajtB74= |","key":"0f81375a-a6e4-4510-8af9-49588155b3e7","digest_value":"KwWTJ0s\\/BwFHxbuazTPBeUreKJndaBQJH88gbajtB74=","enlace_del_pdf":"https:\\/\\/www.nubefact.com\\/cpe\\/0f81375a-a6e4-4510-8af9-49588155b3e7.pdf","enlace_del_xml":"https:\\/\\/www.nubefact.com\\/cpe\\/0f81375a-a6e4-4510-8af9-49588155b3e7.xml","enlace_del_cdr":null,"invoice":{"tipo_de_comprobante":2,"serie":"BBB1","numero":1,"enlace":"https:\\/\\/www.nubefact.com\\/cpe\\/0f81375a-a6e4-4510-8af9-49588155b3e7","aceptada_por_sunat":false,"sunat_description":null,"sunat_note":null,"sunat_responsecode":null,"sunat_soap_error":"","pdf_zip_base64":null,"xml_zip_base64":null,"cdr_zip_base64":null,"cadena_para_codigo_qr":"10721152273 | 03 | BBB1 | 000001 | 6.69 | 43.80 | 30\\/07\\/2026 | 1 | 10131343 | KwWTJ0s\\/BwFHxbuazTPBeUreKJndaBQJH88gbajtB74= |","codigo_hash":"KwWTJ0s\\/BwFHxbuazTPBeUreKJndaBQJH88gbajtB74=","digest_value":"KwWTJ0s\\/BwFHxbuazTPBeUreKJndaBQJH88gbajtB74=","codigo_de_barras":"10721152273 | 03 | BBB1 | 000001 | 6.69 | 43.80 | 30\\/07\\/2026 | 1 | 10131343 | KwWTJ0s\\/BwFHxbuazTPBeUreKJndaBQJH88gbajtB74= |","key":"0f81375a-a6e4-4510-8af9-49588155b3e7"}}', 1, '2026-07-30 15:50:15', '2026-07-30 15:50:15', '{"cliente_nombre":"DE LA CRUZ GUILLEN HILDA","cliente_email":"CARLITOS@GMAIL.COM","items":[{"descripcion":"Bacon Cheddar Smash (Doble)","cantidad":1,"precio_unitario":"19.90"},{"descripcion":"La Extrema Monster","cantidad":1,"precio_unitario":"23.90"}],"cliente_dni":"10131343"}', NULL, '2026-07-30 20:50:15', '2026-07-30 20:50:15'),
	(13, 28, 'boleta', 'BBB1', 2, 'BBB1-000002', 'dni', '70606878', 'aceptado', NULL, 'Generado vía NubeFacT.', NULL, 'https://www.nubefact.com/cpe/46d552fb-7c6f-4819-a35e-5a112a53b3d4.xml', NULL, 'https://www.nubefact.com/cpe/46d552fb-7c6f-4819-a35e-5a112a53b3d4.pdf', NULL, '{"tipo_de_comprobante":2,"serie":"BBB1","numero":2,"enlace":"https:\\/\\/www.nubefact.com\\/cpe\\/46d552fb-7c6f-4819-a35e-5a112a53b3d4","aceptada_por_sunat":false,"sunat_description":null,"sunat_note":null,"sunat_responsecode":null,"sunat_soap_error":"","anulado":false,"pdf_zip_base64":null,"xml_zip_base64":null,"cdr_zip_base64":null,"cadena_para_codigo_qr":"10721152273 | 03 | BBB1 | 000002 | 6.08 | 39.80 | 30\\/07\\/2026 | 1 | 70606878 | SliblhS\\/fjomrMRj+StbxQuG72IZ5jbsKV9XZczNMWw= |","codigo_hash":"SliblhS\\/fjomrMRj+StbxQuG72IZ5jbsKV9XZczNMWw=","codigo_de_barras":"10721152273 | 03 | BBB1 | 000002 | 6.08 | 39.80 | 30\\/07\\/2026 | 1 | 70606878 | SliblhS\\/fjomrMRj+StbxQuG72IZ5jbsKV9XZczNMWw= |","key":"46d552fb-7c6f-4819-a35e-5a112a53b3d4","digest_value":"SliblhS\\/fjomrMRj+StbxQuG72IZ5jbsKV9XZczNMWw=","enlace_del_pdf":"https:\\/\\/www.nubefact.com\\/cpe\\/46d552fb-7c6f-4819-a35e-5a112a53b3d4.pdf","enlace_del_xml":"https:\\/\\/www.nubefact.com\\/cpe\\/46d552fb-7c6f-4819-a35e-5a112a53b3d4.xml","enlace_del_cdr":null,"invoice":{"tipo_de_comprobante":2,"serie":"BBB1","numero":2,"enlace":"https:\\/\\/www.nubefact.com\\/cpe\\/46d552fb-7c6f-4819-a35e-5a112a53b3d4","aceptada_por_sunat":false,"sunat_description":null,"sunat_note":null,"sunat_responsecode":null,"sunat_soap_error":"","pdf_zip_base64":null,"xml_zip_base64":null,"cdr_zip_base64":null,"cadena_para_codigo_qr":"10721152273 | 03 | BBB1 | 000002 | 6.08 | 39.80 | 30\\/07\\/2026 | 1 | 70606878 | SliblhS\\/fjomrMRj+StbxQuG72IZ5jbsKV9XZczNMWw= |","codigo_hash":"SliblhS\\/fjomrMRj+StbxQuG72IZ5jbsKV9XZczNMWw=","digest_value":"SliblhS\\/fjomrMRj+StbxQuG72IZ5jbsKV9XZczNMWw=","codigo_de_barras":"10721152273 | 03 | BBB1 | 000002 | 6.08 | 39.80 | 30\\/07\\/2026 | 1 | 70606878 | SliblhS\\/fjomrMRj+StbxQuG72IZ5jbsKV9XZczNMWw= |","key":"46d552fb-7c6f-4819-a35e-5a112a53b3d4"}}', 1, '2026-07-30 16:30:39', '2026-07-30 16:30:39', '{"cliente_nombre":"CORONADO DE LA CRUZ JHANETH","cliente_email":"ZEAS@GMAIL.COM","items":[{"descripcion":"Bacon Cheddar Smash (Doble)","cantidad":2,"precio_unitario":"19.90"}],"cliente_dni":"70606878"}', NULL, '2026-07-30 21:30:39', '2026-07-30 21:30:39'),
	(14, 29, 'boleta', 'BBB1', 3, 'BBB1-000003', 'dni', '70606878', 'aceptado', NULL, 'Generado vía NubeFacT.', NULL, 'https://www.nubefact.com/cpe/c8139a01-a212-4b9b-a27d-1c34b04235bc.xml', NULL, 'https://www.nubefact.com/cpe/c8139a01-a212-4b9b-a27d-1c34b04235bc.pdf', NULL, '{"tipo_de_comprobante":2,"serie":"BBB1","numero":3,"enlace":"https:\\/\\/www.nubefact.com\\/cpe\\/c8139a01-a212-4b9b-a27d-1c34b04235bc","aceptada_por_sunat":false,"sunat_description":null,"sunat_note":null,"sunat_responsecode":null,"sunat_soap_error":"","anulado":false,"pdf_zip_base64":null,"xml_zip_base64":null,"cdr_zip_base64":null,"cadena_para_codigo_qr":"10721152273 | 03 | BBB1 | 000003 | 6.69 | 43.80 | 30\\/07\\/2026 | 1 | 70606878 | df9ZQRaq84+3D5tfeYDXh5lony7hBQvAo\\/3dXpltaL8= |","codigo_hash":"df9ZQRaq84+3D5tfeYDXh5lony7hBQvAo\\/3dXpltaL8=","codigo_de_barras":"10721152273 | 03 | BBB1 | 000003 | 6.69 | 43.80 | 30\\/07\\/2026 | 1 | 70606878 | df9ZQRaq84+3D5tfeYDXh5lony7hBQvAo\\/3dXpltaL8= |","key":"c8139a01-a212-4b9b-a27d-1c34b04235bc","digest_value":"df9ZQRaq84+3D5tfeYDXh5lony7hBQvAo\\/3dXpltaL8=","enlace_del_pdf":"https:\\/\\/www.nubefact.com\\/cpe\\/c8139a01-a212-4b9b-a27d-1c34b04235bc.pdf","enlace_del_xml":"https:\\/\\/www.nubefact.com\\/cpe\\/c8139a01-a212-4b9b-a27d-1c34b04235bc.xml","enlace_del_cdr":null,"invoice":{"tipo_de_comprobante":2,"serie":"BBB1","numero":3,"enlace":"https:\\/\\/www.nubefact.com\\/cpe\\/c8139a01-a212-4b9b-a27d-1c34b04235bc","aceptada_por_sunat":false,"sunat_description":null,"sunat_note":null,"sunat_responsecode":null,"sunat_soap_error":"","pdf_zip_base64":null,"xml_zip_base64":null,"cdr_zip_base64":null,"cadena_para_codigo_qr":"10721152273 | 03 | BBB1 | 000003 | 6.69 | 43.80 | 30\\/07\\/2026 | 1 | 70606878 | df9ZQRaq84+3D5tfeYDXh5lony7hBQvAo\\/3dXpltaL8= |","codigo_hash":"df9ZQRaq84+3D5tfeYDXh5lony7hBQvAo\\/3dXpltaL8=","digest_value":"df9ZQRaq84+3D5tfeYDXh5lony7hBQvAo\\/3dXpltaL8=","codigo_de_barras":"10721152273 | 03 | BBB1 | 000003 | 6.69 | 43.80 | 30\\/07\\/2026 | 1 | 70606878 | df9ZQRaq84+3D5tfeYDXh5lony7hBQvAo\\/3dXpltaL8= |","key":"c8139a01-a212-4b9b-a27d-1c34b04235bc"}}', 1, '2026-07-30 16:34:03', '2026-07-30 16:34:03', '{"cliente_nombre":"CORONADO DE LA CRUZ JHANETH","cliente_email":"ZEAS@GMAIL.COM","items":[{"descripcion":"La Extrema Monster","cantidad":1,"precio_unitario":"23.90"},{"descripcion":"Bacon Cheddar Smash (Doble)","cantidad":1,"precio_unitario":"19.90"}],"cliente_dni":"70606878"}', NULL, '2026-07-30 21:34:03', '2026-07-30 21:34:03'),
	(15, 30, 'boleta', 'BBB1', 4, 'BBB1-000004', 'dni', '70606878', 'aceptado', NULL, 'Generado vía NubeFacT.', NULL, 'https://www.nubefact.com/cpe/665b20e5-31b7-4651-8667-3b27760a973a.xml', NULL, 'https://www.nubefact.com/cpe/665b20e5-31b7-4651-8667-3b27760a973a.pdf', NULL, '{"tipo_de_comprobante":2,"serie":"BBB1","numero":4,"enlace":"https:\\/\\/www.nubefact.com\\/cpe\\/665b20e5-31b7-4651-8667-3b27760a973a","aceptada_por_sunat":false,"sunat_description":null,"sunat_note":null,"sunat_responsecode":null,"sunat_soap_error":"","anulado":false,"pdf_zip_base64":null,"xml_zip_base64":null,"cdr_zip_base64":null,"cadena_para_codigo_qr":"10721152273 | 03 | BBB1 | 000004 | 9.73 | 63.70 | 30\\/07\\/2026 | 1 | 70606878 | wg9gGJg\\/BaaBtSKTUermB415aZcvW8MVM1U20sdlksU= |","codigo_hash":"wg9gGJg\\/BaaBtSKTUermB415aZcvW8MVM1U20sdlksU=","codigo_de_barras":"10721152273 | 03 | BBB1 | 000004 | 9.73 | 63.70 | 30\\/07\\/2026 | 1 | 70606878 | wg9gGJg\\/BaaBtSKTUermB415aZcvW8MVM1U20sdlksU= |","key":"665b20e5-31b7-4651-8667-3b27760a973a","digest_value":"wg9gGJg\\/BaaBtSKTUermB415aZcvW8MVM1U20sdlksU=","enlace_del_pdf":"https:\\/\\/www.nubefact.com\\/cpe\\/665b20e5-31b7-4651-8667-3b27760a973a.pdf","enlace_del_xml":"https:\\/\\/www.nubefact.com\\/cpe\\/665b20e5-31b7-4651-8667-3b27760a973a.xml","enlace_del_cdr":null,"invoice":{"tipo_de_comprobante":2,"serie":"BBB1","numero":4,"enlace":"https:\\/\\/www.nubefact.com\\/cpe\\/665b20e5-31b7-4651-8667-3b27760a973a","aceptada_por_sunat":false,"sunat_description":null,"sunat_note":null,"sunat_responsecode":null,"sunat_soap_error":"","pdf_zip_base64":null,"xml_zip_base64":null,"cdr_zip_base64":null,"cadena_para_codigo_qr":"10721152273 | 03 | BBB1 | 000004 | 9.73 | 63.70 | 30\\/07\\/2026 | 1 | 70606878 | wg9gGJg\\/BaaBtSKTUermB415aZcvW8MVM1U20sdlksU= |","codigo_hash":"wg9gGJg\\/BaaBtSKTUermB415aZcvW8MVM1U20sdlksU=","digest_value":"wg9gGJg\\/BaaBtSKTUermB415aZcvW8MVM1U20sdlksU=","codigo_de_barras":"10721152273 | 03 | BBB1 | 000004 | 9.73 | 63.70 | 30\\/07\\/2026 | 1 | 70606878 | wg9gGJg\\/BaaBtSKTUermB415aZcvW8MVM1U20sdlksU= |","key":"665b20e5-31b7-4651-8667-3b27760a973a"}}', 1, '2026-07-30 18:50:21', '2026-07-30 18:50:21', '{"cliente_nombre":"CORONADO DE LA CRUZ JHANETH","cliente_email":"ZEAS@GMAIL.COM","items":[{"descripcion":"Bacon Cheddar Smash (Doble)","cantidad":2,"precio_unitario":"19.90"},{"descripcion":"La Extrema Monster","cantidad":1,"precio_unitario":"23.90"}],"cliente_dni":"70606878"}', NULL, '2026-07-30 23:50:21', '2026-07-30 23:50:21'),
	(16, 31, 'boleta', 'BBB1', 7, 'BBB1-000007', 'dni', '70606878', 'error', NULL, 'Este documento ya existe en NubeFacT', NULL, NULL, NULL, 'uploads/sunat/pdf/BBB1-000007.pdf', NULL, NULL, 1, '2026-08-01 23:23:10', '2026-08-01 23:23:10', '{"cliente_nombre":"CORONADO DE LA CRUZ JHANETH","cliente_email":"zeta72115227@gmail.com","items":[{"descripcion":"La Clásica Burger","cantidad":1,"precio_unitario":"14.90"}],"cliente_dni":"70606878"}', 'Este documento ya existe en NubeFacT', '2026-08-02 04:23:10', '2026-08-02 05:10:12'),
	(17, 37, 'boleta', 'BBB1', 10, 'BBB1-000010', 'dni', '72115227', 'error', NULL, 'Este documento ya existe en NubeFacT', NULL, NULL, NULL, 'uploads/sunat/pdf/BBB1-000010.pdf', NULL, NULL, 1, '2026-08-02 00:09:30', '2026-08-02 00:09:30', '{"cliente_nombre":"CORONADO DE LA CRUZ CRISTHIAN ADRIAN","cliente_email":"","items":[{"descripcion":"Dúo Crujiente (Para 2)","cantidad":2,"precio_unitario":"26.00"}],"cliente_dni":"72115227"}', 'Este documento ya existe en NubeFacT', '2026-08-02 05:09:30', '2026-08-02 05:10:12'),
	(18, 38, 'boleta', 'BBB1', 13, 'BBB1-000013', 'dni', '70606878', 'error', NULL, 'Este documento ya existe en NubeFacT', NULL, NULL, NULL, 'uploads/sunat/pdf/BBB1-000013.pdf', NULL, NULL, 1, '2026-08-02 00:12:27', '2026-08-02 00:12:27', '{"cliente_nombre":"CORONADO DE LA CRUZ JHANETH","cliente_email":"","items":[{"descripcion":"La Clásica Burger","cantidad":1,"precio_unitario":"14.90"}],"cliente_dni":"70606878"}', 'Este documento ya existe en NubeFacT', '2026-08-02 05:12:27', '2026-08-02 05:12:54'),
	(19, 40, 'boleta', 'BBB9', 1, 'BBB9-000001', 'dni', '70606878', 'error', NULL, 'Serie No puedes emitir comprobantes con esta serie\'', NULL, NULL, NULL, 'uploads/sunat/pdf/BBB9-000001.pdf', NULL, NULL, 1, '2026-08-02 00:19:49', '2026-08-02 00:19:49', '{"cliente_nombre":"CORONADO DE LA CRUZ JHANETH","cliente_email":"","items":[{"descripcion":"Bacon Cheddar Smash (Doble)","cantidad":1,"precio_unitario":"19.90"},{"descripcion":"Crispy Chicken Melt","cantidad":1,"precio_unitario":"16.90"},{"descripcion":"Mega Crack Box (Individual XL)","cantidad":1,"precio_unitario":"19.90"}],"cliente_dni":"70606878"}', 'Serie No puedes emitir comprobantes con esta serie\'', '2026-08-02 05:19:49', '2026-08-02 05:24:56'),
	(20, 42, 'boleta', 'BBB1', 15, 'BBB1-000015', 'dni', '70606878', 'aceptado', NULL, 'Generado vía NubeFacT.', NULL, 'https://www.nubefact.com/cpe/2c45e1dd-bed4-465e-b325-34b21aa8b815.xml', NULL, 'https://www.nubefact.com/cpe/2c45e1dd-bed4-465e-b325-34b21aa8b815.pdf', NULL, '{"tipo_de_comprobante":2,"serie":"BBB1","numero":15,"enlace":"https:\\/\\/www.nubefact.com\\/cpe\\/2c45e1dd-bed4-465e-b325-34b21aa8b815","aceptada_por_sunat":false,"sunat_description":null,"sunat_note":null,"sunat_responsecode":null,"sunat_soap_error":"","anulado":false,"pdf_zip_base64":null,"xml_zip_base64":null,"cdr_zip_base64":null,"cadena_para_codigo_qr":"10721152273 | 03 | BBB1 | 000015 | 5.62 | 36.80 | 02\\/08\\/2026 | 1 | 70606878 | xUap8TdVsBbRDEfQkG5MYpf2vQqEZFXm5DQQcngNo7w= |","codigo_hash":"xUap8TdVsBbRDEfQkG5MYpf2vQqEZFXm5DQQcngNo7w=","codigo_de_barras":"10721152273 | 03 | BBB1 | 000015 | 5.62 | 36.80 | 02\\/08\\/2026 | 1 | 70606878 | xUap8TdVsBbRDEfQkG5MYpf2vQqEZFXm5DQQcngNo7w= |","key":"2c45e1dd-bed4-465e-b325-34b21aa8b815","digest_value":"xUap8TdVsBbRDEfQkG5MYpf2vQqEZFXm5DQQcngNo7w=","enlace_del_pdf":"https:\\/\\/www.nubefact.com\\/cpe\\/2c45e1dd-bed4-465e-b325-34b21aa8b815.pdf","enlace_del_xml":"https:\\/\\/www.nubefact.com\\/cpe\\/2c45e1dd-bed4-465e-b325-34b21aa8b815.xml","enlace_del_cdr":null,"invoice":{"tipo_de_comprobante":2,"serie":"BBB1","numero":15,"enlace":"https:\\/\\/www.nubefact.com\\/cpe\\/2c45e1dd-bed4-465e-b325-34b21aa8b815","aceptada_por_sunat":false,"sunat_description":null,"sunat_note":null,"sunat_responsecode":null,"sunat_soap_error":"","pdf_zip_base64":null,"xml_zip_base64":null,"cdr_zip_base64":null,"cadena_para_codigo_qr":"10721152273 | 03 | BBB1 | 000015 | 5.62 | 36.80 | 02\\/08\\/2026 | 1 | 70606878 | xUap8TdVsBbRDEfQkG5MYpf2vQqEZFXm5DQQcngNo7w= |","codigo_hash":"xUap8TdVsBbRDEfQkG5MYpf2vQqEZFXm5DQQcngNo7w=","digest_value":"xUap8TdVsBbRDEfQkG5MYpf2vQqEZFXm5DQQcngNo7w=","codigo_de_barras":"10721152273 | 03 | BBB1 | 000015 | 5.62 | 36.80 | 02\\/08\\/2026 | 1 | 70606878 | xUap8TdVsBbRDEfQkG5MYpf2vQqEZFXm5DQQcngNo7w= |","key":"2c45e1dd-bed4-465e-b325-34b21aa8b815"}}', 1, '2026-08-02 00:22:52', '2026-08-02 00:22:52', '{"cliente_nombre":"CORONADO DE LA CRUZ JHANETH","cliente_email":"","items":[{"descripcion":"Crispy Chicken Melt","cantidad":1,"precio_unitario":"16.90"},{"descripcion":"Bacon Cheddar Smash (Doble)","cantidad":1,"precio_unitario":"19.90"}],"cliente_dni":"70606878"}', 'Este documento ya existe en NubeFacT', '2026-08-02 05:22:52', '2026-08-02 05:22:52'),
	(21, 43, 'boleta', 'BBB1', 16, 'BBB1-000016', 'dni', '10131343', 'aceptado', NULL, 'Generado vía NubeFacT.', NULL, 'https://www.nubefact.com/cpe/a910492a-2f52-4908-9fed-a72f72db9455.xml', NULL, 'https://www.nubefact.com/cpe/a910492a-2f52-4908-9fed-a72f72db9455.pdf', NULL, '{"tipo_de_comprobante":2,"serie":"BBB1","numero":16,"enlace":"https:\\/\\/www.nubefact.com\\/cpe\\/a910492a-2f52-4908-9fed-a72f72db9455","aceptada_por_sunat":false,"sunat_description":null,"sunat_note":null,"sunat_responsecode":null,"sunat_soap_error":"","anulado":false,"pdf_zip_base64":null,"xml_zip_base64":null,"cdr_zip_base64":null,"cadena_para_codigo_qr":"10721152273 | 03 | BBB1 | 000016 | 5.62 | 36.80 | 02\\/08\\/2026 | 1 | 10131343 | maoGKzpVVlD+8Z6LF6W6LiiRLjmj3UEfvVMcNGHefWc= |","codigo_hash":"maoGKzpVVlD+8Z6LF6W6LiiRLjmj3UEfvVMcNGHefWc=","codigo_de_barras":"10721152273 | 03 | BBB1 | 000016 | 5.62 | 36.80 | 02\\/08\\/2026 | 1 | 10131343 | maoGKzpVVlD+8Z6LF6W6LiiRLjmj3UEfvVMcNGHefWc= |","key":"a910492a-2f52-4908-9fed-a72f72db9455","digest_value":"maoGKzpVVlD+8Z6LF6W6LiiRLjmj3UEfvVMcNGHefWc=","enlace_del_pdf":"https:\\/\\/www.nubefact.com\\/cpe\\/a910492a-2f52-4908-9fed-a72f72db9455.pdf","enlace_del_xml":"https:\\/\\/www.nubefact.com\\/cpe\\/a910492a-2f52-4908-9fed-a72f72db9455.xml","enlace_del_cdr":null,"invoice":{"tipo_de_comprobante":2,"serie":"BBB1","numero":16,"enlace":"https:\\/\\/www.nubefact.com\\/cpe\\/a910492a-2f52-4908-9fed-a72f72db9455","aceptada_por_sunat":false,"sunat_description":null,"sunat_note":null,"sunat_responsecode":null,"sunat_soap_error":"","pdf_zip_base64":null,"xml_zip_base64":null,"cdr_zip_base64":null,"cadena_para_codigo_qr":"10721152273 | 03 | BBB1 | 000016 | 5.62 | 36.80 | 02\\/08\\/2026 | 1 | 10131343 | maoGKzpVVlD+8Z6LF6W6LiiRLjmj3UEfvVMcNGHefWc= |","codigo_hash":"maoGKzpVVlD+8Z6LF6W6LiiRLjmj3UEfvVMcNGHefWc=","digest_value":"maoGKzpVVlD+8Z6LF6W6LiiRLjmj3UEfvVMcNGHefWc=","codigo_de_barras":"10721152273 | 03 | BBB1 | 000016 | 5.62 | 36.80 | 02\\/08\\/2026 | 1 | 10131343 | maoGKzpVVlD+8Z6LF6W6LiiRLjmj3UEfvVMcNGHefWc= |","key":"a910492a-2f52-4908-9fed-a72f72db9455"}}', 1, '2026-08-02 00:24:20', '2026-08-02 00:24:20', '{"cliente_nombre":"DE LA CRUZ GUILLEN HILDA","cliente_email":"hilda@gmail.com","items":[{"descripcion":"Crispy Chicken Melt","cantidad":1,"precio_unitario":"16.90"},{"descripcion":"Bacon Cheddar Smash (Doble)","cantidad":1,"precio_unitario":"19.90"}],"cliente_dni":"10131343"}', NULL, '2026-08-02 05:24:20', '2026-08-02 05:24:20'),
	(22, 44, 'factura', 'FFF1', 1, 'FFF1-000001', 'ruc', '20603034873', 'aceptado', NULL, 'Generado vía NubeFacT.', NULL, 'https://www.nubefact.com/cpe/09f3a307-b5fd-4668-b8ef-d630459fd25d.xml', 'https://www.nubefact.com/cpe/09f3a307-b5fd-4668-b8ef-d630459fd25d.cdr', 'https://www.nubefact.com/cpe/09f3a307-b5fd-4668-b8ef-d630459fd25d.pdf', NULL, '{"tipo_de_comprobante":1,"serie":"FFF1","numero":1,"enlace":"https:\\/\\/www.nubefact.com\\/cpe\\/09f3a307-b5fd-4668-b8ef-d630459fd25d","aceptada_por_sunat":true,"sunat_description":"La Factura Electrónica FFF1-1 ha sido ACEPTADA CON OBSERVACIONES","sunat_note":null,"sunat_responsecode":"0","sunat_soap_error":"","anulado":false,"pdf_zip_base64":null,"xml_zip_base64":null,"cdr_zip_base64":null,"cadena_para_codigo_qr":"10721152273 | 01 | FFF1 | 000001 | 5.62 | 36.80 | 02\\/08\\/2026 | 6 | 20603034873 | uOhiyBKr4rR3ybdDASZnRhA\\/cabAuVUORXa3411b4Wc= |","codigo_hash":"uOhiyBKr4rR3ybdDASZnRhA\\/cabAuVUORXa3411b4Wc=","codigo_de_barras":"10721152273 | 01 | FFF1 | 000001 | 5.62 | 36.80 | 02\\/08\\/2026 | 6 | 20603034873 | uOhiyBKr4rR3ybdDASZnRhA\\/cabAuVUORXa3411b4Wc= |","key":"09f3a307-b5fd-4668-b8ef-d630459fd25d","digest_value":"uOhiyBKr4rR3ybdDASZnRhA\\/cabAuVUORXa3411b4Wc=","enlace_del_pdf":"https:\\/\\/www.nubefact.com\\/cpe\\/09f3a307-b5fd-4668-b8ef-d630459fd25d.pdf","enlace_del_xml":"https:\\/\\/www.nubefact.com\\/cpe\\/09f3a307-b5fd-4668-b8ef-d630459fd25d.xml","enlace_del_cdr":"https:\\/\\/www.nubefact.com\\/cpe\\/09f3a307-b5fd-4668-b8ef-d630459fd25d.cdr","invoice":{"tipo_de_comprobante":1,"serie":"FFF1","numero":1,"enlace":"https:\\/\\/www.nubefact.com\\/cpe\\/09f3a307-b5fd-4668-b8ef-d630459fd25d","aceptada_por_sunat":true,"sunat_description":"La Factura Electrónica FFF1-1 ha sido ACEPTADA CON OBSERVACIONES","sunat_note":null,"sunat_responsecode":"0","sunat_soap_error":"","pdf_zip_base64":null,"xml_zip_base64":null,"cdr_zip_base64":null,"cadena_para_codigo_qr":"10721152273 | 01 | FFF1 | 000001 | 5.62 | 36.80 | 02\\/08\\/2026 | 6 | 20603034873 | uOhiyBKr4rR3ybdDASZnRhA\\/cabAuVUORXa3411b4Wc= |","codigo_hash":"uOhiyBKr4rR3ybdDASZnRhA\\/cabAuVUORXa3411b4Wc=","digest_value":"uOhiyBKr4rR3ybdDASZnRhA\\/cabAuVUORXa3411b4Wc=","codigo_de_barras":"10721152273 | 01 | FFF1 | 000001 | 5.62 | 36.80 | 02\\/08\\/2026 | 6 | 20603034873 | uOhiyBKr4rR3ybdDASZnRhA\\/cabAuVUORXa3411b4Wc= |","key":"09f3a307-b5fd-4668-b8ef-d630459fd25d"}}', 1, '2026-08-02 00:34:01', '2026-08-02 00:34:01', '{"cliente_nombre":"BEST PERUVIAN IMPORT AND EXPORT E.I.R.L.","cliente_email":"","items":[{"descripcion":"Crispy Chicken Melt","cantidad":1,"precio_unitario":"16.90"},{"descripcion":"Bacon Cheddar Smash (Doble)","cantidad":1,"precio_unitario":"19.90"}],"cliente_ruc":"20603034873"}', NULL, '2026-08-02 05:34:01', '2026-08-02 05:34:01'),
	(23, 48, 'boleta', 'BBB1', 17, 'BBB1-000017', 'dni', '72115227', 'aceptado', NULL, 'Generado vía NubeFacT.', NULL, 'https://www.nubefact.com/cpe/ce404e01-eb0c-43e5-a226-4504b6c962d5.xml', NULL, 'https://www.nubefact.com/cpe/ce404e01-eb0c-43e5-a226-4504b6c962d5.pdf', NULL, '{"tipo_de_comprobante":2,"serie":"BBB1","numero":17,"enlace":"https:\\/\\/www.nubefact.com\\/cpe\\/ce404e01-eb0c-43e5-a226-4504b6c962d5","aceptada_por_sunat":false,"sunat_description":null,"sunat_note":null,"sunat_responsecode":null,"sunat_soap_error":"","anulado":false,"pdf_zip_base64":null,"xml_zip_base64":null,"cdr_zip_base64":null,"cadena_para_codigo_qr":"10721152273 | 03 | BBB1 | 000017 | 2.12 | 13.90 | 02\\/08\\/2026 | 1 | 72115227 | znJnT2brpOvZ\\/JpuK1LNa\\/UyXGfq3Px9ZCgzOmFUD20= |","codigo_hash":"znJnT2brpOvZ\\/JpuK1LNa\\/UyXGfq3Px9ZCgzOmFUD20=","codigo_de_barras":"10721152273 | 03 | BBB1 | 000017 | 2.12 | 13.90 | 02\\/08\\/2026 | 1 | 72115227 | znJnT2brpOvZ\\/JpuK1LNa\\/UyXGfq3Px9ZCgzOmFUD20= |","key":"ce404e01-eb0c-43e5-a226-4504b6c962d5","digest_value":"znJnT2brpOvZ\\/JpuK1LNa\\/UyXGfq3Px9ZCgzOmFUD20=","enlace_del_pdf":"https:\\/\\/www.nubefact.com\\/cpe\\/ce404e01-eb0c-43e5-a226-4504b6c962d5.pdf","enlace_del_xml":"https:\\/\\/www.nubefact.com\\/cpe\\/ce404e01-eb0c-43e5-a226-4504b6c962d5.xml","enlace_del_cdr":null,"invoice":{"tipo_de_comprobante":2,"serie":"BBB1","numero":17,"enlace":"https:\\/\\/www.nubefact.com\\/cpe\\/ce404e01-eb0c-43e5-a226-4504b6c962d5","aceptada_por_sunat":false,"sunat_description":null,"sunat_note":null,"sunat_responsecode":null,"sunat_soap_error":"","pdf_zip_base64":null,"xml_zip_base64":null,"cdr_zip_base64":null,"cadena_para_codigo_qr":"10721152273 | 03 | BBB1 | 000017 | 2.12 | 13.90 | 02\\/08\\/2026 | 1 | 72115227 | znJnT2brpOvZ\\/JpuK1LNa\\/UyXGfq3Px9ZCgzOmFUD20= |","codigo_hash":"znJnT2brpOvZ\\/JpuK1LNa\\/UyXGfq3Px9ZCgzOmFUD20=","digest_value":"znJnT2brpOvZ\\/JpuK1LNa\\/UyXGfq3Px9ZCgzOmFUD20=","codigo_de_barras":"10721152273 | 03 | BBB1 | 000017 | 2.12 | 13.90 | 02\\/08\\/2026 | 1 | 72115227 | znJnT2brpOvZ\\/JpuK1LNa\\/UyXGfq3Px9ZCgzOmFUD20= |","key":"ce404e01-eb0c-43e5-a226-4504b6c962d5"}}', 1, '2026-08-02 01:39:38', '2026-08-02 01:39:38', '{"cliente_nombre":"CORONADO DE LA CRUZ CRISTHIAN ADRIAN","cliente_email":"zeta72115227@gmail.com","items":[{"descripcion":"Broaster Crunch (Personal)","cantidad":1,"precio_unitario":"13.90"}],"cliente_dni":"72115227"}', NULL, '2026-08-02 06:39:38', '2026-08-02 06:39:38');

-- Volcando estructura para tabla carta_digital.comprobante_correlativo
CREATE TABLE IF NOT EXISTS `comprobante_correlativo` (
  `serie` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_comprobante` tinyint NOT NULL DEFAULT '2',
  `ultimo_numero` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`serie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.comprobante_correlativo: ~3 rows (aproximadamente)
INSERT INTO `comprobante_correlativo` (`serie`, `tipo_comprobante`, `ultimo_numero`) VALUES
	('BBB1', 2, 17),
	('BBB9', 2, 1),
	('FFF1', 1, 1);

-- Volcando estructura para tabla carta_digital.configuracion
CREATE TABLE IF NOT EXISTS `configuracion` (
  `clave` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.configuracion: ~59 rows (aproximadamente)
INSERT INTO `configuracion` (`clave`, `valor`) VALUES
	('apiperu_habilitado', '1'),
	('apiperu_token', '4d98f77c7e1ea895a816a58ea0598d323dde32d6f9e4b069ae91d7fb52afd954'),
	('color_fondo', '#fff7ed'),
	('color_primario', '#f2ad18'),
	('color_primario_fuerte', '#f2ad18'),
	('color_secundario', '#ea580c'),
	('color_texto', '#1f2937'),
	('comer_aqui_activo', '1'),
	('costo_delivery', '5.00'),
	('culqi_public_key', 'pk_test_MPcRP4J17oj4cCNH'),
	('culqi_secret_key', 'sk_test_6hf0Do648y4FDIft'),
	('delivery_activo', '1'),
	('direccion_local', 'Av. Principal 123, Lima'),
	('efectivo_activo', '1'),
	('facturacion_driver', 'nubefact'),
	('facturacion_modo_hibrido', '0'),
	('facturacion_reintentos_max', '5'),
	('facturacion_timeout_segundos', '30'),
	('logo', ''),
	('mensaje_bienvenida', '¡Bienvenido! Elige tus platos favoritos y haz tu pedido en segundos.'),
	('nombre_negocio', 'Pollería El Sabor'),
	('nubefact_ruta', 'https://api.nubefact.com/api/v1/26193ce4-ef37-4e90-8b44-4e5d9ff3617f'),
	('nubefact_serie_boleta', 'BBB1'),
	('nubefact_serie_factura', 'FFF1'),
	('nubefact_token', 'd195e97c2206472497ba36d95140776df8ddf315f4c14bccb8a34092283108bc'),
	('recojo_activo', '1'),
	('smtp_enabled', '0'),
	('smtp_from_email', ''),
	('smtp_from_name', 'Carta Digital'),
	('smtp_host', ''),
	('smtp_password', ''),
	('smtp_port', '587'),
	('smtp_secure', 'tls'),
	('smtp_timeout', '15'),
	('smtp_username', ''),
	('sunat_certificado_clave', '20123456789'),
	('sunat_certificado_path', 'uploads/sunat_1785370364.pfx'),
	('sunat_clave_sol', 'moddatos'),
	('sunat_correlativo_boleta', '14'),
	('sunat_correlativo_factura', '1'),
	('sunat_departamento', 'LIMA'),
	('sunat_direccion', 'CALLE AVENIDA TEST'),
	('sunat_distrito', 'LIMA'),
	('sunat_igv_porcentaje', '18'),
	('sunat_modo', 'beta'),
	('sunat_nombre_comercial', 'OGETESSSS'),
	('sunat_provincia', 'LIMA'),
	('sunat_razon_social', 'POLLERIA OGETES CALIENTES'),
	('sunat_ruc_emisor', '20123456789'),
	('sunat_serie_boleta', 'B001'),
	('sunat_serie_factura', 'F001'),
	('sunat_ubigeo', '150101'),
	('sunat_usuario_sol', 'MODDATOS'),
	('tarjeta_activo', '1'),
	('url_publica', ''),
	('whatsapp_numero', '51999999999'),
	('yape_plin_activo', '1'),
	('yape_plin_numero', '999999999'),
	('yape_plin_qr', '');

-- Volcando estructura para tabla carta_digital.estaciones_produccion
CREATE TABLE IF NOT EXISTS `estaciones_produccion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#0f172a',
  `icono` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ti-chef-hat',
  `orden` int NOT NULL DEFAULT '0',
  `activa` tinyint(1) NOT NULL DEFAULT '1',
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.estaciones_produccion: ~2 rows (aproximadamente)
INSERT INTO `estaciones_produccion` (`id`, `nombre`, `descripcion`, `color`, `icono`, `orden`, `activa`, `creado_en`, `actualizado_en`) VALUES
	(1, 'COCINA DE HAMBURGUESAS', 'AREA PARA TODDAS LAS COMIDAS RAPIDAS', '#ffad1f', 'ti-salad', 1, 1, '2026-08-02 06:07:22', '2026-08-02 06:07:22'),
	(2, 'broasters cull', 'AREA PARA TODDAS LAS COMIDAS RAPIDAS solo broaster', '#ff0a0a', 'ti-meat', 2, 1, '2026-08-02 06:08:44', '2026-08-02 06:08:44');

-- Volcando estructura para tabla carta_digital.estacion_categorias
CREATE TABLE IF NOT EXISTS `estacion_categorias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `estacion_id` int NOT NULL,
  `categoria_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_estacion_categoria` (`estacion_id`,`categoria_id`),
  KEY `fk_estacion_cat_categoria` (`categoria_id`),
  CONSTRAINT `fk_estacion_cat_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_estacion_cat_estacion` FOREIGN KEY (`estacion_id`) REFERENCES `estaciones_produccion` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.estacion_categorias: ~2 rows (aproximadamente)
INSERT INTO `estacion_categorias` (`id`, `estacion_id`, `categoria_id`) VALUES
	(1, 1, 9),
	(2, 2, 10);

-- Volcando estructura para tabla carta_digital.estacion_usuarios
CREATE TABLE IF NOT EXISTS `estacion_usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `estacion_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_estacion_usuario` (`estacion_id`,`usuario_id`),
  KEY `fk_estacion_usr_usuario` (`usuario_id`),
  CONSTRAINT `fk_estacion_usr_estacion` FOREIGN KEY (`estacion_id`) REFERENCES `estaciones_produccion` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_estacion_usr_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `admin_usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.estacion_usuarios: ~3 rows (aproximadamente)
INSERT INTO `estacion_usuarios` (`id`, `estacion_id`, `usuario_id`) VALUES
	(1, 1, 1),
	(2, 1, 2),
	(3, 2, 4);

-- Volcando estructura para tabla carta_digital.facturacion_comprobantes
CREATE TABLE IF NOT EXISTS `facturacion_comprobantes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pedido_id` int NOT NULL,
  `tipo_comprobante` enum('boleta','factura') COLLATE utf8mb4_unicode_ci NOT NULL,
  `driver` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'native|nubefact',
  `serie` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `correlativo` int NOT NULL,
  `numero_comprobante` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_documento` enum('dni','ruc','sin_documento') COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_documento` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('pendiente','procesando','aceptado','observado','rechazado','error') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `sunat_codigo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sunat_descripcion` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sunat_ticket` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nubefact_respuesta` longtext COLLATE utf8mb4_unicode_ci COMMENT 'JSON Response de NubeFacT',
  `xml_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cdr_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pdf_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `xml_hash` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload_json` longtext COLLATE utf8mb4_unicode_ci COMMENT 'JSON enviado a API/SUNAT',
  `response_json` longtext COLLATE utf8mb4_unicode_ci COMMENT 'Respuesta completa de API',
  `intentos_envio` int DEFAULT '0',
  `ultimo_intento` datetime DEFAULT NULL,
  `enviado_en` datetime DEFAULT NULL,
  `respondido_en` datetime DEFAULT NULL,
  `error_detalle` text COLLATE utf8mb4_unicode_ci,
  `debug_log` longtext COLLATE utf8mb4_unicode_ci,
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_comprobante` (`numero_comprobante`),
  UNIQUE KEY `uq_numero_comprobante` (`numero_comprobante`),
  KEY `idx_pedido` (`pedido_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_driver` (`driver`),
  KEY `idx_numero_documento` (`numero_documento`),
  KEY `idx_serie_correlativo` (`serie`,`correlativo`),
  CONSTRAINT `fk_comprobante_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.facturacion_comprobantes: ~0 rows (aproximadamente)

-- Volcando estructura para tabla carta_digital.facturacion_config
CREATE TABLE IF NOT EXISTS `facturacion_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `driver` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clave` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` text COLLATE utf8mb4_unicode_ci,
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_driver_clave` (`driver`,`clave`),
  KEY `idx_driver` (`driver`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.facturacion_config: ~21 rows (aproximadamente)
INSERT INTO `facturacion_config` (`id`, `driver`, `clave`, `valor`, `creado_en`, `actualizado_en`) VALUES
	(1, 'native', 'habilitado', '1', '2026-07-30 00:02:19', '2026-07-30 00:02:19'),
	(2, 'native', 'modo', 'demo', '2026-07-30 00:02:19', '2026-07-30 00:02:19'),
	(3, 'native', 'ruc_emisor', '', '2026-07-30 00:02:19', '2026-07-30 00:02:19'),
	(4, 'native', 'razon_social', '', '2026-07-30 00:02:19', '2026-07-30 00:02:19'),
	(5, 'native', 'nombre_comercial', '', '2026-07-30 00:02:19', '2026-07-30 00:02:19'),
	(6, 'native', 'usuario_sol', '', '2026-07-30 00:02:19', '2026-07-30 00:02:19'),
	(7, 'native', 'clave_sol', '', '2026-07-30 00:02:19', '2026-07-30 00:02:19'),
	(8, 'native', 'certificado_path', '', '2026-07-30 00:02:19', '2026-07-30 00:02:19'),
	(9, 'native', 'certificado_clave', '', '2026-07-30 00:02:19', '2026-07-30 00:02:19'),
	(10, 'native', 'direccion', '', '2026-07-30 00:02:19', '2026-07-30 00:02:19'),
	(11, 'native', 'ubigeo', '150131', '2026-07-30 00:02:19', '2026-07-30 00:02:19'),
	(12, 'native', 'distrito', 'LIMA', '2026-07-30 00:02:19', '2026-07-30 00:02:19'),
	(13, 'native', 'provincia', 'LIMA', '2026-07-30 00:02:19', '2026-07-30 00:02:19'),
	(14, 'native', 'departamento', 'LIMA', '2026-07-30 00:02:19', '2026-07-30 00:02:19'),
	(15, 'native', 'serie_boleta', 'B001', '2026-07-30 00:02:19', '2026-07-30 00:02:19'),
	(16, 'native', 'serie_factura', 'F001', '2026-07-30 00:02:19', '2026-07-30 00:02:19'),
	(17, 'nubefact', 'habilitado', '0', '2026-07-30 00:02:19', '2026-07-30 00:02:19'),
	(18, 'nubefact', 'ruta_api', '', '2026-07-30 00:02:19', '2026-07-30 00:02:19'),
	(19, 'nubefact', 'token', '', '2026-07-30 00:02:19', '2026-07-30 00:02:19'),
	(20, 'nubefact', 'serie_boleta', 'BBB1', '2026-07-30 00:02:19', '2026-07-30 00:02:19'),
	(21, 'nubefact', 'serie_factura', 'FFF1', '2026-07-30 00:02:19', '2026-07-30 00:02:19');

-- Volcando estructura para tabla carta_digital.facturacion_error_log
CREATE TABLE IF NOT EXISTS `facturacion_error_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pedido_id` int DEFAULT NULL,
  `driver` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_error` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensaje` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `detalles` longtext COLLATE utf8mb4_unicode_ci,
  `resuelto` tinyint DEFAULT '0',
  `fecha_resolucion` datetime DEFAULT NULL,
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pedido` (`pedido_id`),
  KEY `idx_driver` (`driver`),
  KEY `idx_resuelto` (`resuelto`),
  CONSTRAINT `fk_error_log_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.facturacion_error_log: ~0 rows (aproximadamente)

-- Volcando estructura para tabla carta_digital.facturacion_secuencias
CREATE TABLE IF NOT EXISTS `facturacion_secuencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `driver` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'native|nubefact',
  `serie` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_comprobante` tinyint NOT NULL COMMENT '1=factura|2=boleta',
  `ultimo_numero` int NOT NULL DEFAULT '0',
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_driver_serie` (`driver`,`serie`),
  KEY `idx_driver` (`driver`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.facturacion_secuencias: ~4 rows (aproximadamente)
INSERT INTO `facturacion_secuencias` (`id`, `driver`, `serie`, `tipo_comprobante`, `ultimo_numero`, `creado_en`, `actualizado_en`) VALUES
	(1, 'native', 'B001', 2, 0, '2026-07-30 00:02:19', '2026-07-30 00:02:19'),
	(2, 'native', 'F001', 1, 0, '2026-07-30 00:02:19', '2026-07-30 00:02:19'),
	(3, 'nubefact', 'BBB1', 2, 0, '2026-07-30 00:02:19', '2026-07-30 00:02:19'),
	(4, 'nubefact', 'FFF1', 1, 0, '2026-07-30 00:02:19', '2026-07-30 00:02:19');

-- Volcando estructura para tabla carta_digital.ingredientes
CREATE TABLE IF NOT EXISTS `ingredientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unidad` enum('kg','g','l','ml','m','cm','unidad','porcion') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unidad',
  `stock_actual` decimal(10,3) NOT NULL DEFAULT '0.000',
  `stock_minimo` decimal(10,3) NOT NULL DEFAULT '0.000',
  `costo_unitario` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.ingredientes: ~1 rows (aproximadamente)
INSERT INTO `ingredientes` (`id`, `nombre`, `unidad`, `stock_actual`, `stock_minimo`, `costo_unitario`, `descripcion`, `activo`, `creado_en`, `actualizado_en`) VALUES
	(1, 'POLLO', 'kg', 0.500, 1.000, 9.0000, NULL, 1, '2026-08-02 06:27:50', '2026-08-02 06:55:16');

-- Volcando estructura para tabla carta_digital.ingrediente_movimientos
CREATE TABLE IF NOT EXISTS `ingrediente_movimientos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ingrediente_id` int NOT NULL,
  `tipo` enum('entrada','salida','ajuste') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` decimal(10,3) NOT NULL,
  `stock_antes` decimal(10,3) NOT NULL,
  `stock_despues` decimal(10,3) NOT NULL,
  `motivo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pedido_id` int DEFAULT NULL,
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.ingrediente_movimientos: ~6 rows (aproximadamente)
INSERT INTO `ingrediente_movimientos` (`id`, `ingrediente_id`, `tipo`, `cantidad`, `stock_antes`, `stock_despues`, `motivo`, `pedido_id`, `creado_en`) VALUES
	(1, 1, 'entrada', 1.000, 0.000, 1.000, 'Stock inicial', NULL, '2026-08-02 06:27:50'),
	(2, 1, 'salida', 0.250, 1.000, 0.750, 'Pedido #48', 48, '2026-08-02 06:40:50'),
	(3, 1, 'salida', 0.750, 0.750, 0.000, 'Pedido #46', 46, '2026-08-02 06:54:06'),
	(4, 1, 'entrada', 1000.000, 0.000, 1000.000, NULL, NULL, '2026-08-02 06:54:41'),
	(5, 1, 'ajuste', 999.000, 1000.000, 1.000, NULL, NULL, '2026-08-02 06:54:55'),
	(6, 1, 'salida', 0.500, 1.000, 0.500, 'Pedido #50', 50, '2026-08-02 06:55:17');

-- Volcando estructura para tabla carta_digital.mesas
CREATE TABLE IF NOT EXISTS `mesas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `zona_id` int NOT NULL,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacidad` int NOT NULL DEFAULT '4',
  `sillas` int NOT NULL DEFAULT '4',
  `pos_x` int NOT NULL DEFAULT '80',
  `pos_y` int NOT NULL DEFAULT '80',
	`ancho` int NOT NULL DEFAULT '120',
	`alto` int NOT NULL DEFAULT '74',
  `forma` enum('rectangular','redonda') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'rectangular',
  `activa` tinyint(1) NOT NULL DEFAULT '1',
  `orden` int NOT NULL DEFAULT '0',
	`decoraciones_json` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mesas_zona` (`zona_id`),
  CONSTRAINT `fk_mesas_zona` FOREIGN KEY (`zona_id`) REFERENCES `zonas_mesas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.mesas: ~35 rows (aproximadamente)
INSERT INTO `mesas` (`id`, `zona_id`, `nombre`, `capacidad`, `sillas`, `pos_x`, `pos_y`, `forma`, `activa`, `orden`, `creado_en`, `actualizado_en`) VALUES
	(7, 3, 'M 1', 4, 4, 36, 44, 'rectangular', 1, 1, '2026-08-02 05:48:28', '2026-08-02 05:50:04'),
	(8, 3, 'M 2', 4, 4, 187, 45, 'rectangular', 1, 2, '2026-08-02 05:48:28', '2026-08-02 05:50:04'),
	(9, 3, 'M 3', 4, 4, 336, 46, 'rectangular', 1, 3, '2026-08-02 05:48:28', '2026-08-02 05:50:04'),
	(10, 3, 'M 4', 4, 4, 489, 48, 'rectangular', 1, 4, '2026-08-02 05:48:28', '2026-08-02 05:50:04'),
	(11, 3, 'M 5', 4, 4, 34, 152, 'rectangular', 1, 5, '2026-08-02 05:48:28', '2026-08-02 05:50:04'),
	(12, 3, 'M 6', 4, 4, 191, 146, 'rectangular', 1, 6, '2026-08-02 05:48:28', '2026-08-02 05:50:04'),
	(13, 3, 'M 7', 4, 4, 345, 144, 'rectangular', 1, 7, '2026-08-02 05:48:28', '2026-08-02 05:50:04'),
	(14, 3, 'M 8', 4, 4, 495, 146, 'rectangular', 1, 8, '2026-08-02 05:48:28', '2026-08-02 05:50:04'),
	(15, 3, 'M 9', 4, 4, 34, 251, 'rectangular', 1, 9, '2026-08-02 05:48:28', '2026-08-02 05:50:04'),
	(16, 3, 'M 10', 4, 4, 199, 250, 'rectangular', 1, 10, '2026-08-02 05:48:28', '2026-08-02 05:50:04'),
	(17, 3, 'M 11', 4, 4, 30, 405, 'redonda', 1, 11, '2026-08-02 05:48:58', '2026-08-02 05:50:04'),
	(18, 3, 'M 12', 4, 4, 208, 409, 'redonda', 1, 12, '2026-08-02 05:48:58', '2026-08-02 05:50:04'),
	(19, 3, 'M 13', 4, 4, 384, 413, 'redonda', 1, 13, '2026-08-02 05:48:58', '2026-08-02 05:50:04'),
	(20, 3, 'M 14', 4, 4, 475, 277, 'redonda', 1, 14, '2026-08-02 05:48:58', '2026-08-02 05:50:04'),
	(21, 3, 'M 15', 4, 4, 540, 410, 'redonda', 1, 15, '2026-08-02 05:48:58', '2026-08-02 05:50:04'),
	(22, 4, 'TERR 1', 4, 4, 59, 162, 'redonda', 1, 1, '2026-08-02 05:50:17', '2026-08-02 05:50:52'),
	(23, 4, 'TERR 2', 4, 4, 204, 76, 'redonda', 1, 2, '2026-08-02 05:50:17', '2026-08-02 05:50:52'),
	(24, 4, 'TERR 3', 4, 4, 356, 52, 'redonda', 1, 3, '2026-08-02 05:50:17', '2026-08-02 05:50:52'),
	(25, 4, 'TERR 4', 4, 4, 517, 84, 'redonda', 1, 4, '2026-08-02 05:50:17', '2026-08-02 05:50:52'),
	(26, 4, 'TERR 5', 4, 4, 650, 170, 'redonda', 1, 5, '2026-08-02 05:50:17', '2026-08-02 05:50:52'),
	(27, 4, 'TERR 6', 4, 4, 666, 332, 'redonda', 1, 6, '2026-08-02 05:50:17', '2026-08-02 05:50:52'),
	(28, 4, 'TERR 7', 4, 4, 531, 419, 'redonda', 1, 7, '2026-08-02 05:50:17', '2026-08-02 05:50:52'),
	(29, 4, 'TERR 8', 4, 4, 339, 461, 'redonda', 1, 8, '2026-08-02 05:50:17', '2026-08-02 05:50:52'),
	(30, 4, 'TERR 9', 4, 4, 134, 428, 'redonda', 1, 9, '2026-08-02 05:50:17', '2026-08-02 05:50:52'),
	(31, 4, 'TERR 10', 4, 4, 24, 307, 'redonda', 1, 10, '2026-08-02 05:50:17', '2026-08-02 05:50:52'),
	(32, 5, 'BARR 1', 4, 4, 40, 40, 'redonda', 1, 1, '2026-08-02 05:51:11', '2026-08-02 05:51:11'),
	(33, 5, 'BARR 2', 4, 4, 666, 405, 'redonda', 1, 2, '2026-08-02 05:51:11', '2026-08-02 05:51:39'),
	(34, 5, 'BARR 3', 4, 4, 32, 401, 'redonda', 1, 3, '2026-08-02 05:51:11', '2026-08-02 05:51:39'),
	(35, 5, 'BARR 4', 4, 4, 295, 310, 'redonda', 1, 4, '2026-08-02 05:51:11', '2026-08-02 05:51:39'),
	(36, 5, 'BARR 5', 4, 4, 294, 408, 'redonda', 1, 5, '2026-08-02 05:51:11', '2026-08-02 05:51:39'),
	(37, 5, 'BARR 6', 4, 4, 486, 45, 'redonda', 1, 6, '2026-08-02 05:51:11', '2026-08-02 05:51:39'),
	(38, 5, 'BARR 7', 4, 4, 483, 139, 'redonda', 1, 7, '2026-08-02 05:51:11', '2026-08-02 05:51:39'),
	(39, 5, 'BARR 8', 4, 4, 40, 140, 'redonda', 1, 8, '2026-08-02 05:51:11', '2026-08-02 05:51:11'),
	(40, 5, 'BARR 9', 4, 4, 664, 315, 'redonda', 1, 9, '2026-08-02 05:51:11', '2026-08-02 05:51:39'),
	(41, 5, 'BARR 10', 4, 4, 34, 309, 'redonda', 1, 10, '2026-08-02 05:51:11', '2026-08-02 05:51:39');

-- Volcando estructura para tabla carta_digital.pedidos
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_telefono` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_comprobante` enum('boleta','factura') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_documento` enum('dni','ruc') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_documento` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comprobante_serie` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comprobante_correlativo` int DEFAULT NULL,
  `comprobante_numero` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comprobante_id` int DEFAULT NULL,
  `sunat_estado` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sunat_mensaje` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_entrega` enum('recojo','delivery','comer_aqui') COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referencia` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metodo_pago` enum('efectivo','yape_plin','tarjeta') COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('pendiente','pagado','en_preparacion','en_camino','entregado','cancelado') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `subtotal` decimal(10,2) NOT NULL,
  `costo_delivery` decimal(10,2) DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL,
  `notas` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `culqi_charge_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `facturacion_driver` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'native' COMMENT 'native|nubefact',
  `facturacion_estado` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente' COMMENT 'estado facturación',
  `facturacion_error` text COLLATE utf8mb4_unicode_ci,
  `facturacion_fecha` datetime DEFAULT NULL,
  `facturacion_intento` int DEFAULT '0',
  `cliente_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cliente_dni` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'DNI/RUC del cliente',
  `mesa_id` int DEFAULT NULL,
  `mesa_nombre` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zona_nombre` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caja_turno_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `idx_pedidos_comprobante_id` (`comprobante_id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.pedidos: ~49 rows (aproximadamente)
INSERT INTO `pedidos` (`id`, `codigo`, `cliente_nombre`, `cliente_telefono`, `tipo_comprobante`, `tipo_documento`, `numero_documento`, `comprobante_serie`, `comprobante_correlativo`, `comprobante_numero`, `comprobante_id`, `sunat_estado`, `sunat_mensaje`, `tipo_entrega`, `direccion`, `referencia`, `metodo_pago`, `estado`, `subtotal`, `costo_delivery`, `total`, `notas`, `culqi_charge_id`, `creado_en`, `facturacion_driver`, `facturacion_estado`, `facturacion_error`, `facturacion_fecha`, `facturacion_intento`, `cliente_email`, `cliente_dni`, `mesa_id`, `mesa_nombre`, `zona_nombre`, `caja_turno_id`) VALUES
	(1, 'PED-260721-076DC', 'sadasdasdasdas', '956464555', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'recojo', NULL, NULL, 'efectivo', 'pagado', 50.00, 0.00, 50.00, 'asdasd', NULL, '2026-07-21 06:11:42', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
	(2, 'PED-260721-46041', 'juancito el cachero', '999555888', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'recojo', NULL, NULL, 'tarjeta', 'pagado', 12.00, 0.00, 12.00, 'sin nada', NULL, '2026-07-21 06:15:21', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
	(3, 'PED-260721-5C8ED', 'cristhian matador', '956761889', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'recojo', NULL, NULL, 'yape_plin', 'pendiente', 60.00, 0.00, 60.00, 'nada', NULL, '2026-07-21 06:29:03', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
	(4, 'PED-260721-BA53D', 'aaaaaaaaaaaa', '999888555', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'recojo', NULL, NULL, 'yape_plin', 'pendiente', 12.00, 0.00, 12.00, 'sa', NULL, '2026-07-21 06:32:21', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
	(5, 'PED-260721-180DA', 'aaaaaaaaaaaaaaaaaaaaaaaaa', '999888555', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'recojo', NULL, NULL, 'yape_plin', 'cancelado', 78.00, 0.00, 78.00, NULL, 'chr_test_BUu2cBudZazrmbmy', '2026-07-21 06:55:30', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
	(6, 'PED-260721-0E9BA', 'cristhian', '956761889', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'recojo', NULL, NULL, 'tarjeta', 'pagado', 60.00, 0.00, 60.00, 'asd', 'chr_test_zlS7U87Jb4hWdQD8', '2026-07-21 07:05:26', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
	(7, 'PED-260721-08487', 'asdasd', '956761889', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'recojo', NULL, NULL, 'tarjeta', 'pagado', 32.00, 0.00, 32.00, 'asd', 'chr_test_mo9iGsYb5xtnGqLF', '2026-07-21 07:07:27', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
	(8, 'PED-260721-01ECB', 'dasdas', '156156', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'recojo', NULL, NULL, 'yape_plin', 'pagado', 18.00, 0.00, 18.00, 'sad', 'chr_test_99GDFAqBCaJp0WHV', '2026-07-21 17:30:47', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
	(9, 'PED-260729-71ABE', 'cristhian', '956761889', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'recojo', NULL, NULL, 'yape_plin', 'pagado', 110.00, 0.00, 110.00, NULL, 'chr_test_S7AzNysunRJTqwGC', '2026-07-29 00:06:36', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
	(10, 'PED-260729-3B50E', 'tester miñl', '956761889', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'recojo', NULL, NULL, 'yape_plin', 'pagado', 54.00, 0.00, 54.00, NULL, 'chr_test_8hkoXMEzzwJfaNAo', '2026-07-29 00:12:08', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
	(11, 'PED-260729-D98F7', 'yoo', '956761889', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'recojo', NULL, NULL, 'yape_plin', 'pagado', 36.00, 0.00, 36.00, NULL, 'chr_test_n73gcG1GtoEL5eR8', '2026-07-29 00:16:00', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
	(12, 'PED-260729-8F80D', 'crisssthiannn', '987548657', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'recojo', NULL, NULL, 'yape_plin', 'pagado', 18.00, 0.00, 18.00, NULL, 'chr_test_v2lBkSDAgSnOZjCg', '2026-07-29 00:53:50', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
	(13, 'PED-260729-7BE6B', 'crisssthiannn', '987548657', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'recojo', NULL, NULL, 'yape_plin', 'pagado', 72.00, 0.00, 72.00, NULL, 'chr_test_Ik0DwLBV5p7k6rXQ', '2026-07-29 00:57:26', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
	(14, 'PED-260729-D40B7', 'crissss', '999555444', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'recojo', NULL, NULL, 'yape_plin', 'pagado', 18.00, 0.00, 18.00, 'a', 'chr_test_7tnTfUGgCy6YkBNI', '2026-07-29 00:59:21', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
	(15, 'PED-260729-D7830', 'cristhian coronado', '956761889', 'boleta', 'dni', '72115227', 'B001', 1, 'B001-00000001', 1, 'aceptado', 'La Boleta numero B001-1, ha sido aceptada', 'recojo', NULL, NULL, 'yape_plin', 'pagado', 18.00, 0.00, 18.00, 'aaaa', 'chr_test_9ar6vf8j9eBsopJr', '2026-07-29 01:34:06', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
	(16, 'PED-260729-D87D6', 'crisssthiannnnnnnnnnnnnnnnnnnnnnnnnn', '999555888', 'boleta', 'dni', '12534875', 'B001', 2, 'B001-00000002', 2, 'aceptado', 'La Boleta numero B001-2, ha sido aceptada', 'recojo', NULL, NULL, 'yape_plin', 'pagado', 18.00, 0.00, 18.00, NULL, 'chr_test_EYkeibycLhqwA1WW', '2026-07-29 01:47:04', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
	(17, 'PED-260729-38538', 'cristhiancitoi boleteado', '956761889', 'boleta', 'dni', '72115227', 'B001', 3, 'B001-00000003', 3, 'aceptado', 'La Boleta numero B001-3, ha sido aceptada', 'recojo', NULL, NULL, 'yape_plin', 'pagado', 18.00, 0.00, 18.00, NULL, 'chr_test_O764RjZYFX8luADa', '2026-07-29 02:09:21', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
	(19, 'PED-260730-02F60', 'CORONADO DE LA CRUZ CRISTHIAN ADRIAN', '+51999999999', 'boleta', 'dni', '72115227', 'B001', 7, 'B001-00000007', 5, 'aceptado', 'La Boleta numero B001-7, ha sido aceptada', 'recojo', NULL, NULL, 'yape_plin', 'pagado', 36.00, 0.00, 36.00, NULL, 'chr_test_BFLERXWId6AW87Vx', '2026-07-30 00:58:12', 'native', 'pendiente', NULL, NULL, 0, 'zeta72115227@gmail.com', '72115227', NULL, NULL, NULL, NULL),
	(20, 'PED-260730-3ABCE', 'CORONADO DE LA CRUZ CRISTHIAN ADRIAN', '+51956761889', 'boleta', 'dni', '72115227', 'B001', 8, 'B001-00000008', 6, 'aceptado', 'La Boleta numero B001-8, ha sido aceptada', 'recojo', NULL, NULL, 'yape_plin', 'en_preparacion', 72.00, 0.00, 72.00, NULL, 'chr_test_zwzXvADz4xwx53eJ', '2026-07-30 01:02:01', 'native', 'pendiente', NULL, NULL, 0, 'zeta72115227@gmail.com', '72115227', NULL, NULL, NULL, NULL),
	(21, 'PED-260730-851A2', 'JHANETH CORONADO', '+51954654888', 'boleta', 'dni', '70606878', 'B001', 9, 'B001-00000009', 7, 'aceptado', 'La Boleta numero B001-9, ha sido aceptada', 'recojo', NULL, NULL, 'yape_plin', 'entregado', 14.90, 0.00, 14.90, NULL, 'chr_test_KqiJOEmlFGsWWqNz', '2026-07-30 04:43:10', 'native', 'pendiente', NULL, NULL, 0, 'ZEAS@GMAIL.COM', '70606878', NULL, NULL, NULL, NULL),
	(22, 'PED-260730-4E404', 'JHANETH CORONADO', '956761889', 'boleta', 'dni', '70606878', 'B001', 10, 'B001-00000010', 8, 'aceptado', 'La Boleta numero B001-10, ha sido aceptada', 'recojo', NULL, NULL, 'yape_plin', 'entregado', 40.80, 0.00, 40.80, NULL, 'chr_test_EpJQAYOF4PKnLRal', '2026-07-30 04:49:10', 'native', 'pendiente', NULL, NULL, 0, 'ojtej@gmail.com', '70606878', NULL, NULL, NULL, NULL),
	(23, 'PED-260730-54A0E', 'CORONADO DE LA CRUZ JHANETH', '+51956761889', 'boleta', 'dni', '70606878', 'B001', 11, 'B001-00000011', 9, 'aceptado', 'La Boleta numero B001-11, ha sido aceptada', 'recojo', NULL, NULL, 'yape_plin', 'pagado', 14.90, 0.00, 14.90, NULL, 'chr_test_nYIkKQxxXBfdN7MO', '2026-07-30 19:58:29', 'native', 'pendiente', NULL, NULL, 0, 'admin@admin.com', '70606878', NULL, NULL, NULL, NULL),
	(24, 'PED-260730-68406', 'DE LA CRUZ GUILLEN HILDA', '+51956761889', 'boleta', 'dni', '10131343', 'B001', 12, 'B001-00000012', 10, 'aceptado', 'La Boleta numero B001-12, ha sido aceptada', 'delivery', 'calle numero blaaa', 'aqui loco', 'yape_plin', 'pagado', 19.90, 5.00, 24.90, NULL, 'chr_test_LA8Z2ulet5U0MMgq', '2026-07-30 20:10:23', 'native', 'pendiente', NULL, NULL, 0, 'hilda@gmail.com', '10131343', NULL, NULL, NULL, NULL),
	(25, 'PED-260730-B26E4', 'CORONADO DE LA CRUZ CRISTHIAN ADRIAN', '+5195761889', 'boleta', 'dni', '72115227', 'B001', 13, 'B001-00000013', 11, 'aceptado', 'La Boleta numero B001-13, ha sido aceptada', 'recojo', NULL, NULL, 'yape_plin', 'pagado', 14.90, 0.00, 14.90, NULL, 'chr_test_HiwBA9FDINTBRWuA', '2026-07-30 20:40:19', 'native', 'pendiente', NULL, NULL, 0, 'cristhiandesnukador@gmail.com', '72115227', NULL, NULL, NULL, NULL),
	(26, 'PED-260730-98E5E', 'CORONADO DE LA CRUZ JHANETH', '+51956761889', 'boleta', 'dni', '70606878', NULL, NULL, NULL, NULL, NULL, NULL, 'recojo', NULL, NULL, 'yape_plin', 'pagado', 43.80, 0.00, 43.80, NULL, 'chr_test_HHbrQuNCs2pWExmp', '2026-07-30 20:47:22', 'native', 'pendiente', NULL, NULL, 0, 'zeta72115227@gmail.com', '70606878', NULL, NULL, NULL, NULL),
	(27, 'PED-260730-D1679', 'DE LA CRUZ GUILLEN HILDA', '+51956761889', 'boleta', 'dni', '10131343', 'BBB1', 1, 'BBB1-000001', 12, 'aceptado', 'Comprobante generado vía NubeFacT.', 'recojo', NULL, NULL, 'yape_plin', 'pagado', 43.80, 0.00, 43.80, NULL, 'chr_test_WV9WJdyCYIo03YDy', '2026-07-30 20:50:10', 'native', 'pendiente', NULL, NULL, 0, 'CARLITOS@GMAIL.COM', '10131343', NULL, NULL, NULL, NULL),
	(28, 'PED-260730-5F7AD', 'CORONADO DE LA CRUZ JHANETH', '+51989464654', 'boleta', 'dni', '70606878', 'BBB1', 2, 'BBB1-000002', 13, 'aceptado', 'Comprobante generado vía NubeFacT.', 'recojo', NULL, NULL, 'tarjeta', 'pagado', 39.80, 0.00, 39.80, NULL, 'chr_test_sBtifMEZ2NVIarm6', '2026-07-30 21:30:30', 'native', 'pendiente', NULL, NULL, 0, 'ZEAS@GMAIL.COM', '70606878', NULL, NULL, NULL, NULL),
	(29, 'PED-260730-EAE9B', 'CORONADO DE LA CRUZ JHANETH', '+51989464654', 'boleta', 'dni', '70606878', 'BBB1', 3, 'BBB1-000003', 14, 'aceptado', 'Comprobante generado vía NubeFacT.', 'recojo', NULL, NULL, 'tarjeta', 'pagado', 43.80, 0.00, 43.80, NULL, 'chr_test_7uxm2IUl9LdnNtti', '2026-07-30 21:33:58', 'native', 'pendiente', NULL, NULL, 0, 'ZEAS@GMAIL.COM', '70606878', NULL, NULL, NULL, NULL),
	(30, 'PED-260730-97578', 'CORONADO DE LA CRUZ JHANETH', '+51989464654', 'boleta', 'dni', '70606878', 'BBB1', 4, 'BBB1-000004', 15, 'aceptado', 'Comprobante generado vía NubeFacT.', 'recojo', NULL, NULL, 'yape_plin', 'pagado', 63.70, 0.00, 63.70, NULL, 'chr_test_9hXes9KIv6ETf3XI', '2026-07-30 23:50:18', 'native', 'pendiente', NULL, NULL, 0, 'ZEAS@GMAIL.COM', '70606878', NULL, NULL, NULL, NULL),
	(31, 'PED-260802-A1E96', 'CORONADO DE LA CRUZ JHANETH', '+51956761889', 'boleta', 'dni', '70606878', 'BBB1', 7, 'BBB1-000007', 16, 'error', 'Este documento ya existe en NubeFacT', 'comer_aqui', NULL, NULL, 'efectivo', 'cancelado', 14.90, 0.00, 14.90, ' | Facturado en PED-260802-D07E4', NULL, '2026-08-02 04:23:07', 'native', 'pendiente', NULL, NULL, 0, 'zeta72115227@gmail.com', '70606878', 6, 'mesa test', 'Salon principal', NULL),
	(32, 'PED-260802-F38DB', 'Mesa Mesa 3', '999999999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'comer_aqui', NULL, NULL, 'efectivo', 'cancelado', 58.70, 0.00, 58.70, 'Comanda POS | Facturado en PED-260802-B8F78', NULL, '2026-08-02 05:01:57', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, 3, 'Mesa 3', 'Salon principal', 1),
	(33, 'PED-260802-653A8', 'Mesa Mesa 3', '999999999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'comer_aqui', NULL, NULL, 'efectivo', 'cancelado', 19.90, 0.00, 19.90, 'Comanda POS | Facturado en PED-260802-B8F78', NULL, '2026-08-02 05:02:54', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, 3, 'Mesa 3', 'Salon principal', 1),
	(34, 'PED-260802-7DD51', 'Mesa Mesa 3', '999999999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'comer_aqui', NULL, NULL, 'efectivo', 'cancelado', 19.90, 0.00, 19.90, 'Comanda POS | Facturado en PED-260802-B8F78', NULL, '2026-08-02 05:03:02', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, 3, 'Mesa 3', 'Salon principal', 1),
	(35, 'PED-260802-BDE9C', 'Mesa Mesa 1', '999999999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'comer_aqui', NULL, NULL, 'efectivo', 'cancelado', 52.00, 0.00, 52.00, 'Comanda POS | Facturado en PED-260802-22083', NULL, '2026-08-02 05:03:36', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, 1, 'Mesa 1', 'Salon principal', 1),
	(36, 'PED-260802-B8F78', 'CLIENTINN', '999999999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'comer_aqui', NULL, NULL, 'efectivo', 'entregado', 98.50, 0.00, 98.50, 'NO9TAS DEL CLINETE', NULL, '2026-08-02 05:04:49', 'native', 'pendiente', NULL, NULL, 0, '', '00000000', 3, 'Mesa 3', 'Salon principal', 1),
	(37, 'PED-260802-22083', 'CORONADO DE LA CRUZ CRISTHIAN ADRIAN', '956761889', 'boleta', 'dni', '72115227', 'BBB1', 10, 'BBB1-000010', 17, 'error', 'Este documento ya existe en NubeFacT', 'comer_aqui', NULL, NULL, 'efectivo', 'entregado', 52.00, 0.00, 52.00, 'TUDUBEMM', NULL, '2026-08-02 05:09:28', 'native', 'pendiente', NULL, NULL, 0, '', '72115227', 1, 'Mesa 1', 'Salon principal', 1),
	(38, 'PED-260802-D07E4', 'CORONADO DE LA CRUZ JHANETH', '999999999', 'boleta', 'dni', '70606878', 'BBB1', 13, 'BBB1-000013', 18, 'error', 'Este documento ya existe en NubeFacT', 'comer_aqui', NULL, NULL, 'efectivo', 'entregado', 14.90, 0.00, 14.90, 'Liquidación POS de mesa', NULL, '2026-08-02 05:12:24', 'native', 'pendiente', NULL, NULL, 0, '', '70606878', 6, 'mesa test', 'Salon principal', 1),
	(39, 'PED-260802-C5524', 'Mesa mesa test', '999999999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'comer_aqui', NULL, NULL, 'efectivo', 'cancelado', 56.70, 0.00, 56.70, 'Comanda POS | Facturado en PED-260802-D3F76', NULL, '2026-08-02 05:19:21', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, 6, 'mesa test', 'Salon principal', 1),
	(40, 'PED-260802-D3F76', 'CORONADO DE LA CRUZ JHANETH', '999999999', 'boleta', 'dni', '70606878', 'BBB9', 1, 'BBB9-000001', 19, 'error', 'Serie No puedes emitir comprobantes con esta serie\'', 'comer_aqui', NULL, NULL, 'efectivo', 'entregado', 56.70, 0.00, 56.70, 'Liquidación POS de mesa', NULL, '2026-08-02 05:19:47', 'native', 'pendiente', NULL, NULL, 0, '', '70606878', 6, 'mesa test', 'Salon principal', 1),
	(41, 'PED-260802-AD321', 'Mesa Mesa 2', '999999999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'comer_aqui', NULL, NULL, 'efectivo', 'cancelado', 36.80, 0.00, 36.80, 'Comanda POS | Facturado en PED-260802-BB20D', NULL, '2026-08-02 05:22:37', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, 2, 'Mesa 2', 'Salon principal', 1),
	(42, 'PED-260802-BB20D', 'CORONADO DE LA CRUZ JHANETH', '999999999', 'boleta', 'dni', '70606878', 'BBB1', 15, 'BBB1-000015', 20, 'aceptado', 'Comprobante generado vía NubeFacT.', 'comer_aqui', NULL, NULL, 'efectivo', 'entregado', 36.80, 0.00, 36.80, 'Liquidación POS de mesa', NULL, '2026-08-02 05:22:49', 'native', 'pendiente', NULL, NULL, 0, '', '70606878', 2, 'Mesa 2', 'Salon principal', 1),
	(43, 'PED-260802-679E3', 'DE LA CRUZ GUILLEN HILDA', '+51956761889', 'boleta', 'dni', '10131343', 'BBB1', 16, 'BBB1-000016', 21, 'aceptado', 'Comprobante generado vía NubeFacT.', 'comer_aqui', NULL, NULL, 'efectivo', 'cancelado', 36.80, 0.00, 36.80, ' | Facturado en PED-260802-8FACC', NULL, '2026-08-02 05:24:18', 'native', 'pendiente', NULL, NULL, 0, 'hilda@gmail.com', '10131343', 1, 'Mesa 1', 'Salon principal', NULL),
	(44, 'PED-260802-8FACC', 'BEST PERUVIAN IMPORT AND EXPORT E.I.R.L.', '999999999', 'factura', 'ruc', '20603034873', 'FFF1', 1, 'FFF1-000001', 22, 'aceptado', 'Comprobante generado vía NubeFacT.', 'comer_aqui', NULL, NULL, 'efectivo', 'entregado', 36.80, 0.00, 36.80, 'Liquidación POS de mesa', NULL, '2026-08-02 05:33:57', 'native', 'pendiente', NULL, NULL, 0, '', NULL, 1, 'Mesa 1', 'Salon principal', 2),
	(45, 'PED-260802-0EA76', 'Mesa M 11', '999999999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'comer_aqui', NULL, NULL, 'efectivo', 'pendiente', 33.80, 0.00, 33.80, 'Comanda POS', NULL, '2026-08-02 06:10:13', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, 17, 'M 11', 'SALON PRINCIPAL', 3),
	(46, 'PED-260802-AE9AF', 'Mesa M 11', '999999999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'comer_aqui', NULL, NULL, 'efectivo', 'entregado', 41.70, 0.00, 41.70, 'Comanda POS', NULL, '2026-08-02 06:10:23', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, 17, 'M 11', 'SALON PRINCIPAL', 3),
	(47, 'PED-260802-E0130', 'Mesa M 14', '999999999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'comer_aqui', NULL, NULL, 'efectivo', 'entregado', 137.60, 0.00, 137.60, 'Comanda POS', NULL, '2026-08-02 06:15:32', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, 20, 'M 14', 'SALON PRINCIPAL', 3),
	(48, 'PED-260802-5A683', 'CORONADO DE LA CRUZ CRISTHIAN ADRIAN', '+51956761889', 'boleta', 'dni', '72115227', 'BBB1', 17, 'BBB1-000017', 23, 'aceptado', 'Comprobante generado vía NubeFacT.', 'recojo', NULL, NULL, 'efectivo', 'entregado', 13.90, 0.00, 13.90, NULL, NULL, '2026-08-02 06:39:36', 'native', 'pendiente', NULL, NULL, 0, 'zeta72115227@gmail.com', '72115227', NULL, NULL, NULL, NULL),
	(49, 'PED-260802-AE59F', 'Mesa M 6', '999999999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'comer_aqui', NULL, NULL, 'efectivo', 'entregado', 16.90, 0.00, 16.90, 'Comanda POS', NULL, '2026-08-02 06:53:38', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, 12, 'M 6', 'SALON PRINCIPAL', 3),
	(50, 'PED-260802-AE2F5', 'Mesa M 3', '999999999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'comer_aqui', NULL, NULL, 'efectivo', 'entregado', 27.80, 0.00, 27.80, 'Comanda POS', NULL, '2026-08-02 06:55:11', 'native', 'pendiente', NULL, NULL, 0, NULL, NULL, 9, 'M 3', 'SALON PRINCIPAL', 3);

-- Volcando estructura para tabla carta_digital.pedido_detalle
CREATE TABLE IF NOT EXISTS `pedido_detalle` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pedido_id` int NOT NULL,
  `producto_id` int DEFAULT NULL,
  `nombre_producto` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `cantidad` int NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `opciones_json` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `pedido_id` (`pedido_id`),
  CONSTRAINT `pedido_detalle_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.pedido_detalle: ~73 rows (aproximadamente)
INSERT INTO `pedido_detalle` (`id`, `pedido_id`, `producto_id`, `nombre_producto`, `precio_unitario`, `cantidad`, `subtotal`, `opciones_json`) VALUES
	(1, 1, 1, '1/4 de pollo + papas', 18.00, 1, 18.00, NULL),
	(2, 1, 2, '1/2 pollo + papas', 32.00, 1, 32.00, NULL),
	(3, 2, 4, 'Papas a la huancaína', 12.00, 1, 12.00, NULL),
	(4, 3, 3, 'Pollo entero + papas', 60.00, 1, 60.00, NULL),
	(5, 4, 4, 'Papas a la huancaína', 12.00, 1, 12.00, NULL),
	(6, 5, 1, '1/4 de pollo + papas', 18.00, 1, 18.00, NULL),
	(7, 5, 3, 'Pollo entero + papas', 60.00, 1, 60.00, NULL),
	(8, 6, 3, 'Pollo entero + papas', 60.00, 1, 60.00, NULL),
	(9, 7, 2, '1/2 pollo + papas', 32.00, 1, 32.00, NULL),
	(10, 8, 1, '1/4 de pollo + papas', 18.00, 1, 18.00, NULL),
	(11, 9, 1, 'PIZCITAAA', 18.00, 1, 18.00, NULL),
	(12, 9, 2, '1/2 pollo + papas', 32.00, 1, 32.00, NULL),
	(13, 9, 3, 'Pollo entero + papas', 60.00, 1, 60.00, NULL),
	(14, 10, 1, 'PIZCITAAA', 18.00, 3, 54.00, NULL),
	(15, 11, 1, 'PIZCITAAA', 18.00, 2, 36.00, NULL),
	(16, 12, 1, 'PIZCITAAA', 18.00, 1, 18.00, NULL),
	(17, 13, 1, 'PIZCITAAA', 18.00, 4, 72.00, NULL),
	(18, 14, 1, 'PIZCITAAA', 18.00, 1, 18.00, NULL),
	(19, 15, 1, 'PIZCITAAA', 18.00, 1, 18.00, NULL),
	(20, 16, 1, 'PIZCITAAA', 18.00, 1, 18.00, NULL),
	(21, 17, 1, 'PIZCITAAA', 18.00, 1, 18.00, NULL),
	(23, 19, 1, 'PIZCITAAA', 18.00, 2, 36.00, NULL),
	(24, 20, 1, 'PIZCITAAA', 18.00, 4, 72.00, NULL),
	(25, 21, 6, 'La Clásica Burger', 14.90, 1, 14.90, '[{"grupo_id":1,"grupo_nombre":"Elije tus Cremas","opcion_id":1,"opcion_nombre":"MAYONESA","precio_extra":0},{"grupo_id":1,"grupo_nombre":"Elije tus Cremas","opcion_id":2,"opcion_nombre":"MOSTAZA","precio_extra":0},{"grupo_id":1,"grupo_nombre":"Elije tus Cremas","opcion_id":3,"opcion_nombre":"AJI","precio_extra":0}]'),
	(26, 22, 7, 'Crispy Chicken Melt', 16.90, 1, 16.90, NULL),
	(27, 22, 9, 'La Extrema Monster', 23.90, 1, 23.90, NULL),
	(28, 23, 6, 'La Clásica Burger', 14.90, 1, 14.90, '[{"grupo_id":1,"grupo_nombre":"Elije tus Cremas","opcion_id":1,"opcion_nombre":"MAYONESA","precio_extra":0},{"grupo_id":1,"grupo_nombre":"Elije tus Cremas","opcion_id":2,"opcion_nombre":"MOSTAZA","precio_extra":0}]'),
	(29, 24, 8, 'Bacon Cheddar Smash (Doble)', 19.90, 1, 19.90, NULL),
	(30, 25, 6, 'La Clásica Burger', 14.90, 1, 14.90, '[{"grupo_id":1,"grupo_nombre":"Elije tus Cremas","opcion_id":2,"opcion_nombre":"MOSTAZA","precio_extra":0},{"grupo_id":1,"grupo_nombre":"Elije tus Cremas","opcion_id":3,"opcion_nombre":"AJI","precio_extra":0}]'),
	(31, 26, 8, 'Bacon Cheddar Smash (Doble)', 19.90, 1, 19.90, NULL),
	(32, 26, 9, 'La Extrema Monster', 23.90, 1, 23.90, NULL),
	(33, 27, 8, 'Bacon Cheddar Smash (Doble)', 19.90, 1, 19.90, NULL),
	(34, 27, 9, 'La Extrema Monster', 23.90, 1, 23.90, NULL),
	(35, 28, 8, 'Bacon Cheddar Smash (Doble)', 19.90, 2, 39.80, NULL),
	(36, 29, 9, 'La Extrema Monster', 23.90, 1, 23.90, NULL),
	(37, 29, 8, 'Bacon Cheddar Smash (Doble)', 19.90, 1, 19.90, NULL),
	(38, 30, 8, 'Bacon Cheddar Smash (Doble)', 19.90, 2, 39.80, NULL),
	(39, 30, 9, 'La Extrema Monster', 23.90, 1, 23.90, NULL),
	(40, 31, 6, 'La Clásica Burger', 14.90, 1, 14.90, '[{"grupo_id":1,"grupo_nombre":"Elije tus Cremas","opcion_id":1,"opcion_nombre":"MAYONESA","precio_extra":0},{"grupo_id":1,"grupo_nombre":"Elije tus Cremas","opcion_id":2,"opcion_nombre":"MOSTAZA","precio_extra":0}]'),
	(41, 32, 6, 'La Clásica Burger', 14.90, 1, 14.90, '[{"grupo_id":1,"grupo_nombre":"Elije tus Cremas","opcion_id":2,"opcion_nombre":"MOSTAZA","precio_extra":0},{"grupo_id":1,"grupo_nombre":"Elije tus Cremas","opcion_id":3,"opcion_nombre":"AJI","precio_extra":0},{"grupo_id":1,"grupo_nombre":"Elije tus Cremas","opcion_id":4,"opcion_nombre":"KETCHUP","precio_extra":0}]'),
	(42, 32, 8, 'Bacon Cheddar Smash (Doble)', 19.90, 1, 19.90, NULL),
	(43, 32, 9, 'La Extrema Monster', 23.90, 1, 23.90, NULL),
	(44, 33, 8, 'Bacon Cheddar Smash (Doble)', 19.90, 1, 19.90, NULL),
	(45, 34, 13, 'Mega Crack Box (Individual XL)', 19.90, 1, 19.90, NULL),
	(46, 35, 11, 'Dúo Crujiente (Para 2)', 26.00, 2, 52.00, NULL),
	(47, 36, 6, 'La Clásica Burger', 14.90, 1, 14.90, '[{"grupo_id":1,"grupo_nombre":"Elije tus Cremas","opcion_id":2,"opcion_nombre":"MOSTAZA","precio_extra":0},{"grupo_id":1,"grupo_nombre":"Elije tus Cremas","opcion_id":3,"opcion_nombre":"AJI","precio_extra":0},{"grupo_id":1,"grupo_nombre":"Elije tus Cremas","opcion_id":4,"opcion_nombre":"KETCHUP","precio_extra":0}]'),
	(48, 36, 8, 'Bacon Cheddar Smash (Doble)', 19.90, 1, 19.90, NULL),
	(49, 36, 9, 'La Extrema Monster', 23.90, 1, 23.90, NULL),
	(50, 36, 8, 'Bacon Cheddar Smash (Doble)', 19.90, 1, 19.90, NULL),
	(51, 36, 13, 'Mega Crack Box (Individual XL)', 19.90, 1, 19.90, NULL),
	(52, 37, 11, 'Dúo Crujiente (Para 2)', 26.00, 2, 52.00, NULL),
	(53, 38, 6, 'La Clásica Burger', 14.90, 1, 14.90, '[{"grupo_id":1,"grupo_nombre":"Elije tus Cremas","opcion_id":1,"opcion_nombre":"MAYONESA","precio_extra":0},{"grupo_id":1,"grupo_nombre":"Elije tus Cremas","opcion_id":2,"opcion_nombre":"MOSTAZA","precio_extra":0}]'),
	(54, 39, 8, 'Bacon Cheddar Smash (Doble)', 19.90, 1, 19.90, NULL),
	(55, 39, 7, 'Crispy Chicken Melt', 16.90, 1, 16.90, NULL),
	(56, 39, 13, 'Mega Crack Box (Individual XL)', 19.90, 1, 19.90, NULL),
	(57, 40, 8, 'Bacon Cheddar Smash (Doble)', 19.90, 1, 19.90, NULL),
	(58, 40, 7, 'Crispy Chicken Melt', 16.90, 1, 16.90, NULL),
	(59, 40, 13, 'Mega Crack Box (Individual XL)', 19.90, 1, 19.90, NULL),
	(60, 41, 7, 'Crispy Chicken Melt', 16.90, 1, 16.90, NULL),
	(61, 41, 8, 'Bacon Cheddar Smash (Doble)', 19.90, 1, 19.90, NULL),
	(62, 42, 7, 'Crispy Chicken Melt', 16.90, 1, 16.90, NULL),
	(63, 42, 8, 'Bacon Cheddar Smash (Doble)', 19.90, 1, 19.90, NULL),
	(64, 43, 7, 'Crispy Chicken Melt', 16.90, 1, 16.90, NULL),
	(65, 43, 8, 'Bacon Cheddar Smash (Doble)', 19.90, 1, 19.90, NULL),
	(66, 44, 7, 'Crispy Chicken Melt', 16.90, 1, 16.90, NULL),
	(67, 44, 8, 'Bacon Cheddar Smash (Doble)', 19.90, 1, 19.90, NULL),
	(68, 45, 7, 'Crispy Chicken Melt', 16.90, 2, 33.80, NULL),
	(69, 46, 10, 'Broaster Crunch (Personal)', 13.90, 3, 41.70, NULL),
	(70, 47, 8, 'Bacon Cheddar Smash (Doble)', 19.90, 2, 39.80, NULL),
	(71, 47, 12, 'Banquete Broastero (Familiar)', 48.90, 2, 97.80, NULL),
	(72, 48, 10, 'Broaster Crunch (Personal)', 13.90, 1, 13.90, NULL),
	(73, 49, 7, 'Crispy Chicken Melt', 16.90, 1, 16.90, NULL),
	(74, 50, 10, 'Broaster Crunch (Personal)', 13.90, 2, 27.80, NULL);

-- Volcando estructura para tabla carta_digital.productos
CREATE TABLE IF NOT EXISTS `productos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `categoria_id` int NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `precio_oferta` decimal(10,2) DEFAULT NULL,
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disponible` tinyint(1) DEFAULT '1',
  `destacado` tinyint(1) DEFAULT '0',
  `orden` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `categoria_id` (`categoria_id`),
  CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.productos: ~8 rows (aproximadamente)
INSERT INTO `productos` (`id`, `categoria_id`, `nombre`, `descripcion`, `precio`, `precio_oferta`, `imagen`, `disponible`, `destacado`, `orden`) VALUES
	(6, 9, 'La Clásica Burger', 'ugosa carne de res de 150g, queso cheddar fundido, lechuga fresca, tomate, pepinillos y nuestra salsa especial de la casa en pan brioche sellado con mantequilla.', 18.00, 14.90, 'img_6a6ad0bfaa69d0.31366046.png', 1, 1, 0),
	(7, 9, 'Crispy Chicken Melt', 'Pechuga de pollo crujiente empanizada al estilo crispy, doble queso americano, ensalada coleslaw fresca y mayonesa ahumada en pan brioche.', 20.00, 16.90, 'img_6a6ad0f2726711.51756337.jpg', 1, 1, 0),
	(8, 9, 'Bacon Cheddar Smash (Doble)', 'Doble carne de res smash (160g total) con bordes crocantes, doble queso cheddar derretido, tocino ahumado crocante y salsa BBQ casera', 24.00, 19.90, 'img_6a6ad12e379283.06189002.jpg', 1, 1, 0),
	(9, 9, 'La Extrema Monster', 'Triple carne de res (240g), triple queso cheddar, tocino crocante, aros de cebolla empanizados, huevo frito y salsa tártara de la casa.', 28.00, 23.90, 'img_6a6ad15d7a7948.84725003.jpg', 1, 1, 0),
	(10, 10, 'Broaster Crunch (Personal)', '1 pieza de pollo ultra crujiente (pierna o pecho), papas fritas doraditas, ensalada fresca cole slaw y tus cremas favoritas.', 16.90, 13.90, 'img_6a6b6735ed0955.28355625.png', 1, 0, 0),
	(11, 10, 'Dúo Crujiente (Para 2)', '2 piezas de pollo broaster bien doradas + papas fritas medianas + ensalada fresca + 2 gaseosas de 500ml. ¡Ideal para compartir!', 32.00, 26.00, 'img_6a6b678652b088.86048272.png', 1, 0, 0),
	(12, 10, 'Banquete Broastero (Familiar)', '6 piezas de pollo crujiente + porción familiar de papas fritas + ensalada grande + gaseosa de 1.5L + todas tus cremas.', 58.00, 48.90, 'img_6a6b67d8285784.14634464.png', 1, 0, 0),
	(13, 10, 'Mega Crack Box (Individual XL)', '1 pieza de pollo broaster + 3 tenders crujientes + papas fritas + ensalada + gaseosa de 500ml. Cero hambre, pura crocancia.', 24.90, 19.90, 'img_6a6b92ec1bb479.68019973.png', 1, 0, 0);

-- Volcando estructura para tabla carta_digital.producto_grupos
CREATE TABLE IF NOT EXISTS `producto_grupos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('radio','checkbox') COLLATE utf8mb4_unicode_ci DEFAULT 'radio',
  `requerido` tinyint(1) DEFAULT '0',
  `min_opciones` int DEFAULT '0',
  `max_opciones` int DEFAULT '1',
  `orden` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `producto_grupos_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.producto_grupos: ~1 rows (aproximadamente)
INSERT INTO `producto_grupos` (`id`, `producto_id`, `nombre`, `tipo`, `requerido`, `min_opciones`, `max_opciones`, `orden`) VALUES
	(1, 6, 'Elije tus Cremas', 'checkbox', 0, 2, 5, 0);

-- Volcando estructura para tabla carta_digital.producto_ingredientes
CREATE TABLE IF NOT EXISTS `producto_ingredientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `ingrediente_id` int NOT NULL,
  `cantidad` decimal(10,3) NOT NULL DEFAULT '0.000',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prod_ing` (`producto_id`,`ingrediente_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.producto_ingredientes: ~1 rows (aproximadamente)
INSERT INTO `producto_ingredientes` (`id`, `producto_id`, `ingrediente_id`, `cantidad`) VALUES
	(1, 10, 1, 0.250);

-- Volcando estructura para tabla carta_digital.producto_opciones
CREATE TABLE IF NOT EXISTS `producto_opciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `grupo_id` int NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `precio_extra` decimal(10,2) DEFAULT '0.00',
  `disponible` tinyint(1) DEFAULT '1',
  `orden` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `grupo_id` (`grupo_id`),
  CONSTRAINT `producto_opciones_ibfk_1` FOREIGN KEY (`grupo_id`) REFERENCES `producto_grupos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.producto_opciones: ~6 rows (aproximadamente)
INSERT INTO `producto_opciones` (`id`, `grupo_id`, `nombre`, `precio_extra`, `disponible`, `orden`) VALUES
	(1, 1, 'MAYONESA', 0.00, 1, 0),
	(2, 1, 'MOSTAZA', 0.00, 1, 0),
	(3, 1, 'AJI', 0.00, 1, 0),
	(4, 1, 'KETCHUP', 0.00, 1, 0),
	(5, 1, 'ENSALADA', 0.00, 1, 0),
	(6, 1, 'CEBOLLA', 0.00, 1, 0);

-- Volcando estructura para procedimiento carta_digital.sp_obtener_siguiente_correlativo
DELIMITER //
CREATE PROCEDURE `sp_obtener_siguiente_correlativo`(
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
    
    SELECT ultimo_numero INTO p_numero 
    FROM facturacion_secuencias 
    WHERE driver = p_driver AND serie = p_serie
    FOR UPDATE;
    
    IF p_numero IS NULL THEN
        INSERT INTO facturacion_secuencias (driver, serie, tipo_comprobante, ultimo_numero)
        VALUES (p_driver, p_serie, p_tipo_comprobante, 1);
        SET p_numero = 1;
    ELSE
        SET p_numero = p_numero + 1;
        UPDATE facturacion_secuencias 
        SET ultimo_numero = p_numero
        WHERE driver = p_driver AND serie = p_serie;
    END IF;
    
    COMMIT;
END//
DELIMITER ;

-- Volcando estructura para vista carta_digital.v_comprobantes_aceptados
-- Creando tabla temporal para superar errores de dependencia de VIEW
CREATE TABLE `v_comprobantes_aceptados` (
	`id` INT(10) NOT NULL,
	`pedido_id` INT(10) NOT NULL,
	`driver` VARCHAR(20) NOT NULL COMMENT 'native|nubefact' COLLATE 'utf8mb4_unicode_ci',
	`numero_comprobante` VARCHAR(30) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`respondido_en` DATETIME NULL,
	`pedido_codigo` VARCHAR(20) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`cliente_nombre` VARCHAR(150) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`pdf_path` VARCHAR(255) NULL COLLATE 'utf8mb4_unicode_ci'
) ENGINE=MyISAM;

-- Volcando estructura para vista carta_digital.v_comprobantes_pendientes
-- Creando tabla temporal para superar errores de dependencia de VIEW
CREATE TABLE `v_comprobantes_pendientes` (
	`id` INT(10) NOT NULL,
	`pedido_id` INT(10) NOT NULL,
	`driver` VARCHAR(20) NOT NULL COMMENT 'native|nubefact' COLLATE 'utf8mb4_unicode_ci',
	`numero_comprobante` VARCHAR(30) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`estado` ENUM('pendiente','procesando','aceptado','observado','rechazado','error') NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`intentos_envio` INT(10) NULL,
	`ultimo_intento` DATETIME NULL,
	`pedido_codigo` VARCHAR(20) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`cliente_nombre` VARCHAR(150) NOT NULL COLLATE 'utf8mb4_unicode_ci'
) ENGINE=MyISAM;

-- Volcando estructura para tabla carta_digital.zonas_mesas
CREATE TABLE IF NOT EXISTS `zonas_mesas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
	`ancho` int NOT NULL DEFAULT '1000',
	`alto` int NOT NULL DEFAULT '620',
  `orden` int NOT NULL DEFAULT '0',
  `activa` tinyint(1) NOT NULL DEFAULT '1',
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla carta_digital.zonas_mesas: ~3 rows (aproximadamente)
INSERT INTO `zonas_mesas` (`id`, `nombre`, `ancho`, `alto`, `orden`, `activa`, `creado_en`) VALUES
	(3, 'SALON PRINCIPAL', 1000, 620, 1, 1, '2026-08-02 05:42:33'),
	(4, 'TERRAZA', 1000, 620, 2, 1, '2026-08-02 05:47:57'),
	(5, 'BARRA', 1200, 700, 3, 1, '2026-08-02 05:48:05');

-- Volcando estructura para vista carta_digital.v_comprobantes_aceptados
-- Eliminando tabla temporal y crear estructura final de VIEW
DROP TABLE IF EXISTS `v_comprobantes_aceptados`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_comprobantes_aceptados` AS select `fc`.`id` AS `id`,`fc`.`pedido_id` AS `pedido_id`,`fc`.`driver` AS `driver`,`fc`.`numero_comprobante` AS `numero_comprobante`,`fc`.`respondido_en` AS `respondido_en`,`p`.`codigo` AS `pedido_codigo`,`p`.`cliente_nombre` AS `cliente_nombre`,`fc`.`pdf_path` AS `pdf_path` from (`facturacion_comprobantes` `fc` join `pedidos` `p` on((`fc`.`pedido_id` = `p`.`id`))) where (`fc`.`estado` = 'aceptado') order by `fc`.`respondido_en` desc;

-- Volcando estructura para vista carta_digital.v_comprobantes_pendientes
-- Eliminando tabla temporal y crear estructura final de VIEW
DROP TABLE IF EXISTS `v_comprobantes_pendientes`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_comprobantes_pendientes` AS select `fc`.`id` AS `id`,`fc`.`pedido_id` AS `pedido_id`,`fc`.`driver` AS `driver`,`fc`.`numero_comprobante` AS `numero_comprobante`,`fc`.`estado` AS `estado`,`fc`.`intentos_envio` AS `intentos_envio`,`fc`.`ultimo_intento` AS `ultimo_intento`,`p`.`codigo` AS `pedido_codigo`,`p`.`cliente_nombre` AS `cliente_nombre` from (`facturacion_comprobantes` `fc` join `pedidos` `p` on((`fc`.`pedido_id` = `p`.`id`))) where ((`fc`.`estado` in ('pendiente','procesando','error')) and (`fc`.`intentos_envio` < 5)) order by (`fc`.`ultimo_intento` is null) desc,`fc`.`ultimo_intento`;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
