
<footer class="footer mt-auto">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-6 text-center text-md-start">
        <span class="fw-bold"><i class="bi bi-p-square-fill me-1"></i><?= APP_NAME ?></span>
        <span class="text-muted ms-2">Smart Parking Management</span>
      </div>
      <div class="col-md-6 text-center text-md-end mt-2 mt-md-0">
        <small class="text-muted">&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</small>
      </div>
    </div>
  </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Razorpay Checkout JS (loaded globally, used on payment page) -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<!-- Custom JS -->
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
