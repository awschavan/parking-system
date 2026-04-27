<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdminLogin();

$pdo    = getDB();
$filter = $_GET['status'] ?? 'all';
$search = trim($_GET['q'] ?? '');

$sql = 'SELECT b.*,u.full_name,u.mobile_number,u.vehicle_type,
               p.razorpay_payment_id,p.payment_status,p.amount AS paid_amount
        FROM bookings b
        JOIN users u ON b.user_id=u.user_id
        LEFT JOIN payments p ON b.booking_id=p.booking_id
        WHERE 1=1';
$params = [];

if ($filter !== 'all') { $sql .= ' AND b.status=?'; $params[] = $filter; }
if ($search) {
    $sql .= ' AND (u.full_name LIKE ? OR u.mobile_number LIKE ? OR b.vehicle_number LIKE ? OR b.slot_number LIKE ?)';
    $s = "%$search%";
    array_push($params,$s,$s,$s,$s);
}
$sql .= ' ORDER BY b.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Stats
$totalRev   = $pdo->query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status="paid"')->fetchColumn();
$todayBook  = $pdo->query('SELECT COUNT(*) FROM bookings WHERE DATE(booking_time)=CURDATE()')->fetchColumn();
$activeBook = $pdo->query('SELECT COUNT(*) FROM bookings WHERE status="active"')->fetchColumn();

$adminPage = 'Bookings';
include __DIR__ . '/includes/admin_header.php';
?>

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon orange"><i class="bi bi-calendar-check"></i></div>
      <div><div class="stat-number"><?= count($bookings) ?></div><div class="stat-label">Showing</div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon green"><i class="bi bi-car-front"></i></div>
      <div><div class="stat-number"><?= $activeBook ?></div><div class="stat-label">Active Now</div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon teal"><i class="bi bi-clock"></i></div>
      <div><div class="stat-number"><?= $todayBook ?></div><div class="stat-label">Today</div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon orange"><i class="bi bi-currency-rupee"></i></div>
      <div><div class="stat-number">₹<?= number_format($totalRev) ?></div><div class="stat-label">Revenue</div></div>
    </div>
  </div>
</div>

<!-- Filter bar -->
<div class="d-flex flex-wrap gap-3 align-items-center mb-4">
  <div class="d-flex gap-2 flex-wrap">
    <?php foreach(['all','active','completed','cancelled','pending'] as $s): ?>
    <a href="?status=<?= $s ?>&q=<?= urlencode($search) ?>"
       class="block-tab <?= $filter===$s?'active':'' ?>" style="font-size:.8rem;padding:.35rem .75rem">
      <?= ucfirst($s) ?>
    </a>
    <?php endforeach; ?>
  </div>
  <form method="GET" class="ms-auto d-flex gap-2">
    <input type="hidden" name="status" value="<?= htmlspecialchars($filter) ?>">
    <input type="text" name="q" class="form-control" style="width:220px;font-size:.85rem"
           placeholder="Name, mobile, slot..." value="<?= htmlspecialchars($search) ?>">
    <button class="btn-primary-custom py-1 px-3">Search</button>
  </form>
</div>

<div class="card-dark p-0 overflow-hidden">
  <div class="table-responsive">
    <table class="table table-dark-custom mb-0">
      <thead>
        <tr>
          <th class="ps-3">#</th>
          <th>Slot</th>
          <th>User</th>
          <th>Mobile</th>
          <th>Vehicle</th>
          <th>Date</th>
          <th>Time</th>
          <th>Duration</th>
          <th>Amount</th>
          <th>Razorpay ID</th>
          <th class="pe-3">Status</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($bookings)): ?>
        <tr><td colspan="11" class="text-center py-4 text-muted">No bookings found.</td></tr>
      <?php else: ?>
      <?php foreach ($bookings as $b): ?>
        <tr>
          <td class="ps-3 text-muted small"><?= $b['booking_id'] ?></td>
          <td><strong class="text-accent"><?= htmlspecialchars($b['slot_number']) ?></strong><br><small class="text-muted">Block <?= $b['block_name'] ?></small></td>
          <td><?= htmlspecialchars($b['full_name']) ?></td>
          <td><?= htmlspecialchars($b['mobile_number']) ?></td>
          <td><strong><?= htmlspecialchars($b['vehicle_number']) ?></strong><br><small class="text-muted"><?= $b['vehicle_type'] ?></small></td>
          <td style="font-size:.82rem"><?= date('d M Y',strtotime($b['booking_time'])) ?></td>
          <td style="font-size:.82rem">
            <?= date('h:i A',strtotime($b['booking_time'])) ?><br>
            <span class="text-muted">→ <?= date('h:i A',strtotime($b['expiry_time'])) ?></span>
          </td>
          <td><?= htmlspecialchars($b['duration']) ?></td>
          <td style="color:var(--slot-green);font-weight:700">₹<?= number_format($b['amount'],2) ?></td>
          <td style="font-size:.72rem;max-width:120px;word-break:break-all"><?= htmlspecialchars($b['razorpay_payment_id'] ?? '—') ?></td>
          <td class="pe-3"><span class="status-badge badge-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="mt-2 text-muted small text-end"><?= count($bookings) ?> records found</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
