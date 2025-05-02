<?php
session_start();
include 'db_connection.php';

// Common CSS for all messages (enhanced mobile-responsive)
$common_css = "
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f8d7da; /* Default for error, overridden for success */
        }
        .message-box {
            background-color: white;
            padding: clamp(1rem, 5vw, 2rem) clamp(2rem, 10vw, 3rem);
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
            width: 90%;
            max-width: 500px;
            box-sizing: border-box;
            margin: 1rem;
        }
        h1 {
            color: #721c24; /* Default for error, overridden for success */
            margin-bottom: clamp(0.75rem, 4vh, 1.5rem);
            font-size: clamp(1.25rem, 5vw, 2.5rem);
            line-height: 1.2;
        }
        p {
            font-size: clamp(0.875rem, 4vw, 1.25rem);
            color: #721c24; /* Default for error, overridden for success */
            margin: 0 0 clamp(0.5rem, 2vh, 1rem);
            line-height: 1.5;
        }
        .countdown {
            font-weight: bold;
            color: #721c24; /* Default for error, overridden for success */
        }
        @media (max-width: 768px) {
            .message-box {
                padding: clamp(0.75rem, 4vw, 1.5rem) clamp(1.5rem, 8vw, 2.5rem);
            }
            h1 {
                font-size: clamp(1rem, 4.5vw, 2rem);
            }
            p {
                font-size: clamp(0.75rem, 3.5vw, 1rem);
            }
        }
        @media (max-width: 480px) {
            .message-box {
                padding: clamp(0.5rem, 3vw, 1rem) clamp(1rem, 6vw, 2rem);
                margin: 0.5rem;
            }
            h1 {
                font-size: clamp(0.875rem, 4vw, 1.5rem);
            }
            p {
                font-size: clamp(0.625rem, 3vw, 0.875rem);
            }
        }
    </style>
";

// Common JavaScript for countdown
$common_js = "
    <script>
        let timeLeft = 3;
        const countdown = document.querySelector('.countdown');
        const timer = setInterval(() => {
            timeLeft--;
            countdown.textContent = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(timer);
                window.location.href = 'dashboard.php';
            }
        }, 1000);
    </script>
";

if (!isset($_SESSION['user_id'])) {
    die("
        <html>
        <head><title>Error</title>$common_css</head>
        <body>
            <div class='message-box'>
                <h1>Error</h1>
                <p>User is not logged in!</p>
                <p>Redirecting in <span class='countdown'>3</span> seconds...</p>
            </div>
            $common_js
        </body>
        </html>
    ");
}

$user_id = $_SESSION['user_id'];
$post_content = htmlspecialchars($_POST['post_content'] ?? '', ENT_QUOTES, 'UTF-8');
$media_url = null;
$media_type = 'none'; // Default to 'none' if no file is uploaded

// Handle file upload (only images allowed, up to 100MB)
if (!empty($_FILES['media']) && $_FILES['media']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['media'];
    $upload_dir = __DIR__ . '/uploads/';
    
    // Define allowed image extensions
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Check if the file is an image
    if (!in_array($file_ext, $allowed_extensions)) {
        die("
            <html>
            <head><title>Error</title>$common_css</head>
            <body>
                <div class='message-box'>
                    <h1>Error</h1>
                    <p>Only image files (jpg, jpeg, png, gif, bmp, webp) are allowed!</p>
                    <p>Redirecting in <span class='countdown'>3</span> seconds...</p>
                </div>
                $common_js
            </body>
            </html>
        ");
    }

    // Check upload error codes
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => "File exceeds server upload size limit (" . ini_get('upload_max_filesize') . ")",
            UPLOAD_ERR_FORM_SIZE => "File exceeds form size limit",
            UPLOAD_ERR_PARTIAL => "File was only partially uploaded",
            UPLOAD_ERR_NO_TMP_DIR => "Missing temporary folder",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
            UPLOAD_ERR_EXTENSION => "A PHP extension stopped the upload"
        ];
        $error_msg = $upload_errors[$file['error']] ?? "Unknown upload error (Code: {$file['error']})";
        die("
            <html>
            <head><title>Error</title>$common_css</head>
            <body>
                <div class='message-box'>
                    <h1>Error</h1>
                    <p>Upload failed: $error_msg</p>
                    <p>Redirecting in <span class='countdown'>3</span> seconds...</p>
                </div>
                $common_js
            </body>
            </html>
        ");
    }

    // Ensure upload directory exists and is writable
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            die("
                <html>
                <head><title>Error</title>$common_css</head>
                <body>
                    <div class='message-box'>
                        <h1>Error</h1>
                        <p>Failed to create upload directory</p>
                        <p>Redirecting in <span class='countdown'>3</span> seconds...</p>
                    </div>
                    $common_js
                </body>
                </html>
            ");
        }
    }
    if (!is_writable($upload_dir)) {
        $perms = substr(sprintf('%o', fileperms($upload_dir)), -4);
        die("
            <html>
            <head><title>Error</title>$common_css</head>
            <body>
                <div class='message-box'>
                    <h1>Error</h1>
                    <p>Upload directory is not writable. Current permissions: $perms</p>
                    <p>Redirecting in <span class='countdown'>3</span> seconds...</p>
                </div>
                $common_js
            </body>
            </html>
        ");
    }

    // Generate a unique filename
    $safe_file_name = uniqid('', true) . '.' . $file_ext;
    $file_path = $upload_dir . $safe_file_name;

    // Move the uploaded file and verify success
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        $media_url = 'uploads/' . $safe_file_name;
        $media_type = 'image';
    } else {
        $error_details = "Source: {$file['tmp_name']}, Target: $file_path, Size: " . $file['size'] . " bytes, Max: " . ini_get('upload_max_filesize');
        die("
            <html>
            <head><title>Error</title>$common_css</head>
            <body>
                <div class='message-box'>
                    <h1>Error</h1>
                    <p>Failed to move uploaded image. Details: $error_details</p>
                    <p>Redirecting in <span class='countdown'>3</span> seconds...</p>
                </div>
                $common_js
            </body>
            </html>
        ");
    }
}

// Insert post into database
$sql = "INSERT INTO posts (user_id, post_content, media_url, media_type) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("
        <html>
        <head><title>Error</title>$common_css</head>
        <body>
            <div class='message-box'>
                <h1>Error</h1>
                <p>Failed to prepare database statement</p>
                <p>Redirecting in <span class='countdown'>3</span> seconds...</p>
            </div>
            $common_js
        </body>
        </html>
    ");
}

$stmt->bind_param("isss", $user_id, $post_content, $media_url, $media_type);

if ($stmt->execute()) {
    echo "
        <html>
        <head><title>Success</title>$common_css</head>
        <body style='background-color: #d4edda;'>
            <div class='message-box'>
                <h1 style='color: #155724;'>Success</h1>
                <p style='color: #155724;'>Post created successfully!</p>
                <p style='color: #155724;'>Redirecting to dashboard in <span class='countdown'>3</span> seconds...</p>
            </div>
            $common_js
        </body>
        </html>
    ";
} else {
    $error = htmlspecialchars($stmt->error);
    echo "
        <html>
        <head><title>Error</title>$common_css</head>
        <body>
            <div class='message-box'>
                <h1>Error</h1>
                <p>Database error: $error</p>
                <p>Redirecting in <span class='countdown'>3</span> seconds...</p>
            </div>
            $common_js
        </body>
        </html>
    ";
}

$stmt->close();
$conn->close();
?>