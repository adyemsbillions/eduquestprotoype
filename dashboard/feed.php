<?php
// feed.php
include('db_connection.php');
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch allowed user IDs from the database
$sql_allowed = "SELECT user_id FROM allowed_posters";
$allowed_result = $conn->query($sql_allowed);
$allowed_user_ids = [];
while ($row = $allowed_result->fetch_assoc()) {
    $allowed_user_ids[] = $row['user_id'];
}
$can_post = in_array($user_id, $allowed_user_ids); // Check if the user can post

// Handle the form submission for users who can post
if ($can_post && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $feed_content = $_POST['feed_content'];
    $link_url = $_POST['link_url'] ?? '';  // Optional link
    $image_paths = '';  // Initialize image paths variable

    // Handle file uploads
    if (isset($_FILES['images']) && count($_FILES['images']['name']) > 0) {
        $image_paths_array = [];

        // Loop through the files and upload them
        for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
            $image_name = $_FILES['images']['name'][$i];
            $image_tmp = $_FILES['images']['tmp_name'][$i];
            $image_path = 'uploads/' . basename($image_name);

            // Move the uploaded image to the "uploads" folder
            if (move_uploaded_file($image_tmp, $image_path)) {
                $image_paths_array[] = $image_path;  // Add the path to the array
            }
        }

        // Convert the array to a comma-separated string
        $image_paths = implode(',', $image_paths_array);
    }

    // Insert the feed data into the database
    $sql = "INSERT INTO feed (user_id, feed_content, link_url, image_paths) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $user_id, $feed_content, $link_url, $image_paths);

    if ($stmt->execute()) {
        // Redirect to the feed page
        header("Location: feed.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch all the feed posts
$sql = "SELECT f.feed_id, f.feed_content, f.link_url, f.image_paths, u.username
        FROM feed f
        JOIN users u ON f.user_id = u.id
        ORDER BY f.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed</title>
    <style>
        /* Global Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        /* Body and Layout */
        body {
            background-color: #f9f9f9;
            padding: 20px;
            font-size: 16px;
            color: #333;
        }

        /* Form Container */
        .form-container {
            background-color: #fff;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Form Header */
        .form-container h2 {
            color: #6a1b9a;
            text-align: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        /* Textarea */
        .form-container textarea {
            width: 100%;
            padding: 12px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 6px;
            background-color: #fafafa;
            resize: vertical;
            margin-bottom: 15px;
        }

        /* URL Input */
        .form-container input[type="url"] {
            width: 100%;
            padding: 12px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 6px;
            background-color: #fafafa;
            margin-bottom: 15px;
        }

        /* File Input */
        .form-container input[type="file"] {
            margin-top: 12px;
            font-size: 14px;
        }

        /* Submit Button */
        .form-container button {
            padding: 12px 18px;
            background-color: #6a1b9a;
            color: #fff;
            font-size: 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        .form-container button:hover {
            background-color: #8e24aa;
        }

        /* Feed Post Layout */
        .feed-post {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Feed Post Header */
        .feed-post h3 {
            color: #6a1b9a;
            font-size: 20px;
            margin-bottom: 10px;
        }

        /* Feed Post Content */
        .feed-post p {
            font-size: 16px;
            color: #555;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        /* Feed Link */
        .feed-post a {
            color: #6a1b9a;
            text-decoration: none;
        }

        .feed-post a:hover {
            text-decoration: underline;
        }

        /* Feed Images Container (Flexbox) */
        .feed-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
            justify-content: center; /* Center the images */
        }

        /* Feed Images */
        .feed-images img {
            width: 100%;
            max-width: 100%; /* Ensure images are responsive */
            margin: 5px;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            height: auto;
            object-fit: cover;
        }

        /* Show More Images Button */
        .show-more-images {
            margin-top: 12px;
            font-size: 14px;
            color: #6a1b9a;
            cursor: pointer;
            text-align: center;
            transition: color 0.3s ease;
        }

        .show-more-images:hover {
            color: #8e24aa;
        }

        /* Feed Post Footer */
        .feed-post-footer {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #888;
            margin-top: 15px;
        }

        .feed-post-footer span {
            margin-right: 15px;
        }

        /* Responsiveness */
        @media (max-width: 768px) {
            /* Adjust the layout for smaller screens */
            .form-container {
                padding: 20px;
            }

            .form-container h2 {
                font-size: 20px;
            }

            .feed-post {
                padding: 15px;
            }

            .feed-post h3 {
                font-size: 18px;
            }

            .feed-post p {
                font-size: 14px;
            }

            /* Adjust image layout */
            .feed-images {
                flex-direction: column; /* Stack images vertically on smaller screens */
                align-items: center;
            }

            .feed-images img {
                max-width: 90%; /* Ensure images are responsive and fit better */
            }

            /* Adjust button width for mobile */
            .form-container button {
                width: 100%;
            }

            .show-more-images {
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {
            /* Further adjustments for very small screens */
            .feed-images img {
                max-width: 100%; /* Ensure images take full width on small devices */
                margin: 5px;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-thumb {
            background-color: #6a1b9a;
            border-radius: 5px;
        }

        ::-webkit-scrollbar-track {
            background-color: #f1f1f1;
        }

        /* Tooltip Styling */
        .tooltip {
            display: inline-block;
            position: relative;
        }

        .tooltip:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #333;
            color: #fff;
            padding: 5px;
            border-radius: 4px;
            font-size: 12px;
        }
    </style>

    <script>
        function toggleImages(feed_id) {
            var images = document.getElementById('images-' + feed_id).children;
            var button = document.getElementById('show-more-' + feed_id);

            var allVisible = true;
            for (var i = 1; i < images.length; i++) {
                if (images[i].style.display === 'none') {
                    images[i].style.display = 'block';
                    allVisible = false;
                } else {
                    images[i].style.display = 'none';
                }
            }

            button.innerHTML = allVisible ? 'Show More Images' : 'Show Less Images';
        }
    </script>
</head>
<body>

    <!-- Feed Posting Form: Only show if user is allowed -->
    <?php if ($can_post): ?>
        <div class="form-container">
            <h2>Create New Feed Post</h2>
            <form method="POST" enctype="multipart/form-data">
                <textarea name="feed_content" placeholder="Write your post here..." required></textarea><br>
                <input type="url" name="link_url" placeholder="Optional link URL" /><br>
                <input type="file" name="images[]" accept="image/*" multiple /><br>
                <br>
                <button type="submit">Post</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Display Feed Posts (all users can see these) -->
    <?php while ($feed = $result->fetch_assoc()): ?>
        <div class="feed-post">
            <h3><?php echo htmlspecialchars($feed['username']); ?> posted:</h3>
            <p><?php echo nl2br(htmlspecialchars($feed['feed_content'])); ?></p>

            <!-- Display the link if exists -->
            <?php if (!empty($feed['link_url'])): ?>
                <p><a href="<?php echo htmlspecialchars($feed['link_url']); ?>" target="_blank">Click here to visit the link</a></p>
            <?php endif; ?>

            <!-- Display images if they exist -->
            <?php if (!empty($feed['image_paths'])): ?>
                <div class="feed-images" id="images-<?php echo $feed['feed_id']; ?>">
                    <?php
                        $images = explode(',', $feed['image_paths']);
                        foreach ($images as $index => $image) {
                            if ($index == 0) {
                                echo "<img src='" . htmlspecialchars($image) . "' alt='Feed Image'>";
                            } else {
                                echo "<img src='" . htmlspecialchars($image) . "' alt='Feed Image' style='display:none'>";
                            }
                        }
                    ?>
                </div>
                <!-- Show more images button -->
                <div class="show-more-images" id="show-more-<?php echo $feed['feed_id']; ?>" onclick="toggleImages(<?php echo $feed['feed_id']; ?>)">
                    Show More Images
                </div>
            <?php endif; ?>
        </div>
    <?php endwhile; ?>

</body>
</html>