<?php
require_once '../config/cors.php';
require_once '../config/database.php';
require_once '../models/Plano.php';
require_once '../utils/jwt.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Método no permitido']);
    exit;
}

try {
    // Verificar token JWT
    $jwt = new JWTHandler();
    $token = $jwt->getTokenFromHeader();
    
    if (!$token) {
        http_response_code(401);
        echo json_encode(['message' => 'Token de autorización requerido']);
        exit;
    }
    
    $user_data = $jwt->validateToken($token);
    if (!$user_data) {
        http_response_code(401);
        echo json_encode(['message' => 'Token inválido']);
        exit;
    }
    
    $usuario_id = $user_data['id'];
    
    // Crear conexión a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    $plano = new Plano($db);
    
    // Obtener datos
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->plano_id)) {
        $plano->id = $data->plano_id;
        $plano->usuario_id = $usuario_id;
        
        // Primero obtener el estado actual
        if ($plano->readOne()) {
            $plano->es_favorito = $plano->es_favorito ? 0 : 1; // Toggle
            
            if ($plano->toggleFavorito()) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Favorito actualizado correctamente',
                    'favorito' => $plano->es_favorito
                ]);
            } else {
                http_response_code(503);
                echo json_encode([
                    'success' => false,
                    'message' => 'No se pudo actualizar el favorito'
                ]);
            }
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Plano no encontrado'
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID de plano requerido'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>