<?php
session_start();
include 'database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user'])) {
        echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
        exit;
    }

    $userId = $_SESSION['user']['id']; //Retrieving data from post form
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? 'Other');
    $price = floatval($_POST['price'] ?? 0);
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    $imagePath = '';

    // Handle physical file uploads
    if (isset($_FILES['service_image']) && $_FILES['service_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['service_image']['tmp_name'];
        $fileName = $_FILES['service_image']['name'];
        
        $cleanFileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.\-_]/", "", $fileName);
        
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $dest_path = $uploadDir . $cleanFileName;
        
        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            $imagePath = $dest_path; 
        }
    }
   //checks if any fields are empty.
    if (!empty($title) && $price > 0 && !empty($location) && !empty($description)) {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, description, price, image, category, location, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        if ($stmt->execute([$userId, $title, $description, $price, $imagePath, $category, $location])) {
            echo json_encode(['status' => 'success', 'message' => 'Post created successfully']);
            exit;
        }
    }
    
    echo json_encode(['status' => 'error', 'message' => 'Invalid fields provided']);
    exit;
}