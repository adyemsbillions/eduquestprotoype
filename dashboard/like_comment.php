<?php
// Database connection
include("db_connection.php");
// Handle like
if (isset($_POST['like_post'])) {
    $post_id = $_POST['post_id'];
    $user_id = 1; // This should come from the logged-in user's session
    
    // Check if the user has already liked this post
    $check_like = "SELECT * FROM post_likes WHERE post_id = ? AND user_id = ?";
    $stmt = $conn->prepare($check_like);
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $result_check = $stmt->get_result();
    
    if ($result_check->num_rows == 0) {
        // Insert the like into the post_likes table
        $sql_like = "INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql_like);
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
    }
}

// Handle comment submission
if (isset($_POST['comment_text'])) {
    $post_id = $_POST['post_id'];
    $user_id = 1; // This should come from the logged-in user's session
    $comment_text = $_POST['comment_text'];

    // Insert the comment into the database
    $sql_comment = "INSERT INTO comments (post_id, user_id, comment_text) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql_comment);
    $stmt->bind_param("iis", $post_id, $user_id, $comment_text);
    $stmt->execute();

    // Fetch the newly inserted comment
    $sql_new_comment = "SELECT c.comment_text, u.username, c.created_at 
                        FROM comments c
                        JOIN users u ON c.user_id = u.id
                        WHERE c.post_id = ? ORDER BY c.created_at DESC LIMIT 1";
    $stmt = $conn->prepare($sql_new_comment);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result_new_comment = $stmt->get_result();
    $new_comment = $result_new_comment->fetch_assoc();

    // Return the new comment in JSON format
    echo json_encode([
        'username' => $new_comment['username'],
        'comment_text' => $new_comment['comment_text'],
        'created_at' => date('F j, Y, g:i a', strtotime($new_comment['created_at']))
    ]);
}

$conn->close();
?>
