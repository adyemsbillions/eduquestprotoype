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
$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
$buyer_id = isset($_GET['buyer_id']) ? (int)$_GET['buyer_id'] : 0;

// Check if the user is a seller viewing the dashboard
$is_seller_dashboard = ($post_id <= 0 && $buyer_id <= 0);

if ($is_seller_dashboard) {
    // Show list of buyers who messaged the seller's posts
    $sql_users = "SELECT DISTINCT u.id AS buyer_id, u.username, mp.post_id, mp.item_name
                  FROM saleschat sc
                  JOIN users u ON u.id = sc.sender_id
                  JOIN marketplace_posts mp ON mp.post_id = sc.post_id
                  WHERE mp.user_id = ? AND sc.sender_id != ?
                  ORDER BY sc.created_at DESC";
    $stmt_users = $conn->prepare($sql_users);
    $stmt_users->bind_param("ii", $user_id, $user_id);
    $stmt_users->execute();
    $users_result = $stmt_users->get_result();
} else {
    // Validate post_id and fetch post details
    if ($post_id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid or missing post_id']);
        exit();
    }

    // Fetch post details without ownership restriction initially
    $sql_post = "SELECT mp.post_id, mp.item_name, mp.description, mp.price, mp.images, mp.user_id AS seller_id, u.username 
                 FROM marketplace_posts mp 
                 JOIN users u ON mp.user_id = u.id 
                 WHERE mp.post_id = ?";
    $stmt_post = $conn->prepare($sql_post);
    $stmt_post->bind_param("i", $post_id);
    $stmt_post->execute();
    $post_result = $stmt_post->get_result();
    $post = $post_result->fetch_assoc();
    $stmt_post->close();

    if (!$post) {
        header('Content-Type: application/json');
        echo json_encode(['error' => "Post not found with post_id=$post_id"]);
        $conn->close();
        exit();
    }

    $seller_id = $post['seller_id'];
    // Determine the other user in the chat
    if ($user_id == $seller_id) {
        // Seller viewing chat with a buyer
        $other_user_id = $buyer_id;
    } else {
        // Buyer viewing chat with the seller
        $other_user_id = $seller_id;
    }

    if ($other_user_id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid or missing buyer_id for chat']);
        $conn->close();
        exit();
    }
}

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_seller_dashboard) {
    $message = trim($_POST['message'] ?? '');
    $image_path = '';

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/'; // Ensure this directory exists and is writable
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $upload_file = $upload_dir . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_file)) {
            $image_path = $upload_file;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to upload image']);
            exit();
        }
    }

    if (empty($message) && empty($image_path)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Message or image cannot be empty']);
        exit();
    }

    $receiver_id = $other_user_id;
    $sql_insert = "INSERT INTO saleschat (sender_id, receiver_id, post_id, message, image_path, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("iiiss", $user_id, $receiver_id, $post_id, $message, $image_path);
    if (!$stmt_insert->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Insert failed: ' . $stmt_insert->error]);
        $stmt_insert->close();
        $conn->close();
        exit();
    }
    $stmt_insert->close();

    // Fetch updated messages
    $sql_messages = "SELECT m.message, m.image_path, m.created_at, m.sender_id, u.username 
                     FROM saleschat m 
                     JOIN users u ON m.sender_id = u.id 
                     WHERE m.post_id = ? AND ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)) 
                     ORDER BY m.created_at ASC";
    $stmt_messages = $conn->prepare($sql_messages);
    $stmt_messages->bind_param("iiiii", $post_id, $user_id, $other_user_id, $other_user_id, $user_id);
    $stmt_messages->execute();
    $result = $stmt_messages->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'message' => htmlspecialchars($row['message']),
            'image_path' => $row['image_path'],
            'created_at' => $row['created_at'],
            'sender_id' => (int)$row['sender_id'],
            'username' => htmlspecialchars($row['username'])
        ];
    }
    $stmt_messages->close();

    header('Content-Type: application/json');
    echo json_encode($messages);
    $conn->close();
    exit();
}

// Handle AJAX fetch request
if (isset($_GET['fetch']) && $_GET['fetch'] === 'messages' && !$is_seller_dashboard) {
    $sql_messages = "SELECT m.message, m.image_path, m.created_at, m.sender_id, u.username 
                     FROM saleschat m 
                     JOIN users u ON m.sender_id = u.id 
                     WHERE m.post_id = ? AND ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)) 
                     ORDER BY m.created_at ASC";
    $stmt_messages = $conn->prepare($sql_messages);
    $stmt_messages->bind_param("iiiii", $post_id, $user_id, $other_user_id, $other_user_id, $user_id);
    $stmt_messages->execute();
    $result = $stmt_messages->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'message' => htmlspecialchars($row['message']),
            'image_path' => $row['image_path'],
            'created_at' => $row['created_at'],
            'sender_id' => (int)$row['sender_id'],
            'username' => htmlspecialchars($row['username'])
        ];
    }
    $stmt_messages->close();

    header('Content-Type: application/json');
    echo json_encode($messages);
    $conn->close();
    exit();
}

// Initial page load - Fetch messages for display if chat is selected
if (!$is_seller_dashboard) {
    $sql_messages = "SELECT m.message, m.image_path, m.created_at, m.sender_id, u.username 
                     FROM saleschat m 
                     JOIN users u ON m.sender_id = u.id 
                     WHERE m.post_id = ? AND ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)) 
                     ORDER BY m.created_at ASC";
    $stmt_messages = $conn->prepare($sql_messages);
    $stmt_messages->bind_param("iiiii", $post_id, $user_id, $other_user_id, $other_user_id, $user_id);
    $stmt_messages->execute();
    $messages_result = $stmt_messages->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_seller_dashboard ? 'Seller Messages' : 'Chat with ' . ($user_id == $seller_id ? 'Buyer' : 'Seller'); ?></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <style>
  /* Modal Background */
.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    padding-top: 60px;
    left: 0; top: 0;
    width: 100%; height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.7);
}

/* Modal Image Container */
.modal-content {
    margin: auto;
    display: block;
    max-width: 80%;
    max-height: 80%;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.5);
}

/* Close Button */
.modal-close {
    position: absolute;
    top: 30px; right: 35px;
    color: white;
    font-size: 35px;
    font-weight: bold;
    cursor: pointer;
}

.modal-close:hover,
.modal-close:focus {
    color: #bbb;
    text-decoration: none;
    cursor: pointer;
}

@media (max-width: 700px) {
    .modal-content {
        max-width: 95%;
    }
}
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #eef2f7;
    color: #333;
    margin: 0;
    padding: 20px;
}

.chat-container {
    max-width: 800px;
    margin: auto;
    background-color: #fff;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    border-radius: 10px;
    padding: 25px;
}

h2 {
    color: royalblue;
    margin-bottom: 15px;
    border-bottom: 2px solid royalblue;
    padding-bottom: 10px;
}

.user-list p, .post-details p {
    font-size: 16px;
    color: #555;
    margin: 8px 0;
}

.user-list a {
    color: royalblue;
    font-weight: 600;
    text-decoration: none;
}

.user-list a:hover {
    text-decoration: underline;
}

.post-details .image-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 12px;
    margin-top: 15px;
}

.post-details .image-grid img {
    width: 100%;
    height: auto;
    border-radius: 8px;
    transition: transform 0.2s ease;
}

.post-details .image-grid img:hover {
    transform: scale(1.05);
}

.chat-messages {
    max-height: 400px;
    overflow-y: auto;
    padding: 15px;
    background-color: #f9fbfd;
    border-radius: 8px;
    border: 1px solid #dfe7ef;
    margin-top: 15px;
}

.message {
    margin-bottom: 12px;
    padding: 10px 15px;
    border-radius: 8px;
    position: relative;
    max-width: 70%;
    clear: both;
    word-wrap: break-word;
}

.message.sent {
    background-color: royalblue;
    color: #fff;
    margin-left: auto;
}

.message.received {
    background-color: #e4e8ee;
    color: #333;
    margin-right: auto;
}

.message .time {
    font-size: 11px;
    color: rgba(255,255,255,0.8);
    margin-top: 6px;
    text-align: right;
}

.message.received .time {
    color: #555;
}

.message img {
    max-width: 100%;
    border-radius: 6px;
    margin-top: 8px;
}

.chat-input {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    align-items: center;
}

.chat-input textarea {
    flex: 1;
    padding: 10px;
    border: 1px solid #d0d8e0;
    border-radius: 8px;
    resize: none;
    outline: none;
    font-size: 14px;
}

.chat-input button {
    background-color: royalblue;
    color: #fff;
    border: none;
    padding: 10px 15px;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.chat-input button:hover {
    background-color: #4169e1;
}

::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-thumb {
    background-color: #c1c9d2;
    border-radius: 10px;
}

::-webkit-scrollbar-track {
    background-color: #eef2f7;
}

@media (max-width: 768px) {
    .chat-container {
        padding: 15px;
    }

    .chat-input {
        flex-direction: column;
        align-items: stretch;
    }

    .chat-input button {
        width: 100%;
    }

    .message {
        max-width: 100%;
    }
}

   </style>
</head>
<body>
    <div class="chat-container">
        <?php if ($is_seller_dashboard): ?>
            <div class="user-list">
                <h2>Your Messages</h2>
                <?php if ($users_result->num_rows > 0): ?>
                    <?php while ($user = $users_result->fetch_assoc()): ?>
                        <p>
                            <strong><?php echo htmlspecialchars($user['username']); ?></strong> about 
                            <em><?php echo htmlspecialchars($user['item_name']); ?></em>
                            <a href="saleschat.php?post_id=<?php echo $user['post_id']; ?>&buyer_id=<?php echo $user['buyer_id']; ?>">View Chat</a>
                        </p>
                    <?php endwhile; ?>
                    <?php $stmt_users->close(); ?>
                <?php else: ?>
                    <p>No messages yet.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="post-details">
                <h2><?php echo htmlspecialchars($post['item_name']); ?></h2>
                <p><strong>Seller:</strong> <?php echo htmlspecialchars($post['username']); ?></p>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($post['description']); ?></p>
                <p><strong>Price:</strong> ₦<?php echo number_format($post['price'], 2); ?></p>
                <div class="image-grid">
                  <?php
$images = json_decode($post['images'], true);
if ($images) {
    foreach ($images as $image_path) {
        echo '<img src="' . htmlspecialchars($image_path) . '" alt="Item Image" class="popup-image">';
    }
}
?>

                </div>
            </div>
            <div class="chat-messages" id="chat-messages">
                <?php while ($msg = $messages_result->fetch_assoc()): ?>
                    <div class="message <?php echo $msg['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                        <p><strong><?php echo htmlspecialchars($msg['username']); ?>:</strong> <?php echo htmlspecialchars($msg['message']); ?></p>
                        <?php if (!empty($msg['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($msg['image_path']); ?>" alt="Chat Image">
                        <?php endif; ?>
                        <div class="time"><?php echo $msg['created_at']; ?></div>
                    </div>
                <?php endwhile; ?>
                <?php $stmt_messages->close(); ?>
            </div>
            <div class="chat-input">
                <textarea id="message-input" placeholder="Type your message..." rows="3"></textarea>
                <input type="file" id="image-input" accept="image/*" style="display: none;">
                <button onclick="document.getElementById('image-input').click()">Upload Image</button>
                <button onclick="sendMessage()">Send</button>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!$is_seller_dashboard): ?>
    <script>
        var lastMessageCount = 0;

        function sendMessage() {
            var message = $('#message-input').val().trim();
            var imageInput = document.getElementById('image-input');
            var imageFile = imageInput.files[0];

            if (!message && !imageFile) return;

            var formData = new FormData();
            if (message) {
                formData.append('message', message);
            }
            if (imageFile) {
                formData.append('image', imageFile);
            }

            $.ajax({
                url: 'saleschat.php?post_id=<?php echo $post_id; ?>&buyer_id=<?php echo $buyer_id; ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (Array.isArray(response)) {
                        updateChat(response);
                        $('#message-input').val('');
                        $('#image-input').val('');
                        fetchMessages();
                    } else {
                        console.error('Invalid send response:', response);
                        alert('Message sent but response invalid: ' + (response.error || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Send error:', status, error, xhr.responseText);
                    alert('Failed to send message: ' + (xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Unknown error'));
                }
            });
        }

        function fetchMessages() {
            $.ajax({
                url: 'saleschat.php?post_id=<?php echo $post_id; ?>&buyer_id=<?php echo $buyer_id; ?>&fetch=messages',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (Array.isArray(response)) {
                        if (response.length !== lastMessageCount) {
                            updateChat(response);
                            lastMessageCount = response.length;
                        }
                    } else {
                        console.error('Invalid fetch response:', response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Fetch error:', status, error, xhr.responseText);
                }
            });
        }

        function updateChat(messages) {
            var chat = $('#chat-messages');
            chat.empty();
            messages.forEach(function(msg) {
                var isSent = msg.sender_id == <?php echo $user_id; ?>;
                chat.append(`
                    <div class="message ${isSent ? 'sent' : 'received'}">
                        <p><strong>${msg.username}:</strong> ${msg.message}</p>
                        ${msg.image_path ? `<img src="${msg.image_path}" alt="Chat Image">` : ''}
                        <div class="time">${msg.created_at}</div>
                    </div>`);
            });
            scrollToBottom();
        }

        function scrollToBottom() {
            var chat = $('#chat-messages');
            chat.scrollTop(chat.prop('scrollHeight'));
        }

        fetchMessages();
        setInterval(fetchMessages, 2000);
        scrollToBottom();

        $('#message-input').on('keypress', function(e) {
            if (e.which == 13 && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    </script>
    <?php endif; ?>
    <!-- Modal Popup -->
<div id="imageModal" class="modal">
    <span class="modal-close">&times;</span>
    <img class="modal-content" id="modalImage">
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    const modalClose = document.querySelector('.modal-close');
    
    document.querySelectorAll('.popup-image').forEach(img => {
        img.addEventListener('click', function() {
            modal.style.display = 'block';
            modalImg.src = this.src;
        });
    });

    modalClose.onclick = function() {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }
});
</script>

</body>
</html>

<?php $conn->close(); ?>
