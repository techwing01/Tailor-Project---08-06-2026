<?php
/**
 * TailorMate - Common Header
 * Includes Bootstrap, Font Awesome, sidebar, and common CSS.
 * Every authenticated page must include this.
 */

$currentPage = basename($_SERVER['PHP_SELF']);
$username = $_SESSION['username'] ?? 'User';
$appName = defined('APP_NAME') ? APP_NAME : 'TailorMate';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Page') . ' - ' . $appName ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body class="with-sidebar">

<!-- Sidebar -->
<nav class="sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-cut"></i> <?= e($appName) ?>
    </div>
    <ul class="nav flex-column sidebar-nav">
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage === 'dashboard.php') ? 'active' : '' ?>" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage === 'add_customer.php') ? 'active' : '' ?>" href="add_customer.php">
                <i class="fas fa-user-plus"></i> <span>Add Customer</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage === 'view_customers.php') ? 'active' : '' ?>" href="view_customers.php">
                <i class="fas fa-users"></i> <span>View Customers</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage === 'add_order.php') ? 'active' : '' ?>" href="add_order.php">
                <i class="fas fa-cart-plus"></i> <span>Add Order</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage === 'view_orders.php') ? 'active' : '' ?>" href="view_orders.php">
                <i class="fas fa-boxes"></i> <span>View Orders</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage === 'measurements.php') ? 'active' : '' ?>" href="measurements.php">
                <i class="fas fa-ruler"></i> <span>Measurements</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage === 'settings.php') ? 'active' : '' ?>" href="settings.php">
                <i class="fas fa-cog"></i> <span>Settings</span>
            </a>
        </li>
        <li class="nav-item mt-auto sidebar-logout">
            <a class="nav-link" href="logout.php">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        </li>
    </ul>
</nav>

<!-- Main Content -->
<div class="main-content">
