<?php
/**
 * Manihabs v2 - Visor / Descargador Seguro de Comprobantes
 * Estándar OWASP contra Path Traversal e Inyección de Archivos
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/seguridad.php';

checkAuth();

$pedido_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$user_id   = (int)$_SESSION['user_id'];
$user_rol  = $_SESSION['user_rol'];

if (!$pedido_id) {
    die("Parámetros de consulta no válidos.");
}

$db = getDBConnection();

// Control de Acceso basado en Rol (RBAC)
if ($user_rol === 'cliente') {
    $stmt = $db->prepare("SELECT comprobante_url FROM pedidos WHERE id = :id AND id_cliente = :user_id LIMIT 1");
    $stmt->execute(['id' => $pedido_id, 'user_id' => $user_id]);
} else {
    $stmt = $db->prepare("SELECT comprobante_url FROM pedidos WHERE id = :id AND (id_proveedor = :user_id OR id_proveedor IS NULL) LIMIT 1");
    $stmt->execute(['id' => $pedido_id, 'user_id' => $user_id]);
}

$pedido = $stmt->fetch();

if (!$pedido || empty($pedido['comprobante_url'])) {
    die("El comprobante solicitado no está disponible o no tienes permisos para visualizarlo.");
}

// Prevención de Path Traversal
$relativePath = $pedido['comprobante_url'];
$realBasePath = realpath(__DIR__ . '/uploads/comprobantes/');
$filePath     = realpath(__DIR__ . '/' . $relativePath);

if ($filePath === false || !str_starts_with($filePath, $realBasePath) || !file_exists($filePath)) {
    die("Error de seguridad: El archivo no existe o la ruta es inválida.");
}

// Entrega Segura del Archivo
$mimeType = mime_content_type($filePath);
header("Content-Type: " . $mimeType);
header("Content-Disposition: inline; filename=\"" . basename($filePath) . "\"");
header("Content-Length: " . filesize($filePath));
header("X-Content-Type-Options: nosniff");

readfile($filePath);
exit();