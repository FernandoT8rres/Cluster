-- Sistema de Banner Carrusel para Sign-in
-- Tabla para almacenar los banners del carrusel

USE claut_intranet;

-- Tabla de banners para el carrusel
CREATE TABLE IF NOT EXISTS banner_carrusel (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    imagen_url VARCHAR(500) NOT NULL,
    posicion INT DEFAULT 1,
    activo BOOLEAN DEFAULT TRUE,
    fecha_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_fin DATETIME NULL,
    creado_por INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Insertar banners de ejemplo
INSERT INTO banner_carrusel (titulo, descripcion, imagen_url, posicion, activo, creado_por) VALUES
('Bienvenido a Claut Intranet', 'Conecta, colabora y crece con el clúster automotriz líder de México', 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2340&q=80', 1, TRUE, 1),
('Innovación Automotriz', 'Impulsamos la transformación digital en la industria automotriz mexicana', 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2340&q=80', 2, TRUE, 1),
('Colaboración Empresarial', 'Fortalecemos las alianzas estratégicas entre empresas del sector automotriz', 'https://images.unsplash.com/photo-1560472355-109703aa3edc?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2126&q=80', 3, TRUE, 1);

-- Crear índices
CREATE INDEX idx_banner_activo ON banner_carrusel(activo);
CREATE INDEX idx_banner_posicion ON banner_carrusel(posicion);
CREATE INDEX idx_banner_fechas ON banner_carrusel(fecha_inicio, fecha_fin);
