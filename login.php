<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) { header('Location: ' . APP_URL . '/dashboard.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile   = trim($_POST['mobile_number'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($mobile) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $result = loginUser($mobile, $password);
        if ($result['success']) {
            $redirect = $_GET['redirect'] ?? APP_URL . '/dashboard.php';
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'Login';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-card fade-in-up">
    <div class="auth-logo">
      <div class="icon"><i class="bi bi-shield-lock-fill"></i></div>
      <h1>Welcome Back</h1>
      <p>Sign in to manage your parking</p>
    </div>

    <?php if ($error): ?>
    <div class="alert-custom alert-danger-custom mb-3 auto-dismiss"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['registered'])): ?>
    <div class="alert-custom alert-success-custom mb-3 auto-dismiss">Registration successful! Please log in.</div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="mb-3">
        <label class="form-label">Mobile Number</label>
        <input type="tel" name="mobile_number" class="form-control mobile-input"
               placeholder="9876543210" maxlength="10"
               value="<?= htmlspecialchars($_POST['mobile_number'] ?? '') ?>" required autofocus>
      </div>
      <div class="mb-4">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="••••••" required>
      </div>
      <button type="submit" class="btn-primary-custom w-100">Sign In</button>
    </form>

    <p class="text-center mt-3 mb-0" style="color:var(--text-muted);font-size:.9rem">
      New here? <a href="register.php">Create account</a>
    </p>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
