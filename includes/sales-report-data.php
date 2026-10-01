<?php
// Shared sales-report filter + data builder.
// Expects $pdo. Reads $_GET: filter_type, filter_value, payment, status, category.
// Produces: $filterType, $filterValue, $paymentFilter, $statusFilter, $categoryFilter,
//           $categories, $reportSummary, $labelHeading, $reportData,
//           $totalRevenue, $totalDiscount, $totalOrders, $netRevenue

[$filterType, $filterValue] = periodFilter();

$labelHeading = ['day' => 'Hour', 'month' => 'Date', 'year' => 'Month'][$filterType];

$allowedPayments = ['cash', 'aba', 'card', 'qr'];
$paymentFilter = $_GET['payment'] ?? '';
if (!in_array($paymentFilter, $allowedPayments, true)) $paymentFilter = '';

$allowedStatuses = ['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'];
$statusFilter = $_GET['status'] ?? '';
if (!in_array($statusFilter, $allowedStatuses, true)) $statusFilter = '';

$categoryFilter = (int) ($_GET['category'] ?? 0);
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$categoryName = '';
if ($categoryFilter) {
    foreach ($categories as $cat) {
        if ((int) $cat['id'] === $categoryFilter) $categoryName = $cat['name'];
    }
    if ($categoryName === '') $categoryFilter = 0;
}

[$periodCondition, $periodParams] = periodWhere($filterType, $filterValue, 'o.created_at');

$conds = [$periodCondition];
$params = $periodParams;
if ($paymentFilter !== '') {
    $conds[] = 'o.payment_method = ?';
    $params[] = $paymentFilter;
}
if ($statusFilter !== '') {
    $conds[] = 'o.status = ?';
    $params[] = $statusFilter;
}
if ($categoryFilter) {
    $conds[] = 'EXISTS (SELECT 1 FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = o.id AND p.category_id = ?)';
    $params[] = $categoryFilter;
}
$where = 'WHERE ' . implode(' AND ', $conds);

switch ($filterType) {
    case 'day':
        $labelSql = "CONCAT(LPAD(HOUR(o.created_at), 2, '0'), ':00')";
        $groupBy = 'GROUP BY HOUR(o.created_at)';
        break;
    case 'year':
        $labelSql = "DATE_FORMAT(o.created_at, '%Y-%m')";
        $groupBy = "GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')";
        break;
    default:
        $labelSql = 'DATE(o.created_at)';
        $groupBy = 'GROUP BY DATE(o.created_at)';
        break;
}

$stmt = $pdo->prepare("SELECT $labelSql AS date, COUNT(*) AS orders, SUM(o.total_amount) AS revenue, SUM(o.discount) AS discount FROM orders o $where $groupBy ORDER BY date ASC");
$stmt->execute($params);
$reportData = $stmt->fetchAll();

$totalStmt = $pdo->prepare("SELECT COALESCE(SUM(o.total_amount),0) AS revenue, COALESCE(SUM(o.discount),0) AS discount, COUNT(*) AS orders FROM orders o $where");
$totalStmt->execute($params);
$totals = $totalStmt->fetch();
$totalRevenue = (float) $totals['revenue'];
$totalDiscount = (float) $totals['discount'];
$totalOrders = (int) $totals['orders'];
$netRevenue = $totalRevenue - $totalDiscount;

$reportSummary = [
    ['day' => 'Day', 'month' => 'Month', 'year' => 'Year'][$filterType] . ': ' . $filterValue,
    $paymentFilter !== '' ? 'Payment: ' . ucfirst($paymentFilter) : 'Payment: All',
    $statusFilter !== '' ? 'Status: ' . ucfirst($statusFilter) : 'Status: All',
    $categoryFilter ? 'Category: ' . $categoryName : 'Category: All',
    'Orders: ' . $totalOrders,
];
