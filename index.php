<?php
// Reporte de errores para desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar conexión de BD
require_once 'config/db.php';

if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
    }
}

$error_login = "";

// PROCESAR INICIO DE SESIÓN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_btn'])) {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();
            $user = $res->fetch_assoc();
            $stmt->close();

            // Compatibilidad para hash (password_verify) o texto plano (Demo)
            if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['nombre']     = $user['nombre_completo'];
                $_SESSION['rol']        = strtolower($user['rol'] ?? 'cliente');

                // Redireccionar según el Rol
                if ($_SESSION['rol'] === 'proveedor' || $_SESSION['rol'] === 'admin') {
                    header("Location: dashboard_proveedor.php");
                } else {
                    header("Location: dashboard_cliente.php");
                }
                exit();
            } else {
                $error_login = "Correo o contraseña incorrectos.";
            }
        } else {
            $error_login = "Error en la consulta a la base de datos.";
        }
    } else {
        $error_login = "Por favor, completa todos los campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Manihabs</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }

        .login-card {
            background: white;
            width: 100%;
            max-width: 420px;
            padding: 35px 30px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            border-top: 6px solid var(--red-primary);
        }

        .brand-logo {
            text-align: center;
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--red-primary);
            margin-bottom: 5px;
        }

        .brand-logo span {
            color: var(--yellow-primary);
        }

        .login-subtitle {
            text-align: center;
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            color: var(--dark-accent);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 0.95rem;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            border-color: var(--red-primary);
            outline: none;
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, var(--red-dark), var(--red-primary));
            color: white;
            border: none;
            padding: 12px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .btn-login:hover {
            opacity: 0.95;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #842029;
            padding: 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            border: 1px solid #f5c2c7;
            text-align: center;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="brand-logo">MANIHABS <span>v2</span></div>
    <div class="login-subtitle">Ingresa a tu cuenta para continuar</div>

    <?php if (!empty($error_login)): ?>
        <div class="alert-error"><?php echo $error_login; ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php">
        <div class="form-group">
            <label>Correo Electrónico</label>
            <input type="email" name="email" class="form-control" placeholder="correo@ejemplo.com" required>
        </div>

        <div class="form-group">
            <label>Contraseña</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>

        <button type="submit" name="login_btn" class="btn-login">Iniciar Sesión</button>
    </form>
</div>

</body>
</html>