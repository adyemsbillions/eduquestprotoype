<?php
include('db_connection.php');

// Handle adding a new allowed user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $new_user_id = (int)$_POST['user_id'];
    $sql_check = "SELECT id FROM users WHERE id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $new_user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $sql = "INSERT IGNORE INTO allowed_posters (user_id) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $new_user_id);
        if ($stmt->execute()) {
            $success = "User ID $new_user_id added successfully!";
        } else {
            $error = "Error adding user: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "User ID $new_user_id does not exist!";
    }
    $stmt_check->close();
}

// Handle removing an allowed user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_user'])) {
    $remove_user_id = (int)$_POST['user_id'];
    $sql = "DELETE FROM allowed_posters WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $remove_user_id);
    if ($stmt->execute()) {
        $success = "User ID $remove_user_id removed successfully!";
    } else {
        $error = "Error removing user: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch all users for the dropdown
$sql_users = "SELECT id, username FROM users ORDER BY username";
$users_result = $conn->query($sql_users);

// Fetch currently allowed users
$sql_allowed = "SELECT ap.user_id, u.username 
                FROM allowed_posters ap 
                JOIN users u ON ap.user_id = u.id 
                ORDER BY u.username";
$allowed_result = $conn->query($sql_allowed);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Allowed Posters</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #f4f6f9;
            padding: 20px;
            display: flex;
            justify-content: center;
        }
        .container {
            max-width: 800px;
            width: 100%;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #6a1b9a;
            text-align: center;
            margin-bottom: 25px;
        }
        .form-section, .list-section {
            margin-bottom: 30px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        label {
            font-weight: 600;
            color: #333;
        }
        select {
            padding: 10px;
            border: 2px solid #e0e4e8;
            border-radius: 8px;
            font-size: 16px;
        }
        button {
            padding: 12px;
            background: #6a1b9a;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }
        button:hover {
            background: #4a148c;
        }
        .remove-btn {
            background: #dc3545;
        }
        .remove-btn:hover {
            background: #c82333;
        }
        .message {
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .allowed-list {
            list-style: none;
            padding: 0;
        }
        .allowed-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #e0e4e8;
        }
        .allowed-item:last-child {
            border-bottom: none;
        }
        @media (max-width: 768px) {
            .container { padding: 20px; }
            h2 { font-size: 24px; }
            select, button { font-size: 14px; }
            .allowed-item { flex-direction: column; align-items: flex-start; gap: 10px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Manage Allowed Posters</h2>

        <?php if (isset($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php elseif (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Add User Form -->
        <div class="form-section">
            <h3>Add New Allowed User</h3>
            <form method="POST">
                <label for="user_id">Select User:</label>
                <select name="user_id" id="user_id" required>
                    <option value="">-- Select a User --</option>
                    <?php while ($user = $users_result->fetch_assoc()): ?>
                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?> (ID: <?php echo $user['id']; ?>)</option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" name="add_user">Add User</button>
            </form>
        </div>

        <!-- List of Allowed Users -->
        <div class="list-section">
            <h3>Current Allowed Posters</h3>
            <?php if ($allowed_result->num_rows > 0): ?>
                <ul class="allowed-list">
                    <?php while ($allowed = $allowed_result->fetch_assoc()): ?>
                        <li class="allowed-item">
                            <span><?php echo htmlspecialchars($allowed['username']); ?> (ID: <?php echo $allowed['user_id']; ?>)</span>
                            <form method="POST" onsubmit="return confirm('Remove <?php echo htmlspecialchars($allowed['username']); ?> from allowed posters?');">
                                <input type="hidden" name="user_id" value="<?php echo $allowed['user_id']; ?>">
                                <button type="submit" name="remove_user" class="remove-btn">Remove</button>
                            </form>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No users are currently allowed to post.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>