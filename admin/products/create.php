<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole('admin');
$pdo = getPDO();
$error = '';
$success = '';
$cats = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$v_name = $_POST['name'] ?? '';
$v_desc = $_POST['description'] ?? '';
$v_cat = $_POST['category_id'] ?? '';
$v_price = $_POST['price'] ?? '';
$v_stock = $_POST['stock_quantity'] ?? '0';
$v_sku = $_POST['sku'] ?? '';
$v_sizes = ($_SERVER['REQUEST_METHOD'] === 'POST') ? (array) ($_POST['sizes'] ?? []) : PRODUCT_SIZE_OPTIONS;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../includes/csrf.php';
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token'); }
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $catId = $_POST['category_id'] ?? 0;
    $price = $_POST['price'] ?? 0;
    $stock = $_POST['stock_quantity'] ?? 0;
    $sku = trim($_POST['sku'] ?? '');
    $size = implode(',', array_values(array_intersect(PRODUCT_SIZE_OPTIONS, (array)($_POST['sizes'] ?? []))));
    $image = null;
    $imageError = null;
    $fileChosen = isset($_FILES['image'])
        && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
        && ($_FILES['image']['name'] ?? '') !== '';
    if ($fileChosen) {
        $image = uploadImage($_FILES['image'], 'products', $imageError);
    }
    if (!$name || !$catId || !$price) {
        $error = 'Name, category, and price are required';
    } elseif ($fileChosen && !$image) {
        $error = 'Image upload failed: ' . $imageError . ' The product was not saved.';
    } else {
        try {
            $pdo->prepare("INSERT INTO products (name, description, category_id, price, stock_quantity, image, sku, size) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")->execute([$name, $desc, $catId, $price, $stock, $image, $sku, $size]);
            $success = 'Product created!';
            if ($fileChosen) $success .= ' Image uploaded.';
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Create Product - Coffee POS</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"><link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet"></head><body>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:250px;">
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5">
<h2>Create Product</h2>
<?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<div class="card" style="max-width:600px;">
<div class="card-body">
<form method="POST" enctype="multipart/form-data">
<?php echo csrf_field(); ?>
<div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($v_name); ?>"></div>
<div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($v_desc); ?></textarea></div>
<div class="mb-3"><label class="form-label">Category</label><select name="category_id" class="form-select" required><option value="">-- Select --</option><?php foreach($cats as $c): ?><option value="<?php echo $c['id']; ?>" <?php echo (string)$v_cat === (string)$c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option><?php endforeach; ?></select></div>
<div class="mb-3"><label class="form-label">Price</label><input type="number" name="price" class="form-control" step="0.01" required value="<?php echo htmlspecialchars($v_price); ?>"></div>
<div class="mb-3"><label class="form-label">Stock</label><input type="number" name="stock_quantity" class="form-control" value="<?php echo htmlspecialchars($v_stock); ?>"></div>
<div class="mb-3"><label class="form-label">SKU</label><input type="text" name="sku" class="form-control" value="<?php echo htmlspecialchars($v_sku); ?>"></div>
<div class="mb-3"><label class="form-label">Sizes</label><div class="d-flex gap-3 flex-wrap"><?php foreach (PRODUCT_SIZE_OPTIONS as $s): ?><div class="form-check"><input class="form-check-input" type="checkbox" name="sizes[]" value="<?php echo htmlspecialchars($s); ?>" id="size<?php echo md5($s); ?>" <?php echo in_array($s, $v_sizes, true) ? 'checked' : ''; ?>><label class="form-check-label" for="size<?php echo md5($s); ?>"><?php echo htmlspecialchars($s); ?></label></div><?php endforeach; ?></div></div>
<div class="mb-3"><label class="form-label">Image</label><input type="file" name="image" class="form-control" accept="image/*"><div class="form-text">JPG, JPEG, JFIF, PNG, GIF, WEBP, AVIF or BMP - max 5 MB.</div></div>
<button type="submit" class="btn btn-primary">Create</button>
</form>
</div></div>
</main>
</div>
<?php include __DIR__ . '/../../includes/footer-admin.php'; ?>
