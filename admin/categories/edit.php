<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole('admin');
$pdo = getPDO();
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$id]);
$cat = $stmt->fetch();
if (!$cat) redirect(BASE_URL . '/admin/categories/index.php');
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../includes/csrf.php';
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token'); }
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $image = $cat['image'];
    $imageError = null;
    $fileChosen = isset($_FILES['image'])
        && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
        && ($_FILES['image']['name'] ?? '') !== '';
    if ($fileChosen) {
        $image = uploadImage($_FILES['image'], 'categories', $imageError);
    }
    $cat['name'] = $name;
    $cat['description'] = $desc;
    if (!$name) {
        $error = 'Name is required';
    } elseif ($fileChosen && !$image) {
        $error = 'Image upload failed: ' . $imageError . ' No changes were saved.';
    } else {
        try {
            $pdo->prepare("UPDATE categories SET name = ?, description = ?, image = ? WHERE id = ?")->execute([$name, $desc, $image, $id]);
            $cat['image'] = $image;
            $success = 'Category updated!';
            if ($fileChosen) $success .= ' Image uploaded.';
        } catch (PDOException $e) { $error = 'Error: ' . $e->getMessage(); }
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Edit Category - Coffee POS</title><link href="https://fonts.googleapis.com/css2?family=Khmer+OS&display=swap" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"><link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet"></head><body>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:250px;">
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5">
<h2>Edit Category</h2>
<?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<div class="card" style="max-width:500px;"><div class="card-body"><form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
<div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($cat['name']); ?>" required></div>
<div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($cat['description']); ?></textarea></div>
<div class="mb-3">
<label class="form-label">Current Image</label><br>
<?php if ($cat['image']): ?>
<img src="<?php echo BASE_URL; ?>/public/uploads/categories/<?php echo htmlspecialchars($cat['image']); ?>" style="max-height:100px;border-radius:8px;">
<?php else: ?>
<span class="text-muted">No image</span>
<?php endif; ?>
</div>
<div class="mb-3"><label class="form-label">New Image</label><input type="file" name="image" class="form-control" accept="image/*"><div class="form-text">JPG, JPEG, JFIF, PNG, GIF, WEBP, AVIF or BMP - max 5 MB. Leave empty to keep the current image.</div></div>
<button type="submit" class="btn btn-primary">Update</button></form></div></div>
</main>
</div>
<?php include __DIR__ . '/../../includes/footer-admin.php'; ?>