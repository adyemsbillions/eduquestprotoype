<?php
session_start(); // Start the session to access user data

include 'db_connection.php';

// Check if the user is logged in (i.e., id is in the session)
if (!isset($_SESSION['user_id'])) {
    echo "You need to be logged in to upload an image.";
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch the user's details from the database
$sql = "SELECT username, full_name, department, level FROM users WHERE id = ?"; // Use 'id' instead of 'user_id'
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id); // Binding 'user_id' to 'id' in the query
$stmt->execute();
$result = $stmt->get_result();

// Check if the user exists
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $username = $user['username'];
    $full_name = $user['full_name'];
    $department = $user['department'];
    $level = $user['level'];
} else {
    echo "User not found.";
    exit();
}

$message = ''; // Initialize an empty message variable

// Handle the image upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
    $name = $_POST['name'];
    $imageName = $_FILES['image']['name'];
    $imageTmpName = $_FILES['image']['tmp_name'];
    $imageSize = $_FILES['image']['size'];
    $imageError = $_FILES['image']['error'];
    
    $imageExt = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
    
    // Only allow certain file types (e.g., jpg, jpeg, png)
    $allowed = array('jpg', 'jpeg', 'png');
    
    if (in_array($imageExt, $allowed)) {
        if ($imageError === 0) {
            if ($imageSize < 5000000) { // 5MB limit
                $imageNewName = uniqid('', true) . "." . $imageExt;
                $imageDestination = 'uploads/' . $imageNewName;
                
                // Move the uploaded image to the uploads directory
                if (move_uploaded_file($imageTmpName, $imageDestination)) {
                    // Insert image and details into the database (waiting for approval)
                    $stmt = $conn->prepare("INSERT INTO wcw_images (user_id, name, image_path, approved) VALUES (?, ?, ?, 0)");
                    $stmt->bind_param("iss", $user_id, $name, $imageDestination);
                    $stmt->execute();
                    
                    $message = "Image uploaded successfully! Waiting for approval."; // Success message
                } else {
                    $message = "Error uploading image."; // Error message
                }
            } else {
                $message = "File size is too large!"; // Error message
            }
        } else {
            $message = "Error uploading file."; // Error message
        }
    } else {
        $message = "Invalid file type!"; // Error message
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Your Picture</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* General Body Styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        /* Container Styling */
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Title and Welcome Text */
        h2 {
            text-align: center;
            color: #333;
        }

        p {
            font-size: 16px;
            color: #666;
            text-align: center;
        }

        /* Form Styling */
        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Labels and Inputs */
        label {
            font-size: 14px;
            color: #333;
            font-weight: bold;
        }

        input[type="text"],
        input[type="file"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="file"]:focus {
            border-color:purple;
        }

        /* Submit Button */
        input[type="submit"] {
            background-color:purple;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: purple;
        }

        /* Error or Success Message */
        .message {
            text-align: center;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: bold;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Criteria and Benefits Section */
        .criteria {
            margin-top: 30px;
            background-color: #f0f0f0;
            padding: 20px;
            border-radius: 8px;
        }

        .criteria h3 {
            color: #333;
            font-size: 18px;
        }

        .criteria ul {
            list-style-type: square;
            padding-left: 20px;
        }

        .criteria ul li {
            font-size: 14px;
            color: #333;
            padding-bottom: 8px;
        }

        .criteria .benefits h4 {
            color: #5c9f9e;
            font-size: 16px;
        }

        .criteria .benefits ul li {
            font-size: 14px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Upload Your Picture for WCW Voting</h2>
        <p>Welcome, <?php echo htmlspecialchars($full_name); ?> (<?php echo htmlspecialchars($department); ?>, <?php echo htmlspecialchars($level); ?>)</p>
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form action="upload_wcw.php" method="POST" enctype="multipart/form-data">
            <label for="name">Your Name:</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($full_name); ?>" required readonly><br><br>

            <label for="image">Upload Picture:</label>
            <input type="file" name="image" required><br><br>

            <input type="submit" value="Submit" style="background-color:purple;">
        </form>

        <!-- Criteria and Benefits Section -->
        <div class="criteria">
            <h3>Criteria for Voting</h3>
            <ul>
                <li>Only approved images will be eligible for voting.</li>
                <li>All uploaded images are reviewed and approved by the admin before being made available for voting.</li>
                <li>Votes will be counted only for images that are live and approved.</li>
                <li>Each participant can vote only once per image.</li>
                <li>The participant with the highest votes at the end of the voting period will be declared the WCW (Woman Crush Wednesday) winner. for that week</li>
            <li>Defeated candidate's purple verification will be removed and returned back to blue verification a only WCW can  continue having purple verification</li>
            </ul>

            <div class="benefits">
                <h4>Benefits of WCW</h4>
                <ul>
                    <li>get paid &#8358;5,000 and 5GB mobile data</li>
                    <li>Get a chance to be featured as the WCW winner!</li>
                    <li>Receive recognition from your peers and the community.</li>
                    <li>Increase visibility and enhance your personal brand within the platform.</li>
                    <li>Be part of an exciting and fun community events of the week.</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
