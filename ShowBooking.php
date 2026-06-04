<?php
include 'database.php';

header('Content-Type: application/json');

$postId = $_GET['post_id'] ?? null;

if (!$postId) {
    echo json_encode(['error' => 'Missing post reference']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT booking_date, booking_time 
        FROM bookings 
        WHERE post_id = ? AND status = 'approved'
    ");
    $stmt->execute([$postId]);
    $takenSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $schedule = [];
    foreach ($takenSlots as $slot) {
        $date = $slot['booking_date'];
        $time = date('H:i', strtotime($slot['booking_time']));
        
        if (!isset($schedule[$date])) {
            $schedule[$date] = [];
        }
        $schedule[$date][] = $time;
    }

    echo json_encode(['success' => true, 'schedule' => $schedule]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}