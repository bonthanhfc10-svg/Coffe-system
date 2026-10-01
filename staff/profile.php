<?php
require_once __DIR__ . '/../includes/functions.php';
requireStaff();
$pdo = getPDO();
$stmt = $pdo->prepare("SELECT u.*, c.phone, c.address FROM users u LEFT JOIN customers c ON u.id = c.user_id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/csrf.php';
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token'); }
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    if (!$fullName || !$email) {
        $error = 'Full name and email are required';
    } elseif (!empty($newPassword)) {
        if (empty($currentPassword)) {
            $error = 'Current password is required to change password';
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $error = 'Current password is incorrect';
        } elseif (strlen($newPassword) < 6) {
            $error = 'New password must be at least 6 characters';
        } else {
            $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, password = ? WHERE id = ?")->execute([$fullName, $email, $phone, $address, password_hash($newPassword, PASSWORD_DEFAULT), $_SESSION['user_id']]);
            $_SESSION['full_name'] = $fullName;
            $success = 'Profile updated! Password changed.';
        }
    } else {
        $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?")->execute([$fullName, $email, $phone, $address, $_SESSION['user_id']]);
        $_SESSION['full_name'] = $fullName;
        $success = 'Profile updated!';
    }
}
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user) { header('Location: ' . BASE_URL . '/staff/dashboard.php'); exit(); }
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Profile - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
<style>
body{background:#FFF8F0;font-family:'Segoe UI','Khmer OS',Tahoma,sans-serif}
.staff-profile{padding:20px}
.profile-card{background:#fff;border:none;border-radius:16px;box-shadow:0 4px 20px rgba(62,39,35,.08);overflow:hidden;margin-bottom:20px}
.profile-card .card-header{background:linear-gradient(135deg,#3E2723,#6F4E37);color:#fff;padding:16px 24px;font-weight:700;border:none}
.profile-card .card-body{padding:24px}
.avatar-circle{width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,#6F4E37,#3E2723);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;border:4px solid #fff;box-shadow:0 4px 15px rgba(111,78,55,.3)}
.avatar-circle i{font-size:2.5rem;color:#fff}
.form-icon{position:relative}
.form-icon i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#adb5bd}
.form-icon .form-control{padding-left:42px}
.btn-checkout{background:linear-gradient(135deg,#6F4E37,#3E2723);border:none;border-radius:12px;padding:12px 24px;font-weight:700;color:#fff;transition:all .3s;width:100%}
.btn-checkout:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(111,78,55,.4)}
</style>
</head><body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:260px;">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5 staff-profile">
<div class="text-center mb-4 fade-down">
<div class="avatar-circle mx-auto"><i class="bi bi-person-fill"></i></div>
<h2 class="fw-bold"><?php echo htmlspecialchars($user['full_name']); ?></h2>
<p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
<span class="badge badge-custom"><?php echo ucfirst($user['role']); ?></span>
</div>
<?php if ($error): ?><div class="alert alert-danger fade-up"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($success); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<div class="row">
<div class="col-lg-8 fade-up">
<form method="POST">
<?php echo csrf_field(); ?>
<div class="profile-card">
<div class="card-header"><i class="bi bi-person"></i> Personal Information</div>
<div class="card-body">
<div class="row">
<div class="col-md-6 mb-3"><label class="form-label">Full Name</label><div class="form-icon"><i class="bi bi-person"></i><input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required></div></div>
<div class="col-md-6 mb-3"><label class="form-label">Email</label><div class="form-icon"><i class="bi bi-envelope"></i><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required></div></div>
</div>
<div class="row">
<div class="col-md-6 mb-3"><label class="form-label">Phone</label><div class="form-icon"><i class="bi bi-phone"></i><input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"></div></div>
<div class="col-md-6 mb-3"><label class="form-label">Address</label><div class="form-icon"><i class="bi bi-geo-alt"></i><input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>"></div></div>
</div>
<div class="mb-3"><label class="form-label">Role</label><input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" disabled style="background:#f8f9fa"></div>
</div></div>
<div class="profile-card">
<div class="card-header"><i class="bi bi-lock"></i> Change Password</div>
<div class="card-body">
<div class="mb-3"><label class="form-label">Current Password</label><div class="form-icon"><i class="bi bi-key"></i><input type="password" name="current_password" class="form-control"></div></div>
<div class="mb-3"><label class="form-label">New Password</label><div class="form-icon"><i class="bi bi-shield-lock"></i><input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep current"></div></div>
<small class="text-muted">Minimum 6 characters. Leave blank to keep current password.</small>
</div></div>
<div class="text-center mt-4 fade-up">
<button type="submit" class="btn-checkout"><i class="bi bi-check-lg"></i> Save Profile</button>
</div>
</form>
</div>
<div class="col-lg-4 fade-up">
<div class="profile-card">
<div class="card-body text-center p-4">
<h5 class="fw-bold mb-3"><i class="bi bi-graph-up"></i> Performance</h5>
<div class="stats-row">
<div class="stat-box"><h5>0</h5><small>Today's Orders</small></div>
<div class="stat-box"><h5>$0</h5><small>Today's Sales</small></div>
<div class="stat-box"><h5>0</h5><small>Total Orders</small></div>
</div>
</div></div>
<div class="profile-card">
<div class="card-body p-4">
<h5 class="fw-bold mb-3"><i class="bi bi-lightning"></i> Quick Actions</h5>
<div class="d-grid gap-2">
<a href="<?php echo BASE_URL; ?>/staff/pos.php" class="btn btn-primary btn-sm"><i class="bi bi-cash-coin"></i> Open POS</a>
<a href="<?php echo BASE_URL; ?>/staff/orders.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-receipt"></i> View Orders</a>
<a href="<?php echo BASE_URL; ?>/staff/inventory.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-box-seam"></i> Inventory</a>
<a href="<?php echo BASE_URL; ?>/staff/customers.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-people"></i> Customers</a>
<a href="<?php echo BASE_URL; ?>/staff/reports.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-bar-chart"></i> Reports</a>
</div>
</div></div>
</div>
</div>
</main></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>