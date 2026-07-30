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

// Verificar sesión del cliente
$usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? null;

if (!$usuario_id) {
    header("Location: index.php");
    exit();
}

// 1. Obtener Pedidos del Cliente Conectado
$stmt_pedidos = $conn->prepare("SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY id DESC");
$pedidos = [];
if ($stmt_pedidos) {
    $stmt_pedidos->bind_param("i", $usuario_id);
    $stmt_pedidos->execute();
    $res = $stmt_pedidos->get_result();
    while ($row = $res->fetch_assoc()) {
        $pedidos[] = $row;
    }
    $stmt_pedidos->close();
}

// 2. Obtener Catálogo de Productos para "Hacer Pedido"
$res_productos = $conn->query("SELECT * FROM productos ORDER BY id DESC");
$productos = [];
if ($res_productos) {
    while ($p = $res_productos->fetch_assoc()) {
        $productos[] = $p;
    }
}

$titulo_pagina = 'Mi Portal de Compras';
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

        body { 
            background-color: var(--bg-body); 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 0; 
        }

        .portal-container { max-width: 1150px; margin: 30px auto; padding: 0 20px; }

        /* HEADER Y MENÚ DEL CLIENTE */
        .portal-header {
            background: linear-gradient(135deg, var(--red-dark), var(--red-primary));
            color: white; 
            padding: 20px 28px; 
            border-radius: 12px;
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.25); 
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .portal-header h2 { margin: 0; font-size: 1.5rem; font-weight: 700; color: white; }

        .portal-menu {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-nav {
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.2s;
        }
        .btn-nav:hover { background-color: rgba(255, 255, 255, 0.3); }

        .btn-profile {
            background-color: var(--yellow-primary); 
            color: #000; 
            padding: 8px 16px;
            border-radius: 6px; 
            text-decoration: none; 
            font-weight: 700; 
            font-size: 0.9rem;
        }
        .btn-profile:hover { background-color: var(--yellow-dark); }

        .btn-logout {
            background-color: rgba(255, 255, 255, 0.2); 
            color: white; 
            padding: 8px 14px;
            border-radius: 6px; 
            text-decoration: none; 
            font-weight: 600; 
            border: 1px solid rgba(255,255,255,0.3);
            font-size: 0.9rem;
        }
        .btn-logout:hover { background-color: white; color: var(--red-primary); }

        /* TARJETAS PRINCIPALES */
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 30px; }

        .card-title {
            margin-top: 0; 
            margin-bottom: 20px; 
            color: var(--dark-accent); 
            font-size: 1.25rem;
            border-bottom: 3px solid var(--yellow-primary); 
            display: inline-block; 
            padding-bottom: 5px;
        }

        /* CATÁLOGO / HACER PEDIDO */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }

        .product-card {
            border: 1px solid #e9ecef; border-radius: 10px; overflow: hidden; background: white;
            transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; justify-content: space-between;
        }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 6px 15px rgba(0,0,0,0.1); }

        .product-img {
            width: 100%; height: 160px; object-fit: cover; background: #eee;
        }

        .product-info { padding: 15px; flex-grow: 1; }
        .product-title { font-size: 1.05rem; font-weight: bold; color: var(--dark-accent); margin: 0 0 8px 0; }
        .product-desc { font-size: 0.85rem; color: #6c757d; margin-bottom: 12px; line-height: 1.3; }
        .product-price { font-size: 1.2rem; font-weight: 800; color: var(--red-primary); }

        .btn-add {
            display: block; text-align: center; background-color: var(--red-primary); color: white;
            padding: 10px; text-decoration: none; font-weight: bold; border-radius: 6px; margin: 15px; margin-top: 0;
        }
        .btn-add:hover { background-color: var(--red-dark); }

        /* TABLA DE MIS PEDIDOS */
        .custom-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .custom-table th { background-color: var(--dark-accent); color: white; padding: 14px 12px; font-size: 0.85rem; text-transform: uppercase; text-align: left; }
        .custom-table td { padding: 14px 12px; border-bottom: 1px solid #e9ecef; vertical-align: middle; }

        .badge-status { padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; display: inline-block; }
        .status-pendiente { background-color: var(--yellow-light); color: #856404; border: 1px solid var(--yellow-primary); }
        .status-entregado { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-cancelado { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* ESTILOS DEL BOTÓN COMPROBANTE */
        .btn-invoice { 
            background-color: var(--red-primary); 
            color: white; 
            padding: 7px 14px; 
            border-radius: 6px; 
            text-decoration: none; 
            font-weight: bold; 
            font-size: 0.85rem; 
            display: inline-block;
        }
        .btn-invoice:hover { background-color: var(--red-dark); }

        .btn-disabled {
            background-color: #e9ecef;
            color: #6c757d;
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            cursor: not-allowed;
            border: 1px solid #ced4da;
        }
    </style>
</head>
<body>

<div class="portal-container">

    <!-- BARRA DE NAVEGACIÓN COMPLETA -->
    <div class="portal-header">
        <h2>Mi Portal de Compras</h2>
        <div class="portal-menu">
            <a href="#hacer-pedido" class="btn-nav">🛒 Hacer Pedido</a>
            <a href="#mis-pedidos" class="btn-nav">📦 Mis Pedidos</a>
            <a href="perfil.php" class="btn-profile">👤 Mi Perfil</a>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>

    <!-- SECCIÓN 1: HACER PEDIDO / CATÁLOGO -->
    <div class="card" id="hacer-pedido">
        <h3 class="card-title">Hacer Pedido (Catálogo de Productos)</h3>
        
        <!-- Mensaje de éxito si viene de hacer un pedido -->
        <?php if(isset($_GET['exito']) && $_GET['exito'] == 1): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                ✅ ¡Tu pedido ha sido registrado con éxito! Lo puedes ver en tu historial de abajo.
            </div>
        <?php endif; ?>

        <div class="products-grid">
            <?php if (!empty($productos)): ?>
                <?php foreach ($productos as $prod): 
                    $img = !empty($prod['imagen']) ? $prod['imagen'] : 'assets/img/no-image.png';
                ?>
                <div class="product-card">
                    <div>
                        <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($prod['nombre'] ?? 'Producto'); ?>" class="product-img" onerror="this.src='https://via.placeholder.com/250x160?text=Producto';">
                        <div class="product-info">
                            <h4 class="product-title"><?php echo htmlspecialchars($prod['nombre'] ?? 'Producto'); ?></h4>
                            <p class="product-desc"><?php echo htmlspecialchars(substr($prod['descripcion'] ?? '', 0, 70)) . '...'; ?></p>
                            <div class="product-price">$<?php echo number_format((float)($prod['precio'] ?? 0), 2); ?></div>
                        </div>
                    </div>
                    <!-- AQUÍ ESTÁ EL CAMBIO 1: Apunta a hacer_pedido.php -->
                    <a href="hacer_pedido.php?id=<?php echo $prod['id']; ?>" class="btn-add">🛒 Hacer Pedido</a>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #6c757d;">No hay productos disponibles por el momento.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- SECCIÓN 2: MIS PEDIDOS REALIZADOS -->
    <div class="card" id="mis-pedidos">
        <h3 class="card-title">Mis Pedidos Realizados</h3>

        <div style="overflow-x: auto;">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>N° Pedido</th>
                        <th>Fecha</th>
                        <th>Ciudad</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Comprobante</th>
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
                            <!-- AQUÍ ESTÁ EL CAMBIO 2: El N° de pedido ahora es un enlace al detalle -->
                            <td>
                                <a href="detalle_pedido.php?id=<?php echo (int)$p['id']; ?>" style="color: var(--red-primary); text-decoration: underline; font-weight: bold;">
                                    <?php echo htmlspecialchars($p['numero_pedido'] ?? ('PED-' . $p['id'])); ?>
                                </a>
                            </td>
                            <td><small><?php echo htmlspecialchars($p['fecha_pedido'] ?? 'N/A'); ?></small></td>
                            <td><?php echo htmlspecialchars($p['ciudad'] ?? 'N/A'); ?></td>
                            <td><strong style="color: var(--red-primary);">$<?php echo number_format((float)($p['total'] ?? 0), 2); ?></strong></td>
                            <td>
                                <span class="badge-status <?php echo $badge_class; ?>">
                                    <?php echo ucfirst(htmlspecialchars($p['estado'] ?? 'Pendiente')); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($est === 'entregado'): ?>
                                    <a href="comprobante.php?id=<?php echo (int)$p['id']; ?>" target="_blank" class="btn-invoice">
                                        📄 Ver Comprobante
                                    </a>
                                <?php else: ?>
                                    <span class="btn-disabled" title="El comprobante estará disponible cuando el pedido sea ENTREGADO">
                                        ⏳ Disponible al Entregar
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #888; padding: 30px;">
                                Aún no has realizado ninguna compra.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>