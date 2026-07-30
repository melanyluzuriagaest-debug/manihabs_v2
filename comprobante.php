<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/db.php';

$pedido_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($pedido_id <= 0) {
    die("ID de pedido no válido.");
}

// Obtener datos del pedido y usuario
$stmt = $conn->prepare("SELECT p.*, u.nombre_completo, u.email 
                        FROM pedidos p 
                        LEFT JOIN usuarios u ON p.usuario_id = u.id 
                        WHERE p.id = ?");
$pedido = null;
if ($stmt) {
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();
    $pedido = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$pedido) {
    die("Pedido no encontrado.");
}

// Obtener ítems intentando varias tablas comunes de detalle
$items = [];
$res_items = $conn->query("SELECT dp.*, pr.nombre as nombre_prod 
                          FROM detalle_pedidos dp 
                          LEFT JOIN productos pr ON dp.producto_id = pr.id 
                          WHERE dp.pedido_id = {$pedido_id}");

if (!$res_items || $res_items->num_rows == 0) {
    $res_items = $conn->query("SELECT * FROM detalles_pedido WHERE pedido_id = {$pedido_id}");
}
if (!$res_items || $res_items->num_rows == 0) {
    $res_items = $conn->query("SELECT * FROM pedidos_items WHERE pedido_id = {$pedido_id}");
}

if ($res_items) {
    while ($row = $res_items->fetch_assoc()) {
        $items[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Pedido #<?php echo $pedido_id; ?></title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f8f9fa; padding: 20px; color: #333; }
        .invoice-card { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .invoice-header { display: flex; justify-content: space-between; border-bottom: 3px solid #dc3545; padding-bottom: 20px; margin-bottom: 20px; }
        .brand { font-size: 24px; font-weight: bold; color: #dc3545; }
        .brand span { color: #ffc107; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .table-items { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .table-items th { background: #212529; color: white; padding: 10px; text-align: left; }
        .table-items td { padding: 12px 10px; border-bottom: 1px solid #eee; }
        .total-box { text-align: right; font-size: 18px; font-weight: bold; color: #dc3545; }
        .badge { padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; background: #ffc107; color: #000; }
        .btn-print { background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; cursor: pointer; float: right; }
        @media print { .btn-print { display: none; } body { background: white; padding: 0; } .invoice-card { box-shadow: none; } }
    </style>
</head>
<body>

<div class="invoice-card">
    <button onclick="window.print()" class="btn-print">🖨️ Imprimir / Guardar PDF</button>
    
    <div class="invoice-header">
        <div>
            <div class="brand">MANIHABS <span>v2</span></div>
            <small>Comprobante de Venta y Entrega</small>
        </div>
        <div style="text-align: right;">
            <h3 style="margin: 0; color: #333;">COMPROBANTE</h3>
            <small>N°: <strong><?php echo htmlspecialchars($pedido['numero_pedido'] ?? ('PED-' . $pedido_id)); ?></strong></small><br>
            <small>Fecha: <?php echo htmlspecialchars($pedido['fecha_pedido'] ?? date('Y-m-d')); ?></small>
        </div>
    </div>

    <div class="info-grid">
        <div>
            <strong>Datos del Cliente:</strong><br>
            Nombre: <?php echo htmlspecialchars($pedido['nombre_completo'] ?? 'Cliente'); ?><br>
            Ciudad: <?php echo htmlspecialchars($pedido['ciudad'] ?? 'N/A'); ?><br>
            Dirección: <?php echo htmlspecialchars($pedido['direccion'] ?? 'N/A'); ?>
        </div>
        <div>
            <strong>Estado del Pedido:</strong><br>
            <span class="badge"><?php echo strtoupper(htmlspecialchars($pedido['estado'] ?? 'Pendiente')); ?></span>
        </div>
    </div>

    <h4>Detalle de Productos</h4>
    <table class="table-items">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cant.</th>
                <th>P. Unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $it): 
                    $p_nombre = $it['nombre_prod'] ?? $it['nombre_producto'] ?? $it['producto'] ?? 'Producto de Tienda';
                    $cant = (int)($it['cantidad'] ?? 1);
                    $precio = (float)($it['precio'] ?? $it['precio_unitario'] ?? 0);
                    $sub = $cant * $precio;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($p_nombre); ?></td>
                    <td><?php echo $cant; ?></td>
                    <td>$<?php echo number_format($precio, 2); ?></td>
                    <td>$<?php echo number_format($sub, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td>Producto General / Servicio Manihabs</td>
                    <td>1</td>
                    <td>$<?php echo number_format((float)($pedido['total'] ?? 0), 2); ?></td>
                    <td>$<?php echo number_format((float)($pedido['total'] ?? 0), 2); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="total-box">
        TOTAL A PAGAR / COBRADO: $<?php echo number_format((float)($pedido['total'] ?? 0), 2); ?>
    </div>
</div>

</body>
</html>
