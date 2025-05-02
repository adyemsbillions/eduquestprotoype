<?php
$host = "localhost";
$username = "unimaid9_unimaidresources";
$password = "#adyems123AD";
$dbname = "unimaid9_unimaidresources";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>