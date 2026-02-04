        // Configurar headers para descarga
        header('Content-Type: ' . $archivo['tipo_mime']);
        header('Content-Disposition: attachment; filename="' . $archivo['nombre_original'] . '"');
        header('Content-Length: ' . filesize($ruta_archivo));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        // Enviar archivo
        readfile($ruta_archivo);
        exit();
    }
    
    /**
     * Incrementar visualizaciones
     */
    private function incrementarVisualizaciones($id) {
        $sql = "UPDATE boletines SET visualizaciones = visualizaciones + 1 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        
        return $this->respuestaExitosa(['mensaje' => 'Visualización registrada']);
    }
    
    /**
     * Manejar PUT requests
     */
    private function manejarPUT($segments) {
        if (!$this->usuario_id) {
            throw new Exception('No autorizado', 401);
        }
        
        if (count($segments) >= 2 && is_numeric($segments[1])) {
            return $this->actualizarBoletin($segments[1]);
        }
        
        throw new Exception('Endpoint no encontrado', 404);
    }
    
    /**
     * Manejar DELETE requests
     */
    private function manejarDELETE($segments) {
        if (!$this->usuario_id) {
            throw new Exception('No autorizado', 401);
        }
        
        if (count($segments) >= 2 && is_numeric($segments[1])) {
            return $this->eliminarBoletin($segments[1]);
        }
        
        throw new Exception('Endpoint no encontrado', 404);
    }
    
    /**
     * Actualizar boletín
     */
    private function actualizarBoletin($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Datos inválidos', 400);
        }
        
        // Verificar permisos (solo el autor o admin puede editar)
        $sql = "SELECT autor_id FROM boletines WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $boletin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$boletin) {
            throw new Exception('Boletín no encontrado', 404);
        }
        
        if ($boletin['autor_id'] != $this->usuario_id) {
            // TODO: Verificar si es admin
            throw new Exception('Sin permisos para editar este boletín', 403);
        }
        
        // Actualizar campos
        $campos = [];
        $valores = [];
        
        if (isset($input['titulo'])) {
            $campos[] = 'titulo = ?';
            $valores[] = $input['titulo'];
        }
        
        if (isset($input['contenido'])) {
            $campos[] = 'contenido = ?';
            $valores[] = $input['contenido'];
        }
        
        if (isset($input['resumen'])) {
            $campos[] = 'resumen = ?';
            $valores[] = $input['resumen'];
        }
        
        if (isset($input['imagen'])) {
            $campos[] = 'imagen = ?';
            $valores[] = $input['imagen'];
        }
        
        if (isset($input['estado'])) {
            $campos[] = 'estado = ?';
            $valores[] = $input['estado'];
        }
        
        if (isset($input['destacado'])) {
            $campos[] = 'destacado = ?';
            $valores[] = $input['destacado'] ? 1 : 0;
        }
        
        if (empty($campos)) {
            throw new Exception('No hay campos para actualizar', 400);
        }
        
        $valores[] = $id;
        
        $sql = "UPDATE boletines SET " . implode(', ', $campos) . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($valores);
        
        return $this->respuestaExitosa(['mensaje' => 'Boletín actualizado correctamente']);
    }
    
    /**
     * Eliminar boletín
     */
    private function eliminarBoletin($id) {
        // Verificar permisos
        $sql = "SELECT autor_id FROM boletines WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $boletin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$boletin) {
            throw new Exception('Boletín no encontrado', 404);
        }
        
        if ($boletin['autor_id'] != $this->usuario_id) {
            // TODO: Verificar si es admin
            throw new Exception('Sin permisos para eliminar este boletín', 403);
        }
        
        try {
            $this->conn->beginTransaction();
            
            // Obtener archivos para eliminarlos físicamente
            $archivos = $this->obtenerArchivosBoletin($id);
            
            // Eliminar archivos físicos
            foreach ($archivos as $archivo) {
                $ruta_archivo = UPLOADS_DIR . $archivo['nombre_archivo'];
                if (file_exists($ruta_archivo)) {
                    unlink($ruta_archivo);
                }
            }
            
            // Eliminar registros de archivos
            $sql = "DELETE FROM boletines_archivos WHERE boletin_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$id]);
            
            // Eliminar boletín
            $sql = "DELETE FROM boletines WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$id]);
            
            $this->conn->commit();
            
            return $this->respuestaExitosa(['mensaje' => 'Boletín eliminado correctamente']);
            
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
    
    /**
     * Formatear tamaño de archivo
     */
    private function formatearTamaño($bytes) {
        if ($bytes == 0) return '0 Bytes';
        
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
    
    /**
     * Respuesta exitosa
     */
    private function respuestaExitosa($data = null) {
        return [
            'success' => true,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Respuesta de error
     */
    private function respuestaError($mensaje, $codigo = 500) {
        http_response_code($codigo);
        return [
            'success' => false,
            'message' => $mensaje,
            'code' => $codigo,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

// Manejo de errores globales
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    $api = new BoletinesCompleto();
    $resultado = $api->manejarRequest();
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Error en API boletines: " . $e->getMessage());
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'code' => $e->getCode() ?: 500,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
