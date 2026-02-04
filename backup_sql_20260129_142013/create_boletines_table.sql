-- Script SQL para crear la tabla de boletines - Claut Intranet
-- Base de datos: claut_intranet

USE claut_intranet;

-- Crear tabla de boletines si no existe
CREATE TABLE IF NOT EXISTS boletines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    contenido TEXT NOT NULL,
    estado ENUM('borrador', 'publicado', 'archivado') DEFAULT 'borrador',
    archivo_adjunto VARCHAR(255) NULL,
    visualizaciones INT DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_estado (estado),
    INDEX idx_fecha_creacion (fecha_creacion),
    FULLTEXT INDEX ft_titulo_contenido (titulo, contenido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar algunos datos de ejemplo (opcional)
-- Descomenta las siguientes líneas si quieres datos de prueba iniciales

/*
INSERT INTO boletines (titulo, contenido, estado) VALUES
('Bienvenido al Sistema de Boletines', 'Este es el primer boletín del sistema. Aquí podrás gestionar todos los boletines de la intranet de Claut.', 'publicado'),
('Política de Archivos Adjuntos', 'Se pueden adjuntar archivos de hasta 50MB en formatos como PDF, Word, Excel, PowerPoint, imágenes, videos y audio.', 'publicado'),
('Funcionalidades CRUD', 'El sistema permite Crear, Leer, Actualizar y Eliminar boletines con una interfaz intuitiva y moderna.', 'borrador');
*/

-- Verificar la estructura de la tabla
DESCRIBE boletines;

-- Mostrar información de la tabla
SHOW TABLE STATUS LIKE 'boletines';
