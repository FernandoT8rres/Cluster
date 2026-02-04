-- Modificar tabla descuentos para soportar empresas externas

-- 1. Agregar campo para nombre de empresa
ALTER TABLE descuentos
ADD COLUMN empresa_nombre VARCHAR(255) NULL AFTER empresa_oferente_id,
ADD COLUMN tipo_empresa ENUM('convenio', 'externa') DEFAULT 'convenio' AFTER empresa_nombre;

-- 2. Permitir que empresa_oferente_id sea NULL para empresas externas
ALTER TABLE descuentos
MODIFY empresa_oferente_id INT(11) NULL;

-- 3. Eliminar constraint foreign key temporalmente para modificar
ALTER TABLE descuentos DROP FOREIGN KEY fk_descuentos_empresa;

-- 4. Agregar constraint condicional (solo para empresas en convenio)
-- Nota: MySQL no soporta constraints condicionales directamente,
-- así que lo manejaremos en el código de aplicación

-- 5. Agregar índices para los nuevos campos
ALTER TABLE descuentos
ADD INDEX idx_empresa_nombre (empresa_nombre),
ADD INDEX idx_tipo_empresa (tipo_empresa);

-- 6. Actualizar registros existentes para mantener consistencia
UPDATE descuentos
SET tipo_empresa = 'convenio'
WHERE empresa_oferente_id IS NOT NULL;

-- 7. Intentar re-agregar foreign key solo para empresas en convenio
-- (esto es opcional y puede fallar si no existe la tabla de empresas)
-- ALTER TABLE descuentos
-- ADD CONSTRAINT fk_descuentos_empresa_convenio
-- FOREIGN KEY (empresa_oferente_id) REFERENCES empresas_convenio(id)
-- ON DELETE SET NULL;

-- Comentario: El constraint se maneja en el código de aplicación
-- para evitar problemas con empresas externas