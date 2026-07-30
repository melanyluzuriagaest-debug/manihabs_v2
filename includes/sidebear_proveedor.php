<?php
/**
 * Manihabs v2 - Sidebar del Proveedor
 */

declare(strict_types=1);

$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="app-sidebar">
    <a href="dashboard_proveedor.php" class="sidebar-brand">
        <i class="fa-solid fa-truck-ramp-box" style="color: var(--info-color);"></i>
        <span>MANIHABS PRO</span>
    </a>
    <ul class="sidebar-menu">
        <li class="<?= in_array($current_page, ['dashboard_proveedor.php', 'detalle_pedido_proveedor.php', 'cambiar_estado.php']) ? 'active' : '' ?>">
            <a href="dashboard_proveedor.php">
                <i class="fa-solid fa-boxes-packing"></i>
                <span>Gestión de Pedidos</span>
            </a>
        </li>
        <li class="<?= $current_page === 'perfil.php' ? 'active' : '' ?>">
            <a href="perfil.php">
                <i class="fa-solid fa-id-card"></i>
                <span>Mi Perfil</span>
            </a>
        </li>
        <li>
            <a href="logout.php">
                <i class="fa-solid fa-power-off"></i>
                <span>Cerrar Sesión</span>
            </a>
        </li>
    </ul>
</aside>

<div class="app-main">
    <header class="app-header">
        <div>
            <strong>Módulo de Proveedor</strong>
        </div>
        <div class="header-user">
            <span>Hola, <strong><?= $userName ?></strong></span>
            <div class="user-avatar" style="background-color: var(--info-color);"><?= $avatarInitial ?></div>
        </div>
    </header>
    <main class="app-content">
