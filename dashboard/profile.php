<?php
// Database connection
$conn = new mysqli("localhost", "unimaid9_unimaidresources", "#adyems123AD", "unimaid9_unimaidresources");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session and get the logged-in user's ID
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: unimaidresources.com.ng/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user information (sales_status added to the selection)
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
        $uploadDir = 'Uploads/profile_pictures/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (!in_array($profilePic['type'], $allowedTypes)) {
            $profileError = "Invalid file type. Please upload a JPG, PNG, or GIF dance.";
        } else {
            $fileName = uniqid() . '_' . basename($profilePic['name']);
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
                $stmt_update->close();
            } else {
                $profileError = "Error uploading the profile picture.";
            }
        }
    }
}

// Get the follower count for the user
$sql_followers = "SELECT COUNT(*) AS followers FROM followers WHERE following_id = ?";
$stmt_followers = $conn->prepare($sql_followers);
$stmt_followers->bind_param("i", $user_id);
$stmt_followers->execute();
$followers_result = $stmt_followers->get_result();
$followers_count = $followers_result->fetch_assoc()['followers'];

// Check if the user is already following (for other users' profiles, not self)
$sql_follow_status = "SELECT COUNT(*) AS is_following FROM followers WHERE follower_id = ? AND following_id = ?";
$stmt_follow_status = $conn->prepare($sql_follow_status);
$stmt_follow_status->bind_param("ii", $user_id, $user['id']);
$stmt_follow_status->execute();
$follow_status_result = $stmt_follow_status->get_result();
$follow_status = $follow_status_result->fetch_assoc()['is_following'];

// Fetch user's reels
$sql_reels = "SELECT id, video_url, title, description, likes, comments, shares, created_at FROM reels WHERE user_id = ? ORDER BY created_at DESC";
$stmt_reels = $conn->prepare($sql_reels);
$stmt_reels->bind_param("i", $user_id);
$stmt_reels->execute();
$reels_result = $stmt_reels->get_result();
$reels = $reels_result->fetch_all(MYSQLI_ASSOC);

// Handle Follow/Unfollow action via AJAX
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
        $stmt_follow->close();
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
        $stmt_unfollow->close();
    }
    exit();
}

function formatNumber($num) {
    if ($num >= 1000000) {
        return round($num / 1000000, 1) . 'M';
    }
    if ($num >= 1000) {
        return round($num / 1000, 1) . 'K';
    }
    return $num;
}

function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . 'y ago';
    if ($diff->m > 0) return $diff->m . 'mo ago';
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'm ago';
    return 'Just now';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($user['username']); ?>'s Profile</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6a1b9a',
                        'primary-dark': '#4a148c',
                        'tiktok-bg': '#000',
                        'tiktok-gray': '#2d2d2d',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background: #000;
            color: #fff;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: 0;
        }
        .profile-header {
            position: relative;
            padding: 20px;
            text-align: center;
            background: linear-gradient(to bottom, rgba(0,0,0,0.8), rgba(0,0,0,0));
        }
        .profile-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #fff;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            cursor: pointer;
        }
        .username {
            font-size: 24px;
            font-weight: 700;
            margin: 10px 0 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .verified-badge {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #1e90ff;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .verified-badge i {
            font-size: 12px;
            color: #fff;
        }
        .stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 15px 0;
        }
        .stats div {
            text-align: center;
        }
        .stats span {
            display: block;
            font-size: 18px;
            font-weight: 600;
        }
        .stats small {
            font-size: 14px;
            color: #aaa;
        }
        .bio {
            font-size: 14px;
            color: #ccc;
            margin: 10px 20px;
            line-height: 1.4;
            word-break: break-word;
        }
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 15px 0;
        }
        .action-buttons button, .action-buttons a {
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 20px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #fff;
        }
        .edit-profile-btn, .logout-btn {
            background: #333;
        }
        .edit-profile-btn:hover, .logout-btn:hover {
            background: #444;
        }
        .sales-btn {
            background: linear-gradient(to right, #fe2c55, #ff0050);
        }
        .sales-btn:hover {
            background: linear-gradient(to right, #e0284d, #e50047);
        }
        .tabs {
            display: flex;
            justify-content: center;
            border-bottom: 1px solid #333;
            margin: 0 20px;
        }
        .tab {
            flex: 1;
            text-align: center;
            padding: 12px 0;
            font-size: 16px;
            font-weight: 600;
            color: #aaa;
            cursor: pointer;
            position: relative;
            transition: color 0.3s ease;
        }
        .tab.active {
            color: #fff;
        }
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 2px;
            background: #fff;
        }
        .tab-content {
            display: none;
            padding: 20px;
        }
        .tab-content.active {
            display: block;
        }
        .reels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 8px;
            padding: 0 10px;
        }
        .reel-item {
            position: relative;
            aspect-ratio: 9/16;
            overflow: hidden;
            border-radius: 8px;
            cursor: pointer;
        }
        .reel-item video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            background: #000;
        }
        .reel-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 8px;
            background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
            color: #fff;
            font-size: 12px;
        }
        .reel-overlay i {
            margin-right: 4px;
        }
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            position: relative;
            max-width: 90%;
            max-height: 90vh;
            aspect-ratio: 9/16;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
        }
        .modal-content video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .close-modal {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
            background: rgba(0,0,0,0.5);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .back-button-container {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
        }
        .back-button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(0,0,0,0.5);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .back-button:hover {
            background: rgba(0,0,0,0.7);
            transform: scale(1.1);
        }
        .profile-pic-upload {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .profile-pic-upload.active {
            display: flex;
        }
        .upload-form {
            background: #222;
            padding: 20px;
            border-radius: 12px;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }
        .upload-form input[type="file"] {
            display: block;
            margin: 10px auto;
            color: #fff;
        }
        .upload-form button {
            background: linear-gradient(to right, #fe2c55, #ff0050);
            padding: 10px 20px;
            border-radius: 20px;
            border: none;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
        }
        .upload-form button:hover {
            background: linear-gradient(to right, #e0284d, #e50047);
        }
        .close-upload {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
        }
        .error, .success {
            padding: 10px;
            border-radius: 8px;
            margin: 10px 0;
            text-align: center;
            font-size: 14px;
        }
        .error {
            background: rgba(220, 53, 69, 0.2);
            color: #ff5555;
        }
        .success {
            background: rgba(40, 167, 69, 0.2);
            color: #55ff55;
        }
        @media (max-width: 768px) {
            .profile-img {
                width: 80px;
                height: 80px;
            }
            .username {
                font-size: 20px;
            }
            .stats span {
                font-size: 16px;
            }
            .stats small {
                font-size: 12px;
            }
            .bio {
                font-size: 13px;
            }
            .action-buttons button, .action-buttons a {
                padding: 6px 12px;
                font-size: 13px;
            }
            .reels-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }
        }
        @media (max-width: 480px) {
            .profile-img {
                width: 60px;
                height: 60px;
            }
            .username {
                font-size: 18px;
            }
            .stats {
                gap: 20px;
            }
            .stats span {
                font-size: 14px;
            }
            .stats small {
                font-size: 11px;
            }
            .bio {
                font-size: 12px;
                margin: 10px;
            }
            .action-buttons {
                flex-direction: column;
                gap: 8px;
            }
            .action-buttons button, .action-buttons a {
                width: 100%;
                max-width: 200px;
                margin: 0 auto;
            }
            .tabs {
                margin: 0 10px;
            }
            .tab {
                font-size: 14px;
            }
            .reels-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="back-button-container">
        <a href="dashboard.php" class="back-button" aria-label="Back to dashboard"><i class="fas fa-arrow-left"></i></a>
    </div>

    <div class="container">
        <div class="profile-header">
            <img class="profile-img" src="/dashboard/<?php echo htmlspecialchars($user['profile_picture'] ?: 'default_profile.png'); ?>" alt="Profile Picture">
            <div class="username">
                @<?php echo htmlspecialchars($user['username']); ?>
                <?php if ($user['verified'] == 1): ?>
                    <span class="verified-badge" title="Verified User"><i class="fas fa-check"></i></span>
                <?php endif; ?>
            </div>
            <div class="stats">
                <div>
                    <span id="followers-count"><?php echo formatNumber($followers_count); ?></span>
                    <small>Followers</small>
                </div>
                <div>
                    <span><?php echo formatNumber(count($reels)); ?></span>
                    <small>Reels</small>
                </div>
                <div>
                    <span><?php echo formatNumber(array_sum(array_column($reels, 'likes'))); ?></span>
                    <small>Likes</small>
                </div>
            </div>
            <div class="bio"><?php echo htmlspecialchars($user['about_me'] ?: 'No bio yet.'); ?></div>
            <div class="action-buttons">
                <button class="edit-profile-btn" onclick="window.location.href='profile_form.php'"><i class="fas fa-edit"></i> Edit Profile</button>
                <?php if (isset($user['sales_status']) && $user['sales_status'] == 1): ?>
                    <a href="saleschat.php" class="sales-btn"><i class="fas fa-comments"></i> Sales Chat</a>
                    <a href="salespayment.php" class="sales-btn"><i class="fas fa-upload"></i> Upload Item</a>
                <?php endif; ?>
                <button class="logout-btn" onclick="window.location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </div>
        </div>

        <!-- Profile Picture Upload Modal -->
        <div class="profile-pic-upload">
            <div class="upload-form">
                <span class="close-upload"><i class="fas fa-times"></i></span>
                <h3 class="text-lg font-bold mb-4">Update Profile Picture</h3>
                <?php if (empty($user['profile_picture'])): ?>
                    <div class="error">You need to upload your profile picture now!</div>
                <?php endif; ?>
                <?php if (isset($profileError)): ?>
                    <div class="error"><?php echo $profileError; ?></div>
                <?php endif; ?>
                <?php if (isset($profileSuccess)): ?>
                    <div class="success"><?php echo $profileSuccess; ?></div>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="new_profile_picture" accept="image/*" required>
                    <button type="submit" name="update_profile">Upload Picture</button>
                </form>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <div class="tab active" data-tab="reels"><i class="fas fa-video"></i> Reels</div>
            <div class="tab" data-tab="info"><i class="fas fa-info-circle"></i> Info</div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content active" id="reels">
            <?php if (empty($reels)): ?>
                <div class="text-center py-10 text-gray-400">
                    <p>No reels yet</p>
                    <p class="text-sm mt-2">Start sharing your videos!</p>
                </div>
            <?php else: ?>
                <div class="reels-grid">
                    <?php foreach ($reels as $reel): ?>
                        <div class="reel-item" data-reel-url="<?php echo htmlspecialchars($reel['video_url']); ?>">
                            <video playsinline muted>
                                <source src="<?php echo htmlspecialchars($reel['video_url']); ?>" type="video/mp4">
                            </video>
                            <div class="reel-overlay">
                                <span><i class="fas fa-heart"></i> <?php echo formatNumber($reel['likes']); ?></span>
                                <span><i class="fas fa-comment"></i> <?php echo formatNumber($reel['comments']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="tab-content" id="info">
            <div class="info-section p-4 bg-tiktok-gray rounded-lg">
                <div><strong>Full Name:</strong> <?php echo htmlspecialchars($user['full_name'] ?: 'Not set'); ?></div>
                <div><strong>Address:</strong> <?php echo htmlspecialchars($user['address'] ?: 'Not set'); ?></div>
                <div><strong>Department:</strong> <?php echo htmlspecialchars($user['department'] ?: 'Not set'); ?></div>
                <div><strong>Level:</strong> <?php echo htmlspecialchars($user['level'] ?: 'Not set'); ?></div>
                <div><strong>Gender:</strong> <?php echo htmlspecialchars($user['gender'] ?: 'Not set'); ?></div>
                <div><strong>Interests:</strong> <?php echo htmlspecialchars($user['interests'] ?: 'Not set'); ?></div>
                <div><strong>Relationship Status:</strong> <?php echo htmlspecialchars($user['relationship_status'] ?: 'Not set'); ?></div>
                <div><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone_number'] ?: 'Not set'); ?></div>
            </div>
        </div>

        <!-- Video Modal -->
        <div class="modal">
            <div class="modal-content">
                <span class="close-modal"><i class="fas fa-times"></i></span>
                <video controls playsinline>
                    <source src="" type="video/mp4">
                </video>
            </div>
        </div>
    </div>

    <script>
        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => console.log('Service Worker registered:', registration))
                    .catch(error => console.error('Service Worker registration failed:', error));
            });
        }

        $(document).ready(function() {
            // Tab switching
            $('.tab').on('click', function() {
                $('.tab').removeClass('active');
                $('.tab-content').removeClass('active');
                $(this).addClass('active');
                $(`#${$(this).data('tab')}`).addClass('active');
            });

            // Profile picture upload modal
            $('.profile-img').on('click', function() {
                $('.profile-pic-upload').addClass('active');
            });

            $('.close-upload').on('click', function() {
                $('.profile-pic-upload').removeClass('active');
            });

            // Reel video modal
            $('.reel-item').on('click', function() {
                const videoUrl = $(this).data('reel-url');
                $('.modal video source').attr('src', videoUrl);
                $('.modal video')[0].load();
                $('.modal').addClass('active');
                $('.modal video')[0].play();
            });

            $('.close-modal').on('click', function() {
                $('.modal').removeClass('active');
                $('.modal video')[0].pause();
                $('.modal video source').attr('src', '');
            });

            // Handle profile picture form submission via AJAX
            $('.upload-form form').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                $.ajax({
                    url: '',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.includes('Profile picture updated successfully')) {
                            $('.profile-img').attr('src', response.new_profile_picture);
                            $('.profile-pic-upload').removeClass('active');
                            $('.upload-form').prepend('<div class="success">Profile picture updated successfully.</div>');
                            setTimeout(() => $('.upload-form .success').remove(), 3000);
                            location.reload(); // Refresh to update session
                        } else {
                            $('.upload-form').prepend('<div class="error">Error updating profile picture.</div>');
                            setTimeout(() => $('.upload-form .error').remove(), 3000);
                        }
                    },
                    error: function() {
                        $('.upload-form').prepend('<div class="error">Failed to upload. Please try again.</div>');
                        setTimeout(() => $('.upload-form .error').remove(), 3000);
                    }
                });
            });
        });
    </script>
</body>
</html>
<?php
// Close statements and connection
$stmt->close();
$stmt_followers->close();
$stmt_follow_status->close();
$stmt_reels->close();
$conn->close();
?>