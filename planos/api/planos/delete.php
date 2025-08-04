<?php
require_once '../config/cors.php';
require_once '../config/database.php';
require_once '../utils/jwt.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
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
    
    $usuario_id = $user_data['id']; // Corregido: usar 'id' en lugar de 'user_id'
    
    // Obtener datos del DELETE
    $data = json_decode(file_get_contents("php://input"));
    
    if (empty($data->id)) {
        http_response_code(400);
        echo json_encode(['message' => 'ID del plano requerido']);
        exit;
    }
    
    // Crear conexión a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        http_response_code(500);
        echo json_encode(['message' => 'Error de conexión a la base de datos']);
        exit;
    }
    
    // Verificar que el plano pertenece al usuario
    $query = "SELECT id, archivo_url, metadata 
              FROM planos 
              WHERE id = :plano_id AND usuario_id = :usuario_id";
     
    $stmt = $db->prepare($query);
    $stmt->bindParam(":plano_id", $data->id);
    $stmt->bindParam(":usuario_id", $usuario_id);
    $stmt->execute();
    
    $plano = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plano) {
        http_response_code(404);
        echo json_encode(['message' => 'Plano no encontrado o sin permisos']);
        exit;
    }
    
    // Eliminar archivo físico si existe
    if (!empty($plano['archivo_url'])) {
        $archivo_path = "../../" . $plano['archivo_url'];
        if (file_exists($archivo_path)) {
            if (!unlink($archivo_path)) {
                error_log("No se pudo eliminar el archivo: " . $archivo_path);
            }
        }
    }
    
    // Eliminar el plano de la base de datos
    $delete_query = "DELETE FROM planos WHERE id = :id AND usuario_id = :usuario_id";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->bindParam(":id", $data->id);
    $delete_stmt->bindParam(":usuario_id", $usuario_id);
    
    if ($delete_stmt->execute()) {
        if ($delete_stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Plano eliminado exitosamente'
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'No se encontró el plano para eliminar'
            ]);
        }
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al eliminar el plano de la base de datos'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error eliminando plano: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>