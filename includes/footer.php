<?php
require_once __DIR__ . '/functions.php';

$footerYear  = date('Y');
$footerName  = siteName();
$footerDesc  = siteDescription();
$footerAddr  = setting('site_address', 'Coffee Street');
$footerMap   = trim(setting('site_map_url', ''));
if ($footerMap === '') {
    $footerMap = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($footerAddr);
}
$footerEmail = setting('site_email', 'info@4realcoffee.com');

$footerLinks = [
    ['label' => 'Home',       'icon' => 'bi-house',   'url' => BASE_URL . '/index.php'],
    ['label' => 'Menu',       'icon' => 'bi-cup-hot', 'url' => BASE_URL . '/customer/products.php'],
    ['label' => 'Categories', 'icon' => 'bi-tags',    'url' => BASE_URL . '/customer/categories.php'],
];

$footerRole = $_SESSION['role'] ?? '';
$showSiteFooter = !in_array($footerRole, ['admin', 'staff']);
?>
<?php if ($showSiteFooter): ?>
<footer class="footer mt-5" id="siteFooter">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6 col-12">
                <h5 class="footer-brand"><i class="bi bi-cup-fill"></i> <?php echo $footerName; ?></h5>
                <?php if ($footerDesc !== ''): ?>
                <p class="footer-text"><?php echo htmlspecialchars($footerDesc); ?></p>
                <?php endif; ?>
            </div>
            <div class="col-lg-4 col-md-6 col-12">
                <h6 class="footer-heading">Quick Links</h6>
                <ul class="footer-links">
                    <?php foreach ($footerLinks as $link): ?>
                    <li><a href="<?php echo $link['url']; ?>"><i class="bi <?php echo $link['icon']; ?>"></i> <?php echo $link['label']; ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-lg-4 col-md-6 col-12">
                <h6 class="footer-heading">Contact</h6>
                <div class="footer-contact">
                    <p><i class="bi bi-geo-alt"></i><a href="<?php echo htmlspecialchars($footerMap); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($footerAddr); ?></a></p>
                    <p><i class="bi bi-envelope"></i><a href="mailto:<?php echo $footerEmail; ?>"><?php echo $footerEmail; ?></a></p>
                </div>
            </div>
        </div>
        <hr class="footer-divider">
        <div class="footer-bottom">
            <p>&copy; <?php echo $footerYear; ?> <?php echo $footerName; ?>. sok bunthanh</p>
            <button type="button" class="footer-top" onclick="window.scrollTo({top:0,behavior:'smooth'})"><i class="bi bi-arrow-up"></i> Back to top</button>
        </div>
    </div>
</footer>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>/public/js/app.js?v=3"></script>
</body>
</html>
