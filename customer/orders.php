<?php
require_once __DIR__ . '/../includes/functions.php';
if (!isLoggedIn()) redirect(BASE_URL . '/auth/login.php');
$pdo = getPDO();
$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status != 'cancelled' ORDER BY created_at DESC LIMIT 5");
$recentOrders = $stmt->fetchAll();
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalSpent = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders")->fetchColumn();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Orders - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Khmer+OS&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
</head><body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<main class="container mt-4 pt-5">
    <h2 class="section-title"><i class="bi bi-receipt"></i> My Orders</h2>
    <div class="stats-grid mb-4">
        <div class="stat-card fade-up"><div class="stat-icon" style="background:#e3f2fd;color:#0d6efd"><i class="bi bi-bag"></i></div><div class="stat-info"><h3><?php echo $totalOrders; ?></h3><p>Total Orders</p></div></div>
        <div class="stat-card fade-up"><div class="stat-icon" style="background:#e8f5e9;color:#198754"><i class="bi bi-currency-dollar"></i></div><div class="stat-info"><h3><?php echo formatMoney($totalSpent); ?></h3><p>Total Spent</p></div></div>
        <div class="stat-card fade-up"><div class="stat-icon" style="background:#fff3e0;color:#ff9800"><i class="bi bi-clock"></i></div><div class="stat-info"><h3>3</h3><p>Pending</p></div></div>
    </div>
    <div class="card fade-up">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Order #</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if (!empty($recentOrders)): ?>
                        <?php foreach($recentOrders as $order): ?>
                        <tr class="cart-item-row">
                            <td class="fw-bold"><?php echo htmlspecialchars($order['order_number']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($order['item_count']); ?></td>
                            <td class="fw-bold" style="color:#6f4e37"><?php echo formatMoney($order['total_amount']); ?></td>
                            <td><span class="status-pending"><?php echo ucfirst($order['status']); ?></span></td>
                            <td><a href="<?php echo BASE_URL; ?>/customer/order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center"><div class="empty-state"><i class="bi bi-receipt"></i><h4>No orders yet</h4></div></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>