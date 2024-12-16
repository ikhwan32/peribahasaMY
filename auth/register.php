<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Sila isi semua maklumat yang diperlukan.';
    } elseif ($password !== $confirm_password) {
        $error = 'Kata laluan tidak sepadan.';
    } elseif (strlen($password) < 6) {
        $error = 'Kata laluan mestilah sekurang-kurangnya 6 aksara.';
    } else {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Check if email already exists
        $query = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'Email telah didaftarkan.';
        } else {
            // Check if username already exists
            $query = "SELECT id FROM users WHERE username = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$username]);
            
            if ($stmt->fetch()) {
                $error = 'Nama pengguna telah digunakan.';
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'contributor')";
                $stmt = $conn->prepare($query);
                
                if ($stmt->execute([$username, $email, $hashed_password])) {
                    $success = 'Pendaftaran berjaya! Sila log masuk.';
                    header('refresh:2;url=login.php');
                } else {
                    $error = 'Ralat semasa mendaftar. Sila cuba lagi.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Daftar Akaun Baru</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Nama Pengguna</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Kata Laluan</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">Kata laluan mestilah sekurang-kurangnya 6 aksara.</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Sahkan Kata Laluan</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Daftar</button>
                            <a href="login.php" class="btn btn-link">Sudah Ada Akaun? Log Masuk</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
