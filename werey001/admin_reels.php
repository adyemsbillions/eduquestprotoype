<?php
// admin_reels.php
session_start();

// Database connection
$conn = new mysqli("localhost", "unimaid9_unimaidresources", "#adyems123AD", "unimaid9_unimaidresources");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Handle reel deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_reel'])) {
    $reel_id = intval($_POST['reel_id']);
    
    // Delete associated comments and likes first (due to foreign key constraints)
    $conn->query("DELETE FROM rcomments WHERE reel_id = $reel_id");
    $conn->query("DELETE FROM reel_likes WHERE reel_id = $reel_id");
    
    // Get video file path to delete from server
    $stmt = $conn->prepare("SELECT video_url FROM reels WHERE id = ?");
    $stmt->bind_param("i", $reel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $video_path = $row['video_url'];
        if (file_exists($video_path)) {
            unlink($video_path); // Delete the video file
        }
    }
    $stmt->close();
    
    // Delete the reel
    $stmt = $conn->prepare("DELETE FROM reels WHERE id = ?");
    $stmt->bind_param("i", $reel_id);
    if ($stmt->execute()) {
        $delete_message = "Reel deleted successfully.";
    } else {
        $delete_message = "Error deleting reel: " . $conn->error;
    }
    $stmt->close();
}

// Fetch all reels
$sql = "SELECT r.*, u.username 
        FROM reels r 
        JOIN users u ON r.user_id = u.id 
        ORDER BY r.created_at DESC";
$result = $conn->query($sql);
if ($result === false) {
    die("Query failed: " . $conn->error);
}

$reels = $result->fetch_all(MYSQLI_ASSOC);
$result->free();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Reels</title>
    <style>
        :root {
            --primary-color: #6d28d9;
            --secondary-color: #ede9fe;
            --text-color: #1f2937;
            --border-color: #d1d5db;
            --danger-color: #dc2626;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f3f4f6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        h1 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 20px;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }

        .message.success {
            background-color: #d1fae5;
            color: #065f46;
        }

        .message.error {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .reel-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .reel-item {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .reel-item video {
            width: 100%;
            max-height: 400px;
            border-radius: 4px;
        }

        .reel-info {
            color: var(--text-color);
        }

        .reel-info strong {
            color: var(--primary-color);
        }

        .delete-form {
            margin-top: 10px;
        }

        .delete-btn {
            background-color: var(--danger-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            position: relative;
        }

        .delete-btn:hover {
            background-color: #b91c1c;
        }

        .loader {
            display: none;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--danger-color);
            border-radius: 50%;
            width: 16px;
            height: 16px;
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

        .delete-btn.loading .loader {
            display: block;
        }

        .delete-btn.loading span {
            visibility: hidden;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Reels</h1>
        
        <?php if (isset($delete_message)): ?>
            <div class="message <?php echo strpos($delete_message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <?php echo $delete_message; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($reels)): ?>
            <p>No reels found.</p>
        <?php else: ?>
            <div class="reel-list">
                <?php foreach ($reels as $reel): ?>
                    <div class="reel-item">
                        <div class="reel-info">
                            <strong><?php echo htmlspecialchars($reel['username']); ?></strong>
                            <span> • <?php echo date('M d, Y', strtotime($reel['created_at'])); ?></span>
                        </div>
                        <h3><?php echo htmlspecialchars($reel['title']); ?></h3>
                        <video controls>
                            <source src="/dashboard/<?php echo htmlspecialchars($reel['video_url']); ?>" type="video/mp4">
                        </video>
                        <p><?php echo htmlspecialchars($reel['description']); ?></p>
                        <p>Likes: <?php echo $reel['likes']; ?> | Views: <?php echo $reel['views']; ?> | 
                           Comments: <?php echo $reel['comments']; ?> | Shares: <?php echo $reel['shares']; ?></p>
                        <form class="delete-form" method="post" onsubmit="return confirm('Are you sure you want to delete this reel?');">
                            <input type="hidden" name="reel_id" value="<?php echo $reel['id']; ?>">
                            <button type="submit" name="delete_reel" class="delete-btn">
                                <span>Delete</span><span class="loader"></span>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.delete-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const button = this.querySelector('.delete-btn');
                    button.classList.add('loading');
                });
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>