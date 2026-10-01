<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
$pdo = getPDO();
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT o.*, ui.full_name as user_name FROM orders o LEFT JOIN users ui ON o.user_id = ui.id WHERE o.id = ? AND o.user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$order = $stmt->fetch();
if (!$order) redirect(BASE_URL . '/customer/orders.php');
$items = $pdo->prepare("SELECT oi.*, p.name as product_name, oi.size FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();
$subtotal = array_sum(array_column($items, 'subtotal'));
$discount = (float)($order['discount'] ?? 0);
$grandTotal = (float)$order['total_amount'];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Receipt - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
<style>
body{background:#f8f9fa;font-family:'Segoe UI','Khmer OS',Tahoma,sans-serif}
.receipt-container{max-width:500px;margin:40px auto;padding:40px;background:#fff;border-radius:16px;box-shadow:0 10px 40px rgba(0,0,0,.1);animation:scaleIn .5s ease}
.receipt-header{text-align:center;margin-bottom:30px;padding-bottom:20px;border-bottom:3px solid #6f4e37}
.receipt-header h2{color:#6f4e37;font-weight:800;margin:0}
.receipt-header p{color:#666;margin:4px 0 0}
.receipt-table .table th{background:#6f4e37;color:#fff;border:none;padding:12px}
.receipt-table .table td{padding:10px 12px;vertical-align:middle}
.receipt-table .table-bordered{border:0}
.receipt-table .table-bordered th,.receipt-table .table-bordered td{border-bottom:1px solid #f0f0f0}
.receipt-total{text-align:right;font-size:1.2rem;font-weight:800;color:#6f4e37;margin-top:10px}
.receipt-footer{margin-top:20px;padding-top:15px;border-top:1px solid #eee;display:flex;justify-content:space-between;color:#666;font-size:.9rem}
@media print{.no-print{display:none!important}.receipt-container{box-shadow:none;border:0}}
@media(max-width:576px){.receipt-container{margin:10px;padding:20px}}
</style>
</head><body>
<div class="receipt-container" id="receipt">
<div class="receipt-header">
<h2><i class="bi bi-cup-fill"></i> <?php echo htmlspecialchars(siteName()); ?></h2>
<p><strong>Receipt #<?php echo htmlspecialchars($order['order_number']); ?></strong></p>
<p>Date: <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
</div>
<div class="receipt-table"><table class="table table-bordered"><thead><tr><th>Product</th><th>Size</th><th>Qty</th><th class="text-end">Price</th><th class="text-end">Subtotal</th></tr></thead><tbody>
<?php foreach($items as $i): ?>
<tr><td><?php echo htmlspecialchars($i['product_name']); ?></td><td><?php echo htmlspecialchars($i['size'] ?? '-'); ?></td><td><?php echo $i['quantity']; ?></td><td class="text-end"><?php echo formatMoney($i['unit_price']); ?></td><td class="text-end"><?php echo formatMoney($i['subtotal']); ?></td></tr>
<?php endforeach; ?>
<tr class="fw-bold"><td colspan="3" class="text-end">Subtotal:</td><td class="text-end" style="color:#6f4e37;font-size:1.2rem"><?php echo formatMoney($subtotal); ?></td></tr>
</tbody></table></div>
<?php if ($discount > 0): ?>
<div class="d-flex justify-content-between text-danger fw-bold mb-1"><span>Discount</span><span>-<?php echo formatMoney($discount); ?></span></div>
<?php endif; ?>
<div class="d-flex justify-content-between fw-bold" style="color:#6f4e37;font-size:1.3rem"><span>Total</span><span><?php echo formatMoney($grandTotal); ?></span></div>
<div class="receipt-footer">
<div><i class="bi bi-person"></i> <?php echo htmlspecialchars($order['user_name']); ?></div>
<div><i class="bi bi-credit-card"></i> <?php echo ucfirst($order['payment_method']); ?></div>
</div>
<div class="text-center mt-3 no-print">
<button onclick="window.print()" class="btn btn-primary btn-checkout"><i class="bi bi-printer"></i> Print</button>
<button onclick="downloadPDF()" class="btn btn-success"><i class="bi bi-download"></i> Download PDF</button>
<a href="<?php echo BASE_URL; ?>/customer/orders.php" class="btn btn-outline-secondary">Back to Orders</a>
</div>
</div>
</body></html>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.4.1/html2pdf.bundle.min.js"></script>
<script>
function downloadPDF(){
const el=document.getElementById('receipt');
const opt={margin:10,filename:'receipt_<?php echo htmlspecialchars($order['order_number']); ?>.pdf',image:{type:'jpeg',quality:0.98},html2canvas:{scale:2},jsPDF:{unit:'mm',format:'a4',orientation:'portrait'}};
html2pdf().set(opt).from(el).save();
}
</script>