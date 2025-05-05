<?php
session_start();

// Ensure that the user is logged in as admin
// if (!isset($_SESSION['admin_id'])) {
//     header("Location: admin_login.php");
//     exit();
// }

// Database connection
include('db_connection.php');

// Fetch pending posts
$sql_posts = "SELECT mp.post_id, mp.item_name, mp.description, mp.price, mp.images, u.username, mp.status 
              FROM marketplace_posts mp 
              JOIN users u ON mp.user_id = u.id 
              WHERE mp.status = 'pending' ORDER BY mp.created_at DESC";
$result_posts = $conn->query($sql_posts);

// Handle approval or rejection
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $post_id = $_POST['post_id'];
    $action = $_POST['action'];

    if ($action == 'approve') {
        $update_sql = "UPDATE marketplace_posts SET status = 'approved' WHERE post_id = ?";
    } elseif ($action == 'reject') {
        $update_sql = "UPDATE marketplace_posts SET status = 'rejected' WHERE post_id = ?";
    }

    if ($stmt = $conn->prepare($update_sql)) {
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Approval</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f9;
        margin: 0;
        padding: 20px;
    }

    h2 {
        text-align: center;
        color: #333;
    }

    .post-container {
        display: flex;
        flex-direction: column;
        gap: 20px;
        margin-top: 20px;
    }

    .post {
        background-color: white;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .post h4 {
        margin: 0;
        font-size: 22px;
        color: #333;
    }

    .post p {
        font-size: 16px;
        color: #666;
        margin: 5px 0;
    }

    .post .status {
        font-weight: bold;
        padding: 5px;
        color: #fff;
        border-radius: 4px;
        margin-top: 10px;
    }

    .status.pending {
        background-color: #f39c12;
    }

    .status.approved {
        background-color: #27ae60;
    }

    .status.rejected {
        background-color: #e74c3c;
    }

    .images-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .image-wrapper {
        width: 100px;
        height: 100px;
        overflow: hidden;
        position: relative;
    }

    .image-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: all 0.3s ease-in-out;
    }

    .image-wrapper:hover img {
        transform: scale(1.1);
    }

    .action-buttons {
        margin-top: 15px;
    }

    .action-buttons button {
        padding: 8px 15px;
        margin-right: 10px;
        font-size: 14px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .approve-btn {
        background-color: #27ae60;
        color: white;
    }

    .reject-btn {
        background-color: #e74c3c;
        color: white;
    }
    </style>
</head>

<body>
    <h2>Admin Approval Dashboard</h2>

    <div class="post-container">
        <?php while ($post = $result_posts->fetch_assoc()): ?>
        <div class="post">
            <h4><?php echo htmlspecialchars($post['item_name']); ?></h4>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($post['description']); ?></p>
            <p><strong>Price:</strong> $<?php echo number_format($post['price'], 2); ?></p>
            <p><strong>Posted by:</strong> <?php echo htmlspecialchars($post['username']); ?></p>

            <div class="status <?php echo strtolower($post['status']); ?>">
                Status: <?php echo ucfirst($post['status']); ?>
            </div>

            <p><strong>Images:</strong></p>
            <div class="images-container">
                <?php
                    // Decode the JSON array of image paths
                    $images = json_decode($post['images'], true);
                    if ($images) {
                        foreach ($images as $image_path) {
                            // Fix the image path to replace the escaped slashes
                            $image_path = str_replace("uploads\/", "uploads/", $image_path);
                            // Adjust the image path to match the correct URL for the local server
                            $image_url = "/dashboard/" . $image_path;
                            echo '<div class="image-wrapper"><img src="' . htmlspecialchars($image_url) . '" alt="Item Image" /></div>';
                        }
                    }
                    ?>
            </div>

            <div class="action-buttons">
                <form method="POST" action="">
                    <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                    <button type="submit" name="action" value="approve" class="approve-btn">Approve</button>
                    <button type="submit" name="action" value="reject" class="reject-btn">Reject</button>
                </form>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</body>

</html>