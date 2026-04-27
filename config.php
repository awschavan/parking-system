<?php
// ============================================================
// config.php — Central Configuration
// ============================================================

// ---- Database -----------------------------------------------
define('DB_HOST', 'localhost');
define('DB_USER', 'parkuser');
define('DB_PASS', 'Park@1234');
define('DB_NAME', 'parking_db');
define('DB_CHARSET', 'utf8mb4');

// ---- Razorpay -----------------------------------------------
// Replace with your actual Razorpay credentials
define('RAZORPAY_KEY_ID',     'rzp_test_Si3RXcNl2jlxDK');
define('RAZORPAY_KEY_SECRET', 'azuCsYRpnCGfDsk3Ijrh6vtp');

// ---- Application --------------------------------------------
define('APP_NAME',   'ParkEase');
define('APP_URL',    'http://43.205.129.196/parking-system');       // EC2 Production
define('APP_VERSION','1.0.0');

// ---- Pricing (INR) ------------------------------------------
define('PRICE_HOURLY', 50);   // ₹50 per hour
define('PRICE_DAILY',  200);  // ₹200 per day

// ---- Session ------------------------------------------------
define('SESSION_LIFETIME', 3600); // 1 hour

// ---- Error Reporting (set to 0 in production) ---------------
ini_set('display_errors', 0);
error_reporting(0);

// ---- Timezone -----------------------------------------------
date_default_timezone_set('Asia/Kolkata');

// ---- Start Session ------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
