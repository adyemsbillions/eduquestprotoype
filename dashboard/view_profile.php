<?php
include('db_connection.php');

// Start session and get the logged-in user's ID
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get the user_id from the URL parameter (the user whose profile we're viewing)
if (isset($_GET['user_id'])) {
    $view_user_id = $_GET['user_id'];

    // Fetch user profile details from the database
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $view_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // If user doesn't exist, redirect back to all users
    if (!$user) {
        header("Location: users.php");
        exit();
    }
} else {
    // Redirect back if no user_id is provided
    header("Location: users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?>'s Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #6a1b9a;
            --primary-dark: #4a148c;
            --secondary: #e3e3e3;
            --text: #333;
            --white: #fff;
            --light-bg: #f9f9f9;
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
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: var(--white);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 32px;
            color: var(--primary);
            text-align: center;
            margin-bottom: 30px;
            font-weight: 600;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--secondary);
        }

        .profile-header a {
            display: block; /* Ensures the link wraps the image/icon properly */
        }

        .profile-header img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid var(--primary);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            cursor: pointer; /* Indicates clickability */
            transition: transform 0.2s ease;
        }

        .profile-header img:hover, .profile-header i:hover {
            transform: scale(1.05); /* Slight zoom on hover for both image and icon */
        }

        .profile-info {
            flex-grow: 1;
        }

        .profile-info h3 {
            font-size: 24px;
            color: var(--primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .profile-info p {
            font-size: 16px;
            color: #555;
            margin: 5px 0;
        }

        .profile-details {
            margin-top: 20px;
        }

        .profile-details div {
            margin-bottom: 15px;
            padding: 10px;
            background: var(--light-bg);
            border-radius: 8px;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .profile-details label {
            font-weight: 600;
            color: var(--primary);
            display: inline-block;
            width: 150px;
        }

        .profile-details span {
            color: #555;
        }

        .back-button {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 25px;
            background: var(--primary);
            color: var(--white);
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s ease, transform 0.2s ease;
            text-align: center;
        }

        .back-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Verification badge styles */
        .verified-badge {
            position: relative;
            font-size: 13px;
            color: #1e90ff;
        }

        .verified-badge .fa-check {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 10px;
            color: white;
        }

        .gold-badge {
            position: relative;
            font-size: 13px;
            color: #e0b20b;
        }

        .gold-badge .fa-check {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 10px;
            color: white;
        }

        .black-badge {
            position: relative;
            font-size: 13px;
            color: #000000;
        }

        .black-badge .fa-check {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 10px;
            color: white;
        }

        .pink-badge {
            position: relative;
            font-size: 13px;
            color: #e91e63;
        }

        .pink-badge .fa-check {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 10px;
            color: white;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
                width: 90%;
            }

            h1 {
                font-size: 28px;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-header img, .profile-header i {
                width: 100px;
                height: 100px;
                font-size: 100px; /* Adjust icon size */
            }

            .profile-info h3 {
                font-size: 20px;
            }

            .profile-details label {
                width: 120px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 15px;
                width: 95%;
            }

            h1 {
                font-size: 24px;
            }

            .profile-header img, .profile-header i {
                width: 80px;
                height: 80px;
                font-size: 80px; /* Adjust icon size */
            }

            .profile-info h3 {
                font-size: 18px;
            }

            .profile-details label {
                display: block;
                width: auto;
                margin-bottom: 5px;
            }

            .back-button {
                padding: 10px 20px;
                font-size: 14px;
            }

            .verified-badge, .gold-badge, .black-badge, .pink-badge {
                font-size: 11px;
            }

            .verified-badge .fa-check, .gold-badge .fa-check, .black-badge .fa-check, .pink-badge .fa-check {
                font-size: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($user['username']); ?>'s Profile</h1>

        <!-- Profile Header -->
        <div class="profile-header">
            <a href="<?php echo (!empty($user['profile_picture']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/dashboard/' . $user['profile_picture'])) ? '/dashboard/' . htmlspecialchars($user['profile_picture']) : '#'; ?>" target="_blank">
                <?php if (!empty($user['profile_picture']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/dashboard/' . $user['profile_picture'])): ?>
                    <img src="/dashboard/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture">
                <?php else: ?>
                    <i class="fas fa-user-circle" style="font-size: 120px; color: var(--primary);"></i>
                <?php endif; ?>
            </a>
            <div class="profile-info">
                <h3>
                    <?php echo htmlspecialchars($user['username']); ?>
                    <?php if (isset($user['verified']) && $user['verified'] == 1): ?>
                        <span class="fa fa-circle verified-badge" title="Verified User">
                            <span class="fa fa-check"></span>
                        </span>
                    <?php endif; ?>
                    <?php if (isset($user['verified']) && $user['verified'] == 2): ?>
                        <span class="fa fa-circle gold-badge" title="Gold Verified User">
                            <span class="fa fa-check"></span>
                        </span>
                    <?php endif; ?>
                    <?php if (isset($user['verified']) && $user['verified'] == 3): ?>
                        <span class="fa fa-circle black-badge" title="Black Verified User">
                            <span class="fa fa-check"></span>
                        </span>
                    <?php endif; ?>
                    <?php if (isset($user['verified']) && $user['verified'] == 4): ?>
                        <span class="fa fa-circle pink-badge" title="Pink Verified User">
                            <span class="fa fa-check"></span>
                        </span>
                    <?php endif; ?>
                </h3>
                <p><?php echo htmlspecialchars($user['full_name']); ?></p>
            </div>
        </div>

        <!-- Profile Details -->
        <div class="profile-details">
            <div>
                <label>Full Name:</label>
                <span><?php echo htmlspecialchars($user['full_name']); ?></span>
            </div>
            <div>
                <label>Address:</label>
                <span><?php echo htmlspecialchars($user['address']); ?></span>
            </div>
            <div>
                <label>Department:</label>
                <span><?php echo htmlspecialchars($user['department']); ?></span>
            </div>
            <div>
                <label>Faculty:</label>
                <span><?php echo htmlspecialchars($user['faculty']); ?></span>
            </div>
            <div>
                <label>Level:</label>
                <span><?php echo htmlspecialchars($user['level']); ?></span>
            </div>
            <div>
                <label>Phone Number:</label>
                <span><?php echo htmlspecialchars($user['phone_number']); ?></span>
            </div>
            <div>
                <label>About Me:</label>
                <span><?php echo nl2br(htmlspecialchars($user['about_me'])); ?></span>
            </div>
            <div>
                <label>Interests:</label>
                <span><?php echo nl2br(htmlspecialchars($user['interests'])); ?></span>
            </div>
            <div>
                <label>Relationship Status:</label>
                <span><?php echo htmlspecialchars($user['relationship_status']); ?></span>
            </div>
        </div>

        <!-- Back Button -->
        <a href="users.php" class="back-button">Back to Users List</a>
    </div>
</body>
</html>