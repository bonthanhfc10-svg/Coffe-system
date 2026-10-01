<?php
session_set_cookie_params(0, '/', '', false, true);
session_start();
require_once __DIR__ . '/../config/database.php';
$pdo = null;
function getPDO() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }
    return $pdo;
}
function loadSystemSettings() {
    $pdo = getPDO();
    $settings = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
    foreach ($settings as $s) {
        define(strtoupper($s['setting_key']), $s['setting_value']);
    }
    if (!defined('CURRENCY')) define('CURRENCY', 'KHR');
    if (!defined('CURRENCY_SYMBOL')) {
        $cs = (defined('CURRENCY_SYMBOL') && constant('CURRENCY_SYMBOL') !== '') ? constant('CURRENCY_SYMBOL') : ((CURRENCY === 'USD') ? '$' : '៛');
        define('CURRENCY_SYMBOL', $cs);
    }
    if (!defined('CURRENCY_NAME')) define('CURRENCY_NAME', (CURRENCY === 'USD') ? 'Dollar' : 'Dollar Khmer');
}
loadSystemSettings();

function setting($key, $default = '') {
    $const = strtoupper($key);
    if (defined($const)) {
        $v = constant($const);
        if ($v === null || $v === false) return $default;
        return (string)$v;
    }
    return $default;
}

function settingBool($key, $default = false) {
    $v = strtolower(trim(setting($key, $default ? '1' : '0')));
    return in_array($v, ['1', 'true', 'yes', 'on'], true);
}

function paymentQrImage() {
    $file = trim(setting('payment_qr_image', ''));
    if ($file === '') return '';
    return BASE_URL . '/public/uploads/payments/' . rawurlencode($file);
}

function paymentQrPayload() {
    return trim(setting('payment_qr_payload', ''));
}


function allowedPaymentMethods() {
    $methods = ['cash' => 'Cash'];
    if (settingBool('allow_card_payment', false)) $methods['card'] = 'Card';
    if (settingBool('allow_qr_payment', true)) $methods['qr'] = 'QR';
    return $methods;
}

function siteName() {
    $n = trim(setting('site_name', '4RealCoffee'));
    return $n === '' ? '4RealCoffee' : $n;
}

function siteDescription() {
    return trim((string) setting('site_description', ''));
}

function redirect($url) {
    header("Location: " . $url);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect(BASE_URL . '/auth/login.php');
    }
}

function requireRole($role) {
    if (!isLoggedIn() || $_SESSION['role'] !== $role) {
        redirect(BASE_URL . '/403.php');
    }
}

function allowRoles($roles) {
    if (!isLoggedIn() || !in_array($_SESSION['role'], $roles)) {
        redirect(BASE_URL . '/403.php');
    }
}

function requireStaff() {
    if (!isLoggedIn() || $_SESSION['role'] !== 'staff') {
        redirect(BASE_URL . '/403.php');
    }
}

function createInventoryTransaction($pdo, $productId, $type, $quantity, $previousStock, $newStock, $reference = null, $note = null, $createdBy = null) {
    $stmt = $pdo->prepare("INSERT INTO inventory_transactions (product_id, type, quantity, previous_stock, new_stock, reference, note, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$productId, $type, $quantity, $previousStock, $newStock, $reference, $note, $createdBy]);
}

function getStaffStats($pdo, $staffId) {
    $today = date('Y-m-d');
    $stats = $pdo->prepare("SELECT COUNT(*) as today_orders, COALESCE(SUM(total_amount),0) as today_revenue FROM orders WHERE staff_id = ? AND DATE(created_at) = ?");
    $stats->execute([$staffId, $today]);
    $todayData = $stats->fetch();
    $totalOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE staff_id = $staffId")->fetchColumn();
    $pendingOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE staff_id = $staffId AND status = 'pending'")->fetchColumn();
    $completedOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE staff_id = $staffId AND status = 'completed'")->fetchColumn();
    return [
        'today_orders' => $todayData['today_orders'],
        'today_revenue' => $todayData['today_revenue'],
        'total_orders' => $totalOrders,
        'pending_orders' => $pendingOrders,
        'completed_orders' => $completedOrders
    ];
}

function getLowStockProducts($pdo) {
    $stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1 AND p.stock_quantity <= 10 ORDER BY p.stock_quantity ASC LIMIT 10");
    return $stmt->fetchAll();
}

function getRecentOrders($pdo, $limit = 10) {
    $stmt = $pdo->prepare("SELECT o.*, ui.full_name, COUNT(oi.id) as item_count FROM orders o LEFT JOIN users ui ON o.user_id = ui.id LEFT JOIN order_items oi ON o.id = oi.order_id WHERE o.staff_id IS NOT NULL GROUP BY o.id ORDER BY o.created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function getCustomerCount($pdo) {
    return $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer' AND is_active = 1")->fetchColumn();
}

function getPaymentMethodTotals($pdo, $where = '') {
    $sql = "SELECT payment_method, SUM(total_amount) as total FROM orders $where GROUP BY payment_method";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && isset($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function uploadImage($file, $folder = 'products', &$error = null) {
    $error = null;
    if (!is_array($file) || !isset($file['error']) || is_array($file['error'])) {
        $error = 'No file was received.';
        return false;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = uploadErrorMessage($file['error']);
        return false;
    }
    $allowed = ['jpg', 'jpeg', 'jfif', 'png', 'gif', 'webp', 'avif', 'bmp'];
    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        $type = $ext !== '' ? '.' . $ext : 'unknown type';
        $error = 'Unsupported file type (' . $type . '). Allowed: ' . implode(', ', $allowed) . '.';
        return false;
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        $error = 'Image is too large (' . round($file['size'] / 1048576, 1) . ' MB). Maximum allowed is 5 MB.';
        return false;
    }
    $mime = null;
    if (function_exists('finfo_open')) {
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = @finfo_file($finfo, $file['tmp_name']);
            @finfo_close($finfo);
        }
    }
    if ($mime && strpos($mime, 'image/') !== 0 && !in_array($mime, ['application/octet-stream', 'binary/octet-stream'], true)) {
        $error = 'File content is not a supported image (detected ' . $mime . ').';
        return false;
    }
    $folderPath = UPLOAD_PATH . $folder . '/';
    if (!is_dir($folderPath) && !@mkdir($folderPath, 0775, true)) {
        $error = 'Upload folder does not exist and could not be created.';
        return false;
    }
    if (!is_writable($folderPath)) {
        $error = 'Upload folder is not writable.';
        return false;
    }
    $name = uniqid() . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $folderPath . $name)) {
        return $name;
    }
    $error = 'Could not save the uploaded file.';
    return false;
}

function uploadErrorMessage($code) {
    switch ((int) $code) {
        case UPLOAD_ERR_INI_SIZE:   return 'File exceeds the server upload limit.';
        case UPLOAD_ERR_FORM_SIZE:  return 'File exceeds the form upload limit.';
        case UPLOAD_ERR_PARTIAL:    return 'File was only partially uploaded - please try again.';
        case UPLOAD_ERR_NO_FILE:    return 'No file was selected.';
        case UPLOAD_ERR_NO_TMP_DIR: return 'Server error: missing temporary folder.';
        case UPLOAD_ERR_CANT_WRITE: return 'Server error: could not write the file to disk.';
        case UPLOAD_ERR_EXTENSION:  return 'Upload was blocked by a server extension.';
        default:                    return 'Upload failed (error code ' . (int) $code . ').';
    }
}

function formatMoney($amount) {
    $symbol = defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : ((defined('CURRENCY') && CURRENCY === 'USD') ? '$' : '៛');
    return $symbol . number_format($amount, 2);
}

function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function getUserName($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    return $user ? $user['full_name'] : 'Unknown';
}

function renderPagination($currentPage, $totalPages, $baseUrl) {
    if ($totalPages <= 1) return '';
    $html = '<nav><ul class="pagination">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i == $currentPage ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a></li>';
    }
    $html .= '</ul></nav>';
    return $html;
}

function getPageTitle() {
    $current = basename($_SERVER['PHP_SELF'], '.php');
    $titles = [
        'dashboard' => 'Dashboard',
        'products' => 'Products',
        'orders' => 'Orders',
        'customers' => 'Customers',
        'categories' => 'Categories',
        'inventory' => 'Inventory',
        'reports' => 'Reports',
        'profile' => 'Profile',
        'login' => 'Login',
        'register' => 'Register',
        'cart' => 'Shopping Cart',
        'checkout' => 'Checkout',
        'pos' => 'Point of Sale',
        'receipt' => 'Receipt',
        'index' => 'Home',
        'order-details' => 'Order Details',
        'customer-add' => 'Add Customer',
        'customer-edit' => 'Edit Customer',
    ];
    return isset($titles[$current]) ? $titles[$current] : ucfirst($current);
}

function flash($key, $value = null) {
    if ($value !== null) {
        $_SESSION['flash_' . $key] = $value;
    } else {
        $key = 'flash_' . $key;
        if (isset($_SESSION[$key])) {
            $val = $_SESSION[$key];
            unset($_SESSION[$key]);
            return $val;
        }
        return null;
    }
}

function flashShow($key) {
    $val = flash($key);
    if ($val) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($val) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

function getSetting($pdo, $key, $default = '') {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : $default;
}

function getAllSettings($pdo) {
    $stmt = $pdo->query("SELECT setting_key, setting_value, description FROM settings");
    return $stmt->fetchAll();
}

function updateSetting($pdo, $key, $value) {
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP");
    return $stmt->execute([$key, $value]);
}

function updateSettingsBulk($pdo, $data) {
    $pdo->beginTransaction();
    try {
        foreach ($data as $key => $value) {
            updateSetting($pdo, $key, $value);
        }
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

define('PRODUCT_SIZE_OPTIONS', ['Small', 'Medium', 'Large', 'Extra Large']);

function getProductSizes($sizeField) {
    $sizes = [];
    foreach (explode(',', (string)($sizeField ?? '')) as $s) {
        $s = trim($s);
        if ($s !== '' && !in_array($s, $sizes, true)) $sizes[] = $s;
    }
    return $sizes;
}

function sizeOptionsHtml($sizeField, $selected = '', $emptyLabel = 'None') {
    $sizes = getProductSizes($sizeField);
    $html = '<option value="">' . htmlspecialchars($emptyLabel) . '</option>';
    foreach ($sizes as $s) {
        $isSel = ($selected !== '' ? $s === $selected : count($sizes) === 1);
        $html .= '<option value="' . htmlspecialchars($s) . '"' . ($isSel ? ' selected' : '') . '>' . htmlspecialchars($s) . '</option>';
    }
    return $html;
}

function sizeShortLabel($sizeField) {
    $map = ['Small' => 'S', 'Medium' => 'M', 'Large' => 'L', 'Extra Large' => 'XL'];
    $out = [];
    foreach (getProductSizes($sizeField) as $s) $out[] = $map[$s] ?? $s;
    return implode(' · ', $out);
}

function periodFilter($defaultType = 'month') {
    $type = $_GET['filter_type'] ?? $defaultType;
    if (!in_array($type, ['day', 'month', 'year'], true)) $type = $defaultType;
    $defaults = ['day' => date('Y-m-d'), 'month' => date('Y-m'), 'year' => date('Y')];
    $patterns = ['day' => '/^\d{4}-\d{2}-\d{2}$/', 'month' => '/^\d{4}-\d{2}$/', 'year' => '/^\d{4}$/'];
    $value = $_GET['filter_value'] ?? '';
    if (!is_string($value) || !preg_match($patterns[$type], $value)) {
        $value = $defaults[$type];
    }
    return [$type, $value];
}

function periodWhere($type, $value, $column = 'created_at') {
    if ($type === 'day') {
        return ['DATE(' . $column . ') = ?', [$value]];
    }
    if ($type === 'year') {
        return ['YEAR(' . $column . ') = ?', [(int) $value]];
    }
    return ["DATE_FORMAT($column, '%Y-%m') = ?", [$value]];
}

function appBaseUrl() {
    $custom = trim(setting('app_url', ''));
    if ($custom !== '') return rtrim($custom, '/');
    $parts = parse_url(BASE_URL);
    $scheme = $parts['scheme'] ?? 'http';
    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
    $path = rtrim($parts['path'] ?? '', '/');
    $host = gethostbyname(gethostname());
    if (!filter_var($host, FILTER_VALIDATE_IP) || strpos($host, '127.') === 0) {
        $host = $parts['host'] ?? 'localhost';
    }
    return $scheme . '://' . $host . $port . $path;
}

function payUrl($ref) {
    return appBaseUrl() . '/pay.php?ref=' . rawurlencode($ref);
}

function newPaymentRef() {
    try {
        return 'P' . strtoupper(bin2hex(random_bytes(5)));
    } catch (Exception $e) {
        return 'P' . strtoupper(substr(md5(uniqid('', true)), 0, 10));
    }
}

function createPaymentSession($pdo, $amount, $source = 'pos', $currency = null) {
    $ref = newPaymentRef();
    $currency = $currency !== null && $currency !== '' ? $currency : setting('currency', 'USD');
    $stmt = $pdo->prepare("INSERT INTO payment_sessions (ref, source, amount, currency, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$ref, $source === 'checkout' ? 'checkout' : 'pos', round((float) $amount, 2), $currency]);
    return getPaymentSession($pdo, $ref);
}

function getPaymentSession($pdo, $ref) {
    $ref = trim((string) $ref);
    if ($ref === '') return null;
    $stmt = $pdo->prepare("SELECT * FROM payment_sessions WHERE ref = ?");
    $stmt->execute([$ref]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function paymentSessionState($row) {
    if (!$row) return 'not_found';
    if ($row['status'] === 'completed') return 'completed';
    if ($row['status'] === 'cancelled') return 'cancelled';
    if (strtotime($row['created_at']) < time() - 900) return 'expired';
    return 'pending';
}

function completePaymentSession($pdo, $ref) {
    $row = getPaymentSession($pdo, $ref);
    if (!$row) return null;
    if ($row['status'] === 'pending') {
        $pdo->prepare("UPDATE payment_sessions SET status = 'completed' WHERE ref = ? AND status = 'pending'")->execute([trim($ref)]);
        $row = getPaymentSession($pdo, $ref);
    }
    return $row;
}

function cancelPaymentSession($pdo, $ref) {
    $row = getPaymentSession($pdo, $ref);
    if (!$row || $row['status'] !== 'pending') return $row;
    $pdo->prepare("UPDATE payment_sessions SET status = 'cancelled' WHERE ref = ? AND status = 'pending'")->execute([trim($ref)]);
    return getPaymentSession($pdo, $ref);
}

function attachPaymentSessionOrder($pdo, $ref, $orderNumber) {
    $ref = trim((string) $ref);
    if ($ref === '') return;
    $pdo->prepare("UPDATE payment_sessions SET order_number = ? WHERE ref = ?")->execute([$orderNumber, $ref]);
}

