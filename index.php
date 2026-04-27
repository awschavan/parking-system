<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/parking.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$blocks = getAllBlocksSummary();
$pageTitle = 'Smart Parking Management';
include __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<section class="hero">
  <div class="container position-relative">
    <div class="row align-items-center">
      <div class="col-lg-6 fade-in-up">
        <div class="mb-3">
          <span class="status-badge badge-active" style="font-size:.8rem">● Live Availability</span>
        </div>
        <h1 class="hero-title">Find &amp; Book Your<br><span>Perfect Spot</span></h1>
        <p class="hero-sub mt-3">6 blocks, 60 slots — select your own space, pay instantly via UPI, Google Pay, Debit/Credit Card.</p>
        <div class="mt-4 d-flex gap-3 flex-wrap">
          <a href="register.php" class="btn-primary-custom text-decoration-none">Get Started Free</a>
          <a href="login.php" class="btn-outline-custom text-decoration-none">Sign In</a>
        </div>
      </div>
      <div class="col-lg-6 mt-5 mt-lg-0">
        <div class="row g-3">
          <?php foreach ($blocks as $b): ?>
          <div class="col-6 col-md-4 fade-in-up">
            <div class="card-dark text-center py-3">
              <div style="font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:var(--brand-accent)"><?= htmlspecialchars($b['block_name']) ?></div>
              <div class="mt-1" style="font-size:.8rem;color:var(--text-muted)">Block</div>
              <hr class="divider my-2">
              <div style="color:var(--slot-green);font-weight:700"><?= $b['available'] ?> available</div>
              <div style="color:var(--slot-red);font-size:.8rem"><?= $b['booked'] ?> booked</div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Features -->
<section class="section">
  <div class="container">
    <div class="row g-4 text-center">
      <div class="col-md-3">
        <div class="card-dark h-100 py-4">
          <div style="font-size:2rem;color:var(--brand-accent)"><i class="bi bi-map"></i></div>
          <h5 class="mt-3 mb-1" style="font-family:'Syne',sans-serif">Pick Your Slot</h5>
          <p class="text-muted small mb-0">Visual grid layout — click the exact slot you want.</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card-dark h-100 py-4">
          <div style="font-size:2rem;color:var(--brand-teal)"><i class="bi bi-credit-card-2-front"></i></div>
          <h5 class="mt-3 mb-1" style="font-family:'Syne',sans-serif">Instant Payment</h5>
          <p class="text-muted small mb-0">Razorpay — UPI, Google Pay, PhonePe, Cards.</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card-dark h-100 py-4">
          <div style="font-size:2rem;color:var(--slot-green)"><i class="bi bi-receipt"></i></div>
          <h5 class="mt-3 mb-1" style="font-family:'Syne',sans-serif">Digital Receipt</h5>
          <p class="text-muted small mb-0">Instant printable receipt after every booking.</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card-dark h-100 py-4">
          <div style="font-size:2rem;color:var(--slot-red)"><i class="bi bi-x-circle"></i></div>
          <h5 class="mt-3 mb-1" style="font-family:'Syne',sans-serif">Easy Cancellation</h5>
          <p class="text-muted small mb-0">Cancel anytime before expiry from your dashboard.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Pricing -->
<section class="section" style="background:var(--brand-mid)">
  <div class="container">
    <div class="section-title"><i class="bi bi-tags-fill text-accent"></i> Pricing</div>
    <div class="row g-4 justify-content-center">
      <div class="col-md-4">
        <div class="card-dark text-center py-4">
          <i class="bi bi-clock" style="font-size:2rem;color:var(--brand-teal)"></i>
          <div class="price-display mt-2">₹<?= PRICE_HOURLY ?></div>
          <div style="color:var(--text-muted)">per hour</div>
          <hr class="divider">
          <p class="text-muted small">Perfect for quick visits and short errands.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card-dark text-center py-4" style="border-color:rgba(249,115,22,.4)">
          <i class="bi bi-sun" style="font-size:2rem;color:var(--brand-accent)"></i>
          <div class="price-display mt-2">₹<?= PRICE_DAILY ?></div>
          <div style="color:var(--text-muted)">per day</div>
          <hr class="divider">
          <p class="text-muted small">Full day parking — best value for long stays.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
