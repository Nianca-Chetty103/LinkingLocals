<?php
session_start();
include 'database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId  = $_SESSION['user']['id'];
$postId  = intval($_POST['post_id'] ?? 0);
$rating  = intval($_POST['rating'] ?? 0);
$review  = trim($_POST['review'] ?? '');

if (!$postId || $rating < 1 || $rating > 5 || !$review) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

// Verify the user had a completed booking for this post
$bookingCheck = $pdo->prepare("SELECT id FROM bookings WHERE user_id = ? AND post_id = ? AND status = 'completed' LIMIT 1");
$bookingCheck->execute([$userId, $postId]);
if (!$bookingCheck->fetch()) {
    echo json_encode(['success' => false, 'error' => 'You can only review services you have completed']);
    exit;
}

// Check for duplicate review
$dupCheck = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND post_id = ?");
$dupCheck->execute([$userId, $postId]);
if ($dupCheck->fetch()) {
    echo json_encode(['success' => false, 'error' => 'You have already reviewed this service']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO reviews (user_id, post_id, rating, review, created_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->execute([$userId, $postId, $rating, $review]);
$newId = $pdo->lastInsertId();

echo json_encode(['success' => true, 'id' => $newId]);