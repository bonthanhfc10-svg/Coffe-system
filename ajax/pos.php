<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
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
    $cart = $_SESSION['pos_cart'] ?? [];
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
    $_SESSION['pos_cart'] = $cart;
    $total = 0;
    foreach ($cart as $id => $item) {
        $q = $item['qty'];
        $s = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $s->execute([$id]);
        $total += $s->fetchColumn() * $q;
    }
    echo json_encode(['success' => true, 'count' => array_sum(array_column($cart, 'qty')), 'total' => $total, 'cart' => $cart]);
} elseif ($action === 'remove') {
    $id = (int)($_GET['id'] ?? 0);
    $cart = $_SESSION['pos_cart'] ?? [];
    unset($cart[$id]);
    $_SESSION['pos_cart'] = $cart;
    echo json_encode(['success' => true, 'count' => array_sum(array_column($cart, 'qty'))]);
} elseif ($action === 'update') {
    $id = (int)($_POST['product_id'] ?? 0);
    $delta = (int)($_POST['delta'] ?? 0);
    $cart = $_SESSION['pos_cart'] ?? [];
    $currentQty = isset($cart[$id]) ? $cart[$id]['qty'] : 0;
    $newQty = $currentQty + $delta;
    if ($newQty <= 0) {
        unset($cart[$id]);
    } else {
        $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $stock = $stmt->fetchColumn();
        if ($newQty <= $stock) {
            $cart[$id]['qty'] = $newQty;
        } else {
            echo json_encode(['success' => false, 'error' => 'Not enough stock']); exit;
        }
    }
    $_SESSION['pos_cart'] = $cart;
    $total = 0;
    foreach ($cart as $cid => $item) {
        $s = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $s->execute([$cid]);
        $total += $s->fetchColumn() * $item['qty'];
    }
    echo json_encode(['success' => true, 'count' => array_sum(array_column($cart, 'qty')), 'total' => $total, 'cart' => $cart]);
} elseif ($action === 'checkout') {
    $orderType = $_POST['order_type'] ?? 'dine_in';
    $paymentMethod = $_POST['payment'] ?? 'cash';
    if (!array_key_exists($paymentMethod, allowedPaymentMethods())) $paymentMethod = 'cash';
    $tableNumber = $_POST['table_number'] ?? null;
    $deliveryName = $_POST['delivery_name'] ?? null;
    $deliveryPhone = $_POST['delivery_phone'] ?? null;
    $deliveryAddress = $_POST['delivery_address'] ?? null;
    $discount = (float)($_POST['discount'] ?? 0);
    $cart = $_SESSION['pos_cart'] ?? [];
    if (empty($cart)) { echo json_encode(['success' => false, 'error' => 'Cart empty']); exit; }
    $total = 0;
    $items = [];
    foreach ($cart as $id => $item) {
        $qty = $item['qty'];
        $size = $item['size'] ?? null;
        $stmt = $pdo->prepare("SELECT price, stock_quantity FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if (!$product) { echo json_encode(['success' => false, 'error' => 'Product not found']); exit; }
        if ($qty > $product['stock_quantity']) { echo json_encode(['success' => false, 'error' => 'Not enough stock for ' . $id]); exit; }
        $total += $product['price'] * $qty;
        $items[] = ['id' => $id, 'qty' => $qty, 'price' => $product['price'], 'size' => $size];
    }
        $taxRate = (float)($pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'tax_rate'")->fetchColumn() ?? 0);
        $taxAmt = $total * ($taxRate / 100);
        $discountAmt = $total * ($discount / 100);
        $finalTotal = $total + $taxAmt - $discountAmt;
        $qrRef = '';
        if ($paymentMethod === 'qr') {
            $qrRef = trim($_POST['qr_ref'] ?? '');
            $qrSession = getPaymentSession($pdo, $qrRef);
            if (!$qrSession || paymentSessionState($qrSession) !== 'completed') {
                echo json_encode(['success' => false, 'error' => 'QR payment has not been confirmed yet']);
                exit;
            }
            if (abs((float)$qrSession['amount'] - $finalTotal) > 0.01) {
                echo json_encode(['success' => false, 'error' => 'QR payment amount does not match the order total']);
                exit;
            }
        }
    try {
        $pdo->beginTransaction();
        $orderNumber = generateOrderNumber();
        $staffId = $_SESSION['user_id'];
        $stmt = $pdo->prepare("INSERT INTO orders (order_number, user_id, staff_id, total_amount, discount, payment_method, payment_status, status, order_type, table_number, delivery_customer, delivery_phone, delivery_address) VALUES (?, ?, ?, ?, ?, ?, 'paid', 'completed', ?, ?, ?, ?, ?)");
        $stmt->execute([$orderNumber, $_SESSION['user_id'], $staffId, $finalTotal, $discountAmt, $paymentMethod, $orderType, $tableNumber, $deliveryName, $deliveryPhone, $deliveryAddress]);
        $orderId = $pdo->lastInsertId();
        foreach ($items as $item) {
            $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal, size) VALUES (?, ?, ?, ?, ?, ?)")->execute([$orderId, $item['id'], $item['qty'], $item['price'], $item['price'] * $item['qty'], $item['size']]);
            $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?")->execute([$item['qty'], $item['id']]);
            $stockStmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
            $stockStmt->execute([$item['id']]);
            $newStock = (int)$stockStmt->fetchColumn();
            createInventoryTransaction($pdo, $item['id'], 'out', $item['qty'], $newStock + $item['qty'], $newStock, 'POS Order #' . $orderNumber, 'Order ' . $orderNumber, $staffId);
        }
        $pdo->prepare("INSERT INTO payments (order_id, amount, payment_method, transaction_id, status) VALUES (?, ?, ?, ?, 'completed')")->execute([$orderId, $finalTotal, $paymentMethod, ($paymentMethod === 'qr' && $qrRef !== '' ? $qrRef : 'POS-' . $orderNumber)]);
        if ($qrRef !== '') attachPaymentSessionOrder($pdo, $qrRef, $orderNumber);
        $pdo->commit();
        $_SESSION['pos_cart'] = [];
        echo json_encode(['success' => true, 'order_number' => $orderNumber]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>