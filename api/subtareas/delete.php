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
    echo json_encode(array("message" => "Token de acceso requerido.", "success" => false));
    exit();
}

$token = $matches[1];
$decoded = $jwt->validateToken($token);

if (!$decoded) {
    http_response_code(401);
    echo json_encode(array("message" => "Token inválido.", "success" => false));
    exit();
}

// Obtener datos del DELETE
$data = json_decode(file_get_contents("php://input"));

if (empty($data->id)) {
    http_response_code(400);
    echo json_encode(array("message" => "ID de subtarea requerido.", "success" => false));
    exit();
}

try {
    // Verificar que la subtarea pertenece a un plano del usuario autenticado
    $query_check = "SELECT s.id FROM subtareas s 
                    INNER JOIN planos p ON s.plano_id = p.id 
                    WHERE s.id = :subtarea_id AND p.usuario_id = :usuario_id";
    $stmt_check = $db->prepare($query_check);
    $stmt_check->bindParam(':subtarea_id', $data->id);
    $stmt_check->bindParam(':usuario_id', $decoded['id']);
    $stmt_check->execute();
    
    if ($stmt_check->rowCount() == 0) {
        http_response_code(403);
        echo json_encode(array("message" => "No tienes permisos para eliminar esta subtarea.", "success" => false));
        exit();
    }
    
    // Eliminar subtarea
    $query = "DELETE FROM subtareas WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data->id);
    
    if ($stmt->execute()) {
        echo json_encode(array(
            "message" => "Subtarea eliminada exitosamente.",
            "success" => true
        ));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Error al eliminar la subtarea.", "success" => false));
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Error del servidor: " . $e->getMessage(), "success" => false));
}
?>