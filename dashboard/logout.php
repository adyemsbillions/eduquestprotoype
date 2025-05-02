<?php
session_start();
include('db_connection.php');

if (isset($_SESSION['user_id']) && isset($_SESSION['login_start'])) {
    $user_id = $_SESSION['user_id'];
    $active_time = time() - $_SESSION['login_start'];
    $update_query = "UPDATE users SET total_active_time = total_active_time + $active_time WHERE id = $user_id";
    $conn->query($update_query);
}

session_destroy();
header("Location: ../index.php");
exit();
?>