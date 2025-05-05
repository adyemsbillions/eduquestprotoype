<?php
// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json'); // Set header for JSON response

    include('db_connection.php'); // Include database connection

    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true); // Create uploads directory if it doesn't exist
    }

    $target_file = $target_dir . basename($_FILES["media"]["name"]);
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Validate file type
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'mp4'];
    if (!in_array($file_type, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, JPEG, PNG, GIF, and MP4 are allowed.']);
        exit;
    }

    // Move uploaded file
    if (move_uploaded_file($_FILES["media"]["tmp_name"], $target_file)) {
        // Insert file path into database
        $file_type = ($file_type === 'mp4') ? 'video' : (($file_type === 'gif') ? 'gif' : 'image');
        $stmt = $conn->prepare("INSERT INTO media_slideshow (file_path, file_type) VALUES (?, ?)");
        $stmt->bind_param("ss", $target_file, $file_type);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'File uploaded successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error saving file information to database.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Error uploading file.']);
    }

    $conn->close();
    exit; // Stop further execution after handling the upload
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Media</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background-color: #f4f4f4;
    }

    .upload-container {
        width: 100%;
        max-width: 500px;
        padding: 20px;
        border: 1px solid #ccc;
        background-color: #fff;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        text-align: center;
    }

    .upload-container h1 {
        margin-bottom: 20px;
    }

    .upload-container input[type="file"] {
        margin-bottom: 20px;
    }

    .upload-container button {
        padding: 10px 20px;
        background-color: #007bff;
        color: #fff;
        border: none;
        cursor: pointer;
    }

    .upload-container button:hover {
        background-color: #0056b3;
    }

    .message {
        margin-top: 20px;
        color: green;
    }

    .error {
        margin-top: 20px;
        color: red;
    }
    </style>
</head>

<body>
    <div class="upload-container">
        <h1>Upload Media</h1>
        <form id="uploadForm" enctype="multipart/form-data">
            <input type="file" name="media" accept="image/*, video/*" required>
            <button type="submit">Upload</button>
        </form>
        <div id="message" class="message"></div>
        <div id="error" class="error"></div>
    </div>

    <script>
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent form submission

        const formData = new FormData(this);
        const messageDiv = document.getElementById('message');
        const errorDiv = document.getElementById('error');

        // Clear previous messages
        messageDiv.textContent = '';
        errorDiv.textContent = '';

        // Send the file to the server
        fetch('', { // Use an empty string to submit to the same file
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.textContent = data.message || 'File uploaded successfully!';
                } else {
                    errorDiv.textContent = data.message || 'Error uploading file.';
                }
            })
            .catch(error => {
                errorDiv.textContent = 'An error occurred. Please try again.';
                console.error('Error:', error); // Log errors to the console
            });
    });
    </script>
</body>

</html>