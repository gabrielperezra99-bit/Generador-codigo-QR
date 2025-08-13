<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Diagnóstico del Sistema</h2>";

// 1. Verificar configuración
echo "<h3>1. Verificando configuración...</h3>";
try {
    require_once 'api/config/config.php';
    echo "✅ Config cargado correctamente<br>";
    echo "Environment: " . ENVIRONMENT . "<br>";
    echo "Base URL: " . BASE_URL . "<br>";
    echo "DB Host: " . DB_HOST . "<br>";
    echo "DB Name: " . DB_NAME . "<br>";
    echo "DB User: " . DB_USER . "<br>";
} catch (Exception $e) {
    echo "❌ Error en config: " . $e->getMessage() . "<br>";
}

// 2. Verificar conexión a base de datos
echo "<h3>2. Verificando conexión a base de datos...</h3>";
try {
    require_once 'api/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "✅ Conexión a BD exitosa<br>";
        
        // Verificar tablas
        $tables = $db->query("SHOW TABLES")->fetchAll();
        echo "📋 Tablas encontradas: " . count($tables) . "<br>";
        foreach ($tables as $table) {
            echo "- " . $table[0] . "<br>";
        }
    } else {
        echo "❌ Error de conexión a BD<br>";
    }
} catch (Exception $e) {
    echo "❌ Error BD: " . $e->getMessage() . "<br>";
}

// 3. Verificar JWT
echo "<h3>3. Verificando JWT...</h3>";
try {
    require_once 'api/utils/jwt.php';
    $jwt = new JWTHandler();
    echo "✅ JWT cargado correctamente<br>";
} catch (Exception $e) {
    echo "❌ Error JWT: " . $e->getMessage() . "<br>";
}

// 4. Verificar permisos de uploads
echo "<h3>4. Verificando permisos...</h3>";
$upload_dir = 'uploads/';
if (is_dir($upload_dir)) {
    echo "✅ Directorio uploads existe<br>";
    if (is_writable($upload_dir)) {
        echo "✅ Directorio uploads es escribible<br>";
    } else {
        echo "❌ Directorio uploads NO es escribible<br>";
    }
} else {
    echo "❌ Directorio uploads NO existe<br>";
}

echo "<h3>5. Información del servidor:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "Post Max Size: " . ini_get('post_max_size') . "<br>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
?>

<?php
header('Content-Type: text/plain');
echo "=== DIAGNOSTIC TEST ===\n\n";

// Test 1: Basic PHP
echo "1. PHP Version: " . phpversion() . "\n";

// Test 2: Config loading
try {
    require_once 'api/config/config.php';
    echo "2. Config loaded: OK\n";
    echo "   - Environment: " . ENVIRONMENT . "\n";
    echo "   - DB_HOST: " . DB_HOST . "\n";
    echo "   - DB_NAME: " . DB_NAME . "\n";
    echo "   - BASE_URL: " . BASE_URL . "\n";
} catch (Exception $e) {
    echo "2. Config error: " . $e->getMessage() . "\n";
}

// Test 3: Database connection
try {
    require_once 'api/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "3. Database connection: OK\n";
} catch (Exception $e) {
    echo "3. Database error: " . $e->getMessage() . "\n";
}

// Test 4: JWT library
try {
    require_once 'vendor/autoload.php';
    echo "4. Composer autoload: OK\n";
} catch (Exception $e) {
    echo "4. Composer error: " . $e->getMessage() . "\n";
}

// Test 5: Uploads directory
if (is_dir('uploads') && is_writable('uploads')) {
    echo "5. Uploads directory: OK\n";
} else {
    echo "5. Uploads directory: ERROR (not writable)\n";
}

echo "\n=== END DIAGNOSTIC ===\n";
?>