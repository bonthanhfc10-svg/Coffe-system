<?php
// Expects: $filterType, $filterValue, $paymentFilter, $statusFilter, $categoryFilter,
//          $categories, $reportSummary, $totalRevenue, $totalOrders, $totalDiscount, $netRevenue
?>
<div class="print-only report-print-head">
    <div class="rph-brand"><?php echo htmlspecialchars(siteName()); ?></div>
    <div class="rph-title">Sales Report</div>
    <div class="rph-meta"><?php echo implode(' <span class="rph-sep">|</span> ', array_map('htmlspecialchars', $reportSummary)); ?></div>
    <div class="rph-meta">Generated: <?php echo date('M d, Y H:i'); ?> by <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Staff'); ?></div>
</div>

<div class="card shadow-sm mb-4 fade-up filter-card no-print">
<div class="card-body">
<form method="get" class="row g-3 align-items-end">
<div class="col-md-2"><button type="submit" name="filter_type" value="day" class="btn w-100 <?php echo $filterType==='day'?'btn-dark':'btn-outline-primary'; ?>" style="border-radius:12px">Day</button></div>
<div class="col-md-2"><button type="submit" name="filter_type" value="month" class="btn w-100 <?php echo $filterType==='month'?'btn-dark':'btn-outline-primary'; ?>" style="border-radius:12px">Month</button></div>
<div class="col-md-2"><button type="submit" name="filter_type" value="year" class="btn w-100 <?php echo $filterType==='year'?'btn-dark':'btn-outline-primary'; ?>" style="border-radius:12px">Year</button></div>
<div class="col-md-3">
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
<div class="col-md-3 d-flex gap-2">
<button type="submit" name="filter_type" value="<?php echo htmlspecialchars($filterType); ?>" class="btn btn-primary flex-fill" style="border-radius:12px"><i class="bi bi-funnel"></i> Apply</button>
<button type="button" class="btn btn-outline-dark flex-fill" style="border-radius:12px" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
</div>
<div class="col-md-4">
<select name="payment" class="form-select" style="border-radius:12px">
<option value="">Payment: All</option>
<?php foreach (['cash', 'aba', 'card', 'qr'] as $pm): ?>
<option value="<?php echo $pm; ?>" <?php echo $paymentFilter === $pm ? 'selected' : ''; ?>><?php echo ucfirst($pm); ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-4">
<select name="status" class="form-select" style="border-radius:12px">
<option value="">Status: All</option>
<?php foreach (['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'] as $st): ?>
<option value="<?php echo $st; ?>" <?php echo $statusFilter === $st ? 'selected' : ''; ?>><?php echo ucfirst($st); ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-4">
<select name="category" class="form-select" style="border-radius:12px">
<option value="">Category: All</option>
<?php foreach ($categories as $cat): ?>
<option value="<?php echo (int) $cat['id']; ?>" <?php echo $categoryFilter === (int) $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
<?php endforeach; ?>
</select>
</div>
</form>
<div class="text-muted small mt-2"><i class="bi bi-funnel"></i> <?php echo implode(' <span class="rph-dot">&middot;</span> ', array_map('htmlspecialchars', $reportSummary)); ?></div>
</div></div>

<div class="stats-grid mb-4">
<div class="stat-card fade-up"><div class="stat-icon" style="background:#e8f5e9;color:#198754"><i class="bi bi-currency-dollar"></i></div><div class="stat-info"><h3><?php echo formatMoney($totalRevenue); ?></h3><p>Total Revenue</p></div></div>
<div class="stat-card fade-up"><div class="stat-icon" style="background:#e3f2fd;color:#0d6efd"><i class="bi bi-bag"></i></div><div class="stat-info"><h3><?php echo $totalOrders; ?></h3><p>Total Orders</p></div></div>
<div class="stat-card fade-up"><div class="stat-icon" style="background:#fff3e0;color:#ff9800"><i class="bi bi-percent"></i></div><div class="stat-info"><h3><?php echo formatMoney($totalDiscount); ?></h3><p>Total Discount</p></div></div>
<div class="stat-card fade-up"><div class="stat-icon" style="background:#e8f5e9;color:#198754"><i class="bi bi-check-circle"></i></div><div class="stat-info"><h3><?php echo formatMoney($netRevenue); ?></h3><p>Net Revenue</p></div></div>
</div>
