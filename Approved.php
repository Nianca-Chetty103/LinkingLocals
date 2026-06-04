<?php
include "database.php";
// Check if admin user is approved to use the website.
if (isset($_GET['id'])) {

    $id = intval($_GET['id']);

    $stmt = $conn->prepare("UPDATE Users SET status='approved' WHERE id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "approved";
    } else {
        echo "error";
    }
}
?>