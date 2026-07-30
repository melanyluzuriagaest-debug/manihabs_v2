<?php
session_start();
require_once 'config/db.php';

// Verificar si viene un ID en la URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: No se especificó un producto.");
}

$producto_id = (int)$_GET['id'];

// Buscar el producto en la BD
$stmt = $conn->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    die("Error: El producto no existe.");
}

$producto = $resultado->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de <?php echo htmlspecialchars($producto['nombre']); ?></title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f4f6f9; }
        .detalle-card { background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .btn-volver { display: inline-block; margin-bottom: 20px; color: #dc3545; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

    <div class="detalle-card">
        <a href="dashboard_cliente.php" class="btn-volver">← Volver al Catálogo</a>
        
        <h2><?php echo htmlspecialchars($producto['nombre']); ?></h2>
        <h3 style="color: #dc3545;">$<?php echo number_format($producto['precio'], 2); ?></h3>
        
        <p><strong>Descripción:</strong></p>
        <p><?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?></p>
        
        <!-- Aquí luego puedes agregar tu formulario para seleccionar cantidad y añadir al carrito/hacer pedido -->
        <button style="background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
            Añadir al pedido (Próximamente)
        </button>
    </div>

</body>
</html>
