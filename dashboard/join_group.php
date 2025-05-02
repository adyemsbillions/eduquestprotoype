<?php
session_start(); // Start session

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You need to log in first.");
}

$userId = $_SESSION['user_id']; // Get the logged-in user ID

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the group ID from the form
    $groupId = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT);

    if ($groupId === false) {
        die("Invalid group ID.");
    }

    // Database connection
    $conn = new mysqli('localhost', 'unimaid9_unimaidresources', '#adyems123AD', 'unimaid9_unimaidresources');

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check if the user is already a member
    $sqlCheck = "SELECT * FROM group_members WHERE group_id = ? AND user_id = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("ii", $groupId, $userId);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows > 0) {
        echo "<div class='message error'>You are already a member of this group.</div>";
    } else {
        // Insert user into group_members table
        $sql = "INSERT INTO group_members (group_id, user_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $groupId, $userId);

        if ($stmt->execute()) {
            // Success message with countdown
            echo "<div class='message success'>
                    <div class='icon'>✓</div>
                    <h2>Success!</h2>
                    <p>You have successfully joined the group.</p>
                    <p class='countdown'>Redirecting in <span id='countdown'>5</span> seconds...</p>
                  </div>";
            // JavaScript for countdown and redirect
            echo "<script>
                    let timeLeft = 5;
                    const countdownElement = document.getElementById('countdown');
                    const countdown = setInterval(() => {
                        timeLeft--;
                        countdownElement.textContent = timeLeft;
                        if (timeLeft <= 0) {
                            clearInterval(countdown);
                            window.location.href = 'my_groups.php';
                        }
                    }, 1000);
                  </script>";
        } else {
            echo "<div class='message error'>Error: " . htmlspecialchars($stmt->error) . "</div>";
        }
        $stmt->close();
    }

    $stmtCheck->close();
    $conn->close();
} else {
    echo "<div class='message error'>Invalid request.</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Group</title>
    <style>
        :root {
            --primary: #6a1b9a; /* Purple */
            --success: #28a745; /* Green */
            --error: #dc3545; /* Red */
            --white: #fff;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f3e5f5; /* Light purple background */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .message {
            max-width: 500px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            text-align: center;
            background: var(--white);
            transition: var(--transition);
        }

        .message.success {
            border: 2px solid var(--success);
        }

        .message.error {
            border: 2px solid var(--error);
            color: var(--error);
        }

        .message.success .icon {
            font-size: 40px;
            color: var(--success);
            margin-bottom: 15px;
            width: 60px;
            height: 60px;
            line-height: 60px;
            border-radius: 50%;
            background: rgba(40, 167, 69, 0.1);
            display: inline-block;
        }

        .message h2 {
            font-size: 28px;
            color: var(--primary);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .message p {
            font-size: 16px;
            color: #666;
            margin-bottom: 15px;
        }

        .message .countdown {
            font-size: 14px;
            color: var(--primary);
            font-weight: 500;
        }

        .message .countdown #countdown {
            font-weight: 700;
            color: var(--success);
            padding: 2px 6px;
            background: rgba(40, 167, 69, 0.1);
            border-radius: 4px;
        }

        @media (max-width: 480px) {
            .message {
                padding: 20px;
                max-width: 100%;
                margin: 10px;
            }

            .message h2 {
                font-size: 24px;
            }

            .message p {
                font-size: 14px;
            }

            .message.success .icon {
                font-size: 30px;
                width: 50px;
                height: 50px;
                line-height: 50px;
            }
        }
    </style>
</head>
<body>
    <!-- Content is output directly by PHP above -->
</body>
</html>