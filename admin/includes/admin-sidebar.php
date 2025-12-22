<?php
// Admin sidebar component
// Include this file at the beginning of admin pages after opening body tag
?>

<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="../../icons/logo_darling.svg" alt="Darling" class="sidebar-logo-icon">
        <span>Darling Admin</span>
    </div>
    <ul class="sidebar-menu">
        <li class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
            <a href="dashboard.php">Dashboard</a>
        </li>
        <li class="<?= basename($_SERVER['PHP_SELF']) === 'order-management.php' ? 'active' : '' ?>">
            <a href="order-management.php">Orders</a>
        </li>
        <li class="<?= basename($_SERVER['PHP_SELF']) === 'customer-management.php' ? 'active' : '' ?>">
            <a href="customer-management.php">Customers</a>
        </li>
        <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'operation_staff'): ?>
        <li class="<?= basename($_SERVER['PHP_SELF']) === 'product-management.php' ? 'active' : '' ?>">
            <a href="product-management.php">Products</a>
        </li>
        <?php endif; ?>
        <li class="<?= basename($_SERVER['PHP_SELF']) === 'staff-management.php' ? 'active' : '' ?>">
            <a href="staff-management.php">Staff</a>
        </li>
        <li>
            <a href="logout.php">Logout</a>
        </li>
    </ul>
</aside>
