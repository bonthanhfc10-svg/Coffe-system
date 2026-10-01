<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole('admin');
$pdo = getPDO();
$id = $_GET['id'] ?? 0;
$pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
redirect(BASE_URL . '/admin/products/index.php');
