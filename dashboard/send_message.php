<?php
session_start();
include('db_connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_text'])) {
    $sender_id = $_SESSION['user_id']; // Logged-in user ID
    $receiver_id = $_POST['receiver_id'];
    $message_text = $_POST['message_text'];

    // Insert the message into the database
    $sql = "INSERT INTO messages (sender_id, receiver_id, message_text, status) VALUES (?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $sender_id, $receiver_id, $message_text);
    if ($stmt->execute()) {
        echo "Message sent!";
    } else {
        echo "Error sending message.";
    }
}
?>
