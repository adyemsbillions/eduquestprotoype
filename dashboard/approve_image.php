<?php
include 'db_connection.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Update the status to approved (1)
    $stmt = $conn->prepare("UPDATE wcw_images SET approved = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    echo "Image approved!";
    header("Location: admin_approve.php"); // Redirect back to approval page
}
?>
