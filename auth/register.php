<?php
require_once __DIR__ . '/../includes/functions.php';
if (isLoggedIn()) redirect(BASE_URL . '/index.php');
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/csrf.php';
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    if ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered';
        } else {
            $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'customer')")
                ->execute([$fullName, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $lastId = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO customers (user_id, full_name, email, phone, address) VALUES (?, ?, ?, ?, ?)")->execute([$lastId, $fullName, $email, $phone, $address]);
            $success = 'Registration successful! Please login.';
        }
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Register - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Khmer+OS&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
<style>
body{background:linear-gradient(135deg,#6f4e37,#a0785a,#f0c674)!important;min-height:100vh;display:flex;align-items:center;justify-content:center}
.register-card{background:#fff;border:none;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.2);padding:40px;width:100%;max-width:420px;animation:scaleIn .5s ease}
.register-card h2{color:#6f4e37;font-weight:800;margin-bottom:8px}
.register-card .logo-icon{font-size:3rem;color:#6f4e37;margin-bottom:8px}
.register-card .form-control{border-radius:12px;padding:12px 16px;margin-bottom:12px;border:2px solid #e0e0e0;transition:all .3s}
.register-card .form-control:focus{border-color:#6f4e37;box-shadow:0 0 0 4px rgba(111,78,55,.1)}
.register-card .btn-primary{background:linear-gradient(135deg,#6f4e37,#a0785a);border:none;border-radius:12px;padding:12px;font-weight:700;font-size:1.05rem;transition:all .3s;width:100%}
.register-card .btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(111,78,55,.4)}
.register-card .alert{border-radius:12px}
</style>
</head><body>
<div class="register-card">
<div class="text-center mb-4">
<div class="logo-icon"><i class="bi bi-cup-fill"></i></div>
<h2><?php echo htmlspecialchars(siteName()); ?></h2>
<p class="text-muted">Create your account</p>
</div>
<?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<form method="POST">
<?php echo csrf_field(); ?>
<div class="mb-3"><input type="text" name="full_name" class="form-control" placeholder="Full Name" required></div>
<div class="mb-3"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
<div class="mb-3"><input type="password" name="password" class="form-control" placeholder="Password" required minlength="6"></div>
<div class="mb-3"><input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required minlength="6"></div>
<div class="mb-3"><input type="text" name="phone" class="form-control" placeholder="Phone"></div>
<div class="mb-3"><textarea name="address" class="form-control" rows="3" placeholder="Address"></textarea></div>
<button type="submit" class="btn btn-primary"><i class="bi bi-person-plus"></i> Register</button>
</form>
<p class="mt-3 text-center text-muted">Already have an account? <a href="<?php echo BASE_URL; ?>/auth/login.php">Login</a></p>
</div>
</body></html>