-- ===================================================================
-- ESTRUCTURA DE BASE DE DATOS PARA SISTEMA DE BOLETINES
-- Claut Intranet v1.0
-- ===================================================================

USE claut_intranet;

-- Tabla principal de boletines
CREATE TABLE IF NOT EXISTS `boletines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `contenido` longtext NOT NULL,
  `resumen` text DEFAULT NULL,
  `autor_id` int(11) DEFAULT NULL,
  `estado` enum('borrador','publicado','archivado') DEFAULT 'borrador',
  `destacado` tinyint(1) DEFAULT 0,
  `imagen` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_publicacion` timestamp NULL DEFAULT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `visualizaciones` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha_publicacion` (`fecha_publicacion`),
  KEY `idx_autor_id` (`autor_id`),
  KEY `idx_destacado` (`destacado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de archivos adjuntos de boletines
CREATE TABLE IF NOT EXISTS `boletines_archivos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `boletin_id` int(11) NOT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `tipo_mime` varchar(100) NOT NULL,
  `tamaño` int(11) NOT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_boletin_id` (`boletin_id`),
  FOREIGN KEY (`boletin_id`) REFERENCES `boletines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar boletines de ejemplo (solo si no existen)
INSERT IGNORE INTO `boletines` (`id`, `titulo`, `contenido`, `resumen`, `estado`, `destacado`, `fecha_publicacion`, `visualizaciones`) VALUES
(1, 'Bienvenido a Claut Intranet', 
'<h2>¡Bienvenido al nuevo sistema de boletines de Claut!</h2>

<p>Nos complace anunciar el lanzamiento de nuestro nuevo sistema de comunicación interna. Este boletín marca el inicio de una nueva era en la gestión de información dentro de nuestra organización.</p>

<h3>🚀 Características principales:</h3>
<ul>
<li><strong>Visualización en tiempo real:</strong> Los boletines se muestran directamente desde la base de datos</li>
<li><strong>Soporte multimedia:</strong> Incluye soporte para documentos PDF, imágenes, Word, Excel y más</li>
<li><strong>Diseño responsive:</strong> Optimizado para dispositivos móviles y escritorio</li>
<li><strong>Sistema de búsqueda:</strong> Encuentra rápidamente la información que necesitas</li>
</ul>

<h3>📋 Próximos pasos:</h3>
<p>En las próximas semanas estaremos agregando más funcionalidades como:</p>
<ul>
<li>Sistema de notificaciones push</li>
<li>Comentarios y retroalimentación</li>
<li>Calendario integrado de eventos</li>
<li>Dashboard de métricas avanzadas</li>
</ul>

<p>¡Esperamos que disfruten de esta nueva herramienta!</p>

<p><em>Equipo de Desarrollo Claut Intranet</em></p>', 
'Lanzamiento oficial del nuevo sistema de boletines internos con múltiples funcionalidades y soporte multimedia completo.',
'publicado', 1, NOW(), 0),

(2, 'Manual de Usuario - Sistema de Boletines', 
'<h2>📖 Guía completa para usar el sistema de boletines</h2>

<p>Este manual te ayudará a aprovechar al máximo todas las funcionalidades disponibles en nuestro sistema de boletines.</p>

<h3>🔍 Navegación básica:</h3>
<ol>
<li><strong>Vista principal:</strong> Todos los boletines aparecen en formato de tarjetas</li>
<li><strong>Filtros:</strong> Usa los filtros para encontrar boletines específicos</li>
<li><strong>Búsqueda:</strong> La barra de búsqueda te permite encontrar contenido rápidamente</li>
</ol>

<h3>👁️ Visualización de documentos:</h3>
<p>El sistema soporta la visualización de múltiples formatos:</p>
<ul>
<li>📄 <strong>PDF:</strong> Se muestran directamente en el navegador</li>
<li>🖼️ <strong>Imágenes:</strong> JPG, PNG, GIF con vista previa</li>
<li>📝 <strong>Word:</strong> Conversión automática a formato web</li>
<li>📊 <strong>Excel:</strong> Vista de hojas de cálculo interactiva</li>
<li>🎥 <strong>Videos:</strong> Reproducción directa (MP4, WebM)</li>
</ul>

<h3>⚡ Características avanzadas:</h3>
<ul>
<li><strong>Modo offline:</strong> Los boletines se guardan en caché</li>
<li><strong>Descarga:</strong> Exporta cualquier boletín como HTML</li>
<li><strong>Compartir:</strong> Enlaces directos a boletines específicos</li>
<li><strong>Responsive:</strong> Funciona perfectamente en móviles</li>
</ul>

<h3>🛠️ Soporte técnico:</h3>
<p>Si encuentras algún problema o tienes sugerencias, contacta al equipo de TI.</p>

<p><strong>¡Explora y descubre todas las funcionalidades disponibles!</strong></p>', 
'Guía completa para usuarios sobre cómo utilizar eficientemente el sistema de boletines y todas sus funcionalidades.',
'publicado', 0, NOW() - INTERVAL 1 DAY, 0),

(3, 'Actualización de Seguridad - Marzo 2025', 
'<h2>🔒 Mejoras importantes de seguridad implementadas</h2>

<p>Hemos realizado actualizaciones significativas en la seguridad de nuestro sistema de intranet para proteger mejor la información de nuestra organización.</p>

<h3>🛡️ Mejoras implementadas:</h3>
<ul>
<li><strong>Autenticación reforzada:</strong> Sistema de tokens JWT más seguro</li>
<li><strong>Encriptación mejorada:</strong> Todas las comunicaciones usan HTTPS</li>
<li><strong>Validación de entrada:</strong> Protección contra inyecciones SQL y XSS</li>
<li><strong>Copias de seguridad:</strong> Respaldos automáticos cada 6 horas</li>
</ul>

<h3>📋 Recomendaciones para usuarios:</h3>
<ol>
<li>Actualiza tu contraseña si no lo has hecho en los últimos 3 meses</li>
<li>Usa contraseñas únicas y complejas</li>
<li>No compartas tus credenciales con terceros</li>
<li>Cierra sesión al terminar de usar el sistema</li>
</ol>

<h3>🚨 Reportar problemas de seguridad:</h3>
<p>Si detectas algún comportamiento sospechoso o problema de seguridad, repórtalo inmediatamente al equipo de TI.</p>

<p><strong>La seguridad es responsabilidad de todos. ¡Gracias por tu colaboración!</strong></p>', 
'Información sobre las últimas actualizaciones de seguridad implementadas en el sistema y recomendaciones para usuarios.',
'publicado', 1, NOW() - INTERVAL 2 DAY, 0);

-- Insertar algunos archivos de ejemplo (simulados)
INSERT IGNORE INTO `boletines_archivos` (`id`, `boletin_id`, `nombre_original`, `nombre_archivo`, `tipo_mime`, `tamaño`) VALUES
(1, 2, 'Manual_Usuario_Completo.pdf', 'manual_usuario_completo_20250307.pdf', 'application/pdf', 2048576),
(2, 2, 'Guia_Rapida.png', 'guia_rapida_20250307.png', 'image/png', 512000),
(3, 3, 'Politicas_Seguridad.docx', 'politicas_seguridad_20250307.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1024000);

-- Verificar que las tablas se crearon correctamente
SHOW TABLES LIKE 'boletines%';

-- Mostrar estructura de las tablas
DESCRIBE boletines;
DESCRIBE boletines_archivos;

-- Contar registros insertados
SELECT 
    'boletines' as tabla, 
    COUNT(*) as registros 
FROM boletines
UNION ALL
SELECT 
    'boletines_archivos' as tabla, 
    COUNT(*) as registros 
FROM boletines_archivos;

-- Mostrar boletines creados
SELECT 
    id,
    titulo,
    estado,
    destacado,
    fecha_publicacion,
    visualizaciones
FROM boletines 
ORDER BY fecha_publicacion DESC;
