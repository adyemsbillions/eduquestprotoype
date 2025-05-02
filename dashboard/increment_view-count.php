<?php
include 'db_connection.php'; // Include the database configuration file

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'])) {
    $post_id = $_POST['post_id'];

    // Update the view count for the post
    $sql = "UPDATE posts SET view_count = view_count + 1 WHERE post_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
}
?>
