<?php
session_start();
include 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$currentUser = $_SESSION['user'];
$bookingId = intval($_POST['booking_id'] ?? 0);
$status = trim(strval($_POST['status'] ?? ''));
$hours = floatval($_POST['hours'] ?? 0);

if (!$bookingId || !in_array($status, ['approved', 'cancelled', 'completed'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid status parameters']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT b.*, p.title, p.price, p.user_id as provider_id FROM bookings b JOIN posts p ON b.post_id = p.id WHERE b.id = ?"
    );
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking || (int)$booking['provider_id'] !== (int)$currentUser['id']) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized or invalid booking']);
        exit;
    }

    $pdo->beginTransaction();
    $updateStmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $updateStmt->execute([$status, $bookingId]);

    $paymentLink = null;
    if ($status === 'completed' && $hours > 0) {
        $paymentLink = "finalpay.php?booking_id=" . $bookingId . "&hours=" . urlencode($hours);
    } elseif ($status === 'completed') {
        $paymentLink = "finalpay.php?booking_id=" . $bookingId;
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'status' => $status, 'payment_link' => $paymentLink]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>