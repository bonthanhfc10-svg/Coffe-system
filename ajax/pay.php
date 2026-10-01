<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
$pdo = getPDO();
$action = $_GET['action'] ?? '';
header('Content-Type: application/json');

if ($action === 'create') {
    $source = ($_POST['source'] ?? 'pos') === 'checkout' ? 'checkout' : 'pos';
    if ($source === 'checkout') {
        $cart = $_SESSION['cart'] ?? [];
        if (empty($cart)) { echo json_encode(['success' => false, 'error' => 'Your cart is empty']); exit; }
        $ids = array_keys($cart);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, price FROM products WHERE id IN ($placeholders) AND is_active = 1");
        $stmt->execute($ids);
        $total = 0;
        foreach ($stmt->fetchAll() as $prod) {
            $cartItem = $cart[$prod['id']];
            $qty = is_array($cartItem) ? $cartItem['qty'] : $cartItem;
            $total += $prod['price'] * $qty;
        }
        $discount = 0;
    } else {
        $cart = $_SESSION['pos_cart'] ?? [];
        if (empty($cart)) { echo json_encode(['success' => false, 'error' => 'Cart empty']); exit; }
        $total = 0;
        foreach ($cart as $id => $item) {
            $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $price = $stmt->fetchColumn();
            if ($price === false) { echo json_encode(['success' => false, 'error' => 'Product not found']); exit; }
            $total += $price * (int) $item['qty'];
        }
        $discount = (float) ($_POST['discount'] ?? 0);
        if ($discount < 0) $discount = 0;
        if ($discount > 100) $discount = 100;
        $taxRate = (float) ($pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'tax_rate'")->fetchColumn() ?? 0);
        $total = $total + ($total * ($taxRate / 100)) - ($total * ($discount / 100));
    }
    $session = createPaymentSession($pdo, $total, $source);
    if (!$session) { echo json_encode(['success' => false, 'error' => 'Could not create payment session']); exit; }
    echo json_encode([
        'success' => true,
        'ref' => $session['ref'],
        'state' => paymentSessionState($session),
        'amount' => (float) $session['amount'],
        'amount_text' => formatMoney($session['amount']),
        'currency' => $session['currency'],
        'pay_url' => payUrl($session['ref'])
    ]);
} elseif ($action === 'status') {
    $session = getPaymentSession($pdo, $_GET['ref'] ?? '');
    if (!$session) { echo json_encode(['success' => false, 'state' => 'not_found', 'error' => 'Payment session not found']); exit; }
    echo json_encode([
        'success' => true,
        'state' => paymentSessionState($session),
        'status' => $session['status'],
        'ref' => $session['ref'],
        'amount' => (float) $session['amount'],
        'amount_text' => formatMoney($session['amount']),
        'currency' => $session['currency']
    ]);
} elseif ($action === 'cancel') {
    $session = cancelPaymentSession($pdo, $_POST['ref'] ?? '');
    echo json_encode(['success' => true, 'state' => $session ? paymentSessionState($session) : 'not_found']);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
