<?php
session_start(); // Start the session to access user info

if (!isset($_SESSION['user_id'])) {
    echo "<p class='error'>Please log in to access this page.</p>";
    exit;
}

$user_id = $_SESSION['user_id'];

include('db_connection.php'); // Assuming the DB connection file

// Query to check if the user is verified and their gender
$sql = "SELECT verified, gender FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($verified, $gender);
$stmt->fetch();
$stmt->close();

// Normalize the gender value
$gender = strtolower(trim($gender));

// Check if payment receipt has been uploaded
$receipt_uploaded = false;
$upload_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['receipt'])) {
    $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
    $file_type = $_FILES['receipt']['type'];
    if (in_array($file_type, $allowed_types) && $_FILES['receipt']['error'] == 0) {
        $file_name = uniqid() . '_' . basename($_FILES['receipt']['name']);
        $upload_dir = 'uploads/receipts/';
        $file_path = $upload_dir . $file_name;

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); // Create directory if it doesn't exist
        }

        if (move_uploaded_file($_FILES['receipt']['tmp_name'], $file_path)) {
            // Save receipt info to the database
            $sql = "INSERT INTO verification_receipts (user_id, receipt_path, status) VALUES (?, ?, 'pending')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $user_id, $file_path);
            $stmt->execute();
            $stmt->close();
            $receipt_uploaded = true;
            $upload_message = "<p class='success'>Receipt uploaded successfully! Awaiting admin approval.</p>";
        } else {
            $upload_message = "<p class='error'>Error uploading receipt. Please try again.</p>";
        }
    } else {
        $upload_message = "<p class='error'>Invalid file format. Only JPEG, PNG, and PDF files are allowed.</p>";
    }
}

// Check if the user has an approved receipt
$sql_check = "SELECT status FROM verification_receipts WHERE user_id = ? AND status = 'approved'";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $user_id);
$stmt_check->execute();
$stmt_check->store_result();
$has_approved_receipt = $stmt_check->num_rows > 0;
$stmt_check->close();

// Handle verification request
$request_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_verification']) && $has_approved_receipt) {
    $sql_update = "UPDATE users SET black_verified = 1 WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("i", $user_id);
    if ($stmt_update->execute()) {
        $request_message = "<p class='success'>Black Verification requested successfully!</p>";
    } else {
        $request_message = "<p class='error'>Error requesting verification. Please try again.</p>";
    }
    $stmt_update->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Black Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            flex-direction: column;
            min-height: 100vh;
            box-sizing: border-box;
        }

        .container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 400px;
            width: 100%;
            text-align: center;
            box-sizing: border-box;
            margin: 20px auto;
        }

        .header {
            color: #212121;
            font-size: 28px;
            margin-bottom: 20px;
            word-wrap: break-word;
        }

        .error {
            background-color: #ffcccc;
            color: #cc0000;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            font-size: 16px;
            word-wrap: break-word;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            font-size: 16px;
            word-wrap: break-word;
        }

        .verification-form {
            margin-top: 20px;
            display: block;
            width: 100%;
            margin-bottom: 20px;
        }

        .submit-btn {
            background-color: #212121;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
            box-sizing: border-box;
        }

        .submit-btn:hover {
            background-color: #424242;
        }

        .container p {
            font-size: 18px;
            margin: 10px 0;
        }

        .account-info {
            font-weight: bold;
            margin: 10px 0;
        }

        input[type="file"] {
            margin: 10px 0;
            width: 100%;
        }

        @media screen and (max-width: 768px) {
            .container {
                padding: 15px;
                max-width: 90%;
            }

            .header {
                font-size: 24px;
            }

            .error, .success {
                font-size: 14px;
                padding: 8px;
            }

            .submit-btn {
                padding: 10px 15px;
                font-size: 14px;
            }

            .container p {
                font-size: 16px;
            }
        }

        @media screen and (max-width: 480px) {
            .container {
                padding: 10px;
                max-width: 95%;
            }

            .header {
                font-size: 20px;
            }

            .error, .success {
                font-size: 12px;
                padding: 6px;
            }

            .submit-btn {
                padding: 8px 12px;
                font-size: 12px;
            }

            .container p {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="header">Black Verification</h1>
        
        <?php
        // Display account number and payment instructions
        echo "<p class='account-info'>Send N1000 to this account:</p>";
        echo "<p class='account-info'>Account Number: 1234567890</p>";
        echo "<p>Please upload your payment receipt below.</p>";

        // Check eligibility
        if ($verified != 1) {
            echo "<p class='error'>You must be verified to get a black verification.</p>";
        } elseif ($gender !== 'male') {
            echo "<p class='error'>Only male students are eligible for black verification.</p>";
        } else {
            // Display upload form
            echo "<form action='' method='POST' enctype='multipart/form-data' class='verification-form'>";
            echo "<input type='file' name='receipt' accept='image/jpeg,image/png,application/pdf' required>";
            echo "<input type='submit' value='Upload Receipt' class='submit-btn'>";
            echo "</form>";

            // Display upload message if any
            echo $upload_message;

            // Check if receipt is approved and allow request
            if ($has_approved_receipt) {
                echo "<p class='success'>Your receipt has been approved!</p>";
                echo "<form action='' method='POST' class='verification-form'>";
                echo "<input type='submit' name='request_verification' value='Request Black Verification' class='submit-btn'>";
                echo "</form>";
                echo $request_message;
            } elseif ($receipt_uploaded) {
                echo "<p>Please wait for admin approval of your receipt.</p>";
            }
        }

        $conn->close();
        ?>
    </div>
</body>
</html>