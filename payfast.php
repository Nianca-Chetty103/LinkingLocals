<?php
require 'config.php';
include 'database.php';

/* read data from payfast post */
$pfData = $_POST;

/* checking the basic data */
if (!isset($pfData['payment_status']) || !isset($pfData['m_payment_id'])) {
    exit;
}

/* only successful payments */
if ($pfData['payment_status'] !== 'COMPLETE') {
    exit;
}

$bookingId = (int)$pfData['m_payment_id'];
$paymentRequestId = isset($pfData['custom_int1']) ? (int)$pfData['custom_int1'] : 0;
$pfPaymentId = trim($pfData['pf_payment_id'] ?? '');
$amountGross = trim($pfData['amount_gross'] ?? '');
$paymentStatus = trim($pfData['payment_status']);

try {
    $pdo->beginTransaction();

    /* Update booking status to paid */
    $updateStmt = $pdo->prepare("UPDATE bookings SET status = 'paid' WHERE id = ?");
    $updateStmt->execute([$bookingId]);

    if ($paymentRequestId) {
        $paymentStmt = $pdo->prepare("SELECT id FROM payments WHERE id = ? AND booking_id = ?");
        $paymentStmt->execute([$paymentRequestId, $bookingId]);
        $existingPayment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
    }

    if (empty($existingPayment)) {
        $paymentStmt = $pdo->prepare("SELECT id FROM payments WHERE booking_id = ? AND payment_status = 'pending' ORDER BY created_at DESC LIMIT 1");
        $paymentStmt->execute([$bookingId]);
        $existingPayment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
        if ($existingPayment) {
            $paymentRequestId = (int)$existingPayment['id'];
        }
    }

    if (!empty($existingPayment)) {
        $updatePaymentStmt = $pdo->prepare(
            "UPDATE payments SET pf_payment_id = ?, amount_gross = ?, payment_status = ?, created_at = NOW() WHERE id = ?"
        );
        $updatePaymentStmt->execute([
            $pfPaymentId,
            $amountGross,
            strtolower($paymentStatus),
            $paymentRequestId
        ]);
    } else {
        $insertStmt = $pdo->prepare(
            "INSERT INTO payments (booking_id, pf_payment_id, amount_gross, payment_status, created_at) VALUES (?, ?, ?, ?, NOW())"
        );
        $insertStmt->execute([
            $bookingId,
            $pfPaymentId,
            $amountGross,
            strtolower($paymentStatus)
        ]);
    }

    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    file_put_contents('payfast_log.txt', date('[Y-m-d H:i:s] ') . 'PayFast save error: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    exit;
}

/* OPTIONAL: log success */
file_put_contents("payfast_log.txt", json_encode($pfData) . PHP_EOL, FILE_APPEND);
?>