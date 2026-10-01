<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole('admin');
$pdo = getPDO();
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../includes/csrf.php';
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token'); }
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $role = $_POST['role'] ?? 'customer';
    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered';
        } else {
            $pdo->prepare("INSERT INTO users (full_name, email, password, role, phone, address) VALUES (?, ?, ?, ?, ?, ?)")->execute([$fullName, $email, password_hash($password, PASSWORD_DEFAULT), $role, $phone, $address]);
            $lastId = $pdo->lastInsertId();
            if ($role === 'customer') {
                $pdo->prepare("INSERT INTO customers (user_id, full_name, email, phone, address) VALUES (?, ?, ?, ?, ?)")->execute([$lastId, $fullName, $email, $phone, $address]);
            }
            $success = 'User created!';
        }
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Create User - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
<style>
body{background:#f8f9fa;font-family:'Segoe UI','Khmer OS',Tahoma,sans-serif}
.create-page{padding:20px}
.create-card{background:#fff;border:none;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.08);overflow:hidden}
.create-card .card-body{padding:32px}
.create-card .form-control{border-radius:12px;padding:10px 16px;border:2px solid #e0e0e0;transition:all .3s}
.create-card .form-control:focus{border-color:#6f4e37;box-shadow:0 0 0 4px rgba(111,78,55,.1)}
.create-card .form-label{font-weight:600;font-size:.9rem;margin-bottom:6px}
.create-card .btn-primary{background:linear-gradient(135deg,#6f4e37,#a0785a);border:none;border-radius:12px;padding:10px 24px;font-weight:700;transition:all .3s;width:100%}
.create-card .btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(111,78,55,.4)}
.create-card .form-select{border-radius:12px;border:2px solid #e0e0e0;padding:10px 16px}
.create-card .form-select:focus{border-color:#6f4e37;box-shadow:0 0 0 4px rgba(111,78,55,.1)}
.form-icon{position:relative}
.form-icon i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#adb5bd}
.form-icon .form-control{padding-left:42px}
</style>
</head><body>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:260px;">
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5 create-page">
<div class="page-header fade-down">
    <h2><i class="bi bi-person-plus"></i> Create User</h2>
</div>
<?php if ($error): ?><div class="alert alert-danger fade-up"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($success); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<div class="row justify-content-center">
<div class="col-lg-6 fade-up">
<div class="create-card">
<div class="card-body">
<form method="POST">
<?php echo csrf_field(); ?>
<div class="mb-3">
<label class="form-label">Full Name</label>
<div class="form-icon"><i class="bi bi-person"></i><input type="text" name="full_name" class="form-control" required></div>
</div>
<div class="mb-3">
<label class="form-label">Email</label>
<div class="form-icon"><i class="bi bi-envelope"></i><input type="email" name="email" class="form-control" required></div>
</div>
<div class="mb-3">
<label class="form-label">Password</label>
<div class="form-icon"><i class="bi bi-lock"></i><input type="password" name="password" class="form-control" required minlength="6"></div>
</div>
<div class="mb-3">
<label class="form-label">Role</label>
<select name="role" class="form-select" required>
<option value="customer">Customer</option>
<option value="staff">Staff</option>
<option value="admin">Admin</option>
</select>
</div>
<div class="mb-3"><label class="form-label">Phone</label><div class="form-icon"><i class="bi bi-phone"></i><input type="text" name="phone" class="form-control"></div></div>
<div class="mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="3"></textarea></div>
<button type="submit" class="btn-primary"><i class="bi bi-person-check"></i> Create User</button>
</form>
</div></div>
</div>
</div>
</main></div>
<?php include __DIR__ . '/../../includes/footer-admin.php'; ?>