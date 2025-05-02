<?php
// Include the database connection
include("db_connection.php");

session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "User not logged in"]);
    exit();
}

$user_id = $_SESSION['user_id']; // Logged-in user ID

// Get the data from the AJAX request
$post_id = $_POST['post_id'];
$comment_text = trim($_POST['comment_text']); // Get the comment text

// Sanitize the comment text
$comment_text = htmlspecialchars($comment_text, ENT_QUOTES, 'UTF-8');

// Check if the comment already exists for this post and user
$sql_check_duplicate = "SELECT COUNT(*) AS duplicate_count FROM comments 
                        WHERE post_id = ? AND user_id = ? AND comment_text = ?";
$stmt_check = $conn->prepare($sql_check_duplicate);
$stmt_check->bind_param("iis", $post_id, $user_id, $comment_text); // Bind parameters
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$row = $result_check->fetch_assoc();

if ($row['duplicate_count'] == 0) {
    // Insert the new comment
    $sql_insert_comment = "INSERT INTO comments (post_id, user_id, comment_text, created_at)
                           VALUES (?, ?, ?, NOW())";
    $stmt_insert = $conn->prepare($sql_insert_comment);
    $stmt_insert->bind_param("iis", $post_id, $user_id, $comment_text); // Bind parameters
    $stmt_insert->execute();

    echo json_encode(["status" => "success", "message" => "Comment added successfully!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Duplicate comment!"]);
}
?>
