<?php
/**
 * Manihabs v2 - Detalle del Pedido (Cliente)
 */

declare(strict_types=1);

// Activar reporte de errores temporalmente para cazar bugs
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

// Conexión a la base de datos (Obligatorio)
require_once __DIR__ . '/config/db.php';
$db = getDBConnection();

// Verificamos que el usuario tenga sesión
if (!isset($_SESSION['user_id'])) {
    die("Error: No estás logueado.");
}

$pedido_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$cliente_id = (int)$_SESSION['user_id'];

if (!$pedido_id) {
    header("Location: mis_pedidos.php");
    exit();
}

// 1. Obtener datos del pedido (Corregido: usamos usuario_id y eliminamos joins innecesarios)
$stmt = $db->prepare("
    SELECT id, numero_pedido, total, estado, fecha_pedido, ciudad, direccion 
    FROM pedidos 
    WHERE id = :id AND usuario_id = :cliente_id 
    LIMIT 1
");
$stmt->execute(['id' => $pedido_id, 'cliente_id' => $cliente_id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    header("Location: mis_pedidos.php");
    exit();
}

// 2. Obtener detalles e ítems (Corregido: usamos pedido_id, producto_id, precio_unitario, subtotal)
$stmtItems = $db->prepare("
    SELECT dp.cantidad, dp.precio_unitario, dp.subtotal, prod.nombre as producto_nombre 
    FROM detalles_pedido dp
    JOIN productos prod ON dp.producto_id = prod.id
    WHERE dp.pedido_id = :pedido_id
");
$stmtItems->execute(['pedido_id' => $pedido_id]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

// Usamos htmlspecialchars nativo de PHP para evitar errores 500 por funciones perdidas
$estadoClean = strtolower(htmlspecialchars($pedido['estado'] ?? 'pendiente'));
$tieneComprobante = in_array($estadoClean, ['pagado', 'entregado'], true) && !empty($pedido['comprobante_url']);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Pedido #<?= htmlspecialchars((string)$pedido_id) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #4e73df; --text-muted: #6c757d; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; padding: 20px; color: #333; margin: 0; }
        .container { max-width: 900px; margin: 0 auto; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .page-title { margin: 0; color: #2c3e50; font-size: 24px; }
        .btn-outline { padding: 8px 16px; border: 2px solid var(--primary-color); color: var(--primary-color); text-decoration: none; border-radius: 6px; font-weight: bold; transition: 0.3s; }
        .btn-outline:hover { background-color: var(--primary-color); color: white; }
        .stats-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .badge { padding: 5px 12px; border-radius: 15px; font-size: 12px; font-weight: bold; color: white; display: inline-block; }
        .badge-pendiente { background-color: #f6c23e; }
        .badge-completado { background-color: #1cc88a; }
        .table-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: var(--primary-color); color: white; }
        @media (max-width: 768px) { .stats-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">
            Detalle de Pedido #<?= htmlspecialchars($pedido['numero_pedido'] ?? sprintf('%05d', $pedido['id'])) ?>
        </h1>
        <a href="mis_pedidos.php" class="btn-outline"><i class="fa-solid fa-arrow-left"></i> Volver</a>
    </div>

    <div class="stats-grid">
        <!-- Tarjeta de Resumen -->
        <div class="card">
            <h2 style="font-size: 1.2rem; margin-top: 0; color: var(--text-muted); border-bottom: 1px solid #eee; padding-bottom: 10px;">Resumen del Pedido</h2>
            <p><strong>Fecha de Emisión:</strong> <?= htmlspecialchars(date('d/m/Y H:i', strtotime($pedido['fecha_pedido']))) ?></p>
            <p><strong>Ciudad:</strong> <?= htmlspecialchars($pedido['ciudad'] ?? 'No especificada') ?></p>
            <p><strong>Dirección:</strong> <?= htmlspecialchars($pedido['direccion'] ?? 'No especificada') ?></p>
            <p>
                <strong>Estado Actual:</strong> 
                <span class="badge badge-<?= $estadoClean ?>">
                    <?= ucfirst($estadoClean) ?>
                </span>
            </p>
        </div>
        
        <!-- Tarjeta de Total -->
        <div class="card" style="display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">
            <span style="font-size: 1rem; color: var(--text-muted); font-weight: bold;">Monto Total</span>
            <span style="font-size: 2.5rem; font-weight: 900; color: var(--primary-color); margin: 10px 0;">
                $<?= number_format((float)$pedido['total'], 2) ?>
            </span>
            
            <?php if ($tieneComprobante): ?>
                <a href="generar_comprobante.php?id=<?= (int)$pedido['id'] ?>" target="_blank" class="btn-outline" style="background:#1cc88a; color:white; border-color:#1cc88a; width: 80%;">
                    <i class="fa-solid fa-file-invoice"></i> Comprobante
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabla de Productos -->
    <div class="table-container">
        <h3 style="margin-top: 0; font-size: 1.2rem; border-bottom: 1px solid #eee; padding-bottom: 10px;">Productos en este Pedido</h3>
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Precio Unitario</th>
                    <th>Cantidad</th>
                    <th style="text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($item['producto_nombre']) ?></strong></td>
                        <td>$<?= number_format((float)$item['precio_unitario'], 2) ?></td>
                        <td><?= (int)$item['cantidad'] ?></td>
                        <td style="text-align: right;"><strong>$<?= number_format((float)$item['subtotal'], 2) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>