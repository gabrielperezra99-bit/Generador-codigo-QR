<?php
// Configuración de entorno
define('ENVIRONMENT', 'development'); // Cambiado de 'production' a 'development'

if (ENVIRONMENT === 'production') {
    // Configuración para Hostinger
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'tu_nombre_base_datos');
    define('DB_USER', 'tu_usuario_db');
    define('DB_PASS', 'tu_password_db');
    define('BASE_URL', 'https://tu-dominio.com');
} else {
    // Configuración para desarrollo local
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'planos_qr');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('BASE_URL', 'http://localhost/planos');
}
?>