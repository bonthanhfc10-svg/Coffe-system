<?php
require_once __DIR__ . '/../includes/functions.php';
requireStaff();
$pdo = getPDO();
$page = $_GET['page'] ?? 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$paymentFilter = $_GET['payment'] ?? '';
$dateFilter = $_GET['date'] ?? '';
$where = [];
$params = [];
$where[] = 'o.staff_id = ?';
$params[] = $_SESSION['user_id'];
if ($search) {
    $where[] = '(o.order_number LIKE ? OR ui.full_name LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($statusFilter) {
    $where[] = 'o.status = ?';
    $params[] = $statusFilter;
}
if ($paymentFilter) {
    $where[] = 'o.payment_method = ?';
    $params[] = $paymentFilter;
}
if ($dateFilter) {
    $where[] = 'DATE(o.created_at) = ?';
    $params[] = $dateFilter;
}
$whereClause = 'WHERE ' . implode(' AND ', $where);
$orders = $pdo->prepare("SELECT o.*, ui.full_name, COUNT(oi.id) as item_count, SUM(oi.subtotal) as sub_total FROM orders o LEFT JOIN users ui ON o.user_id = ui.id LEFT JOIN order_items oi ON o.id = oi.order_id $whereClause GROUP BY o.id ORDER BY o.created_at DESC LIMIT ? OFFSET ?");
$allParams = array_merge($params, [$perPage, $offset]);
$orders->execute($allParams);
$orderList = $orders->fetchAll();
$totalCount = $pdo->prepare("SELECT COUNT(DISTINCT o.id) FROM orders o LEFT JOIN users ui ON o.user_id = ui.id $whereClause");
$totalCount->execute($params);
$totalPages = ceil($totalCount->fetchColumn() / $perPage);
$statuses = ['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'];
$statusColors = ['pending' => 'warning', 'confirmed' => 'info', 'preparing' => 'primary', 'ready' => 'dark', 'completed' => 'success', 'cancelled' => 'danger'];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Orders - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
</head><body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:260px;">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5">
<div class="page-header fade-down">
    <h2><i class="bi bi-receipt"></i> Orders</h2>
</div>
<div class="filter-bar fade-up mb-4">
<div class="row g-2">
<div class="col-md-3"><div class="pos-search"><i class="bi bi-search"></i><input type="text" class="form-control" placeholder="Search order or customer..." value="<?php echo htmlspecialchars($search); ?>" onkeydown="if(event.key==='Enter')location.href='?search='+this.value+'&status=<?php echo $statusFilter; ?>&payment=<?php echo $paymentFilter; ?>&date=<?php echo $dateFilter; ?>'"></div></div>
<div class="col-md-2"><select class="form-select" onchange="location.href='?search=<?php echo $search; ?>&status='+this.value+'&payment=<?php echo $paymentFilter; ?>&date=<?php echo $dateFilter; ?>'"><option value="">All Status</option><?php foreach($statuses as $s): ?><option value="<?php echo $s; ?>" <?php echo $statusFilter===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><select class="form-select" onchange="location.href='?search=<?php echo $search; ?>&status=<?php echo $statusFilter; ?>&payment='+this.value+'&date=<?php echo $dateFilter; ?>'"><option value="">All Payment</option><option value="cash" <?php echo $paymentFilter==='cash'?'selected':''; ?>>Cash</option><option value="card" <?php echo $paymentFilter==='card'?'selected':''; ?>>Card</option><option value="qr" <?php echo $paymentFilter==='qr'?'selected':''; ?>>QR</option></select></div>
<div class="col-md-2"><input type="date" class="form-control" value="<?php echo $dateFilter; ?>" onchange="location.href='?search=<?php echo $search; ?>&status=<?php echo $statusFilter; ?>&payment=<?php echo $paymentFilter; ?>&date='+this.value"></div>
</div></div>
<div class="table-responsive fade-up">
<table class="table table-hover">
<thead><tr><th>Order #</th><th>Customer</th><th>Type</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
<tbody>
<?php foreach($orderList as $o): ?>
<tr class="cart-item-row">
<td class="fw-bold"><?php echo htmlspecialchars($o['order_number']); ?></td>
<td><?php echo htmlspecialchars($o['full_name'] ?? 'N/A'); ?></td>
<td><span class="badge badge-custom"><?php echo ucfirst($o['order_type'] ?? 'dine_in'); ?></span></td>
<td><?php echo $o['item_count'] ?? 0; ?></td>
<td class="fw-bold" style="color:#6F4E37"><?php echo formatMoney($o['total_amount']); ?></td>
<td><span class="badge bg-info"><?php echo ucfirst($o['payment_method'] ?? 'cash'); ?></span></td>
<td><span class="badge bg-<?php echo $statusColors[$o['status']] ?? 'secondary'; ?>"><?php echo ucfirst($o['status']); ?></span></td>
<td><small><?php echo date('M d, Y H:i', strtotime($o['created_at'])); ?></small></td>
<td><a href="order-details.php?id=<?php echo $o['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
<?php if (empty($orderList)): ?>
<div class="alert alert-info text-center fade-up">No orders found</div>
<?php endif; ?>
<?php echo renderPagination($page, $totalPages, '?search=' . $search . '&status=' . $statusFilter . '&payment=' . $paymentFilter . '&date=' . $dateFilter . '&page='); ?>
</main></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>