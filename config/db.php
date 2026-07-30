<?php
// Reporte de errores
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Configuración de la base de datos usando variables de entorno
// Si estás en Render, usará lo configurado en la plataforma. 
// Si estás en local, usará tus datos por defecto.
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '123456'; 
$db_name = getenv('DB_NAME') ?: 'manihabs_db';

mysqli_report(MYSQLI_REPORT_OFF);

// Crear la conexión centralizada
$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);

// Eliminamos el bloque que intentaba reconectar a 'localhost' 
// porque es el que genera el error "No such file or directory" en Render.
if ($conn->connect_error) {
    die("Error de conexión a la Base de Datos: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Función Helper para los archivos que la requieran
if (!function_exists('getDBConnection')) {
    function getDBConnection() {
        global $conn;
        return $conn;
    }
}
?>
