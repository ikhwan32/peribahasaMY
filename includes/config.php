<?php
// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', ''); //define user later since this is a public repo
define('DB_PASS', '');
define('DB_NAME', '');

// Application configuration
define('SITE_NAME', 'Peribahasa Malaysia');
define('BASE_URL', 'https://peribahasa.ikhwanhadi.my/');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_MODERATOR', 'moderator');
define('ROLE_CONTRIBUTOR', 'contributor');
