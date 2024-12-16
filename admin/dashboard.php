<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Ensure user is admin or moderator
require_login();
if (!is_admin_or_moderator()) {
    header('Location: /');
    exit;
}

// Get statistics
$db = new Database();
$conn = $db->getConnection();

// Total peribahasa
$query = "SELECT COUNT(*) as total FROM peribahasa WHERE status = 'approved'";
$stmt = $conn->query($query);
$total_peribahasa = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pending submissions
$query = "SELECT COUNT(*) as total FROM peribahasa WHERE status = 'pending'";
$stmt = $conn->query($query);
$pending_submissions = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total users
$query = "SELECT COUNT(*) as total FROM users";
$stmt = $conn->query($query);
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Recent activities
$query = "SELECT p.*, u.username as contributor_name, m.username as moderator_name 
          FROM peribahasa p 
          JOIN users u ON p.contributor_id = u.id 
          LEFT JOIN users m ON p.approved_by = m.id 
          WHERE p.status != 'pending'
          ORDER BY p.updated_at DESC 
          LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->execute();
$recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get top contributors
$query = "SELECT u.username, COUNT(*) as contribution_count 
          FROM peribahasa p 
          JOIN users u ON p.contributor_id = u.id 
          WHERE p.status = 'approved' 
          GROUP BY p.contributor_id 
          ORDER BY contribution_count DESC 
          LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->execute();
$top_contributors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php require_once '../includes/menu.php'; ?>

    <div class="container py-4">
        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Jumlah Peribahasa</h6>
                                <h2 class="display-6 mb-0"><?php echo number_format($total_peribahasa); ?></h2>
                            </div>
                            <i class="bi bi-book display-6"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Menunggu Semakan</h6>
                                <h2 class="display-6 mb-0"><?php echo number_format($pending_submissions); ?></h2>
                            </div>
                            <i class="bi bi-clock-history display-6"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Jumlah Pengguna</h6>
                                <h2 class="display-6 mb-0"><?php echo number_format($total_users); ?></h2>
                            </div>
                            <i class="bi bi-people display-6"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Activities -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Aktiviti Terkini</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Peribahasa</th>
                                        <th>Penyumbang</th>
                                        <th>Status</th>
                                        <th>Moderator</th>
                                        <th>Tarikh</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_activities as $activity): ?>
                                        <tr>
                                            <td><?php echo h($activity['title']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo h($activity['contributor_name']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($activity['status'] === 'approved'): ?>
                                                    <span class="badge bg-success">Diterima</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Ditolak</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo h($activity['moderator_name']); ?></td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo format_date($activity['updated_at']); ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Contributors -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Penyumbang Terbaik</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php foreach ($top_contributors as $contributor): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-person-circle me-2"></i>
                                        <?php echo h($contributor['username']); ?>
                                    </div>
                                    <span class="badge bg-primary rounded-pill">
                                        <?php echo $contributor['contribution_count']; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Pautan Pantas</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <a href="../admin/moderate-submissions.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-shield-check me-2"></i>
                                Moderasi Peribahasa
                            </a>
                            <a href="../" class="list-group-item list-group-item-action">
                                <i class="bi bi-house me-2"></i>
                                Laman Utama
                            </a>
                            <a href="../public/search.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-search me-2"></i>
                                Carian
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
