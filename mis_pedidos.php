<?php
// 1. declare(strict_types) SIEMPRE debe ir primero
declare(strict_types=1);

// Activar reporte de errores temporalmente
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

// 2. Incluir la conexión a la base de datos (¡Faltaba esto!)
require_once __DIR__ . '/config/db.php';

// (Opcional) Tu archivo de seguridad si maneja roles, lo dejo comentado por si acaso
// require_once __DIR__ . '/includes/seguridad.php';
// checkRol('cliente');

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    die("Error: No estás logueado. Inicia sesión para ver tus pedidos.");
}

$db = getDBConnection();
$cliente_id = (int)$_SESSION['user_id'];

try {
    // 3. Consulta corregida: Usamos 'usuario_id' en lugar de 'id_cliente'
    $stmt = $db->prepare("
        SELECT id, numero_pedido, total, estado, fecha_pedido 
        FROM pedidos 
        WHERE usuario_id = :cliente_id 
        ORDER BY id DESC
    ");
    $stmt->execute(['cliente_id' => $cliente_id]);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error en la base de datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - Historial</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; padding: 20px; color: #333; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h2 { color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #4e73df; color: white; }
        tr:hover { background-color: #f1f1f1; }
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; color: white; }
        .bg-pendiente { background-color: #f6c23e; }
        .bg-completado { background-color: #1cc88a; }
        .btn-sm { padding: 6px 12px; background-color: #36b9cc; color: white; text-decoration: none; border-radius: 4px; font-size: 14px; }
        .btn-sm:hover { background-color: #2c9faf; }
        .back-link { display: inline-block; margin-top: 20px; text-decoration: none; color: #4e73df; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <h2><i class="fa-solid fa-box-open"></i> Mis Pedidos</h2>

    <?php if (empty($pedidos)): ?>
        <p>Aún no has realizado ningún pedido en nuestra tienda.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>N° Pedido</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedidos as $p): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['numero_pedido'] ?? 'N/A') ?></strong></td>
                        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($p['fecha_pedido']))) ?></td>
                        <td>$<?= number_format((float)$p['total'], 2) ?></td>
                        <td>
                            <?php 
                                $estado = strtolower($p['estado'] ?? 'pendiente');
                                $badgeClass = ($estado === 'completado') ? 'bg-completado' : 'bg-pendiente';
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= ucfirst($estado) ?></span>
                        </td>
                        <td>
                            <a href="detalle_pedido.php?id=<?= $p['id'] ?>" class="btn-sm"><i class="fa-solid fa-eye"></i> Ver Detalle</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <a href="dashboard_cliente.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Volver al Inicio</a>
</div>

</body>
</html>