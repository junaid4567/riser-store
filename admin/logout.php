<?php
require_once __DIR__ . '/auth.php';
$_SESSION = [];
session_destroy();
header('Location: ' . BASE_URL . '/admin/login.php');
exit;
