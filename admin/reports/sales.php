<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole('admin');
$pdo = getPDO();
require __DIR__ . '/../../includes/sales-report-data.php';
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Sales Report - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/report-print.css" rel="stylesheet">
</head><body>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:260px;">
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5">
<div class="page-header fade-down">
    <h2><i class="bi bi-bar-chart"></i> Sales Report</h2>
    <span class="badge-custom no-print"><?php echo ucfirst($filterType); ?></span>
</div>
<?php include __DIR__ . '/../../includes/sales-report-head.php'; ?>
<div class="card shadow-sm mb-4 fade-up">
<div class="card-body"><canvas id="salesChart"></canvas></div>
</div>
<div class="card shadow-sm fade-up">
<div class="card-body">
<h5 class="fw-bold mb-3"><i class="bi bi-table"></i> Report Details</h5>
<div class="table-responsive">
<table class="table table-hover">
<thead><tr><th><?php echo $labelHeading; ?></th><th>Orders</th><th>Revenue</th><th>Discount</th><th>Net</th></tr></thead>
<tbody>
<?php foreach ($reportData as $row): ?>
<tr class="cart-item-row"><td><?php echo htmlspecialchars($row['date']); ?></td><td class="fw-bold"><?php echo $row['orders']; ?></td><td><?php echo formatMoney($row['revenue']); ?></td><td><?php echo formatMoney($row['discount']); ?></td><td class="fw-bold"><?php echo formatMoney($row['revenue'] - $row['discount']); ?></td></tr>
<?php endforeach; ?>
<?php if (empty($reportData)): ?>
<tr><td colspan="5" class="text-center"><div class="empty-state"><i class="bi bi-inbox"></i><h4>No data found</h4></div></td></tr>
<?php endif; ?>
</tbody></table>
</div>
</div></div>
<?php include __DIR__ . '/../../includes/sales-report-foot.php'; ?>
</main></div>
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
const data = <?php echo json_encode($reportData); ?>;
new Chart(ctx, {
type: 'bar',
data: {
labels: data.map(function(d){ return d.date; }),
datasets: [
{label:'Revenue',data:data.map(function(d){ return parseFloat(d.revenue); }),backgroundColor:'#6f4e37',borderRadius:8},
{label:'Discount',data:data.map(function(d){ return parseFloat(d.discount); }),backgroundColor:'#e74c3c',borderRadius:8}
]
},
options:{responsive:true,plugins:{legend:{position:'bottom'}},scales:{y:{beginAtZero:true,grid:{color:'#f0f0f0'}},x:{grid:{display:false}}}}
});
</script>
<?php include __DIR__ . '/../../includes/footer-admin.php'; ?>
