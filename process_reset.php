<?php
session_start();
require 'db_connection.php';

// Set timezone for consistency
date_default_timezone_set('Africa/Lagos');
$conn->query("SET time_zone = '+01:00'");

// Log request start
error_log("process_reset.php called at " . date('Y-m-d H:i:s'));

// Check if form data and session variables are set
if (!isset($_POST['token'], $_POST['password'], $_POST['confirm_password'], $_POST['csrf_token'], $_SESSION['reset_token'], $_SESSION['reset_user_id'], $_SESSION['csrf_token'])) {
    error_log("Missing required data: POST token=" . (isset($_POST['token']) ? 'set' : 'unset') . 
              ", password=" . (isset($_POST['password']) ? 'set' : 'unset') . 
              ", confirm_password=" . (isset($_POST['confirm_password']) ? 'set' : 'unset') . 
              ", csrf_token=" . (isset($_POST['csrf_token']) ? 'set' : 'unset') . 
              ", SESSION token=" . (isset($_SESSION['reset_token']) ? 'set' : 'unset') . 
              ", SESSION user_id=" . (isset($_SESSION['reset_user_id']) ? 'set' : 'unset') . 
              ", SESSION csrf_token=" . (isset($_SESSION['csrf_token']) ? 'set' : 'unset'));
    header("Location: reset_password.php?error=invalid_request&token=" . urlencode($_POST['token'] ?? ''));
    exit();
}

$token = $_POST['token'];
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$csrf_token = $_POST['csrf_token'];
$session_token = $_SESSION['reset_token'];
$user_id = $_SESSION['reset_user_id'];

error_log("Received token: $token, user ID: $user_id, CSRF token: $csrf_token");

// Validate CSRF token
if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    error_log("CSRF token mismatch: POST csrf_token=$csrf_token, SESSION csrf_token=" . ($_SESSION['csrf_token'] ?? 'unset'));
    header("Location: reset_password.php?error=csrf_token_mismatch&token=" . urlencode($token));
    exit();
}

// Validate token format and session
if (!preg_match('/^[0-9a-fA-F]{64}$/', $token) || $token !== $session_token) {
    error_log("Invalid or mismatched token: POST token=$token, SESSION token=$session_token");
    header("Location: reset_password.php?error=invalid_token&token=" . urlencode($token));
    exit();
}

// Validate passwords
if ($password !== $confirm_password) {
    error_log("Passwords do not match");
    header("Location: reset_password.php?error=passwords_dont_match&token=" . urlencode($token));
    exit();
}

// Basic password strength check (minimum 8 characters)
if (strlen($password) < 8) {
    error_log("Password too short: length=" . strlen($password));
    header("Location: reset_password.php?error=password_too_short&token=" . urlencode($token));
    exit();
}

// Verify token exists in database
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND reset_token = ?");
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    header("Location: reset_password.php?error=database_error&token=" . urlencode($token));
    exit();
}
$stmt->bind_param("is", $user_id, $token);
if (!$stmt->execute()) {
    error_log("Token query execution failed: " . $stmt->error);
    header("Location: reset_password.php?error=database_error&token=" . urlencode($token));
    exit();
}
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    error_log("Token not found for user ID: $user_id, token: $token");
    header("Location: reset_password.php?error=invalid_token&token=" . urlencode($token));
    exit();
}

// Hash the new password
$password_hash = password_hash($password, PASSWORD_BCRYPT);
error_log("Password hashed successfully");

// Update password and clear reset token
$stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ? AND reset_token = ?");
if (!$stmt) {
    error_log("Prepare failed for update: " . $conn->error);
    header("Location: reset_password.php?error=database_error&token=" . urlencode($token));
    exit();
}
$stmt->bind_param("sis", $password_hash, $user_id, $token);
if ($stmt->execute()) {
    if ($stmt->affected_rows === 1) {
        error_log("Password updated and token cleared for user ID: $user_id");
        // Clear session data
        unset($_SESSION['reset_token']);
        unset($_SESSION['reset_user_id']);
        unset($_SESSION['csrf_token']); // Clear CSRF token for security
        header("Location: index.php?reset=success");
        exit();
    } else {
        error_log("No rows affected for integrador ID: $user_id, token: $token");
        header("Location: reset_password.php?error=update_failed&token=" . urlencode($token));
        exit();
    }
} else {
    error_log("Update query failed: " . $stmt->error);
    header("Location: reset_password.php?error=update_failed&token=" . urlencode($token));
    exit();
}

$stmt->close();
$conn->close();
?>