<?php
require_once __DIR__ . '/../includes/functions.php';
if (!isLoggedIn()) redirect(BASE_URL . '/auth/login.php');
$pdo = getPDO();
$cart = $_SESSION['cart'] ?? [];
$items = [];
$total = 0;
if (!empty($cart)) {
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND is_active = 1");
    $stmt->execute($ids);
    $products = $stmt->fetchAll();
    foreach ($products as $prod) {
        $cartItem = $cart[$prod['id']];
        $qty = is_array($cartItem) ? $cartItem['qty'] : $cartItem;
        $size = is_array($cartItem) ? ($cartItem['size'] ?? null) : null;
        $subtotal = $prod['price'] * $qty;
        $total += $subtotal;
        $items[] = ['product' => $prod, 'quantity' => $qty, 'size' => $size, 'subtotal' => $subtotal];
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Shopping Cart - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Khmer+OS&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
</head><body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<main class="container mt-4 pt-5">
    <h2 class="section-title"><i class="bi bi-cart"></i> Shopping Cart</h2>
    <?php if (empty($items)): ?>
    <div class="empty-state fade-up">
        <i class="bi bi-cart-x"></i>
        <h4>Your cart is empty</h4>
        <p>Browse our products and add some items to your cart.</p>
        <a href="<?php echo BASE_URL; ?>/customer/products.php" class="btn btn-primary">Continue Shopping</a>
    </div>
    <?php else: ?>
    <div class="row">
        <div class="col-lg-8 fade-up">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Product</th><th>Size</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach($items as $i): ?>
                    <tr class="cart-item-row">
                        <td><strong><?php echo htmlspecialchars($i['product']['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($i['size'] ?? '-'); ?></td>
                        <td><?php echo formatMoney($i['product']['price']); ?></td>
                        <td>
                            <div class="btn-group" style="width:fit-content">
                                <button class="btn btn-sm btn-outline-secondary" onclick="updateQty(<?php echo $i['product']['id']; ?>, -1)">-</button>
                                <span class="btn btn-sm btn-light" style="min-width:40px;text-align:center;font-weight:700"><?php echo $i['quantity']; ?></span>
                                <button class="btn btn-sm btn-outline-secondary" onclick="updateQty(<?php echo $i['product']['id']; ?>, 1)">+</button>
                            </div>
                        </td>
                        <td class="fw-bold"><?php echo formatMoney($i['subtotal']); ?></td>
                        <td><button class="btn btn-sm btn-outline-danger" onclick="removeFromCart('<?php echo $i['product']['id']; ?>')"><i class="bi bi-trash"></i></button></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="table-success fw-bold"><td colspan="3">Total</td><td colspan="2" style="font-size:1.3rem;color:#6f4e37"><?php echo formatMoney($total); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-4 fade-up">
            <div class="cart-summary">
                <h4 class="mb-4"><i class="bi bi-receipt"></i> Order Summary</h4>
                <hr>
                <div class="d-flex justify-content-between mb-2"><span>Subtotal:</span><span><?php echo formatMoney($total); ?></span></div>
                <div class="d-flex justify-content-between mb-2"><span>Shipping:</span><span class="text-success">Free</span></div>
                <div class="d-flex justify-content-between mb-2"><span>Discount:</span><span class="text-success">0%</span></div>
                <div class="total-row d-flex justify-content-between"><span>Total:</span><span style="color:#6f4e37"><?php echo formatMoney($total); ?></span></div>
                <hr>
                <a href="<?php echo BASE_URL; ?>/customer/checkout.php" class="btn btn-primary w-100 btn-checkout" style="font-size:1.1rem;padding:12px;"><i class="bi bi-check-lg"></i> Proceed to Checkout</a>
                <a href="<?php echo BASE_URL; ?>/customer/products.php" class="btn btn-outline-primary w-100 mt-2">Continue Shopping</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>