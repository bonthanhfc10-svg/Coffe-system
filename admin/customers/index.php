<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole('admin');
$pdo = getPDO();
$customers = $pdo->query("SELECT c.*, u.full_name, u.email FROM customers c JOIN users u ON c.user_id = u.id ORDER BY c.total_spent DESC")->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Customers - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
</head><body>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:260px;">
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5">
<div class="page-header fade-down">
    <h2><i class="bi bi-people"></i> Customers</h2>
</div>
<div class="card shadow-sm fade-up">
<div class="card-body">
<div class="table-responsive">
<table class="table table-hover">
<thead><tr><th>Name</th><th>Email</th><th>Orders</th><th>Total Spent</th><th>Joined</th></tr></thead>
<tbody>
<?php foreach($customers as $c): ?>
<tr class="cart-item-row">
<td><strong><?php echo htmlspecialchars($c['full_name']); ?></strong></td>
<td><?php echo htmlspecialchars($c['email']); ?></td>
<td class="fw-bold"><?php echo $c['total_orders'] ?? 0; ?></td>
<td class="fw-bold" style="color:#6f4e37"><?php echo formatMoney($c['total_spent'] ?? 0); ?></td>
<td><?php echo date('M d, Y', strtotime($c['created_at'] ?? 'now')); ?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
</div></div>
</main></div>
<?php include __DIR__ . '/../../includes/footer-admin.php'; ?>