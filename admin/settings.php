<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireRole('admin');
csrf_token();
$pdo = getPDO();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token'); }
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $siteName = trim($_POST['site_name'] ?? 'Coffee POS');
    $siteEmail = trim($_POST['site_email'] ?? '');
    $sitePhone = trim($_POST['site_phone'] ?? '');
    $siteAddress = trim($_POST['site_address'] ?? '');
    $siteMapUrl = trim($_POST['site_map_url'] ?? '');
    if ($siteMapUrl !== '' && !preg_match('#^https?://#i', $siteMapUrl)) {
        $siteMapUrl = 'https://' . $siteMapUrl;
    }
    if ($siteMapUrl !== '' && filter_var($siteMapUrl, FILTER_VALIDATE_URL) === false) {
        $siteMapUrl = '';
    }
    $siteDescription = trim($_POST['site_description'] ?? '');
    $siteCurrency = trim($_POST['site_currency'] ?? 'KHR');
    $siteCurrencySymbol = trim($_POST['site_currency_symbol'] ?? '$');
    $sql = "UPDATE users SET full_name = ?, email = ?";
    $params = [$fullName, $email];
    if ($password) {
        $sql .= ", password = ?";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }
    $sql .= " WHERE id = ?";
    $params[] = $_SESSION['user_id'];
    $pdo->prepare($sql)->execute($params);
    $_SESSION['full_name'] = $fullName;
    updateSetting($pdo, 'site_name', $siteName);
    updateSetting($pdo, 'site_email', $siteEmail);
    updateSetting($pdo, 'site_phone', $sitePhone);
    updateSetting($pdo, 'site_address', $siteAddress);
    updateSetting($pdo, 'site_map_url', $siteMapUrl);
    updateSetting($pdo, 'site_description', $siteDescription);
    updateSetting($pdo, 'currency', $siteCurrency);
    updateSetting($pdo, 'currency_symbol', $siteCurrencySymbol);
    updateSetting($pdo, 'default_payment_method', in_array($_POST['default_payment_method'] ?? 'cash', ['cash', 'card', 'qr'], true) ? $_POST['default_payment_method'] : 'cash');
    if(isset($_POST['default_discount'])) updateSetting($pdo, 'default_discount', $_POST['default_discount']);
    updateSetting($pdo, 'allow_card_payment', isset($_POST['allow_card_payment']) ? '1' : '0');
    updateSetting($pdo, 'allow_qr_payment', isset($_POST['allow_qr_payment']) ? '1' : '0');
    updateSetting($pdo, 'tax_rate', $_POST['tax_rate'] ?? '0');
    if (array_key_exists('payment_qr_payload', $_POST)) {
        updateSetting($pdo, 'payment_qr_payload', preg_replace('/[^\x20-\x7E]/', '', trim($_POST['payment_qr_payload'])));
    }
    $success = 'Settings updated!';
    $fileChosen = isset($_FILES['payment_qr_image'])
        && ($_FILES['payment_qr_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
        && ($_FILES['payment_qr_image']['name'] ?? '') !== '';
    if ($fileChosen) {
        $qrError = null;
        $newQr = uploadImage($_FILES['payment_qr_image'], 'payments', $qrError);
        if ($newQr) {
            $oldQr = trim(setting('payment_qr_image', ''));
            if ($oldQr !== '') @unlink(UPLOAD_PATH . 'payments/' . $oldQr);
            updateSetting($pdo, 'payment_qr_image', $newQr);
            $success .= ' QR image uploaded.';
        } else {
            $error = 'QR image upload failed: ' . $qrError . ' Other settings were saved.';
        }
    }
}
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$settings = getAllSettings($pdo);
$settingsMap = [];
foreach ($settings as $s) {
    $settingsMap[$s['setting_key']] = $s['setting_value'];
}
$dpm = $settingsMap['default_payment_method'] ?? 'cash';
if ($dpm === 'online') $dpm = 'qr';
if (!in_array($dpm, ['cash', 'card', 'qr'], true)) $dpm = 'cash';
$qrImage = trim($settingsMap['payment_qr_image'] ?? '');
$qrPayload = trim($settingsMap['payment_qr_payload'] ?? '');
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Settings - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
<style>
body{background:#f8f9fa;font-family:'Segoe UI','Khmer OS',Tahoma,sans-serif}
.setting-page{padding:20px}
.setting-card{background:#fff;border:none;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.08);overflow:hidden;margin-bottom:20px}
.setting-card .card-header{background:linear-gradient(135deg,#6f4e37,#a0785a);color:#fff;padding:16px 24px;font-weight:700;font-size:1.05rem;border:none}
.setting-card .card-header i{margin-right:8px}
.setting-card .card-body{padding:24px}
.setting-card .form-control{border-radius:12px;padding:10px 16px;border:2px solid #e0e0e0;transition:all .3s}
.setting-card .form-control:focus{border-color:#6f4e37;box-shadow:0 0 0 4px rgba(111,78,55,.1)}
.setting-card .form-label{font-weight:600;font-size:.9rem;margin-bottom:6px}
.setting-card .btn-primary{background:linear-gradient(135deg,#6f4e37,#a0785a);border:none;border-radius:12px;padding:10px 24px;font-weight:700;transition:all .3s}
.setting-card .btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(111,78,55,.4)}
.setting-card .form-check-input{width:18px;height:18px;margin-top:6px}
.setting-card .form-check-label{font-weight:500}
.setting-footer{text-align:center;padding:30px}
</style>
</head><body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:260px;">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5 setting-page">
<div class="page-header fade-down">
    <h2><i class="bi bi-gear-fill"></i> Settings</h2>
    <span class="badge-custom"><?php echo ucfirst($_SESSION['role']); ?></span>
</div>
<?php if ($success): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($success); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<form method="POST" enctype="multipart/form-data">
<?php echo csrf_field(); ?>
<div class="card setting-card fade-up">
<div class="card-header"><i class="bi bi-person"></i> Profile</div>
<div class="card-body">
<div class="row">
<div class="col-md-6 mb-3"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required></div>
<div class="col-md-6 mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required></div>
</div>
<div class="mb-3"><label class="form-label">New Password (leave blank to keep)</label><input type="password" name="password" class="form-control"></div>
</div></div>
<div class="card setting-card fade-up">
<div class="card-header"><i class="bi bi-info-circle"></i> System Information</div>
<div class="card-body">
<div class="row">
<div class="col-md-6 mb-3"><label class="form-label">Site Name</label><input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($settingsMap['site_name'] ?? 'Coffee POS'); ?>" required></div>
<div class="col-md-6 mb-3"><label class="form-label">Site Email</label><input type="email" name="site_email" class="form-control" value="<?php echo htmlspecialchars($settingsMap['site_email'] ?? 'support@coffee.com'); ?>" required></div>
</div>
<div class="row">
<div class="col-md-6 mb-3"><label class="form-label">Site Phone</label><input type="text" name="site_phone" class="form-control" value="<?php echo htmlspecialchars($settingsMap['site_phone'] ?? ''); ?>"></div>
<div class="col-md-6 mb-3"><label class="form-label">Site Address</label><input type="text" name="site_address" class="form-control" value="<?php echo htmlspecialchars($settingsMap['site_address'] ?? ''); ?>"></div>
</div>
<div class="mb-3"><label class="form-label">Google Map Link</label><input type="url" name="site_map_url" class="form-control" placeholder="https://maps.app.goo.gl/..." value="<?php echo htmlspecialchars($settingsMap['site_map_url'] ?? ''); ?>"><div class="form-text">Address link in the footer opens this location. Leave empty to search Google Maps for the site address instead.</div></div>
<div class="mb-3"><label class="form-label">Site Description</label><textarea name="site_description" class="form-control" rows="2"><?php echo htmlspecialchars($settingsMap['site_description'] ?? ''); ?></textarea></div>
<div class="row">
<div class="col-md-6 mb-3"><label class="form-label">Currency</label><select name="site_currency" class="form-select"><option value="USD" <?php echo ($settingsMap['currency'] ?? 'KHR') === 'USD' ? 'selected' : ''; ?>>Dollar ($)</option><option value="KHR" <?php echo ($settingsMap['currency'] ?? 'KHR') === 'KHR' ? 'selected' : ''; ?>>Khmer</option></select></div>
<div class="col-md-6 mb-3"><label class="form-label">Currency Symbol</label><input type="text" name="site_currency_symbol" class="form-control" value="<?php echo htmlspecialchars($settingsMap['currency_symbol'] ?? '$'); ?>"></div>
</div>
</div></div>
<div class="card setting-card fade-up">
<div class="card-header"><i class="bi bi-credit-card"></i> Payment Settings</div>
<div class="card-body">
<div class="row">
<div class="col-md-6 mb-3"><label class="form-label">Default Payment Method</label><select name="default_payment_method" class="form-select"><option value="cash" <?php echo $dpm === 'cash' ? 'selected' : ''; ?>>Cash</option><option value="card" <?php echo $dpm === 'card' ? 'selected' : ''; ?>>Card</option><option value="qr" <?php echo $dpm === 'qr' ? 'selected' : ''; ?>>QR</option></select></div>
<div class="col-md-6 mb-3"><label class="form-label">Tax Rate (%)</label><input type="number" name="tax_rate" class="form-control" step="0.01" value="<?php echo htmlspecialchars($settingsMap['tax_rate'] ?? '0'); ?>"></div>
</div>
<div class="row">
<div class="col-md-6 mb-3"><label class="form-label">Default Discount (%)</label><input type="number" name="default_discount" class="form-control" value="<?php echo htmlspecialchars($settingsMap['default_discount'] ?? '0'); ?>" min="0" max="100" step="0.5"></div>
<div class="col-md-6 mb-3"><div class="form-check mt-3"><input type="checkbox" name="allow_card_payment" class="form-check-input" id="allowCard" value="1" <?php echo ($settingsMap['allow_card_payment'] ?? '1') === '1' ? 'checked' : ''; ?>> <label class="form-check-label" for="allowCard">Allow Card Payment</label></div></div>
</div>
<div class="form-check mb-3"><input type="checkbox" name="allow_qr_payment" class="form-check-input" id="allowQr" value="1" <?php echo ($settingsMap['allow_qr_payment'] ?? '1') === '1' ? 'checked' : ''; ?>> <label class="form-check-label" for="allowQr">Allow QR Payment</label></div>
<div class="row align-items-end">
<div class="col-md-7 mb-3">
<label class="form-label">Payment QR Image</label>
<?php if ($qrImage !== ''): ?><div class="mb-2"><img src="<?php echo BASE_URL; ?>/public/uploads/payments/<?php echo htmlspecialchars(rawurlencode($qrImage)); ?>" alt="Payment QR" style="max-height:150px;border-radius:12px;border:1px solid #e0e0e0;background:#fff"></div><?php endif; ?>
<input type="file" name="payment_qr_image" class="form-control" accept="image/*">
<div class="form-text">Shown to customers when QR payment is selected. JPG, PNG, GIF, WEBP or AVIF - max 5 MB. Leave empty to keep the current image.</div>
</div>
<div class="col-md-5 mb-3">
<label class="form-label">QR Payload (auto price)</label>
<input type="text" name="payment_qr_payload" class="form-control" value="<?php echo htmlspecialchars($qrPayload); ?>" placeholder="000201...">
<div class="form-text">Text decoded from the QR image (EMVCo / ABA). When set, the QR on screen carries the order total, so the banking app shows the price automatically. Clear it to use the image unchanged.</div>
</div>
</div>
</div></div>
<div class="text-center mt-4 fade-up">
<button type="submit" class="btn btn-primary btn-lg px-5"><i class="bi bi-check-lg"></i> Save All Settings</button>
</div>
</form>
</main></div>
<?php include __DIR__ . '/../includes/footer-admin.php'; ?>