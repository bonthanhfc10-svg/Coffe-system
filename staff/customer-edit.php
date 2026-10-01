<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
allowRoles(['admin', 'staff']);
$pdo = getPDO();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user || $user['role'] !== 'customer') redirect(BASE_URL . '/staff/customers.php');
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/csrf.php';
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token'); }
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    if (!$fullName || !$email) {
        $error = 'Full name and email are required';
    } else {
        $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?")->execute([$fullName, $email, $phone, $address, $id]);
        $pdo->prepare("UPDATE customers SET phone = ?, address = ? WHERE user_id = ?")->execute([$phone, $address, $id]);
        $success = 'Customer updated!';
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Edit Customer - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
<style>
body{background:#FFF8F0;font-family:'Segoe UI','Khmer OS',Tahoma,sans-serif}
.edit-page{padding:20px}
.edit-card{background:#fff;border:none;border-radius:16px;box-shadow:0 4px 20px rgba(62,39,35,.08);overflow:hidden}
.edit-card .card-body{padding:32px}
.form-icon{position:relative}
.form-icon i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#adb5bd}
.form-icon .form-control{padding-left:42px}
</style>
</head><body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:260px;">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5 edit-page">
<div class="page-header fade-down">
    <h2><i class="bi bi-person-gear"></i> Edit Customer</h2>
</div>
<?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($success); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<div class="row justify-content-center">
<div class="col-lg-6 fade-up">
<div class="edit-card">
<div class="card-body">
<form method="POST">
<?php echo csrf_field(); ?>
<div class="mb-3"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required></div>
<div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required></div>
<div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"></div>
<div class="mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea></div>
<button type="submit" class="btn-checkout"><i class="bi bi-check-lg"></i> Update Customer</button>
</form>
</div></div>
</div>
</div>
</main></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>