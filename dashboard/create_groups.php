<?php
session_start(); // Start the session to access session variables

// Check if the user is logged in by checking the session
if (!isset($_SESSION['user_id'])) {
    die("You need to log in first.");
}

// Handle the form submission for creating a group
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form data
    $groupName = $_POST['group_name'];
    $groupDescription = $_POST['group_description'];
    $isPublic = isset($_POST['is_public']) ? 1 : 0; // Default to private if not set

    // Get the logged-in user ID (creator of the group)
    $userId = $_SESSION['user_id'];

    // Database connection
    $conn = new mysqli('localhost', 'unimaid9_unimaidresources', '#adyems123AD', 'unimaid9_unimaidresources');

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("INSERT INTO `groups` (name, description, creator_id, is_public) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $groupName, $groupDescription, $userId, $isPublic);

    if ($stmt->execute()) {
        echo "<p class='success-message'>Group created successfully!</p>";
    } else {
        echo "<p class='error-message'>Error: " . htmlspecialchars($stmt->error) . "</p>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create a Group</title>
    <style>
        /* Global resets and base styling */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Arial', sans-serif;
            background-color: #f4f6f9;
            color: #333;
            line-height: 1.6;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        /* Container styling */
        .container {
            width: 100%;
            max-width: 600px;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .container:hover {
            transform: translateY(-5px);
        }

        /* Header styling */
        h1 {
            font-size: 28px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 25px;
            text-align: center;
        }

        /* Form styling */
        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        label {
            font-size: 16px;
            font-weight: 500;
            color: #34495e;
            margin-bottom: 5px;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 12px 15px;
            font-size: 16px;
            border: 2px solid #e0e4e8;
            border-radius: 8px;
            background-color: #fafafa;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input[type="text"]:focus,
        textarea:focus {
            border-color: #8e44ad;
            box-shadow: 0 0 5px rgba(142, 68, 173, 0.3);
            outline: none;
        }

        textarea {
            resize: vertical;
            min-height: 120px;
            max-height: 300px;
        }

        /* Checkbox styling */
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #8e44ad;
            cursor: pointer;
        }

        .checkbox-container label {
            margin: 0;
            font-size: 16px;
        }

        /* Submit button */
        input[type="submit"] {
            background-color: #8e44ad;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        input[type="submit"]:hover {
            background-color: #9b59b6;
            transform: translateY(-2px);
        }

        input[type="submit"]:active {
            transform: translateY(0);
        }

        /* Success and error messages */
        .success-message {
            color: #27ae60;
            font-size: 16px;
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            background-color: #e8f8f5;
            border-radius: 5px;
        }

        .error-message {
            color: #c0392b;
            font-size: 16px;
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            background-color: #fceae9;
            border-radius: 5px;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 20px auto;
            }

            h1 {
                font-size: 24px;
            }

            label {
                font-size: 14px;
            }

            input[type="text"],
            textarea,
            input[type="submit"] {
                font-size: 14px;
                padding: 10px;
            }

            .checkbox-container label {
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .container {
                padding: 15px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            }

            h1 {
                font-size: 20px;
                margin-bottom: 20px;
            }

            input[type="text"],
            textarea {
                padding: 8px;
                font-size: 13px;
            }

            input[type="submit"] {
                padding: 10px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create a Group</h1>
        <form action="create_groups.php" method="POST">
            <div>
                <label for="group_name">Group Name</label>
                <input type="text" name="group_name" id="group_name" required>
            </div>

            <div>
                <label for="group_description">Group Description</label>
                <textarea name="group_description" id="group_description" required></textarea>
            </div>

            <div class="checkbox-container">
                <input type="checkbox" name="is_public" id="is_public">
                <label for="is_public">Make this group public</label>
            </div>

            <input type="submit" value="Create Group">
        </form>
    </div>
</body>
</html>