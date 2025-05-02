<?php
include("db_connection.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Reset pending message count by marking all messages as 'seen'
$sql_reset = "UPDATE messages SET status = 'seen' WHERE receiver_id = ? AND status = 'pending'";
$stmt_reset = $conn->prepare($sql_reset);
$stmt_reset->bind_param("i", $user_id);
$stmt_reset->execute();
$stmt_reset->close();

// Handle post deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post_id'])) {
    $post_id_to_delete = $_POST['delete_post_id'];

    $sql_check_post = "SELECT user_id FROM posts WHERE post_id = ? AND user_id = ?";
    $stmt_check = $conn->prepare($sql_check_post);
    $stmt_check->bind_param("ii", $post_id_to_delete, $user_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        $sql_check_comments = "SELECT COUNT(*) as comment_count FROM comments WHERE post_id = ?";
        $stmt_comments = $conn->prepare($sql_check_comments);
        $stmt_comments->bind_param("i", $post_id_to_delete);
        $stmt_comments->execute();
        $comments_result = $stmt_comments->get_result();
        $comment_data = $comments_result->fetch_assoc();

        if ($comment_data['comment_count'] > 0) {
            echo "<script>alert('You cannot delete a post that has comments.');</script>";
        } else {
            $sql_delete_post = "DELETE FROM posts WHERE post_id = ?";
            $stmt_delete = $conn->prepare($sql_delete_post);
            $stmt_delete->bind_param("i", $post_id_to_delete);
            if ($stmt_delete->execute()) {
                echo "<script>alert('Post deleted successfully!'); window.location.href = 'your_posts_page.php';</script>";
            } else {
                echo "<script>alert('Error: Could not delete post. Please try again.');</script>";
            }
            $stmt_delete->close();
        }
        $stmt_comments->close();
    } else {
        echo "<script>alert('Error: Unauthorized or post not found.');</script>";
    }
    $stmt_check->close();
}

// Handle new post creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_content'])) {
    $post_content = trim($_POST['post_content']);
    if (!empty($post_content)) {
        $sql_insert = "INSERT INTO posts (user_id, post_content) VALUES (?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("is", $user_id, $post_content);
        if ($stmt_insert->execute()) {
            echo "<script>alert('Post created successfully!'); window.location.href = 'your_posts_page.php';</script>";
        } else {
            echo "<script>alert('Error: Could not create post.');</script>";
        }
        $stmt_insert->close();
    }
}

// Fetch pending message count for navbar (after reset, this should be 0 unless new messages arrive)
$sql_count = "SELECT COUNT(*) as pending_count FROM messages WHERE receiver_id = ? AND status = 'pending'";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param("i", $user_id);
$stmt_count->execute();
$stmt_count->bind_result($pending_count);
$stmt_count->fetch();
$stmt_count->close();

// Fetch user's posts
$sql_posts = "SELECT post_id, user_id, post_content, created_at FROM posts WHERE user_id = ? ORDER BY created_at DESC";
$stmt_posts = $conn->prepare($sql_posts);
$stmt_posts->bind_param("i", $user_id);
$stmt_posts->execute();
$posts_result = $stmt_posts->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Posts</title>
    <link rel="shortcut icon" href="images/singlelogo.png" type="image/x-icon">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #6a1b9a;
            --primary-dark: #4a148c;
            --secondary: #e3e3e3;
            --text: #333;
            --white: #fff;
            --light-bg: #f5f5f5;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: var(--light-bg);
            color: var(--text);
            line-height: 1.6;
            padding: 20px;
        }

        nav {
            background: var(--white);
            padding: 10px 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        nav ul {
            list-style: none;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 20px;
        }

        .nav-item {
            position: relative;
        }

        .nav-link {
            text-decoration: none;
            color: var(--text);
        }

        .badge {
            display: inline-block;
            padding: 0.2em 0.4em;
            font-size: 0.6em;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            border-radius: 0.5rem;
            position: absolute;
            top: -3px;
            right: -3px;
            color: #fff;
            background-color: #dc3545;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        h1 {
            font-size: 28px;
            color: var(--primary);
            margin-bottom: 20px;
            text-align: center;
        }

        .post-form {
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border-radius: 8px;
            border: 1px solid var(--secondary);
            margin-bottom: 15px;
            resize: vertical;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
        }

        .btn {
            padding: 12px 20px;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            transition: background 0.3s;
        }

        .btn:hover {
            background: var(--primary-dark);
        }

        .post {
            background: var(--white);
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .post-content {
            font-size: 16px;
            color: var(--text);
            margin-bottom: 10px;
        }

        .post-meta {
            font-size: 12px;
            color: #777;
            margin-bottom: 10px;
        }

        .delete-btn {
            padding: 8px 15px;
            background: #dc3545;
            color: var(--white);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .delete-btn:hover {
            background: #c82333;
        }

        .comments-section {
            margin-top: 15px;
        }

        .comment {
            background: var(--light-bg);
            padding: 10px;
            border-radius: 8px;
            margin: 10px 0;
        }

        .comment-meta {
            font-size: 12px;
            color: #777;
        }

        .comment-text {
            font-size: 14px;
            color: var(--text);
        }

        .no-content {
            text-align: center;
            color: #777;
            font-style: italic;
            margin: 20px 0;
        }

        @media (max-width: 768px) {
            .container, .post-form {
                padding: 15px;
            }

            h1 {
                font-size: 24px;
            }

            nav ul {
                justify-content: center;
                flex-wrap: wrap;
            }
        }

        @media (max-width: 480px) {
            .container, .post-form {
                padding: 10px;
            }

            h1 {
                font-size: 20px;
            }

            .btn, .delete-btn {
                padding: 8px 12px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
        <div class="back-button-container">
    <a href="dashboard.php" class="back-button" aria-label="Back to dashboard">←</a>
</div>

<style>
    .back-button-container {
        position: fixed;
        bottom: 30px;
        left: 30px;
        z-index: 1000;
    }

    .back-button {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: purple; /* Uses your --primary color: #6a1b9a */
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        text-decoration: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .back-button:hover {
        background: purple; /* Uses your --primary-dark: #4a148c */
        transform: scale(1.1);
    }
</style>
    <div class="container">
    <nav>
        <ul>
            <li class="nav-item">
                <a href="notifications.php" class="nav-link">
                    <div class="menu-user-image">
                        <img src="assets/images/icons/navbar/notification.png" alt="navbar icon" style="width: 24px;">
                        <?php if ($pending_count > 0): ?>
                            <span class="badge"><?php echo $pending_count; ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            </li>
            <!-- Add other nav items here -->
        </ul>
    </nav>

    <div class="container">
        <h1>Your Posts</h1>

        <!-- Post Creation Form -->
        <div class="post-form">
            <form action="" method="POST" enctype="multipart/form-data">
                <textarea name="post_content" rows="4" placeholder="Write a post..." class="form-control" required></textarea>
                <button type="submit" class="btn">Post</button>
            </form>
        </div>

        <?php if ($posts_result->num_rows === 0): ?>
            <p class="no-content">You haven’t created any posts yet.</p>
        <?php else: ?>
            <?php while ($post = $posts_result->fetch_assoc()): ?>
                <div class="post">
                    <div class="post-content"><?php echo nl2br(htmlspecialchars($post['post_content'])); ?></div>
                    <div class="post-meta">Posted on: <?php echo date('F j, Y, g:i a', strtotime($post['created_at'])); ?></div>

                    <form action="" method="POST" onsubmit="return confirm('Are you sure you want to delete this post?');">
                        <input type="hidden" name="delete_post_id" value="<?php echo $post['post_id']; ?>">
                        <button type="submit" class="delete-btn">Delete Post</button>
                    </form>

                    <?php
                    $sql_comments = "SELECT c.comment_id, c.comment_text, c.created_at, u.username AS commenter_username
                                     FROM comments c
                                     JOIN users u ON c.user_id = u.id
                                     WHERE c.post_id = ? ORDER BY c.created_at DESC";
                    $stmt_comments = $conn->prepare($sql_comments);
                    $stmt_comments->bind_param("i", $post['post_id']);
                    $stmt_comments->execute();
                    $comments_result = $stmt_comments->get_result();
                    ?>

                    <div class="comments-section">
                        <?php if ($comments_result->num_rows > 0): ?>
                            <?php while ($comment = $comments_result->fetch_assoc()): ?>
                                <div class="comment">
                                    <div class="comment-meta">
                                        <strong><?php echo htmlspecialchars($comment['commenter_username']); ?></strong>
                                        <span><?php echo date('F j, Y, g:i a', strtotime($comment['created_at'])); ?></span>
                                    </div>
                                    <div class="comment-text"><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="no-content">No comments yet.</p>
                        <?php endif; ?>
                        <?php $stmt_comments->close(); ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
        <?php $stmt_posts->close(); ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>