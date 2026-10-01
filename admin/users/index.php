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
    $where = 'WHERE full_name LIKE ? OR email LIKE ?';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
$stmt = $pdo->prepare("SELECT * FROM users $where ORDER BY full_name LIMIT ? OFFSET ?");
$stmt->execute([...$params, $perPage, $offset]);
$users = $stmt->fetchAll();
$totalCount = $pdo->prepare("SELECT COUNT(*) FROM users $where");
$totalCount->execute(array_slice($params, 0, -2));
$totalPages = ceil($totalCount->fetchColumn() / $perPage);
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Users - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
</head><body>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:260px;">
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5">
<div class="page-header fade-down">
    <h2><i class="bi bi-people"></i> Users</h2>
    <a href="<?php echo BASE_URL; ?>/admin/users/create.php" class="btn btn-primary"><i class="bi bi-plus"></i> Add User</a>
</div>
<div class="filter-bar fade-up mb-4">
    <div class="search-box flex-fill">
        <i class="bi bi-search"></i>
        <input type="text" class="form-control" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>" onkeydown="if(event.key==='Enter')location.href='?search='+this.value" style="border-radius:50px;padding-left:40px">
    </div>
</div>
<div class="table-responsive fade-up">
<table class="table table-hover">
<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($users as $u): ?>
<tr class="cart-item-row">
<td class="fw-bold"><?php echo $u['id']; ?></td>
<td><strong><?php echo htmlspecialchars($u['full_name']); ?></strong></td>
<td><?php echo htmlspecialchars($u['email']); ?></td>
<td><span class="badge badge-custom"><?php echo ucfirst($u['role']); ?></span></td>
<td><span class="<?php echo $u['is_active'] ? 'badge-active' : 'badge-inactive'; ?>"><?php echo $u['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
<td>
<div class="btn-group">
<a href="<?php echo BASE_URL; ?>/admin/users/view.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-primary action-btn view" title="View"><i class="bi bi-eye"></i></a>
<a href="<?php echo BASE_URL; ?>/admin/users/edit.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-secondary action-btn edit" title="Edit"><i class="bi bi-pencil"></i></a>
<a href="<?php echo BASE_URL; ?>/admin/users/toggle.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-warning action-btn" title="Toggle"><i class="bi bi-toggle-on"></i></a>
<a href="<?php echo BASE_URL; ?>/admin/users/delete.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-danger action-btn delete" onclick="return confirm('Delete this user?')" title="Delete"><i class="bi bi-trash"></i></a>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
<?php echo renderPagination($page, $totalPages, '?search=' . $search . '&page='); ?>
</main></div>
<?php include __DIR__ . '/../../includes/footer-admin.php'; ?>