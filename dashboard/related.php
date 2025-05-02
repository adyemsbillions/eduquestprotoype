<?php
// related.php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get the reel ID from the query parameter
$reel_id = isset($_GET['reel_id']) ? intval($_GET['reel_id']) : 0;

if ($reel_id <= 0) {
    header("Location: reels.php");
    exit;
}

// Fetch the current reel's details
$current_reel = [];
$stmt = $conn->prepare("SELECT title, description FROM reels WHERE id = ?");
$stmt->bind_param("i", $reel_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: reels.php");
    exit;
}

$current_reel = $result->fetch_assoc();
$stmt->close();

// Extract keywords from title and description
$keywords = [];
$title_words = preg_split('/\s+/', $current_reel['title']);
$description_words = preg_split('/\s+/', $current_reel['description']);

// Filter out short words and common words
$common_words = ['the', 'and', 'or', 'a', 'an', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'is', 'are', 'was', 'were', 'be', 'been', 'being'];

foreach ($title_words as $word) {
    $word = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $word)));
    if (strlen($word) > 3 && !in_array($word, $common_words)) {
        $keywords[] = $word;
    }
}

foreach ($description_words as $word) {
    $word = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $word)));
    if (strlen($word) > 3 && !in_array($word, $common_words) && !in_array($word, $keywords)) {
        $keywords[] = $word;
    }
}

// If no keywords found, use some default
if (empty($keywords)) {
    $keywords = ['fun', 'cool', 'awesome', 'video'];
}

// Build SQL query to find related reels
$query = "SELECT r.*, u.username, u.profile_picture, u.verified 
          FROM reels r 
          JOIN users u ON r.user_id = u.id 
          WHERE r.id != ? AND (";

$params = [$reel_id];
$param_types = "i";

foreach ($keywords as $index => $keyword) {
    if ($index > 0) {
        $query .= " OR ";
    }
    $query .= "r.title LIKE ? OR r.description LIKE ?";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $param_types .= "ss";
}

$query .= ") ORDER BY (";
foreach ($keywords as $index => $keyword) {
    if ($index > 0) {
        $query .= " + ";
    }
    $query .= "(CASE WHEN r.title LIKE ? THEN 2 ELSE 0 END)";
    $query .= " + (CASE WHEN r.description LIKE ? THEN 1 ELSE 0 END)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $param_types .= "ss";
}

$query .= ") DESC, r.likes DESC LIMIT 20";

// Prepare and execute the query
$stmt = $conn->prepare($query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$related_reels = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Function to format numbers
function formatNumber($num) {
    if ($num >= 1000000) {
        return round($num / 1000000, 1) . 'm';
    }
    if ($num >= 1000) {
        return round($num / 1000, 1) . 'k';
    }
    return $num;
}

// Function to format time
function timeAgo($datetime) {
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Related Reels</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    html, body {
      height: 100%;
      margin: 0;
      background-color: #000;
      color: white;
    }
    .reel-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 2px;
    }
    @media (min-width: 640px) {
      .reel-grid {
        grid-template-columns: repeat(3, 1fr);
      }
    }
    @media (min-width: 1024px) {
      .reel-grid {
        grid-template-columns: repeat(4, 1fr);
      }
    }
    .reel-item {
      position: relative;
      padding-bottom: 177.78%; /* 16:9 aspect ratio */
      overflow: hidden;
    }
    .reel-item video {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .reel-overlay {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      padding: 8px;
      background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
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
  <!-- Header -->
  <div class="sticky top-0 z-10 bg-black/80 backdrop-blur-sm p-4 border-b border-gray-800 flex items-center justify-between">
    <button onclick="window.history.back()" class="text-white">
      <i class="fas fa-arrow-left"></i>
    </button>
    <h1 class="text-lg font-bold">Related Reels</h1>
    <div class="w-6"></div> <!-- Spacer for balance -->
  </div>

  <!-- Related Reels Grid -->
  <div class="reel-grid">
    <?php if (empty($related_reels)): ?>
      <div class="col-span-full text-center py-10 text-gray-400">
        <p>No related reels found</p>
      </div>
    <?php else: ?>
      <?php foreach ($related_reels as $reel): ?>
        <a href="reels.php#reel-<?php echo $reel['id']; ?>" class="reel-item">
          <video loop muted>
            <source src="<?php echo htmlspecialchars($reel['video_url']); ?>" type="video/mp4">
          </video>
          <div class="reel-overlay">
            <div class="flex items-center gap-1">
              <span class="text-sm font-semibold truncate">@<?php echo htmlspecialchars($reel['username']); ?></span>
              <?php if ($reel['verified'] > 0): ?>
                <?php 
                $badgeColor = '';
                switch($reel['verified']) {
                    case 1: $badgeColor = 'bg-blue-500'; break;
                    case 2: $badgeColor = 'bg-yellow-500'; break;
                    case 3: $badgeColor = 'bg-black'; break;
                    case 4: $badgeColor = 'bg-pink-600'; break;
                    default: $badgeColor = 'bg-blue-500';
                }
                ?>
                <span class="verified-badge <?php echo $badgeColor; ?>">
                  <i class="fas fa-check text-white"></i>
                </span>
              <?php endif; ?>
            </div>
            <div class="flex items-center gap-2 text-xs mt-1">
              <span><i class="fas fa-heart mr-1"></i><?php echo formatNumber($reel['likes']); ?></span>
              <span><i class="fas fa-comment mr-1"></i><?php echo formatNumber($reel['comments']); ?></span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <script>
    $(document).ready(function() {
      // Play videos when they're visible
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          const video = entry.target.querySelector('video');
          if (entry.isIntersecting) {
            video.play().catch(e => console.log('Autoplay prevented:', e));
          } else {
            video.pause();
          }
        });
      }, { threshold: 0.5 });

      document.querySelectorAll('.reel-item').forEach(item => observer.observe(item));
    });
  </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>