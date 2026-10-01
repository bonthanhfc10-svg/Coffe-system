<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
allowRoles(['admin', 'staff']);
$pdo = getPDO();
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT u.*, c.total_orders, c.total_spent, c.phone, c.address FROM users u LEFT JOIN customers c ON u.id = c.user_id WHERE u.id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) redirect(BASE_URL . '/staff/customers.php');
$orders = $pdo->prepare("SELECT o.*, SUM(oi.quantity * oi.unit_price) as total FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id WHERE o.user_id = ? GROUP BY o.id ORDER BY o.created_at DESC LIMIT 5");
$orders->execute([$id]);
$orders = $orders->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Customer - Coffee POS</title><link href="https://fonts.googleapis.com/css2?family=Khmer+OS&display=swap" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"><link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet"></head><body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:250px;">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5">
<div class="d-flex justify-content-between align-items-center mb-4">
<h2><i class="bi bi-person"></i> Customer</h2>
<a href="customers.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>
<div class="row g-3 mb-4">
<div class="col-md-4"><div class="card"><div class="card-body text-center"><h6 class="text-muted">Full Name</h6><p class="fs-4 fw-bold"><?php echo htmlspecialchars($user['full_name']); ?></p></div></div></div>
<div class="col-md-4"><div class="card"><div class="card-body text-center"><h6 class="text-muted">Email</h6><p class="fs-5"><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></p></div></div></div>
<div class="col-md-4"><div class="card"><div class="card-body text-center"><h6 class="text-muted">Total Orders</h6><p class="fs-4 fw-bold"><?php echo $user['total_orders'] ?? 0; ?></p></div></div></div>
</div>
<div class="row g-3 mb-4">
<div class="col-md-4"><div class="card"><div class="card-body text-center"><h6 class="text-muted">Total Spent</h6><p class="fs-4 text-danger fw-bold"><?php echo formatMoney($user['total_spent'] ?? 0); ?></p></div></div></div>
<div class="col-md-4"><div class="card"><div class="card-body text-center"><h6 class="text-muted">Phone</h6><p class="fs-5"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></p></div></div></div>
<div class="col-md-4"><div class="card"><div class="card-body text-center"><h6 class="text-muted">Address</h6><p class="fs-5"><?php echo htmlspecialchars($user['address'] ?? 'N/A'); ?></p></div></div></div>
</div>
<div class="card shadow-sm">
<div class="card-header bg-light"><h5 class="mb-0"><i class="bi bi-receipt"></i> Recent Orders</h5></div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead class="table-light"><tr><th>Order #</th><th>Date</th><th>Total</th><th>Status</th></tr></thead>
<tbody>
<?php foreach($orders as $o): ?>
<tr><td><?php echo htmlspecialchars($o['order_number']); ?></td><td><?php echo date('M d, Y', strtotime($o['created_at'])); ?></td><td class="fw-bold"><?php echo formatMoney($o['total_amount']); ?></td><td><span class="badge bg-success"><?php echo ucfirst($o['status']); ?></span></td></tr>
<?php endforeach; ?>
<?php if (empty($orders)): ?>
<tr><td colspan="4" class="text-center text-muted">No orders found</td></tr>
<?php endif; ?>
</tbody></table>
</div>
</div>
</div>
</main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>