<?php
require_once 'config.php';

// Flash message functions
function flash($message, $category = 'info') {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = ['message' => $message, 'category' => $category];
}

function get_flashed_messages() {
    if (isset($_SESSION['flash_messages'])) {
        $messages = $_SESSION['flash_messages'];
        unset($_SESSION['flash_messages']);
        return $messages;
    }
    return [];
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Login required middleware
function require_login() {
    if (!is_logged_in()) {
        flash('Bitte melden Sie sich an.', 'danger');
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

// Admin required middleware
function require_admin() {
    if (!is_logged_in() || !is_admin()) {
        flash('Sie haben keine Berechtigung für diese Seite.', 'danger');
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

// Redirect helper
function redirect($path) {
    header('Location: ' . BASE_URL . '/' . $path);
    exit;
}

// URL for helper
function url_for($path) {
    return BASE_URL . '/' . $path;
}

// Get current logged in user
function get_logged_in_user() {
    if (is_logged_in()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role']
        ];
    }
    return null;
}
?>
