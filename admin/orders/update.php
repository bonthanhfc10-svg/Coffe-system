<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
allowRoles(['admin', 'staff']);
$pdo = getPDO();
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) redirect(BASE_URL . '/admin/orders/index.php');
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../includes/csrf.php';
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token'); }
    $status = $_POST['status'] ?? '';
    if ($status) {
        $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$status, $id]);
        $success = 'Order updated!';
    }
}
$statusColors = ['pending' => 'warning', 'confirmed' => 'info', 'preparing' => 'primary', 'ready' => 'dark', 'completed' => 'success', 'cancelled' => 'danger'];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Update Order - Coffee POS</title><link href="https://fonts.googleapis.com/css2?family=Khmer+OS&display=swap" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"><link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet"></head><body>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:250px;">
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5">
<div class="d-flex justify-content-between align-items-center mb-4">
<h2><i class="bi bi-gear"></i> Update Order</h2>
<a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>
<div class="row justify-content-center">
<div class="col-md-6">
<div class="card shadow-sm">
<div class="card-header bg-primary text-white"><h5 class="mb-0">Order #<?php echo htmlspecialchars($order['order_number']); ?></h5></div>
<div class="card-body">
<div class="d-flex justify-content-between align-items-center mb-3">
<span class="badge bg-<?php echo $statusColors[$order['status']] ?? 'secondary'; ?> fs-6"><?php echo ucfirst($order['status']); ?></span>
<span class="text-muted"><?php echo formatMoney($order['total_amount']); ?></span>
</div>
<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<form method="POST">
<?php echo csrf_field(); ?>
<div class="mb-3">
<label class="form-label">Order Status</label>
<select name="status" class="form-select" required>
<?php foreach(['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'] as $s): ?>
<option value="<?php echo $s; ?>" <?php echo $order['status']===$s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="mb-3">
<label class="form-label">Customer</label>
<input type="text" class="form-control" value="<?php echo htmlspecialchars($order['full_name'] ?? 'N/A'); ?>" disabled>
</div>
<div class="mb-3">
<label class="form-label">Payment Method</label>
<input type="text" class="form-control" value="<?php echo ucfirst($order['payment_method']); ?>" disabled>
</div>
<button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg"></i> Update Status</button>
</form>
</div>
</div>
</div>
</div>
</main>
</div>
<?php include __DIR__ . '/../../includes/footer-admin.php'; ?>