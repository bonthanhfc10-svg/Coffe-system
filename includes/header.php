<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo getPageTitle(); ?> - <?php echo htmlspecialchars(siteName()); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Khmer+OS&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/public/css/style.css?v=3" rel="stylesheet">
</head>
<body>
    <div class="toast-container" id="toastContainer"></div>
    <?php include __DIR__ . '/navbar.php'; ?>
    <main class="container mt-4 pt-5">
        <?php flashShow('success'); flashShow('error'); flashShow('info'); ?>
