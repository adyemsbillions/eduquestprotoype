<?php
// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Check if a file has been uploaded
    if (isset($_FILES['ad_image']) && $_FILES['ad_image']['error'] === UPLOAD_ERR_OK) {

        // Get the uploaded file info
        $fileTmpPath = $_FILES['ad_image']['tmp_name'];
        $fileName = $_FILES['ad_image']['name'];
        $fileSize = $_FILES['ad_image']['size'];
        $fileType = $_FILES['ad_image']['type'];

        // Define allowed file extensions (you can add more formats if needed)
        $allowedExtensions = ['image/jpeg', 'image/png', 'image/gif'];

        // Check if the file type is allowed
        if (in_array($fileType, $allowedExtensions)) {

            // Generate a unique file name to avoid conflicts
            $uploadDir = 'uploads/'; // Directory where images will be stored
            $filePath = $uploadDir . uniqid() . '-' . basename($fileName);

            // Move the uploaded file to the desired directory
            if (move_uploaded_file($fileTmpPath, $filePath)) {

                // Get the ad link from the form
                $adLink = $_POST['ad_link'];

                // Connect to the database
                $servername = "localhost";
                $username = "unimaid9_unimaidresources"; // Your MySQL username
                $password = "#adyems123AD"; // Your MySQL password
                $dbname = "unimaid9_unimaidresources"; // Replace with your database name

                // Create connection
                $conn = new mysqli($servername, $username, $password, $dbname);

                // Check connection
                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }

                // Update the existing ad data in the database (replace the previous ad with id = 0)
                $sql = "UPDATE ads SET image_url = '$filePath', link_url = '$adLink', status = 'active' WHERE id = 0";

                if ($conn->query($sql) === TRUE) {
                    // Redirect to the dashboard page after successful upload
                    header("Location: dashboard.php"); // Replace with your dashboard URL
                    exit();
                } else {
                    echo "Error: " . $sql . "<br>" . $conn->error;
                }

                // Close the database connection
                $conn->close();
            } else {
                echo "Error: Could not upload the file.";
            }
        } else {
            echo "Error: Invalid file type. Only images are allowed.";
        }
    } else {
        echo "Error: No file uploaded.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post New Ad</title>

    <style>
    body {
        font-family: 'Arial', sans-serif;
        background-color: #f4f4f9;
        margin: 0;
        padding: 0;
    }

    header {
        background-color: purple;
        color: white;
        text-align: center;
        padding: 20px;
    }

    h1 {
        font-size: 2.5rem;
    }

    .container {
        width: 50%;
        margin: 0 auto;
        background-color: white;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    form {
        display: flex;
        flex-direction: column;
    }

    label {
        margin-bottom: 8px;
        font-size: 1.1rem;
        color: #333;
    }

    input[type="file"],
    input[type="url"] {
        padding: 10px;
        font-size: 1rem;
        margin-bottom: 20px;
        border: 2px solid #ddd;
        border-radius: 5px;
    }

    input[type="submit"] {
        background-color: purple;
        color: white;
        padding: 12px;
        font-size: 1.1rem;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    input[type="submit"]:hover {
        background-color: purple;
    }

    .error {
        color: red;
        font-size: 1rem;
        margin-bottom: 20px;
    }
    </style>
</head>

<body>

    <header>
        <h1>Post a New Ad</h1>
    </header>

    <div class="container">
        <!-- Form to upload the ad image and URL -->
        <form action="post_ads.php" method="POST" enctype="multipart/form-data">
            <label for="ad_image">Ad Image:</label>
            <input type="file" name="ad_image" id="ad_image" required><br>

            <label for="ad_link">Ad Link:</label>
            <input type="url" name="ad_link" id="ad_link" required><br>

            <input type="submit" value="Submit Ad">
        </form>
    </div>

</body>

</html>