<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
$pdo = getPDO();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/csrf.php';
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token'); }
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if ($fullName && $email) {
        $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?")->execute([$fullName, $email, $_SESSION['user_id']]);
        $_SESSION['full_name'] = $fullName;
        $success = 'Profile updated!';
    } else {
        $error = 'Please fill in all fields';
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Profile - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Khmer+OS&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
</head><body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<main class="container mt-4 pt-5">
<h2 class="section-title"><i class="bi bi-person"></i> My Profile</h2>
<div class="row">
<div class="col-lg-8 fade-up">
<div class="card shadow-sm">
<div class="card-body p-4">
<?php if ($error): ?><div class="alert alert-danger fade-up"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success fade-up"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<form method="POST">
<?php echo csrf_field(); ?>
<div class="mb-3">
<label class="form-label">Full Name</label>
<input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
</div>
<div class="mb-3">
<label class="form-label">Email</label>
<input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
</div>
<div class="mb-3">
<label class="form-label">Role</label>
<input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" disabled style="background:#f8f9fa">
</div>
<div class="mb-3">
<label class="form-label">Member Since</label>
<input type="text" class="form-control" value="<?php echo date('M d, Y', strtotime($user['created_at'])); ?>" disabled style="background:#f8f9fa">
</div>
<button type="submit" class="btn btn-primary btn-checkout"><i class="bi bi-check-lg"></i> Update Profile</button>
</form>
</div></div>
</div>
<div class="col-lg-4 fade-up">
<div class="profile-card shadow-sm">
<div class="profile-header">
<div style="width:80px;height:80px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;margin:0 auto 10px">
<i class="bi bi-person-fill" style="font-size:2.5rem;color:#6f4e37"></i>
</div>
<h4 class="mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h4>
<p class="mb-0 opacity-75"><?php echo htmlspecialchars($user['email']); ?></p>
</div>
<div class="profile-body">
<div class="row g-2">
<div class="col-6"><div class="profile-stat"><h5 class="fw-bold text-primary">0</h5><small>Orders</small></div></div>
<div class="col-6"><div class="profile-stat"><h5 class="fw-bold text-success">0</h5><small>Reviews</small></div></div>
</div>
</div>
</div>
</div>
</div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>