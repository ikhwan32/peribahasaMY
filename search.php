<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

$search = $_GET['q'] ?? '';
$results = [];
$error = '';

if (!empty($search)) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $query = "SELECT p.*, u.username as contributor_name, COUNT(c.id) as comment_count
                  FROM peribahasa p 
                  LEFT JOIN users u ON p.contributor_id = u.id
                  LEFT JOIN comments c ON p.id = c.peribahasa_id
                  WHERE p.status = 'approved' 
                  AND (p.title LIKE ? OR p.meaning LIKE ? OR p.example_usage LIKE ?)
                  GROUP BY p.id
                  ORDER BY p.created_at DESC";
        
        $search_term = "%$search%";
        $stmt = $conn->prepare($query);
        $stmt->execute([$search_term, $search_term, $search_term]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error = 'Ralat semasa mencari peribahasa.';
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carian: <?php echo h($search); ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3">Hasil Carian: "<?php echo h($search); ?>"</h1>
                <p class="text-muted"><?php echo count($results); ?> peribahasa dijumpai</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <?php if (empty($results) && !empty($search)): ?>
            <div class="alert alert-info">
                Tiada peribahasa dijumpai untuk carian "<?php echo h($search); ?>".
                <a href="index.php" class="alert-link">Kembali ke halaman utama</a>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php foreach ($results as $item): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="public/peribahasa.php?id=<?php echo $item['id']; ?>" class="text-decoration-none">
                                    <?php echo h($item['title']); ?>
                                </a>
                            </h5>
                            <p class="card-text"><?php echo h($item['meaning']); ?></p>
                            <?php if ($item['example_usage']): ?>
                                <p class="card-text"><small class="text-muted"><?php echo h($item['example_usage']); ?></small></p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent">
                            <small class="text-muted">
                                <i class="bi bi-person"></i> 
                                <a href="user/profile.php?id=<?php echo $item['contributor_id']; ?>" 
                                   class="text-decoration-none text-muted">
                                    <?php echo h($item['contributor_name']); ?>
                                </a> |
                                <i class="bi bi-calendar"></i> <?php echo format_date($item['created_at']); ?> |
                                <i class="bi bi-chat"></i> <?php echo $item['comment_count']; ?> komen
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
