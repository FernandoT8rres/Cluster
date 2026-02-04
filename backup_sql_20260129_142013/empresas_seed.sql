-- Seed data para empresas en convenio
-- Este archivo inicializa datos de ejemplo para empresas en convenio con Claut

USE u695712029_claut_intranet;

-- Limpiar datos existentes (opcional)
-- DELETE FROM comite_miembros WHERE usuario_id > 3;
-- DELETE FROM usuarios_perfil WHERE empresa_id > 3;
-- DELETE FROM empresas WHERE id > 3;

-- Insertar empresas adicionales
INSERT IGNORE INTO empresas (id, nombre, descripcion, website, telefono, email, direccion, logo, estado, fecha_registro) VALUES
(4, 'General Motors México', 'Fabricante líder de vehículos en México con más de 80 años de presencia', 'https://www.gm.com.mx', '+52-55-5123-4567', 'contacto@gm.com.mx', 'Av. Ejército Nacional 843, Col. Granada, CDMX', './assets/img/logos/gm-logo.png', 'activa', NOW()),
(5, 'Nissan Mexicana', 'Ensambladora de vehículos Nissan para el mercado mexicano y exportación', 'https://www.nissan.com.mx', '+52-55-5234-5678', 'info@nissan.com.mx', 'Av. Revolución 1425, Col. Tlacopac, CDMX', './assets/img/icons/flags/nissan.jpg', 'activa', NOW()),
(6, 'Ford Motor Company México', 'División mexicana de Ford Motor Company, innovación en movilidad', 'https://www.ford.mx', '+52-55-5345-6789', 'ventas@ford.mx', 'Blvd. Manuel Ávila Camacho 36, Naucalpan, Estado de México', './assets/img/logos/ford-logo.png', 'activa', NOW()),
(7, 'Volkswagen de México', 'Fabricante alemán con operaciones en México desde 1967', 'https://www.vw.com.mx', '+52-55-5456-7890', 'contacto@vw.com.mx', 'Av. Universidad 1200, Col. Xoco, CDMX', './assets/img/logos/vw-logo.png', 'activa', NOW()),
(8, 'Toyota Motor México', 'Ensambladora japonesa líder en tecnología híbrida y sustentabilidad', 'https://www.toyota.com.mx', '+52-55-5567-8901', 'info@toyota.com.mx', 'Periférico Sur 4690, Col. Pedregal de Carrasco, CDMX', './assets/img/logos/toyota-logo.png', 'activa', NOW()),
(9, 'Bosch México', 'Proveedor líder de tecnología y servicios automotrices a nivel mundial', 'https://www.bosch.com.mx', '+52-55-5678-9012', 'mexico@bosch.com', 'Av. Robert Bosch 405, Toluca, Estado de México', './assets/img/logos/bosch-logo.png', 'activa', NOW()),
(10, 'Continental Automotive México', 'Proveedor de sistemas y componentes automotrices de alta tecnología', 'https://www.continental.com.mx', '+52-55-5789-0123', 'info@continental.com.mx', 'Zona Industrial Norte, Tlalnepantla, Estado de México', './assets/img/logos/continental-logo.png', 'activa', NOW()),
(11, 'Magna International México', 'Proveedor global de sistemas de movilidad y manufactura automotriz', 'https://www.magna.com', '+52-55-5890-1234', 'contacto@magna.com', 'Parque Industrial Lerma, Lerma, Estado de México', './assets/img/logos/magna-logo.png', 'activa', NOW()),
(12, 'Delphi Technologies México', 'Innovador en tecnologías de propulsión y sistemas eléctricos', 'https://www.delphi.com', '+52-55-5901-2345', 'mexico@delphi.com', 'Ciudad Sahagún, Hidalgo', './assets/img/logos/delphi-logo.png', 'activa', NOW()),
(13, 'Denso México', 'Proveedor de tecnología automotriz avanzada y sistemas térmicos', 'https://www.denso.com.mx', '+52-55-6012-3456', 'info@denso.com.mx', 'Av. Industrias 250, Querétaro, Querétaro', './assets/img/logos/denso-logo.png', 'activa', NOW());

-- Insertar usuarios para las nuevas empresas
INSERT IGNORE INTO usuarios_perfil (nombre, apellido, email, password, telefono, empresa_id, rol, estado, fecha_registro) VALUES
-- Usuarios de General Motors (empresa_id = 4)
('Roberto', 'Hernández', 'roberto.hernandez@gm.com.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-1111-0001', 4, 'empleado', 'activo', NOW()),
('Ana', 'Martínez', 'ana.martinez@gm.com.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-1111-0002', 4, 'empleado', 'activo', NOW()),
('Luis', 'García', 'luis.garcia@gm.com.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-1111-0003', 4, 'moderador', 'activo', NOW()),

-- Usuarios de Nissan (empresa_id = 5)
('Patricia', 'López', 'patricia.lopez@nissan.com.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-2222-0001', 5, 'empleado', 'activo', NOW()),
('Miguel', 'Jiménez', 'miguel.jimenez@nissan.com.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-2222-0002', 5, 'empleado', 'activo', NOW()),

-- Usuarios de Ford (empresa_id = 6)
('Carmen', 'Ruiz', 'carmen.ruiz@ford.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-3333-0001', 6, 'empleado', 'activo', NOW()),
('Jorge', 'Morales', 'jorge.morales@ford.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-3333-0002', 6, 'empleado', 'activo', NOW()),

-- Usuarios de Volkswagen (empresa_id = 7)
('Sandra', 'Torres', 'sandra.torres@vw.com.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-4444-0001', 7, 'empleado', 'activo', NOW()),
('Ricardo', 'Vargas', 'ricardo.vargas@vw.com.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-4444-0002', 7, 'empleado', 'activo', NOW()),

-- Usuarios de Toyota (empresa_id = 8)
('Elena', 'Castro', 'elena.castro@toyota.com.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-5555-0001', 8, 'empleado', 'activo', NOW()),
('Diego', 'Mendoza', 'diego.mendoza@toyota.com.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-5555-0002', 8, 'moderador', 'activo', NOW()),

-- Usuarios de Bosch (empresa_id = 9)
('Martha', 'Silva', 'martha.silva@bosch.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-6666-0001', 9, 'empleado', 'activo', NOW()),
('Alberto', 'Ramírez', 'alberto.ramirez@bosch.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-6666-0002', 9, 'empleado', 'activo', NOW()),

-- Usuarios de Continental (empresa_id = 10)
('Gabriela', 'Fernández', 'gabriela.fernandez@continental.com.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-7777-0001', 10, 'empleado', 'activo', NOW()),
('Francisco', 'Delgado', 'francisco.delgado@continental.com.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-7777-0002', 10, 'empleado', 'activo', NOW()),

-- Usuarios de Magna (empresa_id = 11)
('Isabel', 'Guerrero', 'isabel.guerrero@magna.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-8888-0001', 11, 'empleado', 'activo', NOW()),
('Andrés', 'Medina', 'andres.medina@magna.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-8888-0002', 11, 'empleado', 'activo', NOW()),

-- Usuarios de Delphi (empresa_id = 12)
('Verónica', 'Peña', 'veronica.pena@delphi.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-9999-0001', 12, 'empleado', 'activo', NOW()),
('Arturo', 'Sánchez', 'arturo.sanchez@delphi.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-9999-0002', 12, 'empleado', 'activo', NOW()),

-- Usuarios de Denso (empresa_id = 13)
('Claudia', 'Ramos', 'claudia.ramos@denso.com.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-0000-0001', 13, 'empleado', 'activo', NOW()),
('Héctor', 'Ortiz', 'hector.ortiz@denso.com.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-0000-0002', 13, 'empleado', 'activo', NOW());

-- Crear algunos comités adicionales si no existen
INSERT IGNORE INTO comites (id, nombre, descripcion, presidente_id, estado, fecha_creacion) VALUES
(4, 'Comité de Sostenibilidad', 'Comité enfocado en prácticas sustentables y responsabilidad ambiental en la industria automotriz', 11, 'activo', NOW()),
(5, 'Comité de Seguridad Industrial', 'Comité para supervisar y mejorar la seguridad en plantas industriales del sector automotriz', 6, 'activo', NOW()),
(6, 'Comité de Digitalización', 'Comité para la transformación digital del sector automotriz mexicano', 5, 'activo', NOW());

-- Agregar miembros a los comités existentes y nuevos
INSERT IGNORE INTO comite_miembros (comite_id, usuario_id, cargo, estado, fecha_ingreso) VALUES
-- Comité de Calidad (ID 1) - agregar más miembros
(1, 4, 'vocal', 'activo', NOW()),      -- Roberto Hernández (GM)
(1, 7, 'miembro', 'activo', NOW()),    -- Patricia López (Nissan)
(1, 9, 'miembro', 'activo', NOW()),    -- Carmen Ruiz (Ford)
(1, 13, 'miembro', 'activo', NOW()),   -- Martha Silva (Bosch)

-- Comité de Innovación (ID 2) - agregar más miembros
(2, 5, 'vocal', 'activo', NOW()),      -- Ana Martínez (GM)
(2, 8, 'secretario', 'activo', NOW()), -- Miguel Jiménez (Nissan)
(2, 11, 'miembro', 'activo', NOW()),   -- Sandra Torres (VW)
(2, 15, 'miembro', 'activo', NOW()),   -- Gabriela Fernández (Continental)

-- Comité de Capacitación (ID 3) - agregar más miembros
(3, 6, 'tesorero', 'activo', NOW()),   -- Luis García (GM)
(3, 10, 'vocal', 'activo', NOW()),     -- Jorge Morales (Ford)
(3, 12, 'miembro', 'activo', NOW()),   -- Elena Castro (Toyota)
(3, 17, 'miembro', 'activo', NOW()),   -- Isabel Guerrero (Magna)

-- Comité de Sostenibilidad (ID 4)
(4, 11, 'presidente', 'activo', NOW()), -- Diego Mendoza (Toyota)
(4, 4, 'vicepresidente', 'activo', NOW()), -- Roberto Hernández (GM)
(4, 11, 'secretario', 'activo', NOW()),  -- Sandra Torres (VW)
(4, 21, 'vocal', 'activo', NOW()),       -- Claudia Ramos (Denso)
(4, 14, 'miembro', 'activo', NOW()),     -- Alberto Ramírez (Bosch)

-- Comité de Seguridad Industrial (ID 5)
(5, 6, 'presidente', 'activo', NOW()),   -- Luis García (GM) - cambiar el presidente
(5, 8, 'vicepresidente', 'activo', NOW()), -- Miguel Jiménez (Nissan)
(5, 10, 'secretario', 'activo', NOW()),  -- Jorge Morales (Ford)
(5, 16, 'vocal', 'activo', NOW()),       -- Francisco Delgado (Continental)
(5, 19, 'miembro', 'activo', NOW()),     -- Verónica Peña (Delphi)

-- Comité de Digitalización (ID 6)
(6, 5, 'presidente', 'activo', NOW()),   -- Ana Martínez (GM) - cambiar el presidente
(6, 9, 'vicepresidente', 'activo', NOW()), -- Carmen Ruiz (Ford)
(6, 7, 'secretario', 'activo', NOW()),   -- Patricia López (Nissan)
(6, 18, 'vocal', 'activo', NOW()),       -- Andrés Medina (Magna)
(6, 22, 'miembro', 'activo', NOW());     -- Héctor Ortiz (Denso)

-- Verificar los datos insertados
SELECT 'Resumen de datos insertados:' as Info;

SELECT 
    'Empresas en convenio' as Categoria,
    COUNT(*) as Total
FROM empresas 
WHERE estado = 'activa'

UNION ALL

SELECT 
    'Empleados registrados' as Categoria,
    COUNT(*) as Total
FROM usuarios_perfil 
WHERE estado = 'activo'

UNION ALL

SELECT 
    'Comités activos' as Categoria,
    COUNT(*) as Total
FROM comites 
WHERE estado = 'activo'

UNION ALL

SELECT 
    'Participaciones en comités' as Categoria,
    COUNT(*) as Total
FROM comite_miembros 
WHERE estado = 'activo';

-- Mostrar distribución de empleados por empresa
SELECT 
    e.nombre as Empresa,
    COUNT(u.id) as Empleados_Registrados,
    COUNT(DISTINCT cm.comite_id) as Comites_Participando
FROM empresas e
LEFT JOIN usuarios_perfil u ON e.id = u.empresa_id AND u.estado = 'activo'
LEFT JOIN comite_miembros cm ON u.id = cm.usuario_id AND cm.estado = 'activo'
WHERE e.estado = 'activa'
GROUP BY e.id, e.nombre
ORDER BY Empleados_Registrados DESC;
