<?php
session_start();
include 'db_connection.php';

$user_id = session_id();
$username = isset($_POST['username']) && !empty($_POST['username']) ? $_POST['username'] : 'Guest';
$message = $_POST['message'] ?? '';
$image_path = null;

if (!empty($_FILES['image']['name'])) {
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    $image_path = $target_dir . basename($_FILES['image']['name']);
    move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
}

$stmt = $conn->prepare("INSERT INTO customercare (user_id, username, message, image_path) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $user_id, $username, $message, $image_path);
$stmt->execute();

// Auto-response logic
$common_queries = ["Order Status", "Return Request", "Product Information", "Technical Support", "Billing Issue"];
$auto_responses = [
    "Order Status" => "Please provide your order number and I'll check the status for you.",
    "Return Request" => "I can help you with a return. Please provide your order details.",
    "Product Information" => "What product would you like information about?",
    "Technical Support" => "Please describe your technical issue, and I'll assist you.",
    "Billing Issue" => "Please tell me more about your billing concern."
];

if (in_array($message, $common_queries)) {
    $response = $auto_responses[$message] . " Would you like to speak with a human agent instead? (Yes/No)";
    $stmt = $conn->prepare("INSERT INTO customercare (user_id, username, message, is_bot) VALUES (?, ?, ?, 1)");
    $stmt->bind_param("sss", $user_id, $username, $response);
    $stmt->execute();
} elseif (strtolower($message) === "yes") {
    $response = "Connecting you to a human agent. Please wait...";
    $stmt = $conn->prepare("INSERT INTO customercare (user_id, username, message, is_bot) VALUES (?, ?, ?, 1)");
    $stmt->bind_param("sss", $user_id, $username, $response);
    $stmt->execute();
}

$conn->close();
?>