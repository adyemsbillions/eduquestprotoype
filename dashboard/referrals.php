<?php
session_start();
include('db_connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's referral code
$query = "SELECT referral_code FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $query);
if (!$result || mysqli_num_rows($result) == 0) {
    die("User not found.");
}
$user_data = mysqli_fetch_assoc($result);
$referral_code = $user_data['referral_code'];
$referral_link = "https://unimaidresources.com.ng/index.php?ref=" . $referral_code;

// Get list of referred users with activity stats
$ref_query = "SELECT username, created_at, last_login, login_count, total_active_time, login_method 
              FROM users 
              WHERE referred_by = $user_id 
              ORDER BY created_at DESC";
$ref_result = mysqli_query($conn, $ref_query);

// Calculate aggregate statistics
$stats_query = "SELECT 
                    COUNT(*) as total_referred,
                    AVG(login_count) as avg_logins,
                    AVG(total_active_time) / 3600 as avg_active_hours,
                    SUM(CASE WHEN login_method = 'referral_link' THEN 1 ELSE 0 END) as referral_link_logins,
                    SUM(CASE WHEN login_method = 'email' THEN 1 ELSE 0 END) as email_logins,
                    SUM(CASE WHEN login_method = 'social' THEN 1 ELSE 0 END) as social_logins
                FROM users 
                WHERE referred_by = $user_id";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Referrals - Unimaid Resources</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background-color: #f4f4f4; padding: 20px; line-height: 1.6; color: #333; }
        .container { max-width: 900px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }
        h1 { color: #7d2ae8; font-size: 28px; margin-bottom: 20px; text-align: center; }
        .referral-link { margin: 20px 0; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: center; }
        .referral-link input { flex: 1; min-width: 200px; padding: 10px; font-size: 16px; border: 2px solid #7d2ae8; border-radius: 5px; background: #f9f9f9; color: #333; outline: none; }
        .referral-link button { padding: 10px 20px; background-color: #7d2ae8; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; transition: background-color 0.3s ease; display: flex; align-items: center; gap: 5px; }
        .referral-link button:hover { background-color: #5b13b9; }
        .referral-statement { margin: 15px 0; color: #7d2ae8; font-style: italic; text-align: center; font-size: 16px; }
        .referred-users, .referral-stats { margin-top: 30px; }
        .referred-users h2, .referral-stats h2 { color: #7d2ae8; font-size: 22px; margin-bottom: 15px; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #fff; border-radius: 5px; overflow: hidden; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #7d2ae8; color: #fff; font-weight: 600; }
        td { color: #333; }
        tr:hover { background-color: #f8f0ff; }
        .no-referrals { color: #7d2ae8; font-style: italic; text-align: center; font-size: 16px; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .stat-item { background: #f8f0ff; padding: 15px; border-radius: 5px; text-align: center; }
        .stat-item strong { color: #7d2ae8; }
        .popup { position: fixed; top: 20px; right: 20px; background: #7d2ae8; color: #fff; padding: 10px 20px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2); opacity: 0; visibility: hidden; transition: opacity 0.3s ease; }
        .popup.show { opacity: 1; visibility: visible; }
        .back-button-container {
            position: fixed;
            bottom: 30px;
            left: 30px;
            z-index: 1000;
        }
        .back-button {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #7d2ae8;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .back-button:hover {
            background-color: #5b13b9;
            transform: scale(1.1);
        }
        @media (max-width: 600px) {
            .container { padding: 15px; }
            h1 { font-size: 24px; }
            .referral-link { flex-direction: column; gap: 15px; }
            .referral-link input, .referral-link button { width: 100%; padding: 12px; }
            .referred-users h2, .referral-stats h2 { font-size: 20px; }
            th, td { padding: 10px; font-size: 14px; }
            .popup { top: 10px; right: 10px; font-size: 14px; }
        }
        @media (max-width: 400px) {
            h1 { font-size: 20px; }
            .referral-statement { font-size: 14px; }
            .no-referrals { font-size: 14px; }
            table { display: block; overflow-x: auto; white-space: nowrap; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Your Referral Dashboard</h1>
        
        <div class="referral-link">
            <input type="text" id="referralLink" value="<?php echo htmlspecialchars($referral_link); ?>" readonly>
            <button onclick="copyLink()">Copy <i class="fas fa-copy"></i></button>
        </div>
        <p class="referral-statement">Invite your friends to Unimaid Resources and help them unlock academic tools and campus updates!</p>

        <div class="referral-stats">
            <h2>Referral Activity Statistics</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <strong>Total Referred Users:</strong> <?php echo $stats['total_referred']; ?>
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

        <div class="referred-users">
            <h2>People You’ve Referred</h2>
            <?php if (mysqli_num_rows($ref_result) > 0) { ?>
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Join Date</th>
                            <th>Last Login</th>
                            <th>Login Count</th>
                            <th>Active Time (hrs)</th>
                            <th>Login Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($ref_result)) { ?>
                            <tr>
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
                <p class="no-referrals">You haven’t referred anyone yet. Share your link to get started!</p>
            <?php } ?>
        </div>
    </div>

    <!-- Back Button -->
    <div class="back-button-container">
        <a href="dashboard.php" class="back-button" aria-label="Back to dashboard">←</a>
    </div>

    <div class="popup" id="copyPopup">Copied!</div>

    <script>
        function copyLink() {
            const referralLink = "<?php echo htmlspecialchars($referral_link); ?>";
            const statement = "Join Unimaid Resources and unlock a world of academic tools and campus updates! Use my referral link:";
            const imageUrl = "https://unimaidresources.com.ng/images/referral-preview.jpg";

            const richText = `${statement}<br><a href="${referralLink}">${referralLink}</a><br><img src="${imageUrl}" alt="Unimaid Resources" style="max-width: 300px;">`;

            if (navigator.clipboard && navigator.clipboard.write) {
                const blob = new Blob([richText], { type: 'text/html' });
                const data = [new ClipboardItem({ 'text/html': blob, 'text/plain': new Blob([referralLink], { type: 'text/plain' }) })];
                navigator.clipboard.write(data).then(showPopup).catch(err => {
                    console.error('Clipboard write failed:', err);
                    fallbackCopy(referralLink);
                });
            } else {
                fallbackCopy(referralLink);
            }
        }

        function fallbackCopy(link) {
            const input = document.getElementById('referralLink');
            input.select();
            document.execCommand('copy');
            showPopup();
        }

        function showPopup() {
            const popup = document.getElementById('copyPopup');
            popup.classList.add('show');
            setTimeout(() => popup.classList.remove('show'), 2000);
        }
    </script>
</body>
</html>