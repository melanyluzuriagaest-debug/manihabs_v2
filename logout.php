<?php
/**
 * Manihabs v2 - Cierre de Sesión Seguro
 * Estándar OWASP Session Invalidation
 */

declare(strict_types=1);
require_once __DIR__ . '/config/db.php';

// 1. Limpiar variables de sesión
$_SESSION = [];

// 2. Destruir cookie de sesión en el cliente
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Destruir la sesión en el servidor
session_destroy();

// 4. Redirigir al Login
header("Location: index.php");
exit();