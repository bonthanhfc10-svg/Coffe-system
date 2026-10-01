<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole('admin');
$pdo = getPDO();
$page = $_GET['page'] ?? 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;
$search = $_GET['search'] ?? '';
$where = '';
$params = [];
if ($search) {
    $where = 'WHERE p.name LIKE ? OR p.sku LIKE ?';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id $where ORDER BY p.name LIMIT ? OFFSET ?");
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$products = $stmt->fetchAll();
$totalCount = $pdo->prepare("SELECT COUNT(*) FROM products p $where");
$totalCount->execute(array_slice($params, 0, -2));
$totalPages = ceil($totalCount->fetchColumn() / $perPage);
$cats = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Products - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
</head><body>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:260px;">
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5">
<div class="page-header fade-down">
    <h2><i class="bi bi-cup"></i> Products</h2>
    <a href="<?php echo BASE_URL; ?>/admin/products/create.php" class="btn btn-primary"><i class="bi bi-plus"></i> Add Product</a>
</div>
<div class="filter-bar fade-up mb-4">
    <div class="search-box flex-fill">
        <i class="bi bi-search"></i>
        <input type="text" class="form-control" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>" onkeydown="if(event.key==='Enter')location.href='?search='+this.value" style="border-radius:50px;padding-left:40px">
    </div>
    <select class="form-select" style="width:auto" onchange="if(this.value)location.href='?category='+this.value+('<?php echo $search ? '&search='.$search : ''; ?>)">
        <option value="">All Categories</option>
        <?php foreach($cats as $c): ?>
        <option value="<?php echo $c['id']; ?>" <?php echo (isset($_GET['category']) && $_GET['category']==$c['id'])?'selected':''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="table-responsive fade-up">
<table class="table table-hover">
<thead><tr><th>#</th><th>Image</th><th>Name</th><th>Category</th><th>Size</th><th>Price</th><th>Stock</th><th>SKU</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($products as $p): ?>
<tr class="cart-item-row">
<td class="fw-bold"><?php echo $p['id']; ?></td>
<td><?php if($p['image']): ?><img src="<?php echo BASE_URL; ?>/public/uploads/products/<?php echo htmlspecialchars($p['image']); ?>" onerror="this.onerror=null; this.src='/Coffee POS/public/images/product-placeholder.png'" style="width:40px;height:40px;object-fit:cover;border-radius:8px" alt="<?php echo htmlspecialchars($p['name']); ?>"><?php else: ?><div style="width:40px;height:40px;background:#e9ecef;border-radius:8px;display:flex;align-items:center;justify-content:center"><i class="bi bi-image"></i></div><?php endif; ?></td>
<td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
<td><?php echo htmlspecialchars($p['category_name']); ?></td>
<td><?php echo htmlspecialchars(implode(' · ', getProductSizes($p['size'])) ?: '-'); ?></td>
<td class="fw-bold" style="color:#6f4e37"><?php echo formatMoney($p['price']); ?></td>
<td><?php echo $p['stock_quantity']; ?></td>
<td><code><?php echo htmlspecialchars($p['sku']); ?></code></td>
<td><span class="<?php echo $p['is_active'] ? 'badge-active' : 'badge-inactive'; ?>"><?php echo $p['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
<td>
<div class="btn-group">
<a href="<?php echo BASE_URL; ?>/admin/products/view.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary action-btn view" title="View"><i class="bi bi-eye"></i></a>
<a href="<?php echo BASE_URL; ?>/admin/products/edit.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-secondary action-btn edit" title="Edit"><i class="bi bi-pencil"></i></a>
<a href="<?php echo BASE_URL; ?>/admin/products/delete.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-danger action-btn delete" onclick="return confirm('Delete this product?')" title="Delete"><i class="bi bi-trash"></i></a>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
<?php echo renderPagination($page, $totalPages, '?search=' . $search . '&page='); ?>
</main></div>
<?php include __DIR__ . '/../../includes/footer-admin.php'; ?>