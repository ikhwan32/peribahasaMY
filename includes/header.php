<?php
require_once __DIR__ . '/functions.php';

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<header class="bg-primary text-white mb-4">
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo url('index.php'); ?>"><?php echo SITE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" 
                           href="<?php echo url('index.php'); ?>">Utama</a>
                    </li>
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'submit.php' ? 'active' : ''; ?>" 
                               href="<?php echo url('submit.php'); ?>">Hantar Peribahasa</a>
                        </li>
                    <?php endif; ?>
                    <?php if (is_admin_or_moderator()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('admin/dashboard.php'); ?>">Admin</a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Search Form -->
                <form class="d-flex me-3" action="<?php echo url('search.php'); ?>" method="GET">
                    <input class="form-control me-2" type="search" name="q" placeholder="Cari peribahasa..." 
                           value="<?php echo isset($_GET['q']) ? h($_GET['q']) : ''; ?>">
                    <button class="btn btn-light" type="submit">Cari</button>
                </form>

                <!-- User Menu -->
                <ul class="navbar-nav">
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
                               data-bs-toggle="dropdown">
                                <?php echo h($_SESSION['user']['username']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="<?php echo url('auth/logout.php'); ?>">Log Keluar</a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('auth/login.php'); ?>">Log Masuk</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('auth/register.php'); ?>">Daftar</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>
