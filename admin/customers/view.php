<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole('admin');
$pdo = getPDO();
$id = $_GET['id'] ?? 0;
$c = $pdo->prepare("SELECT c.*, u.full_name, u.email FROM customers c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
$c->execute([$id]);
$customer = $c->fetch();
if (!$customer) redirect(BASE_URL . '/admin/customers/index.php');
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Customer - Coffee POS</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"><link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet"></head><body>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:250px;">
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5">
<h2><?php echo htmlspecialchars($customer['full_name']); ?></h2>
<div class="card" style="max-width:500px;"><div class="card-body">
<p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
<p><strong>Phone:</strong> <?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></p>
<p><strong>Orders:</strong> <?php echo $customer['total_orders'] ?? 0; ?></p>
<p><strong>Total Spent:</strong> <?php echo formatMoney($customer['total_spent'] ?? 0); ?></p>
</div></div>
</main>
</div>
<?php include __DIR__ . '/../../includes/footer-admin.php'; ?>
