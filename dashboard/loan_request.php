<?php
// Include the database connection
include("db_connection.php");

// Start session to get user_id
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login if not logged in
    header("Location: login.php");
    exit();
}

// Get the logged-in user's ID
$user_id = $_SESSION['user_id'];

// Fetch user information along with account details
$sql = "SELECT id, full_name, account_name, account_number, bank_name FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if user details are found
if (!$user) {
    echo "User not found. Please contact support.";
    exit();
}

// Check if loan request exists for the user
$sql = "SELECT * FROM loan_requests WHERE user_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$loan_request = $stmt->get_result()->fetch_assoc();

// If form is submitted to save account details
if (isset($_POST['save_account_details'])) {
    $account_name = $_POST['account_name'];
    $account_number = $_POST['account_number'];
    $bank_name = $_POST['bank_name'];

    // Update account details in the users table
    $sql = "UPDATE users SET account_name = ?, account_number = ?, bank_name = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $account_name, $account_number, $bank_name, $user_id);

    if ($stmt->execute()) {
        echo "Account details updated successfully!";
    } else {
        echo "Error updating account details: " . $stmt->error;
    }
}

// If form is submitted to request loan
if (isset($_POST['request_loan'])) {
    $loan_amount = $_POST['loan_amount'];
    $amount_to_pay = $loan_amount + ($loan_amount * 0.40); // Calculate loan amount + 40%

    // Calculate next payment date (7 days interval)
    $next_payment_date = date('Y-m-d H:i:s', strtotime('+7 days'));

    // Insert loan request into loan_requests table with 'Pending' status
    $sql = "INSERT INTO loan_requests (user_id, loan_amount, status, next_payment_date, amount_to_pay) 
            VALUES (?, ?, 'Pending', ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idss", $user_id, $loan_amount, $next_payment_date, $amount_to_pay);
    
    if ($stmt->execute()) {
        echo "Loan request submitted successfully!";
    } else {
        echo "Error submitting loan request: " . $stmt->error;
    }
}

// Fetch loan request history
$sql = "SELECT * FROM loan_requests WHERE user_id = ? ORDER BY request_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$requests_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Request</title>
    <style>
        /* General Styles */
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

        h1, h2 {
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        input[type="text"], select {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }

        button[type="submit"] {
            background-color: purple;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        button[type="submit"]:hover {
            background-color: purple;
        }

        /* Flexbox Layout */
        .flex-container {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        .account-details, .loan-history {
            flex: 1;
            min-width: 300px; /* Make sure the sections take full width on small screens */
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

        .amount-display {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .container {
                width: 90%;
            }

            .flex-container {
                flex-direction: column;
            }
        }

    </style>
</head>
<body>

    <div class="container">
        <h1>Loan Request</h1>

        <?php if ($user['account_name']): ?>
            <div class="flex-container">
                <!-- Display Account Details -->
                <div class="account-details">
                    <h2>Your Account Details</h2>
                    <p><strong>Account Name:</strong> <?php echo htmlspecialchars($user['account_name']); ?></p>
                    <p><strong>Account Number:</strong> <?php echo htmlspecialchars($user['account_number']); ?></p>
                    <p><strong>Bank Name:</strong> <?php echo htmlspecialchars($user['bank_name']); ?></p>
                </div>
        <?php else: ?>
            <!-- Account Details Form -->
            <div class="account-details">
                <h2>Enter Account Details</h2>
                <form action="loan_request.php" method="POST">
                    <div class="form-group">
                        <label for="account_name">Account Name:</label>
                        <input type="text" name="account_name" required>
                    </div>

                    <div class="form-group">
                        <label for="account_number">Account Number:</label>
                        <input type="text" name="account_number" required>
                    </div>

                    <div class="form-group">
                        <label for="bank_name">Bank Name:</label>
                        <select name="bank_name" required>
                            <option value="Bank A">Bank A</option>
                            <option value="Bank B">Bank B</option>
                            <option value="Bank C">Bank C</option>
                        </select>
                    </div>

                    <button type="submit" name="save_account_details">Save Account Details</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($loan_request): ?>
            <!-- Loan Request Form -->
            <div class="loan-request-form">
                <h2>Request Loan</h2>
                <form action="loan_request.php" method="POST">
                    <div class="form-group">
                        <label for="loan_amount">Select Loan Amount:</label>
                        <select name="loan_amount" required>
                            <option value="1000">1000</option>
                            <option value="2000">2000</option>
                            <option value="5000">5000</option>
                            <option value="10000">10000</option>
                        </select>
                    </div>

                    <div class="amount-display">
                        <div>
                            <label for="loan_amount">Loan Amount:</label>
                            <span id="selected_amount">0</span>
                        </div>
                        <div>
                            <label for="amount_to_pay">Amount to Pay:</label>
                            <span id="amount_to_pay">0</span>
                        </div>
                    </div>

                    <button type="submit" name="request_loan">Request Loan</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Loan Request History -->
        <div class="loan-history">
            <h2>Your Loan Request History</h2>
            <?php while ($request = $requests_result->fetch_assoc()): ?>
                <div class="loan-history-item">
                    <div><strong>Loan Amount:</strong> <?php echo htmlspecialchars($request['loan_amount']); ?></div>
                    <div><strong>Status:</strong> <?php echo htmlspecialchars($request['status']); ?></div>
                    <div><strong>Next Payment Date:</strong> <?php echo htmlspecialchars($request['next_payment_date']); ?></div>
                </div>
            <?php endwhile; ?>
        </div>

    </div>

    <script>
        // JavaScript to calculate Amount to Pay (Loan Amount + 40%)
        const loanAmountSelect = document.querySelector('select[name="loan_amount"]');
        const selectedAmountSpan = document.getElementById('selected_amount');
        const amountToPaySpan = document.getElementById('amount_to_pay');

        loanAmountSelect.addEventListener('change', function() {
            const selectedAmount = parseFloat(loanAmountSelect.value);
            if (!isNaN(selectedAmount)) {
                const amountToPay = selectedAmount + (selectedAmount * 0.40); // Loan amount + 40% of loan amount
                selectedAmountSpan.textContent = selectedAmount.toFixed(2); // Display the selected amount
                amountToPaySpan.textContent = amountToPay.toFixed(2); // Display the calculated amount
            }
        });
    </script>

</body>
</html>
