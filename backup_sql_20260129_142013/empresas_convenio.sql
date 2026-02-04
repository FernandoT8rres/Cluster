-- Estructura de tabla para empresas_convenio
-- Ejecutar este script en tu base de datos MySQL/MariaDB

CREATE TABLE IF NOT EXISTS `empresas_convenio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_empresa` varchar(255) NOT NULL,
  `descripcion` text,
  `logo_url` varchar(500) DEFAULT NULL,
  `sitio_web` varchar(255) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text,
  `categoria` varchar(100) DEFAULT NULL,
  `descuento` decimal(5,2) DEFAULT NULL,
  `beneficios` text,
  `fecha_inicio_convenio` date DEFAULT NULL,
  `fecha_fin_convenio` date DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `destacado` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activo` (`activo`),
  KEY `idx_destacado` (`destacado`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de ejemplo (opcional)
INSERT INTO `empresas_convenio` (`nombre_empresa`, `descripcion`, `categoria`, `descuento`, `beneficios`, `activo`, `destacado`, `sitio_web`, `email`, `telefono`) VALUES
('AutoParts México', 'Proveedor líder de autopartes en México', 'Automotriz', 15.00, 'Descuentos especiales en autopartes, envío gratis en compras mayores a $1000', 1, 1, 'https://autoparts.mx', 'contacto@autoparts.mx', '+52 55 1234 5678'),
('TechSolutions', 'Soluciones tecnológicas para la industria automotriz', 'Tecnología', 10.00, 'Consultoría gratuita, soporte técnico prioritario', 1, 0, 'https://techsolutions.com', 'info@techsolutions.com', '+52 55 2345 6789'),
('Logística Express', 'Servicios de logística y transporte', 'Logística', 20.00, 'Tarifas preferenciales, rastreo en tiempo real', 1, 1, 'https://logisticaexpress.mx', 'ventas@logisticaexpress.mx', '+52 55 3456 7890'),
('Manufactura Avanzada', 'Fabricación de componentes automotrices', 'Manufactura', 12.50, 'Descuentos por volumen, tiempos de entrega prioritarios', 1, 0, 'https://manufactura-avanzada.com', 'contacto@manufactura.com', '+52 55 4567 8901'),
('Servicios Integrales', 'Consultoría y servicios para empresas', 'Servicios', 8.00, 'Primera consulta gratis, planes personalizados', 1, 0, NULL, 'info@servicios.mx', '+52 55 5678 9012');
