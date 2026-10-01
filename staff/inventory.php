<?php
require_once __DIR__ . '/../includes/functions.php';
requireStaff();
$pdo = getPDO();
$products = $pdo->query("SELECT p.*, c.name as category_name, COUNT(oi.id) as total_sold FROM products p JOIN categories c ON p.category_id = c.id LEFT JOIN order_items oi ON p.id = oi.product_id WHERE p.is_active = 1 GROUP BY p.id ORDER BY p.name")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll();
$taxRate = 0;
$taxStmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'tax_rate'");
$taxStmt->execute();
$taxRate = (float)($taxStmt->fetchColumn() ?? 0);
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Inventory - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
<style>
body{background:#FFF8F0;font-family:'Segoe UI','Khmer OS',Tahoma,sans-serif}
.inventory-page{padding:20px}
.inventory-card{background:#fff;border:none;border-radius:16px;box-shadow:0 4px 20px rgba(62,39,35,.08);overflow:hidden}
.inventory-card .card-body{padding:20px}
.stock-badge-inline{padding:4px 12px;border-radius:12px;font-size:.8rem;font-weight:700}
.stock-in{background:#d4edda;color:#155724}
.stock-low{background:#fff3cd;color:#856404}
.stock-out{background:#f8d7da;color:#721c24}
</style>
</head><body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:260px;">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5 inventory-page">
<div class="page-header fade-down">
    <h2><i class="bi bi-box-seam"></i> Inventory</h2>
</div>
<div class="stats-grid mb-4 fade-up">
<div class="stat-card"><div class="stat-icon" style="background:#e8f5e9;color:#198754"><i class="bi bi-box"></i></div><div class="stat-info"><h3><?php echo count($products); ?></h3><p>Total Products</p></div></div>
<div class="stat-card"><div class="stat-icon" style="background:#fff3cd;color:#ff9800"><i class="bi bi-exclamation-triangle"></i></div><div class="stat-info"><h3><?php echo count(array_filter($products, fn($p) => $p['stock_quantity'] > 0 && $p['stock_quantity'] <= 10)); ?></h3><p>Low Stock</p></div></div>
<div class="stat-card"><div class="stat-icon" style="background:#f8d7da;color:#721c24"><i class="bi bi-x-circle"></i></div><div class="stat-info"><h3><?php echo count(array_filter($products, fn($p) => $p['stock_quantity'] <= 0)); ?></h3><p>Out of Stock</p></div></div>
</div>
<div class="card shadow-sm fade-up">
<div class="card-body">
<div class="table-responsive">
<table class="table table-hover">
<thead><tr><th>#</th><th>Product</th><th>Category</th><th>SKU</th><th>Price</th><th>Stock</th><th>Sold</th><th>Status</th></tr></thead>
<tbody>
<?php foreach($products as $p): ?>
<tr>
<td class="fw-bold"><?php echo $p['id']; ?></td>
<td><strong><?php echo htmlspecialchars($p['name']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($p['description'] ?? ''); ?></small></td>
<td><?php echo htmlspecialchars($p['category_name']); ?></td>
<td><code><?php echo htmlspecialchars($p['sku'] ?? 'N/A'); ?></code></td>
<td class="fw-bold" style="color:#6F4E37"><?php echo formatMoney($p['price']); ?></td>
<td><?php echo $p['stock_quantity']; ?></td>
<td><?php echo $p['total_sold'] ?? 0; ?></td>
<td><span class="stock-badge-inline <?php echo $p['stock_quantity'] > 10 ? 'stock-in' : ($p['stock_quantity'] > 0 ? 'stock-low' : 'stock-out'); ?>"><?php echo $p['stock_quantity'] > 10 ? 'In Stock' : ($p['stock_quantity'] > 0 ? 'Low Stock' : 'Out of Stock'); ?></span></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
</div></div>
</main></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>