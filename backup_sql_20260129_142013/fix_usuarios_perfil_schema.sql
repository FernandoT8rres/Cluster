-- Script para corregir la estructura de la tabla usuarios_perfil
-- Este script agrega todos los campos faltantes que están causando el error de registro

USE u695712029_claut_intranet;

-- Agregar todos los campos faltantes a la tabla usuarios_perfil
ALTER TABLE usuarios_perfil
ADD COLUMN IF NOT EXISTS fecha_nacimiento DATE,
ADD COLUMN IF NOT EXISTS nombre_empresa VARCHAR(255),
ADD COLUMN IF NOT EXISTS biografia TEXT,
ADD COLUMN IF NOT EXISTS direccion TEXT,
ADD COLUMN IF NOT EXISTS ciudad VARCHAR(100),
ADD COLUMN IF NOT EXISTS estado_geografico VARCHAR(100),
ADD COLUMN IF NOT EXISTS codigo_postal VARCHAR(20),
ADD COLUMN IF NOT EXISTS pais VARCHAR(100) DEFAULT 'México',
ADD COLUMN IF NOT EXISTS telefono_emergencia VARCHAR(20),
ADD COLUMN IF NOT EXISTS contacto_emergencia VARCHAR(255),
ADD COLUMN IF NOT EXISTS activo TINYINT(1) DEFAULT 1,
ADD COLUMN IF NOT EXISTS cargo VARCHAR(100),
ADD COLUMN IF NOT EXISTS departamento VARCHAR(100),
ADD COLUMN IF NOT EXISTS avatar_mime_type VARCHAR(50),
ADD COLUMN IF NOT EXISTS avatar_filename VARCHAR(255),
ADD COLUMN IF NOT EXISTS avatar LONGBLOB;

-- Modificar el campo rol para incluir todos los roles necesarios
ALTER TABLE usuarios_perfil
MODIFY COLUMN rol ENUM('admin', 'empresa', 'empleado', 'moderador') DEFAULT 'empleado';

-- Modificar el campo estado_usuario para incluir todos los estados necesarios
ALTER TABLE usuarios_perfil
MODIFY COLUMN estado_usuario ENUM('activo', 'inactivo', 'pendiente') DEFAULT 'activo';

-- Agregar índices para mejorar el rendimiento
CREATE INDEX IF NOT EXISTS idx_usuarios_rol ON usuarios_perfil(rol);
CREATE INDEX IF NOT EXISTS idx_usuarios_empresa ON usuarios_perfil(empresa_id);
CREATE INDEX IF NOT EXISTS idx_usuarios_activo ON usuarios_perfil(activo);
CREATE INDEX IF NOT EXISTS idx_usuarios_estado ON usuarios_perfil(estado_usuario);

-- Verificar la estructura actualizada
SELECT 'Estructura actualizada de usuarios_perfil:' as mensaje;
DESCRIBE usuarios_perfil;