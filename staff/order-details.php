<?php
require_once __DIR__ . '/../includes/functions.php';
requireStaff();
$pdo = getPDO();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT o.*, ui.full_name as customer_name, (SELECT full_name FROM users WHERE id = o.staff_id) as staff_name FROM orders o LEFT JOIN users ui ON o.user_id = ui.id WHERE o.id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) redirect(BASE_URL . '/staff/orders.php');
$items = $pdo->prepare("SELECT oi.*, p.name as product_name, oi.size FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();
$taxRate = 0;
$taxStmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'tax_rate'");
$taxStmt->execute();
$taxRate = (float)($taxStmt->fetchColumn() ?? 0);
$subtotal = array_sum(array_column($items, 'subtotal'));
$taxAmt = $subtotal * ($taxRate / 100);
$grandTotal = $order['total_amount'];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Order Details - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
</head><body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:260px;">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5">
<h2 class="section-title"><i class="bi bi-receipt"></i> Order #<?php echo htmlspecialchars($order['order_number']); ?></h2>
<div class="row fade-up">
<div class="col-lg-8">
<div class="card shadow-sm mb-4">
<div class="card-body">
<h5 class="fw-bold mb-3"><i class="bi bi-info-circle"></i> Order Information</h5>
<div class="row">
<div class="col-md-6 mb-2"><strong>Order #:</strong> <?php echo htmlspecialchars($order['order_number']); ?></div>
<div class="col-md-6 mb-2"><strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></div>
<div class="col-md-6 mb-2"><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></div>
<div class="col-md-6 mb-2"><strong>Staff:</strong> <?php echo htmlspecialchars($order['staff_name'] ?? 'N/A'); ?></div>
<div class="col-md-6 mb-2"><strong>Order Type:</strong> <span class="badge badge-custom"><?php echo ucfirst($order['order_type'] ?? 'dine_in'); ?></span></div>
<div class="col-md-6 mb-2"><strong>Payment:</strong> <?php echo ucfirst($order['payment_method'] ?? 'cash'); ?></div>
<div class="col-md-6 mb-2"><strong>Status:</strong> <span class="badge bg-warning"><?php echo ucfirst($order['status']); ?></span></div>
<div class="col-md-6 mb-2"><strong>Payment Status:</strong> <span class="badge bg-info"><?php echo ucfirst($order['payment_status'] ?? 'pending'); ?></span></div>
<?php if ($order['order_type'] === 'dine_in'): ?>
<div class="col-md-6 mb-2"><strong>Table:</strong> <?php echo htmlspecialchars($order['table_number'] ?? 'N/A'); ?></div>
<?php endif; ?>
<?php if ($order['order_type'] === 'delivery'): ?>
<div class="col-md-6 mb-2"><strong>Delivery:</strong> <?php echo htmlspecialchars($order['delivery_customer'] ?? ''); ?></div>
<div class="col-md-6 mb-2"><strong>Phone:</strong> <?php echo htmlspecialchars($order['delivery_phone'] ?? ''); ?></div>
<div class="col-md-6 mb-2"><strong>Address:</strong> <?php echo htmlspecialchars($order['delivery_address'] ?? ''); ?></div>
<?php endif; ?>
</div>
</div></div>
<div class="card shadow-sm mb-4 fade-up">
<div class="card-body">
<h5 class="fw-bold mb-3"><i class="bi bi-bag"></i> Order Items</h5>
<div class="table-responsive">
<table class="table table-hover">
<thead><tr><th>Product</th><th>Size</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
<tbody>
<?php foreach($items as $i): ?>
<tr><td><?php echo htmlspecialchars($i['product_name']); ?></td><td><?php echo htmlspecialchars($i['size'] ?? '-'); ?></td><td><?php echo $i['quantity']; ?></td><td><?php echo formatMoney($i['unit_price']); ?></td><td class="fw-bold"><?php echo formatMoney($i['subtotal']); ?></td></tr>
<?php endforeach; ?>
<tr class="table-success fw-bold"><td colspan="3" class="text-end">Subtotal:</td><td style="color:#6F4E37"><?php echo formatMoney($subtotal); ?></td></tr>
<?php if ((float)$order['discount'] > 0): ?>
<tr class="fw-bold"><td colspan="3" class="text-end text-danger">Discount:</td><td class="text-danger">-<?php echo formatMoney($order['discount']); ?></td></tr>
<?php endif; ?>
<tr><td colspan="3" class="text-end">Tax (<?php echo $taxRate; ?>%):</td><td><?php echo formatMoney($taxAmt); ?></td></tr>
<tr class="fw-bold" style="font-size:1.1rem;color:#6F4E37"><td colspan="3" class="text-end">Total:</td><td><?php echo formatMoney($grandTotal); ?></td></tr>
</tbody></table>
</div>
</div></div>
<div class="fade-up">
<div class="d-flex gap-2">
<form method="POST" action="update-status.php" style="display:inline">
<input type="hidden" name="order_id" value="<?php echo $id; ?>">
<?php echo csrf_field(); ?>
<select name="new_status" class="form-select d-inline-block" style="width:auto" onchange="this.form.submit()">
<option value="<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></option>
<option value="pending">Pending</option>
<option value="confirmed">Confirmed</option>
<option value="preparing">Preparing</option>
<option value="ready">Ready</option>
<option value="completed">Completed</option>
<option value="cancelled">Cancelled</option>
</select>
</form>
<a href="receipt.php?id=<?php echo $id; ?>" class="btn btn-primary"><i class="bi bi-printer"></i> Print Receipt</a>
<a href="orders.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>
</div>
</div>
</div>
</main></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>