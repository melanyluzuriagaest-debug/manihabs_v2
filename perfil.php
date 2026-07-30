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

$usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;

if (!$usuario_id) {
    header("Location: index.php");
    exit();
}

// Determinar el dashboard según el rol del usuario
$rol = strtolower($_SESSION['rol'] ?? 'cliente');
$dashboard_url = ($rol === 'proveedor' || $rol === 'admin') ? 'dashboard_proveedor.php' : 'dashboard_cliente.php';

$mensaje = "";

// PROCESAR ACTUALIZACIÓN DE PERFIL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
    $nombre = sanitize($_POST['nombre_completo'] ?? '');
    $email  = sanitize($_POST['email'] ?? '');
    $telefono = sanitize($_POST['telefono'] ?? '');
    $direccion = sanitize($_POST['direccion'] ?? '');
    $pass_nueva = $_POST['password'] ?? '';

    if (!empty($nombre) && !empty($email)) {
        if (!empty($pass_nueva)) {
            $pass_hash = password_hash($pass_nueva, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE usuarios SET nombre_completo = ?, email = ?, telefono = ?, direccion = ?, password = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("sssssi", $nombre, $email, $telefono, $direccion, $pass_hash, $usuario_id);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $stmt = $conn->prepare("UPDATE usuarios SET nombre_completo = ?, email = ?, telefono = ?, direccion = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ssssi", $nombre, $email, $telefono, $direccion, $usuario_id);
                $stmt->execute();
                $stmt->close();
            }
        }
        $mensaje = "<div class='alert alert-success'>¡Perfil actualizado con éxito!</div>";
    } else {
        $mensaje = "<div class='alert alert-danger'>El nombre y el correo son obligatorios.</div>";
    }
}

// Cargar datos del usuario
$stmt_u = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$usuario = null;
if ($stmt_u) {
    $stmt_u->bind_param("i", $usuario_id);
    $stmt_u->execute();
    $usuario = $stmt_u->get_result()->fetch_assoc();
    $stmt_u->close();
}

if (!$usuario) {
    header("Location: index.php");
    exit();
}

$titulo_pagina = 'Mi Perfil - Manihabs';
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

    body { background-color: var(--bg-body); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

    .profile-container { max-width: 800px; margin: 30px auto; padding: 0 20px; }

    .profile-header {
        background: linear-gradient(135deg, var(--red-dark), var(--red-primary));
        color: white; padding: 20px 25px; border-radius: 10px;
        display: flex; justify-content: space-between; align-items: center;
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2); margin-bottom: 25px;
    }

    .profile-header h2 { margin: 0; font-size: 1.5rem; font-weight: 700; }

    .btn-back {
        background-color: var(--yellow-primary); color: #000; padding: 8px 16px;
        border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 0.9rem;
    }
    .btn-back:hover { background-color: var(--yellow-dark); }

    .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }

    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; color: #495057; margin-bottom: 6px; }
    .form-control { width: 100%; padding: 10px 12px; border: 1px solid #ced4da; border-radius: 6px; font-size: 0.95rem; box-sizing: border-box; }
    .form-control:focus { border-color: var(--red-primary); outline: none; }

    .btn-submit {
        background-color: var(--red-primary); color: white; border: none; padding: 12px 20px;
        border-radius: 6px; font-size: 1rem; font-weight: 700; cursor: pointer; width: 100%;
    }
    .btn-submit:hover { background-color: var(--red-dark); }

    .alert { padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; }
    .alert-success { background: #d1e7dd; color: #0f5132; }
    .alert-danger { background: #f8d7da; color: #842029; }
</style>

<div class="profile-container">

    <div class="profile-header">
        <h2>Mi Perfil</h2>
        <!-- Redirección dinámica -->
        <a href="<?php echo $dashboard_url; ?>" class="btn-back">← Volver al Dashboard</a>
    </div>

    <?php echo $mensaje; ?>

    <div class="card">
        <form method="POST" action="perfil.php">
            
            <div class="form-group">
                <label>Nombre Completo</label>
                <input type="text" name="nombre_completo" class="form-control" value="<?php echo htmlspecialchars($usuario['nombre_completo'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label>Correo Electrónico</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="telefono" class="form-control" value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Dirección</label>
                <input type="text" name="direccion" class="form-control" value="<?php echo htmlspecialchars($usuario['direccion'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Nueva Contraseña <small style="text-transform: none; color: #888;">(Dejar en blanco para no cambiar)</small></label>
                <input type="password" name="password" class="form-control" placeholder="••••••••">
            </div>

            <button type="submit" name="actualizar_perfil" class="btn-submit">Guardar Cambios</button>

        </form>
    </div>

</div>

</body>
</html>