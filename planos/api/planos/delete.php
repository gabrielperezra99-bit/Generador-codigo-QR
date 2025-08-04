<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../utils/jwt.php';

$database = new Database();
$db = $database->getConnection();
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

if(!empty($data->id)) {
    try {
        // Verificar que el plano pertenece al usuario a través del proyecto
        $query = "SELECT p.id, p.archivo_url, p.metadata, pr.usuario_id 
                  FROM planos p 
                  INNER JOIN proyectos pr ON p.proyecto_id = pr.id 
                  WHERE p.id = :plano_id AND pr.usuario_id = :usuario_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":plano_id", $data->id);
        $stmt->bindParam(":usuario_id", $decoded['user_id']);
        $stmt->execute();
        
        $plano = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($plano) {
            // Eliminar archivo físico si existe
            if (!empty($plano['archivo_url'])) {
                $archivo_path = "../../" . $plano['archivo_url'];
                if (file_exists($archivo_path)) {
                    unlink($archivo_path);
                }
            }
            
            // Eliminar el plano de la base de datos
            $delete_query = "DELETE FROM planos WHERE id = :id";
            $delete_stmt = $db->prepare($delete_query);
            $delete_stmt->bindParam(":id", $data->id);
            
            if ($delete_stmt->execute()) {
                http_response_code(200);
                echo json_encode(array("message" => "Plano eliminado exitosamente."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo eliminar el plano."));
            }
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Plano no encontrado o sin permisos."));
        }
    } catch (Exception $e) {
        error_log("Error eliminando plano: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(array("message" => "Error interno del servidor."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "ID del plano requerido."));
}
?>