<?php
require_once '../config/cors.php';
require_once '../config/database.php';
require_once '../models/Subtarea.php';
require_once '../utils/jwt.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Verificar token JWT
    $jwt = new JWTHandler();
    $token = $jwt->getTokenFromHeader();
    
    if (!$token) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token de autorización requerido']);
        exit;
    }
    
    $user_data = $jwt->validateToken($token);
    if (!$user_data) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token inválido']);
        exit;
    }
    
    $usuario_id = $user_data['id'];
    
    // Crear conexión a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    $subtarea = new Subtarea($db);
    
    // Obtener datos
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->plano_id) && !empty($data->titulo)) {
        $subtarea->plano_id = $data->plano_id;
        $subtarea->usuario_id = $usuario_id;
        $subtarea->titulo = $data->titulo;
        $subtarea->descripcion = $data->descripcion ?? '';
        $subtarea->estado = $data->estado ?? 'pendiente';
        $subtarea->prioridad = $data->prioridad ?? 'media';
        $subtarea->fecha_vencimiento = $data->fecha_vencimiento ?? null;
        $subtarea->asignado_a = $data->asignado_a ?? '';
        $subtarea->tiempo_estimado = $data->tiempo_estimado ?? null;
        $subtarea->notas_internas = $data->notas_internas ?? '';

        if ($subtarea->create()) {
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Subtarea creada correctamente',
                'id' => $subtarea->id
            ]);
        } else {
            http_response_code(503);
            echo json_encode(['success' => false, 'message' => 'No se pudo crear la subtarea']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos incompletos. Se requiere plano_id y titulo']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>