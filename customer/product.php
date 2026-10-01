<?php
require_once __DIR__ . '/../includes/functions.php';
$pdo = getPDO();
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.is_active = 1");
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) redirect(BASE_URL . '/customer/products.php');
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title><?php echo htmlspecialchars($product['name']); ?> - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Khmer+OS&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
</head><body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<main class="container mt-4 pt-5">
<div class="row">
<div class="col-md-6 fade-up">
<div class="card p-3 shadow-sm">
<img src="<?php echo $product['image'] ? BASE_URL.'/public/uploads/products/'.$product['image'] : BASE_URL.'/public/images/product-placeholder.png'; ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($product['name']); ?>" style="max-height:400px;object-fit:cover;width:100%;border-radius:12px" onerror="this.onerror=null; this.src='/Coffee POS/public/images/product-placeholder.png'">
</div>
</div>
<div class="col-md-6 fade-up">
<div class="card p-4 shadow-sm">
<h1 class="fw-bold mb-2"><?php echo htmlspecialchars($product['name']); ?></h1>
<p class="text-muted"><i class="bi bi-tag"></i> <?php echo htmlspecialchars($product['category_name']); ?></p>
<p class="lead"><?php echo htmlspecialchars($product['description']); ?></p>
<div class="d-flex align-items-center gap-3 mb-4">
    <?php if ($short = sizeShortLabel($product['size'])): ?>
    <span class="fs-5 text-muted"><?php echo htmlspecialchars($short); ?></span>
    <?php endif; ?>
    <span class="fs-2 fw-bold" style="color:#6f4e37"><?php echo formatMoney($product['price']); ?></span>
    <span class="<?php echo $product['stock_quantity'] > 0 ? 'text-success' : 'text-danger'; ?> fw-bold"><i class="bi bi-circle-fill"></i> <?php echo $product['stock_quantity']; ?> available</span>
</div>
</div></div>
</div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>