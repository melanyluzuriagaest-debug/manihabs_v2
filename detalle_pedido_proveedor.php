<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
    }
}

require_once 'config/db.php';

$pedido_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($pedido_id <= 0) {
    header("Location: dashboard_proveedor.php");
    exit();
}

// Actualizar estado si se envía el formulario
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado'])) {
    $nuevo_estado = sanitize($_POST['nuevo_estado']);
    $stmt_up = $conn->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
    if ($stmt_up) {
        $stmt_up->bind_param("si", $nuevo_estado, $pedido_id);
        if ($stmt_up->execute()) {
            $mensaje = "<div class='alert alert-success'>¡Estado actualizado a '{$nuevo_estado}' correctamente!</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al actualizar el estado.</div>";
        }
        $stmt_up->close();
    }
}

// Obtener datos del pedido
$pedido = null;
$res_pedido = $conn->query("SELECT p.*, u.nombre_completo FROM pedidos p LEFT JOIN usuarios u ON p.usuario_id = u.id WHERE p.id = {$pedido_id}");

if ($res_pedido && $res_pedido->num_rows > 0) {
    $pedido = $res_pedido->fetch_assoc();
} else {
    die("<div style='padding:40px; font-family:sans-serif; text-align:center;'><h2>Pedido #{$pedido_id} no encontrado.</h2><p><a href='dashboard_proveedor.php'>Volver al Panel</a></p></div>");
}

// CONSULTA ROBUSTA DE PRODUCTOS (Revisa múltiples nombres de tablas probables)
$items = [];
$queries = [
    "SELECT dp.*, pr.nombre as nombre_prod FROM detalle_pedidos dp LEFT JOIN productos pr ON dp.producto_id = pr.id WHERE dp.pedido_id = {$pedido_id}",
    "SELECT dp.*, pr.nombre as nombre_prod FROM detalles_pedido dp LEFT JOIN productos pr ON dp.producto_id = pr.id WHERE dp.pedido_id = {$pedido_id}",
    "SELECT * FROM pedidos_items WHERE pedido_id = {$pedido_id}",
    "SELECT * FROM detalle_pedidos WHERE pedido_id = {$pedido_id}",
    "SELECT * FROM detalles_pedido WHERE pedido_id = {$pedido_id}"
];

foreach ($queries as $q) {
    $res_i = @$conn->query($q);
    if ($res_i && $res_i->num_rows > 0) {
        while ($row = $res_i->fetch_assoc()) {
            $items[] = $row;
        }
        break; // Detenerse en la primera tabla que arroje resultados
    }
}

$titulo_pagina = "Detalle de Pedido #" . $pedido_id;
if (file_exists('includes/header.php')) {
    include 'includes/header.php';
}
?>

<style>
    :root {
        --red-primary: #dc3545;
        --red-dark: #b02a37;
        --yellow-primary: #ffc107;
        --yellow-dark: #d39e00;
        --dark-accent: #212529;
        --bg-body: #f4f6f9;
    }

    body {
        background-color: var(--bg-body);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .detail-container {
        max-width: 1100px;
        margin: 30px auto;
        padding: 0 20px;
    }

    .detail-header {
        background: linear-gradient(135deg, var(--red-dark), var(--red-primary));
        color: white;
        padding: 20px 25px;
        border-radius: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
        margin-bottom: 25px;
    }

    .detail-header h2 { margin: 0; font-size: 1.5rem; font-weight: 700; }

    .header-btns {
        display: flex;
        gap: 10px;
    }

    .btn-back {
        background-color: var(--yellow-primary);
        color: #000;
        padding: 8px 16px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: bold;
        font-size: 0.9rem;
    }
    .btn-back:hover { background-color: var(--yellow-dark); }

    .btn-invoice {
        background-color: #ffffff;
        color: var(--red-primary);
        padding: 8px 16px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: bold;
        font-size: 0.9rem;
        border: 1px solid #fff;
    }
    .btn-invoice:hover { background-color: #f8f9fa; }

    .card {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        margin-bottom: 25px;
    }

    .card-title {
        margin-top: 0;
        margin-bottom: 20px;
        color: var(--dark-accent);
        font-size: 1.2rem;
        border-bottom: 3px solid var(--yellow-primary);
        display: inline-block;
        padding-bottom: 5px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }

    .info-item label {
        display: block;
        font-size: 0.8rem;
        text-transform: uppercase;
        color: #6c757d;
        font-weight: 700;
        margin-bottom: 4px;
    }

    .info-item span {
        font-size: 1.05rem;
        font-weight: 600;
        color: #212529;
    }

    .alert { padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; }
    .alert-success { background: #d1e7dd; color: #0f5132; }
    .alert-danger { background: #f8d7da; color: #842029; }

    .status-box {
        display: flex;
        align-items: center;
        gap: 15px;
        background: #f8f9fa;
        padding: 15px 20px;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        margin-top: 20px;
    }

    .select-status {
        padding: 8px 12px;
        border-radius: 6px;
        border: 1px solid #ced4da;
        font-weight: bold;
    }

    .btn-save {
        background: var(--red-primary);
        color: white;
        border: none;
        padding: 8px 18px;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
    }
    .btn-save:hover { background: var(--red-dark); }

    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .items-table th {
        background-color: var(--dark-accent);
        color: white;
        padding: 12px;
        text-align: left;
        font-size: 0.85rem;
        text-transform: uppercase;
    }

    .items-table td {
        padding: 12px;
        border-bottom: 1px solid #e9ecef;
    }
</style>

<div class="detail-container">

    <div class="detail-header">
        <h2>Detalle del Pedido #<?php echo $pedido_id; ?></h2>
        <div class="header-btns">
            <!-- BOTÓN PARA VER Y DESCARGAR COMPROBANTE -->
            <a href="comprobante.php?id=<?php echo $pedido_id; ?>" target="_blank" class="btn-invoice">📄 Ver Comprobante</a>
            <a href="dashboard_proveedor.php" class="btn-back">← Volver al Dashboard</a>
        </div>
    </div>

    <?php echo $mensaje; ?>

    <!-- Datos del Pedido -->
    <div class="card">
        <h3 class="card-title">Datos Generales</h3>
        
        <div class="info-grid">
            <div class="info-item">
                <label>N° Pedido</label>
                <span><?php echo htmlspecialchars($pedido['numero_pedido'] ?? 'N/A'); ?></span>
            </div>
            <div class="info-item">
                <label>Cliente</label>
                <span><?php echo htmlspecialchars($pedido['nombre_completo'] ?? ('Usuario #' . ($pedido['usuario_id'] ?? ''))); ?></span>
            </div>
            <div class="info-item">
                <label>Ciudad</label>
                <span><?php echo htmlspecialchars($pedido['ciudad'] ?? 'N/A'); ?></span>
            </div>
            <div class="info-item">
                <label>Dirección</label>
                <span><?php echo htmlspecialchars($pedido['direccion'] ?? 'N/A'); ?></span>
            </div>
            <div class="info-item">
                <label>Fecha de Pedido</label>
                <span><?php echo htmlspecialchars($pedido['fecha_pedido'] ?? 'N/A'); ?></span>
            </div>
            <div class="info-item">
                <label>Total de la Compra</label>
                <span style="color: var(--red-primary); font-size: 1.3rem;">$<?php echo number_format((float)($pedido['total'] ?? 0), 2); ?></span>
            </div>
        </div>

        <!-- Formulario para Cambiar Estado -->
        <form method="POST" class="status-box">
            <label style="font-weight: bold; color: var(--dark-accent);">Estado del Pedido:</label>
            <select name="nuevo_estado" class="select-status">
                <?php $est_act = strtolower($pedido['estado'] ?? 'pendiente'); ?>
                <option value="Pendiente" <?php echo ($est_act === 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                <option value="En Proceso" <?php echo ($est_act === 'en proceso') ? 'selected' : ''; ?>>En Proceso</option>
                <option value="Entregado" <?php echo ($est_act === 'entregado') ? 'selected' : ''; ?>>Entregado</option>
                <option value="Cancelado" <?php echo ($est_act === 'cancelado') ? 'selected' : ''; ?>>Cancelado</option>
            </select>
            <button type="submit" name="actualizar_estado" class="btn-save">Actualizar Estado</button>
        </form>
    </div>

    <!-- Lista de Productos -->
    <div class="card">
        <h3 class="card-title">Productos en este Pedido</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $item): 
                        $prod_nombre = $item['nombre_prod'] ?? $item['nombre_producto'] ?? $item['producto'] ?? 'Producto';
                        $cant = (int)($item['cantidad'] ?? 1);
                        $precio = (float)($item['precio'] ?? $item['precio_unitario'] ?? 0);
                        $subtotal = $cant * $precio;
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($prod_nombre); ?></strong></td>
                        <td><?php echo $cant; ?></td>
                        <td>$<?php echo number_format($precio, 2); ?></td>
                        <td><strong>$<?php echo number_format($subtotal, 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Muestra fallback con el monto global si los items aún no están vinculados en BD -->
                    <tr>
                        <td><strong>Pedido Manihabs (#<?php echo htmlspecialchars($pedido['numero_pedido'] ?? $pedido_id); ?>)</strong></td>
                        <td>1</td>
                        <td>$<?php echo number_format((float)($pedido['total'] ?? 0), 2); ?></td>
                        <td><strong>$<?php echo number_format((float)($pedido['total'] ?? 0), 2); ?></strong></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>