<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole('admin');
$pdo = getPDO();
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) redirect(BASE_URL . '/admin/products/index.php');
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>View Product - Coffee POS</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"><link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet"></head><body>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:250px;">
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5">
<h2><?php echo htmlspecialchars($product['name']); ?></h2>
<div class="card" style="max-width:600px;">
<div class="card-body">
<?php if ($product['image']): ?><img src="<?php echo BASE_URL . '/public/uploads/products/' . $product['image']; ?>" onerror="this.onerror=null; this.src='/Coffee POS/public/images/product-placeholder.png'" class="img-fluid rounded mb-3" style="max-height:300px;object-fit:cover;"><?php endif; ?>
<p><strong>Category:</strong> <?php echo htmlspecialchars($product['category_name']); ?></p>
<p><strong>Size:</strong> <?php echo htmlspecialchars(implode(' · ', getProductSizes($product['size'])) ?: '-'); ?></p>
<p><strong>Price:</strong> <?php echo formatMoney($product['price']); ?></p>
<p><strong>Stock:</strong> <?php echo $product['stock_quantity']; ?></p>
<p><strong>SKU:</strong> <?php echo htmlspecialchars($product['sku']); ?></p>
<p><strong>Description:</strong> <?php echo htmlspecialchars($product['description']); ?></p>
<p><strong>Status:</strong> <span class="<?php echo $product['is_active'] ? 'text-success' : 'text-danger'; ?>"><?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?></span></p>
</div></div>
<a href="<?php echo BASE_URL; ?>/admin/products/edit.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">Edit</a>
</main>
</div>
<?php include __DIR__ . '/../../includes/footer-admin.php'; ?>
