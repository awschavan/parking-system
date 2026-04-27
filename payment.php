<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/parking.php';
require_once __DIR__ . '/includes/razorpay.php';
require_once __DIR__ . '/includes/db.php';

requireLogin();

if (empty($_SESSION['pending_booking'])) {
    header('Location: ' . APP_URL . '/book_slot.php');
    exit;
}

$pb   = $_SESSION['pending_booking'];
$user = getCurrentUser();
$error = '';
$razorpayOrder = null;

if (!isset($_SESSION['razorpay_order_id'])) {
    $bookingResult = createBooking($pb);
    if (!$bookingResult['success']) {
        $_SESSION['booking_error'] = $bookingResult['message'];
        unset($_SESSION['pending_booking']);
        header('Location: ' . APP_URL . '/book_slot.php');
        exit;
    }
    $bookingId = $bookingResult['booking_id'];
    $amount    = $bookingResult['amount'];

    try {
        $order = createRazorpayOrder($amount, 'booking_'.$bookingId, [
            'booking_id' => $bookingId,
            'slot'       => $pb['slot_number'],
            'vehicle'    => $pb['vehicle_number'],
            'upi_id'     => 'swapnilchavan963@okicici',
        ]);
        $pdo = getDB();
        $pdo->prepare('INSERT INTO payments (razorpay_order_id,user_id,booking_id,amount,payment_status) VALUES (?,?,?,?,"created")')
            ->execute([$order['id'],$pb['user_id'],$bookingId,$amount]);

        $_SESSION['razorpay_order_id']   = $order['id'];
        $_SESSION['razorpay_booking_id'] = $bookingId;
        $_SESSION['razorpay_amount']     = $amount;
        $razorpayOrder = $order;
    } catch (Exception $e) {
        $pdo = getDB();
        $pdo->prepare('UPDATE bookings SET status="cancelled" WHERE booking_id=?')->execute([$bookingId]);
        $pdo->prepare('UPDATE parking_slots SET status="available" WHERE slot_number=?')->execute([$pb['slot_number']]);
        $error = 'Payment gateway error: ' . $e->getMessage();
    }
} else {
    $razorpayOrder = ['id' => $_SESSION['razorpay_order_id']];
}

$bookingId = $_SESSION['razorpay_booking_id'] ?? 0;
$amount    = $_SESSION['razorpay_amount'] ?? 0;
$bookingDate = $pb['booking_date'] ?? date('Y-m-d');
$startTime   = $pb['start_time']   ?? '';
$endTime     = $pb['end_time']     ?? '';

$pageTitle = 'Complete Payment';
include __DIR__ . '/includes/header.php';
?>
<div class="container py-5">
<div class="row justify-content-center">
<div class="col-md-8 col-lg-5">

<?php if ($error): ?>
<div class="alert-custom alert-danger-custom mb-4"><?= htmlspecialchars($error) ?></div>
<a href="book_slot.php" class="btn-primary-custom text-decoration-none">Try Again</a>
<?php else: ?>

<div class="card-dark fade-in-up">
  <h5 style="font-family:'Syne',sans-serif;font-weight:700;margin-bottom:1.5rem">
    <i class="bi bi-receipt me-2 text-accent"></i>Booking Summary
  </h5>

  <div class="receipt-row">
    <span class="receipt-label">Slot Number</span>
    <strong class="text-accent" style="font-family:'Syne',sans-serif;font-size:1.3rem"><?= htmlspecialchars($pb['slot_number']) ?></strong>
  </div>
  <div class="receipt-row">
    <span class="receipt-label">Block</span>
    <span class="receipt-value">Block <?= htmlspecialchars($pb['block_name']) ?></span>
  </div>
  <div class="receipt-row">
    <span class="receipt-label">Vehicle Number</span>
    <span class="receipt-value"><?= htmlspecialchars($pb['vehicle_number']) ?></span>
  </div>
  <div class="receipt-row">
    <span class="receipt-label">Booking Date</span>
    <span class="receipt-value"><?= date('d M Y', strtotime($bookingDate)) ?></span>
  </div>
  <div class="receipt-row">
    <span class="receipt-label">Time</span>
    <span class="receipt-value"><?= htmlspecialchars($startTime) ?> → <?= htmlspecialchars($endTime) ?></span>
  </div>
  <div class="receipt-row">
    <span class="receipt-label">Duration</span>
    <span class="receipt-value"><?= htmlspecialchars($pb['duration']) ?></span>
  </div>
  <div class="receipt-row">
    <span class="receipt-label">Mobile</span>
    <span class="receipt-value"><?= htmlspecialchars($user['mobile_number']) ?></span>
  </div>
  <div class="receipt-row">
    <span class="receipt-label">Amount</span>
    <span style="font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;color:var(--brand-accent)">₹<?= number_format($amount,2) ?></span>
  </div>

  <hr class="divider">

  <!-- Payment Methods -->
  <div class="d-flex flex-wrap gap-2 align-items-center justify-content-center mb-4">
    <span class="text-muted small">Pay via:</span>
    <?php foreach(['UPI','Google Pay','PhonePe','Cards'] as $pm): ?>
    <span style="background:rgba(255,255,255,.08);color:var(--text-main);padding:.4rem .8rem;border-radius:8px;font-weight:600;font-size:.82rem"><?= $pm ?></span>
    <?php endforeach; ?>
  </div>

  <button id="rzp-button" class="btn-primary-custom w-100 pulse-green" style="font-size:1.1rem;padding:1rem">
    <i class="bi bi-shield-lock me-2"></i>Pay ₹<?= number_format($amount,2) ?> Securely
  </button>

  <p class="text-center mt-3 mb-0" style="font-size:.78rem;color:var(--text-muted)">
    <i class="bi bi-lock me-1"></i>256-bit SSL · Razorpay · UPI: swapnilchavan963@okicici
  </p>
</div>
<?php endif; ?>

</div>
</div>
</div>

<?php if (!$error && $razorpayOrder): ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
var options = {
  key:         "<?= RAZORPAY_KEY_ID ?>",
  amount:      <?= (int)($amount*100) ?>,
  currency:    "INR",
  name:        "ParkEase",
  description: "Slot <?= htmlspecialchars($pb['slot_number']) ?> — <?= htmlspecialchars($pb['duration']) ?>",
  order_id:    "<?= $razorpayOrder['id'] ?>",
  prefill: {
    name:    "<?= htmlspecialchars($user['full_name']) ?>",
    contact: "<?= htmlspecialchars($user['mobile_number']) ?>",
    email:   "parking@parkeease.com"
  },
  config: {
    display: {
      blocks: {
        upi: { name: "UPI / Google Pay / PhonePe", instruments: [
          { method: "upi" }
        ]},
        card: { name: "Cards", instruments: [
          { method: "card" }
        ]}
      },
      sequence: ["block.upi","block.card"],
      preferences: { show_default_blocks: true }
    }
  },
  theme: { color: "#f97316" },
  handler: function(response) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = 'http://43.205.129.196/parking-system/payment_success.php';
    var fields = {
      razorpay_payment_id: response.razorpay_payment_id,
      razorpay_order_id:   response.razorpay_order_id,
      razorpay_signature:  response.razorpay_signature,
      booking_id:          '<?= $bookingId ?>'
    };
    Object.entries(fields).forEach(([k,v]) => {
      var i = document.createElement('input');
      i.type='hidden'; i.name=k; i.value=v;
      form.appendChild(i);
    });
    document.body.appendChild(form);
    form.submit();
  },
  modal: {
    ondismiss: function() {
      var btn = document.getElementById('rzp-button');
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-shield-lock me-2"></i>Pay ₹<?= number_format($amount,2) ?> Securely';
    }
  }
};
var rzp = new Razorpay(options);
document.getElementById('rzp-button').addEventListener('click', function() {
  this.disabled = true;
  this.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Opening Payment...';
  rzp.open();
});
</script>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
