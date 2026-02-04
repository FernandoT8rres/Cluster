-- Tabla para mensajes de comités
CREATE TABLE IF NOT EXISTS `mensajes_comites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comite_id` int(11) NOT NULL,
  `destinatario_email` varchar(255) NOT NULL,
  `tipo_mensaje` enum('texto','link','imagen','documento') NOT NULL DEFAULT 'texto',
  `asunto` varchar(255) NOT NULL,
  `contenido` text NOT NULL,
  `fecha_envio` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('leido','no_leido') NOT NULL DEFAULT 'no_leido',
  PRIMARY KEY (`id`),
  KEY `idx_destinatario_email` (`destinatario_email`),
  KEY `idx_comite_id` (`comite_id`),
  KEY `idx_fecha_envio` (`fecha_envio`),
  KEY `idx_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear directorios para subida de archivos (esto debe hacerse manualmente)
-- mkdir -p uploads/mensajes/imagenes
-- mkdir -p uploads/mensajes/documentos
-- chmod 755 uploads/mensajes/
-- chmod 755 uploads/mensajes/imagenes/
-- chmod 755 uploads/mensajes/documentos/