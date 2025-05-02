<?php
// Include the database connection
include("db_connection.php");

// Get the post_id from the AJAX request
$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;

// Check if post_id is valid
if ($post_id <= 0) {
    echo "Invalid post ID.";
    exit();
}

// Fetch the comments for this post
$sql_comments = "SELECT c.comment_text, u.username, c.created_at, c.comment_id 
                 FROM comments c
                 JOIN users u ON c.user_id = u.id
                 WHERE c.post_id = ? ORDER BY c.created_at ASC";
$stmt = $conn->prepare($sql_comments);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result_comments = $stmt->get_result();

// Check if there are comments
if ($result_comments->num_rows > 0) {
    while ($comment = $result_comments->fetch_assoc()) {
        echo "<div class='comment'>";
        echo "<strong>" . htmlspecialchars($comment['username']) . "</strong>: ";
        echo "<p>" . nl2br(htmlspecialchars($comment['comment_text'])) . "</p>";
        echo "<small>Posted on " . date('F j, Y, g:i a', strtotime($comment['created_at'])) . "</small>";
        echo "</div>";
    }
} else {
    echo "No comments yet.";
}
?>
