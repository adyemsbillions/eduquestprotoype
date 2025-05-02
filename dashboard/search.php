<?php
// Include the database connection
include("db_connection.php");

// Start session to handle logged-in user
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

$user_id = $_SESSION['user_id'];  // Logged-in user ID
$search_query = isset($_GET['query']) ? $_GET['query'] : ''; // Get the search query from GET

// Handle Like Request
if (isset($_POST['like_post'])) {
    $post_id = $_POST['post_id'];

    // Check if the user has already liked the post
    $sql_check_like = "SELECT COUNT(*) AS like_count FROM post_likes WHERE post_id = ? AND user_id = ?";
    $stmt_check_like = $conn->prepare($sql_check_like);
    $stmt_check_like->bind_param("ii", $post_id, $user_id);
    $stmt_check_like->execute();
    $result_check_like = $stmt_check_like->get_result();
    $like_exists = $result_check_like->fetch_assoc()['like_count'];

    // If the user hasn't liked the post yet, insert the like
    if ($like_exists == 0) {
        $sql_insert_like = "INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)";
        $stmt_insert_like = $conn->prepare($sql_insert_like);
        $stmt_insert_like->bind_param("ii", $post_id, $user_id);
        $stmt_insert_like->execute();
    }
    
    // Fetch the updated like count
    $sql_like_count = "SELECT COUNT(*) AS like_count FROM post_likes WHERE post_id = ?";
    $stmt_like_count = $conn->prepare($sql_like_count);
    $stmt_like_count->bind_param("i", $post_id);
    $stmt_like_count->execute();
    $result_like_count = $stmt_like_count->get_result();
    $like_count = $result_like_count->fetch_assoc()['like_count'];

    // Return the new like count in JSON format
    echo json_encode(['like_count' => $like_count]);
    exit(); // Finish the script to prevent further processing
}

// Fetch posts based on the search query
if (!empty($search_query)) {
    $sql = "SELECT p.*, u.username, u.profile_picture 
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.post_content LIKE ? OR u.username LIKE ?
            ORDER BY p.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $search_term = '%' . $search_query . '%'; // Search term with wildcards
    $stmt->bind_param("ss", $search_term, $search_term); // Bind both fields to the query
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Fetch all posts if no search query
    $sql = "SELECT p.*, u.username, u.profile_picture 
            FROM posts p
            JOIN users u ON p.user_id = u.id
            ORDER BY p.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
      body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    margin: 0;
    padding: 0;  /* Adjusted padding to remove any limit */
    min-height: 100vh;
}

.container {
    max-width: 1800px;  /* Increased from 1700px to 1800px */
    margin: 0 auto;
    padding: 0 20px;  /* Ensure it’s not constrained by padding */
}


        /* Enhanced Search Form */
        .search-form {
            margin: 30px 0;
            position: sticky;
            top: 20px;
            z-index: 10;
        }

     .search-container {
    position: relative;
    max-width: 1800px;  /* Increased from original max-width */
    margin: 0 auto;
    display: flex;
    background: white;
    border-radius: 25px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: all 0.3s ease;
}


        .search-container:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .search-container input {
            flex: 1;
            padding: 15px 20px;
            font-size: 16px;
            border: none;
            outline: none;
            background: transparent;
            color: #333;
        }

        .search-container button {
            padding: 15px 25px;
            background: linear-gradient(45deg, #6a1b9a, #8e24aa);
            color: white;
            border: none;
            border-radius: 0 25px 25px 0;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s ease;
        }

        .search-container button:hover {
            background: linear-gradient(45deg, #8e24aa, #ab47bc);
        }

        /* Post Card Styling */
        .post-card {
            background: white;
            margin: 20px 0;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .post-header img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
            border: 2px solid #6a1b9a;
        }

        .post-header h3 {
            margin: 0;
            font-size: 20px;
            color: #333;
        }

        .post-header p {
            margin: 5px 0 0;
            font-size: 14px;
            color: #666;
        }

        .post-content {
            font-size: 16px;
            line-height: 1.8;
            color: #444;
            margin-bottom: 20px;
        }

        .post-media img,
        .post-media video {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            margin: 15px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .post-actions {
            display: flex;
            align-items: center;
            gap: 20px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .like-button {
            background: linear-gradient(45deg, #6a1b9a, #8e24aa);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .like-button:hover {
            background: linear-gradient(45deg, #8e24aa, #ab47bc);
            transform: translateY(-2px);
        }

        .like-button.liked {
            background: #666;
        }

        .like-count {
            margin: 0;
            color: #666;
            font-size: 14px;
        }

        .comments-section {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .comments-section h4 {
            color: #333;
            font-size: 18px;
            margin-bottom: 15px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            .post-card {
                padding: 15px;
            }
            .search-container {
                margin: 0 15px;
            }
        }
    </style>
       <!-- Add this line to include Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
</head>
<body>
    <div class="container">
        <!-- Enhanced Search Form -->
        <div class="search-form">
            <form action="search.php" method="GET" class="search-container">
                <input type="text" name="query" placeholder="Search posts or users..." 
                       value="<?php echo htmlspecialchars($search_query); ?>" autocomplete="off">
                <button type="submit"><i class="fas fa-search"></i>  <!-- Font Awesome Search Icon --></button>
            </form>
        </div>

        <!-- Search Results Container -->
        <div id="search-results">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($post = $result->fetch_assoc()): ?>
                    <div class="post-card" id="post-<?php echo $post['post_id']; ?>">
                        <div class="post-header">
                            <img src="/dashboard/<?php echo htmlspecialchars($post['profile_picture']); ?>" alt="avatar">
                            <div>
                                <h3><?php echo htmlspecialchars($post['username']); ?></h3>
                                <p><?php echo date('F j, Y, g:i a', strtotime($post['created_at'])); ?></p>
                            </div>
                        </div>

                        <div class="post-content">
                            <p><?php echo nl2br(htmlspecialchars($post['post_content'])); ?></p>
                        </div>

                        <?php if ($post['media_type'] !== 'none'): ?>
                            <div class="post-media">
                                <?php if ($post['media_type'] === 'image'): ?>
                                    <img src="<?php echo htmlspecialchars($post['media_url']); ?>" alt="Post Image">
                                <?php elseif ($post['media_type'] === 'video'): ?>
                                    <video controls>
                                        <source src="<?php echo htmlspecialchars($post['media_url']); ?>" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php
                        // Fetch the like count for this post
                        $sql_like_count = "SELECT COUNT(*) AS like_count FROM post_likes WHERE post_id = ?";
                        $stmt_like_count = $conn->prepare($sql_like_count);
                        $stmt_like_count->bind_param("i", $post['post_id']);
                        $stmt_like_count->execute();
                        $result_like_count = $stmt_like_count->get_result();
                        $like_count = $result_like_count->fetch_assoc()['like_count'];
                        ?>

                        <div class="post-actions">
                            <button class="like-button" id="like-btn-<?php echo $post['post_id']; ?>" 
                                    data-post-id="<?php echo $post['post_id']; ?>">Like</button>
                            <p class="like-count" id="like-count-<?php echo $post['post_id']; ?>">
                                <?php echo $like_count; ?> Likes</p>
                        </div>

                        <div class="comments-section">
                            <h4>Comments:</h4>
                            <!-- Comments will be displayed here -->
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No posts found for your search query.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Handle Like Button Click
        document.querySelectorAll('.like-button').forEach(button => {
            button.addEventListener('click', function() {
                const postId = this.getAttribute('data-post-id');
                fetch('search.php', {
                    method: 'POST',
                    body: new URLSearchParams({
                        like_post: true,
                        post_id: postId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    // Update the like count dynamically
                    document.getElementById('like-count-' + postId).textContent = data.like_count + ' Likes';
                    document.getElementById('like-btn-' + postId).classList.add('liked');
                })
                .catch(error => console.error('Error:', error));
            });
        });
    </script>
</body>
</html>