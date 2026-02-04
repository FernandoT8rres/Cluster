-- Esquema de base de datos para tabla descuentos
-- Compatible con MySQL/MariaDB

CREATE TABLE IF NOT EXISTS `descuentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_empresa` varchar(255) NOT NULL,
  `categoria` varchar(100) NOT NULL,
  `porcentaje_descuento` int(11) NOT NULL,
  `descripcion` text,
  `ubicacion` varchar(255) DEFAULT NULL,
  `horario` varchar(255) DEFAULT NULL,
  `como_aplicar` text,
  `telefono` varchar(50) DEFAULT NULL,
  `codigo_promocional` varchar(100) DEFAULT NULL,
  `logo_url` varchar(500) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activo` (`activo`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_fecha_creacion` (`fecha_creacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de ejemplo para descuentos
INSERT IGNORE INTO `descuentos` (`nombre_empresa`, `categoria`, `porcentaje_descuento`, `descripcion`, `ubicacion`, `horario`, `como_aplicar`, `telefono`, `codigo_promocional`, `logo_url`, `activo`) VALUES
('Restaurant La Hacienda', 'Restaurante', 15, 'Auténtica comida mexicana en un ambiente familiar y acogedor', 'Av. Revolución 1234, Col. Centro, CDMX', 'Lunes a Domingo 12:00 - 22:00', 'Presentar credencial de empleado CLAUT al momento de pagar', '+52 55 1234-5678', 'CLAUT15', 'https://ui-avatars.com/api/?name=La+Hacienda&background=f59e0b&color=fff&size=100', 1),
('Hotel Business Express', 'Hoteles', 25, 'Hotel de negocios con todas las comodidades para viajeros corporativos', 'Zona Rosa, CDMX', '24 horas', 'Reservar por teléfono mencionando código promocional CLAUT', '+52 55 2345-6789', 'CLAUT25', 'https://ui-avatars.com/api/?name=Business+Express&background=3b82f6&color=fff&size=100', 1),
('Dulces Momentos Pastelería', 'Pastelería', 20, 'Pasteles artesanales y postres gourmet para toda ocasión', 'Col. Roma Norte, CDMX', 'Martes a Domingo 9:00 - 20:00', 'Mostrar credencial de empleado y mencionar descuento CLAUT', '+52 55 3456-7890', 'SWEET20', 'https://ui-avatars.com/api/?name=Dulces+Momentos&background=ec4899&color=fff&size=100', 1),
('Cineplex Entertainment', 'Entretenimiento', 30, 'Cadena de cines con la mejor tecnología y experiencia cinematográfica', 'Múltiples ubicaciones en CDMX', 'Todos los días 10:00 - 23:00', 'Comprar boletos en taquilla con credencial de empleado', '+52 55 4567-8901', 'CINEMA30', 'https://ui-avatars.com/api/?name=Cineplex&background=7c3aed&color=fff&size=100', 1),
('TechStore México', 'Tecnología', 12, 'Tienda especializada en productos tecnológicos y gadgets innovadores', 'Santa Fe, CDMX', 'Lunes a Sábado 10:00 - 21:00, Domingo 11:00 - 19:00', 'Aplicar descuento al momento del pago presentando credencial', '+52 55 5678-9012', 'TECH12', 'https://ui-avatars.com/api/?name=TechStore&background=10b981&color=fff&size=100', 1),
('Clínica Dental Sonríe', 'Salud', 18, 'Servicios dentales integrales con tecnología de vanguardia', 'Polanco, CDMX', 'Lunes a Viernes 8:00 - 19:00, Sábado 9:00 - 15:00', 'Agendar cita mencionando convenio con CLAUT', '+52 55 6789-0123', 'SMILE18', 'https://ui-avatars.com/api/?name=Dental+Sonrie&background=06b6d4&color=fff&size=100', 1),
('Fitness World Gym', 'Otro', 35, 'Gimnasio completo con equipos de última generación y clases grupales', 'Condesa, CDMX', 'Lunes a Viernes 5:00 - 23:00, Fines de semana 7:00 - 21:00', 'Presentar credencial al momento de la inscripción', '+52 55 7890-1234', 'FIT35', 'https://ui-avatars.com/api/?name=Fitness+World&background=dc2626&color=fff&size=100', 1),
('Café de la Esquina', 'Restaurante', 10, 'Café de especialidad y comida ligera en ambiente relajado', 'Col. Del Valle, CDMX', 'Lunes a Viernes 7:00 - 22:00, Fines de semana 8:00 - 23:00', 'Descuento automático al mostrar credencial', '+52 55 8901-2345', 'CAFE10', 'https://ui-avatars.com/api/?name=Cafe+Esquina&background=92400e&color=fff&size=100', 0);