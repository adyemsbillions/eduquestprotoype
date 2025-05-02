<?php
$conn = new mysqli('localhost', 'unimaid9_unimaidresources', '#adyems123AD', 'unimaid9_unimaidresources');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all upcoming events (test without WHERE clause first)
$sql_events = "SELECT * FROM events ORDER BY event_date ASC";  // Remove WHERE clause for debugging
$result_events = $conn->query($sql_events);

// Debug: Display the SQL query
// echo "SQL Query: " . $sql_events . "<br>";

// Check if events are returned
if ($result_events->num_rows > 0) {
    // echo "<p>Events found!</p>";
} else {
    echo "<p>No events found in the database.</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upcoming Events</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 90%;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .event-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            background-color: #f9f9f9;
        }

        .event-item h4 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }

        .event-item p {
            margin: 5px 0;
            font-size: 16px;
            color: #666;
        }

        .event-item .event-date,
        .event-item .event-location {
            font-size: 14px;
            color: #999;
        }

        .event-item img {
            max-width: 100%;
            height: auto;
            margin-top: 10px;
            border-radius: 8px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #6a1b9a;
            color: white;
            border-radius: 5px;
            text-decoration: none;
        }

        .btn:hover {
            background-color: #8e24aa;
        }

        .back-button-container {
            position: fixed;
            bottom: 30px;
            left: 30px;
            z-index: 1000;
        }

        .back-button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #6a1b9a;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background-color: #8e24aa;
            transform: scale(1.1);
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Upcoming Events</h2>

        <!-- Event List -->
        <div class="event-list">
            <?php
            if ($result_events->num_rows > 0) {
                while ($event = $result_events->fetch_assoc()) {
                    // Format event date
                    $event_date = date('F j, Y, g:i a', strtotime($event['event_date']));

                    echo '<div class="event-item">';
                    // Display event image if exists
                    if ($event['event_image']) {
                        echo '<img src="http://localhost/unimaidconnect/admin/' . htmlspecialchars($event['event_image']) . '" alt="Event Image">';
                    }
                    echo '<h4>' . htmlspecialchars($event['event_name']) . '</h4>';
                    echo '<p class="event-date">Date: ' . $event_date . '</p>';
                    echo '<p class="event-location">Location: ' . htmlspecialchars($event['event_location']) . '</p>';
                    echo '<p>' . htmlspecialchars($event['event_description']) . '</p>';
                    echo '<a href="event_details.php?event_id=' . $event['event_id'] . '" class="btn">View Details</a>';
                    echo '</div>';
                }
            } else {
                echo '<p>No upcoming events found.</p>';
            }
            ?>
        </div>
    </div>

    <!-- Back Button -->
    <div class="back-button-container">
        <a href="dashboard.php" class="back-button" aria-label="Back to dashboard">←</a>
    </div>

</body>
</html>

<?php
// Close the database connection
$conn->close();
?>