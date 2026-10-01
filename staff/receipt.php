<?php
require_once __DIR__ . '/../includes/functions.php';
requireStaff();
requireLogin();
allowRoles(['admin', 'staff']);
$pdo = getPDO();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT o.*, ui.full_name as customer_name, (SELECT full_name FROM users WHERE id = o.staff_id) as staff_name FROM orders o LEFT JOIN users ui ON o.user_id = ui.id WHERE o.id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) redirect(BASE_URL . '/staff/orders.php');
$items = $pdo->prepare("SELECT oi.*, p.name as product_name, oi.size FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();
$settings = getAllSettings($pdo);
$settingsMap = [];
foreach ($settings as $s) {
    $settingsMap[$s['setting_key']] = $s['setting_value'];
}
$siteName = htmlspecialchars(siteName());
$siteAddress = htmlspecialchars($settingsMap['site_address'] ?? '');
$sitePhone = htmlspecialchars($settingsMap['site_phone'] ?? '');
$siteEmail = htmlspecialchars($settingsMap['site_email'] ?? '');
$taxRate = (float)($settingsMap['tax_rate'] ?? 0);
$subtotal = array_sum(array_column($items, 'subtotal'));
$discount = (float)($order['discount'] ?? 0);
$taxAmt = $subtotal * ($taxRate / 100);
$grandTotal = (float)$order['total_amount'];
// stored total = subtotal + tax - discount; only show tax when it is really part of it
$showTax = $taxAmt > 0 && abs(($subtotal + $taxAmt - $discount) - $grandTotal) < 0.05;
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Receipt - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body{background:#FFF8F0;font-family:'Segoe UI','Khmer OS',Tahoma,sans-serif}
.receipt-container{max-width:450px;margin:40px auto;padding:40px;background:#fff;border-radius:16px;box-shadow:0 10px 40px rgba(62,39,35,.1);animation:scaleIn .5s ease}
.receipt-header{text-align:center;margin-bottom:25px;padding-bottom:15px;border-bottom:3px solid #6F4E37}
.receipt-header h2{color:#3E2723;font-weight:800;margin:0;font-size:1.5rem}
.receipt-header p{color:#666;margin:3px 0 0;font-size:.9rem}
.receipt-info{background:#FAF6F0;border-radius:10px;padding:12px;margin-bottom:15px}
.receipt-info p{margin:2px 0;font-size:.85rem}
.receipt-table .table th{background:#6F4E37;color:#fff;border:none;padding:8px 6px;font-size:.85rem}
.receipt-table .table td{padding:6px 6px;font-size:.85rem}
.receipt-table .table-bordered{border:0}
.receipt-table .table-bordered th,.receipt-table .table-bordered td{border-bottom:1px solid #f0f0f0}
.receipt-total{display:flex;justify-content:space-between;font-size:1rem;font-weight:800;padding:4px 0}
.receipt-total.receipt-discount{color:#C0392B}
.receipt-grand{display:flex;justify-content:space-between;font-size:1.1rem;font-weight:800;color:#6F4E37;padding:6px 0;border-top:2px solid #6F4E37;margin-top:4px}
.receipt-footer{margin-top:15px;padding-top:12px;border-top:1px solid #eee;font-size:.8rem;color:#666;text-align:center}
@media print{body{background:#fff}.no-print{display:none!important}.receipt-container{box-shadow:none;border:0}}
@media(max-width:500px){.receipt-container{margin:10px;padding:20px}}
</style>
</head><body>
<div class="receipt-container" id="receipt">
<div class="receipt-header">
<h2><i class="bi bi-cup-fill"></i> <?php echo $siteName; ?></h2>
<?php if($siteAddress): ?><p><?php echo $siteAddress; ?></p><?php endif; ?>
<?php if($sitePhone): ?><p><?php echo $sitePhone; ?></p><?php endif; ?>
</div>
<div class="receipt-info">
<div class="d-flex justify-content-between"><strong>Order:</strong> <span>#<?php echo htmlspecialchars($order['order_number']); ?></span></div>
<div class="d-flex justify-content-between"><strong>Date:</strong> <span><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></span></div>
<div class="d-flex justify-content-between"><strong>Customer:</strong> <span><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></span></div>
<div class="d-flex justify-content-between"><strong>Staff:</strong> <span><?php echo htmlspecialchars($order['staff_name'] ?? 'N/A'); ?></span></div>
<div class="d-flex justify-content-between"><strong>Payment:</strong> <span><?php echo ucfirst($order['payment_method'] ?? 'cash'); ?></span></div>
<div class="d-flex justify-content-between"><strong>Status:</strong> <span><?php echo ucfirst($order['status']); ?></span></div>
</div>
<div class="receipt-table"><table class="table table-bordered"><thead><tr><th>Product</th><th>Size</th><th>Qty</th><th class="text-end">Price</th><th class="text-end">Sub</th></tr></thead><tbody>
<?php foreach($items as $i): ?>
<tr><td><?php echo htmlspecialchars($i['product_name']); ?></td><td><?php echo htmlspecialchars($i['size'] ?? '-'); ?></td><td><?php echo $i['quantity']; ?></td><td class="text-end"><?php echo formatMoney($i['unit_price']); ?></td><td class="text-end"><?php echo formatMoney($i['subtotal']); ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<div class="receipt-total"><span>Subtotal:</span><span><?php echo formatMoney($subtotal); ?></span></div>
<?php if ($discount > 0): ?>
<div class="receipt-total receipt-discount"><span>Discount:</span><span>-<?php echo formatMoney($discount); ?></span></div>
<?php endif; ?>
<?php if ($showTax): ?><div class="receipt-total"><span>Tax (<?php echo $taxRate; ?>%):</span><span><?php echo formatMoney($taxAmt); ?></span></div><?php endif; ?>
<div class="receipt-grand"><span>Total:</span><span><?php echo formatMoney($grandTotal); ?></span></div>
<div class="receipt-footer">
<p>Thank you for your order!</p>
<p class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm mt-2"><i class="bi bi-printer"></i> Print Receipt</button></p>
</div>
</div>
</body></html>
<script>window.onload = function(){ window.print(); }</script>