-- Datos adicionales de empresas en convenio para Claut Intranet
-- Este archivo contiene empresas de ejemplo para demostrar la funcionalidad

USE claut_intranet;

-- Verificar si existen registros antes de hacer cambios
SELECT COUNT(*) as total_empresas FROM empresas WHERE estado = 'activa';

-- Insertar más empresas de ejemplo para el convenio
INSERT IGNORE INTO empresas (nombre, descripcion, website, telefono, email, direccion, logo, estado) VALUES
('General Motors México', 'Fabricante líder de vehículos en México', 'https://www.gm.com.mx', '+52-55-5123-4567', 'contacto@gm.com.mx', 'Av. Ejército Nacional 843, CDMX', './assets/img/icons/flags/GM.jpeg', 'activa'),
('Nissan Mexicana', 'Ensambladora de vehículos Nissan para el mercado mexicano', 'https://www.nissan.com.mx', '+52-55-5234-5678', 'info@nissan.com.mx', 'Av. Revolución 1425, CDMX', './assets/img/icons/flags/nissan.jpg', 'activa'),
('Ford Motor Company México', 'División mexicana de Ford Motor Company', 'https://www.ford.mx', '+52-55-5345-6789', 'ventas@ford.mx', 'Blvd. Manuel Ávila Camacho 36, CDMX', NULL, 'activa'),
('Volkswagen de México', 'Fabricante alemán con operaciones en México', 'https://www.vw.com.mx', '+52-55-5456-7890', 'contacto@vw.com.mx', 'Av. Universidad 1200, CDMX', NULL, 'activa'),
('Toyota Motor México', 'Ensambladora japonesa líder en híbridos', 'https://www.toyota.com.mx', '+52-55-5567-8901', 'info@toyota.com.mx', 'Periférico Sur 4690, CDMX', NULL, 'activa'),
('Bosch México', 'Proveedor líder de tecnología y servicios automotrices', 'https://www.bosch.com.mx', '+52-55-5678-9012', 'mexico@bosch.com', 'Av. Robert Bosch 405, CDMX', NULL, 'activa'),
('Continental Automotive México', 'Proveedor de sistemas y componentes automotrices', 'https://www.continental.com.mx', '+52-55-5789-0123', 'info@continental.com.mx', 'Zona Industrial Norte, Estado de México', NULL, 'activa'),
('Magna International México', 'Proveedor de sistemas de movilidad', 'https://www.magna.com', '+52-55-5890-1234', 'contacto@magna.com', 'Parque Industrial Lerma, Estado de México', NULL, 'activa'),
('Delphi Technologies México', 'Innovador en tecnologías de propulsión', 'https://www.delphi.com', '+52-55-5901-2345', 'mexico@delphi.com', 'Ciudad Sahagún, Hidalgo', NULL, 'activa'),
('Denso México', 'Proveedor de tecnología automotriz avanzada', 'https://www.denso.com.mx', '+52-55-6012-3456', 'info@denso.com.mx', 'Av. Industrias 250, Querétaro', NULL, 'activa');

-- Insertar usuarios adicionales para estas empresas
INSERT IGNORE INTO usuarios_perfil (nombre, apellido, email, password, telefono, empresa_id, rol, estado) VALUES
-- Usuarios de General Motors
('Roberto', 'Hernández', 'roberto.hernandez@gm.com.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-1111-0001', 4, 'empleado', 'activo'),
('Ana', 'Martínez', 'ana.martinez@gm.com.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-1111-0002', 4, 'empleado', 'activo'),
('Luis', 'García', 'luis.garcia@gm.com.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-1111-0003', 4, 'moderador', 'activo'),

-- Usuarios de Nissan
('Patricia', 'López', 'patricia.lopez@nissan.com.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-2222-0001', 5, 'empleado', 'activo'),
('Miguel', 'Jiménez', 'miguel.jimenez@nissan.com.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-2222-0002', 5, 'empleado', 'activo'),

-- Usuarios de Ford
('Carmen', 'Ruiz', 'carmen.ruiz@ford.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-3333-0001', 6, 'empleado', 'activo'),
('Jorge', 'Morales', 'jorge.morales@ford.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-3333-0002', 6, 'empleado', 'activo'),

-- Usuarios de Volkswagen
('Sandra', 'Torres', 'sandra.torres@vw.com.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-4444-0001', 7, 'empleado', 'activo'),
('Ricardo', 'Vargas', 'ricardo.vargas@vw.com.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-4444-0002', 7, 'empleado', 'activo'),

-- Usuarios de Toyota
('Elena', 'Castro', 'elena.castro@toyota.com.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-5555-0001', 8, 'empleado', 'activo'),
('Diego', 'Mendoza', 'diego.mendoza@toyota.com.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+52-55-5555-0002', 8, 'moderador', 'activo');

-- Agregar más miembros a los comités existentes
INSERT IGNORE INTO comite_miembros (comite_id, usuario_id, cargo, estado) VALUES
-- Comité de Calidad (ID 1)
(1, 5, 'vocal', 'activo'),      -- Roberto Hernández (GM)
(1, 7, 'miembro', 'activo'),    -- Patricia López (Nissan)
(1, 9, 'miembro', 'activo'),    -- Carmen Ruiz (Ford)

-- Comité de Innovación (ID 2)
(2, 6, 'vocal', 'activo'),      -- Ana Martínez (GM)
(2, 8, 'secretario', 'activo'), -- Miguel Jiménez (Nissan)
(2, 11, 'miembro', 'activo'),   -- Sandra Torres (VW)

-- Comité de Capacitación (ID 3)
(3, 7, 'tesorero', 'activo'),   -- Luis García (GM)
(3, 10, 'vocal', 'activo'),     -- Jorge Morales (Ford)
(3, 13, 'miembro', 'activo'),   -- Elena Castro (Toyota)
(3, 12, 'miembro', 'activo');   -- Ricardo Vargas (VW)

-- Crear comités adicionales
INSERT IGNORE INTO comites (nombre, descripcion, presidente_id, estado) VALUES
('Comité de Sostenibilidad', 'Comité enfocado en prácticas sustentables y responsabilidad ambiental', 14, 'activo'),
('Comité de Seguridad Industrial', 'Comité para supervisar y mejorar la seguridad en plantas industriales', 7, 'activo'),
('Comité de Digitalización', 'Comité para la transformación digital del sector automotriz', 6, 'activo');

-- Agregar miembros a los nuevos comités
INSERT IGNORE INTO comite_miembros (comite_id, usuario_id, cargo, estado) VALUES
-- Comité de Sostenibilidad (ID 4)
(4, 14, 'presidente', 'activo'), -- Diego Mendoza (Toyota)
(4, 5, 'vicepresidente', 'activo'), -- Roberto Hernández (GM)
(4, 11, 'secretario', 'activo'),  -- Sandra Torres (VW)
(4, 13, 'vocal', 'activo'),       -- Elena Castro (Toyota)

-- Comité de Seguridad Industrial (ID 5)
(5, 7, 'presidente', 'activo'),   -- Luis García (GM)
(5, 8, 'vicepresidente', 'activo'), -- Miguel Jiménez (Nissan)
(5, 10, 'secretario', 'activo'),  -- Jorge Morales (Ford)
(5, 12, 'vocal', 'activo'),       -- Ricardo Vargas (VW)

-- Comité de Digitalización (ID 6)
(6, 6, 'presidente', 'activo'),   -- Ana Martínez (GM)
(6, 9, 'vicepresidente', 'activo'), -- Carmen Ruiz (Ford)
(6, 7, 'secretario', 'activo'),   -- Patricia López (Nissan)
(6, 14, 'vocal', 'activo');       -- Diego Mendoza (Toyota)

-- Insertar eventos adicionales
INSERT IGNORE INTO eventos (titulo, descripcion, fecha_inicio, fecha_fin, ubicacion, organizador_id, comite_id, estado) VALUES
('Foro de Sostenibilidad Automotriz 2025', 'Encuentro anual sobre prácticas sustentables en la industria', '2025-09-15 09:00:00', '2025-09-15 18:00:00', 'Centro de Convenciones CDMX', 14, 4, 'programado'),
('Capacitación en Seguridad Industrial', 'Taller intensivo sobre nuevas normas de seguridad', '2025-08-30 08:00:00', '2025-08-30 17:00:00', 'Planta GM Silao', 7, 5, 'programado'),
('Expo Digitalización Automotriz', 'Exhibición de las últimas tecnologías digitales', '2025-10-05 10:00:00', '2025-10-07 19:00:00', 'Expo Santa Fe', 6, 6, 'programado'),
('Mesa Redonda: Futuro de la Movilidad', 'Discusión sobre tendencias y desafíos del sector', '2025-08-25 14:00:00', '2025-08-25 17:00:00', 'Hotel Marriott Polanco', 5, 2, 'programado'),
('Taller de Mejora Continua', 'Metodologías Lean aplicadas al sector automotriz', '2025-09-01 09:00:00', '2025-09-01 15:00:00', 'Centro de Capacitación Nissan', 8, 1, 'programado');

-- Insertar boletines adicionales
INSERT IGNORE INTO boletines (titulo, contenido, resumen, autor_id, estado, fecha_publicacion) VALUES
('Nuevas Alianzas Estratégicas en el Sector', 'Anunciamos nuevas alianzas estratégicas con empresas líderes del sector automotriz que fortalecerán nuestro ecosistema empresarial...', 'Nuevas alianzas estratégicas fortalecen el ecosistema', 1, 'publicado', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('Programa de Capacitación 2025', 'Lanzamos nuestro programa integral de capacitación para el año 2025, enfocado en las competencias del futuro...', 'Nuevo programa de capacitación para el 2025', 7, 'publicado', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('Resultados del Primer Trimestre', 'Compartimos los resultados positivos del primer trimestre del año, con un crecimiento significativo en la participación...', 'Resultados positivos del primer trimestre 2025', 1, 'publicado', NOW()),
('Convocatoria Comité de Sostenibilidad', 'Invitamos a todas las empresas miembro a participar en las iniciativas del Comité de Sostenibilidad...', 'Convocatoria abierta para iniciativas sustentables', 14, 'publicado', DATE_SUB(NOW(), INTERVAL 3 DAY));

-- Insertar descuentos adicionales
INSERT IGNORE INTO descuentos (titulo, descripcion, empresa_oferente, porcentaje_descuento, fecha_inicio, fecha_fin, codigo_descuento, estado) VALUES
('Descuento en Servicios de Consultoría', '25% de descuento en servicios de consultoría empresarial', 'Consultoría Automotriz Premium', 25.00, '2025-08-01', '2025-10-31', 'CONSULT25', 'activo'),
('Software de Gestión Industrial', '30% de descuento en licencias de software de gestión', 'TechSolutions México', 30.00, '2025-08-01', '2025-09-30', 'SOFT30', 'activo'),
('Equipos de Medición', '15% de descuento en equipos de medición y calibración', 'Instrumentos de Precisión SA', 15.00, '2025-08-01', '2025-12-31', 'MEDICION15', 'activo'),
('Servicios de Logística', '20% de descuento en servicios de transporte especializado', 'Logística Especializada México', 20.00, '2025-08-15', '2025-11-15', 'LOGIST20', 'activo'),
('Membresía Club Empresarial', '40% de descuento en membresía anual del club empresarial', 'Club Empresarial Automotriz', 40.00, '2025-08-01', '2025-08-31', 'CLUB40', 'activo');

-- Verificar los datos insertados
SELECT 
    'Empresas' as Tabla,
    COUNT(*) as Total
FROM empresas 
WHERE estado = 'activa'

UNION ALL

SELECT 
    'Usuarios' as Tabla,
    COUNT(*) as Total
FROM usuarios_perfil 
WHERE estado = 'activo'

UNION ALL

SELECT 
    'Comités' as Tabla,
    COUNT(*) as Total
FROM comites 
WHERE estado = 'activo'

UNION ALL

SELECT 
    'Miembros de Comités' as Tabla,
    COUNT(*) as Total
FROM comite_miembros 
WHERE estado = 'activo'

UNION ALL

SELECT 
    'Eventos' as Tabla,
    COUNT(*) as Total
FROM eventos

UNION ALL

SELECT 
    'Boletines' as Tabla,
    COUNT(*) as Total
FROM boletines 
WHERE estado = 'publicado'

UNION ALL

SELECT 
    'Descuentos' as Tabla,
    COUNT(*) as Total
FROM descuentos 
WHERE estado = 'activo';

-- Vista para empresas en convenio con estadísticas
CREATE OR REPLACE VIEW vista_empresas_convenio AS
SELECT 
    e.id,
    e.nombre,
    e.descripcion,
    e.website,
    e.telefono,
    e.email,
    e.direccion,
    e.logo,
    e.estado,
    e.fecha_registro,
    e.fecha_actualizacion,
    COUNT(DISTINCT u.id) as total_empleados,
    COUNT(DISTINCT cm.comite_id) as comites_participando,
    GROUP_CONCAT(DISTINCT c.nombre SEPARATOR ', ') as nombres_comites
FROM empresas e
LEFT JOIN usuarios u ON e.id = u.empresa_id AND u.estado = 'activo'
LEFT JOIN comite_miembros cm ON u.id = cm.usuario_id AND cm.estado = 'activo'
LEFT JOIN comites c ON cm.comite_id = c.id AND c.estado = 'activo'
WHERE e.estado = 'activa'
GROUP BY e.id, e.nombre, e.descripcion, e.website, e.telefono, e.email, 
         e.direccion, e.logo, e.estado, e.fecha_registro, e.fecha_actualizacion
ORDER BY e.nombre;
