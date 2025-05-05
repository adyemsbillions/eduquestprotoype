<?php
session_start();
include 'db_connection.php';

$user_id = $_GET['user_id'];
$result = $conn->query("SELECT * FROM customercare WHERE user_id = '$user_id' ORDER BY timestamp ASC");
while ($row = $result->fetch_assoc()) {
    $class = $row['is_bot'] || $row['is_admin'] ? 'bot-message' : 'user-message';
    $content = $row['message'] ? htmlspecialchars($row['message']) : '';
    if ($row['image_path']) {
        $content .= "<br><img src='{$row['image_path']}' alt='User Image'>";
    }
    echo "<div class='message $class'>$content</div>";
}
$conn->close();