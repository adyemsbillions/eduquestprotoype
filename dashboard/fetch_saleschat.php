<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User not logged in']);
    exit();
}

$conn = new mysqli("localhost", "unimaid9_unimaidresources", "#adyems123AD", "unimaid9_unimaidresources");
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
$seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;
$user_id = (int)$_SESSION['user_id'];

if ($post_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid post_id']);
    exit();
}

// Determine the other party
$sql_post = "SELECT user_id AS seller_id FROM marketplace_posts WHERE post_id = ?";
$stmt_post = $conn->prepare($sql_post);
$stmt_post->bind_param("i", $post_id);
$stmt_post->execute();
$post_result = $stmt_post->get_result();
$post = $post_result->fetch_assoc();
$actual_seller_id = $post['seller_id'];
$other_user_id = ($user_id == $actual_seller_id) ? $seller_id : $actual_seller_id;

// Debug output
echo "<!-- Debug: user_id=$user_id, actual_seller_id=$actual_seller_id, other_user_id=$other_user_id, post_id=$post_id -->";

// Fetch messages
$sql = "SELECT m.message, m.created_at, m.sender_id, m.receiver_id, u.username 
        FROM saleschat m 
        JOIN users u ON m.sender_id = u.id 
        WHERE m.post_id = ? AND ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)) 
        ORDER BY m.created_at ASC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'SQL prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("iiiii", $post_id, $user_id, $other_user_id, $other_user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'message' => htmlspecialchars($row['message']),
        'created_at' => $row['created_at'],
        'sender_id' => (int)$row['sender_id'],
        'receiver_id' => (int)$row['receiver_id'], // Include receiver_id for debugging
        'username' => htmlspecialchars($row['username'])
    ];
}

// Debug: Output fetched messages
echo "<!-- Debug: Fetched " . count($messages) . " messages -->";
foreach ($messages as $msg) {
    echo "<!-- Debug: Message - sender_id={$msg['sender_id']}, receiver_id={$msg['receiver_id']}, message={$msg['message']} -->";
}

header('Content-Type: application/json');
echo json_encode($messages);

$stmt->close();
$conn->close();
?>