<?php
// Include database connection
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

// Mark all notifications as read for the logged-in user when they access the page
$sql_mark_read = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
$stmt_mark_read = $conn->prepare($sql_mark_read);
$stmt_mark_read->bind_param("i", $user_id);
$stmt_mark_read->execute();

// Fetch the posts created by the logged-in user
$sql_posts = "SELECT post_id, user_id, post_content FROM posts WHERE user_id = ? ORDER BY created_at DESC";
$stmt_posts = $conn->prepare($sql_posts);
$stmt_posts->bind_param("i", $user_id); // Bind the logged-in user's ID to get their posts
$stmt_posts->execute();
$posts_result = $stmt_posts->get_result();

// If the user has no posts, display a message
if ($posts_result->num_rows === 0) {
    echo "You haven't posted anything yet.";
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Posts and Comments</title>
    <style>
    /* General Reset and Styling */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: Arial, sans-serif;
    }

    body {
        background-color: #f4f4f4;
        color: #333;
        line-height: 1.6;
        padding: 20px;
    }

    .container {
        max-width: 900px;
        margin: 0 auto;
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        padding: 20px;
    }

    h1 {
        font-size: 28px;
        color: #6a1b9a;
        margin-bottom: 20px;
        text-align: center;
    }

    h2 {
        font-size: 24px;
        color: #444;
        margin-bottom: 10px;
        margin-top: 20px;
    }

    /* Post Section */
    .post {
        background-color: #fff;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    .post p {
        font-size: 16px;
        color: #444;
    }

    /* Comments Section */
    .comment {
        background-color: #f9f9f9;
        padding: 10px;
        border-radius: 8px;
        margin-top: 10px;
        margin-bottom: 10px;
    }

    .comment p {
        font-size: 14px;
        color: #333;
    }

    .comment strong {
        color: #6a1b9a;
    }

    /* No Comments Message */
    .no-comments {
        font-style: italic;
        color: #777;
        font-size: 14px;
        text-align: center;
    }

    /* Button */
    button {
        padding: 12px 20px;
        background-color: #6a1b9a;
        color: #fff;
        font-size: 16px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        display: block;
        margin: 20px auto;
        width: 200px;
    }

    button:hover {
        background-color: #8e24aa;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .container {
            padding: 15px;
        }

        h1 {
            font-size: 24px;
        }

        h2 {
            font-size: 20px;
        }

        .post p,
        .comment p {
            font-size: 14px;
        }

        button {
            width: 100%;
        }
    }

    @media (max-width: 480px) {
        .container {
            padding: 10px;
        }

        h1 {
            font-size: 22px;
        }

        .post {
            padding: 10px;
        }

        button {
            padding: 10px;
        }
    }
</style>

</head>
<body>

    <div class="container">
        <h1>Your Posts and Comments</h1>

        <?php while ($post = $posts_result->fetch_assoc()): ?>
            <h2>Comments for Your Post</h2>
            
            <!-- Display the content of the post -->
            <div class="post">
                <p><strong>Post Content:</strong> <?php echo nl2br(htmlspecialchars($post['post_content'])); ?></p>
            </div>
            
            <?php
            // Fetch comments for this specific post
            $sql_comments = "SELECT c.comment_id, c.comment_text, u.username AS commenter_username
                             FROM comments c
                             JOIN users u ON c.user_id = u.id
                             WHERE c.post_id = ?
                             ORDER BY c.created_at DESC";
            $stmt_comments = $conn->prepare($sql_comments);
            $stmt_comments->bind_param("i", $post['post_id']); // Bind post_id to get comments for this post
            $stmt_comments->execute();
            $comments_result = $stmt_comments->get_result();
            ?>

            <?php if ($comments_result && $comments_result->num_rows > 0): ?>
                <?php while ($comment = $comments_result->fetch_assoc()): ?>
                    <div class="comment">
                        <p><strong><?php echo htmlspecialchars($comment['commenter_username']); ?></strong> commented:</p>
                        <p><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No comments for this post yet.</p>
            <?php endif; ?>

        <?php endwhile; ?>

    </div>

</body>
</html>
