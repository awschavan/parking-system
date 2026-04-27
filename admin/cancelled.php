<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdminLogin();

$pdo = getDB();
$bookings = $pdo->query(
    'SELECT b.*, u.full_name, u.mobile_number,
            p.razorpay_payment_id, p.payment_status
     FROM bookings b
     JOIN users u ON b.user_id = u.user_id
     LEFT JOIN payments p ON b.booking_id = p.booking_id
     WHERE b.status = "cancelled"
     ORDER BY b.cancelled_at DESC'
)->fetchAll();

$adminPage = 'Cancelled';
include __DIR__ . '/includes/admin_header.php';
?>

<div class="card-dark p-0 overflow-hidden">
  <div class="table-responsive">
    <table class="table table-dark-custom mb-0">
      <thead>
        <tr>
          <th class="ps-3">#ID</th>
          <th>Slot</th>
          <th>User</th>
          <th>Mobile</th>
          <th>Vehicle</th>
          <th>Booked On</th>
          <th>Cancelled At</th>
          <th>Reason</th>
          <th>Amount</th>
          <th class="pe-3">Payment Status</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($bookings)): ?>
        <tr><td colspan="10" class="text-center py-4 text-muted">No cancelled bookings.</td></tr>
      <?php else: ?>
      <?php foreach ($bookings as $b): ?>
        <tr>
          <td class="ps-3 text-muted small"><?= $b['booking_id'] ?></td>
          <td><strong class="text-accent"><?= htmlspecialchars($b['slot_number']) ?></strong></td>
          <td><?= htmlspecialchars($b['full_name']) ?></td>
          <td><?= htmlspecialchars($b['mobile_number']) ?></td>
          <td><?= htmlspecialchars($b['vehicle_number']) ?></td>
          <td style="font-size:.8rem"><?= date('d M Y H:i', strtotime($b['booking_time'])) ?></td>
          <td style="font-size:.8rem;color:var(--slot-red)"><?= $b['cancelled_at'] ? date('d M Y H:i', strtotime($b['cancelled_at'])) : '—' ?></td>
          <td style="font-size:.8rem"><?= htmlspecialchars($b['cancel_reason'] ?? '—') ?></td>
          <td>₹<?= number_format($b['amount'], 2) ?></td>
          <td class="pe-3">
            <span class="status-badge <?= $b['payment_status']==='refund_pending'?'badge-pending':'badge-cancelled' ?>">
              <?= ucfirst(str_replace('_',' ',$b['payment_status'] ?? 'n/a')) ?>
            </span>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="mt-2 text-muted small text-end"><?= count($bookings) ?> cancelled bookings</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
