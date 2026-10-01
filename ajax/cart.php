<?php
require_once __DIR__ . '/../includes/functions.php';
if (!isLoggedIn()) { echo json_encode(['success' => false, 'error' => 'Not logged in']); exit; }
$pdo = getPDO();
$action = $_GET['action'] ?? '';
if ($action === 'add') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $qty = (int)($_POST['quantity'] ?? 1);
    $size = trim($_POST['size'] ?? '');
    if ($qty < 1) $qty = 1;
    $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $stock = $stmt->fetchColumn();
    if ($qty > $stock) { echo json_encode(['success' => false, 'error' => 'Not enough stock']); exit; }
    $cart = $_SESSION['cart'] ?? [];
    if (isset($cart[$productId])) {
        if ($cart[$productId]['qty'] + $qty <= $stock) {
            $cart[$productId]['qty'] += $qty;
            $cart[$productId]['size'] = $size;
        } else {
            echo json_encode(['success' => false, 'error' => 'Not enough stock']); exit;
        }
    } else {
        $cart[$productId] = ['qty' => $qty, 'size' => $size];
    }
    $_SESSION['cart'] = $cart;
    $totalCount = array_sum(array_column($cart, 'qty'));
    echo json_encode(['success' => true, 'count' => $totalCount]);
} elseif ($action === 'qty') {
    $id = (int)($_GET['id'] ?? 0);
    $delta = (int)($_GET['delta'] ?? 0);
    $cart = $_SESSION['cart'] ?? [];
    if (!isset($cart[$id])) { echo json_encode(['success' => false, 'error' => 'Not in cart']); exit; }
    $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $stock = $stmt->fetchColumn();
    $newQty = (is_array($cart[$id]) ? $cart[$id]['qty'] : $cart[$id]) + $delta;
    if ($newQty <= 0) {
        unset($cart[$id]);
    } else {
        if ($newQty > $stock) { echo json_encode(['success' => false, 'error' => 'Not enough stock']); exit; }
        if (is_array($cart[$id])) { $cart[$id]['qty'] = $newQty; } else { $cart[$id] = $newQty; }
    }
    $_SESSION['cart'] = $cart;
    echo json_encode(['success' => true, 'count' => array_sum(array_map(function ($i) { return is_array($i) ? $i['qty'] : $i; }, $cart))]);
} elseif ($action === 'remove') {
    $id = (int)($_GET['id'] ?? 0);
    $cart = $_SESSION['cart'] ?? [];
    unset($cart[$id]);
    $_SESSION['cart'] = $cart;
    $totalCount = array_sum(array_column($cart, 'qty'));
    echo json_encode(['success' => true, 'count' => $totalCount]);
} elseif ($action === 'count') {
    $cart = $_SESSION['cart'] ?? [];
    $totalCount = array_sum(array_column($cart, 'qty'));
    echo json_encode(['success' => true, 'count' => $totalCount]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}