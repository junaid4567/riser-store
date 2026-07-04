<?php
/**
 * Admin authentication guard.
 * Include at the top of any admin page that requires login.
 */
require_once __DIR__ . '/../includes/functions.php';

function isAdminLoggedIn() {
    return !empty($_SESSION['admin_id']);
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}
