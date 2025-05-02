<?php
session_start();
include 'db_connection.php';

if (isset($_GET['id'])) {
    $wcw_id = (int)$_GET['id'];
    // Update vote count in database
    $sql = "UPDATE wcw_images SET votes = votes + 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $wcw_id);
    if ($stmt->execute()) {
        // Add WCW ID to voted list
        if (!isset($_SESSION['voted_wcw'])) {
            $_SESSION['voted_wcw'] = [];
        }
        $_SESSION['voted_wcw'][] = $wcw_id;
        header("Location: wcw_vote.php?success=Vote recorded successfully!");
    } else {
        header("Location: wcw_vote.php?error=Failed to record vote.");
    }
    $stmt->close();
} else {
    header("Location: vote.php?error=Invalid WCW ID.");
}
$conn->close();
exit();
?>