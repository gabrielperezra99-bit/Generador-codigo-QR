<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
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

$usuario_id = $decoded['user_id'];

// Obtener parámetros de búsqueda
$search = isset($_GET['search']) ? $_GET['search'] : '';
$cliente = isset($_GET['cliente']) ? $_GET['cliente'] : '';
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';

try {
    $query = "SELECT * FROM planos WHERE usuario_id = :usuario_id";
    
    $params = array(':usuario_id' => $usuario_id);
    
    if (!empty($search)) {
        $query .= " AND (nombre LIKE :search OR descripcion LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    if (!empty($cliente)) {
        $query .= " AND cliente LIKE :cliente";
        $params[':cliente'] = '%' . $cliente . '%';
    }
    
    if (!empty($fecha_desde)) {
        $query .= " AND DATE(fecha_subida) >= :fecha_desde";
        $params[':fecha_desde'] = $fecha_desde;
    }
    
    if (!empty($fecha_hasta)) {
        $query .= " AND DATE(fecha_subida) <= :fecha_hasta";
        $params[':fecha_hasta'] = $fecha_hasta;
    }
    
    $query .= " ORDER BY fecha_subida DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    $num = $stmt->rowCount();
    
    if($num > 0) {
        $planos_arr = array();
        $planos_arr["records"] = array();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $metadata = json_decode($row['metadata'], true) ?? [];
            
            $plano_item = array(
                "id" => $row['id'],
                "usuario_id" => $row['usuario_id'],
                "nombre" => $row['nombre'],
                "descripcion" => $row['descripcion'],
                "cliente" => $row['cliente'],
                "archivo_nombre" => $metadata['archivo_nombre'] ?? $row['nombre'],
                "archivo_ruta" => $row['archivo_url'],
                "archivo_tamaño" => $metadata['archivo_tamaño'] ?? 0,
                "fecha_creacion" => $row['fecha_subida'],
                "visitas" => $row['version'] ?? 0,
                "qr_data" => $row['qr_code'],
                "formato" => $row['formato']
            );

            array_push($planos_arr["records"], $plano_item);
        }

        http_response_code(200);
        echo json_encode($planos_arr);
    } else {
        http_response_code(200);
        echo json_encode(array("records" => array()));
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Error interno del servidor: " . $e->getMessage()));
}
?>