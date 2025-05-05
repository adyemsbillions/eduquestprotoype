<?php
// Include the database connection
include("db_connection.php");

// Start session to get the logged-in user's ID
session_start();



// Fetch loan requests along with user data (full_name) from the users table
$sql = "SELECT lr.id, lr.loan_amount, lr.status, lr.request_date, lr.next_payment_date, lr.amount_to_pay, u.username, u.account_name, u.account_number, u.bank_name
        FROM loan_requests lr
        JOIN users u ON lr.user_id = u.id
        ORDER BY lr.request_date DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$loan_requests = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Loan Requests</title>
    <style>
    /* Add your styles for the admin page */
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f4f7f6;
    }

    .container {
        width: 80%;
        margin: 0 auto;
        max-width: 900px;
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        margin-top: 40px;
    }

    h1 {
        text-align: center;
    }

    .loan-history {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .loan-history-item {
        display: flex;
        justify-content: space-between;
        background-color: #f4f7f6;
        padding: 15px;
        border-radius: 6px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .loan-history-item div {
        flex: 1;
    }

    .loan-history-item button {
        padding: 5px 10px;
        cursor: pointer;
        border-radius: 4px;
    }

    .approve-btn {
        background-color: green;
        color: white;
    }

    .reject-btn {
        background-color: red;
        color: white;
    }
    </style>
</head>

<body>

    <div class="container">
        <h1>Admin - Loan Requests</h1>

        <div class="loan-history">
            <?php while ($loan_request = $loan_requests->fetch_assoc()): ?>
            <div class="loan-history-item">
                <div>
                    <strong>User Name:</strong> <?php echo htmlspecialchars($loan_request['username']); ?><br>
                    <strong>Account Name:</strong> <?php echo htmlspecialchars($loan_request['account_name']); ?><br>
                    <strong>Account Number:</strong>
                    <?php echo htmlspecialchars($loan_request['account_number']); ?><br>
                    <strong>Bank Name:</strong> <?php echo htmlspecialchars($loan_request['bank_name']); ?><br>
                    <strong>Loan Amount:</strong> <?php echo htmlspecialchars($loan_request['loan_amount']); ?><br>
                    <strong>Amount to Pay:</strong> <?php echo htmlspecialchars($loan_request['amount_to_pay']); ?><br>
                    <strong>Next Payment Date:</strong>
                    <?php echo htmlspecialchars($loan_request['next_payment_date']); ?><br>
                    <strong>Status:</strong> <?php echo htmlspecialchars($loan_request['status']); ?><br>
                </div>

                <div>
                    <!-- Approve and Reject Buttons -->
                    <?php if ($loan_request['status'] == 'Pending'): ?>
                    <form action="approve_reject_request.php" method="POST">
                        <input type="hidden" name="loan_request_id" value="<?php echo $loan_request['id']; ?>">
                        <button type="submit" name="approve" class="approve-btn">Approve</button>
                        <button type="submit" name="reject" class="reject-btn">Reject</button>
                    </form>
                    <?php else: ?>
                    <p>Status: <?php echo $loan_request['status']; ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

    </div>

</body>

</html>