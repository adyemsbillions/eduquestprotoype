<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<p class='error'>Please log in to access this page.</p>";
    exit;
}

include('db_connection.php');

// Hardcoded admin check (replace 'admin' with your logic, e.g., role-based if needed)
$user_id = $_SESSION['user_id'];
$sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();


// Handle receipt approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['receipt_id'])) {
    $receipt_id = $_POST['receipt_id'];
    $action = $_POST['action'];
    $status = ($action === 'approve') ? 'approved' : 'rejected';

    $sql_update = "UPDATE verification_receipts SET status = ?, reviewed_at = NOW() WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $status, $receipt_id);
    if ($stmt_update->execute()) {
        $message = "<p class='success'>Receipt " . ($action === 'approve' ? 'approved' : 'rejected') . " successfully!</p>";
    } else {
        $message = "<p class='error'>Error updating receipt status.</p>";
    }
    $stmt_update->close();
}

// Fetch all pending receipts
$sql_receipts = "SELECT vr.id, vr.user_id, vr.receipt_path, vr.uploaded_at, u.username 
                 FROM verification_receipts vr 
                 JOIN users u ON vr.user_id = u.id 
                 WHERE vr.status = 'pending' 
                 ORDER BY vr.uploaded_at ASC";
$result_receipts = $conn->query($sql_receipts);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Review Black Verification Receipts</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        color: #333;
        margin: 0;
        padding: 20px;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        min-height: 100vh;
        box-sizing: border-box;
    }

    .container {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        padding: 20px;
        max-width: 800px;
        width: 100%;
        text-align: center;
        box-sizing: border-box;
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

    .receipt-list {
        text-align: left;
        margin-top: 20px;
    }

    .receipt-item {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 15px;
        background-color: #fafafa;
    }

    .receipt-item p {
        margin: 5px 0;
        font-size: 16px;
    }

    .receipt-item img,
    .receipt-item a {
        max-width: 100%;
        margin: 10px 0;
        display: block;
    }

    .receipt-item form {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 10px;
    }

    .approve-btn,
    .reject-btn {
        border: none;
        padding: 10px 20px;
        font-size: 14px;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        width: 120px;
    }

    .approve-btn {
        background-color: #28a745;
        color: white;
    }

    .approve-btn:hover {
        background-color: #218838;
    }

    .reject-btn {
        background-color: #dc3545;
        color: white;
    }

    .reject-btn:hover {
        background-color: #c82333;
    }

    @media screen and (max-width: 768px) {
        .container {
            padding: 15px;
            max-width: 90%;
        }

        .header {
            font-size: 24px;
        }

        .receipt-item p {
            font-size: 14px;
        }

        .approve-btn,
        .reject-btn {
            padding: 8px 15px;
            font-size: 12px;
            width: 100px;
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

        .receipt-item p {
            font-size: 12px;
        }

        .receipt-item form {
            flex-direction: column;
            gap: 5px;
        }

        .approve-btn,
        .reject-btn {
            padding: 6px 10px;
            font-size: 12px;
            width: 100%;
        }
    }
    </style>
</head>

<body>
    <div class="container">
        <h1 class="header">Admin - Review Black Verification Receipts</h1>

        <?php
        // Display any messages
        if (isset($message)) {
            echo $message;
        }

        // Display pending receipts
        if ($result_receipts->num_rows > 0) {
            echo "<div class='receipt-list'>";
            while ($receipt = $result_receipts->fetch_assoc()) {
                echo "<div class='receipt-item'>";
                echo "<p><strong>User:</strong> " . htmlspecialchars($receipt['username']) . "</p>";
                echo "<p><strong>Uploaded At:</strong> " . $receipt['uploaded_at'] . "</p>";

                // Display receipt (image or PDF link)
                $file_ext = strtolower(pathinfo($receipt['receipt_path'], PATHINFO_EXTENSION));
                if (in_array($file_ext, ['jpg', 'jpeg', 'png'])) {
                    echo "<img src='" . htmlspecialchars($receipt['receipt_path']) . "' alt='Receipt'>";
                } else {
                    echo "<a href='/dashboard/uploads/receipts/" . htmlspecialchars($receipt['receipt_path']) . "' target='_blank'>View PDF Receipt</a>";
                }

                // Approve/Reject form
                echo "<form method='POST'>";
                echo "<input type='hidden' name='receipt_id' value='" . $receipt['id'] . "'>";
                echo "<button type='submit' name='action' value='approve' class='approve-btn'>Approve</button>";
                echo "<button type='submit' name='action' value='reject' class='reject-btn'>Reject</button>";
                echo "</form>";
                echo "</div>";
            }
            echo "</div>";
        } else {
            echo "<p>No pending receipts to review.</p>";
        }

        $conn->close();
        ?>
    </div>
</body>

</html>