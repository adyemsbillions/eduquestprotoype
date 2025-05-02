<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "unimaid9_unimaidresources", "#adyems123AD", "unimaid9_unimaidresources");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$support_id = 1; // Assuming 1 is the customer care user ID

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    
    if (empty($message)) {
        echo json_encode(['error' => 'Message cannot be empty']);
        exit();
    }
    
    $sql_insert = "INSERT INTO customer_chat (sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, NOW())";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("iis", $user_id, $support_id, $message);
    
    if (!$stmt_insert->execute()) {
        echo json_encode(['error' => 'Insert failed: ' . $stmt_insert->error]);
        exit();
    }
    $stmt_insert->close();
}

// Fetch chat messages
$sql_messages = "SELECT c.message, c.created_at, c.sender_id, u.username FROM customer_chat c 
                 JOIN users u ON c.sender_id = u.id 
                 WHERE (c.sender_id = ? AND c.receiver_id = ?) OR (c.sender_id = ? AND c.receiver_id = ?) 
                 ORDER BY c.created_at ASC";
$stmt_messages = $conn->prepare($sql_messages);
$stmt_messages->bind_param("iiii", $user_id, $support_id, $support_id, $user_id);
$stmt_messages->execute();
$messages_result = $stmt_messages->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Support</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
        .chat-container { max-width: 600px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; }
        .chat-messages { max-height: 400px; overflow-y: auto; padding: 10px; background: #fafafa; border-radius: 5px; }
        .message { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .message.sent { background: #007bff; color: white; text-align: right; }
        .message.received { background: #e9ecef; color: #333; text-align: left; }
        .chat-input { display: flex; margin-top: 20px; }
        .chat-input textarea { flex: 1; padding: 10px; resize: none; }
        .chat-input button { padding: 10px; background: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="chat-container">
        <h2>Customer Support</h2>
        <div class="chat-messages" id="chat-box">
            <?php while ($row = $messages_result->fetch_assoc()): ?>
                <div class="message <?= $row['sender_id'] == $user_id ? 'sent' : 'received' ?>">
                    <p><?= htmlspecialchars($row['message']) ?></p>
                    <small><?= $row['created_at'] ?></small>
                </div>
            <?php endwhile; ?>
        </div>
        <form class="chat-input" method="POST" id="chat-form">
            <textarea name="message" id="message" required></textarea>
            <button type="submit">Send</button>
        </form>
    </div>
    <script>
        $("#chat-form").submit(function(event) {
            event.preventDefault();
            var message = $("#message").val();
            $.post("customer_care.php", { message: message }, function(response) {
                location.reload();
            });
        });
    </script>
</body>
</html>
