<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
allowRoles(['admin', 'staff']);
$pdo = getPDO();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/csrf.php';
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token'); }
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? 'pending';
    $validStatuses = ['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'];
    if (in_array($newStatus, $validStatuses)) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$newStatus, $orderId]);
    }
}
redirect(BASE_URL . '/staff/order-details.php?id=' . (int)($_POST['order_id'] ?? 0));
?>