<?php
require_once __DIR__ . '/includes/functions.php';
$pdo = getPDO();
$stmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name LIMIT 6");
$categories = $stmt->fetchAll();
$stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1 ORDER BY p.created_at DESC LIMIT 8");
$featured = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Home - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Khmer+OS&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
</head><body>
<?php include __DIR__ . '/includes/navbar.php'; ?>
<main class="container mt-4 pt-5">
    <div class="hero-section text-center mb-5 fade-down">
        <h1 class="mb-3"><i class="bi bi-cup-fill"></i> <?php echo htmlspecialchars(siteName()); ?></h1>
        <?php if (($heroDesc = siteDescription()) !== ''): ?>
        <p class="mb-0" style="opacity:.95;font-size:1.1rem"><?php echo htmlspecialchars($heroDesc); ?></p>
        <?php endif; ?>
    </div>
    <div class="hero-banner mb-5 fade-down" style="background-image:url('<?php echo BASE_URL; ?>/Image/banner.jpg');background-size:cover;background-position:center;background-repeat:no-repeat;border-radius:12px;position:relative;min-height:280px;overflow:hidden"></div>
    <h3 class="section-title mb-4"><i class="bi bi-tag"></i> Categories</h3>
    <div class="row">
    <?php foreach ($categories as $cat): ?>
        <div class="col-md-4 mb-4 fade-up"><div class="card h-100 hover-shadow">
        <?php if ($cat['image']): ?>
        <img src="<?php echo BASE_URL; ?>/public/uploads/categories/<?php echo htmlspecialchars($cat['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($cat['name']); ?>" style="height:200px;object-fit:cover;">
        <?php endif; ?>
        <div class="card-body"><h5 class="fw-bold"><?php echo htmlspecialchars($cat['name']); ?></h5><p class="text-muted"><?php echo htmlspecialchars($cat['description']); ?></p><a href="<?php echo BASE_URL; ?>/customer/products.php?category_id=<?php echo $cat['id']; ?>" class="btn btn-primary btn-sm">View Products</a></div></div></div>
    <?php endforeach; ?>
    </div>
    <h3 class="section-title mb-4 mt-5"><i class="bi bi-star-fill"></i> Featured Products</h3>
    <div class="row">
    <?php foreach ($featured as $prod): ?>
        <div class="col-md-3 mb-4 fade-up"><div class="card product-card h-100 position-relative hover-shadow">
        <?php if ($prod['image']): ?>
        <img src="<?php echo BASE_URL; ?>/public/uploads/products/<?php echo htmlspecialchars($prod['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($prod['name']); ?>" style="height:220px;object-fit:cover;">
        <?php else: ?>
        <img src="https://via.placeholder.com/300x220?text=Coffee" class="card-img-top" alt="Coffee" style="height:220px;object-fit:cover;">
        <?php endif; ?>
        <span class="position-absolute top-0 start-0 m-2 badge-custom">Popular</span>
        <span class="position-absolute bottom-0 end-0 m-2" style="background:linear-gradient(135deg,#6f4e37,#a0785a);color:#fff;padding:4px 12px;border-radius:20px;font-size:.8rem;font-weight:700;z-index:2"><?php echo formatMoney($prod['price']); ?></span>
        <div class="card-body text-center">
            <h5 class="fw-bold"><?php echo htmlspecialchars($prod['name']); ?></h5>
            <p class="text-muted small"><?php echo htmlspecialchars($prod['category_name']); ?></p>
            <a href="<?php echo BASE_URL; ?>/customer/product.php?id=<?php echo $prod['id']; ?>" class="btn btn-sm btn-primary">View Details</a>
        </div></div></div>
    <?php endforeach; ?>
    </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>