-- Script SQL para crear tabla de archivos adjuntos de boletines
-- Compatible con la tabla 'boletines' existente

-- Crear tabla para archivos adjuntos de boletines
CREATE TABLE IF NOT EXISTS `boletines_archivos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `boletin_id` int NOT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `tipo_mime` varchar(100) NOT NULL,
  `tamaño` bigint NOT NULL,
  `fecha_subida` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_boletin_id` (`boletin_id`),
  KEY `idx_fecha_subida` (`fecha_subida`),
  CONSTRAINT `fk_boletines_archivos_boletin` FOREIGN KEY (`boletin_id`) REFERENCES `boletines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Agregar índices adicionales para optimización
ALTER TABLE `boletines_archivos` 
ADD INDEX `idx_tipo_mime` (`tipo_mime`),
ADD INDEX `idx_nombre_archivo` (`nombre_archivo`);

-- Verificar que la tabla boletines tenga la estructura correcta
-- (Este script asume que ya existe, pero verifica algunos campos importantes)

-- Agregar campo 'prioridad' si no existe (para clasificar anuncios importantes)
ALTER TABLE `boletines` 
ADD COLUMN `prioridad` enum('baja','media','alta','urgente') DEFAULT 'media' AFTER `destacado`;

-- Agregar campo 'tipo' si no existe (para categorizar boletines)
ALTER TABLE `boletines` 
ADD COLUMN `tipo` varchar(50) DEFAULT 'boletin' AFTER `prioridad`;

-- Agregar índice para búsquedas por tipo y prioridad
ALTER TABLE `boletines`
ADD INDEX `idx_tipo_prioridad` (`tipo`, `prioridad`);

-- Crear directorio de uploads (ejecutar manualmente en el servidor)
-- mkdir -p ../uploads/boletines/
-- chmod 755 ../uploads/boletines/

-- Insertar algunos boletines de ejemplo con diferentes tipos y prioridades
INSERT INTO `boletines` (
    `titulo`, 
    `contenido`, 
    `resumen`, 
    `autor_id`, 
    `fecha_publicacion`, 
    `estado`, 
    `destacado`, 
    `prioridad`, 
    `tipo`
) VALUES 
(
    'Actualización Importante del Sistema',
    'Se ha implementado una nueva versión del sistema de intranet con mejoras significativas en rendimiento y nuevas funcionalidades para la gestión de documentos. Esta actualización incluye: 1) Nuevo sistema de archivos adjuntos, 2) Mejoras en la interfaz de usuario, 3) Mayor seguridad en la autenticación.',
    'Nueva versión del sistema con mejoras importantes en rendimiento y funcionalidades.',
    1,
    NOW(),
    'publicado',
    1,
    'alta',
    'anuncio'
),
(
    'Nuevas Políticas de Seguridad',
    'A partir del próximo mes, se implementarán nuevas políticas de seguridad para proteger la información confidencial de la empresa. Todos los empleados deberán: 1) Actualizar sus contraseñas, 2) Completar el curso de ciberseguridad, 3) Configurar autenticación de dos factores.',
    'Implementación de nuevas políticas de seguridad empresarial.',
    1,
    NOW(),
    'publicado',
    1,
    'urgente',
    'comunicado'
),
(
    'Reporte Mensual de Actividades',
    'El reporte mensual de actividades del departamento de tecnología muestra un crecimiento del 15% en la productividad. Los principales logros incluyen: implementación de 5 nuevos proyectos, reducción del 30% en tiempos de respuesta, y mejora en la satisfacción del cliente.',
    'Reporte mensual con crecimiento del 15% en productividad.',
    1,
    DATE_SUB(NOW(), INTERVAL 2 DAY),
    'publicado',
    0,
    'media',
    'boletin'
),
(
    'Convocatoria: Reunión Trimestral',
    'Se convoca a todos los miembros del comité ejecutivo a la reunión trimestral que se llevará a cabo el próximo viernes a las 10:00 AM. Temas a tratar: presupuesto Q4, nuevos proyectos estratégicos, y evaluación de objetivos.',
    'Convocatoria para reunión trimestral del comité ejecutivo.',
    1,
    DATE_SUB(NOW(), INTERVAL 1 DAY),
    'publicado',
    1,
    'alta',
    'noticia'
);

-- Comentarios sobre el uso del sistema:
-- 1. Los archivos se almacenan físicamente en /uploads/boletines/
-- 2. Los nombres de archivo se generan automáticamente para evitar conflictos
-- 3. Se mantiene el nombre original para mostrar al usuario
-- 4. Los tipos MIME permitidos están definidos en la API
-- 5. El tamaño máximo por archivo es de 10MB (configurable)

-- Formatos soportados:
-- - PDF: application/pdf
-- - Word: application/msword, application/vnd.openxmlformats-officedocument.wordprocessingml.document
-- - Excel: application/vnd.ms-excel, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
-- - PowerPoint: application/vnd.ms-powerpoint, application/vnd.openxmlformats-officedocument.presentationml.presentation
-- - Imágenes: image/jpeg, image/png, image/gif, image/webp
-- - Texto: text/plain
-- - Comprimidos: application/zip, application/x-rar-compressed

-- Verificar que todo se haya creado correctamente
SELECT 'Tabla boletines_archivos creada correctamente' AS status;
SHOW TABLES LIKE '%boletines%';
DESCRIBE boletines_archivos;
