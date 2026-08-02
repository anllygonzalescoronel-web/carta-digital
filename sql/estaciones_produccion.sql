-- ============================================================
-- Estaciones de Producción (Cocina, Barra, Pollos, etc.)
-- ============================================================

CREATE TABLE IF NOT EXISTS estaciones_produccion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255) DEFAULT NULL,
    color VARCHAR(20) NOT NULL DEFAULT '#0f172a',
    icono VARCHAR(60) NOT NULL DEFAULT 'ti-chef-hat',
    orden INT NOT NULL DEFAULT 0,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Categorías asignadas a cada estación de producción
CREATE TABLE IF NOT EXISTS estacion_categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estacion_id INT NOT NULL,
    categoria_id INT NOT NULL,
    UNIQUE KEY uq_estacion_categoria (estacion_id, categoria_id),
    CONSTRAINT fk_estacion_cat_estacion FOREIGN KEY (estacion_id) REFERENCES estaciones_produccion(id) ON DELETE CASCADE,
    CONSTRAINT fk_estacion_cat_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Usuarios asignados a cada estación de producción
CREATE TABLE IF NOT EXISTS estacion_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estacion_id INT NOT NULL,
    usuario_id INT NOT NULL,
    UNIQUE KEY uq_estacion_usuario (estacion_id, usuario_id),
    CONSTRAINT fk_estacion_usr_estacion FOREIGN KEY (estacion_id) REFERENCES estaciones_produccion(id) ON DELETE CASCADE,
    CONSTRAINT fk_estacion_usr_usuario FOREIGN KEY (usuario_id) REFERENCES admin_usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;
