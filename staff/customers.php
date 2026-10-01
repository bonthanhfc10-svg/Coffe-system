<?php
require_once __DIR__ . '/../includes/functions.php';
requireStaff();
$pdo = getPDO();
$page = $_GET['page'] ?? 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;
$search = $_GET['search'] ?? '';
$where = [];
$params = [];
if ($search) {
    $where[] = '(u.full_name LIKE ? OR u.email LIKE ? OR c.phone LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
$where[] = "u.role = 'customer'";
$whereClause = 'WHERE ' . implode(' AND ', $where);
$users = $pdo->prepare("SELECT u.*, c.total_orders, c.total_spent, c.phone, c.address FROM users u LEFT JOIN customers c ON u.id = c.user_id $whereClause ORDER BY u.full_name LIMIT ? OFFSET ?");
$allParams = array_merge($params, [$perPage, $offset]);
$users->execute($allParams);
$customers = $users->fetchAll();
$totalCount = $pdo->prepare("SELECT COUNT(*) FROM users u LEFT JOIN customers c ON u.id = c.user_id $whereClause");
$totalCount->execute($params);
$totalPages = ceil($totalCount->fetchColumn() / $perPage);
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Customers - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
<style>
body{background:#FFF8F0;font-family:'Segoe UI','Khmer OS',Tahoma,sans-serif}
.customers-page{padding:20px}
.customer-card{background:#fff;border:none;border-radius:16px;box-shadow:0 4px 20px rgba(62,39,35,.08);overflow:hidden}
.customer-card .card-body{padding:20px}
.customer-avatar{width:50px;height:50px;border-radius:50%;background:linear-gradient(135deg,#6F4E37,#3E2723);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:1.2rem}
</style>
</head><body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:260px;">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5 customers-page">
<div class="page-header fade-down">
    <h2><i class="bi bi-people"></i> Customers</h2>
    <a href="customer-add.php" class="btn btn-primary"><i class="bi bi-person-plus"></i> Add Customer</a>
</div>
<div class="filter-bar fade-up mb-4">
<div class="row g-2">
<div class="col-md-4"><div class="pos-search"><i class="bi bi-search"></i><input type="text" class="form-control" placeholder="Search customers..." value="<?php echo htmlspecialchars($search); ?>" onkeydown="if(event.key==='Enter')location.href='?search='+this.value"></div></div>
</div></div>
<div class="card shadow-sm fade-up">
<div class="card-body">
<div class="table-responsive">
<table class="table table-hover">
<thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Total Spent</th><th>Joined</th><th>Action</th></tr></thead>
<tbody>
<?php foreach($customers as $c): ?>
<tr class="cart-item-row">
<td><div class="d-flex align-items-center gap-2"><div class="customer-avatar"><?php echo strtoupper(substr($c['full_name'], 0, 1)); ?></div><strong><?php echo htmlspecialchars($c['full_name']); ?></strong></div></td>
<td><?php echo htmlspecialchars($c['email']); ?></td>
<td><?php echo htmlspecialchars($c['phone'] ?? 'N/A'); ?></td>
<td class="fw-bold"><?php echo $c['total_orders'] ?? 0; ?></td>
<td class="fw-bold" style="color:#6F4E37"><?php echo formatMoney($c['total_spent'] ?? 0); ?></td>
<td><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
<td><div class="btn-group">
<a href="customer-view.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
<a href="customer-edit.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
</div></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
</div></div>
<?php echo renderPagination($page, $totalPages, '?search=' . $search . '&page='); ?>
</main></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>