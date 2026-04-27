<?php
// includes/parking.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../config.php';

function getSlotsByBlock(string $block): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        'SELECT ps.slot_id, ps.slot_number, ps.status,
                b.booking_id, b.user_id, b.expiry_time, b.duration, b.vehicle_number AS booked_vehicle
         FROM parking_slots ps
         LEFT JOIN bookings b ON ps.slot_number = b.slot_number AND b.status = "active"
         WHERE ps.block_name = ?
         ORDER BY CAST(SUBSTRING(ps.slot_number, 2) AS UNSIGNED)'
    );
    $stmt->execute([$block]);
    return $stmt->fetchAll();
}

function getAllBlocksSummary(): array {
    $pdo  = getDB();
    $stmt = $pdo->query(
        'SELECT block_name,COUNT(*) AS total,
                SUM(status="available") AS available,
                SUM(status="booked") AS booked
         FROM parking_slots GROUP BY block_name ORDER BY block_name'
    );
    return $stmt->fetchAll();
}

function isSlotAvailable(string $slotNumber): bool {
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT status FROM parking_slots WHERE slot_number=? LIMIT 1');
    $stmt->execute([$slotNumber]);
    $row = $stmt->fetch();
    return $row && $row['status'] === 'available';
}

// Count active bookings TODAY for this user (daily limit = 5)
function getUserActiveBookingCount(int $userId): int {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM bookings
         WHERE user_id=? AND status="active"
         AND DATE(booking_time) = CURDATE()'
    );
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function createBooking(array $data): array {
    $pdo = getDB();

    // Check 5/day limit
    $count = getUserActiveBookingCount($data['user_id']);
    if ($count >= 5) {
        return ['success'=>false,'message'=>'Maximum 5 active bookings per day allowed. Limit resets tomorrow.'];
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT status FROM parking_slots WHERE slot_number=? FOR UPDATE');
        $stmt->execute([$data['slot_number']]);
        $slot = $stmt->fetch();
        if (!$slot || $slot['status'] !== 'available') {
            $pdo->rollBack();
            return ['success'=>false,'message'=>'Slot just got booked. Please choose another.'];
        }

        // Use booking_date + start_time if provided, else now
        $bookingDate   = $data['booking_date'] ?? date('Y-m-d');
        $startTime     = $data['start_time']   ?? date('H:i');
        $bookingTime   = $bookingDate . ' ' . $startTime . ':00';

        if ($data['duration'] === '1 hour') {
            $expiryTime = date('Y-m-d H:i:s', strtotime($bookingTime) + 3600);
        } else {
            $expiryTime = date('Y-m-d H:i:s', strtotime($bookingTime) + 86400);
        }
        $amount = ($data['duration'] === '1 hour') ? PRICE_HOURLY : PRICE_DAILY;

        $stmt = $pdo->prepare(
            'INSERT INTO bookings
             (user_id,slot_number,block_name,vehicle_number,booking_time,expiry_time,duration,amount,status)
             VALUES (?,?,?,?,?,?,?,?,"pending")'
        );
        $stmt->execute([
            $data['user_id'], $data['slot_number'], $data['block_name'],
            strtoupper($data['vehicle_number']), $bookingTime, $expiryTime,
            $data['duration'], $amount,
        ]);
        $bookingId = $pdo->lastInsertId();

        $pdo->prepare('UPDATE parking_slots SET status="booked" WHERE slot_number=?')
            ->execute([$data['slot_number']]);

        $pdo->commit();
        return ['success'=>true,'booking_id'=>$bookingId,'amount'=>$amount];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success'=>false,'message'=>$e->getMessage()];
    }
}

function confirmBooking(int $bookingId, string $orderId, string $paymentId, string $sig): bool {
    $pdo = getDB();
    $pdo->prepare('UPDATE bookings SET status="active" WHERE booking_id=?')->execute([$bookingId]);
    $pdo->prepare('UPDATE payments SET payment_status="paid",razorpay_payment_id=?,razorpay_signature=? WHERE razorpay_order_id=?')
        ->execute([$paymentId,$sig,$orderId]);
    return true;
}

function cancelBooking(int $bookingId, int $userId, string $reason=''): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        'SELECT b.*,ps.status AS slot_status FROM bookings b
         JOIN parking_slots ps ON b.slot_number=ps.slot_number
         WHERE b.booking_id=? AND b.user_id=? AND b.status="active" LIMIT 1'
    );
    $stmt->execute([$bookingId,$userId]);
    $booking = $stmt->fetch();
    if (!$booking) return ['success'=>false,'message'=>'Booking not found or already cancelled.'];

    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE bookings SET status="cancelled",cancelled_at=NOW(),cancel_reason=? WHERE booking_id=?')
            ->execute([$reason,$bookingId]);
        $pdo->prepare('UPDATE parking_slots SET status="available" WHERE slot_number=?')
            ->execute([$booking['slot_number']]);
        $pdo->prepare('UPDATE payments SET payment_status="refund_pending" WHERE booking_id=? AND payment_status="paid"')
            ->execute([$bookingId]);
        $pdo->commit();
        return ['success'=>true,'message'=>'Booking cancelled successfully.'];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success'=>false,'message'=>$e->getMessage()];
    }
}

function getUserActiveBookings(int $userId): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        'SELECT b.*,p.razorpay_payment_id,p.payment_status
         FROM bookings b LEFT JOIN payments p ON b.booking_id=p.booking_id
         WHERE b.user_id=? AND b.status="active" ORDER BY b.booking_time DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getUserBookingHistory(int $userId): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        'SELECT b.*,p.razorpay_payment_id,p.payment_status,p.amount AS paid_amount
         FROM bookings b LEFT JOIN payments p ON b.booking_id=p.booking_id
         WHERE b.user_id=? ORDER BY b.booking_time DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getBookingById(int $bookingId): ?array {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        'SELECT b.*,u.full_name,u.mobile_number,u.vehicle_type,
                p.razorpay_payment_id,p.razorpay_order_id,p.payment_status,p.amount AS paid_amount
         FROM bookings b JOIN users u ON b.user_id=u.user_id
         LEFT JOIN payments p ON b.booking_id=p.booking_id
         WHERE b.booking_id=? LIMIT 1'
    );
    $stmt->execute([$bookingId]);
    return $stmt->fetch() ?: null;
}
