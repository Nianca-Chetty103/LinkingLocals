<?php
session_start();
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user'])) die('Authentication required');

    $userId = $_SESSION['user']['id'];
    $postId = intval($_POST['post_id'] ?? 0);
    
    // Fetch price from DB to prevent tampering
    $stmt = $pdo->prepare("SELECT price FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $service = $stmt->fetch();
    $amount = $service['price'];

    // 1. Insert booking as 'pending'
    $stmt = $pdo->prepare("INSERT INTO bookings (user_id, post_id, booking_date, booking_time, address, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->execute([$userId, $postId, $_POST['booking_date'], $_POST['booking_time'], trim($_POST['address'])]);
    $bookingId = $pdo->lastInsertId();

    header('Location: index.php?booking=submitted');
    exit;
}
