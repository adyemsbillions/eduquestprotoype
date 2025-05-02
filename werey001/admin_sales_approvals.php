<?php
include('./db_connection.php');
session_start();

// Ensure database connection
if (!$conn->ping()) {
    error_log("Initial connection lost: " . $conn->error);
    $conn = new mysqli("localhost", "unimaid9_unimaidresources", "#adyems123AD", "unimaid9_unimaidresources");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Handle approval or suspension for pending requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['request_id'])) {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];

    if (!$conn->ping()) {
        error_log("Connection lost before approval/suspension: " . $conn->error);
        $conn = new mysqli("localhost", "unimaid9_unimaidresources", "#adyems123AD", "unimaid9_unimaidresources");
        if ($conn->connect_error) {
            die("Reconnection failed: " . $conn->connect_error);
        }
    }

    $sql = "SELECT user_id FROM sales_requests WHERE id = $request_id";
    $result = $conn->query($sql);
    if (!$result) {
        error_log("Query failed: " . $conn->error);
        echo "Error: Failed to fetch request";
        exit();
    }
    $row = $result->fetch_assoc();
    if (!$row) {
        echo "Error: No request found for ID: $request_id";
        exit();
    }
    $user_id = (int)$row['user_id'];

    if ($action === 'approve') {
        $sales_status = 1;
        $request_status = 'approved';
    } elseif ($action === 'suspend') {
        $sales_status = 2;
        $request_status = 'rejected';
    } else {
        echo "Error: Invalid action";
        exit();
    }

    $sql = "UPDATE users SET sales_status = $sales_status, updated_at = NOW() WHERE id = $user_id";
    if (!$conn->query($sql)) {
        error_log("User update failed: " . $conn->error);
        echo "Error: Failed to update user status";
        exit();
    }

    $request_status = $conn->real_escape_string($request_status);
    $sql = "UPDATE sales_requests SET request_status = '$request_status' WHERE id = $request_id";
    if (!$conn->query($sql)) {
        error_log("Request update failed: " . $conn->error);
        echo "Error: Failed to update request status";
        exit();
    }

    header("Location: admin_sales_approvals.php");
    exit();
}

// Handle suspension or re-approval for existing sellers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['suspend_user']) || isset($_POST['reapprove_user']))) {
    $user_id = (int)$_POST['user_id'];
    $new_status = isset($_POST['suspend_user']) ? 2 : 1;
    $action = isset($_POST['suspend_user']) ? 'suspend' : 'reapprove';

    if (!$conn->ping()) {
        error_log("Connection lost before $action: " . $conn->error);
        $conn = new mysqli("localhost", "unimaid9_unimaidresources", "#adyems123AD", "unimaid9_unimaidresources");
        if ($conn->connect_error) {
            die("Reconnection failed: " . $conn->connect_error);
        }
    }

    $sql = "UPDATE users SET sales_status = $new_status, updated_at = NOW() WHERE id = $user_id";
    if (!$conn->query($sql)) {
        error_log("Status update failed: Action=$action, UserID=$user_id, " . $conn->error);
        echo "Error: Failed to update status";
        exit();
    }

    header("Location: admin_sales_approvals.php");
    exit();
}

// Fetch data
$sql_requests = "SELECT sr.id, sr.user_id, sr.receipt_path, sr.requested_at, u.username
                 FROM sales_requests sr
                 JOIN users u ON sr.user_id = u.id
                 WHERE sr.request_status = 'pending'
                 ORDER BY sr.requested_at DESC";
$result_requests = $conn->query($sql_requests);
if (!$result_requests) {
    error_log("Pending requests query failed: " . $conn->error);
    $result_requests = null;
}

$sql_approved = "SELECT id, username, updated_at FROM users WHERE sales_status = 1 ORDER BY updated_at DESC";
$result_approved = $conn->query($sql_approved);
if (!$result_approved) {
    error_log("Approved users query failed: " . $conn->error);
    $result_approved = null;
}

$sql_suspended = "SELECT id, username, updated_at FROM users WHERE sales_status = 2 ORDER BY updated_at DESC";
$result_suspended = $conn->query($sql_suspended);
if (!$result_suspended) {
    error_log("Suspended users query failed: " . $conn->error);
    $result_suspended = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sales Approval</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            color: #333;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }
        h1 {
            font-size: 28px;
            color: #6a1b9a;
            text-align: center;
            margin-bottom: 20px;
        }
        h2 {
            font-size: 22px;
            color: #6a1b9a;
            margin: 20px 0 10px;
        }
        .request-card, .approved-card, .suspended-card {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 15px;
            margin-bottom: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .request-card img {
            max-width: 150px;
            border-radius: 5px;
            cursor: pointer;
        }
        button {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: #fff;
        }
        .approve-btn { background: #28a745; }
        .approve-btn:hover { background: #218838; }
        .suspend-btn { background: #dc3545; }
        .suspend-btn:hover { background: #c82333; }
        .reapprove-btn { background: #007bff; }
        .reapprove-btn:hover { background: #0069d9; }
        .countdown {
            font-size: 14px;
            color: #555;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal img {
            max-width: 90%;
            max-height: 90vh;
        }
        @media (max-width: 600px) {
            .container { padding: 20px; }
            .request-card, .approved-card, .suspended-card { flex-direction: column; text-align: center; }
            .request-card img { max-width: 100px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sales Approval Dashboard</h1>

        <h2>Pending Requests</h2>
        <div>
            <?php if ($result_requests && $result_requests->num_rows > 0): ?>
                <?php while ($request = $result_requests->fetch_assoc()): ?>
                    <div class="request-card">
                        <img src="/dashboard/<?php echo htmlspecialchars($request['receipt_path']); ?>" alt="Receipt" class="receipt-image">
                        <div>
                            <p><strong>User:</strong> <?php echo htmlspecialchars($request['username']); ?></p>
                            <p><strong>Requested:</strong> <?php echo date('F j, Y, g:i a', strtotime($request['requested_at'])); ?></p>
                        </div>
                        <div>
                            <form method="POST">
                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="approve-btn">Approve</button>
                            </form>
                            <form method="POST">
                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                <input type="hidden" name="action" value="suspend">
                                <button type="submit" class="suspend-btn">Suspend</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No pending requests<?php echo $result_requests === null ? " (query failed)" : ""; ?>.</p>
            <?php endif; ?>
        </div>

        <h2>Approved Sellers</h2>
        <div>
            <?php if ($result_approved && $result_approved->num_rows > 0): ?>
                <?php while ($approved = $result_approved->fetch_assoc()): ?>
                    <?php
                    $days_left = null;
                    if ($approved['updated_at']) {
                        $approval_date = new DateTime($approved['updated_at']);
                        $expiry_date = clone $approval_date;
                        $expiry_date->modify('+31 days');
                        $now = new DateTime();
                        $interval = $now->diff($expiry_date);
                        $days_left = $interval->invert ? 0 : $interval->days;
                    }
                    ?>
                    <div class="approved-card">
                        <div>
                            <p><strong>User:</strong> <?php echo htmlspecialchars($approved['username']); ?></p>
                            <p><strong>Approved On:</strong> <?php echo date('F j, Y, g:i a', strtotime($approved['updated_at'])); ?></p>
                            <p class="countdown"><strong>Expires In:</strong> <?php echo $days_left; ?> days</p>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="user_id" value="<?php echo $approved['id']; ?>">
                            <input type="hidden" name="suspend_user" value="1">
                            <button type="submit" class="suspend-btn">Suspend</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No approved sellers<?php echo $result_approved === null ? " (query failed)" : ""; ?>.</p>
            <?php endif; ?>
        </div>

        <h2>Suspended Sellers</h2>
        <div>
            <?php if ($result_suspended && $result_suspended->num_rows > 0): ?>
                <?php while ($suspended = $result_suspended->fetch_assoc()): ?>
                    <div class="suspended-card">
                        <div>
                            <p><strong>User:</strong> <?php echo htmlspecialchars($suspended['username']); ?></p>
                            <p><strong>Suspended On:</strong> <?php echo date('F j, Y, g:i a', strtotime($suspended['updated_at'])); ?></p>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="user_id" value="<?php echo $suspended['id']; ?>">
                            <input type="hidden" name="reapprove_user" value="1">
                            <button type="submit" class="reapprove-btn">Re-approve</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No suspended sellers<?php echo $result_suspended === null ? " (query failed)" : ""; ?>.</p>
            <?php endif; ?>
        </div>
    </div>

    <div id="imageModal" class="modal">
        <img id="modalImage" src="" alt="Receipt">
    </div>

    <script>
        const modal = document.getElementById("imageModal");
        const modalImg = document.getElementById("modalImage");
        const images = document.querySelectorAll(".receipt-image");

        images.forEach(img => {
            img.onclick = () => {
                modal.style.display = "flex";
                modalImg.src = img.src;
            };
        });

        modal.onclick = (event) => {
            if (event.target !== modalImg) modal.style.display = "none";
        };
    </script>
</body>
</html>
<?php $conn->close(); ?>