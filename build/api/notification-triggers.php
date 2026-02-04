<?php
require_once '../config/database.php';

class NotificationTriggers {
    private $db;
    private $connection;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->connection = $this->db->getConnection();
    }

    /**
     * Crear notificación cuando se agrega un nuevo evento
     */
    public function onNewEvent($eventoData) {
        $notification = [
            'titulo' => 'Nuevo Evento: ' . $eventoData['titulo'],
            'contenido' => $this->generateEventContent($eventoData),
            'tipo' => 'evento',
            'origen_id' => $eventoData['id'],
            'origen_tabla' => 'eventos',
            'dirigido_a' => 'todos',
            'importante' => $this->isEventImportant($eventoData)
        ];

        return $this->createNotification($notification);
    }

    /**
     * Crear notificación cuando se agrega un nuevo boletín
     */
    public function onNewBoletin($boletinData) {
        $notification = [
            'titulo' => 'Nuevo Boletín: ' . ($boletinData['titulo'] ?? 'Boletín Informativo'),
            'contenido' => 'Se ha publicado un nuevo boletín informativo con actualizaciones importantes. Consulta la sección de boletines para más detalles.',
            'tipo' => 'boletin',
            'origen_id' => $boletinData['id'],
            'origen_tabla' => 'boletines',
            'dirigido_a' => 'todos',
            'importante' => false
        ];

        return $this->createNotification($notification);
    }

    /**
     * Crear notificación cuando se agrega un nuevo documento
     */
    public function onNewDocument($documentData) {
        $notification = [
            'titulo' => 'Nuevo Documento: ' . ($documentData['nombre'] ?? 'Documento'),
            'contenido' => 'Se ha añadido un nuevo documento a la biblioteca. Consulta la sección de documentación para acceder al archivo.',
            'tipo' => 'documento',
            'origen_id' => $documentData['id'],
            'origen_tabla' => 'documentos',
            'dirigido_a' => 'todos',
            'importante' => $documentData['importante'] ?? false
        ];

        return $this->createNotification($notification);
    }

    /**
     * Crear notificación cuando se agrega una nueva empresa
     */
    public function onNewCompany($empresaData) {
        $notification = [
            'titulo' => 'Nueva Empresa en Convenio: ' . $empresaData['nombre_empresa'],
            'contenido' => $this->generateCompanyContent($empresaData),
            'tipo' => 'general',
            'origen_id' => $empresaData['id'],
            'origen_tabla' => 'empresas_convenio',
            'dirigido_a' => 'todos',
            'importante' => $empresaData['destacado'] ?? false
        ];

        return $this->createNotification($notification);
    }

    /**
     * Crear notificación cuando un comité programa una sesión
     */
    public function onComiteSession($comiteData, $sessionData) {
        $notification = [
            'titulo' => 'Sesión Virtual - ' . $comiteData['nombre'],
            'contenido' => $this->generateComiteSessionContent($comiteData, $sessionData),
            'tipo' => 'comite',
            'origen_id' => $comiteData['id'],
            'origen_tabla' => 'comites',
            'destinatario_rol' => 'miembro_comite',
            'dirigido_a' => 'rol',
            'importante' => true
        ];

        return $this->createNotification($notification);
    }

    /**
     * Crear notificación cuando se agrega un nuevo banner
     */
    public function onNewBanner($bannerData) {
        $notification = [
            'titulo' => 'Nuevo Anuncio: ' . ($bannerData['titulo'] ?? 'Anuncio Importante'),
            'contenido' => $bannerData['descripcion'] ?? 'Se ha publicado un nuevo anuncio en el panel principal. Consulta la página de inicio para más información.',
            'tipo' => 'general',
            'origen_id' => $bannerData['id'],
            'origen_tabla' => 'banner_carrusel',
            'dirigido_a' => 'todos',
            'importante' => true
        ];

        return $this->createNotification($notification);
    }

    /**
     * Crear notificación personalizada del sistema
     */
    public function onSystemNotification($data) {
        $notification = [
            'titulo' => $data['titulo'],
            'contenido' => $data['contenido'],
            'tipo' => 'sistema',
            'dirigido_a' => $data['dirigido_a'] ?? 'todos',
            'importante' => $data['importante'] ?? false,
            'destinatario_email' => $data['destinatario_email'] ?? null,
            'destinatario_rol' => $data['destinatario_rol'] ?? null
        ];

        return $this->createNotification($notification);
    }

    /**
     * Crear la notificación en la base de datos
     */
    private function createNotification($data) {
        try {
            $sql = "INSERT INTO notificaciones (
                        titulo, contenido, tipo, origen_id, origen_tabla,
                        destinatario_email, destinatario_rol, dirigido_a,
                        importante, metadata, fecha_creacion
                    ) VALUES (
                        :titulo, :contenido, :tipo, :origen_id, :origen_tabla,
                        :destinatario_email, :destinatario_rol, :dirigido_a,
                        :importante, :metadata, CURRENT_TIMESTAMP
                    )";

            $stmt = $this->connection->prepare($sql);
            $result = $stmt->execute([
                ':titulo' => $data['titulo'],
                ':contenido' => $data['contenido'],
                ':tipo' => $data['tipo'],
                ':origen_id' => $data['origen_id'] ?? null,
                ':origen_tabla' => $data['origen_tabla'] ?? null,
                ':destinatario_email' => $data['destinatario_email'] ?? null,
                ':destinatario_rol' => $data['destinatario_rol'] ?? null,
                ':dirigido_a' => $data['dirigido_a'],
                ':importante' => $data['importante'] ? 1 : 0,
                ':metadata' => json_encode($data['metadata'] ?? [])
            ]);

            if ($result) {
                return [
                    'success' => true,
                    'notification_id' => $this->connection->lastInsertId()
                ];
            }

            return ['success' => false, 'error' => 'Error al insertar notificación'];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generar contenido para notificación de evento
     */
    private function generateEventContent($eventoData) {
        $fecha = isset($eventoData['fecha_inicio']) ?
                 date('d/m/Y H:i', strtotime($eventoData['fecha_inicio'])) :
                 'próximamente';

        $modalidad = $eventoData['modalidad'] ?? 'presencial';
        $ubicacion = $eventoData['ubicacion'] ?? '';

        $content = "Se ha programado un nuevo evento para el {$fecha}.";
        $content .= " Modalidad: {$modalidad}.";

        if (!empty($ubicacion)) {
            $content .= " Ubicación: {$ubicacion}.";
        }

        $content .= " ¡No te pierdas esta oportunidad y regístrate ahora!";

        return $content;
    }

    /**
     * Generar contenido para notificación de empresa
     */
    private function generateCompanyContent($empresaData) {
        $nombre = $empresaData['nombre_empresa'];
        $categoria = $empresaData['categoria'] ?? 'servicios';
        $descuento = $empresaData['descuento'] ?? null;

        $content = "Damos la bienvenida a {$nombre} como nueva empresa en convenio.";
        $content .= " Categoría: {$categoria}.";

        if ($descuento) {
            $content .= " Los afiliados podrán acceder a descuentos de hasta {$descuento}%.";
        }

        $content .= " Consulta la sección de empresas en convenio para más detalles.";

        return $content;
    }

    /**
     * Generar contenido para notificación de sesión de comité
     */
    private function generateComiteSessionContent($comiteData, $sessionData) {
        $nombre = $comiteData['nombre'];
        $fecha = $sessionData['fecha'] ?? 'por confirmar';
        $hora = $sessionData['hora'] ?? '';
        $plataforma = $sessionData['plataforma'] ?? 'plataforma virtual';

        $content = "El comité {$nombre} ha programado una sesión virtual";

        if ($fecha !== 'por confirmar') {
            $content .= " para el {$fecha}";
            if (!empty($hora)) {
                $content .= " a las {$hora}";
            }
        }

        $content .= ". Se realizará a través de {$plataforma}.";
        $content .= " Los miembros registrados recibirán el enlace de acceso por email.";

        return $content;
    }

    /**
     * Determinar si un evento es importante
     */
    private function isEventImportant($eventoData) {
        // Un evento es importante si:
        // - Tiene capacidad limitada
        // - Es de tipo especial
        // - Está marcado como destacado

        $capacidad = $eventoData['capacidad_maxima'] ?? 0;
        $tipo = strtolower($eventoData['tipo'] ?? '');

        $tiposImportantes = ['conferencia', 'seminario', 'asamblea', 'capacitacion'];

        return ($capacidad > 0 && $capacidad <= 50) ||
               in_array($tipo, $tiposImportantes) ||
               ($eventoData['destacado'] ?? false);
    }

    /**
     * Método público para activar triggers desde otras partes del sistema
     */
    public static function trigger($type, $data, $extraData = null) {
        $triggers = new self();

        switch ($type) {
            case 'new_event':
                return $triggers->onNewEvent($data);
            case 'new_boletin':
                return $triggers->onNewBoletin($data);
            case 'new_document':
                return $triggers->onNewDocument($data);
            case 'new_company':
                return $triggers->onNewCompany($data);
            case 'comite_session':
                return $triggers->onComiteSession($data, $extraData);
            case 'new_banner':
                return $triggers->onNewBanner($data);
            case 'system_notification':
                return $triggers->onSystemNotification($data);
            default:
                return ['success' => false, 'error' => 'Tipo de trigger no válido'];
        }
    }
}

// Si se llama directamente, manejar request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $input = json_decode(file_get_contents('php://input'), true);
    $type = $input['type'] ?? null;
    $data = $input['data'] ?? null;
    $extraData = $input['extra_data'] ?? null;

    if (!$type || !$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Parámetros requeridos: type, data']);
        exit;
    }

    $result = NotificationTriggers::trigger($type, $data, $extraData);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
}
?>