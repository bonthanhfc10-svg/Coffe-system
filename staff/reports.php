<?php
require_once __DIR__ . '/../includes/functions.php';
requireStaff();
requireLogin();
allowRoles(['admin', 'staff']);
$pdo = getPDO();
$staffId = $_SESSION['user_id'];
$today = date('Y-m-d');
$todayRevenue = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE staff_id = ? AND DATE(created_at) = ?");
$todayRevenue->execute([$staffId, $today]);
$todayRevenue = $todayRevenue->fetchColumn();
$todayOrders = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE staff_id = ? AND DATE(created_at) = ? AND status != 'cancelled'");
$todayOrders->execute([$staffId, $today]);
$todayOrders = $todayOrders->fetchColumn();
$thisWeekRevenue = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE staff_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$thisWeekRevenue->execute([$staffId]);
$thisWeekRevenue = $thisWeekRevenue->fetchColumn();
$thisMonthRevenue = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE staff_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$thisMonthRevenue->execute([$staffId]);
$thisMonthRevenue = $thisMonthRevenue->fetchColumn();
$paymentTotals = $pdo->prepare("SELECT payment_method, SUM(total_amount) as total FROM orders WHERE staff_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY payment_method");
$paymentTotals->execute([$staffId]);
$paymentTotals = $paymentTotals->fetchAll();
$weekRevenue = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) as rev, COUNT(*) as cnt FROM orders WHERE staff_id = ? AND DATE(created_at) = ?");
    $stmt->execute([$staffId, $date]);
    $weekRevenue[] = ['date' => date('D', strtotime($date)), 'revenue' => $stmt->fetchColumn(), 'orders' => $stmt->fetchColumn()];
}
$monthRevenue = [];
for ($i = 11; $i >= 0; $i--) {
    $date = date('Y-m', strtotime("-$i months"));
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) as rev FROM orders WHERE staff_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->execute([$staffId, $date]);
    $monthRevenue[] = ['date' => $date, 'revenue' => $stmt->fetchColumn()];
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Reports - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
<style>
body{background:#FFF8F0;font-family:'Segoe UI','Khmer OS',Tahoma,sans-serif}
.reports-page{padding:20px}
.report-card{background:#fff;border:none;border-radius:16px;box-shadow:0 4px 20px rgba(62,39,35,.08);overflow:hidden;margin-bottom:20px}
.report-card .card-header{background:linear-gradient(135deg,#3E2723,#6F4E37);color:#fff;padding:16px 24px;font-weight:700;border:none}
.report-card .card-body{padding:24px}
</style>
</head><body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:260px;">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5 reports-page">
<div class="page-header fade-down">
    <h2><i class="bi bi-bar-chart"></i> Reports</h2>
    <a href="<?php echo BASE_URL; ?>/staff/reports/sales.php" class="btn btn-primary" style="border-radius:12px"><i class="bi bi-bar-chart-line"></i> Sales Report</a>
</div>
<div class="stats-grid mb-4 fade-up">
<div class="stat-card"><div class="stat-icon" style="background:#e8f5e9;color:#198754"><i class="bi bi-bag"></i></div><div class="stat-info"><h3><?php echo $todayOrders; ?></h3><p>Today's Orders</p></div></div>
<div class="stat-card"><div class="stat-icon" style="background:#e8f5e9;color:#198754"><i class="bi bi-currency-dollar"></i></div><div class="stat-info"><h3><?php echo formatMoney($todayRevenue); ?></h3><p>Today's Sales</p></div></div>
<div class="stat-card"><div class="stat-icon" style="background:#e3f2fd;color:#0d6efd"><i class="bi bi-calendar-week"></i></div><div class="stat-info"><h3><?php echo formatMoney($thisWeekRevenue); ?></h3><p>This Week</p></div></div>
<div class="stat-card"><div class="stat-icon" style="background:#e8f5e9;color:#198754"><i class="bi bi-calendar"></i></div><div class="stat-info"><h3><?php echo formatMoney($thisMonthRevenue); ?></h3><p>This Month</p></div></div>
</div>
<div class="row">
<div class="col-lg-6 fade-up">
<div class="report-card">
<div class="card-header"><i class="bi bi-graph-up"></i> Weekly Revenue</div>
<div class="card-body"><canvas id="weeklyChart"></canvas></div>
</div>
</div>
<div class="col-lg-6 fade-up">
<div class="report-card">
<div class="card-header"><i class="bi bi-credit-card"></i> Payment Method Breakdown</div>
<div class="card-body"><canvas id="paymentChart"></canvas></div>
</div>
</div>
</div>
<div class="card report-card fade-up">
<div class="card-header"><i class="bi bi-table"></i> Payment Method Totals (This Month)</div>
<div class="card-body">
<div class="table-responsive">
<table class="table table-hover">
<thead><tr><th>Payment Method</th><th>Total</th></tr></thead>
<tbody>
<?php foreach($paymentTotals as $pt): ?>
<tr><td class="fw-bold"><?php echo ucfirst($pt['payment_method']); ?></td><td class="fw-bold" style="color:#6F4E37"><?php echo formatMoney($pt['total']); ?></td></tr>
<?php endforeach; ?>
</tbody></table>
</div>
</div></div>
</main></div>
<script>
const ctx = document.getElementById('weeklyChart').getContext('2d');
new Chart(ctx, {type:'bar',data:{labels:<?php echo json_encode(array_column($weekRevenue,'date')); ?>,datasets:[{label:'Revenue',data:<?php echo json_encode(array_column($weekRevenue,'revenue')); ?>,backgroundColor:'#6F4E37',borderRadius:8},{label:'Orders',data:<?php echo json_encode(array_column($weekRevenue,'orders')); ?>,backgroundColor:'#C89B6D',borderRadius:8}]},options:{responsive:true,plugins:{legend:{position:'bottom'}},scales:{y:{beginAtZero:true,grid:{color:'#f0f0f0'}},x:{grid:{display:false}}}}});
const pCtx = document.getElementById('paymentChart').getContext('2d');
new Chart(pCtx, {type:'doughnut',data:{labels:<?php echo json_encode(array_column($paymentTotals,'payment_method')); ?>,datasets:[{data:<?php echo json_encode(array_column($paymentTotals,'total')); ?>,backgroundColor:['#6F4E37','#C89B6D','#3E2723','#F5E6D3'],borderWidth:0}]},options:{responsive:true,plugins:{legend:{position:'bottom'}}}});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>