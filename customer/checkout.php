<?php
require_once __DIR__ . '/../includes/functions.php';
if (!isLoggedIn()) redirect(BASE_URL . '/auth/login.php');
$pdo = getPDO();
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) redirect(BASE_URL . '/customer/cart.php');
$items = [];
$total = 0;
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
$error = '';
$success = '';
$qrPaidOk = false;
$paymentMethods = allowedPaymentMethods();
$qrImageSrc = paymentQrImage();
$qrPayload = paymentQrPayload();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/csrf.php';
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token'); }
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    if (!array_key_exists($paymentMethod, $paymentMethods)) $paymentMethod = 'cash';
    $notes = trim($_POST['notes'] ?? '');
    $qrRef = trim($_POST['qr_ref'] ?? '');
    $qrPaid = false;
    if ($paymentMethod === 'qr') {
        $qrSession = getPaymentSession($pdo, $qrRef);
        $qrPaid = $qrSession && paymentSessionState($qrSession) === 'completed' && abs((float)$qrSession['amount'] - $total) <= 0.01;
        if (!$qrPaid) { $error = 'QR payment has not been confirmed yet. Scan the code and wait for the confirmation.'; }
    }
    $paymentStatus = $qrPaid ? 'paid' : 'pending';
    if ($error === '') {
    try {
        $pdo->beginTransaction();
        $orderNumber = generateOrderNumber();
        $stmt = $pdo->prepare("INSERT INTO orders (order_number, user_id, total_amount, payment_method, payment_status, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$orderNumber, $_SESSION['user_id'], $total, $paymentMethod, $paymentStatus, $notes]);
        $orderId = $pdo->lastInsertId();
        foreach ($items as $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal, size) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$orderId, $item['product']['id'], $item['quantity'], $item['product']['price'], $item['subtotal'], $item['size']]);
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['product']['id']]);
        }
        $cUpd = $pdo->prepare("UPDATE customers SET total_orders = total_orders + 1, total_spent = total_spent + ? WHERE user_id = ?");
        $cUpd->execute([$total, $_SESSION['user_id']]);
        if ($cUpd->rowCount() === 0) {
            $uStmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
            $uStmt->execute([$_SESSION['user_id']]);
            $u = $uStmt->fetch() ?: ['full_name' => '', 'email' => ''];
            $pdo->prepare("INSERT INTO customers (user_id, full_name, email, total_orders, total_spent) VALUES (?, ?, ?, 1, ?)")->execute([$_SESSION['user_id'], $u['full_name'], $u['email'], $total]);
        }
        if ($qrPaid) {
            $pdo->prepare("INSERT INTO payments (order_id, amount, payment_method, transaction_id, status) VALUES (?, ?, 'qr', ?, 'completed')")->execute([$orderId, $total, 'QR-' . $orderNumber]);
            attachPaymentSessionOrder($pdo, $qrRef, $orderNumber);
            $qrPaidOk = true;
        }
        $pdo->commit();
        unset($_SESSION['cart']);
        $success = 'Order placed successfully! Order Number: ' . $orderNumber;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Order failed: ' . $e->getMessage();
    }
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Checkout - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Khmer+OS&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
<script src="<?php echo BASE_URL; ?>/public/js/qrcode.min.js"></script>
<script src="<?php echo BASE_URL; ?>/public/js/qr-pay.js"></script>
</head><body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<main class="container mt-4 pt-5">
<h2 class="section-title"><i class="bi bi-credit-card"></i> Checkout</h2>
<?php if ($error): ?><div class="alert alert-danger fade-up"><i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success fade-up"><i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<div class="row">
<div class="col-lg-8 fade-up">
<div class="card mb-4">
<div class="card-body">
<h5 class="fw-bold mb-3"><i class="bi bi-bag"></i> Order Summary</h5>
<div class="table-responsive">
<table class="table table-hover">
<thead><tr><th>Product</th><th>Size</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
<tbody>
<?php foreach($items as $i): ?>
<tr><td><?php echo htmlspecialchars($i['product']['name']); ?></td><td><?php echo htmlspecialchars($i['size'] ?: '-'); ?></td><td><?php echo $i['quantity']; ?></td><td><?php echo formatMoney($i['product']['price']); ?></td><td class="fw-bold"><?php echo formatMoney($i['subtotal']); ?></td></tr>
<?php endforeach; ?>
<tr class="fw-bold"><td colspan="3" class="text-end">Total:</td><td style="color:#6f4e37;font-size:1.2rem"><?php echo formatMoney($total); ?></td></tr>
</tbody></table>
</div>
</div></div>
<div class="card">
<div class="card-body">
<h5 class="fw-bold mb-3"><i class="bi bi-cash-coin"></i> Payment Method</h5>
<form method="POST" id="checkoutForm">
<?php echo csrf_field(); ?>
<input type="hidden" name="qr_ref" id="qrRefField" value="">
<div class="mb-3">
<label class="form-label">Payment Method</label>
<select name="payment_method" id="paymentMethod" class="form-select" required onchange="toggleQrBox()">
<?php foreach ($paymentMethods as $pm => $label): ?>
<option value="<?php echo $pm; ?>"><?php echo htmlspecialchars($label); ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="mb-3" id="qrBox" hidden>
<label class="form-label">Scan to pay</label>
<div class="card p-3 text-center" style="background:#fff">
<div style="display:flex;gap:20px;justify-content:center;align-items:center;flex-wrap:wrap">
<div>
<div id="custQrCode" style="display:flex;justify-content:center;min-height:190px"><?php if ($qrImageSrc !== ''): ?><img src="<?php echo $qrImageSrc; ?>" alt="Payment QR" style="width:190px;height:190px;object-fit:contain;border-radius:12px;border:1px solid #e0e0e0;background:#fff"><?php endif; ?></div>
</div>
<div style="text-align:left">
<div class="text-muted" style="font-size:.7rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase">Amount to pay</div>
<div style="font-size:1.8rem;font-weight:800;color:#6f4e37;line-height:1.2" id="custQrAmount"><?php echo formatMoney($total); ?></div>
<div class="text-muted" style="font-size:12px" id="custQrRef"></div>
<div class="text-muted" style="font-size:12px;margin-top:6px"><?php echo $qrImageSrc !== '' || $qrPayload !== '' ? 'Scan with your banking app - the price fills in automatically' : 'Press Scan QR to show the payment code'; ?></div>
</div>
</div>
<?php if ($qrImageSrc === '' && $qrPayload === ''): ?>
<p class="text-danger small mb-0 mt-2"><i class="bi bi-exclamation-triangle"></i> No store QR uploaded yet. Add it in Admin &rarr; Settings &rarr; Payment Settings.</p>
<?php endif; ?>
<div class="d-flex gap-2 justify-content-center mt-3">
<button type="button" class="btn btn-outline-secondary btn-sm" id="custQrScanBtn" onclick="custQrScan()"><i class="bi bi-upc-scan"></i> Scan QR</button>
</div>
<p class="text-muted small mt-2 mb-0" id="custQrHint"><?php echo ($qrImageSrc !== '' || $qrPayload !== '') ? 'Press Scan QR - pay with the banking app, then tap Payment received.' : 'Press Scan QR - show the payment code.'; ?></p>
</div>
</div>
<div class="mb-3">
<label class="form-label">Notes (Optional)</label>
<textarea name="notes" class="form-control" rows="3" placeholder="Any special instructions..."></textarea>
</div>
<button type="submit" id="checkoutSubmitBtn" class="btn btn-primary w-100 btn-checkout" style="font-size:1.1rem;padding:12px;"><i class="bi bi-check-lg"></i> Place Order</button>
</form>
</div></div>
</div>
<div class="col-lg-4 fade-up">
<div class="card">
<div class="card-body">
<h5 class="fw-bold mb-3"><i class="bi bi-receipt"></i> Order Total</h5>
<div class="d-flex justify-content-between mb-2"><span>Subtotal:</span><span><?php echo formatMoney($total); ?></span></div>
<div class="d-flex justify-content-between mb-2"><span>Shipping:</span><span class="text-success">Free</span></div>
<div class="d-flex justify-content-between mb-2"><span>Discount:</span><span class="text-success">0%</span></div>
<hr>
<div class="d-flex justify-content-between fs-4 fw-bold" style="color:#6f4e37"><span>Total:</span><span><?php echo formatMoney($total); ?></span></div>
</div></div>
</div>
</div>
</main>
<script>
var qrStore = <?php echo json_encode(siteName()); ?>;
var qrCurrency = <?php echo json_encode(setting('currency', 'USD')); ?>;
var storeQrSrc = <?php echo json_encode($qrImageSrc); ?>;
var storeQrPayload = <?php echo json_encode($qrPayload); ?>;
var qrAmountText = <?php echo json_encode(formatMoney($total)); ?>;
var qrAmountNum = <?php echo json_encode(round($total, 2)); ?>;
var qrState = { ref: '', scanned: false, rendered: false, session: null };

function payFetch(url, body){
  return fetch(url, { method:'POST', headers:{ 'Content-Type':'application/x-www-form-urlencoded' }, body: body || '', credentials:'include' })
    .then(function(r){ return r.text().then(function(t){
      try { return JSON.parse(t); } catch (e) {
        var clean = t.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
        throw new Error(clean ? clean.slice(0, 160) : 'Server error');
      }
    }); });
}
function payErr(e){ var m = (e && e.message) ? e.message : 'Network error - check your connection'; return m.length > 160 ? m.slice(0, 160) + '...' : m; }

function syncCustQr(){
  var scanBtn = document.getElementById('custQrScanBtn');
  var hint = document.getElementById('custQrHint');
  var refEl = document.getElementById('custQrRef');
  var submitBtn = document.getElementById('checkoutSubmitBtn');
  var sel = document.getElementById('paymentMethod');
  if (refEl) refEl.textContent = qrState.ref ? 'Ref: ' + qrState.ref : '';
  if (scanBtn) {
    scanBtn.innerHTML = qrState.scanned
      ? '<i class="bi bi-check-lg"></i> Paid'
      : '<i class="bi bi-upc-scan"></i> Scan QR';
    scanBtn.disabled = qrState.scanned;
  }
  if (hint && !hint.classList.contains('text-danger')) {
    hint.textContent = qrState.scanned
      ? 'Payment confirmed - placing your order.'
      : (storeQrSrc || storeQrPayload
          ? 'Press Scan QR - pay with the banking app, then tap Payment received.'
          : 'Press Scan QR - show the payment code.');
  }
  if (submitBtn) {
    if (sel && sel.value === 'qr') {
      submitBtn.innerHTML = qrState.scanned
        ? '<i class="bi bi-check2-circle"></i> Processing...'
        : '<i class="bi bi-qr-code"></i> Scan QR & Place Order';
    } else {
      submitBtn.innerHTML = '<i class="bi bi-check-lg"></i> Place Order';
    }
  }
}

function renderCustQr(){
  if (qrState.rendered) return;
  qrState.rendered = true;
  var host = document.getElementById('custQrCode');
  if (storeQrPayload) {
    var generated = QrPay.withAmount(storeQrPayload, Number(qrAmountNum), qrCurrency);
    if (generated) { QrPay.render(host, generated, 190); return; }
  }
  if (!storeQrSrc) {
    QrPay.render(host, QrPay.payload({
      store: qrStore, ref: qrState.ref, amountText: qrAmountText, currency: qrCurrency
    }), 190);
  }
}

function custSessionCancel(){
  var s = qrState.session;
  qrState.session = null;
  qrState.ref = '';
  if (s && s.state === 'pending') {
    payFetch(<?php echo json_encode(BASE_URL . '/ajax/pay.php?action=cancel'); ?>, 'ref=' + encodeURIComponent(s.ref)).catch(function(){});
  }
  var refEl = document.getElementById('custQrRef');
  if (refEl) refEl.textContent = '';
}

function custSessionStatus(){
  if (!qrState.session) return Promise.resolve({ success: false, state: 'not_found' });
  return payFetch(<?php echo json_encode(BASE_URL . '/ajax/pay.php?action=status&ref='); ?> + encodeURIComponent(qrState.session.ref))
    .then(function(d){ if (qrState.session) qrState.session.state = d.state; return d; });
}

function custConfirmPaid(){
  var s = qrState.session;
  if (!s) return Promise.reject(new Error('No QR session - close this and press Scan QR again'));
  if (s.state === 'completed') return Promise.resolve(s);
  return payFetch(<?php echo json_encode(BASE_URL . '/pay.php?action=confirm'); ?>, 'ref=' + encodeURIComponent(s.ref))
    .then(function(d){
      if (!d || !d.success) throw new Error((d && d.error) || 'Could not confirm the payment');
      s.state = 'completed';
      s.order = d.order || s.order || '';
      return s;
    });
}

function custSessionAmountOk(s){
  return s && Number(Number(s.amount).toFixed(2)) === Number(Number(qrAmountNum).toFixed(2));
}

function custEnsureSession(){
  var s = qrState.session;
  if (s && (s.state === 'completed' || (s.state === 'pending' && custSessionAmountOk(s)))) {
    return Promise.resolve(s);
  }
  custSessionCancel();
  return payFetch(<?php echo json_encode(BASE_URL . '/ajax/pay.php?action=create'); ?>, 'source=checkout')
    .then(function(d){
      if (!d.success) throw new Error(d.error || 'Could not start the QR payment');
      qrState.session = { ref: d.ref, amount: d.amount, state: d.state, pay_url: d.pay_url };
      qrState.ref = d.ref;
      var refEl = document.getElementById('custQrRef');
      if (refEl) refEl.textContent = 'Ref: ' + d.ref;
      return qrState.session;
    });
}

function custOpenPopup(){
  var s = qrState.session;
  if (!s) return;
  QrPay.openScan({
    ref: s.ref,
    amountText: s.amount_text || qrAmountText,
    currency: qrCurrency,
    payload: s.pay_url,
    amount: Number(s.amount),
    mainSize: 232,
    bankQr: storeQrSrc,
    bankQrPayload: storeQrPayload,
    bankQrCurrency: qrCurrency,
    hint: (storeQrSrc || storeQrPayload)
      ? 'Pay with the banking app, then tap Payment received.'
      : 'Show this code.',
    pollMs: 1500,
    statusFn: custSessionStatus,
    confirmFn: custConfirmPaid,
    doneLabel: 'Payment received',
    onClose: function(){ custSessionCancel(); },
    onDone: function(){
      qrState.scanned = true;
      syncCustQr();
      var f = document.getElementById('qrRefField');
      if (f) f.value = qrState.session ? qrState.session.ref : '';
      document.getElementById('checkoutForm').submit();
    }
  });
}

function resetCustQr(){
  qrState.scanned = false;
  qrState.rendered = false;
  if (qrState.session) custSessionCancel();
  qrState.ref = '';
  var host = document.getElementById('custQrCode');
  if (host && !storeQrSrc) host.innerHTML = '';
  var f = document.getElementById('qrRefField');
  if (f) f.value = '';
  var hint = document.getElementById('custQrHint');
  if (hint) hint.classList.remove('text-danger');
  syncCustQr();
}

function toggleQrBox(){
  var sel = document.getElementById('paymentMethod');
  var box = document.getElementById('qrBox');
  if (!sel || !box) return;
  var isQr = sel.value === 'qr';
  box.hidden = !isQr;
  if (!isQr) { resetCustQr(); return; }
  renderCustQr();
  syncCustQr();
}

function custQrScan(){
  if (qrState.scanned) return;
  custEnsureSession()
    .then(function(){ custOpenPopup(); })
    .catch(function(e){
      var hint = document.getElementById('custQrHint');
      if (hint) { hint.textContent = payErr(e); hint.classList.add('text-danger'); }
    });
}

document.getElementById('checkoutForm').addEventListener('submit', function(e){
  var sel = document.getElementById('paymentMethod');
  if (!sel || sel.value !== 'qr') return;
  if (qrState.scanned) {
    var f = document.getElementById('qrRefField');
    if (f && qrState.session) f.value = qrState.session.ref;
    return;
  }
  e.preventDefault();
  custQrScan();
});

toggleQrBox();
<?php if ($qrPaidOk && !empty($orderNumber)): ?>
QrPay.successScreen({
  amountText: <?php echo json_encode(formatMoney($total)); ?>,
  currency: <?php echo json_encode(setting('currency', 'USD')); ?>,
  order: <?php echo json_encode($orderNumber); ?>,
  ref: <?php echo json_encode('QR-' . $orderNumber); ?>,
  message: 'Thank you for your payment!',
  duration: 3500,
  onEnd: function(){ location.href = <?php echo json_encode(BASE_URL . '/customer/orders.php'); ?>; }
});
<?php endif; ?>
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>