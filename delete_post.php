<?php
session_start();
include 'database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') { //Security check - ensure cant be accessed thru URL
    if (!isset($_SESSION['user'])) {
        //Return error if no session exists
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }

    $userId = $_SESSION['user']['id'];
    $postId = $_POST['post_id'] ?? null;

    if (!$postId) { //checks if post exists
        echo json_encode(['success' => false, 'error' => 'Missing post identifier']);
        exit;
    }

    try {
        // Security check- Make sure this post belongs to the logged-in user.
        $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            echo json_encode(['success' => false, 'error' => 'Listing not found']);
            exit;
        }

        if ((int)$post['user_id'] !== (int)$userId) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized operation']);
            exit;
        }

        // Delete the post (ON DELETE CASCADE on your foreign keys will automatically clean up related bookings/messages if configured)
        $deleteStmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $deleteStmt->execute([$postId]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}