<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "UNIMAIDCONNECT");
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

// Get the list of users to chat with (excluding the current user)
$sql_users = "SELECT id, username FROM users WHERE id != ?";
$stmt_users = $conn->prepare($sql_users);
$stmt_users->bind_param("i", $user_id);
$stmt_users->execute();
$result_users = $stmt_users->get_result();

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    if (isset($_POST['receiver_id']) && isset($_POST['message']) && !empty($_POST['message'])) {
        $receiver_id = $_POST['receiver_id'];
        $message_content = $_POST['message'];
        $image_path = null; // Default is no image

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            // Validate the image
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $image_type = $_FILES['image']['type'];

            if (in_array($image_type, $allowed_types)) {
                // Generate a unique name for the image
                $image_name = uniqid() . '_' . basename($_FILES['image']['name']);
                $upload_dir = 'uploads/images/';
                $upload_path = $upload_dir . $image_name;

                // Move the uploaded file to the server
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image_path = $upload_path; // Save the path of the image
                }
            } else {
                echo "Invalid image format. Only JPEG, PNG, and GIF files are allowed.";
            }
        }

        // Insert the new message into the database with status set to 'pending'
        $sql_insert = "INSERT INTO messages (sender_id, receiver_id, message_text, image_path, status) 
                       VALUES (?, ?, ?, ?, 'pending')";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iiss", $user_id, $receiver_id, $message_content, $image_path);
        if ($stmt_insert->execute()) {
            // Successfully inserted message
        }
    }
}

// Fetch unread messages details (sender's username for notifications)
$sql_unread_messages = "SELECT u.username, m.sender_id, COUNT(*) AS unread_count 
                        FROM messages m 
                        JOIN users u ON m.sender_id = u.id
                        WHERE m.receiver_id = ? AND m.status = 'pending'
                        GROUP BY m.sender_id";
$stmt_unread = $conn->prepare($sql_unread_messages);
$stmt_unread->bind_param("i", $user_id);
$stmt_unread->execute();
$result_unread = $stmt_unread->get_result();
$unread_message_details = [];

while ($row_unread = $result_unread->fetch_assoc()) {
    $unread_message_details[] = $row_unread;
}

// If a message is read by the receiver, update the status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_as_seen'])) {
    $message_id = $_POST['message_id'];
    $sql_mark_seen = "UPDATE messages SET status = 'seen' WHERE id = ? AND receiver_id = ?";
    $stmt_mark_seen = $conn->prepare($sql_mark_seen);
    $stmt_mark_seen->bind_param("ii", $message_id, $user_id);
    $stmt_mark_seen->execute();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Application</title>
    <style>
           .message.received {
            color: #333;
        }

        .message.received.pending {
            font-style: italic;
            color: #888;
        }

        .message.sent {
            color: #1a73e8;
        }

        .message.sent.seen {
            font-weight: bold;
            color: #3b9e4b;
        }

        .message.sent {
            font-style: normal;
        }

        .status {
            font-size: 0.8em;
            color: gray;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .user-list {
            margin-bottom: 20px;
        }
        .chat-box {
            height: 400px;
            overflow-y: scroll;
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #f1f1f1;
        }
        .message {
            margin: 5px 0;
            clear: both;
            padding: 5px;
            border-radius: 5px;
        }
     /* Style for Sent Messages (WhatsApp-style) */
.message.sent {
    background-color: purple; /* WhatsApp green color */
    color: white;
    float: right;
    text-align: left;
    max-width: 75%; /* Adjust to make it look like a typical message bubble */
    border-radius: 20px 20px 0 20px; /* Rounded corners for a soft bubble shape */
    padding: 10px 15px;
    margin-bottom: 10px;
    position: relative;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Add a light shadow for a floating effect */
    word-wrap: break-word; /* Ensure long messages break onto the next line */
    font-size: 14px;
    line-height: 1.5;
}

/* Optional: Add a small triangle pointer at the end of the message bubble */
.message.sent::after {
    content: "";
    position: absolute;
    right: -10px;
    top: 50%;
    border-width: 10px;
    border-style: solid;
    border-color: transparent transparent transparent #25d366;
    transform: translateY(-50%);
}

        /* Style for Received Messages (WhatsApp-style) */
.message.received {
    background-color: grey; /* Light gray background for received messages */
    color: white; /* Dark text for readability */
    float: left;
    text-align: left;
    max-width: 75%; /* Limit width to give a message bubble effect */
    border-radius: 20px 20px 20px 0; /* Rounded corners for soft bubble effect */
    padding: 10px 15px;
    margin-bottom: 10px;
    position: relative;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05); /* Add a soft shadow */
    word-wrap: break-word;
    font-size: 14px;
    line-height: 1.5;
}

/* Optional: Add a small triangle pointer at the end of the received message bubble */
.message.received::after {
    content: "";
    position: absolute;
    left: -10px;
    top: 50%;
    border-width: 10px;
    border-style: solid;
    border-color: transparent #f1f1f1 transparent transparent; /* Match background color */
    transform: translateY(-50%);
}

/* Style for the sender's name */
.message .sender {
    font-weight: bold;
    font-size: 12px;
    color: black; /* Slightly darker for clarity */
    margin-bottom: 5px; /* Add space between sender's name and the message */
}

/* Style for message status (sent/received) */
.message .status {
    font-size: 0.9em;
    color: white; /* Light gray for the status */
    margin-top: 5px;
    text-align: right;
}

        .new-message-notification {
            background-color: #f1c40f;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .message-image img {
            display: block;
            margin-top: 5px;
            border-radius: 5px;
            max-width: 100%;
            max-height: 300px;
        }
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            margin-bottom: 10px;
            border-radius: 5px;
            box-sizing: border-box;
        }
        button {
            background-color: #6a1b9a;
            color: white;
            padding: 10px 15px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #8e24aa;
        }
        @media screen and (max-width: 600px) {
            .container {
                padding: 15px;
            }
            .chat-box {
                height: 300px;
            }
        }
        /* Style for the Select Dropdown */
select {
    width: 100%;
    max-width: 250px; /* Make it responsive */
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    background-color: #f9f9f9;
    color: #333;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
}

select:focus {
    border-color: #6a1b9a;
    outline: none;
    box-shadow: 0 0 8px rgba(106, 27, 154, 0.2);
}

/* Style for the Message Input (textarea) */
textarea {
    width: 100%;
    padding: 15px;
    border-radius: 10px;
    border: 1px solid #ddd;
    font-size: 16px;
    resize: none;
    margin-bottom: 15px;
    box-sizing: border-box;
    background-color: #f8f8f8;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

textarea:focus {
    border-color: #6a1b9a;
    outline: none;
    box-shadow: 0 0 8px rgba(106, 27, 154, 0.2);
}

/* Style for Send Button */
button {
    background-color: #6a1b9a;
    color: white;
    padding: 12px 20px;
    font-size: 16px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
    width: 100%;
    max-width: 120px; /* Makes the button fit within the container */
    margin-top: 10px;
}

button:hover {
    background-color: #8e24aa;
    transform: scale(1.05);
}

button:active {
    background-color: #9c27b0;
}

/* Adjust button size and text for smaller screens */
@media screen and (max-width: 768px) {
    button {
        padding: 10px 15px;
        width: 100%;
    }

    select {
        max-width: 200px;
    }
}

    </style>
</head>
<body>
    <div class="container">
        <h2>Chat Application</h2>

        <!-- Display New Message Notifications Before Chat Selection -->
        <?php if (!empty($unread_message_details)): ?>
            <div class="new-message-notification">
                <h3>You have new messages:</h3>
                <ul>
                    <?php foreach ($unread_message_details as $detail): ?>
                        <li>
                            You have <?php echo $detail['unread_count']; ?> new message<?php echo ($detail['unread_count'] > 1) ? 's' : ''; ?> from <?php echo htmlspecialchars($detail['username']); ?>.
                            <!-- Reply Link -->
                            <a href="?chat_user_id=<?php echo $detail['sender_id']; ?>">Reply</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <p>No new messages.</p>
        <?php endif; ?>

        <!-- User Selection Dropdown -->
        <div class="user-list">
            <form method="GET">
                <label for="chat_user_id">Select User to Chat:</label>
                <select name="chat_user_id" id="chat_user_id">
                    <?php while ($user = $result_users->fetch_assoc()): ?>
                        <option value="<?php echo $user['id']; ?>"><?php echo $user['username']; ?></option>
                    <?php endwhile; ?>
                </select>
                <button type="submit">Start Chat</button>
            </form>
        </div>

        <!-- If a user has selected a chat, show the chat interface -->
        <?php
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
        ?>

        <?php if (isset($chat_user_id)): ?>
            <!-- Chat Box -->
            <div class="chat-box">
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo ($message['sender_id'] == $user_id) ? 'sent' : 'received'; ?> 
                                        <?php echo ($message['sender_id'] != $user_id && $message['status'] == 'pending') ? 'pending' : ''; ?>
                                        <?php echo ($message['sender_id'] == $user_id && $message['status'] == 'seen') ? 'seen' : ''; ?>">
                        <p class="sender"><?php echo ($message['sender_id'] == $user_id) ? 'You' : htmlspecialchars($message['sender_username']); ?>:</p>
                        <p><?php echo htmlspecialchars($message['message_text']); ?></p>
                        
                        <!-- Check if there is an image -->
                        <?php if ($message['image_path']): ?>
                            <div class="message-image">
                                <img src="<?php echo htmlspecialchars($message['image_path']); ?>" alt="Image">
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
                <input type="file" name="image" accept="image/*">
                <button type="submit" name="send_message">Send</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
