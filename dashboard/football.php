<?php
include 'db_connection.php';
session_start();

// Handle post creation (including image upload)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_content'])) {
    $post_content = $_POST['post_content'];
    $user_id = $_SESSION['user_id']; // assuming the user is logged in

    // Handle image upload
    $image_path = null;
    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] == 0) {
        $image_name = $_FILES['post_image']['name'];
        $image_tmp = $_FILES['post_image']['tmp_name'];
        $image_ext = pathinfo($image_name, PATHINFO_EXTENSION);
        $image_path = 'uploads/' . uniqid() . '.' . $image_ext;

        move_uploaded_file($image_tmp, $image_path);
    }

    // Insert post into the database
    $stmt = $conn->prepare("INSERT INTO fpost (content, user_id, image_path) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $post_content, $user_id, $image_path);
    $stmt->execute();
}

$query = "SELECT fpost.content AS post_content, fpost.timestamp AS post_timestamp, fpost.id AS post_id, 
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
    <title>Football Forum</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #eef2f7;
            color: #1f2937;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        h1 {
            text-align: center;
            color: #1e3a8a;
            font-size: 30px;
            margin-bottom: 25px;
        }

        .post-form {
            background: #f0f4ff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .post-form textarea {
            width: 100%;
            padding: 12px;
            font-size: 15px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            resize: vertical;
            margin-bottom: 10px;
            box-sizing:border-box;
        }

        .post-form input[type="file"] {
            margin-bottom: 10px;
        }

        .post-form button {
            background-color: #7F00FF;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 6px;
            cursor: pointer;
        }

        .post-form button:hover {
            background-color: #2744a6;
        }

        .posts .post {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .post .username {
            color: #7F00FF;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 6px;
        }

        .post .content {
            font-size: 16px;
            margin-bottom: 10px;
        }

        .post .timestamp {
            font-size: 12px;
            color: #7F00FF;
        }

        .post img {
            max-width: 100%;
            height: auto;
            margin-top: 10px;
            border-radius: 6px;
        }

        .view-comments {
            display: inline-block;
            margin-top: 10px;
            color: #7F00FF;
            font-weight: 500;
            font-size: 14px;
            text-decoration: none;
        }

        .view-comments:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Football Discussion Forum</h1>

    <form method="POST" enctype="multipart/form-data" class="post-form">
        <textarea name="post_content" placeholder="Write your post..." required></textarea>
        <input type="file" name="post_image" accept="image/*">
        <button type="submit">Post</button>
    </form>

    <div class="posts">
        <?php foreach ($posts as $post): ?>
            <div class="post">
                <h2 class="username"><?php echo htmlspecialchars($post['username']); ?> - <?php echo htmlspecialchars($post['club']); ?></h2>
                <p class="content"><?php echo nl2br(htmlspecialchars($post['post_content'])); ?></p>
                <?php if ($post['image_path']): ?>
                    <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Post Image">
                <?php endif; ?>
                <span class="timestamp"><?php echo $post['post_timestamp']; ?></span>
                <a href="comments.php?post_id=<?php echo $post['post_id']; ?>" class="view-comments">
                    View Comments (<?php echo $post['comment_count']; ?>)
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
