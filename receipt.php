<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/parking.php';

requireLogin();

$bookingId = (int)($_GET['id'] ?? 0);
$isNew     = isset($_GET['new']);
$booking   = getBookingById($bookingId);

// Security: only own booking
if (!$booking || $booking['user_id'] != $_SESSION['user_id']) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$pageTitle = 'Receipt #' . $bookingId;
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">

  <?php if ($isNew): ?>
  <div class="alert-custom alert-success-custom mb-4 text-center auto-dismiss">
    <i class="bi bi-check-circle me-2"></i>
    <strong>Payment Successful!</strong> Your parking slot <strong><?= htmlspecialchars($booking['slot_number']) ?></strong> is confirmed.
  </div>
  <?php endif; ?>

  <div class="receipt-box fade-in-up">
    <div class="receipt-header">
      <i class="bi bi-p-square-fill" style="font-size:2rem"></i>
      <h2 class="mt-2"><?= APP_NAME ?></h2>
      <p style="margin:0;opacity:.85;font-size:.9rem">Parking Booking Receipt</p>
    </div>

    <div class="receipt-body">
      <!-- Success tick -->
      <div class="text-center mb-4">
        <div style="width:64px;height:64px;background:rgba(34,197,94,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto">
          <i class="bi bi-check-lg" style="font-size:2rem;color:var(--slot-green)"></i>
        </div>
        <p class="mt-2 mb-0" style="color:var(--slot-green);font-weight:600">Booking Confirmed</p>
      </div>

      <div class="receipt-row">
        <span class="receipt-label">Booking ID</span>
        <span class="receipt-value">#<?= $booking['booking_id'] ?></span>
      </div>
      <div class="receipt-row">
        <span class="receipt-label">Slot Number</span>
        <span class="receipt-value text-accent" style="font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:800"><?= htmlspecialchars($booking['slot_number']) ?></span>
      </div>
      <div class="receipt-row">
        <span class="receipt-label">Block</span>
        <span class="receipt-value">Block <?= htmlspecialchars($booking['block_name']) ?></span>
      </div>
      <div class="receipt-row">
        <span class="receipt-label">Vehicle Number</span>
        <span class="receipt-value"><?= htmlspecialchars($booking['vehicle_number']) ?></span>
      </div>
      <div class="receipt-row">
        <span class="receipt-label">Name</span>
        <span class="receipt-value"><?= htmlspecialchars($booking['full_name']) ?></span>
      </div>
      <div class="receipt-row">
        <span class="receipt-label">Mobile</span>
        <span class="receipt-value"><?= htmlspecialchars($booking['mobile_number']) ?></span>
      </div>
      <div class="receipt-row">
        <span class="receipt-label">Duration</span>
        <span class="receipt-value"><?= htmlspecialchars($booking['duration']) ?></span>
      </div>
      <div class="receipt-row">
        <span class="receipt-label">Booking Time</span>
        <span class="receipt-value"><?= date('d M Y h:i A', strtotime($booking['booking_time'])) ?></span>
      </div>
      <div class="receipt-row">
        <span class="receipt-label">Valid Until</span>
        <span class="receipt-value"><?= date('d M Y h:i A', strtotime($booking['expiry_time'])) ?></span>
      </div>
      <div class="receipt-row">
        <span class="receipt-label">Payment ID</span>
        <span class="receipt-value" style="font-size:.8rem;word-break:break-all"><?= htmlspecialchars($booking['razorpay_payment_id'] ?? 'N/A') ?></span>
      </div>
      <div class="receipt-row">
        <span class="receipt-label">Status</span>
        <span class="status-badge badge-<?= $booking['status'] ?>"><?= ucfirst($booking['status']) ?></span>
      </div>
      <div class="receipt-row">
        <span class="receipt-label">Amount Paid</span>
        <span class="price-display" style="font-size:1.6rem">₹<?= number_format($booking['amount'], 2) ?></span>
      </div>

      <!-- Actions -->
      <div class="mt-4 d-flex gap-3 flex-wrap justify-content-center">
        <button onclick="printReceipt()" class="btn-primary-custom">
          <i class="bi bi-printer me-2"></i>Print Receipt
        </button>
        <a href="dashboard.php" class="btn-outline-custom text-decoration-none">
          <i class="bi bi-grid me-2"></i>Dashboard
        </a>
      </div>

      <p class="text-center mt-4 mb-0" style="font-size:.75rem;color:var(--text-muted)">
        Thank you for using <?= APP_NAME ?>. Please display this receipt at the parking gate.
      </p>
    </div>
  </div>
</div>

<style>
@media print {
  .navbar-custom, .footer, .btn-primary-custom, .btn-outline-custom, .auto-dismiss { display: none !important; }
  body { background: #fff !important; color: #000 !important; }
  .receipt-box { border: 2px solid #000 !important; max-width: 100% !important; }
  .receipt-header { background: #f97316 !important; -webkit-print-color-adjust: exact; }
  .receipt-header h2, .receipt-header p { color: #fff !important; }
  .receipt-label { color: #555 !important; }
  .receipt-value { color: #000 !important; }
  .receipt-row { border-bottom: 1px solid #eee !important; }
  .text-accent { color: #f97316 !important; }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
