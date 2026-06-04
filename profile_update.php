<?php
//starts session and connects with db.
session_start();
include 'database.php';
//checks if the form was submitted thru post method.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user'])) { //check if user is logged in.
        die(json_encode(['status' => 'error', 'message' => 'Authentication required']));
    }

    $userId = $_SESSION['user']['id'];//check if session set.
    $name = trim($_POST['name'] ?? '');//retrieve info
    $email = trim($_POST['email'] ?? '');
    
    //checks if they both have values.
    if (!empty($name) && !empty($email)) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        if ($stmt->execute([$name, $email, $userId])) {
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
            //redirects user home page if successful.
            header('Location: index.php?update=success');
            exit;
        }
    }
    //otherwise takes them back and shows error message.
    header('Location: index.php?update=failed');
    exit;
}