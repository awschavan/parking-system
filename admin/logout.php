<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
unset($_SESSION['admin_id'], $_SESSION['admin_name']);
session_destroy();
header('Location: ' . APP_URL . '/admin/login.php');
exit;
