<?php
// Database connection variables
$host = 'localhost';  // Database host (e.g., localhost or an IP address)
$username = 'root';  // Database username
$password = '';  // Database password
$database = 'eduquest';  // Database name

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}