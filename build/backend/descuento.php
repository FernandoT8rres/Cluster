<?php
require_once 'database.php';

class Descuento {
    private $conn;
    private $table_name = "descuentos";

    public $id;
    public $nombre_empresa;
    public $categoria;
    public $porcentaje_descuento;
    public $descripcion;
    public $ubicacion;
    public $horario;
    public $como_aplicar;
    public $telefono;
    public $codigo_promocional;
    public $logo_url;
    public $activo;
    public $fecha_creacion;
    public $fecha_actualizacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear descuento
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                 SET nombre_empresa=:nombre_empresa, categoria=:categoria, porcentaje_descuento=:porcentaje_descuento, 
                     descripcion=:descripcion, ubicacion=:ubicacion, horario=:horario, como_aplicar=:como_aplicar, 
                     telefono=:telefono, codigo_promocional=:codigo_promocional, logo_url=:logo_url, activo=:activo";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre_empresa", $this->nombre_empresa);
        $stmt->bindParam(":categoria", $this->categoria);
        $stmt->bindParam(":porcentaje_descuento", $this->porcentaje_descuento);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":ubicacion", $this->ubicacion);
        $stmt->bindParam(":horario", $this->horario);
        $stmt->bindParam(":como_aplicar", $this->como_aplicar);
        $stmt->bindParam(":telefono", $this->telefono);
        $stmt->bindParam(":codigo_promocional", $this->codigo_promocional);
        $stmt->bindParam(":logo_url", $this->logo_url);
        $stmt->bindParam(":activo", $this->activo);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Leer todos los descuentos
    public function leer() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY fecha_creacion DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Leer descuentos activos
    public function leerActivos() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE activo = 1 ORDER BY fecha_creacion DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Leer un descuento específico
    public function leerUno() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->nombre_empresa = $row['nombre_empresa'];
            $this->categoria = $row['categoria'];
            $this->porcentaje_descuento = $row['porcentaje_descuento'];
            $this->descripcion = $row['descripcion'];
            $this->ubicacion = $row['ubicacion'];
            $this->horario = $row['horario'];
            $this->como_aplicar = $row['como_aplicar'];
            $this->telefono = $row['telefono'];
            $this->codigo_promocional = $row['codigo_promocional'];
            $this->logo_url = $row['logo_url'];
            $this->activo = $row['activo'];
            $this->fecha_creacion = $row['fecha_creacion'];
            $this->fecha_actualizacion = $row['fecha_actualizacion'];
            return true;
        }
        return false;
    }

    // Actualizar descuento
    public function actualizar() {
        $query = "UPDATE " . $this->table_name . " 
                 SET nombre_empresa=:nombre_empresa, categoria=:categoria, porcentaje_descuento=:porcentaje_descuento, 
                     descripcion=:descripcion, ubicacion=:ubicacion, horario=:horario, como_aplicar=:como_aplicar, 
                     telefono=:telefono, codigo_promocional=:codigo_promocional, logo_url=:logo_url, activo=:activo,
                     fecha_actualizacion=CURRENT_TIMESTAMP
                 WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nombre_empresa', $this->nombre_empresa);
        $stmt->bindParam(':categoria', $this->categoria);
        $stmt->bindParam(':porcentaje_descuento', $this->porcentaje_descuento);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':ubicacion', $this->ubicacion);
        $stmt->bindParam(':horario', $this->horario);
        $stmt->bindParam(':como_aplicar', $this->como_aplicar);
        $stmt->bindParam(':telefono', $this->telefono);
        $stmt->bindParam(':codigo_promocional', $this->codigo_promocional);
        $stmt->bindParam(':logo_url', $this->logo_url);
        $stmt->bindParam(':activo', $this->activo);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Eliminar descuento
    public function eliminar() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>
