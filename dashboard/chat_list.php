<?php
// Database connection
$conn = new mysqli("localhost", "unimaid9_unimaidresources", "#adyems123AD", "unimaid9_unimaidresources");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

$user_id = $_SESSION['user_id'];

// Get chat user ID from URL
if (isset($_GET['chat_user_id'])) {
    $chat_user_id = $_GET['chat_user_id'];

    // Fetch the selected chat user's messages
    $messages = [];
    $sql_messages = "SELECT m.*, u1.username AS sender_username, u2.username AS receiver_username
                     FROM messages m
                     JOIN users u1 ON m.sender_id = u1.id
                     JOIN users u2 ON m.receiver_id = u2.id
                     WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
                     ORDER BY m.created_at ASC";
    $stmt_messages = $conn->prepare($sql_messages);
    $stmt_messages->bind_param("iiii", $user_id, $chat_user_id, $chat_user_id, $user_id);
    $stmt_messages->execute();
    $messages_result = $stmt_messages->get_result();
    while ($message = $messages_result->fetch_assoc()) {
        $messages[] = $message;
    }

    // Mark messages as read for the receiver
    if ($user_id != $chat_user_id) {
        $sql_mark_read = "UPDATE messages SET status = 'seen' WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'";
        $stmt_mark_read = $conn->prepare($sql_mark_read);
        $stmt_mark_read->bind_param("ii", $chat_user_id, $user_id);
        $stmt_mark_read->execute();
    }
}

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    if (isset($_POST['receiver_id']) && isset($_POST['message']) && !empty($_POST['message'])) {
        $receiver_id = $_POST['receiver_id'];
        $message_content = $_POST['message'];
        $file_path = null; // Default is no file

        // Handle image or PDF upload
        if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
            $allowed_image_types = ['image/jpeg', 'image/png', 'image/gif'];
            $allowed_pdf_types = ['application/pdf'];

            $file_type = $_FILES['file']['type'];

            if (in_array($file_type, $allowed_image_types)) {
                // Handling image upload
                $file_name = uniqid() . '_' . basename($_FILES['file']['name']);
                $upload_dir = 'uploads/files/images/';
                $file_path = $upload_dir . $file_name;

                if (!move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
                    echo "Error uploading image.";
                    exit;
                }
            } elseif (in_array($file_type, $allowed_pdf_types)) {
                // Handling PDF upload with the custom prefix
                $file_name = 'unimaidresources-' . uniqid() . '_' . basename($_FILES['file']['name']);
                $upload_dir = 'uploads/files/pdfs/';
                $file_path = $upload_dir . $file_name;

                if (!move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
                    echo "Error uploading PDF.";
                    exit;
                }
            } else {
                echo "Invalid file format. Only JPEG, PNG, GIF, and PDF files are allowed.";
                exit;
            }
        }

        // Insert the new message into the database with status set to 'pending'
        $sql_insert = "INSERT INTO messages (sender_id, receiver_id, message_text, file_path, status) 
                       VALUES (?, ?, ?, ?, 'pending')";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iiss", $user_id, $receiver_id, $message_content, $file_path);
        if ($stmt_insert->execute()) {
            // Successfully inserted message
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat</title>
    <style>
        /* Include your styles from the previous code here */
    </style>
</head>
<body>
    <div class="container">
        <h2>Chat with <?php echo htmlspecialchars($chat_user_id); ?></h2>

        <!-- Chat Box -->
        <div class="chat-box">
            <?php foreach ($messages as $message): ?>
                <div class="message <?php echo ($message['sender_id'] == $user_id) ? 'sent' : 'received'; ?> 
                                    <?php echo ($message['sender_id'] != $user_id && $message['status'] == 'pending') ? 'pending' : ''; ?>
                                    <?php echo ($message['sender_id'] == $user_id && $message['status'] == 'seen') ? 'seen' : ''; ?>">
                    <p class="sender"><?php echo ($message['sender_id'] == $user_id) ? 'You' : htmlspecialchars($message['sender_username']); ?>:</p>
                    <p><?php echo htmlspecialchars($message['message_text']); ?></p>

                    <!-- Check if there is an image -->
                    <?php if ($message['file_path'] && strpos($message['file_path'], '.pdf') !== false): ?>
                        <div class="message-file">
                            <a href="<?php echo htmlspecialchars($message['file_path']); ?>" download>Download PDF</a>
                        </div>
                    <?php elseif ($message['file_path']): ?>
                        <div class="message-image">
                            <img src="<?php echo htmlspecialchars($message['file_path']); ?>" alt="Image" class="clickable-image">
                        </div>
                    <?php endif; ?>

                    <p class="status"><?php echo ($message['status'] == 'pending') ? '<i>Not Seen</i>' : '<i>Seen</i>'; ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Message Sending Form -->
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="receiver_id" value="<?php echo $chat_user_id; ?>">
            <textarea name="message" placeholder="Type your message..." required></textarea>
            <input type="file" name="file" accept="image/*, application/pdf">
            <button type="submit" name="send_message">Send</button>
        </form>
    </div>
</body>
</html>
