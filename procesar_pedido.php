<?php
/**
 * Manihabs v2 - Procesar el envío del pedido
 */

declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';

// Validar inicio de sesión
$usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? null;
if (!$usuario_id) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Datos enviados desde hacer_pedido.php
        $ciudad = trim($_POST['ciudad'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $cantidades = $_POST['cantidades'] ?? [];

        $total_pedido = 0.0;
        $estado_inicial = 'pendiente';
        $numero_pedido = 'PED-' . date('Ymd') . '-' . rand(1000, 9999);
        $items_pedido = [];

        // 1. Procesar los productos seleccionados
        if (is_array($cantidades)) {
            foreach ($cantidades as $producto_id => $cantidad) {
                $qty = (int)$cantidad;
                if ($qty > 0) {
                    $id = (int)$producto_id;
                    $stmt = $conn->prepare("SELECT * FROM productos WHERE id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $id);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        if ($prod = $res->fetch_assoc()) {
                            $precio = (float)($prod['precio_funda'] ?? $prod['precio'] ?? $prod['precio_unitario'] ?? 0);
                            $total_pedido += ($qty * $precio);
                            
                            $items_pedido[] = [
                                'id' => $id,
                                'qty' => $qty,
                                'precio' => $precio,
                                'nombre' => $prod['nombre'] ?? 'Producto'
                            ];
                        }
                        $stmt->close();
                    }
                }
            }
        }

        // Si la lista de items procesados quedó vacía
        if (empty($items_pedido)) {
            header("Location: hacer_pedido.php?error=vacio");
            exit();
        }

        // 2. Mapear dinámicamente la estructura de la tabla `pedidos`
        $col_res = $conn->query("SHOW COLUMNS FROM pedidos");
        $columnas_existentes = [];
        if ($col_res) {
            while ($col = $col_res->fetch_assoc()) {
                $columnas_existentes[] = strtolower($col['Field']);
            }
        }

        $campos = ['usuario_id', 'total', 'estado'];
        $valores = [$usuario_id, $total_pedido, $estado_inicial];
        $tipos = "ids";

        if (in_array('numero_pedido', $columnas_existentes)) {
            $campos[] = 'numero_pedido';
            $valores[] = $numero_pedido;
            $tipos .= "s";
        }
        if (in_array('ciudad', $columnas_existentes)) {
            $campos[] = 'ciudad';
            $valores[] = $ciudad;
            $tipos .= "s";
        }
        if (in_array('direccion', $columnas_existentes)) {
            $campos[] = 'direccion';
            $valores[] = $direccion;
            $tipos .= "s";
        }
        if (in_array('fecha_pedido', $columnas_existentes)) {
            $campos[] = 'fecha_pedido';
            $valores[] = date('Y-m-d H:i:s');
            $tipos .= "s";
        } elseif (in_array('fecha', $columnas_existentes)) {
            $campos[] = 'fecha';
            $valores[] = date('Y-m-d H:i:s');
            $tipos .= "s";
        }

        $sql = "INSERT INTO pedidos (" . implode(', ', $campos) . ") VALUES (" . implode(', ', array_fill(0, count($campos), '?')) . ")";
        $stmt_insert = $conn->prepare($sql);
        
        if (!$stmt_insert) {
            throw new Exception("Error al preparar INSERT principal: " . $conn->error);
        }

        $stmt_insert->bind_param($tipos, ...$valores);

        if ($stmt_insert->execute()) {
            $nuevo_pedido_id = $stmt_insert->insert_id;
            $stmt_insert->close();

            // 3. Mapear dinámicamente la tabla de detalles para guardar cada producto individual
            $posibles_tablas = ['detalles_pedido', 'detalle_pedidos', 'pedido_detalles', 'detalles_pedidos', 'pedidos_detalle'];
            $tabla_detalle = null;

            foreach ($posibles_tablas as $t) {
                $test_tab = $conn->query("SHOW TABLES LIKE '{$t}'");
                if ($test_tab && $test_tab->num_rows > 0) {
                    $tabla_detalle = $t;
                    break;
                }
            }

            if ($tabla_detalle) {
                // Obtener columnas de la tabla de detalles encontrada
                $cols_det_res = $conn->query("SHOW COLUMNS FROM {$tabla_detalle}");
                $cols_det = [];
                if ($cols_det_res) {
                    while ($c = $cols_det_res->fetch_assoc()) {
                        $cols_det[] = strtolower($c['Field']);
                    }
                }

                $col_ped = in_array('pedido_id', $cols_det) ? 'pedido_id' : (in_array('id_pedido', $cols_det) ? 'id_pedido' : null);
                $col_prod = in_array('producto_id', $cols_det) ? 'producto_id' : (in_array('id_producto', $cols_det) ? 'id_producto' : null);
                $col_cant = in_array('cantidad', $cols_det) ? 'cantidad' : (in_array('cant', $cols_det) ? 'cant' : null);
                $col_prec = in_array('precio_unitario', $cols_det) ? 'precio_unitario' : (in_array('precio', $cols_det) ? 'precio' : (in_array('precio_funda', $cols_det) ? 'precio_funda' : null));

                if ($col_ped && $col_prod && $col_cant) {
                    $fields_det = [$col_ped, $col_prod, $col_cant];
                    $types_det = "iii";

                    if ($col_prec) {
                        $fields_det[] = $col_prec;
                        $types_det .= "d";
                    }
                    if (in_array('subtotal', $cols_det)) {
                        $fields_det[] = 'subtotal';
                        $types_det .= "d";
                    }

                    $sql_det = "INSERT INTO {$tabla_detalle} (" . implode(', ', $fields_det) . ") VALUES (" . implode(', ', array_fill(0, count($fields_det), '?')) . ")";

                    foreach ($items_pedido as $item) {
                        $stmt_det = $conn->prepare($sql_det);
                        if ($stmt_det) {
                            $vals_det = [$nuevo_pedido_id, $item['id'], $item['qty']];
                            if ($col_prec) {
                                $vals_det[] = $item['precio'];
                            }
                            if (in_array('subtotal', $cols_det)) {
                                $vals_det[] = $item['qty'] * $item['precio'];
                            }
                            $stmt_det->bind_param($types_det, ...$vals_det);
                            $stmt_det->execute();
                            $stmt_det->close();
                        }
                    }
                }
            }

            // Redirigir al dashboard con alerta de éxito
            header("Location: dashboard_cliente.php?exito=1");
            exit();
        } else {
            throw new Exception("Error al ejecutar INSERT principal: " . $stmt_insert->error);
        }

    } catch (Throwable $e) {
        echo "<div style='font-family: sans-serif; padding: 30px; background: #f8d7da; color: #721c24; border-radius: 8px; max-width: 600px; margin: 40px auto; border: 1px solid #f5c6cb;'>";
        echo "<h2>⚠️ Error al procesar el pedido</h2>";
        echo "<p><strong>Detalle:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>Archivo:</strong> " . htmlspecialchars($e->getFile()) . " (Línea " . $e->getLine() . ")</p>";
        echo "<br><a href='hacer_pedido.php' style='background: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;'>← Volver e intentar de nuevo</a>";
        echo "</div>";
    }
} else {
    header("Location: dashboard_cliente.php");
    exit();
}