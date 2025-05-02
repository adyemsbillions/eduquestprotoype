<?php
// Include the database connection
include("db_connection.php");

// Start session to handle the logged-in user
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    exit();  // If the user is not logged in, do nothing and exit
}

$user_id = $_SESSION['user_id'];  // Logged-in user ID
$post_id = isset($_POST['post_id']) ? $_POST['post_id'] : null;

if ($post_id) {
    // Check if the user has already liked the post
    $sql_check_like = "SELECT COUNT(*) AS like_count FROM post_likes WHERE post_id = ? AND user_id = ?";
    $stmt_check_like = $conn->prepare($sql_check_like);
    $stmt_check_like->bind_param("ii", $post_id, $user_id);
    $stmt_check_like->execute();
    $result_check_like = $stmt_check_like->get_result();
    $like_exists = $result_check_like->fetch_assoc()['like_count'];

    // If the user hasn't liked the post yet, insert the like
    if ($like_exists == 0) {
        $sql_insert_like = "INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)";
        $stmt_insert_like = $conn->prepare($sql_insert_like);
        $stmt_insert_like->bind_param("ii", $post_id, $user_id);
        $stmt_insert_like->execute();
    }
}

// No need for output, AJAX will handle the updates on the front-end
exit();
?>
