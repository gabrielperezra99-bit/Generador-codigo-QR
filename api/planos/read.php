<?php
require_once '../config/cors.php';
require_once '../config/database.php';
require_once '../utils/jwt.php';

header('Content-Type: application/json');

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
    
    // Obtener planos del usuario con todos los campos necesarios
    $query = "SELECT id, nombre, cliente, descripcion, archivo_url, imagen_preview, formato, qr_code, metadata, 
                     es_favorito, fecha_favorito, estado, progreso_porcentaje, etiquetas, tiempo_estimado, 
                     visitas, fecha_subida, fecha_creacion, fecha_actualizacion
              FROM planos 
              WHERE usuario_id = :usuario_id 
              ORDER BY es_favorito DESC, fecha_favorito DESC, fecha_creacion DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":usuario_id", $usuario_id);
    $stmt->execute();
    
    $planos = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $metadata = json_decode($row['metadata'], true) ?? [];
        
        $plano = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'cliente' => $row['cliente'],
            'descripcion' => $row['descripcion'],
            'archivo_nombre' => $metadata['archivo_nombre'] ?? $row['nombre'],
            'archivo_url' => $row['archivo_url'],
            'imagen_preview' => $row['imagen_preview'],
            'archivo_tamaño' => $metadata['archivo_tamaño'] ?? 0,
            'fecha_creacion' => $row['fecha_creacion'],
            'fecha_subida' => $row['fecha_subida'],
            'fecha_actualizacion' => $row['fecha_actualizacion'],
            'qr_code' => $row['qr_code'],
            'formato' => $row['formato'],
            'visitas' => intval($row['visitas'] ?? 0),
            'favorito' => intval($row['es_favorito']) === 1,
            'es_favorito' => intval($row['es_favorito']),
            'fecha_favorito' => $row['fecha_favorito'],
            'estado' => $row['estado'],
            'progreso_porcentaje' => intval($row['progreso_porcentaje'] ?? 0),
            'etiquetas' => $row['etiquetas'],
            'tiempo_estimado' => $row['tiempo_estimado']
        ];
        
        $planos[] = $plano;
    }
    
    echo json_encode($planos);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>