<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
$pdo = getPDO();
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT o.*, ui.full_name as user_name FROM orders o LEFT JOIN users ui ON o.user_id = ui.id WHERE o.id = ? AND o.user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$order = $stmt->fetch();
if (!$order) redirect(BASE_URL . '/customer/orders.php');
$items = $pdo->prepare("SELECT oi.*, p.name as product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Order Details - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
</head><body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<main class="container mt-4 pt-5">
<h2 class="section-title"><i class="bi bi-receipt"></i> Order #<?php echo htmlspecialchars($order['order_number']); ?></h2>
<div class="card shadow-sm mb-4 fade-up">
<div class="card-body">
<div class="row align-items-center">
<div class="col-md-6">
<span class="badge badge-custom fs-6"><?php echo ucfirst($order['status']); ?></span>
<p class="mb-1 mt-2"><i class="bi bi-calendar"></i> <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
<p class="mb-0"><i class="bi bi-credit-card"></i> <?php echo ucfirst($order['payment_method']); ?></p>
</div>
<div class="col-md-6 text-end">
<span class="display-5 fw-bold" style="color:#6f4e37"><?php echo formatMoney($order['total_amount']); ?></span>
</div>
</div>
</div></div>
<div class="card shadow-sm fade-up">
<div class="card-body">
<h5 class="fw-bold mb-3"><i class="bi bi-bag"></i> Order Items</h5>
<div class="table-responsive">
<table class="table table-hover">
<thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
<tbody>
<?php foreach($items as $i): ?>
<tr><td class="fw-bold"><?php echo htmlspecialchars($i['product_name']); ?></td><td><?php echo $i['quantity']; ?></td><td><?php echo formatMoney($i['unit_price']); ?></td><td class="fw-bold"><?php echo formatMoney($i['subtotal']); ?></td></tr>
<?php endforeach; ?>
<tr class="table-success fw-bold"><td colspan="3" class="text-end">Total</td><td style="color:#6f4e37;font-size:1.2rem"><?php echo formatMoney($order['total_amount']); ?></td></tr>
</tbody></table>
</div>
</div></div>
<div class="mt-3 fade-up">
<a href="<?php echo BASE_URL; ?>/customer/receipt.php?id=<?php echo $id; ?>" class="btn btn-primary btn-checkout"><i class="bi bi-printer"></i> Print Receipt</a>
<a href="<?php echo BASE_URL; ?>/customer/orders.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Orders</a>
</div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>