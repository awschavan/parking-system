<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdminLogin();

$pdo = getDB();

$totalUsers    = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalBookings = $pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
$activeBookings= $pdo->query('SELECT COUNT(*) FROM bookings WHERE status="active"')->fetchColumn();
$cancelledBook = $pdo->query('SELECT COUNT(*) FROM bookings WHERE status="cancelled"')->fetchColumn();
$totalRevenue  = $pdo->query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status="paid"')->fetchColumn();
$availableSlots= $pdo->query('SELECT COUNT(*) FROM parking_slots WHERE status="available"')->fetchColumn();
$bookedSlots   = $pdo->query('SELECT COUNT(*) FROM parking_slots WHERE status="booked"')->fetchColumn();

$recentBookings = $pdo->query(
    'SELECT b.*, u.full_name, u.mobile_number FROM bookings b
     JOIN users u ON b.user_id = u.user_id
     ORDER BY b.created_at DESC LIMIT 8'
)->fetchAll();

$blockStats = $pdo->query(
    'SELECT block_name,
            SUM(status="available") AS avail,
            SUM(status="booked") AS booked,
            COUNT(*) AS total
     FROM parking_slots GROUP BY block_name ORDER BY block_name'
)->fetchAll();

$adminPage = 'Dashboard';
include __DIR__ . '/includes/admin_header.php';
?>

<!-- Stats Row -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon orange"><i class="bi bi-people"></i></div>
      <div><div class="stat-number"><?= $totalUsers ?></div><div class="stat-label">Users</div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon teal"><i class="bi bi-calendar-check"></i></div>
      <div><div class="stat-number"><?= $totalBookings ?></div><div class="stat-label">Total Bookings</div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon green"><i class="bi bi-car-front"></i></div>
      <div><div class="stat-number"><?= $activeBookings ?></div><div class="stat-label">Active Now</div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon orange"><i class="bi bi-currency-rupee"></i></div>
      <div><div class="stat-number">₹<?= number_format($totalRevenue) ?></div><div class="stat-label">Revenue</div></div>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- Block Overview -->
  <div class="col-lg-5">
    <div class="card-dark h-100">
      <div class="section-title" style="font-size:1rem"><i class="bi bi-grid-3x3 text-accent"></i> Block Availability</div>
      <?php foreach ($blockStats as $b): ?>
      <div class="mb-3">
        <div class="d-flex justify-content-between mb-1">
          <span style="font-family:'Syne',sans-serif;font-weight:700">Block <?= $b['block_name'] ?></span>
          <span class="text-muted small"><?= $b['avail'] ?>/<?= $b['total'] ?> available</span>
        </div>
        <?php $pct = $b['total'] > 0 ? ($b['booked'] / $b['total'] * 100) : 0; ?>
        <div class="progress" style="height:8px;background:rgba(255,255,255,.08);border-radius:999px">
          <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $pct>80?'var(--slot-red)':($pct>50?'var(--slot-yellow)':'var(--slot-green)') ?>;border-radius:999px"></div>
        </div>
      </div>
      <?php endforeach; ?>

      <hr class="divider">
      <div class="row text-center">
        <div class="col-6">
          <div style="font-size:1.5rem;font-weight:800;color:var(--slot-green)"><?= $availableSlots ?></div>
          <div class="text-muted small">Available</div>
        </div>
        <div class="col-6">
          <div style="font-size:1.5rem;font-weight:800;color:var(--slot-red)"><?= $bookedSlots ?></div>
          <div class="text-muted small">Booked</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Bookings -->
  <div class="col-lg-7">
    <div class="card-dark h-100 p-0 overflow-hidden">
      <div class="p-3 pb-0">
        <div class="section-title" style="font-size:1rem"><i class="bi bi-clock-history text-accent"></i> Recent Bookings</div>
      </div>
      <div class="table-responsive">
        <table class="table table-dark-custom mb-0">
          <thead>
            <tr>
              <th class="ps-3">Slot</th>
              <th>Name</th>
              <th>Mobile</th>
              <th>Duration</th>
              <th class="pe-3">Status</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($recentBookings as $b): ?>
            <tr>
              <td class="ps-3"><strong class="text-accent"><?= htmlspecialchars($b['slot_number']) ?></strong></td>
              <td><?= htmlspecialchars($b['full_name']) ?></td>
              <td><?= htmlspecialchars($b['mobile_number']) ?></td>
              <td><?= htmlspecialchars($b['duration']) ?></td>
              <td class="pe-3"><span class="status-badge badge-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
