<?php
require_once '../config/cors.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['message' => 'Método no permitido']);
    exit;
}

try {
    // Obtener ID del plano
    $plano_id = $_GET['id'] ?? null;
    
    if (!$plano_id) {
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
    
    // Obtener plano específico directamente de la tabla planos
    $query = "SELECT id, nombre, cliente, descripcion, archivo_url, formato, qr_code, metadata, version, fecha_subida
              FROM planos 
              WHERE id = :plano_id";
     
    $stmt = $db->prepare($query);
    $stmt->bindParam(":plano_id", $plano_id);
    $stmt->execute();
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        http_response_code(404);
        echo json_encode(['message' => 'Plano no encontrado']);
        exit;
    }
    
    // Incrementar visitas
    $current_version = intval($row['version'] ?? 0);
    $new_version = $current_version + 1;
    $update_query = "UPDATE planos SET version = :version WHERE id = :plano_id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(":version", $new_version);
    $update_stmt->bindParam(":plano_id", $plano_id);
    $update_stmt->execute();
    
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
        'visitas' => $new_version
    ];
    
    echo json_encode($plano);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>