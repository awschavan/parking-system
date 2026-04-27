-- ============================================================
-- Online Parking Management System - Database Schema
-- parking_db
-- ============================================================

CREATE DATABASE IF NOT EXISTS parking_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE parking_db;

-- ============================================================
-- TABLE: admin
-- ============================================================
CREATE TABLE IF NOT EXISTS admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO admin (username, password, email) VALUES
('admin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@parking.com');
-- Default admin password: password

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    mobile_number VARCHAR(15) NOT NULL UNIQUE,
    vehicle_type ENUM('Car','Bike') NOT NULL,
    vehicle_number VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: parking_slots
-- ============================================================
CREATE TABLE IF NOT EXISTS parking_slots (
    slot_id INT AUTO_INCREMENT PRIMARY KEY,
    block_name CHAR(1) NOT NULL,
    slot_number VARCHAR(10) NOT NULL UNIQUE,
    status ENUM('available','booked','maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_slot (block_name, slot_number)
) ENGINE=InnoDB;

-- Pre-insert all parking slots
INSERT INTO parking_slots (block_name, slot_number) VALUES
('A','A1'),('A','A2'),('A','A3'),('A','A4'),('A','A5'),
('A','A6'),('A','A7'),('A','A8'),('A','A9'),('A','A10'),
('B','B1'),('B','B2'),('B','B3'),('B','B4'),('B','B5'),
('B','B6'),('B','B7'),('B','B8'),('B','B9'),('B','B10'),
('D','D1'),('D','D2'),('D','D3'),('D','D4'),('D','D5'),
('D','D6'),('D','D7'),('D','D8'),('D','D9'),('D','D10'),
('E','E1'),('E','E2'),('E','E3'),('E','E4'),('E','E5'),
('E','E6'),('E','E7'),('E','E8'),('E','E9'),('E','E10'),
('F','F1'),('F','F2'),('F','F3'),('F','F4'),('F','F5'),
('F','F6'),('F','F7'),('F','F8'),('F','F9'),('F','F10'),
('G','G1'),('G','G2'),('G','G3'),('G','G4'),('G','G5'),
('G','G6'),('G','G7'),('G','G8'),('G','G9'),('G','G10');

-- ============================================================
-- TABLE: bookings
-- ============================================================
CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    slot_number VARCHAR(10) NOT NULL,
    block_name CHAR(1) NOT NULL,
    vehicle_number VARCHAR(20) NOT NULL,
    booking_time DATETIME NOT NULL,
    expiry_time DATETIME NOT NULL,
    duration ENUM('1 hour','1 day') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('active','cancelled','completed','pending') DEFAULT 'pending',
    cancelled_at DATETIME NULL,
    cancel_reason VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (slot_number) REFERENCES parking_slots(slot_number) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: payments
-- ============================================================
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    razorpay_payment_id VARCHAR(100) NULL,
    razorpay_order_id VARCHAR(100) NOT NULL,
    razorpay_signature VARCHAR(255) NULL,
    user_id INT NOT NULL,
    booking_id INT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'INR',
    payment_status ENUM('created','paid','failed','cancelled','refund_pending','refunded') DEFAULT 'created',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- INDEXES for performance
-- ============================================================
CREATE INDEX idx_bookings_user ON bookings(user_id);
CREATE INDEX idx_bookings_slot ON bookings(slot_number);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_payments_user ON payments(user_id);
CREATE INDEX idx_payments_booking ON payments(booking_id);
CREATE INDEX idx_slots_block ON parking_slots(block_name);
CREATE INDEX idx_slots_status ON parking_slots(status);
