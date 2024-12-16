<?php
// Get the current page name for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="../"><?php echo SITE_NAME; ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" 
                       href="../admin/dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'moderate-submissions.php' ? 'active' : ''; ?>" 
                       href="../admin/moderate-submissions.php">
                        <i class="bi bi-shield-check"></i> Moderasi
                        <?php if (isset($pending_submissions) && $pending_submissions > 0): ?>
                            <span class="badge bg-danger"><?php echo $pending_submissions; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'manage-peribahasa.php' ? 'active' : ''; ?>" 
                       href="../admin/manage-peribahasa.php">
                        <i class="bi bi-book"></i> Urus Peribahasa
                    </a>
                </li>
                <?php if (is_admin()): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'manage-users.php' ? 'active' : ''; ?>" 
                       href="../admin/manage-users.php">
                        <i class="bi bi-people"></i> Urus Pengguna
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="../auth/logout.php">
                        <i class="bi bi-box-arrow-right"></i> Log Keluar
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
