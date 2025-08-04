<?php
// Configurar límites para archivos grandes (200MB)
ini_set('upload_max_filesize', '200M');
ini_set('post_max_size', '200M');
ini_set('max_execution_time', 600);
ini_set('max_input_time', 600);
ini_set('memory_limit', '512M');

require_once '../config/cors.php';
require_once '../config/database.php';
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
    
    // Validar datos requeridos
    if (empty($_POST['cliente'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Cliente es requerido']);
        exit;
    }
    
    // Validar archivo
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        $error_message = 'Error al subir archivo';
        if (isset($_FILES['archivo']['error'])) {
            switch ($_FILES['archivo']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error_message = 'El archivo es demasiado grande (máximo 200MB)';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error_message = 'El archivo se subió parcialmente';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error_message = 'No se seleccionó ningún archivo';
                    break;
                default:
                    $error_message = 'Error desconocido al subir archivo';
            }
        }
        http_response_code(400);
        echo json_encode(['message' => $error_message]);
        exit;
    }
    
    $archivo = $_FILES['archivo'];
    
    // Validar tamaño del archivo (200MB máximo)
    if ($archivo['size'] > 209715200) {
        http_response_code(400);
        echo json_encode(['message' => 'El archivo es demasiado grande. Máximo permitido: 200MB']);
        exit;
    }
    
    // Validar tipo de archivo
    $file_extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'dwg', 'dxf'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        http_response_code(400);
        echo json_encode(['message' => 'Tipo de archivo no permitido. Solo PDF, JPG, PNG, DWG y DXF']);
        exit;
    }
    
    // Crear directorio de uploads si no existe
    $upload_dir = '../../uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generar nombre único para el archivo
    $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $file_path = $upload_dir . $unique_filename;
    
    // Mover archivo subido
    if (!move_uploaded_file($archivo['tmp_name'], $file_path)) {
        http_response_code(500);
        echo json_encode(['message' => 'Error al guardar el archivo']);
        exit;
    }
    
    // Crear conexión a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        http_response_code(500);
        echo json_encode(['message' => 'Error de conexión a la base de datos']);
        exit;
    }
    
    // Primero crear un proyecto para este plano
    $cliente = $_POST['cliente'];
    $descripcion = $_POST['descripcion'] ?? '';
    $nombre_proyecto = $cliente . ' - ' . pathinfo($archivo['name'], PATHINFO_FILENAME);
    
    // Insertar proyecto
    $proyecto_query = "INSERT INTO proyectos (usuario_id, nombre, descripcion, cliente) VALUES (:usuario_id, :nombre, :descripcion, :cliente)";
    $proyecto_stmt = $db->prepare($proyecto_query);
    $proyecto_stmt->bindParam(":usuario_id", $usuario_id);
    $proyecto_stmt->bindParam(":nombre", $nombre_proyecto);
    $proyecto_stmt->bindParam(":descripcion", $descripcion);
    $proyecto_stmt->bindParam(":cliente", $cliente);
    
    if (!$proyecto_stmt->execute()) {
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        http_response_code(500);
        echo json_encode(['message' => 'Error al crear el proyecto']);
        exit;
    }
    
    $proyecto_id = $db->lastInsertId();
    
    // Ahora insertar el plano usando la estructura actual de tu BD
    $nombre_plano = pathinfo($archivo['name'], PATHINFO_FILENAME);
    $archivo_url = 'uploads/' . $unique_filename;
    $software = $file_extension; // Usar la extensión como software
    
    // Generar datos del QR
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    $qr_code = $base_url . '/planos/ver-plano-simple.html?id=';
    
    // Crear metadata JSON con información adicional
    $metadata = json_encode([
        'cliente' => $cliente,
        'archivo_nombre' => $archivo['name'],
        'archivo_tamaño' => $archivo['size'],
        'descripcion' => $descripcion
    ]);
    
    // Insertar plano usando tu estructura actual
    $plano_query = "INSERT INTO planos (proyecto_id, nombre, archivo_url, software, qr_code, metadata) 
                    VALUES (:proyecto_id, :nombre, :archivo_url, :software, :qr_code, :metadata)";
    
    $plano_stmt = $db->prepare($plano_query);
    $plano_stmt->bindParam(":proyecto_id", $proyecto_id);
    $plano_stmt->bindParam(":nombre", $nombre_plano);
    $plano_stmt->bindParam(":archivo_url", $archivo_url);
    $plano_stmt->bindParam(":software", $software);
    $plano_stmt->bindParam(":qr_code", $qr_code);
    $plano_stmt->bindParam(":metadata", $metadata);
    
    if ($plano_stmt->execute()) {
        $plano_id = $db->lastInsertId();
        
        // Actualizar QR code con el ID real
        $qr_code_final = $qr_code . $plano_id;
        $update_query = "UPDATE planos SET qr_code = :qr_code WHERE id = :id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(":qr_code", $qr_code_final);
        $update_stmt->bindParam(":id", $plano_id);
        $update_stmt->execute();
        
        http_response_code(201);
        echo json_encode([
            'message' => 'Plano creado exitosamente',
            'id' => $plano_id,
            'proyecto_id' => $proyecto_id,
            'qr_code' => $qr_code_final
        ]);
    } else {
        // Eliminar archivo y proyecto si falla la creación del plano
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Eliminar proyecto creado
        $delete_proyecto = "DELETE FROM proyectos WHERE id = :id";
        $delete_stmt = $db->prepare($delete_proyecto);
        $delete_stmt->bindParam(":id", $proyecto_id);
        $delete_stmt->execute();
        
        $error_info = $plano_stmt->errorInfo();
        http_response_code(500);
        echo json_encode([
            'message' => 'Error al crear el plano en la base de datos',
            'error' => $error_info[2] ?? 'Error desconocido'
        ]);
    }
    
} catch (Exception $e) {
    // Eliminar archivo si existe y hay error
    if (isset($file_path) && file_exists($file_path)) {
        unlink($file_path);
    }
    
    http_response_code(500);
    echo json_encode([
        'message' => 'Error del servidor',
        'error' => $e->getMessage()
    ]);
}
?>