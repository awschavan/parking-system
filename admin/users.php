<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdminLogin();

$pdo = getDB();
$msg = '';

// Toggle active
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $uid  = (int)$_GET['toggle'];
    $curr = $pdo->prepare('SELECT is_active FROM users WHERE user_id=?');
    $curr->execute([$uid]);
    $row  = $curr->fetch();
    if ($row) {
        $pdo->prepare('UPDATE users SET is_active=? WHERE user_id=?')->execute([!$row['is_active'], $uid]);
        $msg = 'User status updated.';
    }
}

$search = trim($_GET['q'] ?? '');
$sql    = 'SELECT u.*, (SELECT COUNT(*) FROM bookings b WHERE b.user_id=u.user_id) AS total_bookings
           FROM users u WHERE 1=1';
$params = [];
if ($search) {
    $sql    .= ' AND (u.full_name LIKE ? OR u.mobile_number LIKE ? OR u.vehicle_number LIKE ?)';
    $s       = "%$search%";
    $params  = [$s,$s,$s];
}
$sql .= ' ORDER BY u.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$adminPage = 'Users';
include __DIR__ . '/includes/admin_header.php';
?>

<?php if ($msg): ?>
<div class="alert-custom alert-success-custom mb-3 auto-dismiss"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="d-flex justify-content-end mb-3">
  <form method="GET" class="d-flex gap-2">
    <input type="text" name="q" class="form-control" style="width:220px;font-size:.85rem"
           placeholder="Search name, mobile, vehicle..." value="<?= htmlspecialchars($search) ?>">
    <button class="btn-primary-custom py-1 px-3">Search</button>
  </form>
</div>

<div class="card-dark p-0 overflow-hidden">
  <div class="table-responsive">
    <table class="table table-dark-custom mb-0">
      <thead>
        <tr>
          <th class="ps-3">#</th>
          <th>Name</th>
          <th>Mobile</th>
          <th>Vehicle Type</th>
          <th>Vehicle Number</th>
          <th>Bookings</th>
          <th>Joined</th>
          <th class="pe-3">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td class="ps-3 text-muted small"><?= $u['user_id'] ?></td>
          <td><?= htmlspecialchars($u['full_name']) ?></td>
          <td><?= htmlspecialchars($u['mobile_number']) ?></td>
          <td><?= htmlspecialchars($u['vehicle_type']) ?></td>
          <td><strong><?= htmlspecialchars($u['vehicle_number']) ?></strong></td>
          <td><?= $u['total_bookings'] ?></td>
          <td style="font-size:.8rem"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
          <td class="pe-3">
            <a href="?toggle=<?= $u['user_id'] ?>&q=<?= urlencode($search) ?>"
               class="badge text-decoration-none"
               style="<?= $u['is_active']?'background:rgba(239,68,68,.15);color:#ef4444':'background:rgba(34,197,94,.15);color:#22c55e' ?>;padding:.35rem .7rem;border-radius:6px;font-size:.75rem"
               onclick="return confirm('Toggle user status?')">
              <?= $u['is_active'] ? 'Disable' : 'Enable' ?>
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="mt-2 text-muted small text-end"><?= count($users) ?> users</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
