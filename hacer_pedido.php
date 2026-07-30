<?php
/**
 * Manihabs v2 - Crear Nuevo Pedido
 */

declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';

// Validación de sesión
$usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? null;
if (!$usuario_id) {
    header("Location: index.php");
    exit();
}

if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
    }
}

// Consulta de productos
$productos = [];
if (isset($conn)) {
    $res = $conn->query("SELECT * FROM productos ORDER BY nombre ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $productos[] = $row;
        }
    }
} else {
    die("Error crítico: No se encontró la conexión a la base de datos.");
}

// Cargar solo el header (NO cargaremos el sidebar para quitar el menú izquierdo)
if (file_exists(__DIR__ . '/includes/header.php')) {
    require_once __DIR__ . '/includes/header.php';
}
?>

<!-- Estilos forzados para respetar el tema Rojo/Amarillo de Manihabs -->
<style>
    body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .portal-container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
    .page-title { color: #dc3545; font-weight: bold; margin-bottom: 20px; }
    .btn-rojo { background-color: #dc3545 !important; color: white !important; border: none !important; }
    .btn-rojo:hover { background-color: #b02a37 !important; }
    .btn-outline-rojo { border: 2px solid #dc3545 !important; color: #dc3545 !important; background: transparent !important; }
    .btn-outline-rojo:hover { background: #dc3545 !important; color: white !important; }
    /* Ocultar elementos del sidebar si el header los trae forzados */
    .sidebar, #sidebar { display: none !important; }
    .main-content, .content { margin-left: 0 !important; width: 100% !important; }
</style>

<div class="portal-container">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #ffc107; padding-bottom: 15px; margin-bottom: 20px;">
        <h1 class="page-title" style="margin: 0;">Realizar Nuevo Pedido</h1>
        <a href="dashboard_cliente.php" class="btn btn-outline-rojo" style="padding: 8px 15px; border-radius: 6px; text-decoration: none; font-weight: bold;">
            ← Volver al Catálogo
        </a>
    </div>

    <!-- Mostrar alerta si viene redirigido por error -->
    <?php if(isset($_GET['error']) && $_GET['error'] == 'vacio'): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
            ⚠️ Debes seleccionar al menos un producto (cantidad mayor a 0) para realizar el pedido.
        </div>
    <?php endif; ?>

    <form action="procesar_pedido.php" method="POST" id="form-pedido">
        <!-- SECCIÓN 1: SELECCIÓN DE PRODUCTOS -->
        <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 25px;">
            <h2 style="font-size: 1.2rem; font-weight: 600; margin-top: 0; border-bottom: 2px solid #f4f6f9; padding-bottom: 10px;">1. Selecciona los Productos</h2>
            
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                    <thead>
                        <tr style="background-color: #f8f9fa;">
                            <th style="padding: 12px; text-align: left; color: #495057;">Producto</th>
                            <th style="padding: 12px; text-align: left; color: #495057;">Precio por Funda</th>
                            <th style="width: 150px; text-align: center; padding: 12px; color: #495057;">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($productos)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: #6c757d; padding: 2rem;">No hay productos registrados.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($productos as $p): 
                                $precio_final = (float)($p['precio_funda'] ?? $p['precio'] ?? 0);
                            ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 15px 12px;"><strong><?= sanitize($p['nombre'] ?? 'Producto') ?></strong></td>
                                    <td style="padding: 15px 12px; color: #dc3545; font-weight: bold;">$<?= number_format($precio_final, 2) ?></td>
                                    <td style="text-align: center; padding: 15px 12px;">
                                        <input type="number" name="cantidades[<?= (int)$p['id'] ?>]" min="0" max="1000" value="0" class="qty-input" data-precio="<?= $precio_final ?>" oninput="calcularTotal()" style="text-align: center; padding: 8px; width: 80px; border: 1px solid #ced4da; border-radius: 6px; font-size: 1rem;">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SECCIÓN 2: DATOS DE ENVÍO -->
        <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 25px;">
            <h2 style="font-size: 1.2rem; font-weight: 600; margin-top: 0; border-bottom: 2px solid #f4f6f9; padding-bottom: 10px;">2. Datos de Envío</h2>
            
            <div style="margin-top: 15px; margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #495057;">Ciudad</label>
                <input type="text" name="ciudad" placeholder="Ej: Cuenca, Guayaquil..." required style="width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 6px; box-sizing: border-box; font-size: 1rem;">
            </div>

            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #495057;">Dirección Exacta y Referencia</label>
                <textarea name="direccion" rows="3" placeholder="Calle principal, intersección, número de casa y referencia..." required style="width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 6px; box-sizing: border-box; font-size: 1rem;"></textarea>
            </div>
        </div>

        <!-- SECCIÓN 3: TOTAL Y BOTÓN -->
        <div style="background: #fff3cd; padding: 25px; border-radius: 10px; border: 1px solid #ffeeba; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div>
                <h3 style="margin: 0; color: #856404; font-size: 1.1rem; text-transform: uppercase;">Total Estimado:</h3>
                <span id="total-pedido" style="font-size: 2.5rem; font-weight: 900; color: #dc3545;">$0.00</span>
            </div>
            
            <button type="submit" class="btn-rojo" style="padding: 15px 30px; font-size: 1.2rem; font-weight: bold; border-radius: 8px; cursor: pointer;">
                🛒 Confirmar y Enviar Pedido
            </button>
        </div>
    </form>
</div>

<script>
    function calcularTotal() {
        let total = 0;
        const inputs = document.querySelectorAll('.qty-input');
        
        inputs.forEach(input => {
            const cantidad = parseInt(input.value) || 0;
            const precio = parseFloat(input.getAttribute('data-precio')) || 0;
            
            if (cantidad > 0) {
                total += (cantidad * precio);
            }
        });
        
        document.getElementById('total-pedido').innerText = "$" + total.toFixed(2);
    }
</script>

</body>
</html>