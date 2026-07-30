<?php
/**
 * Manihabs v2 - Registro de Usuarios
 * Implementación de Seguridad OWASP & Diseño Moderno
 */

declare(strict_types=1);
require_once __DIR__ . '/config/db.php';

// Si ya existe sesión activa, redirigir al dashboard correspondiente
if (isset($_SESSION['user_id'], $_SESSION['user_rol'])) {
    if ($_SESSION['user_rol'] === 'proveedor') {
        header("Location: dashboard_proveedor.php");
    } else {
        header("Location: dashboard_cliente.php");
    }
    exit();
}

$error = '';
$exito = '';

// Procesar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    
    // Validación Anti-CSRF
    if (!verifyCSRFToken($token)) {
        $error = 'Petición no válida. Por favor intente nuevamente.';
    } else {
        $nombre   = sanitize($_POST['nombre'] ?? '');
        $email    = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $rol      = sanitize($_POST['rol'] ?? 'cliente');

        // Roles autorizados estrictos
        $roles_permitidos = ['cliente', 'proveedor'];

        if (empty($nombre) || !$email || empty($password)) {
            $error = 'Por favor complete todos los campos obligatorios con datos válidos.';
        } elseif (strlen($password) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres.';
        } elseif (!in_array($rol, $roles_permitidos, true)) {
            $error = 'El rol seleccionado no es válido.';
        } else {
            $db = getDBConnection();

            // Verificar si el correo ya existe
            $stmtCheck = $db->prepare("SELECT id FROM usuarios WHERE email = :email LIMIT 1");
            $stmtCheck->execute(['email' => $email]);
            
            if ($stmtCheck->fetch()) {
                $error = 'El correo electrónico ya se encuentra registrado.';
            } else {
                // Hasheo seguro BCRYPT OWASP
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                // Insertar usuario
                $stmtInsert = $db->prepare("
                    INSERT INTO usuarios (nombre, email, password, rol) 
                    VALUES (:nombre, :email, :password, :rol)
                ");
                
                $ok = $stmtInsert->execute([
                    'nombre'   => $nombre,
                    'email'    => $email,
                    'password' => $passwordHash,
                    'rol'      => $rol
                ]);

                if ($ok) {
                    $exito = '¡Cuenta creada con éxito! Ya puedes iniciar sesión.';
                } else {
                    $error = 'Ocurrió un error al registrar la cuenta. Intente nuevamente.';
                }
            }
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Manihabs v2</title>
    <link rel="stylesheet" href="css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-brand">
            <h1><i class="fa-solid fa-box-open" style="color: var(--primary-color);"></i> MANIHABS</h1>
            <p>Crea tu cuenta en la plataforma</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span><?= sanitize($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <span><?= sanitize($exito) ?></span>
            </div>
        <?php endif; ?>

        <form action="registro.php" method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

            <div class="form-group">
                <label for="nombre">Nombre Completo</label>
                <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Ej. Juan Pérez" required autofocus>
            </div>

            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="ejemplo@correo.com" required>
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" required>
            </div>

            <div class="form-group">
                <label for="rol">Tipo de Usuario</label>
                <select id="rol" name="rol" class="form-control" required>
                    <option value="cliente">Cliente</option>
                    <option value="proveedor">Proveedor</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem;">
                <i class="fa-solid fa-user-plus"></i> Registrarse
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
            ¿Ya tienes una cuenta? <a href="index.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Inicia sesión aquí</a>
        </div>
    </div>
</div>

</body>
</html>