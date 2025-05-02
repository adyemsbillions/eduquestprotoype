<?php
$conn = new mysqli('localhost', 'unimaid9_unimaidresources', '#adyems123AD', 'unimaid9_unimaidresources');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get event ID from URL
if (isset($_GET['event_id'])) {
    $event_id = $_GET['event_id'];

    // Fetch event details
    $sql_event = "SELECT * FROM events WHERE event_id = ?";
    $stmt = $conn->prepare($sql_event);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $event = $result->fetch_assoc();
        $event_date = date('F j, Y, g:i a', strtotime($event['event_date']));
    } else {
        echo "Event not found.";
        exit;
    }
} else {
    echo "Event ID not provided.";
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details</title>
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

        .event-detail img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }

        .event-detail h3 {
            font-size: 24px;
            margin-top: 15px;
        }

        .event-detail p {
            margin: 10px 0;
        }

        .btn {
            padding: 8px 15px;
            background-color: #2196F3;
            color: white;
            font-size: 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }

        .btn:hover {
            background-color: #1976d2;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Event Details</h2>

        <div class="event-detail">
            <?php
            // Display event image
            if ($event['event_image']) {
                echo '<img src="http://localhost/unimaidconnect/admin/' . htmlspecialchars($event['event_image']) . '" alt="Event Image">';
            }
            ?>
            <h3><?php echo htmlspecialchars($event['event_name']); ?></h3>
            <p><strong>Date and Time:</strong> <?php echo $event_date; ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($event['event_location']); ?></p>
            <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($event['event_description'])); ?></p>
            <a href="events.php" class="btn">Back to Events List</a>
        </div>
    </div>

</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
