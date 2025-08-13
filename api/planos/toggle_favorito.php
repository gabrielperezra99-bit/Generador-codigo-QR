<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../utils/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar token JWT
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token no proporcionado']);
    exit;
}

$token = str_replace('Bearer ', '', $headers['Authorization']);
$jwt = new JWTHandler();
$decoded = $jwt->validateToken($token);

if (!$decoded) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token inválido']);
    exit;
}

$usuario_id = $decoded['id'];

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['plano_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID del plano requerido']);
    exit;
}

$plano_id = $input['plano_id'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar si las columnas existen, si no, crearlas una por una
    $check_favorito = "SHOW COLUMNS FROM planos LIKE 'favorito'";
    $stmt_check_fav = $db->prepare($check_favorito);
    $stmt_check_fav->execute();
    
    if ($stmt_check_fav->rowCount() === 0) {
        $alter_favorito = "ALTER TABLE planos ADD COLUMN favorito BOOLEAN DEFAULT FALSE";
        $db->exec($alter_favorito);
    }
    
    $check_fecha = "SHOW COLUMNS FROM planos LIKE 'fecha_favorito'";
    $stmt_check_fecha = $db->prepare($check_fecha);
    $stmt_check_fecha->execute();
    
    if ($stmt_check_fecha->rowCount() === 0) {
        $alter_fecha = "ALTER TABLE planos ADD COLUMN fecha_favorito TIMESTAMP NULL";
        $db->exec($alter_fecha);
    }
    
    // Verificar que el plano pertenece al usuario y obtener estado actual
    $query = "SELECT id, COALESCE(favorito, 0) as favorito FROM planos WHERE id = :plano_id AND usuario_id = :usuario_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':plano_id', $plano_id, PDO::PARAM_INT);
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Plano no encontrado']);
        exit;
    }
    
    $plano = $stmt->fetch(PDO::FETCH_ASSOC);
    $nuevo_favorito = !$plano['favorito'];
    
    // Actualizar estado de favorito
    $update_query = "UPDATE planos SET favorito = :favorito, fecha_favorito = :fecha_favorito WHERE id = :plano_id AND usuario_id = :usuario_id";
    $update_stmt = $db->prepare($update_query);
    $fecha_favorito = $nuevo_favorito ? date('Y-m-d H:i:s') : null;
    
    $update_stmt->bindParam(':favorito', $nuevo_favorito, PDO::PARAM_BOOL);
    $update_stmt->bindParam(':fecha_favorito', $fecha_favorito);
    $update_stmt->bindParam(':plano_id', $plano_id, PDO::PARAM_INT);
    $update_stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    
    if ($update_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'favorito' => $nuevo_favorito,
            'message' => $nuevo_favorito ? 'Agregado a favoritos' : 'Removido de favoritos'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al actualizar favorito']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage(),
        'error_details' => $e->getTraceAsString()
    ]);
}
?>