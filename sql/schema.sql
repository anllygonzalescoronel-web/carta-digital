-- ============================================
-- Carta Digital - Esquema de Base de Datos
-- ============================================
CREATE DATABASE IF NOT EXISTS carta_digital CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE carta_digital;

-- Usuarios administradores
CREATE TABLE IF NOT EXISTS admin_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Configuración general del negocio (clave/valor, muy flexible)
CREATE TABLE IF NOT EXISTS configuracion (
    clave VARCHAR(80) PRIMARY KEY,
    valor TEXT
) ENGINE=InnoDB;

-- Categorías de productos
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    orden INT DEFAULT 0,
    activo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

-- Productos
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(500),
    precio DECIMAL(10,2) NOT NULL,
    precio_oferta DECIMAL(10,2) DEFAULT NULL,
    imagen VARCHAR(255),
    disponible TINYINT(1) DEFAULT 1,
    destacado TINYINT(1) DEFAULT 0,
    orden INT DEFAULT 0,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Banners deslizantes (slider) de la portada
CREATE TABLE IF NOT EXISTS banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    imagen VARCHAR(255) NOT NULL,
    titulo VARCHAR(150),
    subtitulo VARCHAR(255),
    orden INT DEFAULT 0,
    activo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

-- Pedidos
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    cliente_nombre VARCHAR(150) NOT NULL,
    cliente_telefono VARCHAR(30) NOT NULL,
    tipo_entrega ENUM('recojo','delivery') NOT NULL,
    direccion VARCHAR(255),
    referencia VARCHAR(255),
    metodo_pago ENUM('efectivo','yape_plin','tarjeta') NOT NULL,
    estado ENUM('pendiente','pagado','en_preparacion','en_camino','entregado','cancelado') DEFAULT 'pendiente',
    subtotal DECIMAL(10,2) NOT NULL,
    costo_delivery DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    notas VARCHAR(500),
    culqi_charge_id VARCHAR(100) DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Detalle de cada pedido
CREATE TABLE IF NOT EXISTS pedido_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    producto_id INT,
    nombre_producto VARCHAR(150) NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    cantidad INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Datos iniciales
-- ============================================

-- Usuario admin por defecto -> usuario: admin / clave: admin123
-- (cambiar el hash generando uno nuevo desde admin/generar_hash.php o al primer login)
INSERT INTO admin_usuarios (usuario, password_hash, nombre) VALUES
('admin', '$2y$10$vO8.XETGJIEPqhwBxaPle.KKIRFDJm.L7xomfpbRz05ccTRHiXd2S', 'Administrador')
ON DUPLICATE KEY UPDATE usuario=usuario;
-- Nota: el hash de arriba corresponde a la clave "admin123" (bcrypt).

INSERT INTO configuracion (clave, valor) VALUES
('nombre_negocio', 'Pollería El Sabor'),
('logo', ''),
('color_primario', '#E8590C'),
('color_secundario', '#FFC107'),
('color_texto', '#212121'),
('color_fondo', '#FFF8F0'),
('whatsapp_numero', '51999999999'),
('direccion_local', 'Av. Principal 123, Lima'),
('costo_delivery', '5.00'),
('delivery_activo', '1'),
('recojo_activo', '1'),
('efectivo_activo', '1'),
('yape_plin_activo', '1'),
('tarjeta_activo', '1'),
('yape_plin_qr', ''),
('yape_plin_numero', '999999999'),
('culqi_public_key', 'pk_test_XXXXXXXXXXXXXXXXXXXX'),
('culqi_secret_key', 'sk_test_XXXXXXXXXXXXXXXXXXXX'),
('mensaje_bienvenida', '¡Bienvenido! Elige tus platos favoritos y haz tu pedido en segundos.')
ON DUPLICATE KEY UPDATE clave=clave;

INSERT INTO categorias (nombre, orden) VALUES
('Pollos a la brasa', 1),
('Broasters', 2),
('Entradas', 3),
('Bebidas', 4),
('Postres', 5)
ON DUPLICATE KEY UPDATE nombre=nombre;

INSERT INTO productos (categoria_id, nombre, descripcion, precio, imagen, disponible, orden) VALUES
(1, '1/4 de pollo + papas', 'Incluye papas fritas y ensalada criolla', 18.00, '', 1, 1),
(1, '1/2 pollo + papas', 'Incluye papas fritas, ensalada criolla y cremas', 32.00, '', 1, 2),
(1, 'Pollo entero + papas', 'Incluye papas fritas, ensalada criolla y cremas', 60.00, '', 1, 3),
(3, 'Papas a la huancaína', 'Porción para compartir', 12.00, '', 1, 1),
(4, 'Gaseosa 1L', 'Inca Kola, Coca Cola o Fanta', 8.00, '', 1, 1)
ON DUPLICATE KEY UPDATE nombre=nombre;
