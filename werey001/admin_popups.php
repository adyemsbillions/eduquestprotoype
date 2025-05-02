<?php
include('db_connection.php');

// Handle popup creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_popup'])) {
    $message = trim($_POST['message']);
    $button_text = trim($_POST['button_text']);
    $button_link = trim($_POST['button_link']);
    $target_user_id = !empty($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : null;
    $display_limit = (int)$_POST['display_limit'] ?: 1; // Default to 1 if not set
    $image_path = null;

    // Auto-fix the button link
    if (!empty($button_link) && !preg_match('~^(?:f|ht)tps?://~i', $button_link)) {
        if (filter_var('http://' . $button_link, FILTER_VALIDATE_URL)) {
            $button_link = 'http://' . $button_link;
        }
    }

    if (!empty($_FILES['image']['name'])) {
        $upload_dir = 'uploads/popups/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $image_name = uniqid() . '_' . basename($_FILES['image']['name']);
        $image_path = $upload_dir . $image_name;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
            $image_path = null;
        }
    }

    $sql = "INSERT INTO popups (message, image_path, button_text, button_link, target_user_id, display_limit) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $message, $image_path, $button_text, $button_link, $target_user_id, $display_limit);
    if ($stmt->execute()) {
        $success = "Popup created successfully!";
    } else {
        $error = "Error creating popup: " . $stmt->error;
    }
    $stmt->close();
}

// Handle popup deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_popup'])) {
    $popup_id = (int)$_POST['popup_id'];

    // Delete image if exists
    $sql_image = "SELECT image_path FROM popups WHERE id = ?";
    $stmt_image = $conn->prepare($sql_image);
    $stmt_image->bind_param("i", $popup_id);
    $stmt_image->execute();
    $image_result = $stmt_image->get_result();
    $image_row = $image_result->fetch_assoc();

    if (!empty($image_row['image_path']) && file_exists($image_row['image_path'])) {
        unlink($image_row['image_path']);
    }
    $stmt_image->close();

    // Delete views
    $sql_views = "DELETE FROM popup_views WHERE popup_id = ?";
    $stmt_views = $conn->prepare($sql_views);
    $stmt_views->bind_param("i", $popup_id);
    $stmt_views->execute();
    $stmt_views->close();

    // Delete popup
    $sql_delete = "DELETE FROM popups WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $popup_id);
    if ($stmt_delete->execute()) {
        $success = "Popup deleted successfully!";
    } else {
        $error = "Error deleting popup: " . $stmt_delete->error;
    }
    $stmt_delete->close();
}

// Fetch users for dropdown
$sql_users = "SELECT id, username FROM users ORDER BY username";
$users_result = $conn->query($sql_users);

// Fetch all popups
$sql_popups = "SELECT p.*, u.username AS target_username, 
               (SELECT SUM(view_count) FROM popup_views WHERE popup_id = p.id) AS total_views
               FROM popups p 
               LEFT JOIN users u ON p.target_user_id = u.id 
               ORDER BY p.created_at DESC";
$popups_result = $conn->query($sql_popups);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Popups</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
            padding: 20px;
            display: flex;
            justify-content: center;
        }
        .container {
            max-width: 1000px;
            width: 100%;
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        h2, h3 {
            color: #6a1b9a;
            text-align: center;
            margin-bottom: 20px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 40px;
        }
        label {
            font-weight: 600;
            color: #333;
        }
        input, textarea, select {
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            width: 100%;
            box-sizing: border-box;
        }
        input[type="file"] {
            padding: 5px;
        }
        input[type="number"] {
            width: 100px;
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
        .message {
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .popup-list {
            margin-top: 20px;
        }
        .popup-item {
            padding: 15px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fafafa;
        }
        .popup-item img {
            max-width: 100px;
            border-radius: 5px;
        }
        .popup-details {
            flex: 1;
            margin-right: 15px;
        }
        .delete-btn {
            background: #dc3545;
        }
        .delete-btn:hover {
            background: #c82333;
        }
        @media (max-width: 768px) {
            .popup-item { flex-direction: column; align-items: flex-start; gap: 10px; }
            .popup-item img { max-width: 80px; }
        }
        @media (max-width: 480px) {
            .container { padding: 15px; }
            h2, h3 { font-size: 1.5rem; }
            input, textarea, select, button { font-size: 0.9rem; }
            .popup-details { font-size: 0.9rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Manage Popups</h2>
        <?php if (isset($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php elseif (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Create Popup Form -->
        <form method="POST" enctype="multipart/form-data">
            <label for="message">Message:</label>
            <textarea id="message" name="message" required rows="4"></textarea>

            <label for="image">Image (optional):</label>
            <input type="file" id="image" name="image" accept="image/*">

            <label for="button_text">Button Text (optional):</label>
            <input type="text" id="button_text" name="button_text">

            <label for="button_link">Button Link (optional):</label>
            <input type="text" id="button_link" name="button_link" placeholder="Example: amazon.com or https://amazon.com">

            <label for="target_user_id">Target User (leave blank for all):</label>
            <select id="target_user_id" name="target_user_id">
                <option value="">All Users</option>
                <?php while ($user = $users_result->fetch_assoc()): ?>
                    <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                <?php endwhile; ?>
            </select>

            <label for="display_limit">Display Limit (times to show):</label>
            <input type="number" id="display_limit" name="display_limit" min="1" value="1">

            <button type="submit" name="create_popup">Create Popup</button>
        </form>

        <!-- List of Popups -->
        <div class="popup-list">
            <h3>All Popups</h3>
            <?php if ($popups_result->num_rows > 0): ?>
                <?php while ($popup = $popups_result->fetch_assoc()): ?>
                    <div class="popup-item">
                        <div class="popup-details">
                            <strong>Message:</strong> <?php echo htmlspecialchars($popup['message']); ?><br>
                            <?php if ($popup['image_path']): ?>
                                <img src="<?php echo htmlspecialchars($popup['image_path']); ?>" alt="Popup Image"><br>
                            <?php endif; ?>
                            <?php if ($popup['button_text']): ?>
                                <strong>Button:</strong> <?php echo htmlspecialchars($popup['button_text']); ?> (<?php echo htmlspecialchars($popup['button_link']); ?>)<br>
                            <?php endif; ?>
                            <strong>Target:</strong> <?php echo $popup['target_user_id'] ? htmlspecialchars($popup['target_username']) : 'All Users'; ?><br>
                            <strong>Display Limit:</strong> <?php echo $popup['display_limit']; ?><br>
                            <strong>Total Views:</strong> <?php echo $popup['total_views'] ?: '0'; ?><br>
                            <strong>Created:</strong> <?php echo $popup['created_at']; ?>
                        </div>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this popup?');">
                            <input type="hidden" name="popup_id" value="<?php echo $popup['id']; ?>">
                            <button type="submit" name="delete_popup" class="delete-btn">Delete</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No popups created yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
