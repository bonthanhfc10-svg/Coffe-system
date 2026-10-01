<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole('admin');
$pdo = getPDO();
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) redirect(BASE_URL . '/admin/users/index.php');
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>View User - Coffee POS</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"><link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet"></head><body>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:250px;">
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5">
<h2>User: <?php echo htmlspecialchars($user['full_name']); ?></h2>
<div class="card" style="max-width:500px;">
<div class="card-body">
<p><strong>Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
<p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
<p><strong>Role:</strong> <?php echo ucfirst($user['role']); ?></p>
<p><strong>Status:</strong> <span class="<?php echo $user['is_active'] ? 'text-success' : 'text-danger'; ?>"><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></span></p>
<p><strong>Created:</strong> <?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
<a href="<?php echo BASE_URL; ?>/admin/users/edit.php?id=<?php echo $user['id']; ?>" class="btn btn-primary">Edit</a>
<a href="<?php echo BASE_URL; ?>/admin/users/toggle.php?id=<?php echo $user['id']; ?>" class="btn btn-secondary">Toggle Status</a>
</div></div>
</main>
</div>
<?php include __DIR__ . '/../../includes/footer-admin.php'; ?>
