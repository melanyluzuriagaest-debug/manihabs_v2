<?php
/**
 * Manihabs v2 - Header Global
 */

declare(strict_types=1);
require_once __DIR__ . '/seguridad.php';

// Validar que exista sesión
checkAuth();

$userName = sanitize($_SESSION['user_nombre'] ?? 'Usuario');
$userRol  = sanitize($_SESSION['user_rol'] ?? 'cliente');
$avatarInitial = strtoupper(substr($userName, 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manihabs v2 - Panel de Control</title>
    <!-- Estilos Principales -->
    <link rel="stylesheet" href="css/estilo.css">
    <!-- FontAwesome para Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div id="app-container">