<?php
include('db_connection.php');
// Start session and get the logged-in user's ID
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all users except the logged-in user, including verified status
$sql_users = "SELECT id, username, profile_picture, verified FROM users WHERE id != ?";
$stmt_users = $conn->prepare($sql_users);
$stmt_users->bind_param("i", $user_id);
$stmt_users->execute();
$users_result = $stmt_users->get_result();

// Get the follow status for each user
$follow_status = [];
$sql_follow_status = "SELECT following_id FROM followers WHERE follower_id = ?";
$stmt_follow_status = $conn->prepare($sql_follow_status);
$stmt_follow_status->bind_param("i", $user_id);
$stmt_follow_status->execute();
$follow_status_result = $stmt_follow_status->get_result();

while ($row = $follow_status_result->fetch_assoc()) {
    $follow_status[$row['following_id']] = true;
}

// Get the follower count for each user
$follower_counts = [];
$sql_follower_counts = "SELECT following_id, COUNT(*) AS followers FROM followers GROUP BY following_id";
$stmt_follower_counts = $conn->prepare($sql_follower_counts);
$stmt_follower_counts->execute();
$result_follower_counts = $stmt_follower_counts->get_result();

while ($row = $result_follower_counts->fetch_assoc()) {
    $follower_counts[$row['following_id']] = $row['followers'];
}

// Handle Follow/Unfollow action via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['follow'])) {
    $user_id_to_follow = $_POST['user_id'];
    $action = $_POST['follow_action'];

    if ($action === 'follow') {
        $sql_follow = "INSERT INTO followers (follower_id, following_id) VALUES (?, ?)";
        $stmt_follow = $conn->prepare($sql_follow);
        $stmt_follow->bind_param("ii", $user_id, $user_id_to_follow);
        if ($stmt_follow->execute()) {
            $sql_followers = "SELECT COUNT(*) AS followers FROM followers WHERE following_id = ?";
            $stmt_followers = $conn->prepare($sql_followers);
            $stmt_followers->bind_param("i", $user_id_to_follow);
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
        $stmt_unfollow->bind_param("ii", $user_id, $user_id_to_follow);
        if ($stmt_unfollow->execute()) {
            $sql_followers = "SELECT COUNT(*) AS followers FROM followers WHERE following_id = ?";
            $stmt_followers = $conn->prepare($sql_followers);
            $stmt_followers->bind_param("i", $user_id_to_follow);
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Students</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary: #6a1b9a;
            --primary-dark: #4a148c;
            --secondary: #e9ecef;
            --text: #2c3e50;
            --white: #ffffff;
            --light-bg: #f8f9fa;
            --shadow: rgba(0, 0, 0, 0.15);
            --accent: #00c4cc;
            --hover-bg: #f1f3f5;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--light-bg), #e0e0e0);
            color: var(--text);
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 1000px;
            background: var(--white);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 30px var(--shadow);
            position: relative;
            overflow: hidden;
        }

        h2 {
            font-size: 2.2rem;
            color: var(--primary);
            text-align: center;
            margin-bottom: 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            position: relative;
        }

        h2::after {
            content: '';
            width: 50px;
            height: 3px;
            background: var(--primary);
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
        }

        .search-container {
            position: relative;
            margin-bottom: 30px;
        }

        .search-container input {
            width: 100%;
            padding: 14px 20px;
            border: 2px solid var(--secondary);
            border-radius: 30px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s;
            background: var(--white);
            box-shadow: 0 2px 5px var(--shadow);
        }

        .search-container input:focus {
            border-color: var(--primary);
            box-shadow: 0 4px 15px rgba(106, 27, 154, 0.2);
        }

        .search-container .suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 5px 15px var(--shadow);
            max-height: 200px;
            overflow-y: auto;
            z-index: 10;
            display: none;
        }

        .search-container .suggestions a {
            display: block;
            padding: 10px 20px;
            color: var(--text);
            text-decoration: none;
            transition: background 0.3s, padding-left 0.3s;
        }

        .search-container .suggestions a:hover {
            background: var(--hover-bg);
            padding-left: 25px;
        }

        .user-list {
            display: grid;
            gap: 20px;
        }

        .user-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: var(--white);
            border-radius: 15px;
            box-shadow: 0 5px 15px var(--shadow);
            transition: transform 0.3s, box-shadow 0.3s;
            animation: fadeIn 0.5s ease-in-out;
        }

        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px var(--shadow);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .user-card img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid var(--primary);
            transition: transform 0.3s, border-color 0.3s;
        }

        .user-card img:hover {
            transform: scale(1.05);
            border-color: var(--accent);
        }

        .user-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .user-info a {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary);
            text-decoration: none;
            transition: color 0.3s;
        }

        .user-info a:hover {
            color: var(--primary-dark);
        }

        .followers-count {
            font-size: 0.9rem;
            color: #777;
            font-weight: 500;
        }

        .follow-button {
            background: var(--primary);
            color: var(--white);
            padding: 10px 20px;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s, box-shadow 0.3s;
        }

        .follow-button:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
            box-shadow: 0 4px 10px rgba(74, 20, 140, 0.3);
        }

        .verified-badge, .gold-badge, .black-badge, .pink-badge {
            font-size: 0.9rem;
            margin-left: 8px;
            position: relative;
            top: 2px;
        }

        .verified-badge { color: #1e90ff; }
        .gold-badge { color: #e0b20b; }
        .black-badge { color: #000000; }
        .pink-badge { color: #e91e63; }

        .verified-badge .fa-check, .gold-badge .fa-check, .black-badge .fa-check, .pink-badge .fa-check {
            font-size: 0.6rem;
            color: var(--white);
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
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
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            text-decoration: none;
            box-shadow: 0 4px 12px var(--shadow);
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: var(--primary-dark);
            transform: scale(1.1);
        }

        @media (max-width: 768px) {
            .container { padding: 20px; }
            h2 { font-size: 1.8rem; }
            .user-card { padding: 15px; }
            .user-card img { width: 70px; height: 70px; }
            .user-info a { font-size: 1.2rem; }
            .follow-button { padding: 8px 15px; font-size: 0.9rem; }
        }

        @media (max-width: 480px) {
            body { padding: 10px; }
            .container { padding: 15px; width: calc(100% - 20px); }
            h2 { font-size: 1.5rem; }
            .user-card { flex-direction: column; align-items: flex-start; padding: 12px; }
            .user-card img { width: 60px; height: 60px; }
            .user-info a { font-size: 1rem; }
            .followers-count { font-size: 0.8rem; }
            .follow-button { width: 100%; padding: 8px; font-size: 0.85rem; }
            .search-container input { padding: 12px 15px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>All Students</h2>
        
        <!-- Search Bar -->
        <div class="search-container">
            <input type="text" id="userSearch" placeholder="Search students...">
            <div class="suggestions" id="suggestions"></div>
        </div>

        <div class="user-list" id="userList">
            <?php
            $priority_users = [];
            $other_users = [];
            while ($user = $users_result->fetch_assoc()) {
                $user['followers_count'] = isset($follower_counts[$user['id']]) ? $follower_counts[$user['id']] : 0;
                if ($user['id'] == 13 || $user['id'] == 26) {
                    $priority_users[] = $user;
                } else {
                    $other_users[] = $user;
                }
            }

            usort($priority_users, function($a, $b) {
                return $a['id'] == 13 ? -1 : ($b['id'] == 13 ? 1 : ($a['id'] - $b['id']));
            });

            usort($other_users, function($a, $b) {
                return $b['followers_count'] - $a['followers_count'];
            });

            $users = array_merge($priority_users, $other_users);

            foreach ($users as $user): ?>
                <div class="user-card" data-username="<?php echo htmlspecialchars($user['username']); ?>">
                    <img src="/dashboard/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Image">
                    <div class="user-info">
                        <a href="view_profile.php?user_id=<?php echo $user['id']; ?>">
                            <?php echo htmlspecialchars($user['username']); ?>
                            <?php if (isset($user['verified']) && $user['verified'] == 1): ?>
                                <span class="fa fa-circle verified-badge" title="Verified User">
                                    <span class="fa fa-check"></span>
                                </span>
                            <?php elseif (isset($user['verified']) && $user['verified'] == 2): ?>
                                <span class="fa fa-circle gold-badge" title="Gold Verified User">
                                    <span class="fa fa-check"></span>
                                </span>
                            <?php elseif (isset($user['verified']) && $user['verified'] == 3): ?>
                                <span class="fa fa-circle black-badge" title="Black Verified User">
                                    <span class="fa fa-check"></span>
                                </span>
                            <?php elseif (isset($user['verified']) && $user['verified'] == 4): ?>
                                <span class="fa fa-circle pink-badge" title="Pink Verified User">
                                    <span class="fa fa-check"></span>
                                </span>
                            <?php endif; ?>
                        </a>
                        <div class="followers-count" id="followers-count-<?php echo $user['id']; ?>">Followers: <?php echo $user['followers_count']; ?></div>
                        <button class="follow-button" data-user-id="<?php echo $user['id']; ?>" data-action="<?php echo isset($follow_status[$user['id']]) ? 'unfollow' : 'follow'; ?>">
                            <?php echo isset($follow_status[$user['id']]) ? 'Unfollow' : 'Follow'; ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Back Button -->
    <div class="back-button-container">
        <a href="dashboard.php" class="back-button" aria-label="Back to dashboard">←</a>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Follow/Unfollow AJAX
            $('.follow-button').on('click', function(event) {
                event.preventDefault();
                var button = $(this);
                var userId = button.data('user-id');
                var action = button.data('action');
                var newAction = action === 'follow' ? 'unfollow' : 'follow';
                var newText = action === 'follow' ? 'Unfollow' : 'Follow';

                button.text(newText);
                button.data('action', newAction);

                $.ajax({
                    url: 'users.php',
                    type: 'POST',
                    data: {
                        follow: 1,
                        user_id: userId,
                        follow_action: action
                    },
                    success: function(response) {
                        try {
                            var data = JSON.parse(response);
                            if (data.status === 'success') {
                                button.text(data.button_text);
                                button.data('action', data.button_text.toLowerCase());
                                $('#followers-count-' + userId).text('Followers: ' + data.followers_count);
                            } else {
                                button.text(action === 'follow' ? 'Follow' : 'Unfollow');
                                button.data('action', action);
                                console.error('Server returned error:', data);
                            }
                        } catch (e) {
                            button.text(action === 'follow' ? 'Follow' : 'Unfollow');
                            button.data('action', action);
                            console.error('Failed to parse response:', e);
                        }
                    },
                    error: function(xhr, status, error) {
                        button.text(action === 'follow' ? 'Follow' : 'Unfollow');
                        button.data('action', action);
                        console.error('AJAX error:', status, error);
                    }
                });
            });

            // Search Functionality
            const searchInput = $('#userSearch');
            const suggestions = $('#suggestions');
            const userList = $('#userList');
            const userCards = $('.user-card');

            searchInput.on('input', function() {
                const query = $(this).val().toLowerCase().trim();
                suggestions.empty();

                if (query) {
                    let matches = [];
                    userCards.each(function() {
                        const username = $(this).data('username').toLowerCase();
                        if (username.includes(query)) {
                            matches.push($(this).find('.user-info a').text().trim());
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });

                    if (matches.length > 0) {
                        matches.forEach(match => {
                            suggestions.append(`<a href="#" data-username="${match}">${match}</a>`);
                        });
                        suggestions.show();
                    } else {
                        suggestions.hide();
                    }
                } else {
                    userCards.show();
                    suggestions.hide();
                }
            });

            // Handle suggestion click
            suggestions.on('click', 'a', function(e) {
                e.preventDefault();
                const username = $(this).data('username');
                searchInput.val(username);
                userCards.each(function() {
                    const cardUsername = $(this).data('username');
                    if (cardUsername.toLowerCase() === username.toLowerCase()) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
                suggestions.hide();
            });

            // Hide suggestions when clicking outside
            $(document).on('click', function(e) {
                if (!searchInput.is(e.target) && !suggestions.is(e.target) && suggestions.has(e.target).length === 0) {
                    suggestions.hide();
                }
            });
        });
    </script>
</body>
</html>