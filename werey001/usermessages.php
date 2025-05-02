<?php
include('db_connection.php');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$sender_id = $_SESSION['user_id'];
$message_response = "";

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['message']) || empty(trim($_POST['message']))) {
            throw new Exception("Message cannot be empty.");
        }
        if (!isset($_POST['user'])) {
            throw new Exception("Please select a recipient.");
        }

        $message = trim($_POST['message']);
        $user = $_POST['user'];

        if ($user === 'all') {
            // Delete all existing messages for all users
            $delete_query = "DELETE FROM usermessages";
            $stmt_delete = $conn->prepare($delete_query);
            $stmt_delete->execute();
            $stmt_delete->close();

            // Send to all users except sender
            $query = "SELECT id FROM users WHERE id != ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $sender_id);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $receiver_id = $row['id'];
                $insert_query = "INSERT INTO usermessages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
                $stmt_insert = $conn->prepare($insert_query);
                $stmt_insert->bind_param("iis", $sender_id, $receiver_id, $message);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
            $message_response = "<p class='success'>Message sent to all users, replacing previous messages.</p>";
        } else {
            // Send to a specific user (no deletion)
            $insert_query = "INSERT INTO usermessages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iis", $sender_id, $user, $message);
            $stmt->execute();
            $message_response = "<p class='success'>Message sent to selected user.</p>";
        }
    } catch (Exception $e) {
        $message_response = "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Fetch users for dropdown
$sql_users = "SELECT id, username FROM users WHERE id != ?";
$stmt_users = $conn->prepare($sql_users);
$stmt_users->bind_param("i", $sender_id);
$stmt_users->execute();
$result_users = $stmt_users->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compose Message</title>
    <style>
        :root {
            --primary: #6a1b9a;
            --primary-dark: #4a148c;
            --secondary: #e3e3e3;
            --text: #333;
            --white: #fff;
            --light-bg: #f4f4f4;
            --shadow: rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: var(--light-bg);
            color: var(--text);
            padding: 20px;
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 600px;
            background: var(--white);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px var(--shadow);
        }

        h1 {
            font-size: 28px;
            color: var(--primary);
            text-align: center;
            margin-bottom: 25px;
            font-weight: 600;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            font-size: 16px;
            color: var(--text);
            margin-bottom: 5px;
            font-weight: 500;
        }

        textarea {
            padding: 12px;
            font-size: 14px;
            border-radius: 8px;
            border: 1px solid var(--secondary);
            background: var(--white);
            resize: vertical;
            min-height: 100px;
            outline: none;
            transition: border-color 0.3s ease;
        }

        textarea:focus {
            border-color: var(--primary);
        }

        select {
            padding: 12px;
            font-size: 14px;
            border-radius: 8px;
            border: 1px solid var(--secondary);
            background: var(--white);
            cursor: pointer;
            outline: none;
            transition: border-color 0.3s ease;
        }

        select:focus {
            border-color: var(--primary);
        }

        button {
            padding: 12px 20px;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .success {
            color: #28a745;
            background: #d4edda;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin-top: 15px;
        }

        .error {
            color: #721c24;
            background: #f8d7da;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin-top: 15px;
        }

        @media (max-width: 600px) {
            .container {
                padding: 20px;
                width: 90%;
            }

            h1 {
                font-size: 24px;
            }

            textarea, select, button {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Send Message</h1>
        <form action="usermessages.php" method="POST">
            <label for="message">Message</label>
            <textarea id="message" name="message" placeholder="Type your message here..." required></textarea>
            
            <label for="user">Send To</label>
            <select id="user" name="user">
                <option value="all">All Users</option>
                <?php while ($row = $result_users->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($row['id']); ?>">
                        <?php echo htmlspecialchars($row['username']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <button type="submit">Send Message</button>
        </form>
        <?php if ($message_response): ?>
            <?php echo $message_response; ?>
        <?php endif; ?>
    </div>
</body>
</html>
<?php 
$stmt_users->close();
$conn->close();
?>