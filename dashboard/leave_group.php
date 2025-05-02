<?php
session_start(); // Start session

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You need to log in first.");
}

$userId = $_SESSION['user_id']; // Get the logged-in user ID

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if group_id is set in the POST data
    if (!isset($_POST['group_id'])) {
        die("Group ID is missing. Please check the form submission.");
    }
    $groupId = $_POST['group_id'];

    // Check if the user confirmed they want to leave the group
    if (!isset($_POST['confirm_leave'])) {
        die("Please confirm that you want to leave the group.");
    }

    // Database connection
    $conn = new mysqli("localhost", "unimaid9_unimaidresources", "#adyems123AD", "unimaid9_unimaidresources");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Use prepared statement to delete the user from the group
    $sql = "DELETE FROM group_members WHERE group_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ii", $groupId, $userId); // Bind parameters as integers
    if ($stmt->execute()) {
        // Success message
        $message = "You have left the group. Redirecting to your groups in <span id='countdown'>5</span> seconds...";
    } else {
        $message = "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Group</title>
    <style>
        /* General container */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f3f4f6;
            color: #495057;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        /* Message container */
        .message-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }

        /* Heading */
        h1 {
            font-size: 24px;
            color: #6f42c1; /* Purple */
            margin-bottom: 20px;
        }

        /* Paragraph */
        p {
            font-size: 16px;
            color: #6c757d;
            line-height: 1.6;
        }

        /* Countdown styling */
        #countdown {
            font-weight: bold;
            color: #6f42c1; /* Purple */
        }

        /* Button styling */
        .btn {
            background-color: #6f42c1; /* Purple */
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-top: 20px;
        }

        .btn:hover {
            background-color: #5a2d9b; /* Darker purple */
            transform: translateY(-2px);
        }

        .btn:active {
            background-color: #4b1b6e; /* Even darker purple */
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <div class="message-container">
        <?php if (isset($message)): ?>
            <h1>Success!</h1>
            <p><?php echo $message; ?></p>
        <?php else: ?>
            <h1>Error</h1>
            <p>Something went wrong. Please try again.</p>
        <?php endif; ?>
        <button class="btn" onclick="window.location.href='my_groups.php'">Go to My Groups</button>
    </div>

    <script>
        // Countdown timer for redirection
        let countdown = 5;
        const countdownElement = document.getElementById('countdown');

        const interval = setInterval(() => {
            countdown--;
            if (countdownElement) {
                countdownElement.textContent = countdown;
            }

            if (countdown <= 0) {
                clearInterval(interval);
                window.location.href = 'my_groups.php';
            }
        }, 1000);
    </script>
</body>
</html>