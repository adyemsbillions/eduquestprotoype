<?php
// Database connection
include('db_connection.php');

// Handle approval
if (isset($_GET['approve_id'])) {
    $userId = $conn->real_escape_string($_GET['approve_id']);
    $sqlUpdate = "UPDATE users SET reps_status = 'approved' WHERE id = '$userId'";
    $conn->query($sqlUpdate);
    header("Location: make_reps.php");
    exit();
}

// Handle suspension
if (isset($_GET['suspend_id'])) {
    $userId = $conn->real_escape_string($_GET['suspend_id']);
    $sqlUpdate = "UPDATE users SET reps_status = 'suspended' WHERE id = '$userId'";
    $conn->query($sqlUpdate);
    header("Location: make_reps.php");
    exit();
}

// Handle search
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$sqlUsers = "SELECT id, username, reps_status FROM users";
if ($search) {
    $sqlUsers .= " WHERE username LIKE '%$search%'";
}
$sqlUsers .= " ORDER BY id ASC";
$usersResult = $conn->query($sqlUsers);

$totalUsers = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$approvedCount = $conn->query("SELECT COUNT(*) FROM users WHERE reps_status = 'approved'")->fetch_row()[0];
$suspendedCount = $conn->query("SELECT COUNT(*) FROM users WHERE reps_status = 'suspended'")->fetch_row()[0];
$pendingCount = $totalUsers - $approvedCount - $suspendedCount;
?>

<!-- The HTML portion remains exactly the same -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Representatives</title>
    <style>
    /* Your existing CSS remains unchanged */
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f0f2f5;
        color: #333;
    }

    .container {
        width: 90%;
        max-width: 1200px;
        margin: 30px auto;
        padding: 25px;
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    h1 {
        font-size: 28px;
        color: #1a73e8;
        margin-bottom: 20px;
    }

    .search-container {
        margin-bottom: 20px;
        position: relative;
    }

    .search-input {
        width: 100%;
        padding: 12px;
        font-size: 16px;
        border: 1px solid #ddd;
        border-radius: 5px;
        box-sizing: border-box;
    }

    .suggestions {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background-color: white;
        border: 1px solid #ddd;
        border-radius: 5px;
        max-height: 200px;
        overflow-y: auto;
        display: none;
        z-index: 1000;
    }

    .suggestion-item {
        padding: 10px;
        cursor: pointer;
    }

    .suggestion-item:hover {
        background-color: #f1f3f4;
    }

    .stats {
        display: flex;
        gap: 20px;
        margin-bottom: 25px;
        flex-wrap: wrap;
    }

    .stat-box {
        background-color: #f8f9fa;
        padding: 15px 20px;
        border-radius: 8px;
        flex: 1;
        min-width: 200px;
    }

    .stat-box span {
        font-weight: bold;
        font-size: 18px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background-color: #fff;
        border-radius: 8px;
        overflow: hidden;
    }

    th,
    td {
        padding: 15px;
        text-align: left;
    }

    th {
        background-color: #1a73e8;
        color: white;
        font-weight: 600;
    }

    tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    tr:hover {
        background-color: #f1f3f4;
    }

    .status-approved {
        color: #28a745;
        font-weight: 600;
    }

    .status-suspended {
        color: #dc3545;
        font-weight: 600;
    }

    .status-pending {
        color: #ffa500;
        font-weight: 600;
    }

    .action-links a {
        padding: 6px 12px;
        border-radius: 4px;
        text-decoration: none;
        margin-right: 10px;
        transition: all 0.2s;
    }

    .approve-link {
        background-color: #28a745;
        color: white;
    }

    .suspend-link {
        background-color: #dc3545;
        color: white;
    }

    .action-links a:hover {
        opacity: 0.9;
    }

    .no-action {
        color: #666;
        font-style: italic;
    }
    </style>
</head>

<body>
    <div class="container">
        <h1>Manage Representatives</h1>

        <div class="search-container">
            <form action="make_reps.php" method="GET">
                <input type="text" name="search" class="search-input" id="searchInput"
                    placeholder="Search by username..." value="<?php echo htmlspecialchars($search); ?>">
                <div class="suggestions" id="suggestions"></div>
            </form>
        </div>

        <div class="stats">
            <div class="stat-box">Total Students: <span><?php echo $totalUsers; ?></span></div>
            <div class="stat-box">Approved: <span><?php echo $approvedCount; ?></span></div>
            <div class="stat-box">Suspended: <span><?php echo $suspendedCount; ?></span></div>
            <div class="stat-box">Pending: <span><?php echo $pendingCount; ?></span></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($usersResult->num_rows > 0) {
                    while ($user = $usersResult->fetch_assoc()) {
                        $statusClass = $user['reps_status'] === 'approved' ? 'status-approved' : ($user['reps_status'] === 'suspended' ? 'status-suspended' : 'status-pending');
                        $displayStatus = $user['reps_status'] ?: 'pending';
                        echo "<tr>";
                        echo "<td>" . $user['id'] . "</td>";
                        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                        echo "<td class='$statusClass'>" . $displayStatus . "</td>";
                        echo "<td class='action-links'>";
                        if ($displayStatus === 'pending') {
                            echo "<a href='make_reps.php?approve_id=" . $user['id'] . "' class='approve-link'>Approve</a>";
                            echo "<a href='make_reps.php?suspend_id=" . $user['id'] . "' class='suspend-link'>Suspend</a>";
                        } elseif ($displayStatus === 'approved') {
                            echo "<a href='make_reps.php?suspend_id=" . $user['id'] . "' class='suspend-link'>Suspend</a>";
                        } elseif ($displayStatus === 'suspended') {
                            echo "<a href='make_reps.php?approve_id=" . $user['id'] . "' class='approve-link'>Approve</a>";
                        }
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No users found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script>
    /* Your existing JavaScript remains unchanged */
    </script>
</body>

</html>

<?php
$conn->close();
?>