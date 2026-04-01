-- ============================================================
-- MIGRACIÓN: Sistema de Tokens de Correo
-- Fecha: 2026-04-01
-- Propósito: Tabla para tokens de recuperación de contraseña
--            y verificación de cuenta por correo electrónico
-- ============================================================
-- Ejecutar en producción (Hostinger):
--   mysql -u u695712029_claut_fer -p u695712029_claut_intranet < 20260401_email_tokens.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `email_tokens` (
    `id`          INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `user_email`  VARCHAR(255)   NOT NULL,
    `token`       VARCHAR(128)   NOT NULL,
    `tipo`        ENUM('password_reset', 'account_verify') NOT NULL,
    `expires_at`  DATETIME       NOT NULL,
    `usado`       TINYINT(1)     NOT NULL DEFAULT 0,
    `ip_origen`   VARCHAR(45)    DEFAULT NULL COMMENT 'IP que solicito el token',
    `created_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_token` (`token`),
    KEY `idx_email_tipo` (`user_email`, `tipo`),
    KEY `idx_expires` (`expires_at`, `usado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tokens de uso único para recuperación de contraseña y verificación de cuenta';

-- Columna token_verificacion en usuarios_perfil (si no existe)
-- Permite marcar si la cuenta fue verificada por correo
ALTER TABLE `usuarios_perfil`
    ADD COLUMN IF NOT EXISTS `email_verificado` TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = email confirmado vía token'
    AFTER `estado_usuario`;
