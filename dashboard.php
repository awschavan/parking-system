<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/parking.php';

requireLogin();

$user    = getCurrentUser();
$tab     = $_GET['tab'] ?? 'active';
$active  = getUserActiveBookings($_SESSION['user_id']);
$history = getUserBookingHistory($_SESSION['user_id']);

$totalBookings  = count($history);
$activeCount    = count($active);
$cancelledCount = count(array_filter($history, fn($b) => $b['status'] === 'cancelled'));
$completedCount = count(array_filter($history, fn($b) => $b['status'] === 'completed'));
$bookingLimit   = 5;
$slotsLeft      = $bookingLimit - $activeCount;

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>

<style>
.dash-hero { background:linear-gradient(135deg,#161b22 0%,#1a2436 100%); border-bottom:1px solid var(--border); padding:2rem 0 0; }
.user-avatar { width:56px;height:56px;background:linear-gradient(135deg,var(--brand-accent),#ea6c0a);border-radius:14px;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:1.4rem;color:#fff;flex-shrink:0; }
.limit-bar-wrap { background:rgba(255,255,255,.06);border:1px solid var(--border);border-radius:12px;padding:1rem 1.25rem; }
.limit-bar-track { height:8px;background:rgba(255,255,255,.08);border-radius:999px;overflow:hidden;margin:.5rem 0 .3rem; }
.limit-bar-fill { height:100%;border-radius:999px;transition:width .5s ease; }
.dash-tab-nav { display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:2rem; }
.dash-tab-btn { padding:.75rem 1.5rem;background:none;border:none;color:var(--text-muted);font-family:'Syne',sans-serif;font-weight:600;font-size:.9rem;cursor:pointer;position:relative;text-decoration:none;transition:color .2s;display:flex;align-items:center;gap:.4rem; }
.dash-tab-btn::after { content:'';position:absolute;bottom:-2px;left:0;right:0;height:2px;background:var(--brand-accent);transform:scaleX(0);transition:transform .2s;border-radius:2px; }
.dash-tab-btn.active { color:var(--brand-accent); }
.dash-tab-btn.active::after { transform:scaleX(1); }
.dash-tab-btn:hover { color:var(--text-main); }
.dash-tab-btn .tab-count { background:rgba(249,115,22,.15);color:var(--brand-accent);border-radius:999px;padding:.1rem .5rem;font-size:.72rem;font-weight:700; }
.booking-card-pro { background:var(--card-bg);border:1px solid var(--border);border-radius:14px;overflow:hidden;transition:border-color .2s,transform .15s; }
.booking-card-pro:hover { border-color:rgba(249,115,22,.3);transform:translateY(-2px); }
.booking-card-header { background:rgba(249,115,22,.06);border-bottom:1px solid var(--border);padding:.9rem 1.25rem;display:flex;justify-content:space-between;align-items:center; }
.booking-card-body { padding:1.25rem; }
.booking-meta-row { display:flex;justify-content:space-between;align-items:center;padding:.45rem 0;border-bottom:1px solid rgba(255,255,255,.04);font-size:.85rem; }
.booking-meta-row:last-child { border-bottom:none; }
.booking-meta-label { color:var(--text-muted); }
.booking-meta-value { font-weight:500; }
.slot-big { font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;color:var(--brand-accent);line-height:1; }
.time-chip { display:inline-flex;align-items:center;gap:.3rem;background:rgba(6,182,212,.1);color:var(--brand-teal);border-radius:6px;padding:.2rem .6rem;font-size:.78rem;font-weight:600; }
.empty-state { text-align:center;padding:4rem 2rem; }
.empty-state-icon { width:72px;height:72px;background:rgba(255,255,255,.04);border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;font-size:2rem;color:var(--text-muted); }
.history-table-wrap { background:var(--card-bg);border:1px solid var(--border);border-radius:14px;overflow:hidden; }
</style>

<div class="dash-hero">
  <div class="container">
    <div class="row align-items-center mb-3">
      <div class="col">
        <div class="d-flex align-items-center gap-3">
          <div class="user-avatar"><?= strtoupper(substr($user['full_name'],0,1)) ?></div>
          <div>
            <h4 style="font-family:'Syne',sans-serif;font-weight:800;margin:0;color:#fff"><?= htmlspecialchars($user['full_name']) ?></h4>
            <div style="font-size:.85rem;color:var(--text-muted);margin-top:.2rem">
              <i class="bi bi-phone me-1"></i><?= htmlspecialchars($user['mobile_number']) ?>
              &nbsp;·&nbsp;
              <i class="bi bi-car-front me-1"></i><?= htmlspecialchars($user['vehicle_type']) ?>
            </div>
          </div>
        </div>
      </div>
      <div class="col-auto mt-3 mt-md-0">
        <?php if ($slotsLeft > 0): ?>
        <a href="book_slot.php" class="btn-primary-custom text-decoration-none"><i class="bi bi-plus-circle me-1"></i>Book a Slot</a>
        <?php else: ?>
        <span class="btn-primary-custom" style="opacity:.5;cursor:not-allowed"><i class="bi bi-x-circle me-1"></i>Limit Reached</span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-3">
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon orange"><i class="bi bi-calendar-check"></i></div>
          <div><div class="stat-number"><?= $totalBookings ?></div><div class="stat-label">Total Bookings</div></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon green"><i class="bi bi-car-front"></i></div>
          <div><div class="stat-number"><?= $activeCount ?></div><div class="stat-label">Active Now</div></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon teal"><i class="bi bi-check-circle"></i></div>
          <div><div class="stat-number"><?= $completedCount ?></div><div class="stat-label">Completed</div></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon red"><i class="bi bi-x-circle"></i></div>
          <div><div class="stat-number"><?= $cancelledCount ?></div><div class="stat-label">Cancelled</div></div>
        </div>
      </div>
    </div>

    <!-- Booking Limit Bar -->
    <div class="limit-bar-wrap mb-3">
      <div class="d-flex justify-content-between align-items-center">
        <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:.9rem">
          <i class="bi bi-speedometer2 me-2 text-accent"></i>Booking Limit
        </div>
        <div style="font-size:.85rem">
          <strong style="color:<?= $activeCount>=5?'var(--slot-red)':'var(--brand-accent)' ?>"><?= $activeCount ?></strong>
          <span class="text-muted"> / <?= $bookingLimit ?> slots used</span>
          <?php if ($slotsLeft > 0): ?>
          <span class="ms-2 time-chip"><i class="bi bi-check"></i><?= $slotsLeft ?> remaining</span>
          <?php else: ?>
          <span class="ms-2" style="background:rgba(239,68,68,.15);color:#ef4444;border-radius:6px;padding:.2rem .6rem;font-size:.78rem;font-weight:600">Max reached</span>
          <?php endif; ?>
        </div>
      </div>
      <?php $pct = ($activeCount/$bookingLimit)*100; ?>
      <div class="limit-bar-track">
        <div class="limit-bar-fill" style="width:<?= $pct ?>%;background:<?= $pct>=100?'var(--slot-red)':($pct>=60?'var(--slot-yellow)':'var(--slot-green)') ?>"></div>
      </div>
      <small class="text-muted">Max <?= $bookingLimit ?> active bookings per mobile number. Cancel a booking to free up a slot.</small>
    </div>

    <!-- Tabs -->
    <div class="dash-tab-nav">
      <a href="?tab=active" class="dash-tab-btn <?= $tab==='active'?'active':'' ?>">
        <i class="bi bi-car-front"></i> Active
        <?php if ($activeCount>0): ?><span class="tab-count"><?= $activeCount ?></span><?php endif; ?>
      </a>
      <a href="?tab=history" class="dash-tab-btn <?= $tab==='history'?'active':'' ?>">
        <i class="bi bi-clock-history"></i> History
        <?php if ($totalBookings>0): ?><span class="tab-count"><?= $totalBookings ?></span><?php endif; ?>
      </a>
      <a href="?tab=cancelled" class="dash-tab-btn <?= $tab==='cancelled'?'active':'' ?>">
        <i class="bi bi-x-circle"></i> Cancelled
        <?php if ($cancelledCount>0): ?><span class="tab-count"><?= $cancelledCount ?></span><?php endif; ?>
      </a>
    </div>
  </div>
</div>

<div class="container py-4 pb-5">

  <?php if (isset($_GET['cancelled'])): ?>
  <div class="alert-custom alert-success-custom mb-4 auto-dismiss">
    <i class="bi bi-check-circle me-2"></i>Booking cancelled. Slot is now available.
  </div>
  <?php endif; ?>

  <!-- ACTIVE -->
  <?php if ($tab==='active'): ?>
  <?php if (empty($active)): ?>
  <div class="empty-state">
    <div class="empty-state-icon"><i class="bi bi-p-square"></i></div>
    <h5 style="font-family:'Syne',sans-serif;font-weight:700">No Active Bookings</h5>
    <p class="text-muted mb-4">Book a parking slot to get started.</p>
    <a href="book_slot.php" class="btn-primary-custom text-decoration-none"><i class="bi bi-plus-circle me-2"></i>Book a Slot</a>
  </div>
  <?php else: ?>
  <div class="row g-3">
    <?php foreach ($active as $b): ?>
    <div class="col-md-6 col-xl-4">
      <div class="booking-card-pro">
        <div class="booking-card-header">
          <div class="d-flex align-items-center gap-2">
            <div class="slot-big"><?= htmlspecialchars($b['slot_number']) ?></div>
            <div>
              <div style="font-size:.75rem;color:var(--text-muted)">Block <?= htmlspecialchars($b['block_name']) ?></div>
              <span class="status-badge badge-active">● Active</span>
            </div>
          </div>
          <div class="time-chip"><i class="bi bi-clock"></i><?= htmlspecialchars($b['duration']) ?></div>
        </div>
        <div class="booking-card-body">
          <div class="booking-meta-row">
            <span class="booking-meta-label"><i class="bi bi-car-front me-2"></i>Vehicle</span>
            <span class="booking-meta-value"><?= htmlspecialchars($b['vehicle_number']) ?></span>
          </div>
          <div class="booking-meta-row">
            <span class="booking-meta-label"><i class="bi bi-calendar me-2"></i>Booked On</span>
            <span class="booking-meta-value"><?= date('d M Y, h:i A',strtotime($b['booking_time'])) ?></span>
          </div>
          <div class="booking-meta-row">
            <span class="booking-meta-label"><i class="bi bi-hourglass-split me-2"></i>Valid Until</span>
            <span class="booking-meta-value" style="color:var(--brand-teal)"><?= date('d M Y, h:i A',strtotime($b['expiry_time'])) ?></span>
          </div>
          <div class="booking-meta-row">
            <span class="booking-meta-label"><i class="bi bi-currency-rupee me-2"></i>Paid</span>
            <span class="booking-meta-value" style="color:var(--slot-green);font-weight:700">₹<?= number_format($b['amount'],2) ?></span>
          </div>
          <?php if (!empty($b['razorpay_payment_id'])): ?>
          <div class="booking-meta-row">
            <span class="booking-meta-label"><i class="bi bi-receipt me-2"></i>Txn ID</span>
            <span class="booking-meta-value" style="font-size:.72rem;word-break:break-all"><?= htmlspecialchars($b['razorpay_payment_id']) ?></span>
          </div>
          <?php endif; ?>
          <div class="d-flex gap-2 mt-3">
            <a href="receipt.php?id=<?= $b['booking_id'] ?>" class="btn-outline-custom text-decoration-none py-1 px-3 flex-fill text-center" style="font-size:.82rem">
              <i class="bi bi-receipt me-1"></i>Receipt
            </a>
            <button onclick="confirmCancel(<?= $b['booking_id'] ?>)" class="flex-fill py-1 px-3"
              style="background:rgba(239,68,68,.1);color:#ef4444;border:1px solid rgba(239,68,68,.25);border-radius:8px;font-family:'Syne',sans-serif;font-weight:700;font-size:.82rem;cursor:pointer">
              <i class="bi bi-x-circle me-1"></i>Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- HISTORY -->
  <?php elseif ($tab==='history'): ?>
  <?php if (empty($history)): ?>
  <div class="empty-state">
    <div class="empty-state-icon"><i class="bi bi-calendar-x"></i></div>
    <h5 style="font-family:'Syne',sans-serif;font-weight:700">No Booking History</h5>
    <p class="text-muted">Your bookings will appear here.</p>
  </div>
  <?php else: ?>
  <div class="history-table-wrap">
    <div class="table-responsive">
      <table class="table table-dark-custom mb-0">
        <thead>
          <tr>
            <th class="ps-4">Slot</th><th>Vehicle</th><th>Duration</th>
            <th>Booked</th><th>Expiry</th><th>Amount</th><th>Status</th><th class="pe-4 text-end">Action</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($history as $b): ?>
          <tr>
            <td class="ps-4"><span style="font-family:'Syne',sans-serif;font-weight:800;font-size:1rem;color:var(--brand-accent)"><?= htmlspecialchars($b['slot_number']) ?></span><br><span style="font-size:.72rem;color:var(--text-muted)">Block <?= $b['block_name'] ?></span></td>
            <td><?= htmlspecialchars($b['vehicle_number']) ?></td>
            <td><?= htmlspecialchars($b['duration']) ?></td>
            <td style="font-size:.82rem"><?= date('d M Y',strtotime($b['booking_time'])) ?><br><span style="color:var(--text-muted)"><?= date('h:i A',strtotime($b['booking_time'])) ?></span></td>
            <td style="font-size:.82rem"><?= date('d M Y',strtotime($b['expiry_time'])) ?><br><span style="color:var(--text-muted)"><?= date('h:i A',strtotime($b['expiry_time'])) ?></span></td>
            <td style="color:var(--slot-green);font-weight:700">₹<?= number_format($b['amount'],2) ?></td>
            <td><span class="status-badge badge-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
            <td class="pe-4 text-end">
              <?php if ($b['status']==='active'): ?>
              <a href="receipt.php?id=<?= $b['booking_id'] ?>" style="color:var(--brand-teal)" title="Receipt"><i class="bi bi-receipt"></i></a>
              <a href="cancel_booking.php?id=<?= $b['booking_id'] ?>" onclick="return confirm('Cancel?')" style="color:var(--slot-red);margin-left:.6rem" title="Cancel"><i class="bi bi-x-circle"></i></a>
              <?php elseif ($b['status']!=='pending'): ?>
              <a href="receipt.php?id=<?= $b['booking_id'] ?>" style="color:var(--brand-teal)"><i class="bi bi-receipt"></i></a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- CANCELLED -->
  <?php elseif ($tab==='cancelled'): ?>
  <?php $cancelled = array_filter($history, fn($b) => $b['status']==='cancelled'); ?>
  <?php if (empty($cancelled)): ?>
  <div class="empty-state">
    <div class="empty-state-icon"><i class="bi bi-emoji-smile"></i></div>
    <h5 style="font-family:'Syne',sans-serif;font-weight:700">No Cancelled Bookings</h5>
    <p class="text-muted">Great! No cancellations.</p>
  </div>
  <?php else: ?>
  <div class="row g-3">
    <?php foreach ($cancelled as $b): ?>
    <div class="col-md-6">
      <div class="booking-card-pro" style="border-color:rgba(239,68,68,.15)">
        <div class="booking-card-header" style="background:rgba(239,68,68,.04)">
          <div class="d-flex align-items-center gap-2">
            <div class="slot-big" style="color:var(--slot-red)"><?= htmlspecialchars($b['slot_number']) ?></div>
            <div>
              <div style="font-size:.75rem;color:var(--text-muted)">Block <?= htmlspecialchars($b['block_name']) ?></div>
              <span class="status-badge badge-cancelled">Cancelled</span>
            </div>
          </div>
          <span style="font-size:.8rem;color:var(--text-muted)"><?= htmlspecialchars($b['duration']) ?></span>
        </div>
        <div class="booking-card-body">
          <div class="booking-meta-row"><span class="booking-meta-label"><i class="bi bi-car-front me-2"></i>Vehicle</span><span class="booking-meta-value"><?= htmlspecialchars($b['vehicle_number']) ?></span></div>
          <div class="booking-meta-row"><span class="booking-meta-label"><i class="bi bi-calendar me-2"></i>Booked</span><span class="booking-meta-value"><?= date('d M Y, h:i A',strtotime($b['booking_time'])) ?></span></div>
          <?php if ($b['cancelled_at']): ?>
          <div class="booking-meta-row"><span class="booking-meta-label"><i class="bi bi-x me-2"></i>Cancelled</span><span class="booking-meta-value" style="color:var(--slot-red)"><?= date('d M Y, h:i A',strtotime($b['cancelled_at'])) ?></span></div>
          <?php endif; ?>
          <div class="booking-meta-row"><span class="booking-meta-label"><i class="bi bi-currency-rupee me-2"></i>Amount</span><span class="booking-meta-value">₹<?= number_format($b['amount'],2) ?></span></div>
          <div class="booking-meta-row"><span class="booking-meta-label"><i class="bi bi-arrow-counterclockwise me-2"></i>Refund</span><span class="booking-meta-value" style="color:var(--slot-yellow)">Refund Pending</span></div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>

</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
