<?php if (in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)): ?>
<script>try{if(!window.matchMedia('(max-width:992px)').matches&&localStorage.getItem('panelCollapsed')==='1'){document.documentElement.classList.add('panel-collapsed');}}catch(e){}</script>
<?php endif; ?>
<nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background:linear-gradient(135deg,#6f4e37,#a0785a);box-shadow:0 4px 20px rgba(111,78,55,.35)">
    <div class="container">
        <div class="navbar-start d-flex align-items-center">
            <?php if (in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)): ?>
            <button class="panel-toggle" id="panelToggle" type="button" aria-label="Toggle panel" aria-controls="sidePanel" aria-expanded="true" title="Open / close panel">
                <i class="bi bi-list"></i>
            </button>
            <?php endif; ?>
            <a class="navbar-brand fw-bold fs-4 text-white" href="<?php echo BASE_URL; ?>/index.php"><i class="bi bi-cup-fill"></i> <?php echo htmlspecialchars(siteName()); ?></a>
        </div>
        <button class="navbar-toggler sidebar-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if (isLoggedIn()): ?>
                    <?php if ($_SESSION['role'] === 'customer'): ?>
                        <li class="nav-item"><a class="nav-link fw-semibold text-white" href="<?php echo BASE_URL; ?>/customer/products.php">Products</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white fw-semibold" href="#" id="userMenu" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php
                            $profileUrl = BASE_URL . '/staff/profile.php';
                            if ($_SESSION['role'] === 'admin') $profileUrl = BASE_URL . '/admin/settings.php';
                            elseif ($_SESSION['role'] === 'customer') $profileUrl = BASE_URL . '/customer/profile.php';
                            ?>
                            <li><a class="dropdown-item" href="<?php echo $profileUrl; ?>"><i class="bi bi-person"></i> Profile</a></li>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link fw-semibold text-white btn btn-outline-light btn-sm rounded-pill px-3 ms-2" href="<?php echo BASE_URL; ?>/auth/login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>