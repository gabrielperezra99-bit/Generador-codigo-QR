<?php
include_once '../config/cors.php';
include_once '../config/database.php';
include_once '../models/User.php';
include_once '../utils/jwt.php';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$jwt = new JWTHandler();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->nombre) && !empty($data->email) && !empty($data->password)) {
    $user->nombre = $data->nombre;
    $user->email = $data->email;
    $user->password = $data->password;

    // Verificar si el email ya existe
    if ($user->emailExists()) {
        http_response_code(400);
        echo json_encode(array("message" => "El email ya está registrado"));
    } else {
        if ($user->create()) {
            $token = $jwt->generateToken(array(
                "id" => $user->id,
                "nombre" => $user->nombre,
                "email" => $user->email
            ));

            http_response_code(201);
            echo json_encode(array(
                "message" => "Usuario registrado exitosamente",
                "token" => $token,
                "user" => array(
                    "id" => $user->id,
                    "nombre" => $user->nombre,
                    "email" => $user->email
                )
            ));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Error al registrar usuario"));
        }
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Todos los campos son requeridos"));
}
?>