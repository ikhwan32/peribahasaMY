<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "ALTER TABLE peribahasa ADD COLUMN IF NOT EXISTS feedback TEXT AFTER approved_by";
    $conn->exec($query);
    echo "Feedback column added successfully!\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
