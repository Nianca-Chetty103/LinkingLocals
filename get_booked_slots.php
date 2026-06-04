<?php
session_start();
include 'database.php';

header('Content-Type: application/json');

$postId = intval($_GET['post_id'] ?? 0);

if ($postId <= 0) {
    echo json_encode(['success' => false, 'schedule' => []]);
    exit;
}

try {
    // Restrict status to 'approved' ONLY
    $stmt = $pdo->prepare("
        SELECT booking_date, booking_time 
        FROM bookings 
        WHERE post_id = ? AND status = 'approved'
        ORDER BY booking_date, booking_time
    ");
    $stmt->execute([$postId]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $schedule = [];
    foreach ($bookings as $booking) {
        $date = $booking['booking_date'];
        $schedule[$date][] = $booking['booking_time'];
    }

    echo json_encode(['success' => true, 'schedule' => $schedule]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>