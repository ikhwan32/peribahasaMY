<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

$error = '';
$success = '';
$peribahasa_id = $_GET['id'] ?? 0;

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Get peribahasa details
try {
    $query = "SELECT p.*, 
                     u1.username as contributor_name,
                     u2.username as moderator_name,
                     (SELECT COUNT(*) FROM comments WHERE peribahasa_id = p.id) as comment_count
              FROM peribahasa p 
              LEFT JOIN users u1 ON p.contributor_id = u1.id
              LEFT JOIN users u2 ON p.approved_by = u2.id
              WHERE p.id = ? AND p.status = 'approved'";
    $stmt = $conn->prepare($query);
    $stmt->execute([$peribahasa_id]);
    $peribahasa = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$peribahasa) {
        redirect('../index.php');
    }

    // Get comments
    $query = "SELECT c.*, u.username, u.role
              FROM comments c
              JOIN users u ON c.user_id = u.id
              WHERE c.peribahasa_id = ?
              ORDER BY c.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute([$peribahasa_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Ralat mendapatkan maklumat peribahasa.';
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!is_logged_in()) {
        redirect('../auth/login.php');
    }

    try {
        switch ($_POST['action']) {
            case 'add_comment':
                if (empty($_POST['comment'])) {
                    throw new Exception('Sila masukkan komen anda.');
                }

                $stmt = $conn->prepare("INSERT INTO comments (peribahasa_id, user_id, comment) VALUES (?, ?, ?)");
                $stmt->execute([
                    $peribahasa_id,
                    get_current_user_id(),
                    $_POST['comment']
                ]);
                $success = 'Komen berjaya ditambah.';

                // Refresh comments
                $stmt = $conn->prepare($query);
                $stmt->execute([$peribahasa_id]);
                $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'delete_comment':
                if (!isset($_POST['comment_id'])) {
                    throw new Exception('ID komen tidak sah.');
                }

                // Only allow comment owner or admin/moderator to delete
                $stmt = $conn->prepare("SELECT user_id FROM comments WHERE id = ?");
                $stmt->execute([$_POST['comment_id']]);
                $comment = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($comment && (is_admin_or_moderator() || $comment['user_id'] === get_current_user_id())) {
                    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
                    $stmt->execute([$_POST['comment_id']]);
                    $success = 'Komen berjaya dipadam.';

                    // Refresh comments
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$peribahasa_id]);
                    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    throw new Exception('Anda tidak mempunyai kebenaran untuk memadam komen ini.');
                }
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Set the base path for includes
$base_path = '..';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($peribahasa['title']); ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/style.css">
</head>
<body>
    <?php include $base_path . '/includes/header.php'; ?>

    <div class="container py-4">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo h($success); ?></div>
        <?php endif; ?>

        <!-- Peribahasa Details -->
        <div class="card mb-4">
            <div class="card-body">
                <h1 class="card-title h3"><?php echo h($peribahasa['title']); ?></h1>
                <p class="card-text"><?php echo h($peribahasa['meaning']); ?></p>
                <?php if ($peribahasa['example_usage']): ?>
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Contoh Penggunaan:</h5>
                            <p class="card-text"><?php echo h($peribahasa['example_usage']); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="text-muted small">
                    <p class="mb-1">
                        <i class="bi bi-person"></i> Disumbang oleh: 
                        <a href="<?php echo $base_path; ?>/user/profile.php?id=<?php echo $peribahasa['contributor_id']; ?>" 
                           class="text-decoration-none">
                            <?php echo h($peribahasa['contributor_name']); ?>
                        </a>
                    </p>
                    <p class="mb-1">
                        <i class="bi bi-calendar"></i> Tarikh: <?php echo format_date($peribahasa['created_at']); ?>
                    </p>
                    <?php if ($peribahasa['moderator_name']): ?>
                        <p class="mb-1">
                            <i class="bi bi-shield-check"></i> Disahkan oleh: 
                            <a href="<?php echo $base_path; ?>/user/profile.php?id=<?php echo $peribahasa['approved_by']; ?>" 
                               class="text-decoration-none">
                                <?php echo h($peribahasa['moderator_name']); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Comments Section -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Komen (<?php echo count($comments); ?>)</h2>
            </div>
            <div class="card-body">
                <?php if (is_logged_in()): ?>
                    <!-- Comment Form -->
                    <form method="post" class="mb-4">
                        <input type="hidden" name="action" value="add_comment">
                        <div class="mb-3">
                            <label for="comment" class="form-label">Tambah Komen</label>
                            <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Hantar</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info">
                        <a href="<?php echo $base_path; ?>/auth/login.php">Log masuk</a> untuk menambah komen.
                    </div>
                <?php endif; ?>

                <!-- Comments List -->
                <?php if (empty($comments)): ?>
                    <p class="text-muted text-center py-4">Tiada komen lagi. Jadilah yang pertama untuk memberi komen!</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($comments as $comment): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="d-flex align-items-center mb-1">
                                            <strong class="me-2">
                                                <a href="<?php echo $base_path; ?>/user/profile.php?id=<?php echo $comment['user_id']; ?>" 
                                                   class="text-decoration-none">
                                                    <?php echo h($comment['username']); ?>
                                                </a>
                                            </strong>
                                            <span class="badge bg-secondary"><?php echo h($comment['role']); ?></span>
                                        </div>
                                        <p class="mb-1"><?php echo h($comment['comment']); ?></p>
                                        <small class="text-muted">
                                            <?php echo format_date($comment['created_at']); ?>
                                        </small>
                                    </div>
                                    <?php if (is_logged_in() && (is_admin_or_moderator() || $comment['user_id'] === get_current_user_id())): ?>
                                        <form method="post" class="ms-2">
                                            <input type="hidden" name="action" value="delete_comment">
                                            <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm('Adakah anda pasti mahu memadam komen ini?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include $base_path . '/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
