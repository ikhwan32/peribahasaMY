<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Ensure user is admin or moderator
require_login();
if (!is_admin_or_moderator()) {
    redirect('');
}

$db = new Database();
$conn = $db->getConnection();

$error = '';
$success = '';

// Get pending submissions count for menu badge
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM peribahasa WHERE status = 'pending'");
    $pending_submissions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (PDOException $e) {
    error_log($e->getMessage());
    $pending_submissions = 0;
}

// Handle submission moderation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_id = $_POST['submission_id'] ?? '';
    $action = $_POST['action'] ?? '';
    
    if (!empty($submission_id) && !empty($action)) {
        if ($action === 'approve') {
            $query = "UPDATE peribahasa SET status = 'approved', approved_by = ?, updated_at = NOW() WHERE id = ?";
            $status = 'approved';
        } else {
            $query = "UPDATE peribahasa SET status = 'rejected', approved_by = ?, updated_at = NOW() WHERE id = ?";
            $status = 'rejected';
        }
        
        try {
            $stmt = $conn->prepare($query);
            $stmt->execute([get_current_user_id(), $submission_id]);
            
            if ($stmt->rowCount() > 0) {
                $success = "Peribahasa telah " . ($status === 'approved' ? 'diterima' : 'ditolak');
                // Update pending count after moderation
                $stmt = $conn->query("SELECT COUNT(*) as count FROM peribahasa WHERE status = 'pending'");
                $pending_submissions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            } else {
                $error = 'Tiada perubahan dibuat. Sila pastikan ID peribahasa adalah sah.';
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Ralat semasa mengemaskini status. Sila cuba lagi.';
        }
    } else {
        $error = 'Data tidak lengkap. Sila cuba lagi.';
    }
}

// Get pending submissions
try {
    $query = "SELECT p.*, u.username as contributor_name 
              FROM peribahasa p 
              LEFT JOIN users u ON p.contributor_id = u.id 
              WHERE p.status = 'pending' 
              ORDER BY p.created_at DESC";
    $stmt = $conn->query($query);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Ralat mendapatkan senarai peribahasa.';
    $submissions = [];
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderasi Peribahasa - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php require_once '../includes/menu.php'; ?>

    <div class="container py-4">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Moderasi Peribahasa</h4>
                <span class="badge bg-primary"><?php echo $pending_submissions; ?> menunggu</span>
            </div>
            <div class="card-body">
                <?php if (empty($submissions)): ?>
                    <p class="text-muted text-center py-5">
                        <i class="bi bi-check-circle display-4 d-block mb-3"></i>
                        Tiada peribahasa yang menunggu semakan.
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Peribahasa</th>
                                    <th>Maksud</th>
                                    <th>Penyumbang</th>
                                    <th>Tarikh</th>
                                    <th>Tindakan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($submissions as $submission): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo h($submission['title']); ?></strong>
                                            <?php if ($submission['example_usage']): ?>
                                                <br>
                                                <small class="text-muted">
                                                    Contoh: <?php echo h($submission['example_usage']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo h($submission['meaning']); ?></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo h($submission['contributor_name']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo format_date($submission['created_at']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-sm btn-success approve-btn" 
                                                    data-id="<?php echo $submission['id']; ?>">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger reject-btn" 
                                                    data-id="<?php echo $submission['id']; ?>">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Moderation Modal -->
    <div class="modal fade" id="moderateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="submission_id" id="submissionId">
                    <input type="hidden" name="action" id="action">
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Moderasi Peribahasa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="action-message"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Hantar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update modal content based on action
        function updateModal(action, id) {
            document.getElementById('submissionId').value = id;
            document.getElementById('action').value = action;
            
            const message = action === 'approve' ? 
                'Anda pasti untuk menerima peribahasa ini?' : 
                'Anda pasti untuk menolak peribahasa ini?';
            document.querySelector('.action-message').textContent = message;
            
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.className = action === 'approve' ? 
                'btn btn-success' : 
                'btn btn-danger';
            submitBtn.textContent = action === 'approve' ? 'Terima' : 'Tolak';
        }
        
        // Add click handlers to approve/reject buttons
        document.querySelectorAll('.approve-btn, .reject-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const action = this.classList.contains('approve-btn') ? 'approve' : 'reject';
                updateModal(action, id);
                const modal = new bootstrap.Modal(document.getElementById('moderateModal'));
                modal.show();
            });
        });
    </script>
</body>
</html>
