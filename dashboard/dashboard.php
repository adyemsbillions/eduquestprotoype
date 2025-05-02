<?php
ob_start(); // Start output buffering

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: logout.php");
    exit();
}

// Define absolute path to db_connection.php
$base_path = dirname(__DIR__); // Adjust based on file location
$db_connection_path = $base_path . '/db_connection.php';

// Check if db_connection.php exists
if (!file_exists($db_connection_path)) {
    error_log("db_connection.php not found at: $db_connection_path");
    die("Configuration error: Database connection file missing.");
}

include($db_connection_path);

// Verify $conn is valid
if (!isset($conn) || is_null($conn) || $conn->connect_error) {
    $error = isset($conn->connect_error) ? $conn->connect_error : 'Unknown connection error';
    error_log("Database connection failed: $error");
    die("Connection failed. Please try again later.");
}

$user_id = (int)$_SESSION['user_id'];
$errors = [];

try {
    $sql_popup = "
        SELECT p.*, COALESCE(pv.view_count, 0) AS view_count
        FROM popups p
        LEFT JOIN popup_views pv ON p.id = pv.popup_id AND pv.user_id = ?
        WHERE (p.target_user_id = ? OR p.target_user_id IS NULL)
        AND (pv.view_count IS NULL OR pv.view_count < p.display_limit)
        ORDER BY p.created_at DESC LIMIT 1";
    $stmt_popup = $conn->prepare($sql_popup);
    if (!$stmt_popup) {
        $error = "Popup prepare failed: " . $conn->error . " (Error Code: " . $conn->errno . ")";
        $errors[] = $error;
        error_log($error);
        $user_id_escaped = (int)$user_id;
        $sql_popup_fallback = "
            SELECT p.*, COALESCE(pv.view_count, 0) AS view_count
            FROM popups p
            LEFT JOIN popup_views pv ON p.id = pv.popup_id AND pv.user_id = $user_id_escaped
            WHERE (p.target_user_id = $user_id_escaped OR p.target_user_id IS NULL)
            AND (pv.view_count IS NULL OR pv.view_count < p.display_limit)
            ORDER BY p.created_at DESC LIMIT 1";
        $popup_result = $conn->query($sql_popup_fallback);
        if ($popup_result) {
            $popup = $popup_result->fetch_assoc();
        } else {
            $error = "Popup fallback failed: " . $conn->error . " (Error Code: " . $conn->errno . ")";
            $errors[] = $error;
            error_log($error);
        }
    } else {
        $stmt_popup->bind_param("ii", $user_id, $user_id);
        if ($stmt_popup->execute()) {
            $popup_result = $stmt_popup->get_result();
            $popup = $popup_result->fetch_assoc();
        } else {
            $error = "Popup execute failed: " . $stmt_popup->error . " (Error Code: " . $conn->errno . ")";
            $errors[] = $error;
            error_log($error);
        }
        $stmt_popup->close();
    }
} catch (Exception $e) {
    $error = "Popup query failed: " . $e->getMessage();
    $errors[] = $error;
    error_log($error);
}

if ($popup) {
    $popup_id = (int)$popup['id'];
    try {
        $sql_view = "INSERT INTO popup_views (popup_id, user_id, view_count) 
                     VALUES (?, ?, 1) 
                     ON DUPLICATE KEY UPDATE view_count = view_count + 1";
        $stmt_view = $conn->prepare($sql_view);
        if (!$stmt_view) {
            $error = "Popup view prepare failed: " . $conn->error . " (Error Code: " . $conn->errno . ")";
            $errors[] = $error;
            error_log($error);
            $sql_view_fallback = "INSERT INTO popup_views (popup_id, user_id, view_count) 
                                 VALUES ($popup_id, $user_id_escaped, 1) 
                                 ON DUPLICATE KEY UPDATE view_count = view_count + 1";
            if (!$conn->query($sql_view_fallback)) {
                $error = "Popup view fallback failed: " . $conn->error . " (Error Code: " . $conn->errno . ")";
                $errors[] = $error;
                error_log($error);
            }
        } else {
            $stmt_view->bind_param("ii", $popup_id, $user_id);
            if (!$stmt_view->execute()) {
                $error = "Popup view execute failed: " . $stmt_view->error . " (Error Code: " . $conn->errno . ")";
                $errors[] = $error;
                error_log($error);
            }
            $stmt_view->close();
        }
    } catch (Exception $e) {
        $error = "Popup view query failed: " . $e->getMessage();
        $errors[] = $error;
        error_log($error);
    }
}

// The HTML/CSS/JS for the popup remains the same as in the previous response
$username = $_SESSION['username'];
try {
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        $error = "User prepare failed: " . $conn->error . " (Error Code: " . $conn->errno . ")";
        $errors[] = $error;
        error_log($error);
        $sql_user_fallback = "SELECT * FROM users WHERE id = $user_id_escaped";
        $result = $conn->query($sql_user_fallback);
        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
        } else {
            $error = "User fallback failed: " . $conn->error . " (Error Code: " . $conn->errno . ")";
            $errors[] = $error;
            error_log($error);
            echo "Error fetching user details.";
            exit();
        }
    } else {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
            } else {
                $error = "No user found for ID: $user_id";
                $errors[] = $error;
                error_log($error);
                echo "Error fetching user details.";
                exit();
            }
        } else {
            $error = "User execute failed: " . $stmt->error . " (Error Code: " . $conn->errno . ")";
            $errors[] = $error;
            error_log($error);
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $error = "User query failed: " . $e->getMessage();
    $errors[] = $error;
    error_log($error);
    echo "Error fetching user details.";
    exit();
}

ob_end_flush(); // Flush output buffer
?>

<?php
$conn = new mysqli("localhost", "unimaid9_unimaidresources", "#adyems123AD", "unimaid9_unimaidresources");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql_user = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql_user);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Handle profile picture update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (isset($_FILES['new_profile_picture']) && $_FILES['new_profile_picture']['error'] == 0) {
        $profilePic = $_FILES['new_profile_picture'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $uploadDir = 'uploads/profile_pictures/';

        if (!in_array($profilePic['type'], $allowedTypes)) {
            $profileError = "Invalid file type. Please upload a JPG, PNG, or GIF image.";
        } else {
            $fileName = basename($profilePic['name']);
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($profilePic['tmp_name'], $filePath)) {
                $query = "UPDATE users SET profile_picture = ? WHERE id = ?";
                $stmt_update = $conn->prepare($query);
                $stmt_update->bind_param("si", $filePath, $user_id);
                if ($stmt_update->execute()) {
                    $_SESSION['profile_picture'] = $filePath;
                    $profileSuccess = "Profile picture updated successfully.";
                } else {
                    $profileError = "Error updating profile picture.";
                }
            } else {
                $profileError = "Error uploading the profile picture.";
            }
        }
    }
}

$sql_followers = "SELECT COUNT(*) AS followers FROM followers WHERE following_id = ?";
$stmt_followers = $conn->prepare($sql_followers);
$stmt_followers->bind_param("i", $user_id);
$stmt_followers->execute();
$followers_result = $stmt_followers->get_result();
$followers_count = $followers_result->fetch_assoc()['followers'];

$sql_follow_status = "SELECT COUNT(*) AS is_following FROM followers WHERE follower_id = ? AND following_id = ?";
$stmt_follow_status = $conn->prepare($sql_follow_status);
$stmt_follow_status->bind_param("ii", $user_id, $user['id']);
$stmt_follow_status->execute();
$follow_status_result = $stmt_follow_status->get_result();
$follow_status = $follow_status_result->fetch_assoc()['is_following'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['follow'])) {
    $action = $_POST['follow_action'];
    if ($action === 'follow') {
        $sql_follow = "INSERT INTO followers (follower_id, following_id) VALUES (?, ?)";
        $stmt_follow = $conn->prepare($sql_follow);
        $stmt_follow->bind_param("ii", $user_id, $user['id']);
        if ($stmt_follow->execute()) {
            $sql_followers = "SELECT COUNT(*) AS followers FROM followers WHERE following_id = ?";
            $stmt_followers = $conn->prepare($sql_followers);
            $stmt_followers->bind_param("i", $user_id);
            $stmt_followers->execute();
            $followers_result = $stmt_followers->get_result();
            $followers_count = $followers_result->fetch_assoc()['followers'];
            echo json_encode(['status' => 'success', 'followers_count' => $followers_count, 'button_text' => 'Unfollow']);
        } else {
            echo json_encode(['status' => 'error']);
        }
    } elseif ($action === 'unfollow') {
        $sql_unfollow = "DELETE FROM followers WHERE follower_id = ? AND following_id = ?";
        $stmt_unfollow = $conn->prepare($sql_unfollow);
        $stmt_unfollow->bind_param("ii", $user_id, $user['id']);
        if ($stmt_unfollow->execute()) {
            $sql_followers = "SELECT COUNT(*) AS followers FROM followers WHERE following_id = ?";
            $stmt_followers = $conn->prepare($sql_followers);
            $stmt_followers->bind_param("i", $user_id);
            $stmt_followers->execute();
            $followers_result = $stmt_followers->get_result();
            $followers_count = $followers_result->fetch_assoc()['followers'];
            echo json_encode(['status' => 'success', 'followers_count' => $followers_count, 'button_text' => 'Follow']);
        } else {
            echo json_encode(['status' => 'error']);
        }
    }
    exit();
}
?>
<?php
$conn = new mysqli("localhost", "unimaid9_unimaidresources", "#adyems123AD", "unimaid9_unimaidresources");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT image_url, link_url FROM ads WHERE status = 'active' LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $ad = $result->fetch_assoc();
    $adImage = $ad['image_url'];
    $adLink = $ad['link_url'];
} else {
    $adImage = "";
    $adLink = "#";
}

$conn->close();
?>
<?php
include("db_connection.php");

if (isset($_POST['like_post'])) {
    $post_id = $_POST['post_id'];
    $user_id = 1; // Hardcoded, should be from session
    
    $check_like = "SELECT * FROM post_likes WHERE post_id = ? AND user_id = ?";
    $stmt = $conn->prepare($check_like);
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $result_check = $stmt->get_result();
    
    if ($result_check->num_rows == 0) {
        $sql_like = "INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql_like);
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
    }
}

if (isset($_POST['comment_text'])) {
    $post_id = $_POST['post_id'];
    $user_id = 1; // Hardcoded, should be from session
    $comment_text = $_POST['comment_text'];

    $sql_comment = "INSERT INTO comments (post_id, user_id, comment_text) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql_comment);
    $stmt->bind_param("iis", $post_id, $user_id, $comment_text);
    $stmt->execute();
}

$sql_posts = "SELECT p.post_id, p.post_content, p.media_url, p.media_type, p.created_at, p.user_id
              FROM posts p
              ORDER BY p.created_at DESC";
$result_posts = $conn->query($sql_posts);

if (!$result_posts) {
    die("Query failed: " . $conn->error);
}
?>
<?php
include("db_connection.php");

$user_id = $_SESSION['user_id']; // Use session user ID

if (isset($_POST['like_post'])) {
    $post_id = $_POST['post_id'];
    
    $check_like = "SELECT * FROM post_likes WHERE post_id = ? AND user_id = ?";
    $stmt = $conn->prepare($check_like);
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $result_check = $stmt->get_result();
    
    if ($result_check->num_rows == 0) {
        $sql_like = "INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql_like);
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
    }

    $sql_count = "SELECT COUNT(*) AS like_count FROM post_likes WHERE post_id = ?";
    $stmt = $conn->prepare($sql_count);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result_count = $stmt->get_result();
    $like_count = $result_count->fetch_assoc()['like_count'];

    header('Content-Type: application/json');
    echo json_encode(['like_count' => $like_count]);
    exit();
}

if (isset($_POST['comment_text'])) {
    $post_id = $_POST['post_id'];
    $comment_text = $_POST['comment_text'];

    $sql_comment = "INSERT INTO comments (post_id, user_id, comment_text) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql_comment);
    $stmt->bind_param("iis", $post_id, $user_id, $comment_text);
    $stmt->execute();

    // Optionally return updated comments HTML or count
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    exit();
}

$sql_posts = "SELECT p.post_id, p.post_content, p.media_url, p.media_type, p.created_at, p.user_id
              FROM posts p
              ORDER BY p.created_at DESC";
$result_posts = $conn->query($sql_posts);

if (!$result_posts) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en" class="no-js">

<head>
    <script>
        // Register the Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then((registration) => {
                        console.log('Service Worker registered:', registration);
                    })
                    .catch((error) => {
                        console.error('Service Worker registration failed:', error);
                    });
            });
        }
    </script>

<link rel="shortcut icon" href="images/singlelogo.png" type="image/x-icon">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="keywords" content="unimaid, university resources, academic tools, campus updates, student interaction, news, unimaid resources, umstad online, University of Maiduguri, UNIMAID PORTAL, Unimaid courses, UNIMAID Portal Admission, Unimaid courses and fees, Unimaid school fees">
<meta name="description" content="Unimaid Resources brings ease to students by connecting them with essential academic tools, campus updates, and legitimate, up-to-date news, along with features like groups, chatting, posting, social interaction, and more, all designed to enhance their university experience.">


    <link rel="icon" type="image/png" href="images/singlelogo.png" />
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>UNIMAID Resources</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Major+Mono+Display" rel="stylesheet">
    <link href="assets/css/boxicons.min.css" rel="stylesheet">

    <!-- Styles -->
    <link href="assets/css/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/components.css" rel="stylesheet">
    <link href="assets/css/media.css" rel="stylesheet">
    <link href="assets/css/chat.css" rel="stylesheet">
    <link href="https://vjs.zencdn.net/7.4.1/video-js.css" rel="stylesheet">
    <script src="https://vjs.zencdn.net/ie8/1.1.2/videojs-ie8.min.js" type="text/javascript"></script>
    <script src="assets/js/load.js" type="text/javascript"></script>
    <style>
        /* Add your styles here */
        .like-loading {
            font-size: 14px;
            color: gray;
        }

        .like-button:disabled, .submit-comment-button:disabled {
            background-color: #ddd;
            cursor: not-allowed;
        }

        /* Verification badge styles */
        .verified-badge {
            font-size: 16px;
            color: #1e90ff; /* Blue color for verification */
            margin-left: 5px;
            vertical-align: middle;
        }
    </style>
    
<style>
    /* Style for the post header */
    .post-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 5px; /* Reduced from 10px */
    }

    .user-info {
        display: flex;
        align-items: center;
    }

    .user-info h3 {
        margin: 0;
        font-size: 16px; /* Slightly reduced for compactness */
    }

    .post-time {
        font-size: 12px;
        color: #888;
    }

    .avatar-img {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 50%;
        margin-right: 8px; /* Reduced from 10px */
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 50%;
        margin-right: 8px; /* Reduced from 10px */
    }

    /* General body styling */
    body {
        font-family: Arial, sans-serif;
        background-color: #f0f2f5;
        margin: 0;
        padding: 0;
    }

    /* Feed Container */
    .feed-container {
        width: 100%;
        max-width: 800px;
        margin: 20px auto;
        padding: 15px; /* Reduced from 20px */
    }

    .share-post {
        width: 100%;
        max-width: 800px;
        margin: 20px auto;
        padding: 10px; /* Reduced from 15px */
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 2px rgba(0, 0, 0, 0.1);
        margin-bottom: 15px; /* Reduced from 20px */
        transition: transform 0.2s ease-in-out;
    }

    /* Add transition for the outline property */
    textarea.form-control {
        transition: outline 0.3s ease, border-color 0.3s ease;
    }

    /* CSS for the textarea when focused */
    textarea.form-control:focus {
        outline: 1px solid purple;
        border-color: purple;
    }

    /* Add keyframe animation for continuous outline animation */
    @keyframes outlinePulse {
        0% { outline: 0.5px solid purple; }
        50% { outline: 1px solid blue; }
        100% { outline: 0.5px solid purple; }
    }

    /* Apply animation to the textarea */
    textarea.form-control {
        animation: outlinePulse 2s infinite;
        border: 1px solid transparent;
        padding: 6px; /* Reduced from 8px */
    }

    /* Post Card Styling */
    .post-card {
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        margin-bottom: 15px; /* Reduced from 20px */
        padding: 10px; /* Reduced from 15px */
        transition: transform 0.2s ease-in-out;
       
    }

    .post-card:last-child {
        margin-bottom: 0;
    }

    .post-card:hover {
        transform: scale(1.02);
    }

    /* Post Content */
    .post-content {
        font-size: 16px;
        color: #333;
        margin-bottom: 8px; /* Reduced from 15px */
    }

    /* Media Section - Images and Videos */
    .post-media {
        margin-top: 8px; /* Reduced from 15px */
        margin-bottom: 8px; /* Added to compress below */
    }

    .post-media img, .post-media video {
        max-width: 100%;
        border-radius: 10px;
   
    }

    /* Like Button */
    .post-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 8px; /* Reduced from 15px */
    }

    .like-button {
        background-color: purple;
        color: white;
        border: none;
        padding: 4px 8px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        transition: background-color 0.3s ease;
    }

    .like-button:hover {
        background-color: purple; /* Consider a darker shade for hover */
    }

    /* Like Count */
    .like-count {
        font-size: 14px;
        color: #555;
        font-weight: bolder;
    }

    /* Comments Section */
    .comments-section {
        margin-top: 2px; /* Reduced from 20px */
    }

    .comments-section h4 {
        font-size: 16px; /* Reduced from 18px */
        margin-bottom: 5px; /* Reduced from 10px */
    }

    .comment {
        margin-bottom: 8px; /* Reduced from 10px */
    }

    .comment strong {
        font-size: 14px;
        color: #3b5998;
    }

    .comment-time {
        font-size: 12px;
        color: #777;
    }

    textarea {
        width: 100%;
        padding: 6px; /* Reduced from 8px */
        border-radius: 5px;
        border: 1px solid #ccc;
        margin-top: 0px; /* Reduced from 10px */
        resize: vertical;
        height:50px;
    }

    .comment-button {
        background-color: #3b5998;
        color: white;
        border: none;
        padding: 6px 10px; /* Reduced from 8px 12px */
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        margin-top: 0px; /* Reduced from 10px */
        transition: background-color 0.3s ease;
    }

    .comment-button:hover {
        background-color: #2d4373;
    }

    /* Responsiveness adjustments */
    @media (max-width: 1200px) {
        .feed-container { padding: 5px; } /* Reduced from 15px */
        .post-card { padding: 15px; }
        .post-header h3 { font-size: 16px; }
        .post-time { font-size: 12px; }
        .post-content { font-size: 15px; }
    }

    @media (max-width: 992px) {
        .feed-container { width: 90%; }
        .post-card { padding: 5px; } /* Slightly reduced */
        .post-header h3 { font-size: 15px; }
        .post-time { font-size: 11px; }
        .post-content { font-size: 14px; }
        .like-button { font-size: 12px; padding: 3px 8px; }
        .like-count { font-size: 12px; }
        .comment-button { font-size: 12px; padding: 5px 8px; }
    }

    @media (max-width: 768px) {
        .feed-container { width: 100%; padding: 8px; } /* Reduced further */
        .post-card { padding: 10px; }
        .post-header h3 { font-size: 14px; }
        .post-time { font-size: 10px; }
        .post-content { font-size: 13px; }
        .like-button { font-size: 11px; padding: 3px 6px; }
        .like-count { font-size: 11px; }
        .comment-button { font-size: 11px; padding: 4px 6px; }
        textarea { padding: 5px; }
    }

    @media (max-width: 576px) {
        .feed-container { padding: 5px; }
        .post-card { padding: 8px; }
        .post-header h3 { font-size: 14px; }
        .post-time { font-size: 10px; }
        .post-content { font-size: 13px; }
        .like-button { font-size: 10px; padding: 3px 6px; }
        .like-count { font-size: 10px; }
        .comment-button { font-size: 10px; padding: 4px 6px; }
        textarea { padding: 4px; }
    }

    @media (max-width: 375px) {
        .post-card { padding: 6px; }
        .like-button, .comment-button { font-size: 9px; padding: 3px 5px; }
        .post-header h3 { font-size: 12px; }
        .post-time { font-size: 9px; }
        .post-content { font-size: 12px; }
    }
</style>



</head>

<body class="newsfeed">
    
    
    <!-- Popup Modal -->
<?php if ($popup): ?>
<div id="popupModal" class="popup-modal">
    <div class="popup-content">
        <span class="popup-close" onclick="closePopup()">&times;</span>
        <div class="popup-body">
            <?php if ($popup['image_path']): ?>
                <img src="/werey001/<?php echo htmlspecialchars($popup['image_path']); ?>" alt="Popup Image" class="popup-image">
            <?php endif; ?>
            <p><?php echo htmlspecialchars($popup['message']); ?></p>
            <?php if ($popup['button_text'] && $popup['button_link']): ?>
<a href="<?php echo htmlspecialchars($popup['button_link']); ?>" class="popup-button" onclick="handlePopupLink(event, this)"><?php echo htmlspecialchars($popup['button_text']); ?></a>

            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
<script>
function closePopup() {
    document.getElementById('popupModal').style.display = 'none';
}

// Smart link handler for popup button
function handlePopupLink(event, element) {
    const link = element.getAttribute('href');

    // Check if the link is external
    const isExternal = /^(http|https):\/\//.test(link);

    if (isExternal) {
        // Open in new tab
        window.open(link, '_blank');
    } else {
        // Internal link, open in same tab
        window.location.href = link;
    }

    // Prevent default click
    event.preventDefault();
}
</script>

<style>
.popup-modal {
    display: flex;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%; /* Full screen overlay */
    background: rgba(0, 0, 0, 0.6);
    justify-content: center;
    align-items: center; /* Centers the popup vertically */
    z-index: 1000;
    animation: fadeIn 0.3s ease-in-out;
}

.popup-content {
    background: #fff;
    padding: 20px;
    border-radius: 15px;
    max-width: 500px;
    width: 100%;
    height: 400px; /* Fixed height for the popup */
    overflow-y: auto; /* Scroll if content exceeds height */
    position: relative;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    animation: slideIn 0.3s ease-in-out;
}

.popup-close {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 28px;
    color: #333;
    cursor: pointer;
    transition: color 0.3s;
}

.popup-close:hover {
    color: #ff0000;
}

.popup-body {
    text-align: center;
}

.popup-image {
    max-width: 100%;
    height: auto;
    border-radius: 10px;
    margin-bottom: 15px;
}

.popup-body p {
    font-size: 1.1rem;
    color: #333;
    margin-bottom: 20px;
}

.popup-button {
    display: inline-block;
    padding: 10px 20px;
    background: #6a1b9a;
    color: #fff;
    text-decoration: none;
    border-radius: 25px;
    font-weight: 600;
    transition: background 0.3s, transform 0.2s;
}

.popup-button:hover {
    background: #4a148c;
    transform: scale(1.05);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { transform: translateY(-50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

@media (max-width: 480px) {
    .popup-content {
        width: 95%;
        padding: 15px;
        height: 300px; /* Smaller fixed height for mobile */
    }
    .popup-body p {
        font-size: 1rem;
    }
    .popup-button {
        padding: 8px 15px;
        font-size: 0.9rem;
    }
}
</style>

<script>
    function closePopup() {
        document.getElementById('popupModal').style.display = 'none';
    }
</script>
    
    
    
    <div class="container-fluid" id="wrapper">
        <div class="row newsfeed-size">
            <div class="col-md-12 newsfeed-right-side">
                <nav id="navbar-main" class="navbar navbar-expand-lg shadow-sm sticky-top">
                    <div class="w-100 justify-content-md-center">
                        <ul class="nav navbar-nav enable-mobile px-2">
                            <li class="nav-item">
                                <button type="button" class="btn nav-link p-0">
                                    <a href="reels.php">
                                        <img src="assets/images/icons/theme/reels.png" class="f-nav-icon" alt="Quick make post">
                                    </a>
                                    
                                </button>
                            </li>
                            <li class="nav-item w-100 py-2">
                            <form action="search.php" method="GET" class="d-inline form-inline w-100 px-4">
    <div class="input-group">
        <input type="text" class="form-control search-input" name="query" placeholder="Search for people, post, in unimaid" aria-label="Search" aria-describedby="search-addon">
        <div class="input-group-append">
            <button class="btn search-button" type="submit"><i class='bx bx-search'></i></button>
        </div>
    </div>
</form>

                            </li>

                            
                        <?php
// session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

include('db_connection.php'); // Adjust path as needed

$sql = "SELECT COUNT(*) as pending_count 
        FROM messages 
        WHERE receiver_id = ? AND status = 'pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($pending_count);
$stmt->fetch();
$stmt->close();

$conn->close();
?>

<!-- Navigation Item with Smaller Pending Message Count -->
<li class="nav-item">
    <a href="chat.php" class="nav-link nav-icon nav-links message-drop drop-w-tooltip" data-placement="bottom" data-title="Messages">
        <img src="assets/images/icons/navbar/chat.png" class="message-dropdown f-nav-icon" alt="navbar icon">
        <?php if ($pending_count > 0): ?>
            <span class="badge badge-pill badge-danger"><?php echo $pending_count; ?></span>
        <?php endif; ?>
    </a>
</li>

<!-- CSS with Reduced Badge Size -->
<style>
    .badge {
        display: inline-block;
        padding: 0.2em 0.4em; /* Reduced padding for smaller size */
        font-size: 0.6em; /* Smaller font size (relative to parent) */
        font-weight: 100;
        line-height: 0;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.5rem; /* Slightly larger radius for pill shape relative to size */
        position: absolute;
        top: 0px; /* Adjusted positioning */
        right: -3px; /* Adjusted positioning */
    }

    .badge-pill {
        border-radius: 5rem; /* Maintains pill shape */
    }

    .badge-danger {
        color: #fff;
        background-color: #dc3545; /* Red color, can change to match theme */
    }

    .nav-item {
        position: relative; /* Anchor for absolute positioning of badge */
    }
</style>
                        </ul>
                        <ul class="navbar-nav mr-5 flex-row" id="main_menu">
                            <a class="navbar-brand nav-item mr-lg-5" href="dashboard.php"><img src="images/singlelogo.png" width="40" height="40" class="mr-3" alt="Logo"></a>
                            <!-- Collect the nav links, forms, and other content for toggling -->
                            <form action="search.php" method="GET" class="w-30 mx-2 my-auto d-inline form-inline mr-5 search-form">
    <div class="input-group">
        <input type="text" class="form-control search-input" name="query" placeholder="Search for people, post, in unimaid" aria-label="Search" aria-describedby="search-addon">
        <div class="input-group-append">
            <button class="btn search-button" type="submit"><i class='bx bx-search'></i></button>
        </div>
    </div>
</form>



                            <li class="nav-item s-nav dropdown d-mobile">
                                <a href="#" class="nav-link nav-icon nav-links drop-w-tooltip" data-toggle="dropdown" data-placement="bottom" data-title="Create" role="button" aria-haspopup="true" aria-expanded="false">
                                    <img src="assets/images/icons/navbar/create.png" alt="navbar icon">
                                </a>
                                <div class="dropdown-menu dropdown-menu-right nav-dropdown-menu">
                                    <a href="create_groups.php" class="dropdown-item" aria-describedby="createGroup">
                                        <div class="row">
                                            <div class="col-md-2">
                                                <i class='bx bx-group post-option-icon'></i>
                                            </div>
                                            <div class="col-md-10">
                                                <span class="fs-9">Group</span>
                                                <small id="createGroup" class="form-text text-muted">Find people with shared interests</small>
                                            </div>
                                        </div>
                                    </a>
                                    <a href="feed.php" class="dropdown-item" aria-describedby="createEvent">
                                        <div class="row">
                                            <div class="col-md-2">
                                                <i class='bx bx-calendar post-option-icon'></i>
                                            </div>
                                            <div class="col-md-10">
                                                <span class="fs-9">Event</span>
                                                <small id="createEvent" class="form-text text-muted">bring people together with a public or private event</small>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </li>
                            <li class="nav-item s-nav dropdown message-drop-li">
                                <a href="#" class="nav-link nav-links message-drop drop-w-tooltip" data-toggle="dropdown" data-placement="bottom" data-title="Messages" role="button" aria-haspopup="true" aria-expanded="false">
                                    <img src="assets/images/icons/navbar/message.png" class="message-dropdown" alt="navbar icon"> <span class="badge badge-pill badge-primary">0</span>
                                </a>
                                <ul class="dropdown-menu notify-drop dropdown-menu-right nav-drop shadow-sm">
                                    <div class="notify-drop-title">
                                        <div class="row">
                                          
                                        </div>
                                    </div>
                                    <!-- end notify title -->
                                    <!-- notify content -->

                                    <div class="notify-drop-footer text-center">
                                        <a href="chat.php">Meet students</a>
                                    </div>
                                </ul>
                            </li>
                         <?php
// session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

include('db_connection.php'); // Adjust path as needed (e.g., '../db_connection.php' if in a subdirectory)

// Fetch count of pending messages (unread notifications)
$sql = "SELECT COUNT(*) as pending_count 
        FROM messages 
        WHERE receiver_id = ? AND status = 'pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($pending_count);
$stmt->fetch();
$stmt->close();

$conn->close();
?>

<!-- Navigation Item with Pending Message Count -->
<li class="nav-item s-nav">
    <a href="notifications.php" class="nav-link nav-links">
        <div class="menu-user-image" style="position: relative;">
            <img src="assets/images/icons/navbar/notification.png" alt="navbar icon">
            <?php if ($pending_count > 0): ?>
                <span class="badge badge-pill badge-danger"><?php echo $pending_count; ?></span>
            <?php endif; ?>
        </div>
    </a>
</li>

<!-- CSS for the Badge -->
<style>
    .badge {
        display: inline-block;
        padding: 0.2em 0.4em; /* Small padding */
        font-size: 0.6em; /* Small font size */
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.5rem;
        position: absolute;
        top: -3px; /* Adjusted for smaller size */
        right: -3px; /* Adjusted for smaller size */
    }

    .badge-pill {
        border-radius: 10rem; /* Pill shape */
    }

    .badge-danger {
        color: #fff;
        background-color: #dc3545; /* Red color */
    }

    .menu-user-image {
        position: relative; /* Anchor for badge positioning */
    }
</style>
                          
                            <li class="nav-item s-nav dropdown d-mobile">
                                <a href="#" class="nav-link nav-links nav-icon drop-w-tooltip" data-toggle="dropdown" data-placement="bottom" data-title="Pages" role="button" aria-haspopup="true" aria-expanded="false">
                                    <img src="assets/images/icons/navbar/flag.png" alt="navbar icon">
                                </a>
                                <div class="dropdown-menu dropdown-menu-right nav-drop">
                                    <a class="dropdown-item" href="newsfeed-2.html">Newsfeed 2</a>
                                    <a class="dropdown-item" href="sign-in.html">Sign in</a>
                                    <a class="dropdown-item" href="sign-up.html">Sign up</a>
                                </div>
                            </li>
                            <li class="nav-item s-nav d-mobile">
                                <a href="mkt.php" class="nav-link nav-links nav-icon drop-w-tooltip" data-placement="bottom" data-title="Marketplace">
                                    <img src="assets/images/icons/navbar/market.png" alt="navbar icon">
                                </a>
                            </li>
           <?php
ob_start();
include("db_connection.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details for the logged-in user
$sql_user = "SELECT profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($sql_user);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_user = $stmt->get_result();

if ($result_user->num_rows === 1) {
    $user = $result_user->fetch_assoc();
} else {
    // Fallback if user not found
    $user = ['profile_picture' => 'default_profile.jpg']; // Default image
}
?>

<li class="nav-item s-nav">
    <a href="profile.php" class="nav-link nav-links">
        <div class="menu-user-image">
            <img class="avatar-img" 
                 src="/dashboard/<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                 alt="avatar" 
                 style="width: 50px; height: 30px; object-fit: cover; border-radius: 50%; margin-right: 10px;">
        </div>
    </a>
</li>

<?php ob_end_flush(); ?>
                            
                            <li class="nav-item s-nav">
                                <a href="mkt.php" class="nav-link nav-links">
                                    <div class="menu-user-image">
                                    <img src="assets/images/icons/navbar/cart-24.png" alt="navbar icon">
                                </a>
                            </li>
                            <button type="button" class="btn nav-link" id="menu-toggle"><img src="assets/images/icons/theme/navs.png" alt="Navbar navs"></button>
                        </ul>

                    </div>

                </nav>
                <div class="row newsfeed-right-side-content mt-3">
                    <div class="col-md-3 newsfeed-left-side sticky-top shadow-sm" id="sidebar-wrapper">
                        <div class="card newsfeed-user-card h-100">
                            <ul class="list-group list-group-flush newsfeed-left-sidebar">
                                <li class="list-group-item">
                                    <h6>Home</h6>
                                </li>
                                
                                <li class="list-group-item d-flex justify-content-between align-items-center sd-active">
                                    <a href="feed.php" class="sidebar-item"><img src="assets/images/icons/left-sidebar/newsfeed.png" alt="newsfeed"> News Feed</a>
                                    <a href="feed.php" class="newsfeedListicon"><i class='bx bx-dots-horizontal-rounded'></i></a>
                                </li>
                                 <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="users.php" class="sidebar-item"><img src="assets/images/icons/left-sidebar/find-friends.png" alt="find-friends"> Find Friends</a>
                                    <span class="badge badge-primary badge-pill"></span>
                                </li>
                                                                <li class="list-group-item d-flex justify-content-between align-items-center">
    <a href="https://chatbot.unimaidresources.com.ng" 
       class="sidebar-item" 
       target="_blank" 
       rel="noopener noreferrer">
        <img src="assets/images/icons/left-sidebar/quiz.png" alt="Quiz Icon"> 
        Chat AI
    </a>
    <span class="badge badge-primary badge-pill"></span>
</li>
                              <li class="list-group-item d-flex justify-content-between align-items-center">
    <a href="https://quiz.unimaidresources.com.ng" class="sidebar-item">
        <img src="assets/images/icons/left-sidebar/quiz.png" alt="Quiz Icon"> 
        Quiz
    </a>
    <span class="badge badge-primary badge-pill"></span>
</li>

                                 <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="referrals.php" class="sidebar-item"><img src="assets/images/icons/left-sidebar/ref.png" alt="find-friends"> Join Promoters</a>
                                    <span class="badge badge-primary badge-pill"></span>
                                </li>
                                  <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="vote.php" class="sidebar-item"><img src="assets/images/icons/left-sidebar/wcw.png" alt="message"> Women Crush Wednesday</a>
                                   
                                </li>
                              
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="chat.php" class="sidebar-item"><img src="assets/images/icons/left-sidebar/message.png" alt="message"> Messages</a>
                                   
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="maingroups.php" class="sidebar-item"><img src="assets/images/icons/left-sidebar/group.png" alt="group"> Groups</a>
                                   
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="events.php" class="sidebar-item"><img src="assets/images/icons/left-sidebar/event.png" alt="event"> Events</a>
                                 
                                </li>
                               
                               
                                <?php
// Database connection
$servername = "localhost";
$username = "unimaid9_unimaidresources";
$password = "#adyems123AD";
$dbname = "unimaid9_unimaidresources";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the user's gender from the database
$userId = $_SESSION['user_id']; // Assuming you store the user's ID in a session
$query = "SELECT gender FROM users WHERE id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Error in preparing statement: " . $conn->error);
}

$stmt->bind_param("i", $userId);

if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}

$stmt->bind_result($gender);

if (!$stmt->fetch()) {
    die("Error fetching result: " . $stmt->error);
}

$stmt->close();

// Normalize the gender value
$gender = strtolower(trim($gender));

// Display links based on gender
if ($gender === 'female') {
    echo '<li class="list-group-item d-flex justify-content-between align-items-center">
                <i class="fa fa-check-circle"></i> Pink Verification
            </a>
            <span class="badge badge-primary badge-pill"></span>
          </li>';
} elseif ($gender === 'male') {
    echo '<li class="list-group-item d-flex justify-content-between align-items-center">
            <a href="black_verification.php" class="sidebar-item">
                <i class="fa fa-check-circle"></i> Black Verification
            </a>
            <span class="badge badge-primary badge-pill"></i></span>
          </li>';
} 
?>

<li class="list-group-item d-flex justify-content-between align-items-center">
    <a href="request_verification.php" class="sidebar-item">
        <i class="fa fa-check-circle"></i> Blue Verification
    </a>
    <span class="badge badge-primary badge-pill"></span>
</li>







                                <li class="list-group-item d-flex justify-content-between align-items-center newsLink">
                                    <a href="https://github.com/ArtMin96/argon-social" target="_blank" class="sidebar-item"><img src="assets/images/icons/left-sidebar/news.png" alt="find-friends"> News</a>
                                    <span class="badge badge-primary badge-pill">
                                        
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    
                    
                    
                    <div class="col-md-6 second-section" id="page-content-wrapper">
                        <div class="mb-3">
                            <div class="btn-group d-flex">
                                <a href="my_groups.php" class="btn btn-quick-links mr-3 ql-active">
                                    <img src="assets/images/icons/theme/groups-24.png" class="mr-2" alt="quick links icon" style="color: white;">
                                    <span class="fs-8"> My Groups</span>
                                </a>
                                <a href="select_club.php" class="btn btn-quick-links mr-3">
                                    <img src="assets/images/icons/theme/football-24.png" class="mr-2" alt="quick links icon">
                                    <span class="fs-8">Football</span>
                                </a>
                                <a href="display_materials.php" class="btn btn-quick-links">
                                    <img src="assets/images/icons/theme/books-24.png" class="mr-2" alt="quick links icon">
                                    <span class="fs-8">Materials</span>
                                </a>
                            </div>
                        </div>
                        

<?php
include('db_connection.php');
// Fetch media files from the database
$sql = "SELECT file_path, file_type FROM media_slideshow ORDER BY created_at DESC LIMIT 3";
$result = $conn->query($sql);

$media = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $media[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responsive 728x90 Slideshow</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f4f4f4;
        }

        /* Container for Slideshow */
        .container {
            width: 100%;
            max-width: 728px; /* Max width for larger screens */
            height: 0;
            padding-bottom: 12.32%; /* Maintain aspect ratio of 728x90 (90/728 * 100) */
            position: relative;
            background-color: #fff;
            border: 1px solid #ccc;
            overflow: hidden;
        }

        .slideshow {
            width: 100%;
            height: 100%;
            display: flex;
            overflow: hidden;
            position: absolute;
            top: 0;
            left: 0;
        }

        .slide {
            width: 100%;
            height: 100%;
            flex-shrink: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .slide img, .slide video {
            width: 100%;
            height: 100%;
            object-fit: cover; /* Ensures image/video fully covers the container */
        }

        /* Responsive adjustments for smaller screens */
        @media (max-width: 768px) {
            .container {
                max-width: 100%; /* Full width on smaller screens */
                padding-bottom: 25%; /* Adjust aspect ratio for mobile (4:1 ratio) */
            }
        }

        @media (max-width: 480px) {
            .container {
                padding-bottom: 33.33%; /* Further adjust for very small screens (3:1 ratio) */
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Slideshow -->
        <div class="slideshow" id="slideshow">
            <?php
            // Dynamically generate slides based on fetched media
            foreach ($media as $index => $item) {
                $file_path = $item['file_path'];
                $file_type = $item['file_type'];
                $display_style = $index === 0 ? 'flex' : 'none'; // Show first slide initially

                if ($file_type === 'video') {
                    echo "<div class='slide' style='display: $display_style;'>
                            <video src='$file_path' autoplay muted loop></video>
                          </div>";
                } else {
                    echo "<div class='slide' style='display: $display_style;'>
                            <img src='$file_path' alt='Slide $index'>
                          </div>";
                }
            }
            ?>
        </div>
    </div>

    <script>
        // Slideshow logic
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slide');

        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.style.display = i === index ? 'flex' : 'none';
            });
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % slides.length;
            showSlide(currentSlide);
        }

        // Initialize
        showSlide(currentSlide); // Show the first slide
        setInterval(nextSlide, 3000); // Change slide every 3 seconds
    </script>
</body>
</html>
<br>







               <?php
// reels.php
// session_start();
include 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['reel'])) {
        $_SESSION['redirect_reel'] = $_GET['reel'];
    }
    header("Location: login.php");
    exit;
}

// Handle video upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['reel_video'])) {
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $target_file = $target_dir . uniqid() . '_' . basename($_FILES["reel_video"]["name"]);
    $videoFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    $allowed_types = array('mp4', 'mov', 'avi');
    if (in_array($videoFileType, $allowed_types)) {
        if (move_uploaded_file($_FILES["reel_video"]["tmp_name"], $target_file)) {
            $title = htmlspecialchars($_POST['title']);
            $description = htmlspecialchars($_POST['description']);
            $user_id = $_SESSION['user_id'];
            
            $sql = "INSERT INTO reels (user_id, video_url, title, description) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                die("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("isss", $user_id, $target_file, $title, $description);
            if (!$stmt->execute()) {
                echo "Execute failed: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "File upload failed";
        }
    } else {
        echo "Invalid file type. Allowed types: mp4, mov, avi";
    }
}

// Fetch reels with comments and like status
$user_id = $_SESSION['user_id'];

// NOTE: We also pull in profile_picture, verified from 'users'
$sql = "SELECT r.*, u.username, u.profile_picture, u.verified
        FROM reels r
        JOIN users u ON r.user_id = u.id
        ORDER BY created_at DESC";

$result = $conn->query($sql);
if ($result === false) {
    die("Query failed: " . $conn->error);
}

$reels = [];
while ($row = $result->fetch_assoc()) {
    $comment_sql = "SELECT c.*, u.username as comment_user 
                    FROM rcomments c 
                    JOIN users u ON c.user_id = u.id 
                    WHERE c.reel_id = ? 
                    ORDER BY c.created_at";
    $comment_stmt = $conn->prepare($comment_sql);
    $comment_stmt->bind_param("i", $row['id']);
    $comment_stmt->execute();
    $row['comments_array'] = $comment_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $like_stmt = $conn->prepare("SELECT COUNT(*) FROM reel_likes WHERE reel_id = ? AND user_id = ?");
    $like_stmt->bind_param("ii", $row['id'], $user_id);
    $like_stmt->execute();
    $row['user_liked'] = $like_stmt->get_result()->fetch_row()[0] > 0;
    
    $reels[] = $row;
    $comment_stmt->close();
    $like_stmt->close();
}
$result->free();

$scroll_to_reel = isset($_SESSION['redirect_reel']) ? $_SESSION['redirect_reel'] : null;
unset($_SESSION['redirect_reel']);


foreach ($reels as &$reel) {
    $likes = (int)$reel['likes'];
    $comments = (int)$reel['comments'];
    $shares = (int)$reel['shares'];
    $views = max((int)$reel['views'], 1); // Avoid division by zero

    // Engagement Ratio
    $engagementRatio = ($likes + $comments + $shares) / $views;

    // Recency Boost
    $createdAt = strtotime($reel['created_at']);
    $hoursSince = (time() - $createdAt) / 3600;
    $recentBoost = 0;

    if ($hoursSince <= 1) {
        $recentBoost = 50;
    } elseif ($hoursSince <= 24) {
        $recentBoost = 20;
    }

    // Score calculation
    $reel['score'] = ($likes * 3) + ($comments * 4) + ($shares * 5) + ($engagementRatio * 10) + $recentBoost + rand(1, 5);
}

// Now sort by score descending
usort($reels, function($a, $b) {
    return $b['score'] <=> $a['score'];
});

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Reels</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary: #6a1b9a;
            --primary-dark: #4a148c;
            --secondary: #ede9fe;
            --text: #1f2937;
            --light-bg: #f9fafb;
            --border: #d1d5db;
            --shadow: rgba(0, 0, 0, 0.1);
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
            padding: 20px;
            min-height: 100vh;
        }

        .upload-form {
            max-width: 600px;
            margin: 0 auto 40px;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px var(--shadow);
        }

        .upload-form h2 {
            color: var(--primary);
            margin-bottom: 20px;
            font-size: 1.5em;
        }

        .upload-form input[type="text"],
        .upload-form textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .upload-form input[type="text"]:focus,
        .upload-form textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 5px rgba(106, 27, 154, 0.3);
        }

        .upload-form input[type="file"] {
            margin: 15px 0;
            font-size: 14px;
        }

        .upload-form input[type="submit"] {
            background: var(--primary);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .upload-form input[type="submit"]:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(74, 20, 140, 0.2);
        }

        .reels-container {
            max-width: 700px;
            margin: 0 auto;
        }

        .reel {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px var(--shadow);
            transition: transform 0.3s ease;
        }

        .reel:hover {
            transform: translateY(-5px);
        }

        .reel video {
            width: 100%;
            max-height: 600px;
            border-radius: 8px;
            background: #000;
            object-fit: contain;
        }

        .reel-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        /* ADDED: Profile picture, link, verification badge */
        /* Replaced your old reel-header content with new code below. */
        
        .reel-header img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
        }

        .reel-header strong {
            color: var(--primary);
            font-size: 1.1em;
        }

        .reel-header span {
            color: #6b7280;
            font-size: 0.9em;
        }

        .reel-title {
            font-size: 1.3em;
            font-weight: 600;
            margin: 10px 0;
            color: var(--text);
        }

        .reel-description {
            color: #4b5563;
            margin-bottom: 20px;
            font-size: 0.95em;
        }

        .interactions {
            display: flex;
            gap: 25px;
            margin: 15px 0;
            color: #6b7280;
            font-size: 0.9em;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin: 15px 0;
        }

        .action-btn {
            background: var(--secondary);
            color: var(--primary);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            min-width: 80px;
        }

        .action-btn:hover {
            background: #ddd6fe;
            transform: translateY(-2px);
        }

        .action-btn.liked {
            background: var(--primary);
            color: white;
            cursor: not-allowed;
        }

        .comments-section {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid var(--border);
        }

        .comment {
            margin: 10px 0;
            padding: 10px;
            background: #f9fafb;
            border-radius: 8px;
            font-size: 0.9em;
        }

        .comment.hidden {
            display: none;
        }

        .comment strong {
            color: var(--primary);
        }

        .comment-form {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .comment-form textarea {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid var(--border);
            resize: vertical;
            min-height: 60px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .comment-form textarea:focus {
            border-color: var(--primary);
            outline: none;
        }

        .comment-form button {
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .comment-form button:hover {
            background: var(--primary-dark);
        }

        .loader {
            display: none;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .action-btn.loading .loader {
            display: block;
        }

        .action-btn.loading span {
            visibility: hidden;
        }

        .toggle-comments {
            color: var(--primary);
            cursor: pointer;
            margin-top: 10px;
            font-size: 0.9em;
            display: none;
            transition: color 0.3s ease;
        }

        .toggle-comments.visible {
            display: inline-block;
        }

        .toggle-comments:hover {
            color: var(--primary-dark);
        }

        .back-button {
            position: fixed;
            bottom: 30px;
            left: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            text-decoration: none;
            box-shadow: 0 4px 12px var(--shadow);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .back-button:hover {
            background: var(--primary-dark);
            transform: scale(1.1);
        }

        @media (max-width: 768px) {
            .upload-form {
                padding: 15px;
            }

            .reel {
                padding: 15px;
            }

            .reel video {
                max-height: 400px;
            }

            .action-buttons {
                flex-wrap: wrap;
            }

            .back-button {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .upload-form input[type="submit"],
            .action-btn,
            .comment-form button {
                padding: 8px 15px;
                font-size: 14px;
            }

            .reel-title {
                font-size: 1.1em;
            }

            .interactions {
                flex-wrap: wrap;
                gap: 15px;
            }
        }

        @keyframes spin2 {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
     /* Badge container for position */
.badge-container {
    position: relative;
    display: inline-block;
}

/* Outer circle of the badge */
.badge-container .fa-circle {
    font-size: 12px;
}

/* Check mark inside the badge */
.badge-container .fa-check {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 8px;
}

/* Color variations based on badge level */
.badge-blue .fa-circle {
    color: #3498db; /* Blue */
}

.badge-gold .fa-circle {
    color: #e0b20b; /* Gold */
}

.badge-black .fa-circle {
    color: #000000; /* Black */
}

.badge-pink .fa-circle {
    color: #e91e63; /* Pink */
}

    </style>
    <script>
        // Service Worker script from your original code
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('Service Worker registered:', reg))
                    .catch(err => console.error('Service Worker registration failed:', err));
            });
        }
    </script>
</head>
<body>
    <div class="upload-form">
        <h2>Upload a Reel</h2>
        <form action="" method="post" enctype="multipart/form-data">
            <input type="text" name="title" placeholder="Title" required aria-label="Reel title">
            <textarea name="description" placeholder="Description" rows="3" aria-label="Reel description"></textarea>
            <input type="file" name="reel_video" accept="video/*" required aria-label="Upload video">
            <input type="submit" value="Upload Reel">
        </form>
    </div>

    <div class="reels-container">
        <?php foreach ($reels as $reel): ?>
            <div class="reel" data-reel-id="<?php echo $reel['id']; ?>">
                <!-- 
                    REEL HEADER: Now includes
                      - Profile picture
                      - Link to user profile
                      - Verification badge 
                -->
          <div class="reel-header">
    <a href="view_profile.php?user_id=<?php echo htmlspecialchars($reel['user_id']); ?>">
        <img src="<?php echo htmlspecialchars($reel['profile_picture']); ?>" alt="Profile" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;">
    </a>
    <div>
        <a href="view_profile.php?user_id=<?php echo htmlspecialchars($reel['user_id']); ?>" style="text-decoration: none; color: inherit;">
            <strong>
                <?php echo htmlspecialchars($reel['username']); ?> <!-- This is the username -->
                <?php if (isset($reel['verified']) && $reel['verified'] == 1): ?>
                    <span style="position: relative; display: inline-block; margin-left: 8px; width: 18px; height: 18px;">
                        <i class="fa fa-circle" style="color: #3498db; font-size: 18px;"></i>
                        <i class="fa fa-check" style="color: white; font-size: 10px; position: absolute; top: 53%; left: 50%; transform: translate(-50%, -50%);"></i>
                    </span>
                <?php elseif (isset($reel['verified']) && $reel['verified'] == 2): ?>
                    <span style="position: relative; display: inline-block; margin-left: 8px; width: 18px; height: 18px;">
                        <i class="fa fa-circle" style="color: #e0b20b; font-size: 18px;"></i>
                        <i class="fa fa-check" style="color: white; font-size: 10px; position: absolute; top: 53%; left: 50%; transform: translate(-50%, -50%);"></i>
                    </span>
                <?php elseif (isset($reel['verified']) && $reel['verified'] == 3): ?>
                    <span style="position: relative; display: inline-block; margin-left: 8px; width: 18px; height: 18px;">
                        <i class="fa fa-circle" style="color: #000000; font-size: 18px;"></i>
                        <i class="fa fa-check" style="color: white; font-size: 10px; position: absolute; top: 53%; left: 50%; transform: translate(-50%, -50%);"></i>
                    </span>
                <?php elseif (isset($reel['verified']) && $reel['verified'] == 4): ?>
                    <span style="position: relative; display: inline-block; margin-left: 8px; width: 18px; height: 18px;">
                        <i class="fa fa-circle" style="color: #e91e63; font-size: 18px;"></i>
                        <i class="fa fa-check" style="color: white; font-size: 10px; position: absolute; top: 53%; left: 50%; transform: translate(-50%, -50%);"></i>
                    </span>
                <?php endif; ?>
            </strong>
        </a>
        <span> • <?php echo date('M d, Y', strtotime($reel['created_at'])); ?></span>
    </div>
</div>


                <div class="reel-title"><?php echo htmlspecialchars($reel['title']); ?></div>
                <video controls loop data-reel-id="<?php echo $reel['id']; ?>">
                    <source src="<?php echo htmlspecialchars($reel['video_url']); ?>" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
                <div class="reel-description"><?php echo htmlspecialchars($reel['description']); ?></div>
                <div class="interactions">
                    <span class="like-count">Likes: <?php echo $reel['likes']; ?></span>
                    <span class="view-count">Views: <?php echo $reel['views']; ?></span>
                    <span class="comment-count">Comments: <?php echo $reel['comments']; ?></span>
                    <span class="share-count">Shares: <?php echo $reel['shares']; ?></span>
                </div>
                <div class="action-buttons">
                    <button class="action-btn like-btn <?php echo $reel['user_liked'] ? 'liked' : ''; ?>" 
                            data-reel-id="<?php echo $reel['id']; ?>" 
                            <?php echo $reel['user_liked'] ? 'disabled' : ''; ?>>Like</button>
                    <button class="action-btn comment-btn" data-reel-id="<?php echo $reel['id']; ?>">Comment</button>
                    <button class="action-btn share-btn" data-reel-id="<?php echo $reel['id']; ?>">Share</button>
                </div>
                <div class="comments-section">
                    <?php 
                    $comment_count = count($reel['comments_array']);
                    foreach ($reel['comments_array'] as $index => $comment): 
                    ?>
                        <div class="comment <?php echo $index >= 2 ? 'hidden' : ''; ?>">
                            <strong><?php echo htmlspecialchars($comment['comment_user']); ?>:</strong>
                            <?php echo htmlspecialchars($comment['comment_text']); ?>
                        </div>
                    <?php endforeach; ?>
                    <span class="toggle-comments <?php echo $comment_count > 2 ? 'visible' : ''; ?>" 
                          data-state="more">Show more</span>
                    <form class="comment-form" data-reel-id="<?php echo $reel['id']; ?>">
                        <textarea placeholder="Add a comment..." rows="2" aria-label="Comment"></textarea>
                        <button type="submit" class="action-btn"><span>Post</span><span class="loader"></span></button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!--<a href="dashboard.php" class="back-button" aria-label="Back to dashboard">←</a>-->

    <script>
        // JavaScript for intersection observer, likes, shares, comments, show more, etc.
        document.addEventListener('DOMContentLoaded', () => {
            const videos = document.querySelectorAll('video');
            
            videos.forEach(video => {
                video.addEventListener('play', function() {
                    videos.forEach(otherVideo => {
                        if (otherVideo !== video) otherVideo.pause();
                    });
                    video.muted = false;
                    
                    const reelId = this.getAttribute('data-reel-id');
                    if (!this.dataset.viewCounted) {
                        fetch('interact.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `action=view&reel_id=${reelId}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const reelDiv = this.closest('.reel');
                                const viewCount = reelDiv.querySelector('.view-count');
                                const currentViews = parseInt(viewCount.textContent.split(': ')[1]);
                                viewCount.textContent = `Views: ${currentViews + 1}`;
                                this.dataset.viewCounted = true;
                            }
                        })
                        .catch(error => console.error('Error:', error));
                    }
                });
            });

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const video = entry.target;
                    if (entry.isIntersecting) {
                        video.play().catch(error => console.log('Autoplay blocked:', error));
                    } else {
                        video.pause();
                    }
                });
            }, { threshold: 0.7 });

            videos.forEach(video => observer.observe(video));

            // LIKE
            document.querySelectorAll('.like-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (this.classList.contains('liked')) return;
                    
                    const reelId = this.getAttribute('data-reel-id');
                    fetch('interact.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=like&reel_id=${reelId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const reelDiv = this.closest('.reel');
                            const likeCount = reelDiv.querySelector('.like-count');
                            const currentLikes = parseInt(likeCount.textContent.split(': ')[1]);
                            likeCount.textContent = `Likes: ${currentLikes + 1}`;
                            this.classList.add('liked');
                            this.disabled = true;
                        }
                    })
                    .catch(error => console.error('Error:', error));
                });
            });

            // SHARE
            document.querySelectorAll('.share-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const reelId = this.getAttribute('data-reel-id');
                    const shareUrl = `${window.location.origin}${window.location.pathname}?reel=${reelId}`;
                    
                    navigator.clipboard.writeText(shareUrl).then(() => {
                        fetch('interact.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `action=share&reel_id=${reelId}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const reelDiv = this.closest('.reel');
                                const shareCount = reelDiv.querySelector('.share-count');
                                const currentShares = parseInt(shareCount.textContent.split(': ')[1]);
                                shareCount.textContent = `Shares: ${currentShares + 1}`;
                                alert('Link copied to clipboard!');
                            }
                        });
                    }).catch(error => console.error('Error copying to clipboard:', error));
                });
            });

            // COMMENT
            document.querySelectorAll('.comment-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const reelId = this.getAttribute('data-reel-id');
                    const commentText = this.querySelector('textarea').value.trim();
                    const submitButton = this.querySelector('button');
                    
                    if (commentText) {
                        submitButton.classList.add('loading');
                        submitButton.disabled = true;

                        fetch('interact.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `action=comment&reel_id=${reelId}&comment=${encodeURIComponent(commentText)}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            submitButton.classList.remove('loading');
                            submitButton.disabled = false;

                            if (data.success) {
                                const commentsSection = this.closest('.comments-section');
                                const newComment = document.createElement('div');
                                newComment.className = 'comment';
                                newComment.innerHTML = `<strong>You:</strong> ${commentText}`;
                                commentsSection.insertBefore(newComment, commentsSection.querySelector('.comment') || commentsSection.querySelector('.toggle-comments'));
                                this.querySelector('textarea').value = '';
                                const commentCount = this.closest('.reel').querySelector('.comment-count');
                                const currentComments = parseInt(commentCount.textContent.split(': ')[1]);
                                commentCount.textContent = `Comments: ${currentComments + 1}`;
                                
                                const comments = commentsSection.querySelectorAll('.comment');
                                const toggle = commentsSection.querySelector('.toggle-comments');
                                comments.forEach((comment, index) => {
                                    comment.classList.toggle('hidden', index >= 2);
                                });
                                if (comments.length > 2) {
                                    toggle.classList.add('visible');
                                    toggle.textContent = 'Show more';
                                    toggle.setAttribute('data-state', 'more');
                                }
                            }
                        })
                        .catch(error => {
                            submitButton.classList.remove('loading');
                            submitButton.disabled = false;
                            console.error('Error:', error);
                        });
                    }
                });
            });

            // TOGGLE COMMENTS
            document.querySelectorAll('.toggle-comments').forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const commentsSection = this.closest('.comments-section');
                    const comments = commentsSection.querySelectorAll('.comment');
                    const isShowingMore = this.getAttribute('data-state') === 'more';
                    
                    comments.forEach((comment, index) => {
                        comment.classList.toggle('hidden', !isShowingMore && index >= 2);
                    });
                    
                    this.textContent = isShowingMore ? 'Show less' : 'Show more';
                    this.setAttribute('data-state', isShowingMore ? 'less' : 'more');
                });
            });

            // AUTO-SCROLL if reel param
            <?php if ($scroll_to_reel): ?>
                const reelElement = document.querySelector(`.reel[data-reel-id="${<?php echo $scroll_to_reel; ?>}"]`);
                if (reelElement) {
                    const video = reelElement.querySelector('video');
                    reelElement.scrollIntoView({ behavior: 'smooth' });
                    video.play().catch(error => console.log('Autoplay prevented:', error));
                }
            <?php endif; ?>
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>

                    </div>
                    <div class="col-md-3 third-section">
                        <div class="card shadow-sm">
                            <div class="card-body">
                               
                             <!--califonia-->
                    <h5>
                          "Unimaid Resources brings ease to students by connecting them with essential academic tools, campus updates, and legitimate, up-to-date news, along with features like groups, chatting, posting, social interaction, and more, all designed to enhance their university experience."
                    </h5>
                             
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" id="postModal" aria-labelledby="postModal" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-body post-body">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-7 post-content">
                                <img src="https://scontent.fevn1-2.fna.fbcdn.net/v/t1.0-9/56161887_588993861570433_2896723195090436096_n.jpg?_nc_cat=103&_nc_eui2=AeFI0UuTq3uUF_TLEbnZwM-qSRtgOu0HE2JPwW6b4hIki73-2OWYhc7L1MPsYl9cYy-w122CCak-Fxj0TE1a-kjsd-KXzh5QsuvxbW_mg9qqtg&_nc_ht=scontent.fevn1-2.fna&oh=ea44bffa38f368f98f0553c5cef8e455&oe=5D050B05" alt="post-image">
                            </div>
                            <div class="col-md-5 pr-3">
                                <div class="media text-muted pr-3 pt-3">
                                    <img src="assets/images/users/user-1.jpg" alt="user image" class="mr-3 post-modal-user-img">
                                    <div class="media-body">
                                        <div class="d-flex justify-content-between align-items-center w-100 post-modal-top-user fs-9">
                                            <a href="#" class="text-gray-dark">John Michael</a>
                                            <div class="dropdown">
                                                <a href="#" class="postMoreSettings" role="button" data-toggle="dropdown" id="postOptions" aria-haspopup="true" aria-expanded="false">
                                                    <i class='bx bx-dots-horizontal-rounded'></i>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-right dropdown-menu-lg-left postDropdownMenu">
                                                    <a href="#" class="dropdown-item" aria-describedby="savePost">
                                                        <div class="row">
                                                            <div class="col-md-2">
                                                                <i class='bx bx-bookmark-plus postOptionIcon'></i>
                                                            </div>
                                                            <div class="col-md-10">
                                                                <span class="postOptionTitle">Save post</span>
                                                                <small id="savePost" class="form-text text-muted">Add this to your saved items</small>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="d-block fs-8">3 hours ago <i class='bx bx-globe ml-3'></i></span>
                                    </div>
                                </div>
                                <div class="mt-3 post-modal-caption fs-9">
                                    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Quis voluptatem veritatis harum, tenetur, quibusdam voluptatum, incidunt saepe minus maiores ea atque sequi illo veniam sint quaerat corporis totam et. Culpa?</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chat Popup -->
<!--
    <div class="chat-popup shadow" id="hide-in-mobile">
        <div class="row chat-window col-xs-5 col-md-3">
            <div class="col-md-12">
                <div class="card">
                    <div class="top-bar shadow-sm d-flex align-items-center">
                        <div class="col-md-6 col-xs-6">
                            <a href="profile.html">
                                <img src="assets/images/users/user-2.jpg" class="mr-2 chatbox-user-img" alt="Chat user image">
                                <span class="panel-title">Karen Minas</span>
                            </a>
                        </div>
                        <div class="col-md-6 col-xs-6 d-flex align-items-center justify-content-between">
                            <a href="#">
                                <img src="assets/images/icons/messenger/video-call.png" class="chatbox-call" alt="Chatbox contact types">
                            </a>
                            <a href="#" data-toggle="modal" data-target="#callModal">
                                <img src="assets/images/icons/messenger/call.png" class="chatbox-call" alt="Chatbox contact types">
                            </a>
                            <a href="javascript:void(0)"><i id="minimize-chat-window" class="bx bx-minus icon_minim"></i></a>
                            <a href="javascript:void(0)" id="close-chatbox"><i class="bx bx-x"></i></a>
                        </div>
                    </div>
                    <div id="messagebody" class="msg_container_base">
                        <div class="row msg_container base_sent">
                            <div class="col-md-10 col-xs-10">
                                <div class="messages message-reply bg-primary shadow-sm">
                                    <p>Are you going to the party on Saturday?</p>
                                </div>
                            </div>
                        </div>
                        <div class="row msg_container base_receive">
                            <div class="col-md-10 col-xs-10">
                                <div class="messages message-receive shadow-sm">
                                    <p>I was thinking about it. Are you?</p>
                                </div>
                            </div>
                        </div>
                        <div class="row msg_container base_receive">
                            <div class="col-xs-10 col-md-10">
                                <div class="messages message-receive shadow-sm">
                                    <p>Really? Well, what time does it start?</p>
                                </div>
                            </div>
                        </div>
                        <div class="row msg_container base_sent">
                            <div class="col-xs-10 col-md-10">
                                <div class="messages message-reply bg-primary shadow-sm">
                                    <p>It starts at 8:00 pm, and I really think you should go.</p>
                                </div>
                            </div>
                        </div>
                        <div class="row msg_container base_receive">
                            <div class="col-xs-10 col-md-10">
                                <div class="messages message-receive shadow-sm">
                                    <p>Well, who else is going to be there?</p>
                                </div>
                            </div>
                        </div>
                        <div class="row msg_container base_sent">
                            <div class="col-md-10 col-xs-10">
                                <div class="messages message-reply bg-primary shadow-sm">
                                    <p>Everybody from school.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer chat-inputs">
                        <div class="col-md-12 message-box">
                            <input type="text" class="w-100 search-input type-message" placeholder="Type a message..." />
                            <div class="chat-tools">
                                <a href="#" class="chatbox-tools">
                                    <img src="assets/images/icons/theme/post-image.png" class="chatbox-tools-img" alt="Chatbox tool">
                                </a>
                                <a href="#" class="chatbox-tools">
                                    <img src="assets/images/icons/messenger/gif.png" class="chatbox-tools-img" alt="Chatbox tool">
                                </a>
                                <a href="#" class="chatbox-tools">
                                    <img src="assets/images/icons/messenger/smile.png" class="chatbox-tools-img" alt="Chatbox tool">
                                </a>
                                <a href="#" class="chatbox-tools">
                                    <img src="assets/images/icons/messenger/console.png" class="chatbox-tools-img" alt="Chatbox tool">
                                </a>
                                <a href="#" class="chatbox-tools">
                                    <img src="assets/images/icons/messenger/attach-file.png" class="chatbox-tools-img" alt="Chatbox tool">
                                </a>
                                <a href="#" class="chatbox-tools">
                                    <img src="assets/images/icons/messenger/photo-camera.png" class="chatbox-tools-img" alt="Chatbox tool">
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
-->
    <!-- END Chat Popup -->
    
    <!-- Call modal -->
    <div id="callModal" class="modal fade call-modal" tabindex="-1" role="dialog" aria-labelledby="callModalLabel" aria-hidden="true">
        <div class="modal-dialog call-modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header align-items-center">
                    <div class="call-status">
                        <h1 id="callModalLabel" class="modal-title mr-3">Connected</h1>
                        <span class="online-status bg-success"></span>
                    </div>
                    <div class="modal-options d-flex align-items-center">
                        <button type="button" class="btn btn-quick-link" id="minimize-call-window">
                            <i class='bx bx-minus'></i>
                        </button>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="row h-100">
                        <div class="col-md-12 d-flex align-items-center justify-content-center">
                            <div class="call-user text-center">
                                <div class="call-user-img-anim">
                                    <img src="assets/images/users/user-1.jpg" class="call-user-img" alt="Call user image">
                                </div>
                                <p class="call-user-name">Name Surename</p>
                                <p class="text-muted call-time">05:28</p>
                            </div>
                        </div>
                        <div class="col-md-4 offset-md-4 d-flex align-items-center justify-content-between call-btn-list">
                            <a href="#" class="btn call-btn" data-toggle="tooltip" data-placement="top" data-title="Disable microphone"><i class='bx bxs-microphone'></i></a>
                            <a href="#" class="btn call-btn" data-toggle="tooltip" data-placement="top" data-title="Enable camera"><i class='bx bxs-video-off'></i></a>
                            <a href="#" class="btn call-btn drop-call" data-toggle="tooltip" data-placement="top" data-title="End call" data-dismiss="modal" aria-label="Close"><i class='bx bxs-phone'></i></a>
                            <a href="#" class="btn call-btn" data-toggle="tooltip" data-placement="top" data-title="Share Screen"><i class='bx bx-laptop'></i></a>
                            <a href="#" class="btn call-btn" data-toggle="tooltip" data-placement="top" data-title="Dark mode"><i class='bx bx-moon'></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
  <style>

  </style>
    <!-- END call modal -->
  <!--==================================================================-->
  <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/slick.js"></script>
    <script src="js/jquery.nice-select.min.js"></script>
    <script src="js/plyr.js"></script>
    <!-- <script src="assets/js/plugins/apexcharts.js"></script> -->
    <script src="js/wow.min.js"></script>
    <script src="js/plugin.js"></script>
    <script src="js/main.js"></script>
    <script src="js/script.js"></script>

    <!-- Core -->
    <script src="assets/js/jquery/jquery-3.3.1.min.js"></script>
    <script src="assets/js/popper/popper.min.js"></script>
    <script src="assets/js/bootstrap/bootstrap.min.js"></script>
    <!-- Optional -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.8.0"></script>
    <script type="text/javascript">
        $("#menu-toggle").click(function(e) {
            e.preventDefault();
            $("#wrapper").toggleClass("toggled");
        });

    </script>
    <script src="assets/js/app.js"></script>
    <script src="assets/js/components/components.js"></script>
</body>

</html>
