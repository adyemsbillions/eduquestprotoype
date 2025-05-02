<?php
require_once 'db_connection.php';

// Check if the user is logged in and fetch user info
session_start(); // Start the session
$user_id = $_SESSION['user_id']; // Assuming user_id is stored in the session after login

// Fetch user details
$sql = "SELECT profile_picture, address, department, faculty, level, id_number, phone_number, stays_in_hostel, full_name, verified FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Check for missing fields
$missing_fields = [];

$required_fields = [
    'profile_picture' => 'Profile Picture',
    'address' => 'Address',
    'department' => 'Department',
    'faculty' => 'Faculty',
    'level' => 'Level',
    'id_number' => 'ID Number',
    'phone_number' => 'Phone Number',
    'stays_in_hostel' => 'Stays in Hostel',
    'full_name' => 'Full Name'
];

foreach ($required_fields as $field => $label) {
    if (empty($user[$field])) {
        $missing_fields[] = $label;
    }
}

// Handle user verification request
if (isset($_POST['request_verification'])) {
    if (count($missing_fields) > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please complete your profile before requesting verification. Missing fields: ' . implode(', ', $missing_fields)
        ]);
    } else {
        // Insert the verification request into the database
        $sql = "INSERT INTO verification_requests (user_id, status) VALUES (?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Your request for verification has been sent.'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'There was an error while processing your verification request. Please try again later.'
            ]);
        }
    }
}

// Handle verification status update (for admin or backend action)
if (isset($_POST['verify_user']) && $_POST['verify_user'] == 'true') {
    // Update the user's verification status in the database
    $sql = "UPDATE users SET verified = 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'You are now verified!'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'There was an error updating your verification status. Please try again later.'
        ]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Verification</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
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

        .profile-status {
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #ddd;
        }

        .profile-status ul {
            list-style: none;
            padding: 0;
        }

        .profile-status ul li {
            margin: 10px 0;
            font-size: 16px;
        }

        .profile-status ul li span {
            font-weight: bold;
        }

        .alert {
            display: none;
            padding: 15px;
            margin-top: 20px;
            font-size: 16px;
            text-align: center;
            border-radius: 5px;
            transition: opacity 0.5s ease;
        }

        .alert.success {
            background-color: #4CAF50;
            color: white;
        }

        .alert.error {
            background-color: #f44336;
            color: white;
        }

        .request-verification-btn {
            display: block;
            width: 100%;
            padding: 15px;
            font-size: 16px;
            text-align: center;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .request-verification-btn:hover {
            background-color: #45a049;
        }

    </style>
</head>
<body>

<div class="container">
    <h2>Request Verification</h2>

    <!-- Alert Box -->
    <div id="alert-box" class="alert"></div>

    <div class="profile-status">
        <h3>Your Profile Status</h3>
        <p>Below are the details you have provided. Please complete your profile before requesting verification:</p>
        <ul>
            <?php if (!empty($missing_fields)): ?>
                <?php foreach ($missing_fields as $field): ?>
                    <li><span>Missing:</span> <?php echo $field; ?></li>
                <?php endforeach; ?>
            <?php else: ?>
                <li><span>Profile Complete:</span> All required fields are filled!</li>
            <?php endif; ?>
        </ul>

        <!-- Show current verification status -->
        <div id="verification-status">
            <?php if ($user['verified'] == 1): ?>
                <p><strong>Status:</strong> Verified</p>
            <?php elseif ($user['verified'] == 0): ?>
                <p><strong>Status:</strong> Pending Verification</p>
            <?php endif; ?>
        </div>
    </div>

    <button id="request-verification" class="request-verification-btn">
        <?php echo (count($missing_fields) > 0) ? 'Complete Your Profile First' : 'Request Verification'; ?>
    </button>
</div>

<script>
$(document).ready(function() {
    // Function to show alert
    function showAlert(status, message) {
        var alertBox = $("#alert-box");
        alertBox.removeClass("success error").addClass(status).text(message).fadeIn();

        setTimeout(function() {
            alertBox.fadeOut();
        }, 3000); // Hide after 3 seconds
    }

    // Handle request verification
    $("#request-verification").on("click", function() {
        $.ajax({
            url: "", // Same file, handling everything in one page
            type: "POST",
            data: { 
                request_verification: true 
            },
            success: function(response) {
                var data = JSON.parse(response);
                showAlert(data.status, data.message); // Show alert
                if (data.status == 'success') {
                    location.reload(); // Reload the page if the verification request was successful
                }
            }
        });
    });
});
</script>

</body>
</html>
