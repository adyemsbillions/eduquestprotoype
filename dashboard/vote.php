<?php
session_start();
include 'db_connection.php';

// Check if user is logged in (optional, add your logic if needed)
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit();
// }

// Initialize voted array if not set
if (!isset($_SESSION['voted_wcw'])) {
    $_SESSION['voted_wcw'] = [];
}

// Fetch approved images ordered by highest votes first
$sql = "SELECT * FROM wcw_images WHERE approved = 1 ORDER BY votes DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote for Your WCW</title>
    <style>
        /* General Body Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }

        /* Container for all content */
        .container {
            width: 90%;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            box-sizing: border-box;
        }

        /* Title Styles */
        h2 {
            text-align: center;
            color: #800080; /* Matches purple theme */
            margin-bottom: 20px;
            font-size: 28px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Apply Button Styles */
        .apply-button {
            display: inline-block;
            padding: 12px 25px;
            margin-top: 20px;
            background-color: #800080;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .apply-button:hover {
            background-color: #9b30ff; /* Lighter purple for hover */
            transform: scale(1.05);
        }

        /* Image Box Styles */
        .image-box {
            display: inline-block;
            width: 100%;
            max-width: 250px;
            margin: 10px;
            padding: 15px;
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .image-box:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        /* Image Styles */
        .wcw-image {
            width: 100%;
            height: auto;
            border-radius: 10px;
            border: 2px solid #800080;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Name Styles */
        .name {
            font-size: 16px;
            font-weight: bold;
            margin-top: 10px;
            color: #800080;
        }

        /* Votes Count */
        .votes {
            font-size: 14px;
            color: #777;
            margin-top: 5px;
        }

        /* Vote Button */
        .vote-button {
            display: inline-block;
            padding: 10px 20px;
            margin-top: 10px;
            background-color: #800080;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .vote-button:hover {
            background-color: #9b30ff; /* Lighter purple for hover */
        }

        .vote-button.disabled {
            background-color: #ccc;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* Message Styles */
        .message {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .success {
            color: #28a745;
        }

        .error {
            color: #dc3545;
        }

        /* Back Button Styles */
        .back-button-container {
            position: fixed;
            bottom: 30px;
            left: 30px;
            z-index: 1000;
        }

        .back-button {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #800080;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background-color: #9b30ff;
            transform: scale(1.1);
        }

        /* Mobile Responsive Design */
        @media (max-width: 768px) {
            .container {
                width: 100%;
                padding: 10px;
            }

            .image-box {
                width: 90%;
                max-width: 100%;
                margin: 10px 0;
            }

            h2 {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            h2 {
                font-size: 20px;
            }

            .name {
                font-size: 14px;
            }

            .votes {
                font-size: 12px;
            }

            .vote-button, .apply-button {
                padding: 8px 16px;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Vote for Your Favorite WCW</h2>

        <!-- Apply Button Link -->
        <center>
            <a href="upload_wcw.php" class="apply-button">Apply to be a WCW</a>
        </center>

        <?php
        // Display message if there is one in the URL
        if (isset($_GET['success'])) {
            echo "<div class='message success'>" . htmlspecialchars($_GET['success']) . "</div>";
        }
        if (isset($_GET['error'])) {
            echo "<div class='message error'>" . htmlspecialchars($_GET['error']) . "</div>";
        }

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Check if the user has voted for this specific WCW
                $has_voted = in_array($row['id'], $_SESSION['voted_wcw']);

                echo "<div class='image-box'>
                    <img src='" . htmlspecialchars($row['image_path']) . "' alt='" . htmlspecialchars($row['name']) . "' class='wcw-image' />
                    <p class='name'>" . htmlspecialchars($row['name']) . "</p>
                    <p class='votes'>Votes: " . $row['votes'] . " / 500</p>";
                
                // Show vote button or disabled message based on voting status
                if ($has_voted) {
                    echo "<p>You have already voted for this WCW!</p>";
                } else {
                    echo "<a href='vote_action.php?id=" . $row['id'] . "' class='vote-button'>Vote</a>";
                }

                echo "</div>";
            }
        } else {
            echo "<p>No approved images to vote for.</p>";
        }
        ?>
    </div>

    <!-- Back Button -->
    <div class="back-button-container">
        <a href="dashboard.php" class="back-button" aria-label="Back to dashboard">←</a>
    </div>

</body>
</html>

<?php
$conn->close();
?>