<?php
// Configurar límites para archivos grandes (200MB)
ini_set('upload_max_filesize', '200M');
ini_set('post_max_size', '200M');
ini_set('max_execution_time', 600);
ini_set('max_input_time', 600);
ini_set('memory_limit', '512M');

require_once '../config/config.php'; // Agregar esta línea
require_once '../config/cors.php';
require_once '../config/database.php';
require_once '../utils/jwt.php';
require_once '../utils/dwg_converter.php';

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
    
    // Después de mover el archivo subido
    if (!move_uploaded_file($archivo['tmp_name'], $file_path)) {
        http_response_code(500);
        echo json_encode(['message' => 'Error al guardar el archivo']);
        exit;
    }
    
    // Generar previsualización para archivos DWG
    $preview_url = null;
    if (strtolower($file_extension) === 'dwg') {
        $converter = new DWGConverter();
        $preview_filename = pathinfo($unique_filename, PATHINFO_FILENAME) . '_preview.jpg';
        $preview_path = $upload_dir . $preview_filename;
        
        if ($converter->convertDWGToImage($file_path, $preview_path)) {
            // Generar thumbnail más pequeño
            $thumbnail_filename = pathinfo($unique_filename, PATHINFO_FILENAME) . '_thumb.jpg';
            $thumbnail_path = $upload_dir . $thumbnail_filename;
            
            if ($converter->generateThumbnail($preview_path, $thumbnail_path)) {
                $preview_url = 'uploads/' . $thumbnail_filename;
            }
        }
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
    
    // Preparar datos para insertar directamente en planos
    // Obtener datos del formulario
    $nombre_plano = $_POST['nombre'] ?? '';
    $cliente = $_POST['cliente'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $empresa = $_POST['empresa'] ?? 'FREMAQ'; // Nuevo campo
    $archivo_url = 'uploads/' . $unique_filename;
    $formato = $file_extension; // Usar la extensión como formato
    
    // Generar datos del QR con configuración dinámica
    $base_url = BASE_URL; // Usar la constante en lugar de hardcodear
    $qr_code = $base_url . '/ver-plano-simple.html?id=';
    
    // Remover /api de la ruta si existe
    $request_uri = $_SERVER['REQUEST_URI'];
    $base_path = str_replace('/api/planos/create.php', '', $request_uri);
    
    $qr_code = $base_url . $base_path . '/ver-plano-simple.html?id=';
    
    // Crear metadata JSON con información adicional
    $metadata_json = json_encode([
        'archivo_nombre' => $archivo['name'],
        'archivo_tamaño' => $archivo['size']
    ]);
    
    // Insertar plano directamente con la nueva estructura
    $plano_query = "INSERT INTO planos (usuario_id, nombre, cliente, descripcion, archivo_url, formato, qr_code, metadata, empresa)
                    VALUES (:usuario_id, :nombre, :cliente, :descripcion, :archivo_url, :formato, :qr_code, :metadata, :empresa)";
    
    $plano_stmt = $db->prepare($plano_query);
    $plano_stmt->bindParam(":usuario_id", $usuario_id);
    $plano_stmt->bindParam(":nombre", $nombre_plano);
    $plano_stmt->bindParam(":cliente", $cliente);
    $plano_stmt->bindParam(":descripcion", $descripcion);
    $plano_stmt->bindParam(":archivo_url", $archivo_url);
    $plano_stmt->bindParam(":formato", $formato);
    $plano_stmt->bindParam(":qr_code", $qr_code);
    $plano_stmt->bindParam(":metadata", $metadata_json);
    $plano_stmt->bindParam(":empresa", $empresa);
    
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
            'qr_code' => $qr_code_final
        ]);
    } else {
        // Eliminar archivo si falla la creación del plano
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
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
