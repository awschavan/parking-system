<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/parking.php';

requireLogin();

$bookingId = (int)($_GET['id'] ?? 0);
$error     = '';
$success   = '';

if ($bookingId > 0) {
    $reason = trim($_POST['reason'] ?? 'Cancelled by user');
    $result = cancelBooking($bookingId, $_SESSION['user_id'], $reason);
    if ($result['success']) {
        header('Location: ' . APP_URL . '/dashboard.php?tab=cancelled&cancelled=1');
        exit;
    } else {
        $error = $result['message'];
    }
} else {
    $error = 'Invalid booking ID.';
}

$pageTitle = 'Cancel Booking';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5 text-center">
  <div class="card-dark d-inline-block px-5 py-5">
    <div style="font-size:3rem;color:var(--slot-red)"><i class="bi bi-x-circle-fill"></i></div>
    <h3 class="mt-3" style="font-family:'Syne',sans-serif">Cancellation Failed</h3>
    <p class="text-muted"><?= htmlspecialchars($error) ?></p>
    <a href="dashboard.php" class="btn-primary-custom text-decoration-none mt-2 d-inline-block">Back to Dashboard</a>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
