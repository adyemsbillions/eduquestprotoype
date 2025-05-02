<?php
require_once 'db_connection.php';
session_start();

if (!isset($_GET['token'])) {
    header("Location: index.php?error=invalid_token");
    exit();
}

$token = $conn->real_escape_string($_GET['token']);
$stmt = $conn->prepare("SELECT id, token_expires FROM users WHERE verification_token = ? AND email_verified = FALSE");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $current_time = date('Y-m-d H:i:s');
    if ($user['token_expires'] > $current_time) {
        $stmt = $conn->prepare("UPDATE users SET email_verified = TRUE, verification_token = NULL, token_expires = NULL WHERE id = ?");
        $stmt->bind_param("i", $user['id']);
        if ($stmt->execute()) {
            header("Location: index.php?verify=success");
        } else {
            header("Location: index.php?error=database_error");
        }
    } else {
        header("Location: index.php?error=token_expired");
    }
} else {
    header("Location: index.php?error=invalid_token");
}

$conn->close();
?>