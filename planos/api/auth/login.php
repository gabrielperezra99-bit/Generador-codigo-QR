<?php
// Configurar el manejo de errores para que no se muestren en pantalla
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

include_once '../config/cors.php';

try {
    include_once '../config/database.php';
    include_once '../models/User.php';
    include_once '../utils/jwt.php';

    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Error de conexión a la base de datos");
    }

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

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "message" => "Error del servidor",
        "error" => $e->getMessage()
    ));
}
?>