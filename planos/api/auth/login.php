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

if (!empty($data->email) && !empty($data->password)) {
    $user->email = $data->email;
    
    if ($user->emailExists()) {
        if ($user->verifyPassword($data->password)) {
            $token = $jwt->generateToken(array(
                "id" => $user->id,
                "nombre" => $user->nombre,
                "email" => $user->email
            ));

            http_response_code(200);
            echo json_encode(array(
                "message" => "Login exitoso",
                "token" => $token,
                "user" => array(
                    "id" => $user->id,
                    "nombre" => $user->nombre,
                    "email" => $user->email
                )
            ));
        } else {
            http_response_code(401);
            echo json_encode(array("message" => "Credenciales inválidas"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Usuario no encontrado"));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Email y contraseña son requeridos"));
}
?>