<?php
require_once __DIR__ . '/../includes/functions.php';
$pdo = getPDO();
$category_id = $_GET['category_id'] ?? null;
$search = trim($_GET['search'] ?? '');
$page = $_GET['page'] ?? 1;
$perPage = 9;
$offset = ($page - 1) * $perPage;
$where = [];
$params = [];
if ($category_id) {
    $where[] = 'p.category_id = ?';
    $params[] = $category_id;
}
if ($search !== '') {
    $where[] = 'p.name LIKE ?';
    $params[] = '%' . $search . '%';
}
$where[] = 'p.is_active = 1';
$whereClause = 'WHERE ' . implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT SQL_CALC_FOUND_ROWS p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id $whereClause ORDER BY p.name LIMIT ? OFFSET ?");
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$products = $stmt->fetchAll();
$total = $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
$totalPages = ceil($total / $perPage);
$cats = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll();
$catName = '';
if ($category_id) {
    $cStmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $cStmt->execute([$category_id]);
    $catName = $cStmt->fetchColumn();
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Products - Coffee POS</title><link href="https://fonts.googleapis.com/css2?family=Khmer+OS&display=swap" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"><link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet"></head><body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<main class="container mt-4 pt-5">
<h2><i class="bi bi-cup"></i> <?php if ($search !== ''): ?>Search results for &ldquo;<?php echo htmlspecialchars($search); ?>&rdquo;<?php else: ?><?php echo $catName ? htmlspecialchars($catName) : 'All Products'; ?><?php endif; ?></h2>
<div class="row mb-4">
<div class="col-md-4 mb-2">
<form method="get" class="search-box">
<i class="bi bi-search"></i>
<input type="text" name="search" class="form-control" placeholder="Search products by name..." value="<?php echo htmlspecialchars($search); ?>">
<?php if ($category_id): ?><input type="hidden" name="category_id" value="<?php echo (int) $category_id; ?>"><?php endif; ?>
</form>
</div>
<div class="col-md-3 mb-2">
<select class="form-select" onchange="location.href='?category_id='+this.value+'&search='+encodeURIComponent(<?php echo json_encode($search); ?>)"><option value="">All Categories</option><?php foreach($cats as $c): ?><option value="<?php echo $c['id']; ?>"<?php echo $category_id==$c['id']?' selected':''; ?>><?php echo htmlspecialchars($c['name']); ?></option><?php endforeach; ?></select>
</div>
<?php if ($search !== ''): ?>
<div class="col-md-2 mb-2"><a href="?category_id=<?php echo (int) ($category_id ?? ''); ?>" class="btn btn-outline-secondary w-100"><i class="bi bi-x-circle"></i> Clear</a></div>
<?php endif; ?>
</div>
<div class="row">
<?php foreach($products as $p): ?>
<div class="col-md-4 mb-4"><div class="card product-card h-100"><img src="<?php echo $p['image'] ? BASE_URL.'/public/uploads/products/'.$p['image'] : BASE_URL.'/public/images/product-placeholder.png'; ?>" onerror="this.onerror=null; this.src='/Coffee POS/public/images/product-placeholder.png'" class="card-img-top" alt="<?php echo htmlspecialchars($p['name']); ?>" style="height:200px;object-fit:cover;"><div class="card-body"><h5><?php echo htmlspecialchars($p['name']); ?></h5><p class="text-muted"><?php echo htmlspecialchars($p['description']); ?></p><p class="fw-bold"><?php echo formatMoney($p['price']); ?><?php if ($short = sizeShortLabel($p['size'])): ?> <small class="text-muted fw-normal"><?php echo htmlspecialchars($short); ?></small><?php endif; ?></p><a href="<?php echo BASE_URL; ?>/customer/product.php?id=<?php echo $p['id']; ?>" class="btn btn-primary btn-sm">View</a></div></div></div>
<?php endforeach; ?>
</div>
<?php if (empty($products)): ?>
<div class="alert alert-info text-center"><?php echo $search !== '' ? 'No products found for &ldquo;' . htmlspecialchars($search) . '&rdquo;' : 'No products found'; ?></div>
<?php endif; ?>
<?php echo renderPagination($page, $totalPages, '?category_id=' . ($category_id ?? '') . '&search=' . urlencode($search) . '&page='); ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
