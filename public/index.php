<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

// Get latest approved peribahasa
$db = new Database();
$conn = $db->getConnection();

$query = "SELECT p.*, u.username as contributor_name 
          FROM peribahasa p 
          LEFT JOIN users u ON p.contributor_id = u.id 
          WHERE p.status = 'approved' 
          ORDER BY p.created_at DESC 
          LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->execute();
$peribahasas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get peribahasa of the day
$today = date('Y-m-d');
$query = "SELECT p.*, dp.display_date 
          FROM daily_peribahasa dp 
          JOIN peribahasa p ON dp.peribahasa_id = p.id 
          WHERE dp.display_date = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$today]);
$dailyPeribahasa = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo url(''); ?>"><?php echo SITE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('public/search.php'); ?>">
                            <i class="bi bi-search"></i> Cari Peribahasa
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('contributor/submit-peribahasa.php'); ?>">
                                <i class="bi bi-plus-circle"></i> Hantar Peribahasa
                            </a>
                        </li>
                        <?php if ($_SESSION['user']['role'] === 'admin' || $_SESSION['user']['role'] === 'moderator'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo url('admin/dashboard.php'); ?>">
                                    <i class="bi bi-speedometer2"></i> Dashboard
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('auth/logout.php'); ?>">
                                <i class="bi bi-box-arrow-right"></i> Log Keluar
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('auth/login.php'); ?>">
                                <i class="bi bi-box-arrow-in-right"></i> Log Masuk
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('auth/register.php'); ?>">
                                <i class="bi bi-person-plus"></i> Daftar
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($dailyPeribahasa): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Peribahasa Hari Ini</h5>
            </div>
            <div class="card-body">
                <h4><?php echo htmlspecialchars($dailyPeribahasa['title']); ?></h4>
                <p class="card-text"><?php echo htmlspecialchars($dailyPeribahasa['meaning']); ?></p>
                <?php if ($dailyPeribahasa['example_usage']): ?>
                    <p class="card-text"><small class="text-muted">Contoh: <?php echo htmlspecialchars($dailyPeribahasa['example_usage']); ?></small></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <h2>Peribahasa Terkini</h2>
        <div class="row">
            <?php foreach ($peribahasas as $peribahasa): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($peribahasa['title']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($peribahasa['meaning']); ?></p>
                        <p class="card-text"><small class="text-muted">Disumbangkan oleh: <?php echo htmlspecialchars($peribahasa['contributor_name']); ?></small></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
