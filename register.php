<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) { header('Location: ' . APP_URL . '/dashboard.php'); exit; }

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'full_name'      => trim($_POST['full_name']    ?? ''),
        'mobile_number'  => trim($_POST['mobile_number'] ?? ''),
        'vehicle_type'   => trim($_POST['vehicle_type']  ?? ''),
        'vehicle_number' => trim($_POST['vehicle_number'] ?? ''),
        'password'       => $_POST['password']           ?? '',
    ];
    $confirm = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($data['full_name'])) {
        $error = 'Full name is required.';
    } elseif (!preg_match('/^[6-9]\d{9}$/', $data['mobile_number'])) {
        $error = 'Enter a valid 10-digit Indian mobile number.';
    } elseif (!in_array($data['vehicle_type'], ['Car','Bike'])) {
        $error = 'Select a valid vehicle type.';
    } elseif (!preg_match('/^[A-Z]{2}\d{2}[A-Z]{1,2}\d{4}$/', strtoupper($data['vehicle_number']))) {
        $error = 'Enter a valid vehicle number (e.g. MH12AB1234).';
    } elseif (strlen($data['password']) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($data['password'] !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $result = registerUser($data);
        if ($result['success']) {
            $success = 'Registration successful! You can now <a href="login.php">login</a>.';
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'Register';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-card fade-in-up">
    <div class="auth-logo">
      <div class="icon"><i class="bi bi-person-plus-fill"></i></div>
      <h1>Create Account</h1>
      <p>Join ParkEase — book your first slot in minutes</p>
    </div>

    <?php if ($error): ?>
    <div class="alert-custom alert-danger-custom mb-3 auto-dismiss"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert-custom alert-success-custom mb-3"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="mb-3">
        <label class="form-label">Full Name *</label>
        <input type="text" name="full_name" class="form-control" placeholder="Rahul Sharma"
               value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Mobile Number *</label>
        <input type="tel" name="mobile_number" class="form-control mobile-input" placeholder="9876543210"
               value="<?= htmlspecialchars($_POST['mobile_number'] ?? '') ?>" maxlength="10" required>
      </div>
      <div class="row mb-3">
        <div class="col-6">
          <label class="form-label">Vehicle Type *</label>
          <select name="vehicle_type" class="form-select" required>
            <option value="">Select</option>
            <option value="Car"  <?= (($_POST['vehicle_type'] ?? '') === 'Car')  ? 'selected' : '' ?>>🚗 Car</option>
            <option value="Bike" <?= (($_POST['vehicle_type'] ?? '') === 'Bike') ? 'selected' : '' ?>>🏍️ Bike</option>
          </select>
        </div>
        <div class="col-6">
          <label class="form-label">Vehicle Number *</label>
          <input type="text" name="vehicle_number" class="form-control vehicle-input" placeholder="MH12AB1234"
                 value="<?= htmlspecialchars(strtoupper($_POST['vehicle_number'] ?? '')) ?>" required>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Password *</label>
        <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
      </div>
      <div class="mb-4">
        <label class="form-label">Confirm Password *</label>
        <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
      </div>
      <button type="submit" class="btn-primary-custom w-100">Create Account</button>
    </form>

    <p class="text-center mt-3 mb-0" style="color:var(--text-muted);font-size:.9rem">
      Already have an account? <a href="login.php">Sign in</a>
    </p>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
