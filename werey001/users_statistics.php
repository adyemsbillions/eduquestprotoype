<?php
session_start();
include('db_connection.php');

// Check if user is logged in
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit();
// }

// Optional: Add admin check if needed
// if ($_SESSION['role'] !== 'admin') { header("Location: dashboard.php"); exit(); }

// Check database connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Define online threshold (5 minutes = 300 seconds)
define('ONLINE_THRESHOLD', 300);

// Get all users with activity stats (using last_login instead of last_activity)
$users_query = "SELECT username, created_at, last_login, login_count, total_active_time, login_method 
                FROM users 
                ORDER BY created_at DESC";
$users_result = mysqli_query($conn, $users_query);
if (!$users_result) {
    die("Users query failed: " . mysqli_error($conn));
}

// Calculate online users count using last_login
$online_query = "SELECT COUNT(*) as online_count FROM users WHERE last_login > NOW() - INTERVAL " . ONLINE_THRESHOLD . " SECOND";
$online_result = mysqli_query($conn, $online_query);
$online_count = $online_result ? mysqli_fetch_assoc($online_result)['online_count'] : 0;

// Calculate aggregate statistics for all users
$stats_query = "SELECT 
                    COUNT(*) as total_users,
                    AVG(login_count) as avg_logins,
                    AVG(total_active_time) / 3600 as avg_active_hours,
                    SUM(CASE WHEN login_method = 'referral_link' THEN 1 ELSE 0 END) as referral_link_logins,
                    SUM(CASE WHEN login_method = 'email' THEN 1 ELSE 0 END) as email_logins,
                    SUM(CASE WHEN login_method = 'social' THEN 1 ELSE 0 END) as social_logins
                FROM users";
$stats_result = mysqli_query($conn, $stats_query);
if (!$stats_result) {
    die("Stats query failed: " . mysqli_error($conn));
}
$stats = mysqli_fetch_assoc($stats_result);
if (!$stats) {
    $stats = ['total_users' => 0, 'avg_logins' => 0, 'avg_active_hours' => 0, 'referral_link_logins' => 0, 'email_logins' => 0, 'social_logins' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Users Statistics - Unimaid Resources</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background-color: #f4f4f4; padding: 20px; line-height: 1.6; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }
        h1 { color: #7d2ae8; font-size: 28px; margin-bottom: 20px; text-align: center; }
        .users-stats, .all-users { margin-top: 30px; }
        .users-stats h2, .all-users h2 { color: #7d2ae8; font-size: 22px; margin-bottom: 15px; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #fff; border-radius: 5px; overflow: hidden; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #7d2ae8; color: #fff; font-weight: 600; }
        td { color: #333; }
        tr:hover { background-color: #f8f0ff; }
        .no-users { color: #7d2ae8; font-style: italic; text-align: center; font-size: 16px; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .stat-item { background: #f8f0ff; padding: 15px; border-radius: 5px; text-align: center; }
        .stat-item strong { color: #7d2ae8; }
        .debug { color: red; font-size: 14px; margin-top: 10px; text-align: center; }
        .online-status { 
            display: inline-block; 
            width: 10px; 
            height: 10px; 
            border-radius: 50%; 
            margin-right: 5px;
        }
        .online { background-color: #2ecc71; }
        .offline { background-color: #e74c3c; }
        .online-text { color: #2ecc71; font-weight: bold; }
        .offline-text { color: #e74c3c; }
        @media (max-width: 600px) {
            .container { padding: 15px; }
            h1 { font-size: 24px; }
            .users-stats h2, .all-users h2 { font-size: 20px; }
            th, td { padding: 10px; font-size: 14px; }
        }
        @media (max-width: 400px) {
            h1 { font-size: 20px; }
            .no-users { font-size: 14px; }
            table { display: block; overflow-x: auto; white-space: nowrap; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>All Users Statistics</h1>

        <div class="users-stats">
            <h2>Overall User Activity</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <strong>Total Users:</strong> <?php echo $stats['total_users']; ?>
                </div>
                <div class="stat-item">
                    <strong>Online Now:</strong> <span class="online-text"><?php echo $online_count; ?></span>
                </div>
                <div class="stat-item">
                    <strong>Avg. Logins per User:</strong> <?php echo number_format($stats['avg_logins'], 1); ?>
                </div>
                <div class="stat-item">
                    <strong>Avg. Active Time (hrs):</strong> <?php echo number_format($stats['avg_active_hours'], 2); ?>
                </div>
                <div class="stat-item">
                    <strong>Referral Link Logins:</strong> <?php echo $stats['referral_link_logins']; ?>
                </div>
                <div class="stat-item">
                    <strong>Email Logins:</strong> <?php echo $stats['email_logins']; ?>
                </div>
                <div class="stat-item">
                    <strong>Social Logins:</strong> <?php echo $stats['social_logins']; ?>
                </div>
            </div>
        </div>

        <div class="all-users">
            <h2>All Users</h2>
            <?php if (mysqli_num_rows($users_result) > 0) { ?>
                <table>
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Username</th>
                            <th>Join Date</th>
                            <th>Last Login</th>
                            <th>Login Count</th>
                            <th>Active Time (hrs)</th>
                            <th>Login Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($users_result)) { 
                            $is_online = strtotime($row['last_login']) > (time() - ONLINE_THRESHOLD);
                            ?>
                            <tr>
                                <td>
                                    <span class="online-status <?php echo $is_online ? 'online' : 'offline'; ?>"></span>
                                    <span class="<?php echo $is_online ? 'online-text' : 'offline-text'; ?>">
                                        <?php echo $is_online ? 'Online' : 'Offline'; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                <td><?php echo htmlspecialchars($row['last_login'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['login_count'] ?? 0); ?></td>
                                <td><?php echo number_format(($row['total_active_time'] ?? 0) / 3600, 2); ?></td>
                                <td><?php echo htmlspecialchars($row['login_method'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <p class="no-users">No users found in the system.</p>
            <?php } ?>
            <p class="debug">Debug: <?php echo "Rows fetched: " . mysqli_num_rows($users_result); ?></p>
        </div>
    </div>
</body>
</html>