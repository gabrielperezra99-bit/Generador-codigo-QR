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
    
    // Obtener planos del usuario directamente de la tabla planos
    $query = "SELECT id, nombre, cliente, descripcion, archivo_url, formato, qr_code, metadata, version, fecha_subida
              FROM planos 
              WHERE usuario_id = :usuario_id 
              ORDER BY fecha_subida DESC";
    
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
            'archivo_tamaño' => $metadata['archivo_tamaño'] ?? 0,
            'fecha_creacion' => $row['fecha_subida'],
            'qr_code' => $row['qr_code'],
            'formato' => $row['formato'],
            'visitas' => intval($row['version'] ?? 0)
        ];
        
        $planos[] = $plano; // Corregido: era $plano_item, ahora es $plano
    }
    
    echo json_encode($planos);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>