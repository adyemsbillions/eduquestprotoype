<?php
$conn = new mysqli('localhost', 'unimaid9_unimaidresources', '#adyems123AD', 'unimaid9_unimaidresources');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session and get the logged-in user's ID
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

$user_id = $_SESSION['user_id'];

// Initialize a variable for the message
$message = "";

// Handle form submission for adding a marketplace post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_post'])) {
    $item_name = $_POST['item_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    
    // Handle multiple image uploads
    $image_paths = [];
    if (!empty($_FILES['images']['name'][0])) {
        $upload_dir = "uploads/marketplace_images/"; // Make sure this directory exists
        foreach ($_FILES['images']['name'] as $key => $image_name) {
            $target_file = $upload_dir . basename($image_name);
            
            // Use getimagesize() to validate image types
            $image_info = getimagesize($_FILES['images']['tmp_name'][$key]);
            if ($image_info !== false) {
                // The file is an image, proceed with uploading
                if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $target_file)) {
                    // Store the image paths in an array (relative path)
                    $image_paths[] = $target_file;
                }
            } else {
                $message = "<p style='color: red;'>Invalid file type. Please upload only image files (JPG, PNG, GIF).</p>";
                break;
            }
        }
    }

    // Convert image paths array to JSON format
    $image_paths_json = json_encode($image_paths);

    // Insert the post into the marketplace posts table (with status 'pending' for admin approval)
    $sql = "INSERT INTO marketplace_posts (user_id, item_name, description, price, images, status) 
            VALUES (?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issds", $user_id, $item_name, $description, $price, $image_paths_json);
    if ($stmt->execute()) {
        $message = "<p style='color: green;'>Your post has been submitted and is awaiting admin approval.</p>";
    } else {
        $message = "<p style='color: red;'>Failed to submit your post. Please try again later.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Your Item - Marketplace</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .form-group input[type="file"] {
            padding: 5px;
        }

        .form-group button {
            padding: 10px 20px;
            background-color: #6a1b9a;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .form-group button:hover {
            background-color: #8e24aa;
        }

        .message {
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Post Your Item</h2>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="item_name">Item Name</label>
                <input type="text" id="item_name" name="item_name" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" required></textarea>
            </div>

            <div class="form-group">
                <label for="price">Price ($)</label>
                <input type="number" id="price" name="price" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="images">Upload Images (You can select multiple images)</label>
                <input type="file" id="images" name="images[]" accept="image/*" multiple>
            </div>

            <div class="form-group">
                <button type="submit" name="submit_post">Submit Post</button>
            </div>
        </form>

        <!-- Display the success or error message here -->
        <div class="message">
            <?php echo $message; ?>
        </div>
    </div>
</body>
</html>
