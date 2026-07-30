<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/db.php';

if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
    }
}

// Validar que el usuario tenga sesión activa
$usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? null;
if (!$usuario_id) {
    header("Location: index.php");
    exit();
}

$mensaje = "";

// PROCESAR ACTUALIZACIÓN DE ESTADO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado'])) {
    $p_id = isset($_POST['pedido_id']) ? (int)$_POST['pedido_id'] : 0;
    $nuevo_estado = strtolower(trim(sanitize($_POST['nuevo_estado'] ?? '')));

    if ($p_id > 0 && !empty($nuevo_estado)) {
        $stmt_up = $conn->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        if ($stmt_up) {
            $stmt_up->bind_param("si", $nuevo_estado, $p_id);
            if ($stmt_up->execute()) {
                $mensaje = "<div class='alert alert-success'>¡Estado del pedido #{$p_id} actualizado a '" . strtoupper($nuevo_estado) . "' con éxito!</div>";
            } else {
                $mensaje = "<div class='alert alert-danger'>Error al ejecutar la actualización: " . htmlspecialchars($stmt_up->error) . "</div>";
            }
            $stmt_up->close();
        } else {
            $mensaje = "<div class='alert alert-danger'>Error en la preparación de la consulta: " . htmlspecialchars($conn->error) . "</div>";
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>Datos incompletos para actualizar el pedido.</div>";
    }
}

// OBTENER ESTADÍSTICAS DEL PROVEEDOR
$tot_pedidos = 0;
$tot_ingresos = 0.00;
$tot_pendientes = 0;
$tot_entregados = 0;

$res_stat = $conn->query("SELECT 
    COUNT(*) as total_p, 
    SUM(total) as total_i,
    SUM(CASE WHEN LOWER(estado) = 'pendiente' THEN 1 ELSE 0 END) as pend,
    SUM(CASE WHEN LOWER(estado) = 'entregado' THEN 1 ELSE 0 END) as entr
FROM pedidos");

if ($res_stat && $row_s = $res_stat->fetch_assoc()) {
    $tot_pedidos    = (int)($row_s['total_p'] ?? 0);
    $tot_ingresos   = (float)($row_s['total_i'] ?? 0);
    $tot_pendientes = (int)($row_s['pend'] ?? 0);
    $tot_entregados = (int)($row_s['entr'] ?? 0);
}

// OBTENER LISTA DE PEDIDOS
$pedidos = [];
$res_pedidos = $conn->query("SELECT p.*, u.nombre_completo FROM pedidos p LEFT JOIN usuarios u ON p.usuario_id = u.id ORDER BY p.id DESC");
if ($res_pedidos) {
    while ($r = $res_pedidos->fetch_assoc()) {
        $pedidos[] = $r;
    }
}

$titulo_pagina = 'Panel de Control - Proveedor';
if (file_exists('includes/header.php')) {
    include 'includes/header.php';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina; ?></title>
    <style>
        :root {
            --red-primary: #dc3545;
            --red-dark: #b02a37;
            --yellow-primary: #ffc107;
            --yellow-dark: #d39e00;
            --yellow-light: #fff3cd;
            --dark-accent: #212529;
            --bg-body: #f4f6f9;
        }

        body { background-color: var(--bg-body); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }

        .dash-container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }

        .dash-header {
            background: linear-gradient(135deg, var(--red-dark), var(--red-primary));
            color: white; padding: 22px 28px; border-radius: 12px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.25); margin-bottom: 25px;
        }

        .dash-header h2 { margin: 0; font-size: 1.6rem; font-weight: 700; }

        .btn-profile {
            background-color: var(--yellow-primary); color: #000; padding: 9px 16px;
            border-radius: 6px; text-decoration: none; font-weight: 700; font-size: 0.9rem; margin-right: 8px;
        }
        .btn-profile:hover { background-color: var(--yellow-dark); }

        .btn-logout {
            background-color: rgba(255, 255, 255, 0.15); color: white; padding: 9px 16px;
            border-radius: 6px; text-decoration: none; font-weight: 600; border: 1px solid rgba(255,255,255,0.3);
        }
        .btn-logout:hover { background-color: white; color: var(--red-primary); }

        /* Metricas KPI */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .kpi-card {
            background: white; padding: 20px; border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04); border-left: 5px solid var(--red-primary);
        }
        .kpi-card.yellow { border-left-color: var(--yellow-primary); }

        .kpi-title { font-size: 0.8rem; font-weight: 700; color: #6c757d; text-transform: uppercase; margin-bottom: 6px; }
        .kpi-value { font-size: 1.8rem; font-weight: 800; color: var(--dark-accent); }

        /* Tabla */
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .card-title { margin-top: 0; margin-bottom: 20px; color: var(--dark-accent); font-size: 1.25rem; border-bottom: 3px solid var(--yellow-primary); display: inline-block; padding-bottom: 5px; }

        .custom-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .custom-table th { background-color: var(--dark-accent); color: white; padding: 14px 12px; font-size: 0.85rem; text-transform: uppercase; text-align: left; }
        .custom-table td { padding: 14px 12px; border-bottom: 1px solid #e9ecef; vertical-align: middle; }

        .badge-status { padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; display: inline-block; }
        .status-pendiente { background-color: var(--yellow-light); color: #856404; border: 1px solid var(--yellow-primary); }
        .status-entregado { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-cancelado { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .btn-detail { background-color: var(--red-primary); color: white; padding: 7px 14px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 0.85rem; }
        .btn-detail:hover { background-color: var(--red-dark); }

        .btn-check { background-color: var(--yellow-primary); color: #000; border: none; padding: 7px 12px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn-check:hover { background-color: var(--yellow-dark); }

        .select-status { padding: 6px 10px; border-radius: 6px; border: 1px solid #ced4da; font-size: 0.85rem; }

        .alert { padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; }
        .alert-success { background: #d1e7dd; color: #0f5132; }
        .alert-danger { background: #f8d7da; color: #842029; }
    </style>
</head>
<body>

<div class="dash-container">

    <div class="dash-header">
        <h2>Panel de Control - Proveedor</h2>
        <div>
            <a href="perfil.php" class="btn-profile">Mi Perfil</a>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>

    <?php echo $mensaje; ?>

    <!-- MÉTRICAS / KPIS -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-title">Total Pedidos</div>
            <div class="kpi-value"><?php echo $tot_pedidos; ?></div>
        </div>
        <div class="kpi-card yellow">
            <div class="kpi-title">Ingresos Totales</div>
            <div class="kpi-value">$<?php echo number_format($tot_ingresos, 2); ?></div>
        </div>
        <div class="kpi-card yellow">
            <div class="kpi-title">Pendientes</div>
            <div class="kpi-value"><?php echo $tot_pendientes; ?></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Entregados</div>
            <div class="kpi-value"><?php echo $tot_entregados; ?></div>
        </div>
    </div>

    <!-- TABLA DE PEDIDOS -->
    <div class="card">
        <h3 class="card-title">Gestión de Pedidos</h3>

        <div style="overflow-x: auto;">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>N° Pedido</th>
                        <th>Cliente</th>
                        <th>Ciudad</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Estado Actual</th>
                        <th>Cambiar Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pedidos)): ?>
                        <?php foreach ($pedidos as $p): 
                            $est = strtolower(trim($p['estado'] ?? 'pendiente'));
                            
                            $badge_class = 'status-pendiente';
                            if ($est === 'entregado') $badge_class = 'status-entregado';
                            if ($est === 'cancelado') $badge_class = 'status-cancelado';
                        ?>
                        <tr>
                            <td><strong>#<?php echo $p['id']; ?></strong></td>
                            <td><strong><?php echo htmlspecialchars($p['numero_pedido'] ?? ('PED-' . $p['id'])); ?></strong></td>
                            <td><?php echo htmlspecialchars($p['nombre_completo'] ?? 'Cliente Demo'); ?></td>
                            <td><?php echo htmlspecialchars($p['ciudad'] ?? 'N/A'); ?></td>
                            <td><small><?php echo htmlspecialchars($p['fecha_pedido'] ?? 'N/A'); ?></small></td>
                            <td><strong style="color: var(--red-primary);">$<?php echo number_format((float)($p['total'] ?? 0), 2); ?></strong></td>
                            <td>
                                <span class="badge-status <?php echo $badge_class; ?>">
                                    <?php echo strtoupper(htmlspecialchars($p['estado'] ?? 'Pendiente')); ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="dashboard_proveedor.php" style="display: flex; gap: 6px; align-items: center;">
                                    <input type="hidden" name="pedido_id" value="<?php echo $p['id']; ?>">
                                    <select name="nuevo_estado" class="select-status">
                                        <option value="pendiente" <?php echo ($est === 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                                        <option value="entregado" <?php echo ($est === 'entregado') ? 'selected' : ''; ?>>Entregado</option>
                                        <option value="cancelado" <?php echo ($est === 'cancelado') ? 'selected' : ''; ?>>Cancelado</option>
                                    </select>
                                    <button type="submit" name="actualizar_estado" class="btn-check" title="Guardar estado">✓</button>
                                </form>
                            </td>
                            <td>
                                <a href="detalle_pedido_proveedor.php?id=<?php echo $p['id']; ?>" class="btn-detail">Ver Detalle</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 30px; color: #888;">No hay pedidos registrados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>