<?php
header('Content-Type: text/plain');
echo "=== DIAGNÓSTICO BÁSICO ===\n";
echo "Servidor: " . $_SERVER['HTTP_HOST'] . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Directorio actual: " . __DIR__ . "\n";
echo "Archivos en directorio actual:\n";
$files = scandir('.');
foreach($files as $file) {
    if($file != '.' && $file != '..') {
        echo "- $file\n";
    }
}

echo "\n=== PRUEBA DE CONFIG ===\n";
if(file_exists('api/config/config.php')) {
    echo "config.php existe\n";
    try {
        require_once 'api/config/config.php';
        echo "Config cargado: OK\n";
        echo "Environment: " . ENVIRONMENT . "\n";
    } catch(Exception $e) {
        echo "Error en config: " . $e->getMessage() . "\n";
    }
} else {
    echo "config.php NO EXISTE\n";
}
?>