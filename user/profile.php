<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

$error = '';
$user_id = $_GET['id'] ?? 0;

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

try {
    // Get user details
    $query = "SELECT u.*, 
              (SELECT COUNT(*) FROM peribahasa WHERE contributor_id = u.id AND status = 'approved') as peribahasa_count,
              (SELECT COUNT(*) FROM comments WHERE user_id = u.id) as comment_count
              FROM users u 
              WHERE u.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        redirect('../index.php');
    }

    // Get user's approved peribahasa
    $query = "SELECT p.*, 
              (SELECT COUNT(*) FROM comments WHERE peribahasa_id = p.id) as comment_count
              FROM peribahasa p 
              WHERE p.contributor_id = ? AND p.status = 'approved'
              ORDER BY p.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $peribahasa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get user's recent comments
    $query = "SELECT c.*, p.title as peribahasa_title, p.id as peribahasa_id
              FROM comments c
              JOIN peribahasa p ON c.peribahasa_id = p.id
              WHERE c.user_id = ?
              ORDER BY c.created_at DESC
              LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $recent_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Ralat mendapatkan maklumat pengguna.';
}

// Set the base path for includes
$base_path = '..';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil <?php echo h($user['username']); ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container py-4">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <!-- User Profile -->
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bi bi-person-circle display-1"></i>
                        </div>
                        <h1 class="h3 card-title"><?php echo h($user['username']); ?></h1>
                        <p class="card-text">
                            <span class="badge bg-secondary"><?php echo h($user['role']); ?></span>
                        </p>
                        <div class="text-muted">
                            <p class="mb-1">
                                <i class="bi bi-calendar"></i> Ahli sejak: <?php echo format_date($user['created_at']); ?>
                            </p>
                            <p class="mb-1">
                                <i class="bi bi-book"></i> <?php echo $user['peribahasa_count']; ?> peribahasa
                            </p>
                            <p class="mb-1">
                                <i class="bi bi-chat"></i> <?php echo $user['comment_count']; ?> komen
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- User's Peribahasa -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Peribahasa yang Disumbangkan</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($peribahasa_list)): ?>
                            <p class="text-muted text-center py-4">Belum ada peribahasa yang disumbangkan.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($peribahasa_list as $item): ?>
                                    <a href="../public/peribahasa.php?id=<?php echo $item['id']; ?>" 
                                       class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1"><?php echo h($item['title']); ?></h5>
                                            <small class="text-muted">
                                                <i class="bi bi-chat"></i> <?php echo $item['comment_count']; ?>
                                            </small>
                                        </div>
                                        <p class="mb-1"><?php echo h($item['meaning']); ?></p>
                                        <small class="text-muted">
                                            <?php echo format_date($item['created_at']); ?>
                                        </small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Comments -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Komen Terkini</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_comments)): ?>
                            <p class="text-muted text-center py-4">Belum ada komen.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($recent_comments as $comment): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                <a href="../public/peribahasa.php?id=<?php echo $comment['peribahasa_id']; ?>" 
                                                   class="text-decoration-none">
                                                    <?php echo h($comment['peribahasa_title']); ?>
                                                </a>
                                            </h6>
                                            <small class="text-muted">
                                                <?php echo format_date($comment['created_at']); ?>
                                            </small>
                                        </div>
                                        <p class="mb-1"><?php echo h($comment['comment']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
