<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You need to log in first.");
}

$groupId = $_GET['id']; // Get the group ID from the URL
$userId = $_SESSION['user_id']; // Get the logged-in user ID

// Database connection
$conn = new mysqli('localhost', 'root', '', 'unimaidconnect');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch group information
$sqlGroup = "SELECT id, name, description, can_post FROM groups WHERE id = '$groupId'";
$groupResult = $conn->query($sqlGroup);
$group = $groupResult->fetch_assoc();

// Fetch the number of members in the group
$sqlMembers = "SELECT COUNT(*) AS member_count FROM group_members WHERE group_id = '$groupId'";
$membersResult = $conn->query($sqlMembers);
$members = $membersResult->fetch_assoc();

// Fetch posts in the group
$sqlPosts = "SELECT p.id, p.post_text, p.image_url, p.created_at, u.username
             FROM group_posts p
             JOIN users u ON p.user_id = u.id
             WHERE p.group_id = '$groupId'
             ORDER BY p.created_at DESC";
$postsResult = $conn->query($sqlPosts);

// Handle post submission (if posting is enabled)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $group['can_post'] == 1) {
    $postText = $_POST['post_text'];

    // Handle image upload
    $imageUrl = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $uploadDir = 'uploads/';
        $imageUrl = $uploadDir . uniqid() . '-' . basename($fileName);

        if (move_uploaded_file($fileTmpPath, $imageUrl)) {
            // Image uploaded successfully
        } else {
            echo "Error uploading the image.";
        }
    }

    // Insert post into the database
    $sqlInsertPost = "INSERT INTO group_posts (group_id, user_id, post_text, image_url)
                      VALUES ('$groupId', '$userId', '$postText', '$imageUrl')";

    if ($conn->query($sqlInsertPost) === TRUE) {
        echo "Post submitted successfully!";
        header("Location: group.php?id=" . $groupId);
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group: <?php echo htmlspecialchars($group['name']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
        }
        h1 {
            font-size: 24px;
            color: #333;
        }
        .group-info {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .group-info p {
            font-size: 16px;
            margin: 10px 0;
        }
        .group-info .members-count {
            font-weight: bold;
            font-size: 18px;
        }
        .post-form textarea {
            width: 100%;
            height: 100px;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .post-form input[type="file"] {
            margin-bottom: 10px;
        }
        .post-form input[type="submit"] {
            background-color: purple;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .post-form input[type="submit"]:hover {
            background-color: purple;
        }
        .post {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            background-color: #fff;
        }
        .post img {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
        }
        .post p {
            font-size: 14px;
            color: #333;
        }
        .post .username {
            font-weight: bold;
            color: purple;
        }
        .post .created-at {
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="group-info">
            <h1>Group: <?php echo htmlspecialchars($group['name']); ?></h1>
            <p><?php echo nl2br(htmlspecialchars($group['description'])); ?></p>
            <p class="members-count">Members: <?php echo $members['member_count']; ?></p>

            <?php if ($group['can_post'] == 0): ?>
                <p><strong>Posting is currently disabled in this group.</strong></p>
            <?php endif; ?>
        </div>

        <?php if ($group['can_post'] == 1): ?>
            <h2>Post a Message</h2>
            <form action="group.php?id=<?php echo $groupId; ?>" method="POST" enctype="multipart/form-data" class="post-form">
                <textarea name="post_text" placeholder="Write your message here" required></textarea><br>
                <input type="file" name="image"><br>
                <input type="submit" value="Post">
            </form>
        <?php endif; ?>

        <h2>Group Posts</h2>

        <?php
        if ($postsResult->num_rows > 0) {
            while ($post = $postsResult->fetch_assoc()) {
                echo "<div class='post'>";
                echo "<p class='username'>" . htmlspecialchars($post['username']) . ":</p>";
               

                if ($post['image_url']) {
                    echo "<img src='" . htmlspecialchars($post['image_url']) . "' alt='Post Image'>";
                }
                echo "<p>" . nl2br(htmlspecialchars($post['post_text'])) . "</p>";
                echo "<p class='created-at'>Posted on: " . $post['created_at'] . "</p>";
                echo "</div>";
            }
        } else {
            echo "<p>No posts yet in this group.</p>";
        }
        ?>

    </div>

</body>
</html>

<?php
$conn->close();
?>
