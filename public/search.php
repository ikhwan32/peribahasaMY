<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

$peribahasas = [];
$total_results = 0;
$error = '';

try {
    if ($search !== '') {
        // Validate search term length
        if (mb_strlen($search) < 2) {
            $error = 'Sila masukkan sekurang-kurangnya 2 aksara untuk mencari.';
        } else {
            // Get total count for pagination
            $query = "SELECT COUNT(*) as count 
                    FROM peribahasa 
                    WHERE status = 'approved' 
                    AND (title LIKE ? OR meaning LIKE ? OR example_usage LIKE ?)";
            $stmt = $conn->prepare($query);
            $search_term = "%{$search}%";
            $stmt->execute([$search_term, $search_term, $search_term]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_results = $result['count'];

            if ($total_results > 0) {
                // Get paginated results
                $query = "SELECT p.*, u.username as contributor_name 
                        FROM peribahasa p 
                        LEFT JOIN users u ON p.contributor_id = u.id 
                        WHERE p.status = 'approved' 
                        AND (p.title LIKE ? OR p.meaning LIKE ? OR p.example_usage LIKE ?) 
                        ORDER BY p.title ASC 
                        LIMIT ? OFFSET ?";
                $stmt = $conn->prepare($query);
                $stmt->bindValue(1, $search_term, PDO::PARAM_STR);
                $stmt->bindValue(2, $search_term, PDO::PARAM_STR);
                $stmt->bindValue(3, $search_term, PDO::PARAM_STR);
                $stmt->bindValue(4, $per_page, PDO::PARAM_INT);
                $stmt->bindValue(5, $offset, PDO::PARAM_INT);
                $stmt->execute();
                $peribahasas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }
} catch (PDOException $e) {
    error_log("Search error: " . $e->getMessage());
    $error = 'Maaf, terdapat ralat teknikal semasa mencari. Sila cuba lagi.';
}

// Calculate total pages
$total_pages = ceil($total_results / $per_page);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Peribahasa - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo url('/assets/css/style.css'); ?>">
    <style>
        .highlight {
            background-color: #fff3cd;
            padding: 0 2px;
            border-radius: 2px;
        }
        #liveSearchResults {
            position: absolute;
            width: 100%;
            z-index: 1000;
            max-height: 400px;
            overflow-y: auto;
            box-shadow: 0 4px 6px rgba(0,0,0,.1);
        }
        .search-item {
            cursor: pointer;
        }
        .search-item:hover {
            background-color: #f8f9fa;
        }
        .search-meaning {
            font-size: 0.9em;
            color: #6c757d;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo url(''); ?>"><?php echo SITE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo url('public/search.php'); ?>">
                            <i class="bi bi-search"></i> Cari Peribahasa
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('contributor/submit-peribahasa.php'); ?>">
                                <i class="bi bi-plus-circle"></i> Hantar Peribahasa
                            </a>
                        </li>
                        <?php if (is_admin_or_moderator()): ?>
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
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center mb-5">
            <div class="col-md-8">
                <h1 class="text-center mb-4">Cari Peribahasa</h1>
                
                <!-- Search Form -->
                <form action="" method="GET" class="mb-4 position-relative">
                    <div class="input-group input-group-lg">
                        <input type="text" 
                               class="form-control" 
                               name="q" 
                               id="searchInput"
                               value="<?php echo h($search); ?>" 
                               placeholder="Cari peribahasa atau maksudnya..."
                               autocomplete="off">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-search"></i> Cari
                        </button>
                    </div>
                    <!-- Live Search Results -->
                    <div id="liveSearchResults" class="list-group shadow-sm"></div>
                </form>

                <!-- Search Results -->
                <?php if ($search !== ''): ?>
                    <div class="mb-3">
                        <h2>Hasil Carian</h2>
                        <?php if ($total_results > 0): ?>
                            <p class="text-muted">Dijumpai <?php echo number_format($total_results); ?> peribahasa untuk "<?php echo h($search); ?>"</p>
                        <?php else: ?>
                            <p class="text-muted">Tiada peribahasa dijumpai untuk "<?php echo h($search); ?>"</p>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($peribahasas)): ?>
                        <div class="row">
                            <?php foreach ($peribahasas as $peribahasa): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo h($peribahasa['title']); ?></h5>
                                            <p class="card-text"><?php echo h($peribahasa['meaning']); ?></p>
                                            <?php if ($peribahasa['example_usage']): ?>
                                                <p class="card-text"><small class="text-muted">Contoh: <?php echo h($peribahasa['example_usage']); ?></small></p>
                                            <?php endif; ?>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    Disumbangkan oleh: <?php echo h($peribahasa['contributor_name']); ?>
                                                </small>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?q=<?php echo urlencode($search); ?>&page=<?php echo ($page - 1); ?>">
                                                <i class="bi bi-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?q=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?q=<?php echo urlencode($search); ?>&page=<?php echo ($page + 1); ?>">
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Initial State -->
                    <div class="text-center text-muted">
                        <i class="bi bi-search display-1"></i>
                        <p class="lead">Masukkan kata kunci untuk mencari peribahasa</p>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Live search functionality
        const searchInput = document.getElementById('searchInput');
        const liveSearchResults = document.getElementById('liveSearchResults');
        let searchTimeout;

        function highlightText(text, query) {
            if (!query) return text;
            const regex = new RegExp(`(${query})`, 'gi');
            return text.replace(regex, '<span class="highlight">$1</span>');
        }

        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                liveSearchResults.classList.add('d-none');
                liveSearchResults.innerHTML = '';
                return;
            }
            
            // Add loading indicator
            liveSearchResults.classList.remove('d-none');
            liveSearchResults.innerHTML = '<div class="p-3 text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>';
            
            searchTimeout = setTimeout(() => {
                fetch(`<?php echo url('public/api/search.php'); ?>?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length === 0) {
                            liveSearchResults.innerHTML = '<div class="list-group-item text-muted">Tiada hasil carian</div>';
                            return;
                        }
                        
                        const html = data.map(item => `
                            <a href="?q=${encodeURIComponent(item.title)}" class="list-group-item list-group-item-action search-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <strong class="mb-1">${highlightText(item.title, query)}</strong>
                                </div>
                                <p class="mb-1 search-meaning">${highlightText(item.meaning, query)}</p>
                            </a>
                        `).join('');
                        
                        liveSearchResults.innerHTML = html;
                    })
                    .catch(() => {
                        liveSearchResults.innerHTML = '<div class="list-group-item text-danger">Ralat semasa mencari</div>';
                    });
            }, 300);
        });

        // Close live search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !liveSearchResults.contains(e.target)) {
                liveSearchResults.classList.add('d-none');
            }
        });

        // Highlight search results in the main content
        document.addEventListener('DOMContentLoaded', function() {
            const searchQuery = searchInput.value.trim();
            if (searchQuery) {
                document.querySelectorAll('.card-title, .card-text').forEach(element => {
                    if (!element.querySelector('.text-muted')) { // Don't highlight metadata
                        element.innerHTML = highlightText(element.textContent, searchQuery);
                    }
                });
            }
        });
    </script>
</body>
</html>
