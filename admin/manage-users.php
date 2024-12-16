<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Ensure user is admin
if (!is_admin()) {
    redirect('');
}

$db = new Database();
$conn = $db->getConnection();

$error = '';
$success = '';
$users = [];  // Initialize users array

// Handle user role updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'update_role') {
            $user_id = (int)$_POST['user_id'];
            $new_role = $_POST['role'];
            
            // Validate role
            if (!in_array($new_role, ['user', 'moderator', 'admin'])) {
                throw new Exception('Invalid role specified');
            }
            
            // Don't allow changing own role
            if ($user_id === get_current_user_id()) {
                throw new Exception('You cannot change your own role');
            }
            
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $user_id]);
            $success = 'User role updated successfully';
        }
        elseif ($_POST['action'] === 'toggle_status') {
            $user_id = (int)$_POST['user_id'];
            $new_status = $_POST['status'] === '1' ? 1 : 0;
            
            // Don't allow deactivating own account
            if ($user_id === get_current_user_id()) {
                throw new Exception('You cannot deactivate your own account');
            }
            
            $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->execute([$new_status, $user_id]);
            $success = 'User status updated successfully';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch all users
try {
    $query = "SELECT id, username, email, role, created_at, is_active, 
              (SELECT COUNT(*) FROM peribahasa WHERE contributor_id = users.id AND status = 'approved') as contribution_count 
              FROM users 
              ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($users === false) {
        throw new Exception('Failed to fetch users');
    }
} catch (PDOException $e) {
    $error = 'Error fetching users: ' . $e->getMessage();
    error_log("Database error in manage-users.php: " . $e->getMessage());
    $users = [];
} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Error in manage-users.php: " . $e->getMessage());
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urus Pengguna - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php require_once '../includes/menu.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Urus Pengguna</h1>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= h($success) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Pengguna</th>
                                <th>Emel</th>
                                <th>Peranan</th>
                                <th>Status</th>
                                <th>Sumbangan</th>
                                <th>Tarikh Daftar</th>
                                <th>Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= h($user['id']) ?></td>
                                    <td><?= h($user['username']) ?></td>
                                    <td><?= h($user['email']) ?></td>
                                    <td>
                                        <form method="post" class="d-inline role-form">
                                            <input type="hidden" name="action" value="update_role">
                                            <input type="hidden" name="user_id" value="<?= h($user['id']) ?>">
                                            <select name="role" class="form-select form-select-sm" 
                                                    onchange="this.form.submit()" 
                                                    <?= $user['id'] === get_current_user_id() ? 'disabled' : '' ?>>
                                                <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Pengguna</option>
                                                <option value="moderator" <?= $user['role'] === 'moderator' ? 'selected' : '' ?>>Moderator</option>
                                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="post" class="d-inline status-form">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?= h($user['id']) ?>">
                                            <input type="hidden" name="status" value="<?= $user['is_active'] ? '0' : '1' ?>">
                                            <button type="submit" class="btn btn-sm <?= $user['is_active'] ? 'btn-success' : 'btn-danger' ?>"
                                                    <?= $user['id'] === get_current_user_id() ? 'disabled' : '' ?>>
                                                <?= $user['is_active'] ? 'Aktif' : 'Tidak Aktif' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td><?= h($user['contribution_count']) ?></td>
                                    <td><?= format_date($user['created_at']) ?></td>
                                    <td>
                                        <a href="<?= url('public/profile.php?id=' . $user['id']) ?>" 
                                           class="btn btn-sm btn-info" 
                                           target="_blank">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
