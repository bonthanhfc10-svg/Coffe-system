<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole('admin');
$pdo = getPDO();
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT o.*, ui.full_name as user_name FROM orders o LEFT JOIN users ui ON o.user_id = ui.id WHERE o.id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) redirect(BASE_URL . '/admin/orders/index.php');
$items = $pdo->prepare("SELECT oi.*, p.name as product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();
$statusColors = ['pending' => 'warning', 'confirmed' => 'info', 'preparing' => 'primary', 'ready' => 'dark', 'completed' => 'success', 'cancelled' => 'danger'];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Order Details - Coffee POS</title><link href="https://fonts.googleapis.com/css2?family=Khmer+OS&display=swap" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"><link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet"></head><body>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:250px;">
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5">
<div class="d-flex justify-content-between align-items-center mb-4">
<h2>Order #<?php echo htmlspecialchars($order['order_number']); ?></h2>
<a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>
<div class="row g-3 mb-4">
<div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="small text-muted mb-1">Customer</div><p class="mb-0 fw-bold"><?php echo htmlspecialchars($order['user_name']); ?></p></div></div></div>
<div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="small text-muted mb-1">Status</div><p class="mb-0"><span class="badge bg-<?php echo $statusColors[$order['status']] ?? 'secondary'; ?> fs-6"><?php echo ucfirst($order['status']); ?></span></p></div></div></div>
<div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="small text-muted mb-1">Total</div><p class="mb-0 fw-bold text-danger fs-5"><?php echo formatMoney($order['total_amount']); ?></p></div></div></div>
<?php if($order['discount'] > 0): ?><div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="small text-muted mb-1">Discount</div><p class="mb-0 text-success fw-bold">-<?php echo formatMoney($order['discount']); ?></p></div></div></div>
<div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="small text-muted mb-1">Grand Total</div><p class="mb-0 fw-bold fs-5"><?php echo formatMoney($order['total_amount'] - $order['discount']); ?></p></div></div></div>
<?php endif; ?>
</div>
<div class="row g-3 mb-4">
<div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="small text-muted mb-1">Payment</div><p class="mb-0"><?php echo ucfirst($order['payment_method']); ?></p></div></div></div>
<div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="small text-muted mb-1">Date</div><p class="mb-0"><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p></div></div></div>
<div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="small text-muted mb-1">Order ID</div><p class="mb-0"><?php echo $order['id']; ?></p></div></div></div>
</div>
<div class="card shadow-sm mb-4">
<div class="card-header bg-light"><h5 class="mb-0"><i class="bi bi-cup"></i> Order Items</h5></div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead class="table-light"><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
<tbody>
<?php foreach($items as $i): ?>
<tr><td><?php echo htmlspecialchars($i['product_name']); ?></td><td><?php echo $i['quantity']; ?></td><td><?php echo formatMoney($i['unit_price']); ?></td><td class="fw-bold"><?php echo formatMoney($i['subtotal']); ?></td></tr>
<?php endforeach; ?>
<tr class="table-success fw-bold"><td colspan="3" class="text-end">Total</td><td><?php echo formatMoney($order['total_amount']); ?></td></tr>
<?php if($order['discount'] > 0): ?>
<tr class="table-warning fw-bold"><td colspan="3" class="text-end">Discount</td><td>-<?php echo formatMoney($order['discount']); ?></td></tr>
<tr class="table-success fw-bold"><td colspan="3" class="text-end">Grand Total</td><td><?php echo formatMoney($order['total_amount'] - $order['discount']); ?></td></tr>
<?php endif; ?>
</tbody></table>
</div>
</div>
</div>
<div class="card shadow-sm">
<div class="card-body">
<div class="d-flex justify-content-between align-items-center">
<div>
<small class="text-muted">Notes:</small>
<p class="mb-0"><?php echo htmlspecialchars($order['notes'] ?? 'No notes'); ?></p>
</div>
<a href="update.php?id=<?php echo $order['id']; ?>" class="btn btn-primary">Update Status</a>
</div>
</div>
</div>
</main>
</div>
<?php include __DIR__ . '/../../includes/footer-admin.php'; ?>