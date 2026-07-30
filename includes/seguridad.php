<?php
/**
 * Manihabs v2 - Control Centralizado de Seguridad y Sesiones
 * OWASP Authentication & Authorization Guard
 */

declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';

/**
 * Verifica si el usuario inició sesión
 */
function checkAuth(): void {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
}

/**
 * Control del Rol Requerido
 */
function checkRol(string $rolRequerido): void {
    checkAuth();
    if (($_SESSION['user_rol'] ?? '') !== $rolRequerido) {
        // Si intenta entrar a un área que no es de su rol, lo redirigimos a su dashboard
        if (($_SESSION['user_rol'] ?? '') === 'proveedor') {
            header("Location: dashboard_proveedor.php");
        } else {
            header("Location: dashboard_cliente.php");
        }
        exit();
    }
}