<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "UNIMAIDCONNECT");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user details
$username = $_SESSION['username'];
$query = "SELECT * FROM users WHERE username = '$username'";
$result = $conn->query($query);

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
} else {
    echo "Error fetching user details.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h1>

    <div>
        <h2>Your Profile Details</h2>

        <!-- Display Profile Picture -->
        <?php if (!empty($user['profile_picture'])): ?>
            <img class="avatar-img max-un" src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="avatar" style="width: 80px; height: 80px; object-fit: cover; border-radius: 10px;">

        <?php else: ?>
            <p>No profile picture uploaded.</p>
        <?php endif; ?>

        <!-- Display Username -->
        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>

        <!-- Display Email -->
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
    </div>

    <a href="logout.php">Logout</a>
</body>
</html>
