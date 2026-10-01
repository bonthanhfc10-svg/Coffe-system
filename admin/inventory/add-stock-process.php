<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole('admin');
$pdo = getPDO();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../includes/csrf.php';
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token'); }
    $productId = (int)($_POST['product_id'] ?? 0);
    $qty = (int)($_POST['quantity'] ?? 0);
    $reference = trim($_POST['reference'] ?? '');
    if ($qty > 0 && $productId) {
        $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?")->execute([$qty, $productId]);
        $pdo->prepare("INSERT INTO inventory_log (product_id, quantity, action, reference, created_by) VALUES (?, ?, 'in', ?, ?)")->execute([$productId, $qty, $reference, $_SESSION['user_id']]);
    }
}
redirect(BASE_URL . '/admin/inventory/index.php');
