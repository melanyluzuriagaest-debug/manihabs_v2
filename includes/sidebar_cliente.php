<?php
/**
 * Manihabs v2 - Sidebar del Cliente
 */

declare(strict_types=1);

$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="app-sidebar">
    <a href="dashboard_cliente.php" class="sidebar-brand">
        <i class="fa-solid fa-box-open" style="color: var(--primary-color);"></i>
        <span>MANIHABS</span>
    </a>
    <ul class="sidebar-menu">
        <li class="<?= $current_page === 'dashboard_cliente.php' ? 'active' : '' ?>">
            <a href="dashboard_cliente.php">
                <i class="fa-solid fa-chart-line"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="<?= $current_page === 'hacer_pedido.php' ? 'active' : '' ?>">
            <a href="hacer_pedido.php">
                <i class="fa-solid fa-cart-plus"></i>
                <span>Nuevo Pedido</span>
            </a>
        </li>
        <li class="<?= in_array($current_page, ['mis_pedidos.php', 'detalle_pedido.php']) ? 'active' : '' ?>">
            <a href="mis_pedidos.php">
                <i class="fa-solid fa-receipt"></i>
                <span>Mis Pedidos</span>
            </a>
        </li>
        <li class="<?= $current_page === 'perfil.php' ? 'active' : '' ?>">
            <a href="perfil.php">
                <i class="fa-solid fa-user-gear"></i>
                <span>Mi Perfil</span>
            </a>
        </li>
        <li>
            <a href="logout.php">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Cerrar Sesión</span>
            </a>
        </li>
    </ul>
</aside>

<div class="app-main">
    <header class="app-header">
        <div>
            <strong>Módulo de Cliente</strong>
        </div>
        <div class="header-user">
            <span>Hola, <strong><?= $userName ?></strong></span>
            <div class="user-avatar"><?= $avatarInitial ?></div>
        </div>
    </header>
    <main class="app-content">