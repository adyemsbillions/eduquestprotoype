<?php
// Include the database connection
include("db_connection.php");

// Start session for admin login
session_start();


// Check if form is submitted for approval or rejection
if (isset($_POST['loan_request_id'])) {
    $loan_request_id = $_POST['loan_request_id'];
    $status = '';

    // Check if approve or reject button was clicked
    if (isset($_POST['approve'])) {
        $status = 'Approved';
    } elseif (isset($_POST['reject'])) {
        $status = 'Rejected';
    }

    // Update the loan request status
    if ($status) {
        $sql = "UPDATE loan_requests SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $loan_request_id);

        if ($stmt->execute()) {
            echo "Loan request has been " . $status . "!";
            header("Location: loan_requests.php");  // Redirect back to the loan requests page
        } else {
            echo "Error updating loan request: " . $stmt->error;
        }
    }
}