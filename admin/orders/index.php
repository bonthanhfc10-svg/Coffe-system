<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole('admin');
$pdo = getPDO();
$statusColors = ['pending' => 'warning', 'confirmed' => 'info', 'preparing' => 'primary', 'ready' => 'dark', 'completed' => 'success', 'cancelled' => 'danger'];
$statusFilter = $_GET['status'] ?? '';
if (!isset($statusColors[$statusFilter])) $statusFilter = '';
[$filterType, $filterValue] = periodFilter('month');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$conds = [];
$params = [];
if ($statusFilter) {
    $conds[] = 'o.status = ?';
    $params[] = $statusFilter;
}
[$periodCondition, $periodParams] = periodWhere($filterType, $filterValue, 'o.created_at');
$conds[] = $periodCondition;
foreach ($periodParams as $pp) $params[] = $pp;
$whereClause = 'WHERE ' . implode(' AND ', $conds);

$stmt = $pdo->prepare("SELECT o.*, ui.full_name FROM orders o LEFT JOIN users ui ON o.user_id = ui.id $whereClause ORDER BY o.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([...$params, $perPage, $offset]);
$orders = $stmt->fetchAll();

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o $whereClause");
$countStmt->execute($params);
$totalCount = $countStmt->fetchColumn();
$totalPages = ceil($totalCount / $perPage);

$statsStmt = $pdo->prepare("SELECT COUNT(*) as total, COALESCE(SUM(CASE WHEN o.status='completed' THEN 1 ELSE 0 END),0) as completed, COALESCE(SUM(CASE WHEN o.status='pending' THEN 1 ELSE 0 END),0) as pending, COALESCE(SUM(o.total_amount),0) as revenue, COALESCE(SUM(o.discount),0) as discount FROM orders o $whereClause");
$statsStmt->execute($params);
$stats = $statsStmt->fetch();

$periodLabel = ['day' => 'Day', 'month' => 'Month', 'year' => 'Year'][$filterType];
$filterQs = '&filter_type=' . urlencode($filterType) . '&filter_value=' . urlencode($filterValue);
$baseUrl = '?status=' . urlencode($statusFilter) . $filterQs . '&page=';
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Orders - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Khmer+OS&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
</head><body>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:260px;">
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5">
<div class="page-header fade-down">
    <h2><i class="bi bi-receipt"></i> All Orders</h2>
    <div class="btn-group">
        <a href="index.php<?php echo '?' . ltrim($filterQs, '&'); ?>" class="btn btn-sm btn-outline-dark <?php echo !$statusFilter ? 'active' : ''; ?>">All</a>
        <?php foreach(array_keys($statusColors) as $s): ?>
        <a href="index.php?status=<?php echo $s; echo $filterQs; ?>" class="btn btn-sm btn-outline-dark <?php echo $statusFilter===$s ? 'active' : ''; ?>"><?php echo ucfirst($s); ?></a>
        <?php endforeach; ?>
    </div>
</div>
<div class="card shadow-sm mb-4 fade-up">
<div class="card-body">
<form method="get" class="row g-3 align-items-end">
<?php if ($statusFilter): ?><input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>"><?php endif; ?>
<div class="col-md-2"><button type="submit" name="filter_type" value="day" class="btn w-100 <?php echo $filterType==='day'?'btn-dark':'btn-outline-primary'; ?>" style="border-radius:12px">Day</button></div>
<div class="col-md-2"><button type="submit" name="filter_type" value="month" class="btn w-100 <?php echo $filterType==='month'?'btn-dark':'btn-outline-primary'; ?>" style="border-radius:12px">Month</button></div>
<div class="col-md-2"><button type="submit" name="filter_type" value="year" class="btn w-100 <?php echo $filterType==='year'?'btn-dark':'btn-outline-primary'; ?>" style="border-radius:12px">Year</button></div>
<div class="col-md-4">
<?php if ($filterType === 'day'): ?>
<input type="date" name="filter_value" class="form-control" value="<?php echo htmlspecialchars($filterValue); ?>" style="border-radius:12px">
<?php elseif ($filterType === 'month'): ?>
<input type="month" name="filter_value" class="form-control" value="<?php echo htmlspecialchars($filterValue); ?>" style="border-radius:12px">
<?php else: ?>
<select name="filter_value" class="form-select" style="border-radius:12px">
<?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
<option value="<?php echo $y; ?>" <?php echo $filterValue == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
<?php endfor; ?>
</select>
<?php endif; ?>
</div>
<div class="col-md-2"><button type="submit" class="btn btn-primary w-100" style="border-radius:12px">Apply</button></div>
</form>
<div class="text-muted small mt-2"><i class="bi bi-funnel"></i> Showing: <?php echo $periodLabel; ?> &mdash; <?php echo htmlspecialchars($filterValue); ?><?php echo $statusFilter ? ' &middot; ' . ucfirst($statusFilter) : ''; ?> &middot; <?php echo $totalCount; ?> order(s)</div>
</div></div>
<div class="stats-grid mb-4">
    <div class="stat-card fade-up"><div class="stat-icon" style="background:#e3f2fd;color:#0d6efd"><i class="bi bi-bag"></i></div><div class="stat-info"><h3><?php echo $stats['total']; ?></h3><p>Total Orders</p></div></div>
    <div class="stat-card fade-up"><div class="stat-icon" style="background:#e8f5e9;color:#198754"><i class="bi bi-check-circle"></i></div><div class="stat-info"><h3><?php echo $stats['completed']; ?></h3><p>Completed</p></div></div>
    <div class="stat-card fade-up"><div class="stat-icon" style="background:#fff3e0;color:#ff9800"><i class="bi bi-clock"></i></div><div class="stat-info"><h3><?php echo $stats['pending']; ?></h3><p>Pending</p></div></div>
    <div class="stat-card fade-up"><div class="stat-icon" style="background:#e8f5e9;color:#198754"><i class="bi bi-currency-dollar"></i></div><div class="stat-info"><h3><?php echo formatMoney($stats['revenue']); ?></h3><p>Revenue</p></div></div>
</div>
<div class="row g-3">
<?php foreach($orders as $o): ?>
<div class="col-md-6 col-lg-4 fade-up">
<div class="card border-start border-4 border-<?php echo $statusColors[$o['status']] ?? 'secondary'; ?> shadow-sm" style="transition:var(--transition)">
<div class="card-body">
<div class="d-flex justify-content-between align-items-start mb-2">
<h5 class="mb-0"><?php echo htmlspecialchars($o['order_number']); ?></h5>
<span class="badge bg-<?php echo $statusColors[$o['status']] ?? 'secondary'; ?>"><?php echo ucfirst($o['status']); ?></span>
</div>
<div class="small text-muted mb-1"><i class="bi bi-person"></i> <?php echo htmlspecialchars($o['full_name'] ?? 'N/A'); ?></div>
<div class="small text-muted mb-1"><i class="bi bi-calendar"></i> <?php echo date('M d, Y H:i', strtotime($o['created_at'])); ?></div>
<div class="small text-muted mb-2"><i class="bi bi-credit-card"></i> <?php echo ucfirst($o['payment_method']); ?></div>
<div class="d-flex justify-content-between align-items-center">
<span class="fw-bold text-danger fs-5"><?php echo formatMoney($o['total_amount']); ?></span>
<a href="view.php?id=<?php echo $o['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> View</a>
</div>
</div></div>
</div>
<?php endforeach; ?>
</div>
<?php if (empty($orders)): ?>
<div class="alert alert-info text-center fade-up">No orders found for <?php echo $periodLabel; ?> <?php echo htmlspecialchars($filterValue); ?></div>
<?php endif; ?>
<?php echo renderPagination($page, $totalPages, $baseUrl); ?>
</main></div>
<?php include __DIR__ . '/../../includes/footer-admin.php'; ?>
