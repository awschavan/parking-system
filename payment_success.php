<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/parking.php';
require_once __DIR__ . '/includes/razorpay.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$razorpayPaymentId = trim($_POST['razorpay_payment_id'] ?? '');
$razorpayOrderId   = trim($_POST['razorpay_order_id']   ?? '');
$razorpaySignature = trim($_POST['razorpay_signature']  ?? '');
$bookingId         = (int)($_POST['booking_id']         ?? 0);

$error = '';

// Verify signature
if (!verifyRazorpaySignature($razorpayOrderId, $razorpayPaymentId, $razorpaySignature)) {
    $error = 'Payment verification failed. Signature mismatch. Please contact support.';
} else {
    // Confirm booking in DB
    confirmBooking($bookingId, $razorpayOrderId, $razorpayPaymentId, $razorpaySignature);

    // Clean up session
    unset($_SESSION['pending_booking']);
    unset($_SESSION['razorpay_order_id']);
    unset($_SESSION['razorpay_booking_id']);
    unset($_SESSION['razorpay_amount']);

    // Redirect to receipt
    header('Location: ' . APP_URL . '/receipt.php?id=' . $bookingId . '&new=1');
    exit;
}

$pageTitle = 'Payment Result';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5 text-center">
  <div class="card-dark d-inline-block px-5 py-5">
    <div style="font-size:3rem;color:var(--slot-red)"><i class="bi bi-x-circle-fill"></i></div>
    <h3 class="mt-3" style="font-family:'Syne',sans-serif">Payment Failed</h3>
    <p class="text-muted"><?= htmlspecialchars($error) ?></p>
    <a href="dashboard.php" class="btn-primary-custom text-decoration-none mt-3 d-inline-block">Go to Dashboard</a>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
