<?php
session_start();
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user'])) {
        die(json_encode(['status' => 'error', 'message' => 'Authentication required']));
    }

    $userId = $_SESSION['user']['id'];
    $postId = intval($_POST['post_id'] ?? 0);
    $rating = mountaineer_sanitize_rating($_POST['rating'] ?? 5);
    $review = trim($_POST['review'] ?? '');

    if ($postId > 0 && !empty($review)) {
        $stmt = $pdo->prepare("INSERT INTO reviews (user_id, post_id, rating, review, created_at) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt->execute([$userId, $postId, $rating, $review])) {
            header('Location: index.php?review=success');
            exit;
        }
    }
    header('Location: index.php?review=failed');
    exit;
}
// Rounds down the to the nearest rating between 1 - 5.
function mountaineer_sanitize_rating($val) {
    $int = intval($val);
    return ($int >= 1 && $int <= 5) ? $int : 5;
}