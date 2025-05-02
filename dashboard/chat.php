<?php
// Database connection
$conn = new mysqli("localhost", "unimaid9_unimaidresources", "#adyems123AD", "unimaid9_unimaidresources");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle AJAX request to fetch new messages and reactions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fetch_messages'])) {
    header('Content-Type: application/json');
    $chat_user_id = isset($_POST['chat_user_id']) ? (int)$_POST['chat_user_id'] : null;
    $last_message_id = isset($_POST['last_message_id']) ? (int)$_POST['last_message_id'] : 0;
    
    if (!$chat_user_id) {
        echo json_encode(['success' => false, 'error' => 'No chat user selected']);
        exit;
    }

    // Fetch new messages
    $messages = [];
    $sql_messages = "SELECT m.*, u1.username AS sender_username, u2.username AS receiver_username,
                            GROUP_CONCAT(mr.emoji) AS reactions,
                            m_reply.message_text AS reply_to_text, u_reply.username AS reply_to_username
                     FROM messages m
                     JOIN users u1 ON m.sender_id = u1.id
                     JOIN users u2 ON m.receiver_id = u2.id
                     LEFT JOIN message_reactions mr ON m.id = mr.message_id
                     LEFT JOIN messages m_reply ON m.reply_to_id = m_reply.id
                     LEFT JOIN users u_reply ON m_reply.sender_id = u_reply.id
                     WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
                     AND m.id > ?
                     GROUP BY m.id
                     ORDER BY m.created_at ASC";
    $stmt_messages = $conn->prepare($sql_messages);
    if (!$stmt_messages) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
        exit;
    }
    $stmt_messages->bind_param("iiiii", $user_id, $chat_user_id, $chat_user_id, $user_id, $last_message_id);
    $stmt_messages->execute();
    $messages_result = $stmt_messages->get_result();
    while ($message = $messages_result->fetch_assoc()) {
        $messages[] = $message;
    }

    // Fetch all message IDs in the current chat to check for new reactions
    $message_ids = [];
    $sql_all_ids = "SELECT id FROM messages 
                    WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)";
    $stmt_all_ids = $conn->prepare($sql_all_ids);
    $stmt_all_ids->bind_param("iiii", $user_id, $chat_user_id, $chat_user_id, $user_id);
    $stmt_all_ids->execute();
    $ids_result = $stmt_all_ids->get_result();
    while ($row = $ids_result->fetch_assoc()) {
        $message_ids[] = $row['id'];
    }

    // Fetch new reactions for all messages in the chat
    $reactions = [];
    if (!empty($message_ids)) {
        $placeholders = implode(',', array_fill(0, count($message_ids), '?'));
        $sql_reactions = "SELECT mr.message_id, GROUP_CONCAT(mr.emoji) AS emojis
                          FROM message_reactions mr
                          WHERE mr.message_id IN ($placeholders)
                          GROUP BY mr.message_id";
        $stmt_reactions = $conn->prepare($sql_reactions);
        $stmt_reactions->bind_param(str_repeat('i', count($message_ids)), ...$message_ids);
        $stmt_reactions->execute();
        $reactions_result = $stmt_reactions->get_result();
        while ($reaction = $reactions_result->fetch_assoc()) {
            $reactions[$reaction['message_id']] = $reaction['emojis'];
        }
    }

    // Mark messages as seen
    $sql_mark_read = "UPDATE messages SET status = 'seen' WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'";
    $stmt_mark_read = $conn->prepare($sql_mark_read);
    if (!$stmt_mark_read) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
        exit;
    }
    $stmt_mark_read->bind_param("ii", $chat_user_id, $user_id);
    $stmt_mark_read->execute();

    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'reactions' => $reactions
    ]);
    exit;
}

// Get the list of users to chat with (excluding the current user)
// Get the list of users to chat with (only those the user follows or is followed by)
$sql_users = "
    SELECT DISTINCT u.id, u.username 
    FROM users u
    INNER JOIN followers f ON 
        (u.id = f.follower_id AND f.following_id = ?) OR 
        (u.id = f.following_id AND f.follower_id = ?)
    WHERE u.id != ?";
$stmt_users = $conn->prepare($sql_users);
$stmt_users->bind_param("iii", $user_id, $user_id, $user_id);
$stmt_users->execute();
$result_users = $stmt_users->get_result();
$users = [];
while ($row = $result_users->fetch_assoc()) {
    $users[] = $row;
}

// Handle message sending via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    header('Content-Type: application/json');
    if (isset($_POST['receiver_id']) && isset($_POST['message']) && !empty($_POST['message'])) {
        $receiver_id = (int)$_POST['receiver_id'];
        $message_content = trim($_POST['message']);
        $file_path = null;
        $reply_to_id = isset($_POST['reply_to_id']) ? (int)$_POST['reply_to_id'] : null;

        if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
            $allowed_image_types = ['image/jpeg', 'image/png', 'image/gif'];
            $allowed_pdf_types = ['application/pdf'];
            $file_type = $_FILES['file']['type'];

            if (in_array($file_type, $allowed_image_types)) {
                $file_name = uniqid() . '_' . basename($_FILES['file']['name']);
                $upload_dir = 'uploads/files/images/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $file_path = $upload_dir . $file_name;
                if (!move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
                    echo json_encode(['success' => false, 'error' => 'Error uploading image']);
                    exit;
                }
            } elseif (in_array($file_type, $allowed_pdf_types)) {
                $file_name = 'unimaidresources-' . uniqid() . '_' . basename($_FILES['file']['name']);
                $upload_dir = 'uploads/files/pdfs/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $file_path = $upload_dir . $file_name;
                if (!move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
                    echo json_encode(['success' => false, 'error' => 'Error uploading PDF']);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid file format']);
                exit;
            }
        }

        $sql_insert = "INSERT INTO messages (sender_id, receiver_id, message_text, file_path, status, reply_to_id) 
                       VALUES (?, ?, ?, ?, 'pending', ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iissi", $user_id, $receiver_id, $message_content, $file_path, $reply_to_id);
        $success = $stmt_insert->execute();

        if ($success) {
            $message_id = $conn->insert_id;
            $reply_to_text = null;
            $reply_to_username = null;
            if ($reply_to_id) {
                $sql_reply = "SELECT m.message_text, u.username 
                              FROM messages m 
                              JOIN users u ON m.sender_id = u.id 
                              WHERE m.id = ?";
                $stmt_reply = $conn->prepare($sql_reply);
                $stmt_reply->bind_param("i", $reply_to_id);
                $stmt_reply->execute();
                $reply_result = $stmt_reply->get_result();
                if ($reply_row = $reply_result->fetch_assoc()) {
                    $reply_to_text = $reply_row['message_text'];
                    $reply_to_username = $reply_row['username'];
                }
            }
            echo json_encode([
                'success' => true,
                'file_path' => $file_path,
                'message_id' => $message_id,
                'message_text' => $message_content,
                'reply_to_id' => $reply_to_id,
                'reply_to_text' => $reply_to_text,
                'reply_to_username' => $reply_to_username
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt_insert->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    }
    exit;
}

// Handle emoji reaction via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_emoji'])) {
    header('Content-Type: application/json');
    $message_id = (int)$_POST['message_id'];
    $emoji = trim($_POST['emoji']);

    if (!$message_id || !$emoji) {
        echo json_encode(['success' => false, 'error' => 'Invalid message ID or emoji']);
        exit;
    }

    // Check if reaction already exists to avoid duplicates (optional)
    $sql_check = "SELECT COUNT(*) FROM message_reactions WHERE message_id = ? AND user_id = ? AND emoji = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("iis", $message_id, $user_id, $emoji);
    $stmt_check->execute();
    $stmt_check->bind_result($count);
    $stmt_check->fetch();
    $stmt_check->close();

    if ($count > 0) {
        echo json_encode(['success' => true, 'emoji' => $emoji]); // Already reacted
        exit;
    }

    $sql_emoji = "INSERT INTO message_reactions (message_id, user_id, emoji) VALUES (?, ?, ?)";
    $stmt_emoji = $conn->prepare($sql_emoji);
    if (!$stmt_emoji) {
        echo json_encode(['success' => false, 'error' => 'Database prepare error: ' . $conn->error]);
        exit;
    }
    $stmt_emoji->bind_param("iis", $message_id, $user_id, $emoji);
    $success = $stmt_emoji->execute();

    if ($success) {
        echo json_encode(['success' => true, 'emoji' => $emoji]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt_emoji->error]);
    }
    exit;
}

// Fetch unread messages details
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

// Initial messages for page load
$chat_user_id = isset($_GET['chat_user_id']) ? (int)$_GET['chat_user_id'] : null;
$messages = [];
if ($chat_user_id) {
    $table_check = $conn->query("SHOW TABLES LIKE 'message_reactions'");
    $sql_messages = $table_check->num_rows > 0 ?
        "SELECT m.*, u1.username AS sender_username, u2.username AS receiver_username,
                GROUP_CONCAT(mr.emoji) AS reactions,
                m_reply.message_text AS reply_to_text, u_reply.username AS reply_to_username
         FROM messages m
         JOIN users u1 ON m.sender_id = u1.id
         JOIN users u2 ON m.receiver_id = u2.id
         LEFT JOIN message_reactions mr ON m.id = mr.message_id
         LEFT JOIN messages m_reply ON m.reply_to_id = m_reply.id
         LEFT JOIN users u_reply ON m_reply.sender_id = u_reply.id
         WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
         GROUP BY m.id
         ORDER BY m.created_at ASC" :
        "SELECT m.*, u1.username AS sender_username, u2.username AS receiver_username,
                m_reply.message_text AS reply_to_text, u_reply.username AS reply_to_username
         FROM messages m
         JOIN users u1 ON m.sender_id = u1.id
         JOIN users u2 ON m.receiver_id = u2.id
         LEFT JOIN messages m_reply ON m.reply_to_id = m_reply.id
         LEFT JOIN users u_reply ON m_reply.sender_id = u_reply.id
         WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
         ORDER BY m.created_at ASC";
    $stmt_messages = $conn->prepare($sql_messages);
    $stmt_messages->bind_param("iiii", $user_id, $chat_user_id, $chat_user_id, $user_id);
    $stmt_messages->execute();
    $messages_result = $stmt_messages->get_result();
    while ($message = $messages_result->fetch_assoc()) {
        $messages[] = $message;
    }

    $sql_mark_read = "UPDATE messages SET status = 'seen' WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'";
    $stmt_mark_read = $conn->prepare($sql_mark_read);
    $stmt_mark_read->bind_param("ii", $chat_user_id, $user_id);
    $stmt_mark_read->execute();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>chat || unimaid resources</title>
    <style>
        :root {
            --primary: #6a1b9a;
            --primary-dark: #4a148c;
            --secondary: #e3e3e3;
            --text: #333;
            --white: #fff;
            --light-bg: #f5f5f5;
            --error: #ff0000;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--light-bg);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px;
        }

        .chat-container {
            width: 100%;
            max-width: 900px;
            height: 90vh;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .chat-header {
            padding: 15px 20px;
            background: var(--primary);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chat-header h2 {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .user-select {
            padding: 15px;
            background: var(--white);
            border-bottom: 1px solid var(--secondary);
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .user-select-container {
            position: relative;
            flex: 1;
            min-width: 200px;
        }

        .user-select-container select {
            width: 100%;
            padding: 10px 30px 10px 10px;
            border: 1px solid var(--secondary);
            border-radius: 8px;
            font-size: 1rem;
            background: var(--white);
            outline: none;
            appearance: none;
            color: var(--text);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .user-select-container select:focus {
            border-color: var(--primary);
        }

        .user-select-container::after {
            content: '\25BC';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            color: var(--primary);
            pointer-events: none;
        }

        .user-search-container {
            position: relative;
            flex: 1;
            min-width: 200px;
        }

        .user-search-container input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--secondary);
            border-radius: 8px;
            font-size: 1rem;
            background: var(--white);
            outline: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .user-search-container input[type="text"]:focus {
            border-color: var(--primary);
        }

        .user-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--white);
            border: 1px solid var(--secondary);
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 10;
            display: none;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .user-list a {
            display: block;
            padding: 10px;
            color: var(--text);
            text-decoration: none;
            transition: background 0.2s;
        }

        .user-list a:hover {
            background: var(--light-bg);
        }

        .notifications {
            padding: 15px;
            background: #f3e5f5;
            max-height: 150px;
            overflow-y: auto;
        }

        .notifications h3 {
            font-size: 1rem;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .notifications ul {
            list-style: none;
        }

        .notifications li {
            margin: 5px 0;
            font-size: 0.9rem;
        }

        .notifications a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .notifications a:hover {
            text-decoration: underline;
        }

        .chat-area {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: var(--light-bg);
            position: relative;
        }

        .message {
            margin: 10px 0;
            padding: 12px 16px;
            border-radius: 12px;
            max-width: 70%;
            font-size: 0.95rem;
            line-height: 1.4;
            position: relative;
            transition: transform 0.2s;
        }

        .message.sent {
            background: var(--primary);
            color: var(--white);
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }

        .message.received {
            background: var(--secondary);
            color: var(--text);
            margin-right: auto;
            border-bottom-left-radius: 4px;
        }

        .message .sender {
            font-size: 0.75rem;
            opacity: 0.8;
            margin-bottom: 4px;
        }

        .message .status {
            font-size: 0.65rem;
            opacity: 0.6;
            margin-top: 4px;
        }

        .message .reply-to {
            font-size: 0.85rem;
            font-style: italic;
            color: #666;
            background: #f0f0f0;
            padding: 5px 10px;
            border-radius: 6px;
            margin-bottom: 8px;
            border-left: 3px solid var(--primary);
        }

        .message-file a {
            color: var(--primary);
            background: var(--white);
            padding: 6px 12px;
            border-radius: 12px;
            text-decoration: none;
            display: inline-block;
            margin-top: 8px;
        }

        .message-image img {
            max-width: 100%;
            border-radius: 8px;
            margin-top: 8px;
            cursor: pointer;
        }

        .message-reactions {
            margin-top: 4px;
            font-size: 1rem;
        }

        .reply-form {
            margin-top: 10px;
            padding: 10px;
            background: #faf5ff;
            border-radius: 8px;
            position: relative;
        }

        .reply-form textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--secondary);
            border-radius: 6px;
            font-size: 0.9rem;
            resize: none;
        }

        .reply-form button {
            margin-top: 5px;
            padding: 8px 16px;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .emoji-picker {
            position: absolute;
            background: var(--white);
            border: 1px solid var(--secondary);
            border-radius: 8px;
            padding: 10px;
            display: none;
            z-index: 20;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            opacity: 0;
            transform: scale(0.8);
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .emoji-picker.show {
            opacity: 1;
            transform: scale(1);
        }

        .emoji-picker span {
            font-size: 1.5rem;
            margin: 0 5px;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .emoji-picker span:hover {
            transform: scale(1.2);
        }

        .emoji-picker .close-emoji {
            position: absolute;
            top: -20px;
            right: -23px;
            font-size: 1rem;
            color: white;
            cursor: pointer;
            padding: 2px 6px;
            border-radius: 100%;
            background: red;
            transition: background 0.2s ease;
        }

        /*.emoji-picker .close-emoji:hover {*/
        /*    background:red;*/
        /*}*/

        .chat-input {
            padding: 15px;
            background: var(--white);
            border-top: 1px solid var(--secondary);
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            position: relative;
        }

        .chat-input textarea {
            flex: 1;
            min-height: 50px;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid var(--secondary);
            font-size: 0.95rem;
            resize: none;
            outline: none;
        }

        .chat-input input[type="file"] {
            padding: 8px;
            border: 1px solid var(--secondary);
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .chat-input button {
            padding: 10px 20px;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }

        .chat-input button:hover {
            background: var(--primary-dark);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
        }

        .modal-content {
            max-width: 90%;
            max-height: 90vh;
            margin: 50px auto;
            display: block;
        }

        .close {
            position: absolute;
            top: 20px;
            right: 20px;
            color: var(--white);
            font-size: 2rem;
            cursor: pointer;
        }

        .chat-area::-webkit-scrollbar {
            width: 6px;
        }

        .chat-area::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
        }

        .error-message {
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--error);
            color: var(--white);
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            z-index: 20;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .error-message.show {
            opacity: 1;
        }

        .loader {
            display: none;
            border: 3px solid var(--secondary);
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            position: absolute;
        }

        .chat-input .loader {
            right: 80px;
            top: 50%;
            transform: translateY(-50%);
        }

        .reply-form .loader {
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }

        @keyframes spin {
            0% { transform: translateY(-50%) rotate(0deg); }
            100% { transform: translateY(-50%) rotate(360deg); }
        }

        @media (max-width: 600px) {
            .chat-container {
                height: 100vh;
                border-radius: 0;
                margin: 0;
            }

            .chat-header h2 {
                font-size: 1rem;
            }

            .chat-input {
                flex-direction: column;
            }

            .chat-input textarea,
            .chat-input button,
            .chat-input input[type="file"] {
                width: 100%;
            }

            .message {
                max-width: 85%;
            }

            .user-select {
                flex-direction: column;
                gap: 10px;
            }

            .user-select-container,
            .user-search-container {
                min-width: 100%;
            }

            .user-list {
                position: static;
                max-height: 150px;
            }

            .error-message {
                width: 90%;
                text-align: center;
            }

            .chat-input .loader {
                right: 15px;
                top: auto;
                bottom: 20px;
                transform: none;
            }

            .reply-form .loader {
                right: 15px;
                top: auto;
                bottom: 10px;
                transform: none;
            }
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <h2>Chat with friends</h2><br>
            <i>you can only see who you either follow or follows you</i>
        </div>

        <!-- Notifications -->
        <?php if (!empty($unread_message_details)): ?>
            <div class="notifications">
                <h3>New Messages</h3>
                <ul>
                    <?php foreach ($unread_message_details as $detail): ?>
                        <li>
                            <?php echo $detail['unread_count']; ?> new message<?php echo $detail['unread_count'] > 1 ? 's' : ''; ?> 
                            from <?php echo htmlspecialchars($detail['username']); ?> 
                            <a href="?chat_user_id=<?php echo $detail['sender_id']; ?>">Reply</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <div class="notifications">
                <p>No new messages.</p>
            </div>
        <?php endif; ?>

        <!-- User Selection and Search -->
        <div class="user-select">
            <form method="GET" class="user-select-container">
                <select name="chat_user_id" onchange="this.form.submit()">
                    <option value="">Select a user</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo (isset($chat_user_id) && $chat_user_id == $user['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <div class="user-search-container">
                <input type="text" id="userSearch" placeholder="Search users..." autocomplete="off">
                <div class="user-list" id="userList">
                    <?php foreach ($users as $user): ?>
                        <a href="?chat_user_id=<?php echo $user['id']; ?>" class="user-item"><?php echo htmlspecialchars($user['username']); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Chat Area -->
        <?php if (isset($chat_user_id)): ?>
            <div class="chat-area" id="chatArea">
                <div id="errorContainer" class="error-message"></div>
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo $message['sender_id'] == $user_id ? 'sent' : 'received'; ?>" data-message-id="<?php echo $message['id']; ?>">
                        <div class="sender"><?php echo $message['sender_id'] == $user_id ? 'You' : htmlspecialchars($message['sender_username']); ?></div>
                        <?php if ($message['reply_to_id'] && $message['reply_to_text']): ?>
                            <div class="reply-to">
                                Replying to <?php echo htmlspecialchars($message['reply_to_username']); ?>: 
                                "<?php echo htmlspecialchars(substr($message['reply_to_text'], 0, 50)) . (strlen($message['reply_to_text']) > 50 ? '...' : ''); ?>"
                            </div>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($message['message_text']); ?>
                        <?php if ($message['file_path']): ?>
                            <?php if (strpos($message['file_path'], '.pdf') !== false): ?>
                                <div class="message-file">
                                    <a href="<?php echo htmlspecialchars($message['file_path']); ?>" download>Download PDF</a>
                                </div>
                            <?php else: ?>
                                <div class="message-image">
                                    <img src="<?php echo htmlspecialchars($message['file_path']); ?>" alt="Attachment" class="clickable-image">
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <div class="status"><?php echo $message['status'] == 'pending' ? 'Not Seen' : 'Seen'; ?></div>
                        <?php if (isset($message['reactions']) && $message['reactions']): ?>
                            <div class="message-reactions"><?php echo htmlspecialchars($message['reactions']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Input Form -->
            <div class="chat-input" id="chatInputForm">
                <input type="hidden" id="receiverId" value="<?php echo $chat_user_id; ?>">
                <textarea id="messageInput" placeholder="Type a message..." required></textarea>
                <input type="file" id="fileInput" accept="image/*,application/pdf">
                <button id="sendButton" type="button">Send</button>
                <div class="loader" id="messageLoader"></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">×</span>
        <img class="modal-content" id="modalImage">
    </div>

    <!-- Emoji Picker -->
    <div id="emojiPicker" class="emoji-picker">
        <span class="close-emoji" onclick="closeEmojiPicker()">✖</span>
        <span data-emoji="👍">👍</span>
        <span data-emoji="❤️">❤️</span>
        <span data-emoji="😂">😂</span>
        <span data-emoji="😢">😢</span>
        <span data-emoji="😡">😡</span>
    </div>

    <script>
        // Modal functionality
        const modal = document.getElementById("imageModal");
        const modalImg = document.getElementById("modalImage");
        const images = document.getElementsByClassName("clickable-image");

        function updateModalListeners() {
            for (let img of images) {
                img.onclick = function() {
                    modal.style.display = "block";
                    modalImg.src = this.src;
                };
            }
        }
        updateModalListeners();

        function closeModal() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) closeModal();
        }

        // Auto-scroll to bottom
        const chatArea = document.getElementById('chatArea');
        if (chatArea) {
            chatArea.scrollTop = chatArea.scrollHeight;
        }

        // Search functionality
        const searchInput = document.getElementById('userSearch');
        const userList = document.getElementById('userList');
        const userItems = userList.getElementsByClassName('user-item');

        searchInput.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            userList.style.display = filter ? 'block' : 'none';

            for (let item of userItems) {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(filter) ? 'block' : 'none';
            }
        });

        document.addEventListener('click', function(event) {
            if (!userList.contains(event.target) && event.target !== searchInput) {
                userList.style.display = 'none';
            }
        });

        searchInput.addEventListener('focus', function() {
            if (this.value) {
                userList.style.display = 'block';
            }
        });

        // Function to show error message
        function showError(message) {
            const errorContainer = document.getElementById('errorContainer');
            errorContainer.textContent = message;
            errorContainer.classList.add('show');
            setTimeout(() => {
                errorContainer.classList.remove('show');
            }, 3000);
        }

        // AJAX for sending messages with loader
        const sendButton = document.getElementById('sendButton');
        const messageInput = document.getElementById('messageInput');
        const fileInput = document.getElementById('fileInput');
        const receiverIdInput = document.getElementById('receiverId');
        const messageLoader = document.getElementById('messageLoader');

        if (sendButton) {
            sendButton.addEventListener('click', function() {
                const receiverId = receiverIdInput.value;
                const messageText = messageInput.value.trim();

                if (!messageText && !fileInput.files.length) {
                    return;
                }

                const formData = new FormData();
                formData.append('send_message', true);
                formData.append('receiver_id', receiverId);
                formData.append('message', messageText);
                if (fileInput.files.length) {
                    formData.append('file', fileInput.files[0]);
                }

                messageLoader.style.display = 'block';
                sendButton.disabled = true;

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        messageInput.value = '';
                        fileInput.value = '';
                        updateModalListeners();
                    } else {
                        showError('Error sending message: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error sending message:', error);
                    showError('Failed to send message: Network error');
                })
                .finally(() => {
                    messageLoader.style.display = 'none';
                    sendButton.disabled = false;
                });
            });
        }

        // Slide to reply with loader
        let startX, startY, isDragging = false;

        function handleSlideStart(event) {
            const message = event.target.closest('.message');
            if (!message || message.querySelector('.reply-form')) return;

            startX = event.type.includes('mouse') ? event.pageX : event.touches[0].pageX;
            startY = event.type.includes('mouse') ? event.pageY : event.touches[0].pageY;
            isDragging = true;
            message.style.transition = 'none';
        }

        function handleSlideMove(event) {
            if (!isDragging) return;
            const message = event.target.closest('.message');
            if (!message) return;

            const currentX = event.type.includes('mouse') ? event.pageX : event.touches[0].pageX;
            const currentY = event.type.includes('mouse') ? event.pageY : event.touches[0].pageY;
            const diffX = currentX - startX;
            const diffY = Math.abs(currentY - startY);

            if (diffY > Math.abs(diffX) || diffX < 0) {
                isDragging = false;
                message.style.transform = 'translateX(0)';
                return;
            }

            message.style.transform = `translateX(${diffX}px)`;
        }

        function handleSlideEnd(event) {
            if (!isDragging) return;
            const message = event.target.closest('.message');
            if (!message) return;

            isDragging = false;
            message.style.transition = 'transform 0.2s';
            const diffX = (event.type.includes('mouse') ? event.pageX : event.changedTouches[0].pageX) - startX;

            if (diffX > 50) {
                const messageId = message.dataset.messageId;
                const messageText = message.childNodes[2].textContent.trim();
                const sender = message.querySelector('.sender').textContent;

                const replyForm = document.createElement('div');
                replyForm.className = 'reply-form';
                replyForm.innerHTML = `
                    <p style="font-style: italic; color: #666;">Replying to ${sender}: "${messageText.substring(0, 50)}${messageText.length > 50 ? '...' : ''}"</p>
                    <textarea placeholder="Type your reply..." required></textarea>
                    <button type="button">Send Reply</button>
                    <div class="loader" id="replyLoader-${messageId}"></div>
                `;
                message.appendChild(replyForm);

                replyForm.addEventListener('click', function(event) {
                    event.stopPropagation();
                });

                replyForm.querySelector('textarea').addEventListener('focus', function(event) {
                    event.stopPropagation();
                    isDragging = false;
                });

                const replyButton = replyForm.querySelector('button');
                const replyLoader = replyForm.querySelector(`#replyLoader-${messageId}`);
                replyButton.onclick = function(event) {
                    event.stopPropagation();
                    const replyText = replyForm.querySelector('textarea').value.trim();
                    if (!replyText) {
                        showError('Reply cannot be empty');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('send_message', true);
                    formData.append('receiver_id', receiverIdInput.value);
                    formData.append('message', replyText);
                    formData.append('reply_to_id', messageId);

                    replyLoader.style.display = 'block';
                    replyButton.disabled = true;

                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            replyForm.remove();
                        } else {
                            showError('Error sending reply: ' + data.error);
                        }
                    })
                    .catch(error => {
                        showError('Network error: ' + error.message);
                    })
                    .finally(() => {
                        replyLoader.style.display = 'none';
                        replyButton.disabled = false;
                    });
                };

                document.addEventListener('click', function closeForm(event) {
                    if (!replyForm.contains(event.target) && event.target !== message) {
                        replyForm.remove();
                        document.removeEventListener('click', closeForm);
                    }
                });
            }
            message.style.transform = 'translateX(0)';
        }

        chatArea.addEventListener('touchstart', handleSlideStart, { passive: false });
        chatArea.addEventListener('touchmove', handleSlideMove, { passive: false });
        chatArea.addEventListener('touchend', handleSlideEnd);
        chatArea.addEventListener('mousedown', handleSlideStart);
        chatArea.addEventListener('mousemove', handleSlideMove);
        chatArea.addEventListener('mouseup', handleSlideEnd);

        // Double-tap/click to show emoji picker
        const emojiPicker = document.getElementById('emojiPicker');
        let tapCount = 0;
        let tapTimeout;

        function closeEmojiPicker() {
            emojiPicker.classList.remove('show');
            setTimeout(() => {
                emojiPicker.style.display = 'none';
            }, 200);
        }

        chatArea.addEventListener('click', function(event) {
            const message = event.target.closest('.message');
            if (!message) return;

            tapCount++;
            if (tapCount === 1) {
                tapTimeout = setTimeout(() => {
                    tapCount = 0;
                }, 300);
            } else if (tapCount === 2) {
                clearTimeout(tapTimeout);
                tapCount = 0;

                const rect = message.getBoundingClientRect();
                emojiPicker.style.left = `${rect.left}px`;
                emojiPicker.style.top = `${rect.bottom + window.scrollY}px`;
                emojiPicker.dataset.messageId = message.dataset.messageId;
                emojiPicker.style.display = 'block';
                emojiPicker.classList.add('show');

                document.addEventListener('click', function hidePicker(e) {
                    if (!emojiPicker.contains(e.target) && e.target !== message) {
                        closeEmojiPicker();
                        document.removeEventListener('click', hidePicker);
                    }
                }, { once: true });
            }
        });

        chatArea.addEventListener('touchend', function(event) {
            const message = event.target.closest('.message');
            if (!message) return;

            tapCount++;
            if (tapCount === 1) {
                tapTimeout = setTimeout(() => {
                    tapCount = 0;
                }, 300);
            } else if (tapCount === 2) {
                clearTimeout(tapTimeout);
                tapCount = 0;

                const rect = message.getBoundingClientRect();
                emojiPicker.style.left = `${rect.left}px`;
                emojiPicker.style.top = `${rect.bottom + window.scrollY}px`;
                emojiPicker.dataset.messageId = message.dataset.messageId;
                emojiPicker.style.display = 'block';
                emojiPicker.classList.add('show');

                document.addEventListener('touchend', function hidePicker(e) {
                    if (!emojiPicker.contains(e.target) && e.target !== message) {
                        closeEmojiPicker();
                        document.removeEventListener('touchend', hidePicker);
                    }
                }, { once: true });
            }
        });

        emojiPicker.addEventListener('click', function(event) {
            event.preventDefault();
            const emojiSpan = event.target.closest('span[data-emoji]');
            if (!emojiSpan) return;

            const emoji = emojiSpan.dataset.emoji;
            const messageId = this.dataset.messageId;

            if (!emoji || !messageId) {
                showError('Invalid emoji or message ID');
                return;
            }

            const formData = new FormData();
            formData.append('add_emoji', true);
            formData.append('message_id', messageId);
            formData.append('emoji', emoji);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const message = chatArea.querySelector(`.message[data-message-id="${messageId}"]`);
                    let reactionsDiv = message.querySelector('.message-reactions');
                    if (!reactionsDiv) {
                        reactionsDiv = document.createElement('div');
                        reactionsDiv.className = 'message-reactions';
                        message.appendChild(reactionsDiv);
                    }
                    const currentReactions = reactionsDiv.textContent || '';
                    if (!currentReactions.includes(emoji)) {
                        reactionsDiv.textContent = currentReactions + emoji;
                    }
                    closeEmojiPicker();
                } else {
                    showError('Error adding emoji: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error adding emoji:', error);
                showError('Network error: ' + error.message);
            });
        });

        // Incremental message and reaction refresh
        let lastMessageId = <?php echo !empty($messages) ? max(array_column($messages, 'id')) : 0; ?>;
        function refreshMessages() {
            const receiverId = receiverIdInput ? receiverIdInput.value : null;
            if (!receiverId) return;

            const formData = new FormData();
            formData.append('fetch_messages', true);
            formData.append('chat_user_id', receiverId);
            formData.append('last_message_id', lastMessageId);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const isAtBottom = chatArea.scrollTop + chatArea.clientHeight >= chatArea.scrollHeight - 10;

                    if (data.messages.length > 0) {
                        data.messages.forEach(message => {
                            const existingMessage = chatArea.querySelector(`.message[data-message-id="${message.id}"]`);
                            if (!existingMessage) {
                                const div = document.createElement('div');
                                div.className = `message ${message.sender_id == <?php echo $user_id; ?> ? 'sent' : 'received'}`;
                                div.dataset.messageId = message.id;
                                div.innerHTML = `
                                    <div class="sender">${message.sender_id == <?php echo $user_id; ?> ? 'You' : message.sender_username}</div>
                                    ${message.reply_to_id && message.reply_to_text ? `<div class="reply-to">Replying to ${message.reply_to_username}: "${message.reply_to_text.substring(0, 50)}${message.reply_to_text.length > 50 ? '...' : ''}"</div>` : ''}
                                    ${message.message_text}
                                    ${message.file_path ? (message.file_path.includes('.pdf') ? 
                                        `<div class="message-file"><a href="${message.file_path}" download>Download PDF</a></div>` : 
                                        `<div class="message-image"><img src="${message.file_path}" alt="Attachment" class="clickable-image"></div>`) : ''}
                                    <div class="status">${message.status}</div>
                                    ${message.reactions ? `<div class="message-reactions">${message.reactions}</div>` : ''}
                                `;
                                chatArea.appendChild(div);
                                lastMessageId = Math.max(lastMessageId, message.id);
                            }
                        });
                        updateModalListeners();
                    }

                    Object.keys(data.reactions).forEach(messageId => {
                        const message = chatArea.querySelector(`.message[data-message-id="${messageId}"]`);
                        if (message) {
                            let reactionsDiv = message.querySelector('.message-reactions');
                            const newReactions = data.reactions[messageId];
                            if (!reactionsDiv) {
                                reactionsDiv = document.createElement('div');
                                reactionsDiv.className = 'message-reactions';
                                message.appendChild(reactionsDiv);
                            }
                            if (reactionsDiv.textContent !== newReactions) {
                                reactionsDiv.textContent = newReactions;
                            }
                        }
                    });

                    if (isAtBottom) {
                        chatArea.scrollTop = chatArea.scrollHeight;
                    }
                } else {
                    showError('Error loading messages: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error fetching messages:', error);
                showError('Failed to load messages: ' + error.message);
            });
        }

        if (chatArea) {
            setInterval(refreshMessages, 5000);
            refreshMessages();
        }
    </script>
    <div class="back-button-container">
    <a href="dashboard.php" class="back-button" aria-label="Back to dashboard">←</a>
</div>

<style>
    .back-button-container {
        position: fixed;
        top: 50%;
        left: 30px;
        transform: translateY(-50%);
        z-index: 1000;
    }

    .back-button {
        width: 40px; /* Reduced from 60px */
        height: 40px; /* Reduced from 60px */
        border-radius: 50%;
        background: var(--primary); /* Uses your --primary color: #6a1b9a */
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px; /* Reduced from 24px */
        text-decoration: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .back-button:hover {
        background: var(--primary-dark); /* Uses your --primary-dark: #4a148c */
        transform: scale(1.1);
    }
</style>
</body>
</html>