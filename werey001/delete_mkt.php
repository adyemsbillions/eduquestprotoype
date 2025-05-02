<?php
// Database connection
$conn = new mysqli("localhost", "unimaid9_unimaidresources", "#adyems123AD", "unimaid9_unimaidresources");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle delete request
if (isset($_POST['delete_post'])) {
    $post_id = $_POST['post_id'];
    $sql = "DELETE FROM marketplace_posts WHERE post_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $post_id);
    if ($stmt->execute()) {
        $success_message = "Post deleted successfully!";
    } else {
        $error_message = "Error deleting post: " . $conn->error;
    }
    $stmt->close();
}

// Fetch all posts (including non-approved ones)
$sql_posts = "SELECT mp.post_id, mp.item_name, mp.description, mp.price, mp.images, mp.status, mp.created_at, u.username 
              FROM marketplace_posts mp 
              JOIN users u ON mp.user_id = u.id 
              ORDER BY mp.created_at DESC";
$result_posts = $conn->query($sql_posts);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Marketplace Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .post {
            padding: 15px;
            margin-bottom: 15px;
            background-color: #fafafa;
            border: 1px solid #ddd;
            border-radius: 5px;
            position: relative;
        }

        .post h3 {
            margin: 0 0 10px;
            color: #2c3e50;
        }

        .post p {
            margin: 5px 0;
            color: #666;
        }

        .status {
            padding: 5px 10px;
            border-radius: 15px;
            color: white;
            font-size: 12px;
            display: inline-block;
        }

        .status-approved {
            background-color: #27ae60;
        }

        .status-pending {
            background-color: #e67e22;
        }

        .delete-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }

        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            text-align: center;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 5px;
            margin-top: 10px;
        }

        .image-grid img {
            width: 100%;
            height: auto;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Marketplace Management</h2>

        <?php 
        if (isset($success_message)) {
            echo "<div class='message success'>$success_message</div>";
        }
        if (isset($error_message)) {
            echo "<div class='message error'>$error_message</div>";
        }
        ?>

        <div id="marketplace-posts">
            <?php while ($post = $result_posts->fetch_assoc()): ?>
                <div class="post">
                    <h3><?php echo htmlspecialchars($post['item_name']); ?></h3>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($post['description']); ?></p>
                    <p><strong>Price:</strong> $<?php echo number_format($post['price'], 2); ?></p>
                    <p><strong>Posted by:</strong> <?php echo htmlspecialchars($post['username']); ?></p>
                    <p><strong>Date:</strong> <?php echo htmlspecialchars($post['created_at']); ?></p>
                    <p><strong>Status:</strong> 
                        <span class="status <?php echo $post['status'] === 'approved' ? 'status-approved' : 'status-pending'; ?>">
                            <?php echo htmlspecialchars($post['status']); ?>
                        </span>
                    </p>

                    <div class="image-grid">
                        <?php
                        $images = json_decode($post['images'], true);
                        if ($images) {
                            foreach ($images as $image_path) {
                                echo '<img src="' . htmlspecialchars($image_path) . '" alt="Item Image">';
                            }
                        }
                        ?>
                    </div>

                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this post?');">
                        <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                        <button type="submit" name="delete_post" class="delete-btn">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>