<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdminLogin();

$pdo = getDB();

$payments = $pdo->query(
    'SELECT p.*, u.full_name, u.mobile_number,
            b.slot_number, b.duration, b.vehicle_number
     FROM payments p
     JOIN users u ON p.user_id = u.user_id
     LEFT JOIN bookings b ON p.booking_id = b.booking_id
     ORDER BY p.payment_date DESC'
)->fetchAll();

$totalRevenue = $pdo->query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status="paid"')->fetchColumn();
$totalPending = $pdo->query('SELECT COUNT(*) FROM payments WHERE payment_status="refund_pending"')->fetchColumn();

$adminPage = 'Payments';
include __DIR__ . '/includes/admin_header.php';
?>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="stat-card">
      <div class="stat-icon green"><i class="bi bi-currency-rupee"></i></div>
      <div><div class="stat-number">₹<?= number_format($totalRevenue, 2) ?></div><div class="stat-label">Total Revenue</div></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card">
      <div class="stat-icon orange"><i class="bi bi-arrow-counterclockwise"></i></div>
      <div><div class="stat-number"><?= $totalPending ?></div><div class="stat-label">Refunds Pending</div></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card">
      <div class="stat-icon teal"><i class="bi bi-receipt"></i></div>
      <div><div class="stat-number"><?= count($payments) ?></div><div class="stat-label">Total Transactions</div></div>
    </div>
  </div>
</div>

<div class="card-dark p-0 overflow-hidden">
  <div class="table-responsive">
    <table class="table table-dark-custom mb-0">
      <thead>
        <tr>
          <th class="ps-3">#</th>
          <th>Razorpay Order ID</th>
          <th>Payment ID</th>
          <th>User</th>
          <th>Mobile</th>
          <th>Slot</th>
          <th>Amount</th>
          <th>Date</th>
          <th class="pe-3">Status</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($payments as $p): ?>
        <tr>
          <td class="ps-3 text-muted small"><?= $p['payment_id'] ?></td>
          <td style="font-size:.75rem;word-break:break-all;max-width:140px"><?= htmlspecialchars($p['razorpay_order_id']) ?></td>
          <td style="font-size:.75rem;word-break:break-all;max-width:140px"><?= htmlspecialchars($p['razorpay_payment_id'] ?? '—') ?></td>
          <td><?= htmlspecialchars($p['full_name']) ?></td>
          <td><?= htmlspecialchars($p['mobile_number']) ?></td>
          <td><strong class="text-accent"><?= htmlspecialchars($p['slot_number'] ?? '—') ?></strong></td>
          <td>₹<?= number_format($p['amount'], 2) ?></td>
          <td style="font-size:.8rem"><?= date('d M Y H:i', strtotime($p['payment_date'])) ?></td>
          <td class="pe-3">
            <span class="status-badge <?php
              $ps = $p['payment_status'];
              echo $ps==='paid'?'badge-active':($ps==='refund_pending'?'badge-pending':($ps==='failed'?'badge-cancelled':'badge-pending'));
            ?>"><?= ucfirst(str_replace('_',' ',$ps)) ?></span>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
