<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$user_id) {
    redirect('');
}

$db = new Database();
$conn = $db->getConnection();
$error = '';
$user = null;
$contributions = [];

try {
    // Get user details
    $stmt = $conn->prepare("
        SELECT id, username, email, role, created_at,
        (SELECT COUNT(*) FROM peribahasa WHERE contributor_id = users.id AND status = 'approved') as contribution_count
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        redirect('');
    }

    // Get user's contributions
    $stmt = $conn->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM comments WHERE peribahasa_id = p.id) as comment_count
        FROM peribahasa p 
        WHERE p.contributor_id = ? 
        AND p.status = 'approved'
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $contributions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Error fetching user data';
}

$page_title = "Profil " . ($user ? h($user['username']) : '');
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?= url('') ?>"><?= SITE_NAME ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('public/search.php') ?>">Cari</a>
                    </li>
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('public/submit.php') ?>">Hantar</a>
                        </li>
                        <?php if (is_admin_or_moderator()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= url('admin/dashboard.php') ?>">Dashboard</a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('auth/logout.php') ?>">Log Keluar</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('auth/login.php') ?>">Log Masuk</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('auth/register.php') ?>">Daftar</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>

        <?php if ($user): ?>
            <div class="row">
                <!-- User Profile Card -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <i class="bi bi-person-circle display-1"></i>
                            </div>
                            <h2 class="card-title h4 text-center mb-3"><?= h($user['username']) ?></h2>
                            
                            <div class="mb-3 text-center">
                                <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'moderator' ? 'success' : 'primary') ?>">
                                    <?= ucfirst(h($user['role'])) ?>
                                </span>
                            </div>

                            <div class="mb-3">
                                <small class="text-muted d-block">Email:</small>
                                <?= h($user['email']) ?>
                            </div>

                            <div class="mb-3">
                                <small class="text-muted d-block">Tarikh Daftar:</small>
                                <?= format_date($user['created_at']) ?>
                            </div>

                            <div class="mb-3">
                                <small class="text-muted d-block">Jumlah Sumbangan:</small>
                                <?= h($user['contribution_count']) ?> peribahasa
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User's Contributions -->
                <div class="col-md-8">
                    <h3 class="h4 mb-4">Sumbangan Peribahasa</h3>

                    <?php if (empty($contributions)): ?>
                        <div class="alert alert-info">
                            Pengguna ini belum membuat sebarang sumbangan.
                        </div>
                    <?php else: ?>
                        <div class="row row-cols-1 row-cols-md-2 g-4">
                            <?php foreach ($contributions as $peribahasa): ?>
                                <div class="col">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <a href="<?= url('public/view.php?id=' . $peribahasa['id']) ?>" 
                                                   class="text-decoration-none">
                                                    <?= h($peribahasa['title']) ?>
                                                </a>
                                            </h5>
                                            <p class="card-text text-muted small mb-2">
                                                <i class="bi bi-calendar"></i> <?= format_date($peribahasa['created_at']) ?>
                                                &bull;
                                                <i class="bi bi-chat"></i> <?= h($peribahasa['comment_count']) ?> komen
                                            </p>
                                            <p class="card-text"><?= h($peribahasa['meaning']) ?></p>
                                            <?php if ($peribahasa['example_usage']): ?>
                                                <p class="card-text"><small class="text-muted">Contoh: <?= h($peribahasa['example_usage']) ?></small></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
