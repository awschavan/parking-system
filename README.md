# ParkEase — Online Parking Management System
## Complete Deployment Guide

---

## Project Structure

```
parking-system/
├── config.php                  ← Central config (DB, Razorpay keys, pricing)
├── index.php                   ← Homepage
├── register.php                ← User registration
├── login.php                   ← User login
├── dashboard.php               ← User dashboard (bookings, history, cancel)
├── book_slot.php               ← Visual slot selection grid
├── payment.php                 ← Razorpay order creation + checkout
├── payment_success.php         ← Payment verification handler
├── razorpay_checkout.php       ← Checkout bridge
├── receipt.php                 ← Printable booking receipt
├── cancel_booking.php          ← Booking cancellation handler
├── logout.php                  ← Session destroy + redirect
├── database.sql                ← Full DB schema + seed data
├── .htaccess                   ← Apache URL rules + security
├── includes/
│   ├── db.php                  ← PDO singleton connection
│   ├── auth.php                ← Login/register/session helpers
│   ├── parking.php             ← Slot + booking business logic
│   ├── razorpay.php            ← Razorpay API (cURL, no Composer)
│   ├── header.php              ← Shared HTML header + navbar
│   └── footer.php              ← Shared HTML footer + scripts
├── assets/
│   ├── css/style.css           ← Full custom stylesheet
│   └── js/app.js               ← Slot grid JS + helpers
└── admin/
    ├── login.php               ← Admin login
    ├── logout.php              ← Admin logout
    ├── dashboard.php           ← Overview + stats + block chart
    ├── bookings.php            ← All bookings (filter + search)
    ├── cancelled.php           ← Cancelled bookings list
    ├── payments.php            ← Payment transactions
    ├── users.php               ← User management (enable/disable)
    ├── slots.php               ← Add/remove/maintenance slots
    └── includes/
        ├── admin_header.php    ← Admin sidebar layout header
        └── admin_footer.php    ← Admin layout footer
```

---

## 1. Razorpay Setup (REQUIRED FIRST)

1. Sign up at https://dashboard.razorpay.com
2. Go to **Settings → API Keys → Generate Test Key**
3. Copy your **Key ID** and **Key Secret**
4. Open `config.php` and replace:

```php
define('RAZORPAY_KEY_ID',     'rzp_test_XXXXXXXXXXXXXXXX');  // ← paste here
define('RAZORPAY_KEY_SECRET', 'XXXXXXXXXXXXXXXXXXXXXXXX');   // ← paste here
```

5. For live payments, use `rzp_live_...` keys from Razorpay dashboard.

---

## 2. Local Setup (XAMPP / Windows)

### Prerequisites
- XAMPP installed (PHP 7.4+ / 8.x, Apache, MariaDB)

### Steps

```bash
# 1. Copy project folder to XAMPP web root
xcopy /E /I parking-system "C:\xampp\htdocs\parking-system"

# 2. Start XAMPP → Apache + MySQL

# 3. Open phpMyAdmin: http://localhost/phpmyadmin
#    Create database: parking_db
#    Import: parking-system/database.sql

# 4. Update config.php
#    DB_PASS = '' (XAMPP default has no password)
#    APP_URL  = 'http://localhost/parking-system'

# 5. Open browser
#    http://localhost/parking-system          ← User portal
#    http://localhost/parking-system/admin    ← Admin panel
```

**Admin credentials:** `admin` / `password`

---

## 3. EC2 Amazon Linux Deployment

### 3.1 — Launch EC2 Instance

- AMI: **Amazon Linux 2023** (or Amazon Linux 2)
- Instance type: t2.micro (free tier)
- Security Group — Inbound rules:
  - HTTP  port 80   — 0.0.0.0/0
  - HTTPS port 443  — 0.0.0.0/0
  - SSH   port 22   — Your IP only
- Download your `.pem` key file

### 3.2 — Connect via PowerShell (Windows)

```powershell
# Open PowerShell as Administrator

# Fix .pem file permissions (required on Windows)
icacls "C:\Users\YourName\Downloads\your-key.pem" /inheritance:r
icacls "C:\Users\YourName\Downloads\your-key.pem" /grant:r "$($env:USERNAME):(R)"

# Connect to EC2
ssh -i "C:\Users\YourName\Downloads\your-key.pem" ec2-user@YOUR_EC2_PUBLIC_IP
```

### 3.3 — Install LAMP Stack on EC2

```bash
# ---- Update system ----
sudo dnf update -y          # Amazon Linux 2023
# OR for Amazon Linux 2:
# sudo yum update -y

# ---- Install Apache ----
sudo dnf install -y httpd
sudo systemctl start httpd
sudo systemctl enable httpd

# ---- Install PHP 8.1 + extensions ----
sudo dnf install -y php php-mysqlnd php-pdo php-mbstring php-xml php-curl php-json

# Verify PHP
php -v

# ---- Install MariaDB ----
sudo dnf install -y mariadb105-server
sudo systemctl start mariadb
sudo systemctl enable mariadb

# ---- Secure MariaDB ----
sudo mysql_secure_installation
# Answer prompts:
#   Set root password:       YES → set a strong password
#   Remove anonymous users:  YES
#   Disallow remote root:    YES
#   Remove test database:    YES
#   Reload privilege tables: YES

# ---- Enable mod_rewrite ----
sudo sed -i 's/AllowOverride None/AllowOverride All/g' /etc/httpd/conf/httpd.conf
sudo systemctl restart httpd
```

### 3.4 — Upload Project via SCP (PowerShell)

```powershell
# On your LOCAL Windows PowerShell:

# Upload entire project folder (run from parent directory of parking-system)
scp -i "C:\Users\YourName\Downloads\your-key.pem" -r `
    "C:\path\to\parking-system" `
    ec2-user@YOUR_EC2_PUBLIC_IP:/home/ec2-user/

# SSH back in and move to web root
ssh -i "C:\Users\YourName\Downloads\your-key.pem" ec2-user@YOUR_EC2_PUBLIC_IP
```

### 3.5 — Configure on EC2

```bash
# ---- Move project to Apache web root ----
sudo cp -r /home/ec2-user/parking-system /var/www/html/parking-system

# ---- Set correct ownership ----
sudo chown -R apache:apache /var/www/html/parking-system
sudo chmod -R 755 /var/www/html/parking-system

# ---- Create and import database ----
sudo mysql -u root -p
```

```sql
CREATE DATABASE parking_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'parkuser'@'localhost' IDENTIFIED BY 'StrongPass@123';
GRANT ALL PRIVILEGES ON parking_db.* TO 'parkuser'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
# Import schema
sudo mysql -u root -p parking_db < /var/www/html/parking-system/database.sql

# Verify tables
sudo mysql -u parkuser -p parking_db -e "SHOW TABLES;"
```

### 3.6 — Update config.php for Production

```bash
sudo nano /var/www/html/parking-system/config.php
```

Change these values:
```php
define('DB_USER', 'parkuser');
define('DB_PASS', 'StrongPass@123');    // your DB password
define('APP_URL', 'http://YOUR_EC2_PUBLIC_IP/parking-system');

// Use live Razorpay keys for production:
define('RAZORPAY_KEY_ID',     'rzp_live_XXXXXXXXXXXXXXXX');
define('RAZORPAY_KEY_SECRET', 'XXXXXXXXXXXXXXXXXXXXXXXX');

// Disable error display in production:
ini_set('display_errors', 0);
```

### 3.7 — Restart Services & Verify

```bash
# Restart Apache
sudo systemctl restart httpd

# Check Apache status
sudo systemctl status httpd

# Check PHP loaded
php -m | grep -E "pdo|curl|mbstring|mysqlnd"

# Check MariaDB
sudo systemctl status mariadb

# Test from browser:
# http://YOUR_EC2_PUBLIC_IP/parking-system
# http://YOUR_EC2_PUBLIC_IP/parking-system/admin/login.php
```

---

## 4. SELinux Fix (if pages return 403)

Amazon Linux may have SELinux enforcing. Run:

```bash
# Check SELinux status
getenforce

# If "Enforcing", allow Apache to connect to DB + network:
sudo setsebool -P httpd_can_network_connect_db 1
sudo setsebool -P httpd_can_network_connect 1

# Fix file context
sudo restorecon -Rv /var/www/html/parking-system/

# Or temporarily set permissive (not recommended for prod)
sudo setenforce 0
```

---

## 5. Firewall Fix (if port 80 blocked on server)

```bash
# Check if firewalld is running
sudo systemctl status firewalld

# Allow HTTP
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

---

## 6. Updating Code After Deployment

```powershell
# Re-upload changed file via SCP (PowerShell)
scp -i "C:\path\your-key.pem" `
    "C:\path\to\parking-system\config.php" `
    ec2-user@YOUR_EC2_PUBLIC_IP:/home/ec2-user/

# SSH in and move
ssh -i "C:\path\your-key.pem" ec2-user@YOUR_EC2_PUBLIC_IP
sudo cp /home/ec2-user/config.php /var/www/html/parking-system/config.php
sudo chown apache:apache /var/www/html/parking-system/config.php
```

---

## 7. Application URLs

| URL | Description |
|-----|-------------|
| `http://HOST/parking-system/` | Homepage |
| `http://HOST/parking-system/register.php` | User Registration |
| `http://HOST/parking-system/login.php` | User Login |
| `http://HOST/parking-system/dashboard.php` | User Dashboard |
| `http://HOST/parking-system/book_slot.php` | Book a Slot |
| `http://HOST/parking-system/admin/login.php` | Admin Login |
| `http://HOST/parking-system/admin/dashboard.php` | Admin Dashboard |
| `http://HOST/parking-system/admin/bookings.php` | All Bookings |
| `http://HOST/parking-system/admin/users.php` | Manage Users |
| `http://HOST/parking-system/admin/slots.php` | Manage Slots |
| `http://HOST/parking-system/admin/payments.php` | Transactions |

Replace `HOST` with `localhost` or your EC2 public IP.

---

## 8. Default Credentials

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `password` |
| User | Register at `/register.php` | (your choice) |

**Change the admin password immediately in production:**
```sql
UPDATE admin SET password = '$2y$12$NEWHASHEDPASSWORD' WHERE username = 'admin';
```
Generate hash with: `php -r "echo password_hash('YourNewPassword', PASSWORD_BCRYPT);"`

---

## 9. Pricing Configuration

Edit `config.php`:
```php
define('PRICE_HOURLY', 50);   // ₹50 per hour
define('PRICE_DAILY',  200);  // ₹200 per day
```

---

## 10. Parking Blocks & Slots

| Block | Slots | Count |
|-------|-------|-------|
| A | A1–A10 | 10 |
| B | B1–B10 | 10 |
| D | D1–D10 | 10 |
| E | E1–E10 | 10 |
| F | F1–F10 | 10 |
| G | G1–G10 | 10 |
| **Total** | | **60** |

---

## 11. Troubleshooting

| Issue | Fix |
|-------|-----|
| Blank page | Check Apache error log: `sudo tail -f /var/log/httpd/error_log` |
| DB connection failed | Verify DB_USER/DB_PASS in config.php; check MariaDB is running |
| 403 Forbidden | Check file permissions + SELinux (section 4) |
| Razorpay popup doesn't open | Verify KEY_ID in config.php; check browser console |
| Payment signature mismatch | Verify KEY_SECRET is correct; check for extra spaces |
| Slots not showing | Re-import database.sql; check parking_slots table |
| mod_rewrite not working | Enable AllowOverride All in httpd.conf (section 3.3) |
