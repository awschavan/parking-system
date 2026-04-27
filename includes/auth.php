<?php
// ============================================================
// includes/auth.php — Authentication Helpers
// ============================================================

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireAdminLogin(): void {
    if (!isAdminLoggedIn()) {
        header('Location: ' . APP_URL . '/admin/login.php');
        exit;
    }
}

function loginUser(string $mobile, string $password): array {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE mobile_number = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$mobile]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']       = $user['user_id'];
        $_SESSION['user_name']     = $user['full_name'];
        $_SESSION['vehicle_type']  = $user['vehicle_type'];
        $_SESSION['vehicle_number']= $user['vehicle_number'];
        $_SESSION['mobile']        = $user['mobile_number'];
        return ['success' => true, 'user' => $user];
    }
    return ['success' => false, 'message' => 'Invalid mobile number or password.'];
}

function registerUser(array $data): array {
    $pdo = getDB();

    // Check duplicate mobile
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE mobile_number = ?');
    $stmt->execute([$data['mobile_number']]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Mobile number already registered.'];
    }

    $hash = password_hash($data['password'], PASSWORD_BCRYPT);
    $stmt = $pdo->prepare(
        'INSERT INTO users (full_name, mobile_number, vehicle_type, vehicle_number, password)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $data['full_name'],
        $data['mobile_number'],
        $data['vehicle_type'],
        strtoupper($data['vehicle_number']),
        $hash
    ]);
    return ['success' => true, 'user_id' => $pdo->lastInsertId()];
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}
