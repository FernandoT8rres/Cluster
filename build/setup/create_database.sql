-- Script SQL para crear e inicializar la base de datos claut_intranet
-- Ejecutar este script para crear la base de datos completa

CREATE DATABASE IF NOT EXISTS claut_intranet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE claut_intranet;

-- Tabla de empresas
CREATE TABLE IF NOT EXISTS empresas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    website VARCHAR(255),
    telefono VARCHAR(50),
    email VARCHAR(255),
    direccion TEXT,
    logo VARCHAR(255),
    estado ENUM('activa', 'inactiva') DEFAULT 'activa',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(50),
    empresa_id INT,
    rol ENUM('admin', 'empleado', 'moderador') DEFAULT 'empleado',
    estado ENUM('activo', 'inactivo', 'pendiente') DEFAULT 'pendiente',
    avatar VARCHAR(255),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE SET NULL
);

-- Resto de tablas
CREATE TABLE IF NOT EXISTS comites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    presidente_id INT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (presidente_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS comite_miembros (
    id INT PRIMARY KEY AUTO_INCREMENT,
    comite_id INT NOT NULL,
    usuario_id INT NOT NULL,
    cargo ENUM('presidente', 'vicepresidente', 'secretario', 'tesorero', 'vocal', 'miembro') DEFAULT 'miembro',
    fecha_ingreso TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    FOREIGN KEY (comite_id) REFERENCES comites(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_usuario_comite (comite_id, usuario_id)
);

CREATE TABLE IF NOT EXISTS eventos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME,
    ubicacion VARCHAR(255),
    organizador_id INT,
    comite_id INT,
    estado ENUM('programado', 'en_curso', 'finalizado', 'cancelado') DEFAULT 'programado',
    imagen VARCHAR(255),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organizador_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (comite_id) REFERENCES comites(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS boletines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(255) NOT NULL,
    contenido TEXT NOT NULL,
    resumen VARCHAR(500),
    autor_id INT,
    estado ENUM('borrador', 'publicado', 'archivado') DEFAULT 'borrador',
    fecha_publicacion DATETIME,
    imagen VARCHAR(255),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS documentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    archivo VARCHAR(255) NOT NULL,
    tipo_archivo VARCHAR(50),
    tamaño INT,
    categoria ENUM('general', 'comite', 'legal', 'financiero', 'tecnico') DEFAULT 'general',
    autor_id INT,
    comite_id INT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (comite_id) REFERENCES comites(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS descuentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    empresa_oferente VARCHAR(255) NOT NULL,
    porcentaje_descuento DECIMAL(5,2),
    codigo_descuento VARCHAR(100),
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    terminos_condiciones TEXT,
    imagen VARCHAR(255),
    estado ENUM('activo', 'inactivo', 'expirado') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contactos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefono VARCHAR(50),
    asunto VARCHAR(255) NOT NULL,
    mensaje TEXT NOT NULL,
    estado ENUM('pendiente', 'en_proceso', 'resuelto', 'cerrado') DEFAULT 'pendiente',
    respuesta TEXT,
    respondido_por INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_respuesta TIMESTAMP NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (respondido_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- DATOS DE EJEMPLO (con contraseñas: password123)
INSERT INTO empresas (nombre, descripcion, website, telefono, email, direccion) VALUES
('Autopartes México SA', 'Distribuidora de autopartes y refacciones', 'www.autopartesmexico.com', '555-1234567', 'info@autopartesmexico.com', 'Av. Industrial 123, CDMX'),
('Talleres Unidos SC', 'Red de talleres especializados', 'www.talleresunidos.com', '555-2345678', 'contacto@talleresunidos.com', 'Calle Mecánicos 45, CDMX'),
('Logística Automotriz', 'Servicios de logística para el sector automotriz', 'www.logisticaauto.com', '555-3456789', 'ventas@logisticaauto.com', 'Zona Industrial Norte, CDMX');

-- Usuarios de ejemplo (sin usuario administrador por defecto)
-- NOTA: El primer administrador debe ser creado manualmente después de la instalación
INSERT INTO usuarios (nombre, apellido, email, password, telefono, empresa_id, rol, estado) VALUES
('Fernando', 'Torres', 'fernando@claut.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-1111111', 1, 'empleado', 'activo'),
('María', 'González', 'maria@autopartes.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-2222222', 1, 'empleado', 'activo'),
('Carlos', 'Rodríguez', 'carlos@talleres.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-3333333', 2, 'empleado', 'activo');

-- Comités
INSERT INTO comites (nombre, descripcion, presidente_id) VALUES
('Comité de Calidad', 'Comité encargado de supervisar los estándares de calidad', 1),
('Comité de Innovación', 'Comité para promover la innovación tecnológica', 2),
('Comité de Capacitación', 'Comité para coordinar programas de capacitación', 3);

-- Miembros de comités
INSERT INTO comite_miembros (comite_id, usuario_id, cargo) VALUES
(1, 1, 'presidente'),
(1, 2, 'secretario'),
(1, 3, 'vocal'),
(2, 2, 'presidente'),
(2, 3, 'miembro'),
(3, 3, 'presidente'),
(3, 1, 'miembro');

-- Eventos
INSERT INTO eventos (titulo, descripcion, fecha_inicio, fecha_fin, ubicacion, organizador_id, comite_id) VALUES
('Reunión Mensual Comité de Calidad', 'Revisión de métricas de calidad del mes', '2025-08-15 10:00:00', '2025-08-15 12:00:00', 'Sala de Juntas A', 1, 1),
('Capacitación en Nuevas Tecnologías', 'Taller sobre innovaciones en el sector automotriz', '2025-08-20 09:00:00', '2025-08-20 17:00:00', 'Auditorio Principal', 2, 2),
('Evaluación de Proveedores', 'Sesión para evaluar proveedores actuales', '2025-08-25 14:00:00', '2025-08-25 16:00:00', 'Sala de Reuniones B', 3, 1);

-- Boletines
INSERT INTO boletines (titulo, contenido, resumen, autor_id, estado, fecha_publicacion) VALUES
('Bienvenidos a la Nueva Intranet', 'Nos complace anunciar el lanzamiento de nuestra nueva plataforma de intranet...', 'Lanzamiento de la nueva plataforma de intranet', 1, 'publicado', NOW()),
('Actualización de Políticas de Calidad', 'Se han actualizado las políticas de calidad siguiendo los nuevos estándares...', 'Nuevas políticas de calidad implementadas', 1, 'publicado', NOW()),
('Próximos Eventos del Mes', 'Te informamos sobre los próximos eventos programados para este mes...', 'Calendario de eventos del mes actual', 1, 'publicado', NOW());

-- Descuentos
INSERT INTO descuentos (titulo, descripcion, empresa_oferente, porcentaje_descuento, fecha_inicio, fecha_fin, codigo_descuento) VALUES
('Descuento en Herramientas', '20% de descuento en todas las herramientas especializadas', 'Herramientas Pro SA', 20.00, '2025-08-01', '2025-08-31', 'HERR20'),
('Capacitación Especializada', '15% de descuento en cursos de capacitación técnica', 'Instituto Técnico Automotriz', 15.00, '2025-08-01', '2025-09-30', 'CAPAC15'),
('Descuento en Refacciones', '10% de descuento en refacciones originales', 'Refacciones Originales México', 10.00, '2025-08-01', '2025-12-31', 'REF10');

SELECT 'Base de datos claut_intranet creada exitosamente con datos de ejemplo' as mensaje;
SELECT 'NOTA: No hay usuario administrador por defecto. Debe crear uno manualmente.' as nota_admin;