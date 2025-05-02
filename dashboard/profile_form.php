<?php
// Include the database connection
include("db_connection.php");

// Start session to get user_id
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get the logged-in user's ID
$user_id = $_SESSION['user_id'];

// Check database connection
if (!$conn) {
    die("Database connection failed.");
}

// Handle form submission for profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    // Sanitize and retrieve form data
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $address = $conn->real_escape_string($_POST['address'] ?? '');
    $faculty = $conn->real_escape_string($_POST['faculty'] ?? '');
    $department = $conn->real_escape_string($_POST['department'] ?? '');
    $level = $conn->real_escape_string($_POST['level'] ?? '');
    $stays_in_hostel = $conn->real_escape_string($_POST['stays_in_hostel'] ?? '');
    $relationship_status = $conn->real_escape_string($_POST['relationship_status'] ?? '');
    $phone_number = $conn->real_escape_string($_POST['phone_number'] ?? '');
    $gender = $conn->real_escape_string($_POST['gender'] ?? '');
    $about_me = $conn->real_escape_string($_POST['about_me'] ?? '');
    $interests = $conn->real_escape_string($_POST['interests'] ?? '');

    // Update query with all fields excluding id_number
    $update_sql = "UPDATE users SET 
        full_name = '$full_name', 
        address = '$address', 
        faculty = '$faculty', 
        department = '$department', 
        level = '$level',
        stays_in_hostel = '$stays_in_hostel',
        relationship_status = '$relationship_status',
        phone_number = '$phone_number', 
        gender = '$gender', 
        about_me = '$about_me', 
        interests = '$interests', 
        updated_at = NOW()
        WHERE id = '$user_id'";
    
    // Execute update and check result
    if ($conn->query($update_sql)) {
        // Redirect to profile.php on success
        header("Location: profile.php");
        exit();
    } else {
        $error_message = "Failed to update profile: " . $conn->error;
    }
}

// Fetch the user profile information from the database
$sql = "SELECT * FROM users WHERE id = '$user_id'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "User not found.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 90%;
            max-width: 600px;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"], textarea, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        textarea {
            resize: vertical;
            height: 100px;
        }
        button[type="submit"] {
            background-color: purple;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
            width: 100%;
        }
        button[type="submit"]:hover {
            background-color: darkviolet;
        }
        .message {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Your Profile</h1>

        <!-- Display error message if update fails -->
        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Profile Form -->
        <form method="POST" action="">

            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="form-group">
                <label for="phone_number">Phone Number:</label>
                <input type="text" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="address">Address:</label>
                <input type="text" name="address" value="<?php echo htmlspecialchars($user['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="faculty">Faculty:</label>
                <input type="text" name="faculty" value="<?php echo htmlspecialchars($user['faculty'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="department">Department:</label>
                <input type="text" name="department" value="<?php echo htmlspecialchars($user['department'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="level">Level:</label>
                <input type="text" name="level" value="<?php echo htmlspecialchars($user['level'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="stays_in_hostel">Stays in Hostel:</label>
                <select name="stays_in_hostel">
                    <option value="Yes" <?php echo ($user['stays_in_hostel'] ?? '') == 'Yes' ? 'selected' : ''; ?>>Yes</option>
                    <option value="No" <?php echo ($user['stays_in_hostel'] ?? '') == 'No' ? 'selected' : ''; ?>>No</option>
                </select>
            </div>

            <div class="form-group">
                <label for="relationship_status">Relationship Status:</label>
                <select name="relationship_status">
                    <option value="Single" <?php echo ($user['relationship_status'] ?? '') == 'Single' ? 'selected' : ''; ?>>Single</option>
                    <option value="In a Relationship" <?php echo ($user['relationship_status'] ?? '') == 'In a Relationship' ? 'selected' : ''; ?>>In a Relationship</option>
                    <option value="Married" <?php echo ($user['relationship_status'] ?? '') == 'Married' ? 'selected' : ''; ?>>Married</option>
                    <option value="Divorced" <?php echo ($user['relationship_status'] ?? '') == 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                </select>
            </div>

            <div class="form-group">
                <label for="gender">Gender:</label>
                <select name="gender">
                    <option value="Male" <?php echo ($user['gender'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo ($user['gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>

            <div class="form-group">
                <label for="about_me">About Me:</label>
                <textarea name="about_me"><?php echo htmlspecialchars($user['about_me'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="form-group">
                <label for="interests">Interests:</label>
                <textarea name="interests"><?php echo htmlspecialchars($user['interests'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <button type="submit" name="update_profile">Update Profile</button>
        </form>
    </div>
</body>
</html>
