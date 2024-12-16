<?php

/**
 * Sanitize output
 * @param string $text
 * @return string
 */
function h($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate URL
 * @param string $path
 * @return string
 */
function url($path = '') {
    // Remove leading slash if present
    $path = ltrim($path, '/');
    
    // Get the protocol (http or https)
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    
    // Build the complete URL
    return $protocol . BASE_URL . ($path ? '/' . $path : '');
}

/**
 * Redirect to URL
 * @param string $path
 */
function redirect($path) {
    header('Location: ' . url($path));
    exit;
}

/**
 * Check if user is logged in
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user']);
}

/**
 * Redirect if user is not logged in
 */
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('auth/login.php');
    }
}

/**
 * Get current user ID
 * @return int|null
 */
function get_current_user_id() {
    return $_SESSION['user']['id'] ?? null;
}

/**
 * Check if user is admin
 * @return bool
 */
function is_admin() {
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}

/**
 * Check if user is moderator
 * @return bool
 */
function is_moderator() {
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'moderator';
}

/**
 * Check if user is admin or moderator
 * @return bool
 */
function is_admin_or_moderator() {
    return is_admin() || is_moderator();
}

/**
 * Format date to Malaysian format
 * @param string $date
 * @return string
 */
function format_date($date) {
    return date('d M Y, H:i', strtotime($date));
}

/**
 * Generate random string for peribahasa of the day
 * @return string SQL query part
 */
function get_random_order_sql() {
    return 'RAND()';
}
