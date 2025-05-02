<?php
// Assuming the user is logged in and the user ID is stored in session
$userId = $_SESSION['user_id']; // Logged-in user

// Group ID passed as a GET parameter
$groupId = $_GET['group_id']; 

// Database connection
$conn = new mysqli('localhost', 'root', '', 'your_database_name');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Insert into group_members table
$sql = "INSERT INTO group_members (group_id, user_id, joined_at) 
        VALUES ('$groupId', '$userId', NOW())";

if ($conn->query($sql) === TRUE) {
    echo "You have successfully joined the group!";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
