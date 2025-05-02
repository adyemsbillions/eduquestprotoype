<?php
// Assuming you've included your database connection file
require_once 'db_connection.php';

$sql = "SELECT * FROM anonymous_posts ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($post = $result->fetch_assoc()) {
        echo '<div class="post">';
        echo '<p>' . htmlspecialchars($post['content']) . '</p>';
        if ($post['image_path']) {
            echo '<img src="' . htmlspecialchars($post['image_path']) . '" alt="Post Image" style="max-width: 100%; height: auto;">';
        }
        echo '<p><small>Posted on: ' . $post['created_at'] . '</small></p>';
        echo '</div>';
    }
} else {
    echo '<p>No posts available.</p>';
}
?>
