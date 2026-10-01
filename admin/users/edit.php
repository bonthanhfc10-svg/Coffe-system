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
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../includes/csrf.php';
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token'); }
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'customer';
    $sql = "UPDATE users SET full_name = ?, email = ?, role = ?, phone = ?, address = ?";
    $params = [$fullName, $email, $role, $_POST['phone'] ?? '', $_POST['address'] ?? ''];
    if ($password) {
        $sql .= ", password = ?";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }
    $sql .= " WHERE id = ?";
    $params[] = $id;
    $pdo->prepare($sql)->execute($params);
    $success = 'User updated!';
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Edit User - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
<style>
body{background:#f8f9fa;font-family:'Segoe UI','Khmer OS',Tahoma,sans-serif}
.edit-page{padding:20px}
.edit-card{background:#fff;border:none;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.08);overflow:hidden}
.edit-card .card-body{padding:32px}
.edit-card .form-control{border-radius:12px;padding:10px 16px;border:2px solid #e0e0e0;transition:all .3s}
.edit-card .form-control:focus{border-color:#6f4e37;box-shadow:0 0 0 4px rgba(111,78,55,.1)}
.edit-card .form-label{font-weight:600;font-size:.9rem;margin-bottom:6px}
.edit-card .btn-primary{background:linear-gradient(135deg,#6f4e37,#a0785a);border:none;border-radius:12px;padding:10px 24px;font-weight:700;transition:all .3s;width:100%}
.edit-card .btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(111,78,55,.4)}
.edit-card .form-select{border-radius:12px;border:2px solid #e0e0e0;padding:10px 16px}
.edit-card .form-select:focus{border-color:#6f4e37;box-shadow:0 0 0 4px rgba(111,78,55,.1)}
.form-icon{position:relative}
.form-icon i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#adb5bd}
.form-icon .form-control{padding-left:42px}
</style>
</head><body>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:260px;">
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5 edit-page">
<div class="page-header fade-down">
    <h2><i class="bi bi-person-gear"></i> Edit User</h2>
</div>
<?php if ($error): ?><div class="alert alert-danger fade-up"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($success); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<div class="row justify-content-center">
<div class="col-lg-6 fade-up">
<div class="edit-card">
<div class="card-body">
<form method="POST">
<?php echo csrf_field(); ?>
<div class="mb-3">
<label class="form-label">Full Name</label>
<div class="form-icon"><i class="bi bi-person"></i><input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required></div>
</div>
<div class="mb-3">
<label class="form-label">Email</label>
<div class="form-icon"><i class="bi bi-envelope"></i><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required></div>
</div>
<div class="mb-3">
<label class="form-label">Password (leave blank to keep)</label>
<div class="form-icon"><i class="bi bi-lock"></i><input type="password" name="password" class="form-control"></div>
</div>
<div class="mb-3">
<label class="form-label">Phone</label>
<div class="form-icon"><i class="bi bi-phone"></i><input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"></div>
</div>
<div class="mb-3">
<label class="form-label">Address</label>
<div class="form-icon"><i class="bi bi-geo-alt"></i><input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>"></div>
</div>
<div class="mb-3">
<label class="form-label">Role</label>
<select name="role" class="form-select" required>
<option value="customer" <?php echo $user['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
<option value="staff" <?php echo $user['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
<option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
</select>
</div>
<button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Update User</button>
</form>
</div></div>
</div>
</div>
</main></div>
<?php include __DIR__ . '/../../includes/footer-admin.php'; ?>