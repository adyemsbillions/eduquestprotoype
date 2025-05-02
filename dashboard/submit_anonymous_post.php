<?php
// Assuming you've included your database connection file
require_once 'db_connection.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the post content
    $content = $_POST['content'];
    
    // Check if an image is uploaded
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image = $_FILES['image'];
        
        // Generate a unique filename for the image
        $imageExtension = pathinfo($image['name'], PATHINFO_EXTENSION);
        $imageName = uniqid() . '.' . $imageExtension;
        
        // Set the upload directory
        $uploadDir = 'uploads/';
        $uploadPath = $uploadDir . $imageName;
        
        // Check if upload directory exists, if not, create it
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Move the uploaded file to the server
        if (move_uploaded_file($image['tmp_name'], $uploadPath)) {
            $imagePath = $uploadPath;
        } else {
            echo "Error uploading image.";
            exit;
        }
    }

    // Insert post content and image path into the database
    $sql = "INSERT INTO anonymous_posts (content, image_path) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $content, $imagePath);
    
    if ($stmt->execute()) {
        echo "Post submitted successfully!";
    } else {
        echo "Error submitting post.";
    }
}
?>
