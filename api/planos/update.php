<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/Plano.php';
include_once '../utils/jwt.php';

$database = new Database();
$db = $database->getConnection();

$plano = new Plano($db);
$jwt = new JWTHandler();

// Verificar token JWT
$headers = apache_request_headers();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(array("message" => "Token de acceso requerido."));
    exit();
}

$token = $matches[1];
$decoded = $jwt->validateToken($token);

if (!$decoded) {
    http_response_code(401);
    echo json_encode(array("message" => "Token inválido."));
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->id) && !empty($data->nombre)) {
    $plano->id = $data->id;
    
    // Verificar que el plano pertenece al usuario
    if($plano->readOne() && $plano->usuario_id == $decoded['user_id']) {
        $plano->nombre = $data->nombre;
        $plano->descripcion = isset($data->descripcion) ? $data->descripcion : '';
        $plano->cliente = isset($data->cliente) ? $data->cliente : '';

        if($plano->update()) {
            http_response_code(200);
            echo json_encode(array("message" => "Plano actualizado exitosamente."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "No se pudo actualizar el plano."));
        }
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "Plano no encontrado o sin permisos."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Datos incompletos."));
}
?>