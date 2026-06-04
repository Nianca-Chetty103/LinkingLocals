<?php
session_start();
include 'database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }

    $userId = $_SESSION['user']['id'];
    $postId = $_POST['post_id'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? 'Other');
    $price = floatval($_POST['price'] ?? 0);
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!$postId || empty($title) || $price <= 0 || empty($location) || empty($description)) {
        echo json_encode(['success' => false, 'error' => 'All fields are required']);
        exit;
    }

    try {
        // Security check: Verify ownership
        $stmt = $pdo->prepare("SELECT user_id, image FROM posts WHERE id = ?");
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

        // Default to the old image path currently in the database
        $imagePath = $post['image'];

        // If a brand new file is uploaded, process it
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
                $imagePath = $dest_path; // Update with new path
            }
        }

        // Perform the SQL Update statement
        $updateStmt = $pdo->prepare("UPDATE posts SET title = ?, category = ?, price = ?, location = ?, description = ?, image = ? WHERE id = ?");
        $updateStmt->execute([$title, $category, $price, $location, $description, $imagePath, $postId]);

        echo json_encode([
            'success' => true, 
            'updated_post' => [
                'id' => $postId,
                'title' => $title,
                'category' => $category,
                'price' => $price,
                'location' => $location,
                'description' => $description,
                'image' => $imagePath
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}