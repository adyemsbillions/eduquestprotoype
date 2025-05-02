<?php
// like_post.php
session_start();
include("db_connection.php");  // Include your database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['post_id'])) {
        $post_id = $_POST['post_id'];
        $user_id = $_SESSION['user_id'];  // Assuming the user is logged in and their ID is stored in the session

        // Check if the user already liked the post
        $sql_check_like = "SELECT * FROM post_likes WHERE post_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql_check_like);
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // User hasn't liked the post yet, so insert a like
            $sql_insert_like = "INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql_insert_like);
            $stmt->bind_param("ii", $post_id, $user_id);
            $stmt->execute();
        }

        // Get the updated like count
        $sql_like_count = "SELECT COUNT(*) AS like_count FROM post_likes WHERE post_id = ?";
        $stmt = $conn->prepare($sql_like_count);
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        $result_like_count = $stmt->get_result();
        $like_count = $result_like_count->fetch_assoc()['like_count'];

        // Return the updated like count as JSON
        echo json_encode(['like_count' => $like_count]);
    }
}
?>
