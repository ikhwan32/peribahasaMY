<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

if (strlen($search) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Improved search ranking with FULLTEXT search
    $query = "SELECT id, title, meaning,
              MATCH(title) AGAINST (? IN BOOLEAN MODE) * 2 +
              MATCH(meaning) AGAINST (? IN BOOLEAN MODE) as relevance
              FROM peribahasa 
              WHERE status = 'approved' 
              AND (
                  MATCH(title, meaning) AGAINST (? IN BOOLEAN MODE)
                  OR title LIKE ? 
                  OR meaning LIKE ?
              )
              ORDER BY 
                CASE 
                    WHEN title = ? THEN 100
                    WHEN title LIKE ? THEN 50
                    WHEN title LIKE ? THEN 25
                    ELSE relevance
                END DESC,
                title ASC 
              LIMIT ?";
              
    $stmt = $conn->prepare($query);
    $search_term = "%{$search}%";
    $start_term = "{$search}%";
    $stmt->execute([
        $search, // MATCH title
        $search, // MATCH meaning
        $search, // MATCH both
        $search_term, // LIKE title/meaning
        $search_term,
        $search, // Exact match
        $start_term, // Starts with
        $search_term, // Contains
        $limit
    ]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Truncate long meanings for live search
    foreach ($results as &$result) {
        if (strlen($result['meaning']) > 150) {
            $result['meaning'] = substr($result['meaning'], 0, 147) . '...';
        }
        unset($result['relevance']); // Remove relevance score from output
    }
    
    echo json_encode($results);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
