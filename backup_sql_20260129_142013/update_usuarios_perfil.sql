-- Script para actualizar la tabla usuarios_perfil con nuevos campos
-- Ejecutar en la base de datos u695712029_claut_intranet

-- Primero verificar si la tabla usuarios existe y renombrarla si es necesario
-- (Solo ejecutar si la tabla se llama 'usuarios' actualmente)
-- RENAME TABLE usuarios TO usuarios_perfil;

-- Agregar nuevos campos a la tabla usuarios_perfil
ALTER TABLE usuarios_perfil 
ADD COLUMN IF NOT EXISTS empresa_id INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS telefono VARCHAR(20),
ADD COLUMN IF NOT EXISTS cargo VARCHAR(100),
ADD COLUMN IF NOT EXISTS departamento VARCHAR(100),
ADD COLUMN IF NOT EXISTS avatar_mime_type VARCHAR(50),
ADD COLUMN IF NOT EXISTS avatar_filename VARCHAR(255);

-- Modificar el campo rol para incluir los nuevos roles
ALTER TABLE usuarios_perfil 
MODIFY COLUMN rol ENUM('admin', 'empresa', 'empleado') DEFAULT 'empleado';

-- Agregar el campo avatar como LONGBLOB para almacenar imágenes
ALTER TABLE usuarios_perfil 
ADD COLUMN IF NOT EXISTS avatar LONGBLOB;

-- Agregar clave foránea hacia empresas_convenio
ALTER TABLE usuarios_perfil 
ADD CONSTRAINT fk_usuario_empresa 
FOREIGN KEY (empresa_id) REFERENCES empresas_convenio(id) ON DELETE SET NULL;

-- Agregar índices para mejorar el rendimiento
CREATE INDEX IF NOT EXISTS idx_usuarios_rol ON usuarios_perfil(rol);
CREATE INDEX IF NOT EXISTS idx_usuarios_empresa ON usuarios_perfil(empresa_id);
CREATE INDEX IF NOT EXISTS idx_usuarios_activo ON usuarios_perfil(activo);

-- Insertar datos de ejemplo (solo si no existen usuarios)
INSERT IGNORE INTO usuarios_perfil (nombre, apellido, email, password, rol, telefono, cargo, departamento, activo)
VALUES 
('Fernando', 'Torres', 'fernando@claut.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '+52 55 1234 5678', 'Administrador del Sistema', 'Tecnología', 1),
('María', 'González', 'empresa@techsolutions.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empresa', '+52 55 9876 5432', 'Representante Comercial', 'Ventas', 1),
('Carlos', 'Rodríguez', 'empleado@claut.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empleado', '+52 55 5555 1234', 'Desarrollador', 'Tecnología', 1);

-- Actualizar el usuario empresa para que esté ligado a la primera empresa (si existe)
UPDATE usuarios_perfil 
SET empresa_id = (SELECT id FROM empresas_convenio ORDER BY id LIMIT 1)
WHERE rol = 'empresa' AND empresa_id IS NULL;

-- Mostrar estructura final de la tabla
DESCRIBE usuarios_perfil;