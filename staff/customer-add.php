<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
allowRoles(['admin', 'staff']);
$pdo = getPDO();
$page = $_GET['page'] ?? 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;
$search = $_GET['search'] ?? '';
$where = [];
$params = [];
if ($search) {
    $where[] = '(full_name LIKE ? OR email LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
$where[] = "role = 'customer'";
$whereClause = 'WHERE ' . implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT * FROM users $whereClause ORDER BY full_name LIMIT ? OFFSET ?");
$stmt->execute([...$params, $perPage, $offset]);
$users = $stmt->fetchAll();
$totalCount = $pdo->prepare("SELECT COUNT(*) FROM users $whereClause");
$totalCount->execute($params);
$totalPages = ceil($totalCount->fetchColumn() / $perPage);
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Add Customer - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
<style>
body{background:#FFF8F0;font-family:'Segoe UI','Khmer OS',Tahoma,sans-serif}
.add-page{padding:20px}
.add-card{background:#fff;border:none;border-radius:16px;box-shadow:0 4px 20px rgba(62,39,35,.08);overflow:hidden}
.add-card .card-body{padding:32px}
.form-icon{position:relative}
.form-icon i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#adb5bd}
.form-icon .form-control{padding-left:42px}
</style>
</head><body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:260px;">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5 add-page">
<div class="page-header fade-down">
    <h2><i class="bi bi-person-plus"></i> Add Customer</h2>
</div>
<div class="row justify-content-center">
<div class="col-lg-6 fade-up">
<div class="add-card">
<div class="card-body">
<form method="POST" action="customer-add-process.php">
<?php echo csrf_field(); ?>
<div class="mb-3"><label class="form-label">Full Name</label><div class="form-icon"><i class="bi bi-person"></i><input type="text" name="full_name" class="form-control" required></div></div>
<div class="mb-3"><label class="form-label">Email</label><div class="form-icon"><i class="bi bi-envelope"></i><input type="email" name="email" class="form-control" required></div></div>
<div class="mb-3"><label class="form-label">Phone</label><div class="form-icon"><i class="bi bi-phone"></i><input type="text" name="phone" class="form-control"></div></div>
<div class="mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="3"></textarea></div>
<button type="submit" class="btn-checkout"><i class="bi bi-person-check"></i> Add Customer</button>
</form>
</div></div>
</div>
</div>
</main></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>