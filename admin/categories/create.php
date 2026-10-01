<?php
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireRole('admin');
$pdo = getPDO();
$error = '';
$success = '';
$v_name = $_POST['name'] ?? '';
$v_desc = $_POST['description'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../includes/csrf.php';
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token'); }
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $image = '';
    $imageError = null;
    $fileChosen = isset($_FILES['image'])
        && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
        && ($_FILES['image']['name'] ?? '') !== '';
    if ($fileChosen) {
        $image = uploadImage($_FILES['image'], 'categories', $imageError);
    }
    if (!$name) {
        $error = 'Name is required';
    } elseif ($fileChosen && !$image) {
        $error = 'Image upload failed: ' . $imageError . ' The category was not saved.';
    } else {
        try {
            if ($image) {
                $pdo->prepare("INSERT INTO categories (name, description, image) VALUES (?, ?, ?)")->execute([$name, $desc, $image]);
            } else {
                $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)")->execute([$name, $desc]);
            }
            $success = 'Category created!';
            if ($fileChosen) $success .= ' Image uploaded.';
        } catch (PDOException $e) { $error = 'Error: ' . $e->getMessage(); }
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Create Category - Coffee POS</title><link href="https://fonts.googleapis.com/css2?family=Khmer+OS&display=swap" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"><link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet"></head><body>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>
<div class="main-content" style="margin-left:250px;">
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>
<main class="container mt-4 pt-5">
<h2>Create Category</h2>
<?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<div class="card" style="max-width:500px;"><div class="card-body"><form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
<div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($v_name); ?>"></div>
<div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($v_desc); ?></textarea></div>
<div class="mb-3"><label class="form-label">Image</label><input type="file" name="image" class="form-control" accept="image/*"><div class="form-text">JPG, JPEG, JFIF, PNG, GIF, WEBP, AVIF or BMP - max 5 MB.</div></div>
<button type="submit" class="btn btn-primary">Create</button></form></div></div>
</main>
</div>
<?php include __DIR__ . '/../../includes/footer-admin.php'; ?>