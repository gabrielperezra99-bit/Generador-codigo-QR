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
    
    // Obtener plano específico usando tu estructura actual
    $query = "SELECT p.id, p.nombre, p.archivo_url, p.software, p.qr_code, p.metadata, p.fecha_subida,
                     pr.nombre as proyecto_nombre, pr.cliente, pr.descripcion as proyecto_descripcion
              FROM planos p 
              INNER JOIN proyectos pr ON p.proyecto_id = pr.id 
              WHERE p.id = :plano_id";
    
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
    $update_query = "UPDATE planos SET version = COALESCE(version, '0') + 1 WHERE id = :plano_id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(":plano_id", $plano_id);
    $update_stmt->execute();
    
    $metadata = json_decode($row['metadata'], true) ?? [];
    
    $plano = [
        'id' => $row['id'],
        'nombre' => $row['nombre'],
        'cliente' => $row['cliente'],
        'descripcion' => $row['proyecto_descripcion'],
        'archivo_nombre' => $metadata['archivo_nombre'] ?? $row['nombre'],
        'archivo_url' => $row['archivo_url'],
        'archivo_tamaño' => $metadata['archivo_tamaño'] ?? 0,
        'fecha_creacion' => $row['fecha_subida'],
        'qr_code' => $row['qr_code'],
        'proyecto_nombre' => $row['proyecto_nombre'],
        'software' => $row['software'],
        'visitas' => $row['version'] ?? 0
    ];
    
    echo json_encode($plano);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>