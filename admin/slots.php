<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdminLogin();

$pdo = getDB();
$msg = '';
$err = '';

// Add slot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $block = strtoupper(trim($_POST['block_name'] ?? ''));
        $slot  = strtoupper(trim($_POST['slot_number'] ?? ''));
        if (!preg_match('/^[A-G]$/', $block) || empty($slot)) {
            $err = 'Invalid block or slot number.';
        } else {
            try {
                $pdo->prepare('INSERT INTO parking_slots (block_name, slot_number) VALUES (?,?)')->execute([$block,$slot]);
                $msg = "Slot $slot added successfully.";
            } catch (PDOException $e) {
                $err = 'Slot already exists or invalid data.';
            }
        }
    } elseif ($_POST['action'] === 'remove') {
        $slotId = (int)$_POST['slot_id'];
        // Check no active booking
        $check = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE slot_number=(SELECT slot_number FROM parking_slots WHERE slot_id=?) AND status="active"');
        $check->execute([$slotId]);
        if ($check->fetchColumn() > 0) {
            $err = 'Cannot remove slot with active booking.';
        } else {
            $pdo->prepare('DELETE FROM parking_slots WHERE slot_id=?')->execute([$slotId]);
            $msg = 'Slot removed.';
        }
    } elseif ($_POST['action'] === 'toggle_maintenance') {
        $slotId = (int)$_POST['slot_id'];
        $row = $pdo->prepare('SELECT status FROM parking_slots WHERE slot_id=?');
        $row->execute([$slotId]);
        $s = $row->fetch();
        if ($s && $s['status'] !== 'booked') {
            $newStatus = $s['status'] === 'maintenance' ? 'available' : 'maintenance';
            $pdo->prepare('UPDATE parking_slots SET status=? WHERE slot_id=?')->execute([$newStatus, $slotId]);
            $msg = "Slot status updated to $newStatus.";
        }
    }
}

$slots = $pdo->query('SELECT * FROM parking_slots ORDER BY block_name, slot_number')->fetchAll();

$adminPage = 'Slots';
include __DIR__ . '/includes/admin_header.php';
?>

<?php if ($msg): ?><div class="alert-custom alert-success-custom mb-3 auto-dismiss"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert-custom alert-danger-custom mb-3 auto-dismiss"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="row g-4">
  <!-- Add Slot -->
  <div class="col-md-4">
    <div class="card-dark">
      <h5 style="font-family:'Syne',sans-serif;font-weight:700;margin-bottom:1.5rem">
        <i class="bi bi-plus-circle me-2 text-accent"></i>Add New Slot
      </h5>
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="mb-3">
          <label class="form-label">Block (A,B,D,E,F,G)</label>
          <input type="text" name="block_name" class="form-control vehicle-input" maxlength="1" placeholder="A" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Slot Number (e.g. A11)</label>
          <input type="text" name="slot_number" class="form-control vehicle-input" placeholder="A11" required>
        </div>
        <button class="btn-primary-custom w-100">Add Slot</button>
      </form>
    </div>
  </div>

  <!-- Slots Table -->
  <div class="col-md-8">
    <div class="card-dark p-0 overflow-hidden">
      <div class="table-responsive">
        <table class="table table-dark-custom mb-0">
          <thead>
            <tr>
              <th class="ps-3">Slot</th>
              <th>Block</th>
              <th>Status</th>
              <th class="pe-3">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($slots as $s): ?>
            <tr>
              <td class="ps-3"><strong class="text-accent"><?= htmlspecialchars($s['slot_number']) ?></strong></td>
              <td>Block <?= htmlspecialchars($s['block_name']) ?></td>
              <td>
                <span class="status-badge <?= $s['status']==='available'?'badge-active':($s['status']==='booked'?'badge-cancelled':'badge-pending') ?>">
                  <?= ucfirst($s['status']) ?>
                </span>
              </td>
              <td class="pe-3">
                <?php if ($s['status'] !== 'booked'): ?>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="action" value="toggle_maintenance">
                  <input type="hidden" name="slot_id" value="<?= $s['slot_id'] ?>">
                  <button class="badge text-decoration-none border-0" style="background:rgba(234,179,8,.15);color:#eab308;padding:.35rem .7rem;border-radius:6px;cursor:pointer">
                    <?= $s['status']==='maintenance'?'Restore':'Maintenance' ?>
                  </button>
                </form>
                <form method="POST" class="d-inline ms-1">
                  <input type="hidden" name="action" value="remove">
                  <input type="hidden" name="slot_id" value="<?= $s['slot_id'] ?>">
                  <button onclick="return confirm('Remove slot <?= $s['slot_number'] ?>?')"
                          class="badge text-decoration-none border-0"
                          style="background:rgba(239,68,68,.15);color:#ef4444;padding:.35rem .7rem;border-radius:6px;cursor:pointer">
                    Remove
                  </button>
                </form>
                <?php else: ?>
                <span class="text-muted small">In use</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
