<?php
require_once __DIR__ . '/../includes/functions.php';
$pdo = getPDO();
$stmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Categories - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Khmer+OS&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
</head><body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<main class="container mt-4 pt-5">
<h2 class="section-title"><i class="bi bi-tags"></i> Categories</h2>
<div class="row g-4">
<?php foreach($categories as $cat): ?>
<div class="col-md-6 col-lg-4 fade-up">
<div class="card shadow-sm hover-shadow" style="transition:var(--transition)">
<?php if ($cat['image']): ?>
<img src="<?php echo BASE_URL; ?>/public/uploads/categories/<?php echo htmlspecialchars($cat['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($cat['name']); ?>" style="height:220px;object-fit:cover;">
<?php else: ?>
<div style="height:220px;background:linear-gradient(135deg,#6f4e37,#a0785a);display:flex;align-items:center;justify-content:center"><i class="bi bi-tags" style="font-size:3rem;color:rgba(255,255,255,.5)"></i></div>
<?php endif; ?>
<div class="card-body">
<h5 class="fw-bold"><?php echo htmlspecialchars($cat['name']); ?></h5>
<p class="text-muted small"><?php echo htmlspecialchars($cat['description']); ?></p>
<a href="<?php echo BASE_URL; ?>/customer/products.php?category_id=<?php echo $cat['id']; ?>" class="btn btn-primary btn-sm mt-2">View Products</a>
</div></div></div>
<?php endforeach; ?>
</div>
<?php if (empty($categories)): ?>
<div class="empty-state fade-up"><i class="bi bi-tags"></i><h4>No categories found</h4><a href="<?php echo BASE_URL; ?>/customer/products.php" class="btn btn-primary">Browse Products</a></div>
<?php endif; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>