<?php
$conn = new mysqli("localhost", "root", "", "eduquest");
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error . " (Error Code: " . $conn->connect_errno . ")");
    die("Connection failed. Please try again later.");
}
$conn->set_charset("utf8mb4");