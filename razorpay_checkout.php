<?php
// razorpay_checkout.php — lightweight bridge page (POST handler)
// Called when needing to re-open Razorpay checkout after a page refresh
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

// Just redirect to payment page if session still active
if (!empty($_SESSION['razorpay_order_id'])) {
    header('Location: ' . APP_URL . '/payment.php');
} else {
    header('Location: ' . APP_URL . '/book_slot.php');
}
exit;
