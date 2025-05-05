<?php
require_once 'db_connection.php';
session_start();
// Assume admin_id is stored in session after login
$admin_id = $_SESSION['admin_id'];

// Get all open chats
$chats = $conn->query("SELECT DISTINCT user_id FROM customer_care WHERE status = 'open'")->fetchAll();

if (isset($_GET['user_id'])) {
    $selected_user = $_GET['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $message = $_POST['message'];
        $stmt = $conn->prepare("INSERT INTO customer_care (user_id, admin_id, message_text, sender_type) VALUES (?, ?, ?, 'admin')");
        $stmt->execute([$selected_user, $admin_id, $message]);
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Admin Chat</title>
    <style>
    .admin-container {
        display: flex;
        width: 800px;
        margin: 20px auto;
        gap: 20px;
    }

    .user-list {
        width: 200px;
        border: 1px solid #ccc;
        padding: 10px;
    }

    .user-link {
        display: block;
        padding: 5px;
        text-decoration: none;
        color: #333;
    }

    .user-link:hover {
        background: #f0f0f0;
    }

    .chat-section {
        flex-grow: 1;
        border: 1px solid #ccc;
        border-radius: 5px;
    }

    .chat-header {
        background: #f0f0f0;
        padding: 10px;
        text-align: center;
    }

    .chat-messages {
        height: 300px;
        overflow-y: auto;
        padding: 10px;
    }

    .user-message {
        background: #e3f2fd;
        padding: 8px;
        margin: 5px 0;
        border-radius: 5px;
        text-align: left;
    }

    .admin-message {
        background: #f5f5f5;
        padding: 8px;
        margin: 5px 0;
        border-radius: 5px;
        text-align: right;
    }

    .chat-form {
        padding: 10px;
        display: flex;
        gap: 10px;
    }

    .chat-form input {
        flex-grow: 1;
        padding: 5px;
    }

    .chat-form button {
        padding: 5px 15px;
    }
    </style>
</head>

<body>
    <div class="admin-container">
        <div class="user-list">
            <h3>Active Chats</h3>
            <?php foreach ($chats as $chat): ?>
            <a href="?user_id=<?= $chat['user_id'] ?>" class="user-link"><?= $chat['user_id'] ?></a>
            <?php endforeach; ?>
        </div>
        <div class="chat-section">
            <?php if (isset($selected_user)): ?>
            <div class="chat-header">Chat with User <?= $selected_user ?></div>
            <div class="chat-messages" id="chat-messages">
                <?php
                    $stmt = $conn->prepare("SELECT * FROM customer_care WHERE user_id = ? ORDER BY timestamp");
                    $stmt->execute([$selected_user]);
                    $messages = $stmt->fetchAll();
                    foreach ($messages as $msg) {
                        $class = $msg['sender_type'] === 'user' ? 'user-message' : 'admin-message';
                        echo "<div class='$class'>" . htmlspecialchars($msg['message_text']) . "</div>";
                    }
                    ?>
            </div>
            <form method="POST" class="chat-form">
                <input type="text" name="message" placeholder="Type your response..." required>
                <button type="submit">Send</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <script>
    // Auto-refresh every 5 seconds when a user is selected
    if (document.getElementById('chat-messages')) {
        setInterval(function() {
            const urlParams = new URLSearchParams(window.location.search);
            const userId = urlParams.get('user_id');
            fetch(`admin_chat.php?user_id=${userId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('chat-messages').innerHTML =
                        new DOMParser().parseFromString(data, 'text/html')
                        .querySelector('#chat-messages').innerHTML;
                });
        }, 5000);
    }
    </script>
</body>

</html>