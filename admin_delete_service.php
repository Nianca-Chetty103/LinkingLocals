<?php
session_start();
include 'database.php';

header('Content-Type: application/json');

// Verify admin role
function normalizeRole($role) {
    return strtolower(trim((string) $role));
}

function isAdminRole($role) {
    $normalized = normalizeRole($role);
    return $normalized === 'admin' || strpos($normalized, 'admin') !== false;
}

$currentRole = '';
if (!empty($_SESSION['role'])) {
    $currentRole = normalizeRole($_SESSION['role']);
} elseif (!empty($_SESSION['user']['role'])) {
    $currentRole = normalizeRole($_SESSION['user']['role']);
}

if (!isAdminRole($currentRole)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $serviceId = $_GET['id'] ?? null;

    if (!$serviceId) {
        echo json_encode(['success' => false, 'error' => 'Missing service ID']);
        exit;
    }

    try {
        // Delete related reviews first
        $deleteReviewsStmt = $pdo->prepare("DELETE FROM reviews WHERE post_id = ?");
        $deleteReviewsStmt->execute([$serviceId]);

        // Delete related bookings
        $deleteBookingsStmt = $pdo->prepare("DELETE FROM bookings WHERE post_id = ?");
        $deleteBookingsStmt->execute([$serviceId]);

        // Delete the service
        $deleteServiceStmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $deleteServiceStmt->execute([$serviceId]);

        echo json_encode(['success' => true, 'message' => 'Service deleted successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
?>
