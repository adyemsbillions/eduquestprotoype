<?php
// Database connection variables
$host = 'localhost';  // Database host (e.g., localhost or an IP address)
$username = 'unimaid9_unimaidresources';  // Database username
$password = '#adyems123AD';  // Database password
$database = 'unimaid9_unimaidresources';  // Database name

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
