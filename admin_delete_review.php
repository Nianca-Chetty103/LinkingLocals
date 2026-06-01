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
    $reviewId = $_GET['id'] ?? null;

    if (!$reviewId) {
        echo json_encode(['success' => false, 'error' => 'Missing review ID']);
        exit;
    }

    try {
        $deleteReviewStmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $deleteReviewStmt->execute([$reviewId]);

        echo json_encode(['success' => true, 'message' => 'Review deleted successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
?>
