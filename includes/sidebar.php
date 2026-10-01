<?php if (isLoggedIn() && in_array($_SESSION['role'], ['admin', 'staff'])): ?>
<div class="sidebar" id="sidePanel">
    <div class="sidebar-header">
        <h4><i class="bi bi-speedometer2"></i> <?php echo ucfirst($_SESSION['role']); ?> Panel</h4>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/<?php echo $_SESSION['role']; ?>/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/admin/users"><i class="bi bi-people"></i> Users</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/admin/products"><i class="bi bi-cup"></i> Products</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/admin/categories"><i class="bi bi-tags"></i> Categories</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/admin/inventory"><i class="bi bi-box-seam"></i> Inventory</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/admin/orders"><i class="bi bi-receipt"></i> Orders</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/admin/customers"><i class="bi bi-people"></i> Customers</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/admin/reports/sales.php"><i class="bi bi-bar-chart"></i> Reports</a></li>
        <?php endif; ?>
        <?php if ($_SESSION['role'] === 'staff'): ?>
            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/staff/pos.php"><i class="bi bi-cash-coin"></i> POS</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/staff/orders.php"><i class="bi bi-receipt"></i> Orders</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/staff/inventory.php"><i class="bi bi-box-seam"></i> Inventory</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/staff/customers.php"><i class="bi bi-people"></i> Customers</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/staff/reports.php"><i class="bi bi-bar-chart"></i> Reports</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/<?php echo $_SESSION['role'] === 'admin' ? 'admin/settings.php' : 'staff/profile.php'; ?>"><i class="bi bi-person"></i> Profile</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="<?php echo BASE_URL; ?>/auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
    </ul>
</div>
<?php endif; ?>