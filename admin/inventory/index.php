<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole('admin');
$pdo = getPDO();
$products = $pdo->query("SELECT * FROM products ORDER BY name")->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Inventory - Coffee POS</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"><link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet"></head><body>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:250px;">
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5">
<h2>Inventory</h2>
<div class="table-responsive"><table class="table table-hover"><thead><tr><th>Product</th><th>Category</th><th>Size</th><th>Stock</th><th>Price</th><th>Actions</th></tr></thead><tbody>
<?php foreach($products as $p): ?>
<tr><td><?php echo htmlspecialchars($p['name']); ?></td><td><?php echo htmlspecialchars($p['category_name'] ?? ''); ?></td><td><?php echo htmlspecialchars($p['size'] ?? '-'); ?></td><td class="<?php echo $p['stock_quantity'] < 10 ? 'text-danger fw-bold' : ''; ?>"><?php echo $p['stock_quantity']; ?></td><td><?php echo formatMoney($p['price']); ?></td><td><?php if ($_SESSION['role'] === 'admin'): ?><a href="<?php echo BASE_URL; ?>/admin/inventory/add-stock.php?product_id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary">Add Stock</a><?php else: ?><span class="text-muted">-</span><?php endif; ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
</main>
</div>
<?php include __DIR__ . '/../../includes/footer-admin.php'; ?>
