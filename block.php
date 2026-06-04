<?php
session_start();
include 'database.php';

// Ensure only admins can block
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'admin') {
    die("Unauthorized");
}

if (isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
    
    // Update the user's status to 'blocked'
    $stmt = $conn->prepare("UPDATE users SET status = 'blocked' WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    echo "User blocked successfully.";
}
?>