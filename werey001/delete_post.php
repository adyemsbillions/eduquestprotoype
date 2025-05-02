<?php
ob_start();
include("db_connection.php");

// Define bad words array (unchanged)
$bad_words = [
    'fucking', 'fuck', 'stupid', 'porn', 'vagina', 'penis', 'shit', 'asshole', 'bitch', 'damn',
    'bastard', 'cunt', 'dick', 'pussy', 'ass', 'cock', 'whore', 'slut', 'faggot', 'nigger',
    'retard', 'crap', 'bullshit', 'piss', 'prick', 'twat', 'wanker', 'arse', 'bollocks', 'tits',
    'motherfucker', 'douche', 'jerk', 'suck', 'blowjob', 'cum', 'screw', 'arsehole', 'dumbass',
    'shithead', 'freak', 'idiot', 'moron', 'dickhead', 'piss off', 'jackass', 'nasty', 'pervert',
    'skank', 'trash', 'scum', 'horny', 'bitchy', 'fucked', 'shag'
];

// Search functionality (unchanged)
$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Fetch posts (unchanged)
$sql_posts = "
    SELECT p.post_id, p.post_content, p.media_url, p.media_type, p.created_at, 
           u.username, u.profile_picture
    FROM posts p
    JOIN users u ON p.user_id = u.id
    " . ($search_query ? "WHERE p.post_content LIKE '%$search_query%'" : "") . "
    ORDER BY p.created_at DESC
";
$result_posts = $conn->query($sql_posts);

if (!$result_posts) {
    die("Query failed: " . $conn->error);
}

// Create flagged posts table (unchanged)
$conn->query("
    CREATE TABLE IF NOT EXISTS flagged_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT,
        flagged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE
    )
");

// Filter function (unchanged)
function filterContent($content, $bad_words, $conn) {
    $words = explode(' ', strtolower($content));
    $has_bad_words = false;
    
    foreach ($words as $word) {
        if (in_array(trim($word, ".,!?"), $bad_words)) {
            $has_bad_words = true;
            break;
        }
    }
    
    return [
        'content' => preg_replace_callback(
            '/\b(' . implode('|', array_map('preg_quote', $bad_words)) . ')\b/i',
            function($match) {
                return '<span class="bad-word">' . $match[0] . '</span>';
            },
            htmlspecialchars($content)
        ),
        'has_bad_words' => $has_bad_words
    ];
}

// Handle post deletion (unchanged)
if (isset($_GET['delete_post_id'])) {
    $post_id = $_GET['delete_post_id'];
    $conn->begin_transaction();
    try {
        $sql_replies = "DELETE r FROM replies r INNER JOIN comments c ON r.comment_id = c.comment_id WHERE c.post_id = ?";
        $stmt_replies = $conn->prepare($sql_replies);
        $stmt_replies->bind_param("i", $post_id);
        $stmt_replies->execute();

        $sql_likes = "DELETE FROM post_likes WHERE post_id = ?";
        $stmt_likes = $conn->prepare($sql_likes);
        $stmt_likes->bind_param("i", $post_id);
        $stmt_likes->execute();

        $sql_comments = "DELETE FROM comments WHERE post_id = ?";
        $stmt_comments = $conn->prepare($sql_comments);
        $stmt_comments->bind_param("i", $post_id);
        $stmt_comments->execute();

        $sql_delete_post = "DELETE FROM posts WHERE post_id = ?";
        $stmt_delete_post = $conn->prepare($sql_delete_post);
        $stmt_delete_post->bind_param("i", $post_id);
        $stmt_delete_post->execute();

        $conn->commit();
        header("Location: delete_post.php?message=Post+deleted+successfully");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: delete_post.php?error=Failed+to+delete+post:+" . urlencode($e->getMessage()));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Posts</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary: #6a1b9a;
            --primary-dark: #4a148c;
            --secondary: #e3e3e3;
            --text: #333;
            --white: #fff;
            --light-bg: #f5f5f5;
            --danger: #dc3545;
            --success: #28a745;
            --warning: #ffc107;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
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

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 15px;
        }

        h1 {
            font-size: 32px;
            color: var(--primary);
            text-align: center;
            margin-bottom: 30px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Search Bar */
        .search-bar {
            background: var(--white);
            padding: 15px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .search-bar input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid var(--secondary);
            border-radius: 6px;
            font-size: 16px;
            transition: var(--transition);
        }

        .search-bar input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 5px rgba(106, 27, 154, 0.3);
        }

        .search-bar button {
            padding: 10px 25px;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: var(--transition);
        }

        .search-bar button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Quick Access */
        .quick-access {
            background: var(--white);
            padding: 15px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
            justify-content: center;
        }

        .quick-access a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 5px;
            transition: var(--transition);
        }

        .quick-access a:hover {
            background: var(--primary);
            color: var(--white);
        }

        /* Messages */
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 500;
        }

        .message.success {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .message.error {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger);
            border: 1px solid var(--danger);
        }

        /* Post Card */
        .post-card {
            background: var(--white);
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-bottom: 25px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.15);
        }

        .post-card.flagged {
            border: 3px solid var(--danger);
            background: rgba(220, 53, 69, 0.05);
        }

        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--secondary);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-info img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
            padding: 2px;
        }

        .user-info h6 {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
        }

        .post-content p {
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .bad-word {
            color: var(--danger);
            font-weight: bold;
            background: rgba(220, 53, 69, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
        }

        .post-time {
            color: #666;
            font-size: 13px;
            margin-bottom: 20px;
            font-style: italic;
        }

        .post-media img,
        .post-media video {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-bottom: 20px;
            display: block;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .delete-btn {
            display: inline-block;
            padding: 12px 25px;
            background: var(--danger);
            color: var(--white);
            text-decoration: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            transition: var(--transition);
        }

        .delete-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        }

        .no-posts {
            text-align: center;
            font-size: 18px;
            color: #777;
            padding: 30px;
            background: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-top: 20px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 0 10px;
            }

            h1 {
                font-size: 28px;
            }

            .post-card {
                padding: 20px;
            }

            .user-info img {
                width: 40px;
                height: 40px;
            }

            .user-info h6 {
                font-size: 16px;
            }

            .search-bar {
                flex-direction: column;
            }

            .search-bar input,
            .search-bar button {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 24px;
            }

            .post-card {
                padding: 15px;
            }

            .user-info img {
                width: 35px;
                height: 35px;
            }

            .user-info h6 {
                font-size: 14px;
            }

            .post-content p {
                font-size: 14px;
            }

            .delete-btn {
                width: 100%;
                text-align: center;
            }

            .quick-access {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Delete Posts</h1>

        <div class="search-bar">
            <form method="GET">
                <input type="text" name="search" placeholder="Search posts..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit">Search</button>
            </form>
        </div>

        <div class="quick-access">
            <a href="?flagged=1">View Flagged Posts</a>
            <a href="?">View All Posts</a>
        </div>

        <?php
        if (isset($_GET['message'])) {
            echo '<p class="message success">' . htmlspecialchars(urldecode($_GET['message'])) . '</p>';
        }
        if (isset($_GET['error'])) {
            echo '<p class="message error">' . htmlspecialchars(urldecode($_GET['error'])) . '</p>';
        }

        $show_flagged = isset($_GET['flagged']) && $_GET['flagged'] == 1;
        if ($show_flagged) {
            $flagged_posts = $conn->query("
                SELECT p.* FROM posts p
                JOIN flagged_posts fp ON p.post_id = fp.post_id
                ORDER BY fp.flagged_at DESC
            ");
        }
        ?>

        <?php if ($result_posts->num_rows > 0): ?>
            <?php while ($post = $result_posts->fetch_assoc()): ?>
                <?php 
                $filtered = filterContent($post['post_content'], $bad_words, $conn);
                if ($filtered['has_bad_words']) {
                    $conn->query("INSERT IGNORE INTO flagged_posts (post_id) VALUES ({$post['post_id']})");
                }
                if ($show_flagged && !$filtered['has_bad_words']) continue;
                ?>
                <div class="post-card <?php echo $filtered['has_bad_words'] ? 'flagged' : ''; ?>">
                    <div class="post-header">
                        <div class="user-info">
                            <img src="/dashboard/<?php echo htmlspecialchars($post['profile_picture']); ?>" alt="avatar">
                            <h6><?php echo htmlspecialchars($post['username']); ?></h6>
                        </div>
                    </div>

                    <div class="post-content">
                        <p><?php echo nl2br($filtered['content']); ?></p>
                    </div>

                    <p class="post-time"><?php echo date('F j, Y, g:i a', strtotime($post['created_at'])); ?></p>

                    <?php if ($post['media_type'] !== 'none'): ?>
                        <div class="post-media">
                            <?php if ($post['media_type'] === 'image'): ?>
                                <img src="/dashboard/<?php echo htmlspecialchars($post['media_url']); ?>" alt="Post Image">
                            <?php elseif ($post['media_type'] === 'video'): ?>
                                <video controls>
                                    <source src="<?php echo htmlspecialchars($post['media_url']); ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <a href="delete_post.php?delete_post_id=<?php echo $post['post_id']; ?>" 
                       class="delete-btn" 
                       onclick="return confirm('Are you sure you want to delete this post? This will also delete all likes, comments, and replies and cannot be undone.');">
                        Delete Post
                    </a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="no-posts">No posts available to delete.</p>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
ob_end_flush();
?>