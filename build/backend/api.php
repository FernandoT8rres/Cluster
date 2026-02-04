<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'database.php';
require_once 'descuento.php';

$database = Database::getInstance();
$db = $database->getConnection();
$descuento = new Descuento($db);

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if(isset($_GET['id'])) {
            // Obtener un descuento específico
            $descuento->id = $_GET['id'];
            if($descuento->leerUno()) {
                $descuento_item = array(
                    "id" => $descuento->id,
                    "nombre_empresa" => $descuento->nombre_empresa,
                    "categoria" => $descuento->categoria,
                    "porcentaje_descuento" => $descuento->porcentaje_descuento,
                    "descripcion" => $descuento->descripcion,
                    "ubicacion" => $descuento->ubicacion,
                    "horario" => $descuento->horario,
                    "como_aplicar" => $descuento->como_aplicar,
                    "telefono" => $descuento->telefono,
                    "codigo_promocional" => $descuento->codigo_promocional,
                    "logo_url" => $descuento->logo_url,
                    "activo" => $descuento->activo,
                    "fecha_creacion" => $descuento->fecha_creacion,
                    "fecha_actualizacion" => $descuento->fecha_actualizacion
                );
                http_response_code(200);
                echo json_encode($descuento_item);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Descuento no encontrado."));
            }
        } else if(isset($_GET['activos']) && $_GET['activos'] == 'true') {
            // Obtener solo descuentos activos
            $stmt = $descuento->leerActivos();
            $num = $stmt->rowCount();

            if($num > 0) {
                $descuentos_arr = array();
                $descuentos_arr["records"] = array();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    extract($row);
                    $descuento_item = array(
                        "id" => $id,
                        "nombre_empresa" => $nombre_empresa,
                        "categoria" => $categoria,
                        "porcentaje_descuento" => $porcentaje_descuento,
                        "descripcion" => $descripcion,
                        "ubicacion" => $ubicacion,
                        "horario" => $horario,
                        "como_aplicar" => $como_aplicar,
                        "telefono" => $telefono,
                        "codigo_promocional" => $codigo_promocional,
                        "logo_url" => $logo_url,
                        "activo" => $activo,
                        "fecha_creacion" => $fecha_creacion,
                        "fecha_actualizacion" => $fecha_actualizacion
                    );
                    array_push($descuentos_arr["records"], $descuento_item);
                }
                http_response_code(200);
                echo json_encode($descuentos_arr);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "No se encontraron descuentos activos."));
            }
        } else {
            // Obtener todos los descuentos
            $stmt = $descuento->leer();
            $num = $stmt->rowCount();

            if($num > 0) {
                $descuentos_arr = array();
                $descuentos_arr["records"] = array();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    extract($row);
                    $descuento_item = array(
                        "id" => $id,
                        "nombre_empresa" => $nombre_empresa,
                        "categoria" => $categoria,
                        "porcentaje_descuento" => $porcentaje_descuento,
                        "descripcion" => $descripcion,
                        "ubicacion" => $ubicacion,
                        "horario" => $horario,
                        "como_aplicar" => $como_aplicar,
                        "telefono" => $telefono,
                        "codigo_promocional" => $codigo_promocional,
                        "logo_url" => $logo_url,
                        "activo" => $activo,
                        "fecha_creacion" => $fecha_creacion,
                        "fecha_actualizacion" => $fecha_actualizacion
                    );
                    array_push($descuentos_arr["records"], $descuento_item);
                }
                http_response_code(200);
                echo json_encode($descuentos_arr);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "No se encontraron descuentos."));
            }
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if(!empty($data->nombre_empresa) && !empty($data->categoria) && !empty($data->porcentaje_descuento)) {
            $descuento->nombre_empresa = $data->nombre_empresa;
            $descuento->categoria = $data->categoria;
            $descuento->porcentaje_descuento = $data->porcentaje_descuento;
            $descuento->descripcion = $data->descripcion;
            $descuento->ubicacion = $data->ubicacion ?? '';
            $descuento->horario = $data->horario ?? '';
            $descuento->como_aplicar = $data->como_aplicar ?? '';
            $descuento->telefono = $data->telefono ?? '';
            $descuento->codigo_promocional = $data->codigo_promocional ?? '';
            $descuento->logo_url = $data->logo_url ?? '';
            $descuento->activo = $data->activo ?? 1;

            if($descuento->crear()) {
                http_response_code(201);
                echo json_encode(array("message" => "Descuento creado exitosamente."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo crear el descuento."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "No se pueden crear datos incompletos."));
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));

        $descuento->id = $data->id;
        $descuento->nombre_empresa = $data->nombre_empresa;
        $descuento->categoria = $data->categoria;
        $descuento->porcentaje_descuento = $data->porcentaje_descuento;
        $descuento->descripcion = $data->descripcion;
        $descuento->ubicacion = $data->ubicacion ?? '';
        $descuento->horario = $data->horario ?? '';
        $descuento->como_aplicar = $data->como_aplicar ?? '';
        $descuento->telefono = $data->telefono ?? '';
        $descuento->codigo_promocional = $data->codigo_promocional ?? '';
        $descuento->logo_url = $data->logo_url ?? '';
        $descuento->activo = $data->activo ?? 1;

        if($descuento->actualizar()) {
            http_response_code(200);
            echo json_encode(array("message" => "Descuento actualizado exitosamente."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "No se pudo actualizar el descuento."));
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"));
        $descuento->id = $data->id;

        if($descuento->eliminar()) {
            http_response_code(200);
            echo json_encode(array("message" => "Descuento eliminado exitosamente."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "No se pudo eliminar el descuento."));
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Método no permitido."));
        break;
}
?>
