-- ============================================================
-- MIGRACIÓN: Configuración de Notificaciones por Correo
-- Fecha: 2026-04-01
-- Propósito: Tabla de control para habilitar/deshabilitar
--            el envío de correos por tipo de evento del sistema.
--            Administrable desde el Panel Admin → Correos
-- ============================================================
-- Ejecutar en producción (Hostinger phpMyAdmin):
--   u695712029_claut_intranet → SQL → Ejecutar
-- ============================================================

CREATE TABLE IF NOT EXISTS `email_notification_config` (
    `id`           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `evento_tipo`  VARCHAR(50)    NOT NULL COMMENT 'Identificador único del tipo de evento',
    `nombre`       VARCHAR(100)   NOT NULL COMMENT 'Nombre legible para el panel admin',
    `descripcion`  VARCHAR(255)   DEFAULT NULL,
    `activo`       TINYINT(1)     NOT NULL DEFAULT 1,
    `dirigido_a`   ENUM('admin','todos','destinatario') NOT NULL DEFAULT 'admin',
    `icono`        VARCHAR(50)    DEFAULT 'fa-bell',
    `updated_at`   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_evento_tipo` (`evento_tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Configuración de notificaciones por correo controlada desde el panel admin';

-- Insertar los 4 tipos de notificación por defecto
INSERT INTO `email_notification_config`
    (`evento_tipo`, `nombre`, `descripcion`, `activo`, `dirigido_a`, `icono`)
VALUES
    ('nuevo_evento',    'Nuevo Evento',           'Notifica cuando se crea un nuevo evento en el calendario', 1, 'todos',        'fa-calendar-plus'),
    ('nuevo_descuento', 'Nuevo Descuento',         'Notifica cuando se publica un nuevo descuento empresarial', 1, 'todos',    'fa-tag'),
    ('nueva_empresa',   'Nueva Empresa Registrada','Alerta al admin cuando se registra una nueva empresa socia', 1, 'admin',  'fa-building'),
    ('mensaje_buzon',   'Mensaje en Buzón',        'Envía copia por correo de notificaciones internas del sistema', 1, 'destinatario', 'fa-envelope')
ON DUPLICATE KEY UPDATE
    `nombre` = VALUES(`nombre`),
    `descripcion` = VALUES(`descripcion`),
    `icono` = VALUES(`icono`);
