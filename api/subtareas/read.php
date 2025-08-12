<?php
require_once '../config/cors.php';
require_once '../config/database.php';
require_once '../models/Subtarea.php';
require_once '../utils/jwt.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
    
    $subtarea = new Subtarea($db);
    
    $plano_id = isset($_GET['plano_id']) ? $_GET['plano_id'] : '';

    if (!empty($plano_id)) {
        $subtarea->plano_id = $plano_id;
        $subtarea->usuario_id = $usuario_id;
        
        $stmt = $subtarea->readByPlano();
        $num = $stmt->rowCount();

        if ($num > 0) {
            $subtareas_arr = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $subtarea_item = [
                    'id' => $row['id'],
                    'plano_id' => $row['plano_id'],
                    'titulo' => $row['titulo'],
                    'descripcion' => $row['descripcion'],
                    'estado' => $row['estado'],
                    'completada' => $row['estado'] === 'completada', // Add this for compatibility
                    'prioridad' => $row['prioridad'],
                    'fecha_vencimiento' => $row['fecha_vencimiento'],
                    'asignado_a' => $row['asignado_a'],
                    'tiempo_estimado' => $row['tiempo_estimado'],
                    'notas_internas' => $row['notas_internas'],
                    'fecha_creacion' => $row['fecha_creacion'],
                    'fecha_actualizacion' => $row['fecha_actualizacion']
                ];

                $subtareas_arr[] = $subtarea_item;
            }

            http_response_code(200);
            echo json_encode($subtareas_arr);
        } else {
            http_response_code(200);
            echo json_encode([]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'ID de plano requerido']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>