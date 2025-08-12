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
    
    if (!empty($data->id)) {
        $subtarea->id = $data->id;
        $subtarea->usuario_id = $usuario_id;
        
        if (isset($data->completada)) {
            $subtarea->estado = $data->completada ? 'completada' : 'pendiente';
        }
        
        if (isset($data->titulo)) {
            $subtarea->titulo = $data->titulo;
        }
        
        if (isset($data->descripcion)) {
            $subtarea->descripcion = $data->descripcion;
        }
        
        if (isset($data->prioridad)) {
            $subtarea->prioridad = $data->prioridad;
        }
        
        if (isset($data->fecha_vencimiento)) {
            $subtarea->fecha_vencimiento = $data->fecha_vencimiento;
        }
        
        if (isset($data->asignado_a)) {
            $subtarea->asignado_a = $data->asignado_a;
        }
        
        if (isset($data->tiempo_estimado)) {
            $subtarea->tiempo_estimado = $data->tiempo_estimado;
        }
        
        if (isset($data->notas_internas)) {
            $subtarea->notas_internas = $data->notas_internas;
        }

        if ($subtarea->update()) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Subtarea actualizada correctamente'
            ]);
        } else {
            http_response_code(503);
            echo json_encode(['success' => false, 'message' => 'No se pudo actualizar la subtarea']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de subtarea requerido']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>