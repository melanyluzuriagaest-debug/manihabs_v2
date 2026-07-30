<?php
/**
 * Manihabs v2 - Cancelación Segura de Pedidos
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/seguridad.php';

checkRol('cliente');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: mis_pedidos.php");
    exit();
}

$token     = $_POST['csrf_token'] ?? '';
$pedido_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$cliente_id = (int)$_SESSION['user_id'];

if (!verifyCSRFToken($token) || !$pedido_id) {
    header("Location: mis_pedidos.php");
    exit();
}

$db = getDBConnection();

// Actualizar pedido a cancelado SOLO si le pertenece al cliente y está en estado pendiente
$stmt = $db->prepare("
    UPDATE pedidos 
    SET estado = 'cancelado' 
    WHERE id = :id AND id_cliente = :cliente_id AND estado = 'pendiente'
");
$stmt->execute([
    'id'         => $pedido_id,
    'cliente_id' => $cliente_id
]);

header("Location: mis_pedidos.php");
exit();