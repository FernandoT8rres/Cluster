-- ============================================================
-- Migration: Campos para directorio empresarial
-- Tabla: empresas_convenio
-- Fecha: 2026-04-12
-- Descripcion: Agrega campos para directorio interactivo de socios:
--   municipio, certificaciones, exporta, redes sociales
-- Patron: idempotente con IF NOT EXISTS
-- ============================================================

ALTER TABLE `empresas_convenio`
    -- Directorio
    ADD COLUMN IF NOT EXISTS `entidad_federativa` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Estado de México (directorio)' AFTER `estado`,
    ADD COLUMN IF NOT EXISTS `municipio`         VARCHAR(120)  NULL DEFAULT NULL COMMENT 'Municipio de la empresa'            AFTER `entidad_federativa`,
    ADD COLUMN IF NOT EXISTS `certificaciones`   TEXT          NULL DEFAULT NULL COMMENT 'Certificaciones separadas por coma' AFTER `municipio`,
    ADD COLUMN IF NOT EXISTS `exporta`           TINYINT(1)    NOT NULL DEFAULT 0 COMMENT '1=Exporta, 0=Solo nacional'        AFTER `certificaciones`,
    ADD COLUMN IF NOT EXISTS `redes_fb`          VARCHAR(255)  NULL DEFAULT NULL COMMENT 'URL Facebook'                       AFTER `exporta`,
    ADD COLUMN IF NOT EXISTS `redes_x`           VARCHAR(255)  NULL DEFAULT NULL COMMENT 'URL X (Twitter)'                    AFTER `redes_fb`,
    ADD COLUMN IF NOT EXISTS `redes_linkedin`    VARCHAR(255)  NULL DEFAULT NULL COMMENT 'URL LinkedIn'                       AFTER `redes_x`,
    ADD COLUMN IF NOT EXISTS `redes_instagram`   VARCHAR(255)  NULL DEFAULT NULL COMMENT 'URL Instagram'                      AFTER `redes_linkedin`,
    ADD COLUMN IF NOT EXISTS `autoriza_directorio` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=Autoriza aparecer en directorio' AFTER `redes_instagram`,
    -- Trazabilidad
    ADD COLUMN IF NOT EXISTS `usuario_registro_nombre`   VARCHAR(255) NULL DEFAULT NULL COMMENT 'Nombre del usuario que registró' AFTER `autoriza_directorio`,
    ADD COLUMN IF NOT EXISTS `usuario_registro_apellido` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Apellido del usuario que registró' AFTER `usuario_registro_nombre`,
    ADD COLUMN IF NOT EXISTS `departamento`              VARCHAR(100) NULL DEFAULT NULL COMMENT 'Departamento del usuario' AFTER `usuario_registro_apellido`,
    ADD COLUMN IF NOT EXISTS `cargo`                     VARCHAR(100) NULL DEFAULT NULL COMMENT 'Cargo del usuario' AFTER `departamento`,
    ADD COLUMN IF NOT EXISTS `logo_archivo`              VARCHAR(255) NULL DEFAULT NULL COMMENT 'Path al logo físico' AFTER `cargo`,
    -- Convenios Clúster (Sección opcional)
    ADD COLUMN IF NOT EXISTS `convenio_descripcion` TEXT         NULL DEFAULT NULL COMMENT 'Descripción del descuento con Clúster' AFTER `logo_archivo`,
    ADD COLUMN IF NOT EXISTS `vigencia_inicio`      DATE         NULL DEFAULT NULL COMMENT 'Inicio de vigencia de la promoción'   AFTER `convenio_descripcion`,
    ADD COLUMN IF NOT EXISTS `vigencia_fin`         DATE         NULL DEFAULT NULL COMMENT 'Fin de vigencia de la promoción'      AFTER `vigencia_inicio`,
    ADD COLUMN IF NOT EXISTS `contacto_movil`       VARCHAR(20)  NULL DEFAULT NULL COMMENT 'Móvil de contacto para convenio'      AFTER `vigencia_fin`,
    ADD COLUMN IF NOT EXISTS `contacto_cargo`       VARCHAR(100) NULL DEFAULT NULL COMMENT 'Cargo de la persona de contacto'     AFTER `contacto_movil`;

-- ============================================================
-- Verificar resultado (Manual)
-- ============================================================
-- Nota: En Hostinger, SELECT sobre INFORMATION_SCHEMA puede dar Error #1044.
-- Verifica los cambios en la pestaña "Structure" de phpMyAdmin.

