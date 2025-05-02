<?php
include 'db_connection.php';
session_start();

// Check if the user is logged in and has the necessary permissions
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle post deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_post'])) {
    $post_id = $_POST['post_id'];

    // Step 1: Delete replies associated with the comments of the post
    $stmt = $conn->prepare("DELETE freplies FROM freplies 
                            JOIN fcomment ON freplies.comment_id = fcomment.id 
                            WHERE fcomment.post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();

    // Step 2: Delete comments associated with the post
    $stmt = $conn->prepare("DELETE FROM fcomment WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();

    // Step 3: Delete the post itself
    $stmt = $conn->prepare("DELETE FROM fpost WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();

    // Redirect back to the delete page to refresh the list
    header("Location: delete_fpost.php");
    exit();
}

// Query to fetch all posts
$query = "SELECT fpost.id AS post_id, fpost.content AS post_content, fpost.timestamp AS post_timestamp, 
                 fpost.image_path, users.username, users.club,
                 (SELECT COUNT(*) FROM fcomment WHERE post_id = fpost.id) AS comment_count
          FROM fpost 
          JOIN users ON fpost.user_id = users.id 
          ORDER BY fpost.timestamp DESC";
$result = $conn->query($query);

$posts = [];
if ($result) {
    while ($post = $result->fetch_assoc()) {
        $posts[] = $post;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Posts</title>
  <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }

        .container {
            max-width: 900px;
            width: 100%;
            margin: 20px auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
            height: calc(100vh - 60px);
        }

        h1 {
            text-align: center;
            color: #333;
            font-size: 28px;
            margin-bottom: 20px;
        }

        .post {
            margin-bottom: 20px;
            padding: 15px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .post h2 {
            margin: 0;
            font-size: 20px;
            color: #0078d4;
        }

        .post .username {
            font-weight: bold;
            font-size: 18px;
        }

        .post .timestamp {
            font-size: 0.85em;
            color: #777;
        }

        .post .content {
            margin-top: 10px;
            font-size: 16px;
            line-height: 1.5;
        }

        .post img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 10px;
        }

        .comment-count {
            color: #777;
            font-size: 0.85em;
            margin-top: 5px;
            text-align: right;
        }

        .delete-button {
            background-color: #ff4d4d;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .delete-button:hover {
            background-color: #cc0000;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Delete Posts</h1>

    <!-- Displaying posts with delete option -->
    <div class="posts">
        <?php foreach ($posts as $post): ?>
            <div class="post">
                <h2 class="username"><?php echo htmlspecialchars($post['username']); ?> - <?php echo htmlspecialchars($post['club']); ?></h2>
                <p class="content"><?php echo nl2br(htmlspecialchars($post['post_content'])); ?></p>

                <!-- Display image if available -->
                <?php if ($post['image_path']): ?>
                    <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Post Image" style="max-width: 100%; height: auto;">
                <?php endif; ?>

                <span class="timestamp"><?php echo $post['post_timestamp']; ?></span>

                <!-- Comment Count -->
                <span class="comment-count">Comments: <?php echo $post['comment_count']; ?></span>

                <!-- Delete Button -->
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                    <button type="submit" name="delete_post" class="delete-button">Delete Post</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>