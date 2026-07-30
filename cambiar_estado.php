<?php
// Depuración activa temporal
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);
session_start();

require_once 'config/db.php';

// Validar que la petición sea por método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acceso no permitido.");
}

// Verificación de autenticación
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'proveedor') {
    die("Error: No tienes permisos para realizar esta acción.");
}

// Captura de datos del formulario
$pedido_id = isset($_POST['pedido_id']) ? (int)$_POST['pedido_id'] : 0;
$nuevo_estado = $_POST['nuevo_estado'] ?? '';
$fecha_entrega = !empty($_POST['fecha_entrega']) ? $_POST['fecha_entrega'] : null;

$estados_validos = ['pendiente', 'enviado', 'entregado', 'pagado', 'cancelado'];

if ($pedido_id <= 0 || !in_array($nuevo_estado, $estados_validos)) {
    die("Error: Estado o ID de pedido no válido.");
}

$conn->begin_transaction();

try {
    // Actualización de estado y fecha
    if (!empty($fecha_entrega)) {
        $stmt = $conn->prepare("UPDATE pedidos SET estado = ?, fecha_entrega = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nuevo_estado, $fecha_entrega, $pedido_id);
    } else {
        $stmt = $conn->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevo_estado, $pedido_id);
    }

    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar pedido: " . $stmt->error);
    }
    $stmt->close();

    // Si se cancela el pedido, reponer stock
    if ($nuevo_estado === 'cancelado') {
        $stmt_det = $conn->prepare("SELECT producto_id, cantidad FROM detalles_pedido WHERE pedido_id = ?");
        $stmt_det->bind_param("i", $pedido_id);
        $stmt_det->execute();
        $detalles = $stmt_det->get_result();

        while ($d = $detalles->fetch_assoc()) {
            $stmt_stock = $conn->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
            $stmt_stock->bind_param("ii", $d['cantidad'], $d['producto_id']);
            if (!$stmt_stock->execute()) {
                throw new Exception("Error al actualizar stock: " . $stmt_stock->error);
            }
            $stmt_stock->close();
        }
        $stmt_det->close();
    }

    $conn->commit();

    // Redirección exitosa
    header('Location: detalle_pedido_proveedor.php?id=' . $pedido_id);
    exit();

} catch (Exception $e) {
    $conn->rollback();
    die("Error durante la transacción: " . $e->getMessage());
}