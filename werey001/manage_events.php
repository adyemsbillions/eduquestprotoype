<?php
// Database connection
include('db_connection.php');

// Initialize variables
$event_name = $event_description = $event_date = $event_location = $image_path = '';

// Handle form submission for creating or updating an event
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $event_name = $_POST['event_name'] ?? '';
    $event_description = $_POST['event_description'] ?? '';
    $event_date = $_POST['event_date'] ?? '';
    $event_location = $_POST['event_location'] ?? '';

    // Handle optional image upload
    if (!empty($_FILES['event_image']['name'])) {
        $upload_dir = "uploads/event_images/";

        // Ensure the directory exists
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true); // Create the directory if it doesn't exist
        }

        $image_name = basename($_FILES['event_image']['name']);
        $target_file = $upload_dir . $image_name;
        $image_type = mime_content_type($_FILES['event_image']['tmp_name']);

        // Ensure the file is an image
        if (strpos($image_type, 'image') !== false) {
            // Move the uploaded file to the target directory
            if (move_uploaded_file($_FILES['event_image']['tmp_name'], $target_file)) {
                $image_path = $target_file; // Store the image path if uploaded successfully
            } else {
                echo "Error uploading the image.";
            }
        } else {
            echo "Uploaded file is not an image.";
        }
    }

    // Insert or update event in the database
    if (isset($_POST['event_id']) && !empty($_POST['event_id'])) {
        // Update event if an event ID is provided
        $event_id = $_POST['event_id'];
        $update_sql = "UPDATE events SET event_name = ?, event_description = ?, event_date = ?, event_location = ?, event_image = ? WHERE event_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssssi", $event_name, $event_description, $event_date, $event_location, $image_path, $event_id);
        $stmt->execute();
        $stmt->close();
        echo "Event updated successfully.";
    } else {
        // Insert new event if no event ID is provided
        $insert_sql = "INSERT INTO events (event_name, event_description, event_date, event_location, event_image) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("sssss", $event_name, $event_description, $event_date, $event_location, $image_path);
        $stmt->execute();
        $stmt->close();
        echo "Event created successfully.";
    }
}

// Handle event deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_sql = "DELETE FROM events WHERE event_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    echo "Event deleted successfully.";
}

// Fetch all events
$sql_events = "SELECT * FROM events ORDER BY event_date DESC";
$result_events = $conn->query($sql_events);

// If an event is being edited, fetch its data
if (isset($_GET['event_id'])) {
    $event_id = $_GET['event_id'];
    $sql_edit_event = "SELECT * FROM events WHERE event_id = ?";
    $stmt = $conn->prepare($sql_edit_event);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result_edit = $stmt->get_result();
    if ($result_edit->num_rows > 0) {
        $event_data = $result_edit->fetch_assoc();
        $event_name = $event_data['event_name'];
        $event_description = $event_data['event_description'];
        $event_date = $event_data['event_date'];
        $event_location = $event_data['event_location'];
        $image_path = $event_data['event_image'];
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events</title>
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

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
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

    .event-list {
        margin-top: 30px;
    }

    .event-item {
        background-color: #fafafa;
        padding: 15px;
        margin-bottom: 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
    }

    .event-item img {
        max-width: 100px;
        margin-right: 10px;
    }

    .event-item h4 {
        margin: 0;
        font-size: 18px;
    }

    .event-item p {
        margin: 5px 0;
    }

    .event-item .btn {
        padding: 8px 15px;
        background-color: #f44336;
        color: white;
        font-size: 14px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .event-item .btn-edit {
        background-color: #2196F3;
    }
    </style>
</head>

<body>
    <div class="container">
        <h2>Manage Events</h2>

        <!-- Event Form -->
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="event_id" value="<?php echo isset($event_id) ? $event_id : ''; ?>">
            <div class="form-group">
                <label for="event_name">Event Name</label>
                <input type="text" id="event_name" name="event_name" value="<?php echo $event_name; ?>" required>
            </div>

            <div class="form-group">
                <label for="event_description">Event Description</label>
                <textarea id="event_description" name="event_description" rows="4"
                    required><?php echo $event_description; ?></textarea>
            </div>

            <div class="form-group">
                <label for="event_date">Event Date and Time</label>
                <input type="datetime-local" id="event_date" name="event_date"
                    value="<?php echo date('Y-m-d\TH:i', strtotime($event_date)); ?>" required>
            </div>

            <div class="form-group">
                <label for="event_location">Event Location</label>
                <input type="text" id="event_location" name="event_location" value="<?php echo $event_location; ?>"
                    required>
            </div>

            <div class="form-group">
                <label for="event_image">Event Image (Optional)</label>
                <input type="file" id="event_image" name="event_image" accept="image/*">
            </div>

            <div class="form-group">
                <button type="submit"><?php echo isset($event_id) ? 'Update Event' : 'Create Event'; ?></button>
            </div>
        </form>

        <!-- List of Events -->
        <div class="event-list">
            <?php while ($event = $result_events->fetch_assoc()): ?>
            <div class="event-item">
                <h4><?php echo htmlspecialchars($event['event_name']); ?></h4>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($event['event_description']); ?></p>
                <p><strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($event['event_date'])); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($event['event_location']); ?></p>

                <!-- Display image if available -->
                <?php if ($event['event_image']): ?>
                <p><img src="<?php echo htmlspecialchars($event['event_image']); ?>" alt="Event Image"></p>
                <?php endif; ?>

                <a href="manage_events.php?delete_id=<?php echo $event['event_id']; ?>" class="btn">Delete Event</a>
                <a href="manage_events.php?event_id=<?php echo $event['event_id']; ?>" class="btn btn-edit">Edit
                    Event</a>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>

</html>

<?php
// Close the database connection
$conn->close();
?>