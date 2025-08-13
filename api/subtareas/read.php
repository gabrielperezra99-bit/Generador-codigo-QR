<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
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

try {
    $plano_id = isset($_GET['plano_id']) ? $_GET['plano_id'] : null;
    
    if (!$plano_id) {
        http_response_code(400);
        echo json_encode(array("message" => "ID de plano requerido.", "success" => false));
        exit();
    }
    
    // Verificar que el plano pertenece al usuario autenticado
    $query_check = "SELECT id FROM planos WHERE id = :plano_id AND usuario_id = :usuario_id";
    $stmt_check = $db->prepare($query_check);
    $stmt_check->bindParam(':plano_id', $plano_id);
    $stmt_check->bindParam(':usuario_id', $decoded['id']);
    $stmt_check->execute();
    
    if ($stmt_check->rowCount() == 0) {
        http_response_code(403);
        echo json_encode(array("message" => "No tienes permisos para ver las subtareas de este plano.", "success" => false));
        exit();
    }
    
    // Obtener subtareas del plano
    $query = "SELECT id, titulo, descripcion, estado, fecha_creacion, fecha_actualizacion 
              FROM subtareas 
              WHERE plano_id = :plano_id 
              ORDER BY fecha_creacion DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':plano_id', $plano_id);
    $stmt->execute();
    
    $subtareas = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $subtareas[] = array(
            'id' => $row['id'],
            'titulo' => $row['titulo'],
            'descripcion' => $row['descripcion'],
            'estado' => $row['estado'],
            'fecha_creacion' => $row['fecha_creacion'],
            'fecha_actualizacion' => $row['fecha_actualizacion'],
            'completada' => $row['estado'] === 'completada'
        );
    }
    
    echo json_encode($subtareas);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Error del servidor: " . $e->getMessage(), "success" => false));
}
?>