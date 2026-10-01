<?php
require_once __DIR__ . '/includes/functions.php';
$pdo = getPDO();
$ref = trim($_GET['ref'] ?? ($_POST['ref'] ?? ''));
$session = $ref !== '' ? getPaymentSession($pdo, $ref) : null;
$state = paymentSessionState($session);

$payAction = $_GET['action'] ?? ($_POST['action'] ?? '');
if ($payAction === 'confirm') {
    header('Content-Type: application/json');
    if (!$session) { echo json_encode(['success' => false, 'state' => 'not_found', 'error' => 'Payment session not found']); exit; }
    $done = completePaymentSession($pdo, $ref);
    echo json_encode([
        'success' => $done && $done['status'] === 'completed',
        'state' => paymentSessionState($done),
        'ref' => $done['ref'] ?? $ref,
        'amount' => (float) ($done['amount'] ?? 0),
        'amount_text' => formatMoney($done['amount'] ?? 0),
        'currency' => $done['currency'] ?? setting('currency', 'USD'),
        'order' => $done['order_number'] ?? null
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Pay - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="./public/css/style.css?v=3" rel="stylesheet">
<script src="./public/js/qr-pay.js"></script>
<style>
  body { background: linear-gradient(160deg, #F7F1EA, #EFE3D8); min-height: 100vh; }
  .pay-wrap { max-width: 420px; margin: 0 auto; padding: 34px 16px; }
  .pay-card { background: #fff; border-radius: 20px; padding: 26px 20px; text-align: center; box-shadow: 0 16px 44px rgba(60,35,20,.14); }
  .pay-logo { width: 54px; height: 54px; border-radius: 50%; background: linear-gradient(135deg,#6F4E37,#3E2723); color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.5rem; margin: 0 auto 10px; }
  .pay-store { font-weight: 800; color: #3E2723; font-size: 1.05rem; }
  .pay-label { font-size: .66rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #A08D80; margin-top: 18px; }
  .pay-amount { font-size: 2.9rem; font-weight: 900; color: #5D4037; line-height: 1.1; }
  .pay-cur { font-size: .75rem; font-weight: 800; letter-spacing: .16em; color: #A08D80; }
  .pay-ref { font-size: .78rem; color: #888; margin-top: 8px; }
  .pay-status { margin-top: 18px; padding: 12px; border-radius: 12px; background: #F6EFE8; font-size: .85rem; color: #6F4E37; font-weight: 700; }
  .pay-status.err { background: #FDECEA; color: #C0392B; }
  .pay-status.ok { background: #E8F7EF; color: #1E7A4F; }
  .pay-btn { margin-top: 14px; width: 100%; border: none; border-radius: 12px; padding: 13px; font-weight: 800; font-size: 1rem; color: #fff; background: linear-gradient(135deg,#1E7A4F,#14603B); }
  .pay-btn:disabled { opacity: .6; }
  .pay-foot { font-size: .74rem; color: #9a9a9a; margin-top: 16px; }
</style>
</head>
<body>
<main class="pay-wrap">
  <div class="pay-card">
    <?php if (!$session): ?>
      <div class="pay-logo"><i class="bi bi-exclamation-triangle"></i></div>
      <div class="pay-store">Payment not found</div>
      <div class="pay-status err">This payment link is invalid or has been removed.</div>
    <?php else: ?>
      <div class="pay-logo"><i class="bi bi-cup-hot"></i></div>
      <div class="pay-store"><?php echo htmlspecialchars(siteName()); ?></div>
      <div class="pay-label">Amount to pay</div>
      <div class="pay-amount" id="payAmount"><?php echo formatMoney($session['amount']); ?></div>
      <div class="pay-cur"><?php echo htmlspecialchars($session['currency']); ?></div>
      <div class="pay-ref">Reference: <?php echo htmlspecialchars($session['ref']); ?></div>
      <div class="pay-status" id="payStatus">
        <?php
        if ($state === 'completed') echo 'Payment already confirmed.';
        elseif ($state === 'cancelled') echo 'This payment was cancelled.';
        elseif ($state === 'expired') echo 'This payment link has expired.';
        else echo 'Confirming your payment automatically...';
        ?>
      </div>
      <?php if ($state === 'pending'): ?>
      <button type="button" class="pay-btn" id="payConfirmBtn"><i class="bi bi-check2-circle"></i> Confirm payment</button>
      <?php endif; ?>
      <div class="pay-foot">You scanned the QR code from <?php echo htmlspecialchars(siteName()); ?>.</div>
    <?php endif; ?>
  </div>
</main>
<script>
var payRef = <?php echo json_encode($session['ref'] ?? ''); ?>;
var payState = <?php echo json_encode($state); ?>;
var payBusy = false;

function payStatus(msg, cls) {
  var el = document.getElementById('payStatus');
  if (!el) return;
  el.className = 'pay-status' + (cls ? ' ' + cls : '');
  el.textContent = msg;
}

function paySuccess(d) {
  payState = 'completed';
  payStatus('Payment confirmed. Thank you!', 'ok');
  var btn = document.getElementById('payConfirmBtn');
  if (btn) btn.disabled = true;
  QrPay.successScreen({
    amountText: d.amount_text,
    currency: d.currency,
    ref: d.ref,
    order: d.order || '',
    message: 'Payment confirmed. Thank you!',
    duration: 4000
  });
}

function payConfirm() {
  if (payBusy || payState === 'completed') return;
  payBusy = true;
  var btn = document.getElementById('payConfirmBtn');
  if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Confirming...'; }
  fetch('pay.php?ref=' + encodeURIComponent(payRef) + '&action=confirm', { method: 'POST' })
    .then(function (r) { return r.json(); })
    .then(function (d) {
      payBusy = false;
      if (d && d.success) { paySuccess(d); return; }
      payStatus((d && d.error) ? d.error : 'Could not confirm the payment', 'err');
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check2-circle"></i> Confirm payment'; }
    })
    .catch(function () {
      payBusy = false;
      payStatus('Network error - tap the button to try again', 'err');
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check2-circle"></i> Confirm payment'; }
    });
}

document.addEventListener('DOMContentLoaded', function () {
  if (payState === 'pending') {
    setTimeout(payConfirm, 1200);
  } else if (payState === 'completed') {
    var el = document.getElementById('payAmount');
    QrPay.successScreen({
      amountText: el ? el.textContent : '',
      currency: <?php echo json_encode($session['currency'] ?? ''); ?>,
      ref: <?php echo json_encode($session['ref'] ?? ''); ?>,
      order: <?php echo json_encode($session['order_number'] ?? ''); ?>,
      message: 'Payment confirmed. Thank you!',
      duration: 4000
    });
  }
});
</script>
</body>
</html>
