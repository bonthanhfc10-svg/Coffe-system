<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
allowRoles(['admin', 'staff']);
$pdo = getPDO();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../includes/csrf.php';
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token'); }
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    if (strlen($password) < 6) {
        $password = bin2hex(random_bytes(16));
    }
$pdo->prepare("INSERT INTO users (full_name, email, password, role, phone, address) VALUES (?, ?, ?, 'customer', ?, ?)")->execute([$fullName, $email, password_hash($password, PASSWORD_DEFAULT), $phone, $address]);
$lastId = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO customers (user_id, full_name, email, phone, address) VALUES (?, ?, ?, ?, ?)")->execute([$lastId, $fullName, $email, $phone, $address]);
    flash('success', 'Customer created!');
}
redirect(BASE_URL . '/staff/customers.php');
?>