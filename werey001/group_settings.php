<?php
session_start();



// Database connection with error handling
include('db_connection.php');

// Handle post request to update can_post status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['group_id'])) {
    $groupId = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT);
    $canPost = isset($_POST['can_post']) ? 1 : 0;

    if ($groupId === false) {
        $error = "Invalid group ID";
    } else {
        $sqlUpdatePostStatus = "UPDATE `groups` SET can_post = ? WHERE id = ?";
        $stmt = $conn->prepare($sqlUpdatePostStatus);
        $stmt->bind_param("ii", $canPost, $groupId);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Group post settings updated successfully!";
            header("Location: group_settings.php");
            exit();
        } else {
            $error = "Error updating settings: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all groups
$sql = "SELECT id, name, can_post FROM `groups`";
$result = $conn->query($sql);

if ($result === false) {
    die("Query failed: " . $conn->error . "<br>SQL: " . $sql);
}

// Function to get member count
function getGroupMemberCount($groupId, $conn)
{
    $sql = "SELECT COUNT(*) as member_count FROM group_members WHERE group_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $groupId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['member_count'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Groups</title>
    <style>
    :root {
        --primary: #6a1b9a;
        --primary-hover: #4a148c;
        --secondary: #f4f4f4;
        --text: #333;
        --white: #fff;
        --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        --transition: all 0.3s ease;
        --success: #28a745;
        --error: #dc3545;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: Arial, sans-serif;
        background: var(--secondary);
        color: var(--text);
        line-height: 1.6;
        min-height: 100vh;
        padding: 20px;
    }

    .container {
        max-width: 900px;
        margin: 0 auto;
        background: var(--white);
        padding: 30px;
        border-radius: 10px;
        box-shadow: var(--shadow);
        position: relative;
    }

    h1 {
        color: var(--primary);
        font-size: 28px;
        margin-bottom: 25px;
        text-align: center;
        font-weight: 700;
    }

    .logout {
        position: absolute;
        top: 20px;
        right: 20px;
        color: var(--primary);
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        transition: var(--transition);
    }

    .logout:hover {
        color: var(--primary-hover);
    }

    .message {
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        text-align: center;
        font-weight: 500;
    }

    .message.success {
        background: rgba(40, 167, 69, 0.1);
        color: var(--success);
        border: 1px solid var(--success);
    }

    .message.error {
        background: rgba(220, 53, 69, 0.1);
        color: var(--error);
        border: 1px solid var(--error);
    }

    .group-list {
        margin-top: 20px;
    }

    .group-item {
        background: #f9f9f9;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        transition: var(--transition);
    }

    .group-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .group-item h3 {
        color: var(--primary);
        font-size: 22px;
        margin-bottom: 15px;
        font-weight: 600;
    }

    .group-item form {
        display: flex;
        align-items: center;
        gap: 15px;
        margin: 15px 0;
    }

    .group-item label {
        font-size: 16px;
        cursor: pointer;
    }

    .group-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .group-item input[type="submit"] {
        background: var(--primary);
        color: var(--white);
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 15px;
        font-weight: 500;
        transition: var(--transition);
    }

    .group-item input[type="submit"]:hover {
        background: var(--primary-hover);
        transform: translateY(-2px);
    }

    .group-item p {
        color: #666;
        font-size: 14px;
        font-style: italic;
    }

    .no-groups {
        text-align: center;
        padding: 20px;
        color: #777;
        font-size: 16px;
    }

    @media (max-width: 768px) {
        .container {
            padding: 20px;
        }

        .group-item form {
            flex-direction: column;
            align-items: flex-start;
        }

        .group-item input[type="submit"] {
            width: 100%;
        }
    }
    </style>
</head>

<body>
    <div class="container">
        <a href="logout.php" class="logout">Logout</a>
        <h1>Manage All Groups</h1>

        <?php
        if (isset($_SESSION['success_message'])) {
            echo '<div class="message success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
            unset($_SESSION['success_message']);
        }
        if (isset($error)) {
            echo '<div class="message error">' . htmlspecialchars($error) . '</div>';
        }
        ?>

        <div class="group-list">
            <?php if ($result->num_rows > 0): ?>
            <?php while ($group = $result->fetch_assoc()): ?>
            <div class="group-item">
                <h3><?php echo htmlspecialchars($group['name']); ?></h3>
                <form action="group_settings.php" method="POST"
                    onsubmit="return confirm('Are you sure you want to save these settings?');">
                    <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                    <label for="can_post_<?php echo $group['id']; ?>">Allow Members to Post:</label>
                    <input type="checkbox" id="can_post_<?php echo $group['id']; ?>" name="can_post"
                        <?php echo $group['can_post'] ? 'checked' : ''; ?>>
                    <input type="submit" value="Save Settings">
                </form>
                <p><strong>Number of Members:</strong> <?php echo getGroupMemberCount($group['id'], $conn); ?></p>
            </div>
            <?php endwhile; ?>
            <?php else: ?>
            <div class="no-groups">No groups found in the database.</div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>

<?php
$conn->close();
?>