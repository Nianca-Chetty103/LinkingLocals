<?php
session_start();
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user'])) {
        die(json_encode(['status' => 'error', 'message' => 'Authentication required']));
    }

    $senderId = $_SESSION['user']['id'];
    $receiverId = intval($_POST['receiver_id'] ?? 0);
    $postId = intval($_POST['post_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if ($receiverId > 0 && $postId > 0 && !empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages_db (sender_id, receiver_id, post_id, message, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
        if ($stmt->execute([$senderId, $receiverId, $postId, $message])) {
            header("Location: index.php?chat=success&post_id=$postId&receiver_id=$receiverId");
            exit;
        }
    }
    header('Location: index.php?chat=failed');
    exit;
}