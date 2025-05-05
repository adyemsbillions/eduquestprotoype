<?php
session_start();
include('db_connection.php');

if (!isset($_SESSION['user_id'])) {
  if (isset($_GET['reel'])) {
    $_SESSION['redirect_reel'] = $_GET['reel'];
  }
  header("Location: login.php");
  exit;
}

// Upload handler
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['reel_video'])) {
  $target_dir = "Uploads/";
  if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
  }

  $target_file = $target_dir . uniqid() . '_' . basename($_FILES["reel_video"]["name"]);
  $videoFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
  $allowed_types = ['mp4', 'mov', 'avi'];
  $max_size = 50 * 1024 * 1024; // 50MB

  if (in_array($videoFileType, $allowed_types)) {
    if ($_FILES["reel_video"]["size"] <= $max_size) {
      if (move_uploaded_file($_FILES["reel_video"]["tmp_name"], $target_file)) {
        $title = htmlspecialchars($_POST['title'] ?? '');
        $description = htmlspecialchars($_POST['description'] ?? '');
        $user_id = $_SESSION['user_id'];
        $tags = htmlspecialchars($_POST['tags'] ?? '');

        $sql = "INSERT INTO reels (user_id, video_url, title, description) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $user_id, $target_file, $title, $description);
        $stmt->execute();
        $reel_id = $stmt->insert_id;
        $stmt->close();

        // Insert tags
        if (!empty($tags)) {
          $tag_array = array_map('trim', explode(',', $tags));
          foreach ($tag_array as $tag) {
            if (!empty($tag)) {
              $stmt = $conn->prepare("INSERT INTO reel_tags (reel_id, tag) VALUES (?, ?)");
              $stmt->bind_param("is", $reel_id, $tag);
              $stmt->execute();
              $stmt->close();
            }
          }
        }

        header("Location: reels.php#reel-$reel_id");
        exit;
      }
    } else {
      $_SESSION['error'] = "File too large. Maximum size is 50MB.";
    }
  } else {
    $_SESSION['error'] = "Only MP4, MOV, and AVI files are allowed.";
  }
}

// Interaction handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $user_id = $_SESSION['user_id'];
  $reel_id = intval($_POST['reel_id'] ?? 0);
  $action = $_POST['action'];

  if ($action === 'like') {
    $check = $conn->prepare("SELECT id FROM reel_likes WHERE user_id = ? AND reel_id = ?");
    $check->bind_param("ii", $user_id, $reel_id);
    $check->execute();
    $result = $check->get_result();

    try {
      $conn->begin_transaction();

      if ($result->num_rows === 0) {
        // Add like
        $stmt = $conn->prepare("INSERT INTO reel_likes (user_id, reel_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $reel_id);
        $stmt->execute();
        $stmt->close();

        $update = $conn->prepare("UPDATE reels SET likes = likes + 1 WHERE id = ?");
        $update->bind_param("i", $reel_id);
        $update->execute();
        $update->close();
      } else {
        // Remove like
        $delete = $conn->prepare("DELETE FROM reel_likes WHERE user_id = ? AND reel_id = ?");
        $delete->bind_param("ii", $user_id, $reel_id);
        $delete->execute();
        $delete->close();

        $update = $conn->prepare("UPDATE reels SET likes = likes - 1 WHERE id = ?");
        $update->bind_param("i", $reel_id);
        $update->execute();
        $update->close();
      }

      $conn->commit();

      // Fetch updated like count
      $count_stmt = $conn->prepare("SELECT likes FROM reels WHERE id = ?");
      $count_stmt->bind_param("i", $reel_id);
      $count_stmt->execute();
      $count_result = $count_stmt->get_result()->fetch_assoc();
      $count_stmt->close();

      if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => true, 'likes' => $count_result['likes'], 'liked' => $result->num_rows === 0]);
        exit;
      }
    } catch (Exception $e) {
      $conn->rollback();
      if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => false, 'error' => 'Database error']);
        exit;
      }
      $_SESSION['error'] = "Failed to process like: " . $e->getMessage();
    }
    $check->close();

    header("Location: reels.php#reel-$reel_id");
    exit;
  }

  if ($action === 'comment' && !empty($_POST['comment_text'])) {
    $text = htmlspecialchars(trim($_POST['comment_text']));
    $stmt = $conn->prepare("INSERT INTO rcomments (user_id, reel_id, comment_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $reel_id, $text);
    $stmt->execute();
    $comment_id = $stmt->insert_id;
    $stmt->close();
    $conn->query("UPDATE reels SET comments = comments + 1 WHERE id = $reel_id");

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
      $comment = $conn->query("
                SELECT c.*, u.username, u.profile_picture, u.verified 
                FROM rcomments c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.id = $comment_id
            ")->fetch_assoc();

      echo json_encode([
        'success' => true,
        'comment' => [
          'id' => $comment['id'],
          'username' => $comment['username'],
          'profile_picture' => $comment['profile_picture'],
          'verified' => $comment['verified'],
          'text' => $comment['comment_text'],
          'time' => 'Just now'
        ]
      ]);
      exit;
    }
  }

  if ($action === 'share') {
    $stmt = $conn->prepare("INSERT INTO reel_shares (user_id, reel_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $reel_id);
    $stmt->execute();
    $stmt->close();
    $conn->query("UPDATE reels SET shares = shares + 1 WHERE id = $reel_id");

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
      $count = $conn->query("SELECT shares FROM reels WHERE id = $reel_id")->fetch_assoc()['shares'];
      echo json_encode(['success' => true, 'shares' => $count]);
      exit;
    }
  }

  header("Location: reels.php#reel-$reel_id");
  exit;
}

// Fetch all reels with recommendation algorithm
$user_id = $_SESSION['user_id'];

// Get user's interests and followed users
$user_interests = [];
$followed_users = [];
$interest_stmt = $conn->prepare("SELECT interest FROM user_interests WHERE user_id = ?");
$interest_stmt->bind_param("i", $user_id);
$interest_stmt->execute();
$result = $interest_stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $user_interests[] = $row['interest'];
}
$interest_stmt->close();

$follow_stmt = $conn->prepare("SELECT followed_id FROM follows WHERE follower_id = ?");
$follow_stmt->bind_param("i", $user_id);
$follow_stmt->execute();
$result = $follow_stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $followed_users[] = $row['followed_id'];
}
$follow_stmt->close();

// Fetch all reels with engagement data
$sql = "
    SELECT r.*, u.username, u.profile_picture, u.verified,
           (r.likes * 0.4 + r.comments * 0.3 + r.shares * 0.2) AS engagement_score,
           TIMESTAMPDIFF(HOUR, r.created_at, NOW()) AS hours_old
    FROM reels r 
    JOIN users u ON r.user_id = u.id
";
$result = $conn->query($sql);
$reels = [];

while ($row = $result->fetch_assoc()) {
  // Get reel tags
  $tag_stmt = $conn->prepare("SELECT tag FROM reel_tags WHERE reel_id = ?");
  $tag_stmt->bind_param("i", $row['id']);
  $tag_stmt->execute();
  $tags_result = $tag_stmt->get_result();
  $reel_tags = [];
  while ($tag_row = $tags_result->fetch_assoc()) {
    $reel_tags[] = $tag_row['tag'];
  }
  $tag_stmt->close();

  // Calculate relevance score
  $relevance_score = 0;
  foreach ($user_interests as $interest) {
    if (in_array($interest, $reel_tags)) {
      $relevance_score += 0.3; // Boost for matching interests
    }
  }
  if (in_array($row['user_id'], $followed_users)) {
    $relevance_score += 0.2; // Boost for followed users
  }

  // Calculate recency decay (exponential decay over 7 days)
  $recency_score = exp(-$row['hours_old'] / (7 * 24));

  // Combine scores
  $total_score = ($row['engagement_score'] * 0.5) + ($relevance_score * 0.3) + ($recency_score * 0.2);
  $row['total_score'] = $total_score;

  // Get 5 most recent comments
  $comment_stmt = $conn->prepare("
        SELECT c.*, u.username, u.profile_picture, u.verified 
        FROM rcomments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.reel_id = ? 
        ORDER BY c.created_at DESC 
        LIMIT 5
    ");
  $comment_stmt->bind_param("i", $row['id']);
  $comment_stmt->execute();
  $row['comments_array'] = $comment_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

  // Check if current user liked this reel
  $like_stmt = $conn->prepare("SELECT COUNT(*) FROM reel_likes WHERE reel_id = ? AND user_id = ?");
  $like_stmt->bind_param("ii", $row['id'], $user_id);
  $like_stmt->execute();
  $row['user_liked'] = $like_stmt->get_result()->fetch_row()[0] > 0;

  $reels[] = $row;
  $comment_stmt->close();
  $like_stmt->close();
}
$result->free();

// Sort reels by total score
usort($reels, function ($a, $b) {
  return $b['total_score'] <=> $a['total_score'];
});

// Fallback: If no reels, fetch all reels sorted by engagement and recency
if (empty($reels)) {
  $sql = "
        SELECT r.*, u.username, u.profile_picture, u.verified,
               (r.likes * 0.4 + r.comments * 0.3 + r.shares * 0.2) AS engagement_score,
               TIMESTAMPDIFF(HOUR, r.created_at, NOW()) AS hours_old
        FROM reels r 
        JOIN users u ON r.user_id = u.id 
        ORDER BY (r.likes * 0.4 + r.comments * 0.3 + r.shares * 0.2) DESC, r.created_at DESC
    ";
  $result = $conn->query($sql);
  while ($row = $result->fetch_assoc()) {
    $comment_stmt = $conn->prepare("
            SELECT c.*, u.username, u.profile_picture, u.verified 
            FROM rcomments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.reel_id = ? 
            ORDER BY c.created_at DESC 
            LIMIT 5
        ");
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
}

function formatNumber($num)
{
  if ($num >= 1000000) {
    return round($num / 1000000, 1) . 'm';
  }
  if ($num >= 1000) {
    return round($num / 1000, 1) . 'k';
  }
  return $num;
}

function timeAgo($datetime)
{
  $now = new DateTime;
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
    <title>Reels</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: '#6a0dad',
                    'primary-dark': '#4b0082',
                }
            }
        }
    }
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
    html,
    body {
        height: 100%;
        margin: 0;
        overflow: hidden;
        touch-action: pan-y;
        background-color: #000;
    }

    .reel-feed {
        -webkit-overflow-scrolling: touch;
        scroll-snap-type: y mandatory;
        height: 100vh;
        overflow-y: scroll;
    }

    .reel-item {
        scroll-snap-align: start;
        height: 100vh;
        position: relative;
    }

    video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        background-color: #000;
    }

    .comments-box {
        position: fixed;
        width: 100%;
        background: rgba(22, 24, 35, 0.95);
        backdrop-filter: blur(20px);
        transition: transform 0.3s ease-in-out;
        transform: translateY(100%);
        bottom: 0;
        height: 65vh;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        z-index: 50;
    }

    .comments-box.active {
        transform: translateY(0);
    }

    .upload-form {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.9);
        z-index: 40;
        transition: opacity 0.3s ease, visibility 0.3s ease;
        opacity: 0;
        visibility: hidden;
    }

    .upload-form.active {
        opacity: 1;
        visibility: visible;
    }

    .like-animation {
        animation: likeHeart 0.5s ease-in-out;
    }

    @keyframes likeHeart {
        0% {
            transform: scale(1);
        }

        25% {
            transform: scale(1.2);
        }

        50% {
            transform: scale(0.95);
        }

        75% {
            transform: scale(1.1);
        }

        100% {
            transform: scale(1);
        }
    }

    .double-tap-heart {
        position: absolute;
        font-size: 80px;
        color: white;
        opacity: 0;
        pointer-events: none;
        animation: fadeHeart 1s linear;
    }

    @keyframes fadeHeart {
        0% {
            transform: scale(0);
            opacity: 0;
        }

        15% {
            transform: scale(1.1);
            opacity: 0.9;
        }

        30% {
            transform: scale(0.95);
        }

        45% {
            transform: scale(1);
        }

        100% {
            transform: scale(1.5);
            opacity: 0;
        }
    }

    .comment-input {
        background: rgba(22, 24, 35, 0.06);
        border: 1px solid rgba(22, 24, 35, 0.12);
        border-radius: 8px;
    }

    .comment-input:focus {
        border-color: rgba(22, 24, 35, 0.2);
    }

    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }

    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    .progress-bar {
        position: absolute;
        top: 0;
        left: 0;
        height: 2px;
        background-color: white;
        z-index: 10;
        transition: width 0.1s linear;
    }

    .verified-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 16px;
        height: 16px;
        border-radius: 50%;
    }

    .verified-badge i {
        font-size: 8px;
    }
    </style>
</head>

<body class="bg-black text-white">
    <!-- Progress bar for reel scrolling -->
    <div class="progress-bar" id="progress-bar"></div>

    <!-- Upload Form -->
    <div id="upload-form" class="upload-form">
        <div class="relative w-full h-full flex items-center justify-center p-4">
            <button id="close-upload"
                class="absolute top-4 right-4 text-2xl text-gray-300 hover:text-white transition-colors">
                <i class="fas fa-times"></i>
            </button>
            <form action="" method="post" enctype="multipart/form-data" class="w-full max-w-md space-y-4">
                <h2 class="text-xl font-bold text-white text-center mb-6">Upload New Reel</h2>

                <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-500/20 text-red-300 p-3 rounded-lg text-sm">
                    <?php echo $_SESSION['error'];
            unset($_SESSION['error']); ?>
                </div>
                <?php endif; ?>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-300">Title</label>
                    <input type="text" name="title" placeholder="Add a title" required
                        class="w-full p-3 bg-gray-800 rounded-lg focus:ring-2 focus:ring-primary outline-none text-white placeholder-gray-400">
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-300">Description</label>
                    <textarea name="description" placeholder="Tell everyone about your reel" rows="3"
                        class="w-full p-3 bg-gray-800 rounded-lg focus:ring-2 focus:ring-primary outline-none text-white placeholder-gray-400"></textarea>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-300">Tags (comma-separated)</label>
                    <input type="text" name="tags" placeholder="e.g., comedy, dance, travel"
                        class="w-full p-3 bg-gray-800 rounded-lg focus:ring-2 focus:ring-primary outline-none text-white placeholder-gray-400">
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-300">Video</label>
                    <div class="relative border-2 border-dashed border-gray-700 rounded-lg p-6 text-center">
                        <input type="file" name="reel_video" accept="video/*" required
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <i class="fas fa-video text-3xl text-gray-400 mb-2"></i>
                        <p class="text-sm text-gray-300">Click to upload video</p>
                        <p class="text-xs text-gray-500 mt-1">MP4, MOV or AVI. Max 50MB.</p>
                    </div>
                </div>
                <button type="submit"
                    class="w-full p-3 bg-gradient-to-r from-primary to-primary-dark rounded-lg hover:from-primary-dark hover:to-primary transition-colors font-medium">
                    Upload Reel
                </button>
            </form>
        </div>
    </div>

    <!-- Reel Feed -->
    <div class="reel-feed" id="reel-feed">
        <?php foreach ($reels as $reel): ?>
        <div class="reel-item" id="reel-<?php echo $reel['id']; ?>">
            <!-- Video Container with Double Tap Area -->
            <div class="video-container w-full h-full relative">
                <video loop playsinline class="w-full h-full">
                    <source src="<?php echo htmlspecialchars($reel['video_url']); ?>" type="video/mp4">
                </video>
                <div class="absolute inset-0 double-tap-area" data-reel-id="<?php echo $reel['id']; ?>"></div>
            </div>

            <!-- Overlay Content -->
            <div
                class="absolute bottom-0 left-0 w-full p-4 pb-20 bg-gradient-to-t from-black/80 to-transparent pointer-events-none">
                <!-- Reel Header -->
                <div class="reel-header flex items-center gap-3 mb-3">
                    <a href="view_profile.php?user_id=<?php echo htmlspecialchars($reel['user_id']); ?>"
                        class="flex-shrink-0">
                        <img src="<?php echo htmlspecialchars($reel['profile_picture']); ?>" alt="Profile"
                            class="w-10 h-10 rounded-full object-cover border-2 border-white/80">
                    </a>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1">
                            <a href="view_profile.php?user_id=<?php echo htmlspecialchars($reel['user_id']); ?>"
                                class="text-sm font-bold text-white truncate hover:underline">
                                @<?php echo htmlspecialchars($reel['username']); ?>
                            </a>

                            <?php if (isset($reel['verified']) && $reel['verified'] > 0): ?>
                            <?php
                  $badgeColor = '';
                  switch ($reel['verified']) {
                    case 1:
                      $badgeColor = 'bg-blue-500';
                      break;
                    case 2:
                      $badgeColor = 'bg-yellow-500';
                      break;
                    case 3:
                      $badgeColor = 'bg-black';
                      break;
                    case 4:
                      $badgeColor = 'bg-pink-600';
                      break;
                    default:
                      $badgeColor = 'bg-blue-500';
                  }
                  ?>
                            <span class="verified-badge <?php echo $badgeColor; ?>">
                                <i class="fas fa-check text-white"></i>
                            </span>
                            <?php endif; ?>

                            <span class="text-xs text-gray-300 ml-1">
                                • <?php echo timeAgo($reel['created_at']); ?>
                            </span>
                        </div>

                        <?php if (!empty($reel['title'])): ?>
                        <p class="text-xs text-white mt-1 line-clamp-1">
                            <?php echo htmlspecialchars($reel['title']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>

                <p class="text-sm font-medium text-white line-clamp-2">
                    <?php echo htmlspecialchars($reel['description']); ?></p>
            </div>

            <!-- Side Panel -->
            <div class="absolute right-2 bottom-24 flex flex-col gap-2 items-center">
                <!-- Upload Button -->
                <button class="upload-toggle relative">
                    <div
                        class="w-10 h-10 flex items-center justify-center rounded-full bg-gradient-to-r from-primary to-primary-dark backdrop-blur-sm">
                        <i class="fas fa-plus text-white"></i>
                    </div>
                </button>

                <!-- Like Button -->
                <form method="POST" class="flex flex-col items-center like-form"
                    data-reel-id="<?php echo $reel['id']; ?>">
                    <input type="hidden" name="action" value="like">
                    <input type="hidden" name="reel_id" value="<?php echo $reel['id']; ?>">
                    <button type="submit" class="relative">
                        <div
                            class="w-10 h-10 flex items-center justify-center rounded-full bg-black/30 backdrop-blur-sm">
                            <i
                                class="<?php echo $reel['user_liked'] ? 'fas fa-heart text-red-500' : 'far fa-heart text-white'; ?> text-2xl"></i>
                        </div>
                        <span
                            class="text-xs font-semibold text-white mt-1 like-count"><?php echo formatNumber($reel['likes']); ?></span>
                    </button>
                </form>

                <!-- Comment Button -->
                <button class="toggle-comments relative" data-reel-id="<?php echo $reel['id']; ?>">
                    <div class="w-10 h-10 flex items-center justify-center rounded-full bg-black/30 backdrop-blur-sm">
                        <i class="fas fa-comment text-2xl text-white"></i>
                    </div>
                    <span
                        class="text-xs font-semibold text-white mt-1"><?php echo formatNumber($reel['comments']); ?></span>
                </button>

                <!-- Share Button -->
                <form method="POST" class="flex flex-col items-center share-form"
                    data-reel-id="<?php echo $reel['id']; ?>">
                    <input type="hidden" name="action" value="share">
                    <input type="hidden" name="reel_id" value="<?php echo $reel['id']; ?>">
                    <button type="submit" class="relative">
                        <div
                            class="w-10 h-10 flex items-center justify-center rounded-full bg-black/30 backdrop-blur-sm">
                            <i class="fas fa-share text-2xl text-white"></i>
                        </div>
                        <span
                            class="text-xs font-semibold text-white mt-1 share-count"><?php echo formatNumber($reel['shares']); ?></span>
                    </button>
                </form>

                <!-- Related Reels Link -->
                <a href="related.php?reel_id=<?php echo $reel['id']; ?>"
                    class="w-10 h-10 flex items-center justify-center rounded-full bg-black/30 backdrop-blur-sm">
                    <i class="fas fa-layer-group text-xl text-white"></i>
                </a>
            </div>

            <!-- Comments Box -->
            <div class="comments-box" data-reel-id="<?php echo $reel['id']; ?>">
                <div class="h-full flex flex-col">
                    <!-- Header -->
                    <div class="px-4 py-3 border-b border-gray-700 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-white"><?php echo formatNumber($reel['comments']); ?> comments
                        </h3>
                        <button class="close-comments text-gray-300 hover:text-white transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Comments List -->
                    <div class="comments-list flex-1 overflow-y-auto scrollbar-hide px-4 py-2">
                        <?php if (empty($reel['comments_array'])): ?>
                        <div class="text-center py-10 text-gray-400">
                            <p>No comments yet</p>
                            <p class="text-sm mt-1">Be the first to comment</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($reel['comments_array'] as $comment): ?>
                        <div class="comment flex gap-3 py-3">
                            <img src="<?php echo htmlspecialchars($comment['profile_picture']); ?>"
                                class="w-8 h-8 rounded-full flex-shrink-0">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1">
                                    <strong
                                        class="text-sm font-semibold text-white">@<?php echo htmlspecialchars($comment['username']); ?></strong>
                                    <?php if ($comment['verified'] > 0): ?>
                                    <?php
                          $badgeColor = '';
                          switch ($comment['verified']) {
                            case 1:
                              $badgeColor = 'bg-blue-500';
                              break;
                            case 2:
                              $badgeColor = 'bg-yellow-500';
                              break;
                            case 3:
                              $badgeColor = 'bg-black';
                              break;
                            case 4:
                              $badgeColor = 'bg-pink-600';
                              break;
                            default:
                              $badgeColor = 'bg-blue-500';
                          }
                          ?>
                                    <span class="verified-badge <?php echo $badgeColor; ?>">
                                        <i class="fas fa-check text-white"></i>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-sm text-gray-100">
                                    <?php echo htmlspecialchars($comment['comment_text']); ?></p>
                                <div class="flex items-center gap-4 mt-1">
                                    <span
                                        class="text-xs text-gray-400"><?php echo timeAgo($comment['created_at']); ?></span>
                                    <button class="text-xs text-gray-400 hover:text-white">Reply</button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Comment Input -->
                    <form method="POST" class="comment-form px-4 py-3 border-t border-gray-700 flex gap-2"
                        data-reel-id="<?php echo $reel['id']; ?>">
                        <input type="hidden" name="action" value="comment">
                        <input type="hidden" name="reel_id" value="<?php echo $reel['id']; ?>">
                        <div class="flex-1 relative">
                            <textarea name="comment_text" placeholder="Add a comment..."
                                class="w-full p-3 pr-10 bg-gray-800 rounded-lg focus:ring-2 focus:ring-primary outline-none text-white text-sm resize-none comment-input"
                                rows="1"></textarea>
                            <button type="submit"
                                class="absolute right-2 bottom-2 text-primary hover:text-primary-dark transition-colors">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
    // Helper functions
    function formatNumber(num) {
        num = parseInt(num) || 0;
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'm';
        }
        if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'k';
        }
        return num.toString();
    }

    function timeAgo(dateString) {
        const now = new Date();
        const date = new Date(dateString);
        const seconds = Math.floor((now - date) / 1000);

        const intervals = {
            year: 31536000,
            month: 2592000,
            week: 604800,
            day: 86400,
            hour: 3600,
            minute: 60
        };

        if (seconds < 60) return 'Just now';
        if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
        if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
        if (seconds < 604800) return Math.floor(seconds / 86400) + 'd ago';
        if (seconds < 2592000) return Math.floor(seconds / 604800) + 'w ago';
        if (seconds < 31536000) return Math.floor(seconds / 2592000) + 'mo ago';
        return Math.floor(seconds / 31536000) + 'y ago';
    }

    // Document ready
    $(document).ready(function() {
        // Video playback control with progress tracking
        const reelFeed = document.getElementById('reel-feed');
        const videos = document.querySelectorAll('video');
        const progressBar = document.getElementById('progress-bar');

        // Set progress bar width based on scroll position
        function updateProgressBar() {
            const scrollTop = reelFeed.scrollTop;
            const scrollHeight = reelFeed.scrollHeight - reelFeed.clientHeight;
            const scrollProgress = (scrollTop / scrollHeight) * 100;
            progressBar.style.width = `${scrollProgress}%`;
        }

        reelFeed.addEventListener('scroll', updateProgressBar);

        // Play video when in view
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                const video = entry.target.querySelector('video');
                if (entry.isIntersecting) {
                    videos.forEach(v => {
                        if (v !== video) {
                            v.pause();
                            v.currentTime = 0;
                        }
                    });
                    video.play().catch(e => console.log('Autoplay prevented:', e));
                } else {
                    video.pause();
                }
            });
        }, {
            threshold: 0.8
        });

        document.querySelectorAll('.reel-item').forEach(item => observer.observe(item));

        // Double tap to like
        const doubleTapAreas = document.querySelectorAll('.double-tap-area');
        doubleTapAreas.forEach(area => {
            let lastTap = 0;
            area.addEventListener('click', (e) => {
                const currentTime = new Date().getTime();
                const tapLength = currentTime - lastTap;

                if (tapLength < 300 && tapLength > 0) {
                    // Double tap detected
                    const reelId = area.getAttribute('data-reel-id');
                    const form = document.querySelector(`.like-form[data-reel-id="${reelId}"]`);
                    const likeButton = form.querySelector('i');

                    // Show heart animation
                    const heart = document.createElement('div');
                    heart.className = 'double-tap-heart';
                    heart.innerHTML = '<i class="fas fa-heart"></i>';
                    heart.style.left = `${e.clientX - 40}px`;
                    heart.style.top = `${e.clientY - 40}px`;
                    document.body.appendChild(heart);

                    // Trigger like if not already liked
                    if (!likeButton.classList.contains('fas') || !likeButton.classList.contains(
                            'text-red-500')) {
                        $(form).trigger('submit');
                    }

                    setTimeout(() => {
                        heart.remove();
                    }, 1000);
                }
                lastTap = currentTime;
            });
        });

        // Upload form toggle
        $('.upload-toggle').on('click', function() {
            $('#upload-form').addClass('active');
        });

        $('#close-upload').on('click', function() {
            $('#upload-form').removeClass('active');
        });

        // Comments toggle
        $('.toggle-comments').on('click', function() {
            const reelId = $(this).data('reel-id');
            $(`.comments-box[data-reel-id="${reelId}"]`).addClass('active');
            $('body').css('overflow', 'hidden');
        });

        $('.close-comments').on('click', function() {
            $(this).closest('.comments-box').removeClass('active');
            $('body').css('overflow', '');
        });

        // Auto-resize comment textarea
        $('textarea[name="comment_text"]').on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // AJAX for likes with animation
        $('.like-form').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const reelId = form.data('reel-id');
            const likeButton = form.find('i');
            const likeCount = form.find('.like-count');

            $.ajax({
                url: '',
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Toggle heart icon and color
                        if (response.liked) {
                            likeButton.removeClass('far fa-heart text-white').addClass(
                                'fas fa-heart text-red-500');
                        } else {
                            likeButton.removeClass('fas fa-heart text-red-500').addClass(
                                'far fa-heart text-white');
                        }
                        likeButton.addClass('like-animation');
                        setTimeout(() => likeButton.removeClass('like-animation'), 500);
                        likeCount.text(formatNumber(response.likes));
                    } else {
                        console.error('Like failed:', response.error);
                        alert('Failed to process like: ' + response.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error);
                    alert('Failed to process like. Please try again.');
                }
            });
        });

        // AJAX for comments
        $('.comment-form').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const reelId = form.data('reel-id');
            const commentText = form.find('textarea').val().trim();

            if (!commentText) return;

            const submitButton = form.find('button[type="submit"]');
            const commentsList = form.closest('.comments-box').find('.comments-list');
            const noCommentsMsg = commentsList.find('.text-center');
            const commentCountEl = $(`.toggle-comments[data-reel-id="${reelId}"] span`);
            const commentsHeader = form.closest('.comments-box').find('h3');

            submitButton.html('<i class="fas fa-spinner fa-spin"></i>');

            $.ajax({
                url: '',
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const comment = response.comment;

                        const newComment = `
                <div class="comment flex gap-3 py-3">
                  <img src="${comment.profile_picture}" 
                       class="w-8 h-8 rounded-full flex-shrink-0">
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1">
                      <strong class="text-sm font-semibold text-white">@${comment.username}</strong>
                      ${comment.verified > 0 ? `
                        <span class="verified-badge ${comment.verified === 2 ? 'bg-yellow-500' : 
                          comment.verified === 3 ? 'bg-black' : 
                          comment.verified === 4 ? 'bg-pink-600' : 'bg-blue-500'}">
                          <i class="fas fa-check text-white"></i>
                        </span>
                      ` : ''}
                    </div>
                    <p class="text-sm text-gray-100">${comment.text}</p>
                    <div class="flex items-center gap-4 mt-1">
                      <span class="text-xs text-gray-400">${comment.time}</span>
                      <button class="text-xs text-gray-400 hover:text-white">Reply</button>
                    </div>
                  </div>
                </div>`;

                        if (noCommentsMsg.length) {
                            noCommentsMsg.replaceWith(newComment);
                        } else {
                            commentsList.prepend(newComment);
                        }

                        // Update comment count
                        const currentCount = parseInt(commentCountEl.text().replace(
                            /[^0-9]/g, '')) || 0;
                        const newCount = currentCount + 1;
                        commentCountEl.text(formatNumber(newCount));
                        commentsHeader.text(`${formatNumber(newCount)} comments`);

                        // Clear the input
                        form.find('textarea').val('').height('auto');
                    }
                },
                error: function() {
                    alert('Failed to post comment. Please try again.');
                },
                complete: function() {
                    submitButton.html('<i class="fas fa-paper-plane"></i>');
                }
            });
        });

        // AJAX for shares
        $('.share-form').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const reelId = form.data('reel-id');
            const shareCount = form.find('.share-count');

            $.ajax({
                url: '',
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        shareCount.text(formatNumber(response.shares));
                        alert('Share this reel with your friends!');
                    }
                },
                error: function() {
                    alert('Failed to share. Please try again.');
                }
            });
        });
    });
    </script>
</body>

</html>
<?php
// Close database connection
$conn->close();
?>