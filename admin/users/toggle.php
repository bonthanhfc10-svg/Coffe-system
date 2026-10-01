<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole('admin');
$pdo = getPDO();
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();
if ($user) {
    $newStatus = $user['is_active'] ? 0 : 1;
    $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$newStatus, $id]);
}
redirect(BASE_URL . '/admin/users/index.php');
