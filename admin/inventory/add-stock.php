<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole('admin');
$pdo = getPDO();
$productId = $_GET['product_id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();
if (!$product) redirect(BASE_URL . '/admin/inventory/index.php');
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../includes/csrf.php';
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token'); }
    $qty = (int)($_POST['quantity'] ?? 0);
    $reference = trim($_POST['reference'] ?? '');
    if ($qty <= 0) { $error = 'Quantity must be positive'; } else {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?")->execute([$qty, $productId]);
            $pdo->prepare("INSERT INTO inventory_log (product_id, quantity, action, reference, created_by) VALUES (?, ?, 'in', ?, ?)")->execute([$productId, $qty, $reference, $_SESSION['user_id']]);
            $pdo->commit();
            $success = 'Stock added!';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Add Stock - Coffee POS</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"><link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet"></head><body>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:250px;">
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5">
<h2>Add Stock - <?php echo htmlspecialchars($product['name']); ?></h2>
<p>Current Stock: <?php echo $product['stock_quantity']; ?></p>
<?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<div class="card" style="max-width:500px;"><div class="card-body">
<form method="POST"><?php echo csrf_field(); ?>
<div class="mb-3"><label class="form-label">Quantity</label><input type="number" name="quantity" class="form-control" required min="1"></div>
<div class="mb-3"><label class="form-label">Reference</label><input type="text" name="reference" class="form-control"></div>
<button type="submit" class="btn btn-primary">Add Stock</button></form>
</div></div>
</main>
</div>
<?php include __DIR__ . '/../../includes/footer-admin.php'; ?>
