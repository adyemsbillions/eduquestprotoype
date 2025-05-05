<?php
require_once 'db_connection.php';

// Fetch all pending verification requests
$sql = "SELECT vr.id, u.username, vr.request_date FROM verification_requests vr
        JOIN users u ON vr.user_id = u.id
        WHERE vr.status = 'pending'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Verification Requests</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
    body {
        font-family: 'Roboto', sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f4f7fc;
        color: #333;
    }

    .container {
        max-width: 900px;
        margin: 50px auto;
        padding: 20px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    h2 {
        text-align: center;
        margin-bottom: 30px;
        color: #4CAF50;
    }

    .request-list {
        margin-bottom: 30px;
    }

    .request-list table {
        width: 100%;
        border-collapse: collapse;
    }

    .request-list th,
    .request-list td {
        padding: 12px;
        text-align: left;
        border: 1px solid #ddd;
    }

    .request-list th {
        background-color: #4CAF50;
        color: white;
    }

    .btn {
        padding: 10px 20px;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .btn-approve {
        background-color: #4CAF50;
    }

    .btn-reject {
        background-color: #f44336;
    }

    .btn:hover {
        opacity: 0.9;
    }
    </style>
</head>

<body>

    <div class="container">
        <h2>Verification Requests</h2>

        <div class="request-list">
            <?php if ($result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>User</th>
                    <th>Request Date</th>
                    <th>Action</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['username']; ?></td>
                    <td><?php echo $row['request_date']; ?></td>
                    <td>
                        <a href="approve_request.php?id=<?php echo $row['id']; ?>" class="btn btn-approve">Approve</a>
                        <a href="reject_request.php?id=<?php echo $row['id']; ?>" class="btn btn-reject">Reject</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
            <?php else: ?>
            <p>No pending verification requests.</p>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>