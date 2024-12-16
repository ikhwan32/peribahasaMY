<?php
// Debug: Check session status
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Debug: Check session data
error_log('Session data: ' . print_r($_SESSION, true));

$db = new Database();
$conn = $db->getConnection();

// Get peribahasa of the day
$today = date('Y-m-d');
$query = "SELECT p.*, u.username as contributor_name 
          FROM daily_peribahasa dp 
          JOIN peribahasa p ON dp.peribahasa_id = p.id 
          LEFT JOIN users u ON p.contributor_id = u.id
          WHERE dp.display_date = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$today]);
$dailyPeribahasa = $stmt->fetch(PDO::FETCH_ASSOC);

// If no peribahasa of the day, select a random one and insert it
if (!$dailyPeribahasa) {
    $query = "SELECT p.*, u.username as contributor_name 
              FROM peribahasa p 
              LEFT JOIN users u ON p.contributor_id = u.id
              WHERE p.status = 'approved' 
              ORDER BY " . get_random_order_sql() . " 
              LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $dailyPeribahasa = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($dailyPeribahasa) {
        $query = "INSERT INTO daily_peribahasa (peribahasa_id, display_date) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$dailyPeribahasa['id'], $today]);
    }
}

// Get latest approved peribahasa
$query = "SELECT p.*, u.username as contributor_name, COUNT(c.id) as comment_count 
          FROM peribahasa p 
          LEFT JOIN users u ON p.contributor_id = u.id 
          LEFT JOIN comments c ON p.id = c.peribahasa_id
          WHERE p.status = 'approved' 
          GROUP BY p.id 
          ORDER BY p.created_at DESC 
          LIMIT 6";
$stmt = $conn->prepare($query);
$stmt->execute();
$latestPeribahasas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total counts
$query = "SELECT 
            (SELECT COUNT(*) FROM peribahasa WHERE status = 'approved') as total_peribahasa,
            (SELECT COUNT(DISTINCT contributor_id) FROM peribahasa WHERE status = 'approved' AND contributor_id IS NOT NULL) as total_contributors";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Koleksi Peribahasa Melayu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href=""><?php echo SITE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="public/search.php">
                            <i class="bi bi-search"></i> Cari Peribahasa
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="contributor/submit-peribahasa.php">
                                <i class="bi bi-plus-circle"></i> Hantar Peribahasa
                            </a>
                        </li>
                        <?php if (is_admin_or_moderator()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin/dashboard.php">
                                    <i class="bi bi-speedometer2"></i> Dashboard
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="auth/logout.php">
                                <i class="bi bi-box-arrow-right"></i> Log Keluar
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="auth/login.php">
                                <i class="bi bi-box-arrow-in-right"></i> Log Masuk
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="auth/register.php">
                                <i class="bi bi-person-plus"></i> Daftar
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="display-4 mb-3">Khazanah Peribahasa Melayu</h1>
                    <p class="lead mb-4">Jelajahi kebijaksanaan dan keindahan bahasa Melayu melalui koleksi peribahasa yang lengkap.</p>
                    <div class="d-flex gap-2">
                        <a href="public/search.php" class="btn btn-primary">
                            <i class="bi bi-search"></i> Cari Peribahasa
                        </a>
                        <?php if (!is_logged_in()): ?>
                        <a href="auth/register.php" class="btn btn-outline-primary">
                            <i class="bi bi-person-plus"></i> Sertai Kami
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6 text-center">
                    <img src="assets/images/hero-illustration.svg" alt="Ilustrasi Peribahasa" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="py-4 bg-primary text-white">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-6">
                    <h3 class="display-6"><?php echo number_format($stats['total_peribahasa']); ?></h3>
                    <p>Peribahasa</p>
                </div>
                <div class="col-md-6">
                    <h3 class="display-6"><?php echo number_format($stats['total_contributors']); ?></h3>
                    <p>Penyumbang</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5">
        <!-- Peribahasa of the Day -->
        <?php if ($dailyPeribahasa): ?>
        <div class="card mb-5 peribahasa-of-day">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="card-title text-primary mb-0">Peribahasa Hari Ini</h5>
                        <h3 class="mt-2"><?php echo h($dailyPeribahasa['title']); ?></h3>
                        <p class="card-text"><?php echo h($dailyPeribahasa['meaning']); ?></p>
                        <?php if ($dailyPeribahasa['example_usage']): ?>
                            <p class="card-text"><small class="text-muted">Contoh: <?php echo h($dailyPeribahasa['example_usage']); ?></small></p>
                        <?php endif; ?>
                        <p class="card-text">
                            <small class="text-muted">
                                Disumbangkan oleh: <?php echo h($dailyPeribahasa['contributor_name']); ?>
                            </small>
                        </p>
                    </div>
                    <div class="col-md-4 text-center">
                        <i class="bi bi-quote display-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Latest Peribahasa -->
        <h2 class="mb-4">Peribahasa Terkini</h2>
        <div class="row">
            <?php foreach ($latestPeribahasas as $peribahasa): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <a href="public/peribahasa.php?id=<?php echo $peribahasa['id']; ?>" class="text-decoration-none">
                                <?php echo h($peribahasa['title']); ?>
                            </a>
                        </h5>
                        <p class="card-text"><?php echo h($peribahasa['meaning']); ?></p>
                        <?php if ($peribahasa['example_usage']): ?>
                            <p class="card-text"><small class="text-muted"><?php echo h($peribahasa['example_usage']); ?></small></p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent">
                        <small class="text-muted">
                            <i class="bi bi-person"></i> 
                            <a href="user/profile.php?id=<?php echo $peribahasa['contributor_id']; ?>" 
                               class="text-decoration-none text-muted">
                                <?php echo h($peribahasa['contributor_name']); ?>
                            </a> |
                            <i class="bi bi-calendar"></i> <?php echo format_date($peribahasa['created_at']); ?> |
                            <i class="bi bi-chat"></i> <?php echo $peribahasa['comment_count']; ?> komen
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><?php echo SITE_NAME; ?></h5>
                    <p class="text-muted">Melestarikan warisan bahasa dan budaya Melayu melalui peribahasa.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item">
                            <a href="public/search.php" class="text-muted text-decoration-none">Cari Peribahasa</a>
                        </li>
                        <li class="list-inline-item">
                            <a href="contributor/submit-peribahasa.php" class="text-muted text-decoration-none">Hantar Peribahasa</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
