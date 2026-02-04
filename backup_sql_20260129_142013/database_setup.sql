-- Base de datos para el sistema de descuentos Claut
CREATE DATABASE IF NOT EXISTS claut_intranet;
USE claut_intranet;

-- Tabla de descuentos
CREATE TABLE descuentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_empresa VARCHAR(255) NOT NULL,
    categoria VARCHAR(100) NOT NULL,
    porcentaje_descuento INT NOT NULL,
    descripcion TEXT,
    ubicacion TEXT,
    horario TEXT,
    como_aplicar TEXT,
    telefono VARCHAR(20),
    codigo_promocional VARCHAR(50),
    logo_url VARCHAR(500),
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Índices para optimización
CREATE INDEX idx_activo ON descuentos(activo);
CREATE INDEX idx_categoria ON descuentos(categoria);
CREATE INDEX idx_fecha_creacion ON descuentos(fecha_creacion);
