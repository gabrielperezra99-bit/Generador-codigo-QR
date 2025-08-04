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
    
    // Obtener planos del usuario usando la estructura actual
    $query = "SELECT p.id, p.nombre, p.archivo_url, p.software, p.qr_code, p.metadata, p.fecha_subida,
                     pr.nombre as proyecto_nombre, pr.cliente, pr.descripcion as proyecto_descripcion
              FROM planos p 
              INNER JOIN proyectos pr ON p.proyecto_id = pr.id 
              WHERE pr.usuario_id = :usuario_id 
              ORDER BY p.fecha_subida DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":usuario_id", $usuario_id);
    $stmt->execute();
    
    $planos = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $metadata = json_decode($row['metadata'], true) ?? [];
        
        $plano_item = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'cliente' => $row['cliente'],
            'descripcion' => $row['proyecto_descripcion'],
            'archivo_nombre' => $metadata['archivo_nombre'] ?? $row['nombre'],
            'archivo_ruta' => $row['archivo_url'],
            'archivo_tamaño' => $metadata['archivo_tamaño'] ?? 0,
            'fecha_creacion' => $row['fecha_subida'],
            'qr_data' => $row['qr_code'],
            'proyecto_nombre' => $row['proyecto_nombre'],
            'software' => $row['software']
        ];
        
        $planos[] = $plano_item;
    }
    
    echo json_encode($planos);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>