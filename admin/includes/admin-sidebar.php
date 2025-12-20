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
        <li class="<?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>">
            <a href="orders.php">Orders</a>
        </li>
        <li class="<?= basename($_SERVER['PHP_SELF']) === 'customer-management.php' ? 'active' : '' ?>">
            <a href="customer-management.php">Customers</a>
        </li>
        <li class="<?= basename($_SERVER['PHP_SELF']) === 'product-management.php' ? 'active' : '' ?>">
            <a href="product-management.php">Products</a>
        </li>
        <li class="<?= basename($_SERVER['PHP_SELF']) === 'staff-management.php' ? 'active' : '' ?>">
            <a href="staff-management.php">Staff</a>
        </li>
        <li>
            <a href="login.php">Logout</a>
        </li>
    </ul>
</aside>
