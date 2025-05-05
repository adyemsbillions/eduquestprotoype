<?php
include 'db_connection.php';
session_start();

// Assuming admin is logged in, otherwise redirect to login page.
// if ($_SESSION['admin'] != true) {
//     header("Location: login.php");
//     exit;
// }

$sql = "SELECT * FROM wcw_images WHERE approved = 0";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Images</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to external CSS file -->
    <style>
    /* General styles */
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        padding: 0;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
    }

    /* Image box styling */
    .image-box {
        background-color: #ffffff;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        margin: 20px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        text-align: center;
        width: 300px;
        display: inline-block;
        vertical-align: top;
        transition: transform 0.3s ease;
    }

    .image-box:hover {
        transform: scale(1.05);
    }

    /* Styling for image */
    .image-box img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        cursor: pointer;
    }

    /* Name styling */
    .image-box p {
        margin: 10px 0;
        font-size: 18px;
        color: #333;
    }

    /* Approve button styling */
    .image-box a {
        display: inline-block;
        padding: 10px 20px;
        background-color: #4CAF50;
        color: #fff;
        text-decoration: none;
        border-radius: 4px;
        font-weight: bold;
        transition: background-color 0.3s;
    }

    .image-box a:hover {
        background-color: #45a049;
    }

    /* No images message */
    p {
        font-size: 20px;
        color: #333;
    }

    /* Modal (Hidden by default) */
    .modal {
        display: none;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.8);
        overflow: auto;
        padding-top: 60px;
    }

    /* Modal content (the image) */
    .modal-content {
        margin: auto;
        display: block;
        max-width: 80%;
        max-height: 80%;
        object-fit: contain;
    }

    /* Close button */
    .close {
        position: absolute;
        top: 15px;
        right: 35px;
        color: #fff;
        font-size: 40px;
        font-weight: bold;
        transition: 0.3s;
    }

    .close:hover,
    .close:focus {
        color: #f1f1f1;
        text-decoration: none;
        cursor: pointer;
    }
    </style>
</head>

<body>

    <div class="container">
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<div class='image-box'>
                    <img src='/dashboard/" . $row['image_path'] . "' alt='Image' onclick='openModal(\"" . $row['image_path'] . "\")' />
                    <p>" . $row['name'] . "</p>
                    <a href='approve_image.php?id=" . $row['id'] . "'>Approve</a>
                  </div>";
            }
        } else {
            echo "<p>No images waiting for approval.</p>";
        }
        ?>
    </div>

    <!-- Modal -->
    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage" />
    </div>

    <script>
    // Open the modal
    function openModal(imagePath) {
        var modal = document.getElementById("imageModal");
        var modalImage = document.getElementById("modalImage");
        modal.style.display = "block";
        modalImage.src = imagePath;
    }

    // Close the modal
    function closeModal() {
        var modal = document.getElementById("imageModal");
        modal.style.display = "none";
    }
    </script>

</body>

</html>

<?php
$conn->close();
?>