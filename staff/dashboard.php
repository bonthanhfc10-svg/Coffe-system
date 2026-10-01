<?php
require_once __DIR__ . '/../includes/functions.php';
requireStaff();
$pdo = getPDO();
$staffId = $_SESSION['user_id'];
$stats = getStaffStats($pdo, $staffId);
$today = date('Y-m-d');
$lowStock = getLowStockProducts($pdo);
$recentOrders = getRecentOrders($pdo, 8);
$totalCustomers = getCustomerCount($pdo);
$todayOrders = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE staff_id = ? AND DATE(created_at) = ? AND status != 'cancelled'");
$todayOrders->execute([$staffId, $today]);
$todayOrderCount = $todayOrders->fetchColumn();
$pendingCount = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE staff_id = ? AND status = 'pending'");
$pendingCount->execute([$staffId]);
$pendingCount = $pendingCount->fetchColumn();
$completedCount = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE staff_id = ? AND status = 'completed'");
$completedCount->execute([$staffId]);
$completedCount = $completedCount->fetchColumn();
$thisWeekRevenue = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE staff_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$thisWeekRevenue->execute([$staffId]);
$thisWeekRevenue = $thisWeekRevenue->fetchColumn();
$thisMonthRevenue = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE staff_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$thisMonthRevenue->execute([$staffId]);
$thisMonthRevenue = $thisMonthRevenue->fetchColumn();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Staff Dashboard - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
<style>
body{background:#FFF8F0;font-family:'Segoe UI','Khmer OS',Tahoma,sans-serif}
.staff-page{padding:20px}
.staff-card{background:#fff;border:none;border-radius:16px;box-shadow:0 4px 20px rgba(62,39,35,.08);overflow:hidden;margin-bottom:20px}
.staff-card .card-header{background:linear-gradient(135deg,#3E2723,#6F4E37);color:#fff;padding:16px 24px;font-weight:700;font-size:1.05rem;border:none}
.staff-card .card-header i{margin-right:8px}
.staff-card .card-body{padding:24px}
.stat-icon{width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0}
</style>
</head><body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:260px;">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5 staff-page">
<div class="page-header fade-down">
    <h2><i class="bi bi-speedometer2"></i> Staff Dashboard</h2>
    <span class="badge-custom">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
</div>
<div class="stats-grid mb-4">
    <div class="stat-card fade-up"><div class="stat-icon" style="background:#e8f5e9;color:#198754"><i class="bi bi-bag"></i></div><div class="stat-info"><h3><?php echo $todayOrderCount; ?></h3><p>Today's Orders</p></div></div>
    <div class="stat-card fade-up"><div class="stat-icon" style="background:#e8f5e9;color:#198754"><i class="bi bi-currency-dollar"></i></div><div class="stat-info"><h3><?php echo formatMoney($stats['today_revenue']); ?></h3><p>Today's Sales</p></div></div>
    <div class="stat-card fade-up"><div class="stat-icon" style="background:#fff3e0;color:#ff9800"><i class="bi bi-clock"></i></div><div class="stat-info"><h3><?php echo $pendingCount; ?></h3><p>Pending</p></div></div>
    <div class="stat-card fade-up"><div class="stat-icon" style="background:#e8f5e9;color:#198754"><i class="bi bi-check-circle"></i></div><div class="stat-info"><h3><?php echo $completedCount; ?></h3><p>Completed</p></div></div>
    <div class="stat-card fade-up"><div class="stat-icon" style="background:#e3f2fd;color:#0d6efd"><i class="bi bi-people"></i></div><div class="stat-info"><h3><?php echo $totalCustomers; ?></h3><p>Customers</p></div></div>
    <div class="stat-card fade-up"><div class="stat-icon" style="background:#e8f5e9;color:#198754"><i class="bi bi-currency-dollar"></i></div><div class="stat-info"><h3><?php echo formatMoney($thisWeekRevenue); ?></h3><p>This Week</p></div></div>
</div>
<div class="row">
<div class="col-lg-4 mb-4 fade-up">
    <div class="staff-card">
        <div class="card-header"><i class="bi bi-exclamation-triangle"></i> Low Stock Products</div>
        <div class="card-body p-3">
            <?php if (!empty($lowStock)): ?>
            <div class="table-responsive"><table class="table table-sm table-hover mb-0">
            <thead><tr><th>Product</th><th>Stock</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach($lowStock as $p): ?>
            <tr><td><?php echo htmlspecialchars($p['name']); ?></td><td><?php echo $p['stock_quantity']; ?></td><td><span class="badge bg-danger">Low</span></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
            <?php else: ?>
            <p class="text-muted text-center">All products in good stock</p>
            <?php endif; ?>
        </div></div>
</div>
<div class="col-lg-8 mb-4 fade-up">
    <div class="staff-card">
        <div class="card-header"><i class="bi bi-receipt"></i> Recent Orders</div>
        <div class="card-body p-0">
            <div class="table-responsive">
            <table class="table table-hover mb-0">
            <thead><tr><th>Order #</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php if (!empty($recentOrders)): ?>
            <?php foreach($recentOrders as $o): ?>
            <tr><td class="fw-bold"><?php echo htmlspecialchars($o['order_number']); ?></td><td><?php echo htmlspecialchars($o['full_name'] ?? 'N/A'); ?></td><td><?php echo $o['item_count'] ?? 0; ?></td><td class="fw-bold" style="color:#6F4E37"><?php echo formatMoney($o['total_amount']); ?></td><td><span class="badge bg-warning"><?php echo ucfirst($o['status']); ?></span></td><td><small><?php echo date('M d H:i', strtotime($o['created_at'])); ?></small></td></tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr><td colspan="6" class="text-center text-muted">No recent orders</td></tr>
            <?php endif; ?>
            </tbody></table></div>
        </div></div>
</div>
</div>
</main></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>