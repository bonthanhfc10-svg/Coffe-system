<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole('admin');
$pdo = getPDO();
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
$stmt->execute([$id]);
redirect(BASE_URL . '/admin/users/index.php');
