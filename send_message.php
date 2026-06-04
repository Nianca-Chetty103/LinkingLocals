<?php
session_start();
include 'database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$senderId    = $_SESSION['user']['id'];
$receiverId  = intval($_POST['receiver_id'] ?? 0);
$postId      = intval($_POST['post_id'] ?? 0);
$message     = trim($_POST['message'] ?? '');

if (!$receiverId || !$message) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, post_id, message, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
$stmt->execute([$senderId, $receiverId, $postId ?: null, $message]);
$newId = $pdo->lastInsertId();

echo json_encode(['success' => true, 'id' => $newId]);