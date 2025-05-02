<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include('db_connection.php');

    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $download_link = trim($_POST['download_link']);
    $note = trim($_POST['note']);

    // Allowed categories (To prevent SQL injection)
    $allowed_categories = ['handouts', 'past_questions', 'summaries'];
    if (!in_array($category, $allowed_categories)) {
        die("Invalid category selected.");
    }

    // Handle Google Drive link conversion
    if (strpos($download_link, 'drive.google.com') !== false) {
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)\//', $download_link, $matches)) {
            $file_id = $matches[1];
            $download_link = "https://drive.google.com/uc?export=download&id=" . $file_id;
        } else {
            die("Invalid Google Drive link.");
        }
    }

    // Insert into database using prepared statements
    $stmt = $conn->prepare("INSERT INTO $category (title, download_link, note, category) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $title, $download_link, $note, $category);

    if ($stmt->execute()) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $stmt->error;
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
    <title>Upload Resource</title>
    <style>
        :root {
            --primary: #6a1b9a; /* Purple */
            --primary-dark: #4a148c;
            --text: #2d2d2d;
            --white: #fff;
            --light-bg: #fafafa;
            --shadow: rgba(0, 0, 0, 0.05);
            --border: #e0e0e0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--light-bg);
            color: var(--text);
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 6px 20px var(--shadow);
        }

        h1 {
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 30px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group label {
            font-size: 16px;
            font-weight: 500;
            color: var(--text);
            margin-bottom: 8px;
            display: block;
        }

        .form-group input[type="text"],
        .form-group input[type="url"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            font-size: 15px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--white);
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="url"]:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 6px rgba(106, 27, 154, 0.2);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group select {
            cursor: pointer;
            appearance: none;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="%232d2d2d" viewBox="0 0 16 16"><path d="M8 12l-6-6h12z"/></svg>') no-repeat right 15px center;
            background-size: 12px;
        }

        input[type="submit"] {
            background: var(--primary);
            color: var(--white);
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        input[type="submit"]:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                margin: 20px;
                padding: 20px;
            }

            h1 {
                font-size: 24px;
            }

            .form-group label {
                font-size: 15px;
            }

            .form-group input[type="text"],
            .form-group input[type="url"],
            .form-group select,
            .form-group textarea {
                font-size: 14px;
                padding: 10px 12px;
            }

            input[type="submit"] {
                font-size: 15px;
                padding: 10px 20px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }

            h1 {
                font-size: 20px;
            }

            .form-group label {
                font-size: 14px;
            }

            .form-group input[type="text"],
            .form-group input[type="url"],
            .form-group select,
            .form-group textarea {
                font-size: 13px;
            }

            input[type="submit"] {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Upload Resource</h1>
    <form action="upload_handout.php" method="POST">
        <div class="form-group">
            <label for="title">Title:</label>
            <input type="text" name="title" required>
        </div>

        <div class="form-group">
            <label for="download_link">Download Link (Google Drive link):</label>
            <input type="url" name="download_link" required>
        </div>

        <div class="form-group">
            <label for="category">Category:</label>
            <select name="category">
                <option value="handouts">Handouts</option>
                <option value="past_questions">Past Questions</option>
                <option value="summaries">Summaries</option>
            </select>
        </div>

        <div class="form-group">
            <label for="note">Optional Note:</label>
            <textarea name="note"></textarea>
        </div>

        <input type="submit" value="Upload">
    </form>
</div>

</body>
</html>