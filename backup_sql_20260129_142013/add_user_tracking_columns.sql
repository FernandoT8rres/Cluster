-- Agregar columnas para rastrear información del usuario loggeado que envía el formulario

ALTER TABLE comite_registros
ADD COLUMN usuario_loggeado_id VARCHAR(50) NULL AFTER comentarios,
ADD COLUMN usuario_loggeado_nombre VARCHAR(255) NULL AFTER usuario_loggeado_id,
ADD COLUMN usuario_loggeado_email VARCHAR(255) NULL AFTER usuario_loggeado_nombre,
ADD COLUMN usuario_loggeado_empresa VARCHAR(255) NULL AFTER usuario_loggeado_email,
ADD COLUMN session_info TEXT NULL AFTER usuario_loggeado_empresa,
ADD COLUMN ip_address VARCHAR(45) NULL AFTER session_info;

-- Agregar índices para consultas eficientes
ALTER TABLE comite_registros
ADD INDEX idx_usuario_loggeado_email (usuario_loggeado_email),
ADD INDEX idx_usuario_loggeado_id (usuario_loggeado_id);