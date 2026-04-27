<?php
// admin/includes/admin_header.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdminLogin();
$adminPage = $adminPage ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($adminPage) ?> | <?= APP_NAME ?> Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
<style>
.admin-sidebar {
  width: 240px;
  min-height: 100vh;
  background: var(--brand-mid);
  border-right: 1px solid var(--border);
  padding: 1.5rem 1rem;
  position: fixed;
  top: 0; left: 0;
  overflow-y: auto;
  z-index: 100;
}
.admin-main { margin-left: 240px; padding: 2rem; min-height: 100vh; }
.admin-logo { font-family:'Syne',sans-serif; font-weight:800; font-size:1.2rem; color:var(--brand-accent); padding:.5rem .75rem 1.5rem; display:flex; align-items:center; gap:.5rem; }
.admin-nav-link {
  display: flex; align-items: center; gap: .6rem;
  padding: .55rem .75rem;
  border-radius: 8px;
  color: var(--text-muted);
  font-size: .9rem;
  font-weight: 500;
  text-decoration: none;
  transition: all .2s;
  margin-bottom: 2px;
}
.admin-nav-link:hover, .admin-nav-link.active {
  background: rgba(249,115,22,.12);
  color: var(--brand-accent);
}
.admin-nav-section { font-size: .7rem; text-transform:uppercase; letter-spacing:.8px; color:rgba(148,163,184,.5); padding: .8rem .75rem .3rem; }
@media(max-width:768px){
  .admin-sidebar{display:none;}
  .admin-main{margin-left:0;}
}
</style>
</head>
<body>

<div class="admin-sidebar">
  <div class="admin-logo"><i class="bi bi-p-square-fill"></i><?= APP_NAME ?> Admin</div>

  <div class="admin-nav-section">Overview</div>
  <a href="<?= APP_URL ?>/admin/dashboard.php"  class="admin-nav-link <?= ($adminPage==='Dashboard')?'active':'' ?>"><i class="bi bi-speedometer2"></i>Dashboard</a>

  <div class="admin-nav-section">Bookings</div>
  <a href="<?= APP_URL ?>/admin/bookings.php"    class="admin-nav-link <?= ($adminPage==='Bookings')?'active':'' ?>"><i class="bi bi-calendar-check"></i>All Bookings</a>
  <a href="<?= APP_URL ?>/admin/cancelled.php"   class="admin-nav-link <?= ($adminPage==='Cancelled')?'active':'' ?>"><i class="bi bi-x-circle"></i>Cancelled</a>
  <a href="<?= APP_URL ?>/admin/payments.php"    class="admin-nav-link <?= ($adminPage==='Payments')?'active':'' ?>"><i class="bi bi-credit-card"></i>Payments</a>

  <div class="admin-nav-section">Management</div>
  <a href="<?= APP_URL ?>/admin/users.php"       class="admin-nav-link <?= ($adminPage==='Users')?'active':'' ?>"><i class="bi bi-people"></i>Users</a>
  <a href="<?= APP_URL ?>/admin/slots.php"       class="admin-nav-link <?= ($adminPage==='Slots')?'active':'' ?>"><i class="bi bi-grid-3x3"></i>Parking Slots</a>

  <div class="admin-nav-section">Account</div>
  <a href="<?= APP_URL ?>/admin/logout.php"      class="admin-nav-link" style="color:var(--slot-red)"><i class="bi bi-box-arrow-right"></i>Logout</a>
</div>

<div class="admin-main">
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 style="font-family:'Syne',sans-serif;font-weight:700;margin:0"><?= htmlspecialchars($adminPage) ?></h2>
    <small class="text-muted"><?= date('l, d F Y') ?></small>
  </div>
  <div style="color:var(--text-muted);font-size:.85rem">
    <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?>
  </div>
</div>
