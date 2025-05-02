     <div class="share-post d-flex gap-3 gap-sm-5 p-3 p-sm-5">
                            <!--dont-->
    <div class="profile-box">
        <img class="avatar-img max-un" src="/dashboard/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="avatar" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;">
   
   
   
    </div>
    <form action="create_post.php" method="POST" class="w-100 position-relative" enctype="multipart/form-data">
        <textarea name="post_content" cols="10" rows="2" placeholder="Write a post to unimaid resources..." class="form-control"></textarea>
        
        <!-- Image/Video Preview Area -->
        <div class="image-preview mt-2" id="imagePreview" style="display: none;">
            <img src="" id="previewImg" alt="Selected Image Preview" class="img-fluid rounded" style="max-height: 200px;">
        </div>
        <div class="video-preview mt-2" id="videoPreview" style="display: none;">
            <video id="previewVideo" class="img-fluid rounded" controls style="max-height: 200px;"></video>
        </div>
        
       
        
        <ul class="d-flex justify-content-between flex-wrap mt-3 gap-3">
            <li class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-primary" style="background-color: purple; ">Post</button>
            </li>
            <li class="d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#photoVideoMod">
                <label for="fileUpload" class="cursor-pointer">
                    <img src="images/vgallery.png" class="max-un" alt="icon">
                    <span>Upload </span>
                </label>
                <input type="file" name="media" id="fileUpload" accept="image/*,video/*" style="display: none;" onchange="previewFile()">
            </li>
            <!-- <li class="d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#activityMod">
                <img src="images/emoji-laughing.png" class="max-un" alt="icon">
                <span>Activity</span>
            </li> -->
        </ul>
    </form>
</div>

<script>
// Function to preview the image or video after selection
function previewFile() {
    const file = document.getElementById('fileUpload').files[0];
    const imagePreview = document.getElementById('imagePreview');
    const videoPreview = document.getElementById('videoPreview');
    const previewImg = document.getElementById('previewImg');
    const previewVideo = document.getElementById('previewVideo');

    // Reset previews
    imagePreview.style.display = 'none';
    videoPreview.style.display = 'none';
    previewImg.src = '';
    previewVideo.src = '';

    const reader = new FileReader();

    reader.onload = function() {
        if (file) {
            if (file.type.startsWith('image/')) {
                // If it's an image, show image preview
                imagePreview.style.display = 'block';
                previewImg.src = reader.result;
            } else if (file.type.startsWith('video/')) {
                // If it's a video, show video preview
                videoPreview.style.display = 'block';
                previewVideo.src = reader.result;
            }
        }
    };

    if (file) {
        reader.readAsDataURL(file); // Read the selected file
    }
}
</script>
<!--post here-->

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

// Handle liking a post via AJAX
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

    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['like_count' => $like_count, 'status' => $result_check->num_rows == 0 ? 'liked' : 'already_liked']);
    exit();
}

// Handle comment posting via AJAX
if (isset($_POST['comment_text']) && isset($_POST['post_id'])) {
    $post_id = $_POST['post_id'];
    $comment_text = $_POST['comment_text'];
    
    $sql_comment = "INSERT INTO comments (post_id, user_id, comment_text) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql_comment);
    $stmt->bind_param("iis", $post_id, $user_id, $comment_text);
    $stmt->execute();
    
    // Get updated comment count
    $sql_count = "SELECT COUNT(*) AS comment_count FROM comments WHERE post_id = ? AND user_id != 1";
    $stmt = $conn->prepare($sql_count);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result_count = $stmt->get_result();
    $comment_count = $result_count->fetch_assoc()['comment_count'];
    
    // Get the newly added comment details
    $sql_new_comment = "SELECT c.comment_text, u.username 
                       FROM comments c 
                       JOIN users u ON c.user_id = u.id 
                       WHERE c.post_id = ? AND c.user_id = ? 
                       ORDER BY c.created_at DESC LIMIT 1";
    $stmt = $conn->prepare($sql_new_comment);
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $new_comment = $stmt->get_result()->fetch_assoc();
    
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'comment_count' => $comment_count,
        'comment_html' => '<div class="comment">' . 
                         htmlspecialchars($new_comment['username']) . ': ' . 
                         htmlspecialchars($new_comment['comment_text']) . 
                         '</div>',
        'status' => 'success'
    ]);
    exit();
}

// Fetch posts with scoring algorithm
$sql_posts = "
    SELECT 
        p.post_id, 
        p.post_content, 
        p.media_url, 
        p.media_type, 
        p.created_at, 
        p.user_id, 
        u.username, 
        u.profile_picture, 
        u.verified,
        (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.post_id) AS like_count,
        (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.post_id AND c.user_id != 1) AS comment_count,
        IF(f.follower_id IS NOT NULL, 50, 0) AS follower_score,
        (
            IF(f.follower_id IS NOT NULL, 50, 0) + 
            (5 * (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.post_id)) + 
            (10 * (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.post_id AND c.user_id != 1)) - 
            DATEDIFF(NOW(), p.created_at)
        ) AS total_score
    FROM posts p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN followers f ON f.following_id = p.user_id AND f.follower_id = ?
    ORDER BY total_score DESC, p.created_at DESC
";
$stmt_posts = $conn->prepare($sql_posts);
$stmt_posts->bind_param("i", $user_id);
$stmt_posts->execute();
$result_posts = $stmt_posts->get_result();

if (!$result_posts) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .post-card { 
            background: white; 
            margin: 10px 0;
            padding: 15px;
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); 
        }
        .post-header { 
            display: flex; 
            align-items: center; 
            margin-bottom: 3px;
        }
        .user-info img { 
            width: 40px; 
            height: 40px; 
            border-radius: 50%; 
            margin-right: 8px;
            object-fit: cover; 
        }
        .user-info h6 { 
            margin: 0; 
            font-size: 14px; 
            display: inline; 
        }
        .post-content p { 
            margin: 0 0 8px;
            font-size: 16px; 
            line-height: 1.2;
        }
        .post-time { 
            color: #666; 
            font-size: 12px; 
            margin-bottom: 3px;
        }
        .post-media img { 
            max-width: 100%;
            max-height: 250px;
            height: auto;
            border-radius: 5px; 
            margin-bottom: 5px;
            object-fit: cover;
            cursor: pointer;
            transition: max-height 0.3s ease;
        }
        .post-media img.full-size { 
            max-height: none;
        }
        .post-media video { 
            max-width: 100%; 
            height: auto; 
            border-radius: 5px; 
            margin-bottom: 5px;
        }
        .post-actions { 
            display: flex; 
            align-items: center; 
            gap: 5px;
            margin-bottom: 3px;
        }
        .like-button { 
            background: #6a1b9a; 
            color: white; 
            padding: 6px 12px;
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
        }
        .like-button.liked { 
            background: #999; 
        }
        .like-count { 
            margin: 0; 
            font-size: 14px; 
        }
        .like-loading { 
            font-size: 14px; 
        }
        .comments-section { 
            margin-top: 2px;
        }
        .comments-section h4 { 
            font-size: 14px; 
            margin: 0 0 2px;
        }
        .show-comments-button { 
            font-size: 14px; 
            margin-bottom: 2px;
            display: block; 
        }
        .comment-form textarea { 
            width: 100%; 
            margin-top: 2px;
            padding: 6px;
            border-radius: 5px; 
            border: 1px solid #ddd; 
        }
        .submit-comment-button { 
            background: #6a1b9a; 
            color: white; 
            padding: 6px 12px;
            border: none; 
            border-radius: 5px; 
            margin-top: 5px;
            cursor: pointer; 
        }
        .verified-badge { 
            position: relative; 
            font-size: 13px; 
        }
        .verified-badge .fa-check { 
            position: absolute; 
            top: 50%; 
            left: 50%; 
            transform: translate(-50%, -50%); 
            font-size: 10px; 
            color: white; 
        }
        .gold-badge { 
            position: relative; 
            font-size: 13px; 
            color: #e0b20b; 
        }
        .gold-badge .fa-check { 
            position: absolute; 
            top: 50%; 
            left: 50%; 
            transform: translate(-50%, -50%); 
            font-size: 10px; 
            color: white; 
        }
        .black-badge { 
            position: relative; 
            font-size: 13px; 
            color: #000000; 
        }
        .black-badge .fa-check { 
            position: absolute; 
            top: 50%; 
            left: 50%; 
            transform: translate(-50%, -50%); 
            font-size: 10px; 
            color: white; 
        }
        .pink-badge { 
            position: relative; 
            font-size: 13px; 
            color: #e91e63; 
        }
        .pink-badge .fa-check { 
            position: absolute; 
            top: 50%; 
            left: 50%; 
            transform: translate(-50%, -50%); 
            font-size: 10px; 
            color: white; 
        }
    </style>
</head>
<body>
<div class="feed-container">
    <?php while ($post = $result_posts->fetch_assoc()): ?>
        <div class="post-card" id="post-<?php echo $post['post_id']; ?>">
            <?php
                $like_count = $post['like_count'];
                $sql_user_liked = "SELECT * FROM post_likes WHERE post_id = ? AND user_id = ?";
                $stmt = $conn->prepare($sql_user_liked);
                $stmt->bind_param("ii", $post['post_id'], $user_id);
                $stmt->execute();
                $user_liked_result = $stmt->get_result();
                $user_has_liked = $user_liked_result->num_rows > 0;
                $comment_count = $post['comment_count'];
            ?>

            <div class="post-header">
               <div class="user-info">
    <img src="/dashboard/<?php echo htmlspecialchars($post['profile_picture']); ?>" alt="avatar">
    <h6>
        <a href="view_profile.php?user_id=<?php echo htmlspecialchars($post['user_id']); ?>">
            <?php echo htmlspecialchars($post['username']); ?>
            <?php if (isset($post['verified']) && $post['verified'] == 1): ?>
                <span class="fa fa-circle verified-badge" title="Verified User">
                    <span class="fa fa-check"></span>
                </span>
            <?php endif; ?>
            <?php if (isset($post['verified']) && $post['verified'] == 2): ?>
                <span class="fa fa-circle gold-badge" title="Gold Verified User">
                    <span class="fa fa-check"></span>
                </span>
            <?php endif; ?>
            <?php if (isset($post['verified']) && $post['verified'] == 3): ?>
                <span class="fa fa-circle black-badge" title="Black Verified User">
                    <span class="fa fa-check"></span>
                </span>
            <?php endif; ?>
            <?php if (isset($post['verified']) && $post['verified'] == 4): ?>
                <span class="fa fa-circle pink-badge" title="Pink Verified User">
                    <span class="fa fa-check"></span>
                </span>
            <?php endif; ?>
        </a>
    </h6>
</div>
            </div>

            <div class="post-content">
                <p><?php echo nl2br(htmlspecialchars($post['post_content'])); ?></p>
            </div>

            <p class="post-time"><?php echo date('F j, Y, g:i a', strtotime($post['created_at'])); ?></p>

            <?php if ($post['media_type'] !== 'none'): ?>
                <div class="post-media">
                    <?php if ($post['media_type'] === 'image'): ?>
                        <img src="<?php echo htmlspecialchars($post['media_url']); ?>" alt="Post Image" class="post-image">
                    <?php elseif ($post['media_type'] === 'video'): ?>
                        <video controls>
                            <source src="<?php echo htmlspecialchars($post['media_url']); ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="post-actions">
                <form class="like-form" method="POST">
                    <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>" />
                    <button type="button" class="like-button <?php echo $user_has_liked ? 'liked' : ''; ?>" 
                            data-post-id="<?php echo $post['post_id']; ?>" 
                            <?php echo $user_has_liked ? 'disabled' : ''; ?>>
                        <i class="fa fa-thumbs-up"></i> Like
                    </button>
                </form>

                <p class="like-count" id="like-count-<?php echo $post['post_id']; ?>"><?php echo $like_count; ?> Likes</p>
                <div class="like-loading" id="like-loading-<?php echo $post['post_id']; ?>" style="display: none;">
                    Adding like...
                </div>
            </div>

            <div class="comments-section">
                <a href="view_comments.php?post_id=<?php echo $post['post_id']; ?>" class="show-comments-button">
                    Show Comments (<span class="comment-count" id="comment-count-<?php echo $post['post_id']; ?>"><?php echo $comment_count; ?></span>)
                </a>

                <form class="comment-form" data-post-id="<?php echo $post['post_id']; ?>">
                    <textarea name="comment_text" placeholder="Add a comment..." required></textarea>
                    <button type="submit" class="submit-comment-button" data-post-id="<?php echo $post['post_id']; ?>">
                     <span class="button-text" style="color: white;">Add Comment</span>

                        <span class="comment-loading" style="display: none;">Posting...</span>
                    </button>
                </form>

                <div class="comments-list" id="comments-list-<?php echo $post['post_id']; ?>">
                    <!-- Comments will be loaded here via AJAX -->
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Like button handler
    $(".like-button").on("click", function() {
        var postId = $(this).data("post-id");
        var $button = $(this);
        var $likeCount = $("#like-count-" + postId);
        var $loading = $("#like-loading-" + postId);

        if ($button.prop("disabled")) return;

        $loading.show();
        $button.prop("disabled", true);

        $.ajax({
            url: window.location.href,
            type: "POST",
            data: { 
                like_post: true,
                post_id: postId
            },
            dataType: "json",
            success: function(response) {
                $likeCount.text(response.like_count + " Likes");
                if (response.status === "liked") {
                    $button.addClass("liked");
                    $button.prop("disabled", true);
                } else {
                    $button.prop("disabled", false);
                }
                $loading.fadeOut(500);
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                console.log("Response:", xhr.responseText);
                alert("Failed to like post: " + error);
                $loading.fadeOut(500);
                $button.prop("disabled", false);
            }
        });
    });

    // Comment form handler
    $(".comment-form").on("submit", function(e) {
        e.preventDefault();
        var $form = $(this);
        var postId = $form.data("post-id");
        var commentText = $form.find("textarea").val();
        var $button = $form.find(".submit-comment-button");
        var $buttonText = $button.find(".button-text");
        var $loading = $button.find(".comment-loading");

        if (commentText.trim() === "") return;

        $button.prop("disabled", true);
        $buttonText.hide();
        $loading.show();

        $.ajax({
            url: window.location.href,
            type: "POST",
            data: {
                post_id: postId,
                comment_text: commentText
            },
            dataType: "json",
            success: function(response) {
                if (response.status === "success") {
                    // Add new comment to the top of the list
                    $("#comments-list-" + postId).prepend(response.comment_html);
                    // Update comment count
                    $("#comment-count-" + postId).text(response.comment_count);
                    // Clear textarea
                    $form.find("textarea").val("");
                }
                $loading.hide();
                $buttonText.show();
                $button.prop("disabled", false);
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                alert("Failed to post comment: " + error);
                $loading.hide();
                $buttonText.show();
                $button.prop("disabled", false);
            }
        });
    });

    // Image click handler to toggle full size
    $(".post-image").on("click", function() {
        $(this).toggleClass("full-size");
    });
});
</script>
</body>
</html>
