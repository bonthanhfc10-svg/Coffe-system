<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
allowRoles(['admin', 'staff']);
$pdo = getPDO();
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$totalProducts = [];
foreach ($categories as $c) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $stmt->execute([$c['id']]);
    $totalProducts[$c['id']] = $stmt->fetchColumn();
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Categories - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
</head><body>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:260px;">
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5">
<div class="page-header fade-down">
    <h2><i class="bi bi-tags"></i> Categories</h2>
    <a href="<?php echo BASE_URL; ?>/admin/categories/create.php" class="btn btn-primary"><i class="bi bi-plus"></i> Add Category</a>
</div>
<div class="row g-4 fade-up">
<?php foreach($categories as $c): ?>
<div class="col-md-4">
<div class="card shadow-sm hover-shadow" style="transition:var(--transition)">
<?php if ($c['image']): ?>
<img src="<?php echo BASE_URL; ?>/public/uploads/categories/<?php echo htmlspecialchars($c['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($c['name']); ?>" style="height:200px;object-fit:cover;">
<?php else: ?>
<div style="height:200px;background:linear-gradient(135deg,#6f4e37,#a0785a);display:flex;align-items:center;justify-content:center"><i class="bi bi-tags" style="font-size:3rem;color:rgba(255,255,255,.5)"></i></div>
<?php endif; ?>
<div class="card-body">
<h5 class="fw-bold"><?php echo htmlspecialchars($c['name']); ?></h5>
<p class="text-muted small"><?php echo htmlspecialchars($c['description']); ?></p>
<div class="d-flex justify-content-between align-items-center mt-3">
<small class="badge-custom"><?php echo $totalProducts[$c['id']] ?? 0; ?> products</small>
<div>
<a href="<?php echo BASE_URL; ?>/admin/categories/edit.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary action-btn edit"><i class="bi bi-pencil"></i></a>
<a href="<?php echo BASE_URL; ?>/admin/categories/delete.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-danger action-btn delete" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
</div>
</div>
</div></div></div>
<?php endforeach; ?>
</div>
<?php if (empty($categories)): ?>
<div class="alert alert-info text-center fade-up">No categories found</div>
<?php endif; ?>
</main></div>
<?php include __DIR__ . '/../../includes/footer-admin.php'; ?>