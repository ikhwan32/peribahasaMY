<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Ensure user is admin or moderator
require_login();
if (!is_admin_or_moderator()) {
    redirect('');
}

$db = new Database();
$conn = $db->getConnection();

$error = '';
$success = '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'create':
                if (empty($_POST['title']) || empty($_POST['meaning'])) {
                    throw new Exception('Sila isi semua medan yang diperlukan');
                }
                
                $stmt = $conn->prepare("INSERT INTO peribahasa (title, meaning, example_usage, status, contributor_id, approved_by) VALUES (?, ?, ?, 'approved', ?, ?)");
                $stmt->execute([
                    $_POST['title'],
                    $_POST['meaning'],
                    $_POST['example_usage'] ?? null,
                    get_current_user_id(),
                    get_current_user_id()
                ]);
                $success = 'Peribahasa berjaya ditambah';
                break;

            case 'update':
                if (empty($_POST['title']) || empty($_POST['meaning'])) {
                    throw new Exception('Sila isi semua medan yang diperlukan');
                }
                
                $stmt = $conn->prepare("UPDATE peribahasa SET title = ?, meaning = ?, example_usage = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['title'],
                    $_POST['meaning'],
                    $_POST['example_usage'] ?? null,
                    $_POST['peribahasa_id']
                ]);
                $success = 'Peribahasa berjaya dikemaskini';
                break;

            case 'delete':
                $stmt = $conn->prepare("DELETE FROM peribahasa WHERE id = ?");
                $stmt->execute([$_POST['peribahasa_id']]);
                $success = 'Peribahasa berjaya dipadam';
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log($e->getMessage());
    }
}

// Get total count for pagination
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM peribahasa");
    $total_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $total_pages = ceil($total_count / $per_page);

    // Get peribahasa with contributor info
    $query = "SELECT p.*, u.username as contributor_name 
              FROM peribahasa p 
              LEFT JOIN users u ON p.contributor_id = u.id 
              ORDER BY p.created_at DESC 
              LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $per_page, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $peribahasas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Error fetching data';
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urus Peribahasa - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php require_once '../includes/menu.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Urus Peribahasa</h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle"></i> Tambah Peribahasa
            </button>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= h($success) ?></div>
        <?php endif; ?>

        <!-- Peribahasa List -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Peribahasa</th>
                                <th>Maksud</th>
                                <th>Contoh</th>
                                <th>Penyumbang</th>
                                <th>Tarikh</th>
                                <th>Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($peribahasas as $peribahasa): ?>
                                <tr>
                                    <td><?= h($peribahasa['id']) ?></td>
                                    <td><?= h($peribahasa['title']) ?></td>
                                    <td><?= h($peribahasa['meaning']) ?></td>
                                    <td><?= h($peribahasa['example_usage']) ?></td>
                                    <td><?= h($peribahasa['contributor_name']) ?></td>
                                    <td><?= format_date($peribahasa['created_at']) ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary edit-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal"
                                                data-id="<?= h($peribahasa['id']) ?>"
                                                data-title="<?= h($peribahasa['title']) ?>"
                                                data-meaning="<?= h($peribahasa['meaning']) ?>"
                                                data-example="<?= h($peribahasa['example_usage']) ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteModal"
                                                data-id="<?= h($peribahasa['id']) ?>"
                                                data-title="<?= h($peribahasa['title']) ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Peribahasa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label class="form-label">Peribahasa</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Maksud</label>
                            <textarea class="form-control" name="meaning" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contoh Penggunaan</label>
                            <textarea class="form-control" name="example_usage"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Tambah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Peribahasa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="peribahasa_id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Peribahasa</label>
                            <input type="text" class="form-control" name="title" id="edit_title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Maksud</label>
                            <textarea class="form-control" name="meaning" id="edit_meaning" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contoh Penggunaan</label>
                            <textarea class="form-control" name="example_usage" id="edit_example"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Padam Peribahasa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Adakah anda pasti mahu memadamkan peribahasa ini?</p>
                    <p class="text-danger" id="delete_title"></p>
                </div>
                <form method="post">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="peribahasa_id" id="delete_id">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Padam</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle edit button clicks
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_id').value = this.dataset.id;
                document.getElementById('edit_title').value = this.dataset.title;
                document.getElementById('edit_meaning').value = this.dataset.meaning;
                document.getElementById('edit_example').value = this.dataset.example;
            });
        });

        // Handle delete button clicks
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('delete_id').value = this.dataset.id;
                document.getElementById('delete_title').textContent = this.dataset.title;
            });
        });
    </script>
</body>
</html>
