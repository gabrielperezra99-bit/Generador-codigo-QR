<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
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

// Obtener datos del POST
$data = json_decode(file_get_contents("php://input"));

if (empty($data->plano_id) || empty($data->titulo)) {
    http_response_code(400);
    echo json_encode(array("message" => "Datos incompletos. Se requiere plano_id y titulo.", "success" => false));
    exit();
}

try {
    // Verificar que el plano pertenece al usuario autenticado
    $query_check = "SELECT id FROM planos WHERE id = :plano_id AND usuario_id = :usuario_id";
    $stmt_check = $db->prepare($query_check);
    $stmt_check->bindParam(':plano_id', $data->plano_id);
    $stmt_check->bindParam(':usuario_id', $decoded['id']);
    $stmt_check->execute();
    
    if ($stmt_check->rowCount() == 0) {
        http_response_code(403);
        echo json_encode(array("message" => "No tienes permisos para agregar subtareas a este plano.", "success" => false));
        exit();
    }
    
    // Insertar nueva subtarea
    $query = "INSERT INTO subtareas (plano_id, titulo, descripcion, estado) VALUES (:plano_id, :titulo, :descripcion, :estado)";
    $stmt = $db->prepare($query);
    
    $titulo = htmlspecialchars(strip_tags($data->titulo));
    $descripcion = isset($data->descripcion) ? htmlspecialchars(strip_tags($data->descripcion)) : $titulo;
    $estado = 'pendiente';
    
    $stmt->bindParam(':plano_id', $data->plano_id);
    $stmt->bindParam(':titulo', $titulo);
    $stmt->bindParam(':descripcion', $descripcion);
    $stmt->bindParam(':estado', $estado);
    
    if ($stmt->execute()) {
        $subtarea_id = $db->lastInsertId();
        echo json_encode(array(
            "message" => "Subtarea creada exitosamente.",
            "success" => true,
            "id" => $subtarea_id
        ));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Error al crear la subtarea.", "success" => false));
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Error del servidor: " . $e->getMessage(), "success" => false));
}
?>