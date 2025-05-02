<?php
session_start();
include('db_connection.php');

$user_id = $_SESSION['user_id']; // Logged-in user ID
$receiver_id = $_GET['receiver_id'];

// Get all messages between the logged-in user and selected receiver
$sql = "
    SELECT sender_id, receiver_id, message_text, created_at, status 
    FROM messages 
    WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $user_id, $receiver_id, $receiver_id, $user_id);
$stmt->execute();
$messages_result = $stmt->get_result();

$output = "";
while ($message = $messages_result->fetch_assoc()) {
    $class = ($message['sender_id'] == $user_id) ? 'sent' : 'received';
    $output .= "<div class='message $class'>
                    <p><strong>" . ($message['sender_id'] == $user_id ? 'You' : 'User ' . $message['sender_id']) . ":</strong></p>
                    <p class='message-text'>" . htmlspecialchars($message['message_text']) . "</p>
                    <small class='message-time'>" . $message['created_at'] . " - Status: " . ucfirst($message['status']) . "</small>
                </div>";
}

echo $output;
?>
