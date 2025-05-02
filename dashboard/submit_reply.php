<?php
include("db_connection.php");

// Start session to handle logged-in user
session_start();

// Assume user is logged in, and their user_id is stored in session
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Logged-in user ID

// Check if reply form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the form data
    $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : null;
    $comment_id = isset($_POST['comment_id']) ? $_POST['comment_id'] : null;
    $reply_text = isset($_POST['reply_text']) ? $_POST['reply_text'] : '';

    // Validate the post_id, comment_id, and reply_text
    if (!$post_id || !$comment_id || empty($reply_text)) {
        echo "Error: Missing required data.";
        exit();
    }

    // Insert the reply into the database
    $sql_insert_reply = "INSERT INTO replies (comment_id, post_id, user_id, reply_text, created_at) 
                         VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql_insert_reply);
    $stmt->bind_param("iiis", $comment_id, $post_id, $user_id, $reply_text);

    if ($stmt->execute()) {
        // After the reply is successfully added, we can insert a notification for the user
        $sql_notification = "INSERT INTO notifications (user_id, post_id, message, is_read, created_at) 
                             VALUES (?, ?, ?, 0, NOW())";
        $stmt_notification = $conn->prepare($sql_notification);
        $message = "New reply to your comment on post #$post_id";
        $stmt_notification->bind_param("iis", $user_id, $post_id, $message);
        $stmt_notification->execute();

        // Redirect back to the post page or comments page
        header("Location: post_details.php?post_id=$post_id");
        exit();
    } else {
        echo "Error: Unable to submit reply.";
    }
}
?>
