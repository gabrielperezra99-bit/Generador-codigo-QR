<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, PUT");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/Plano.php';
include_once '../utils/jwt.php';

$database = new Database();
$db = $database->getConnection();

$plano = new Plano($db);
$jwt = new JWTHandler();

// Verificar token JWT
$token = $jwt->getTokenFromHeader();

if (!$token) {
    http_response_code(401);
    echo json_encode(array("success" => false, "message" => "Token de acceso requerido."));
    exit();
}

$decoded = $jwt->validateToken($token);

if (!$decoded) {
    http_response_code(401);
    echo json_encode(array("success" => false, "message" => "Token inválido."));
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->id) && !empty($data->nombre)) {
    $plano->id = $data->id;
    $plano->usuario_id = $decoded['id'];
    
    // Verificar que el plano pertenece al usuario
    if($plano->readOne() && $plano->usuario_id == $decoded['id']) {
        $plano->nombre = $data->nombre;
        $plano->descripcion = isset($data->descripcion) ? $data->descripcion : '';
        $plano->cliente = isset($data->cliente) ? $data->cliente : '';
        $plano->estado = isset($data->estado) ? $data->estado : $plano->estado;
        $plano->progreso_porcentaje = isset($data->progreso_porcentaje) ? $data->progreso_porcentaje : $plano->progreso_porcentaje;
        $plano->etiquetas = isset($data->etiquetas) ? $data->etiquetas : $plano->etiquetas;
        $plano->tiempo_estimado = isset($data->tiempo_estimado) ? $data->tiempo_estimado : $plano->tiempo_estimado;
        $plano->imagen_preview = $plano->imagen_preview; // Mantener el valor actual

        if($plano->update()) {
            http_response_code(200);
            echo json_encode(array("success" => true, "message" => "Plano actualizado exitosamente."));
        } else {
            http_response_code(503);
            echo json_encode(array("success" => false, "message" => "No se pudo actualizar el plano."));
        }
    } else {
        http_response_code(404);
        echo json_encode(array("success" => false, "message" => "Plano no encontrado o sin permisos."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Datos incompletos."));
}
?>