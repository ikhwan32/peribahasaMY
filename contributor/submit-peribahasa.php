<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Ensure user is logged in
require_login();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $meaning = trim($_POST['meaning'] ?? '');
    $example = trim($_POST['example'] ?? '');
    
    // Validation
    if (empty($title) || empty($meaning)) {
        $error = 'Sila isi peribahasa dan maksudnya.';
    } else {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Check if peribahasa already exists
        $query = "SELECT id FROM peribahasa WHERE title = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$title]);
        
        if ($stmt->fetch()) {
            $error = 'Peribahasa ini telah wujud dalam sistem.';
        } else {
            // Insert new peribahasa
            $query = "INSERT INTO peribahasa (title, meaning, example_usage, contributor_id, status, created_at) 
                     VALUES (?, ?, ?, ?, 'pending', NOW())";
            $stmt = $conn->prepare($query);
            
            try {
                $stmt->execute([$title, $meaning, $example ?: null, get_current_user_id()]);
                $success = 'Peribahasa anda telah dihantar untuk semakan. Terima kasih!';
                
                // Clear form after successful submission
                $title = $meaning = $example = '';
                
                // Notify moderators (you can implement email notification here)
                
            } catch (PDOException $e) {
                $error = 'Ralat semasa menghantar peribahasa. Sila cuba lagi.';
            }
        }
    }
}

// Get user's pending submissions
$db = new Database();
$conn = $db->getConnection();
$query = "SELECT * FROM peribahasa WHERE contributor_id = ? AND status = 'pending' ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute([get_current_user_id()]);
$pending_submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hantar Peribahasa - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
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
                        <a class="nav-link" href="../public/search.php">
                            <i class="bi bi-search"></i> Cari Peribahasa
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="../contributor/submit-peribahasa.php">
                            <i class="bi bi-plus-circle"></i> Hantar Peribahasa
                        </a>
                    </li>
                    <?php if (is_admin_or_moderator()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/logout.php">
                            <i class="bi bi-box-arrow-right"></i> Log Keluar
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Hantar Peribahasa Baru</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="" id="submitForm">
                            <div class="mb-3">
                                <label for="title" class="form-label">Peribahasa <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="title" 
                                       name="title" 
                                       value="<?php echo h($title ?? ''); ?>"
                                       required>
                                <div class="form-text">Contoh: Bagai telur di hujung tanduk</div>
                            </div>

                            <div class="mb-3">
                                <label for="meaning" class="form-label">Maksud <span class="text-danger">*</span></label>
                                <textarea class="form-control" 
                                          id="meaning" 
                                          name="meaning" 
                                          rows="3" 
                                          required><?php echo h($meaning ?? ''); ?></textarea>
                                <div class="form-text">Terangkan maksud peribahasa dengan jelas</div>
                            </div>

                            <div class="mb-3">
                                <label for="example" class="form-label">Contoh Penggunaan</label>
                                <textarea class="form-control" 
                                          id="example" 
                                          name="example" 
                                          rows="2"><?php echo h($example ?? ''); ?></textarea>
                                <div class="form-text">Berikan contoh ayat yang menggunakan peribahasa ini (pilihan)</div>
                            </div>

                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Peribahasa yang dihantar akan disemak oleh moderator sebelum diterbitkan.
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send"></i> Hantar Peribahasa
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Pending Submissions -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Sumbangan Anda</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pending_submissions)): ?>
                            <p class="text-muted">Tiada peribahasa yang menunggu semakan.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($pending_submissions as $submission): ?>
                                    <div class="list-group-item">
                                        <h6 class="mb-1"><?php echo h($submission['title']); ?></h6>
                                        <p class="mb-1 small"><?php echo h($submission['meaning']); ?></p>
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i> 
                                            <?php echo format_date($submission['created_at']); ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Submission Guidelines -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">Panduan Penyumbangan</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i> 
                                Pastikan ejaan yang betul
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i> 
                                Berikan maksud yang tepat dan jelas
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i> 
                                Elakkan peribahasa yang sudah wujud
                            </li>
                            <li>
                                <i class="bi bi-check-circle text-success"></i> 
                                Sertakan contoh penggunaan jika boleh
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.getElementById('submitForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const meaning = document.getElementById('meaning').value.trim();
            
            if (title.length < 3) {
                e.preventDefault();
                alert('Sila masukkan peribahasa yang lengkap.');
                return;
            }
            
            if (meaning.length < 10) {
                e.preventDefault();
                alert('Sila berikan maksud yang lebih terperinci.');
                return;
            }
        });
    </script>
</body>
</html>
