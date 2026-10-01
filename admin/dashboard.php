<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole('admin');
$pdo = getPDO();
$todayOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$todayRevenue = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();
$pendingOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Admin Dashboard - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
</head><body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:260px;">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5">
<div class="page-header fade-down">
    <h2><i class="bi bi-speedometer2"></i> Admin Dashboard</h2>
    <span class="badge-custom"><?php echo date('l, F j, Y'); ?></span>
</div>
<div class="stats-grid mb-4">
    <div class="stat-card fade-up"><div class="stat-icon" style="background:#e3f2fd;color:#0d6efd"><i class="bi bi-bag"></i></div><div class="stat-info"><h3><?php echo $todayOrders; ?></h3><p>Today Orders</p></div></div>
    <div class="stat-card fade-up"><div class="stat-icon" style="background:#e8f5e9;color:#198754"><i class="bi bi-currency-dollar"></i></div><div class="stat-info"><h3><?php echo formatMoney($todayRevenue); ?></h3><p>Today Revenue</p></div></div>
    <div class="stat-card fade-up"><div class="stat-icon" style="background:#e3f2fd;color:#0d6efd"><i class="bi bi-cart"></i></div><div class="stat-info"><h3><?php echo $totalOrders; ?></h3><p>Total Orders</p></div></div>
    <div class="stat-card fade-up"><div class="stat-icon" style="background:#e8f5e9;color:#198754"><i class="bi bi-currency-dollar"></i></div><div class="stat-info"><h3><?php echo formatMoney($totalRevenue); ?></h3><p>Total Revenue</p></div></div>
    <div class="stat-card fade-up"><div class="stat-icon" style="background:#e3f2fd;color:#0d6efd"><i class="bi bi-people"></i></div><div class="stat-info"><h3><?php echo $totalUsers; ?></h3><p>Customers</p></div></div>
    <div class="stat-card fade-up"><div class="stat-icon" style="background:#fff3e0;color:#ff9800"><i class="bi bi-clock"></i></div><div class="stat-info"><h3><?php echo $pendingOrders; ?></h3><p>Pending</p></div></div>
</div>
<div class="row">
<div class="col-md-6 fade-up">
    <div class="card">
        <div class="card-body">
            <h5 class="fw-bold mb-3"><i class="bi bi-lightning"></i> Quick Actions</h5>
            <div class="quick-actions">
                <a href="<?php echo BASE_URL; ?>/admin/products" class="btn btn-primary btn-sm">Products</a>
                <a href="<?php echo BASE_URL; ?>/admin/orders" class="btn btn-outline-primary btn-sm">Orders</a>
                <a href="<?php echo BASE_URL; ?>/admin/customers" class="btn btn-outline-primary btn-sm">Customers</a>
                <a href="<?php echo BASE_URL; ?>/admin/reports/sales.php" class="btn btn-outline-primary btn-sm">Reports</a>
                <a href="<?php echo BASE_URL; ?>/admin/inventory" class="btn btn-outline-primary btn-sm">Inventory</a>
            </div>
        </div>
    </div>
</div>
<div class="col-md-6 fade-up">
    <div class="card">
        <div class="card-body">
            <h5 class="fw-bold mb-3"><i class="bi bi-bar-chart"></i> Revenue This Week</h5>
            <div class="d-flex align-items-end" style="height:150px;gap:8px">
                <?php
                $days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
                $values = [rand(50,200),rand(80,250),rand(60,180),rand(100,300),rand(90,220),rand(150,400),rand(120,350)];
                $maxVal = max($values);
                foreach($days as $i=>$day):
                $h = ($values[$i]/$maxVal)*120;
                ?>
                <div class="text-center flex-fill">
                    <div style="background:linear-gradient(180deg,#6f4e37,#a0785a);height:<?php echo $h; ?>px;border-radius:4px 4px 0 0;transition:height .3s" title="<?php echo $day; ?>: <?php echo formatMoney($values[$i]); ?>"></div>
                    <small class="text-muted mt-1 d-block"><?php echo $day; ?></small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
</div>
</main></div>
<?php include __DIR__ . '/../includes/footer-admin.php'; ?>
