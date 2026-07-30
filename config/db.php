<?php
// Reporte de errores
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Configuración de la base de datos
$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = '123456'; // Ajusta la clave si en tu MariaDB es diferente o vacía ''
$db_name = 'manihabs_db';

mysqli_report(MYSQLI_REPORT_OFF);

// Crear la conexión centralizada
$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    $conn = @new mysqli('localhost', $db_user, $db_pass, $db_name);
}

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