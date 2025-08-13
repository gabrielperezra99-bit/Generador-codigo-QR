<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$result = [];

// Test 1: Basic PHP
$result['php_version'] = phpversion();
$result['server_name'] = $_SERVER['SERVER_NAME'] ?? 'unknown';

// Test 2: Config loading
try {
    require_once '../config/config.php';
    $result['config'] = [
        'status' => 'OK',
        'environment' => ENVIRONMENT,
        'db_host' => DB_HOST,
        'db_name' => DB_NAME,
        'base_url' => BASE_URL
    ];
} catch (Exception $e) {
    $result['config'] = [
        'status' => 'ERROR',
        'message' => $e->getMessage()
    ];
}

// Test 3: Database connection
try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    $result['database'] = ['status' => 'OK'];
} catch (Exception $e) {
    $result['database'] = [
        'status' => 'ERROR',
        'message' => $e->getMessage()
    ];
}

// Test 4: JWT library
try {
    require_once '../../vendor/autoload.php';
    $result['composer'] = ['status' => 'OK'];
} catch (Exception $e) {
    $result['composer'] = [
        'status' => 'ERROR',
        'message' => $e->getMessage()
    ];
}

// Test 5: Uploads directory
if (is_dir('../../uploads') && is_writable('../../uploads')) {
    $result['uploads'] = ['status' => 'OK'];
} else {
    $result['uploads'] = ['status' => 'ERROR', 'message' => 'Directory not writable'];
}

echo json_encode($result, JSON_PRETTY_PRINT);
?>