<?php
// Configuración de entorno
define('ENVIRONMENT', 'production'); // ✅ Mantener en production

if (ENVIRONMENT === 'production') {
    // Configuración para Hostinger
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'u617265898_planos_qr');
    define('DB_USER', 'u617265898_gabriel');
    define('DB_PASS', 'TU_PASSWORD_REAL'); // ⚠️ Pon tu contraseña real aquí
    define('BASE_URL', 'https://qr.kodeongg.com'); // ✅ Correcto
} else {
    // Configuración para desarrollo local
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'planos_qr');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('BASE_URL', 'http://localhost/planos'); // ⚠️ Esta línea no se ejecuta en production
}
?>